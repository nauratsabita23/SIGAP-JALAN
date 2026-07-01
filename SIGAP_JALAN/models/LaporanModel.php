<?php
require_once __DIR__ . '/../connection.php';

class LaporanModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConn();
    }

    public function getAll(int $limit = 50): array {
        $stmt = $this->conn->prepare(
            "SELECT l.*,
                    u.nama AS pelapor_akun,
                    (SELECT COUNT(*) FROM laporan_likes ll WHERE ll.laporan_id = l.id) AS total_likes,
                    (SELECT COUNT(*) FROM laporan_komentar lk WHERE lk.laporan_id = l.id) AS total_komentar
             FROM laporan l
             LEFT JOIN users u ON l.user_id = u.id
             ORDER BY l.created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getByUserId(int $userId, int $limit = 20): array {
        $stmt = $this->conn->prepare(
            "SELECT l.*,
                    (SELECT COUNT(*) FROM laporan_likes ll WHERE ll.laporan_id = l.id) AS total_likes
             FROM laporan l WHERE l.user_id = ? ORDER BY l.created_at DESC LIMIT ?"
        );
        $stmt->bind_param('ii', $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function getById(int $id): ?array {
        $stmt = $this->conn->prepare(
            "SELECT l.*, u.nama AS pelapor_akun,
                    (SELECT COUNT(*) FROM laporan_likes ll WHERE ll.laporan_id = l.id) AS total_likes
             FROM laporan l LEFT JOIN users u ON l.user_id = u.id WHERE l.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function getStats(): array {
        $stats = [];
        $res = $this->conn->query("SELECT status, COUNT(*) AS jml FROM laporan GROUP BY status");
        while ($row = $res->fetch_assoc()) {
            $stats[$row['status']] = (int)$row['jml'];
        }
        return $stats;
    }

    public function getStatsTingkat(): array {
        $stats = [];
        $res = $this->conn->query("SELECT tingkat, COUNT(*) AS jml FROM laporan GROUP BY tingkat");
        while ($row = $res->fetch_assoc()) {
            $stats[$row['tingkat']] = (int)$row['jml'];
        }
        return $stats;
    }

    public function getRecentActivity(int $limit = 5): array {
        $stmt = $this->conn->prepare(
            "SELECT l.*, u.nama AS pelapor_akun,
                    (SELECT COUNT(*) FROM laporan_likes ll WHERE ll.laporan_id = l.id) AS total_likes
             FROM laporan l
             LEFT JOIN users u ON l.user_id = u.id
             ORDER BY l.updated_at DESC, l.created_at DESC
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    // Buat laporan — tidak perlu user_id (bisa anonim)
    public function create(?int $userId, string $namaPelapor, string $jenis, string $deskripsi, string $tingkat, string $lokasi, ?float $lat, ?float $lon, ?string $foto): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporan (user_id, nama_pelapor, jenis_kerusakan, deskripsi, tingkat, status, lokasi_nama, latitude, longitude, foto, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'menunggu', ?, ?, ?, ?, NOW(), NOW())"
        );
        $stmt->bind_param('isssssdds', $userId, $namaPelapor, $jenis, $deskripsi, $tingkat, $lokasi, $lat, $lon, $foto);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getLastInsertId(): int {
        return (int)$this->conn->insert_id;
    }

    // Multi-foto
    public function addFoto(int $laporanId, string $namaFile, int $urutan = 0): bool {
        $stmt = $this->conn->prepare(
            "INSERT INTO laporan_foto (laporan_id, foto, urutan) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('isi', $laporanId, $namaFile, $urutan);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getFotoByLaporanId(int $laporanId): array {
        $stmt = $this->conn->prepare(
            "SELECT * FROM laporan_foto WHERE laporan_id = ? ORDER BY urutan ASC"
        );
        $stmt->bind_param('i', $laporanId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->conn->prepare("UPDATE laporan SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateTingkat(int $id, string $tingkat): bool {
        if (!in_array($tingkat, ['ringan','sedang','berat'])) return false;
        $stmt = $this->conn->prepare("UPDATE laporan SET tingkat = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $tingkat, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM laporan WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // === LIKES ===
    public function toggleLike(int $laporanId, string $ip, ?int $userId): array {
        // Cek apakah sudah liked
        $stmt = $this->conn->prepare("SELECT id FROM laporan_likes WHERE laporan_id = ? AND ip_address = ?");
        $stmt->bind_param('is', $laporanId, $ip);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Unlike
            $stmt = $this->conn->prepare("DELETE FROM laporan_likes WHERE laporan_id = ? AND ip_address = ?");
            $stmt->bind_param('is', $laporanId, $ip);
            $stmt->execute();
            $stmt->close();
            $liked = false;
        } else {
            // Like
            $stmt = $this->conn->prepare("INSERT INTO laporan_likes (laporan_id, user_id, ip_address) VALUES (?, ?, ?)");
            $stmt->bind_param('iis', $laporanId, $userId, $ip);
            $stmt->execute();
            $stmt->close();
            $liked = true;
        }

        $count = $this->getLikeCount($laporanId);
        return ['liked' => $liked, 'count' => $count];
    }

    public function getLikeCount(int $laporanId): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) AS c FROM laporan_likes WHERE laporan_id = ?");
        $stmt->bind_param('i', $laporanId);
        $stmt->execute();
        $c = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        return $c;
    }

    public function isLiked(int $laporanId, string $ip): bool {
        $stmt = $this->conn->prepare("SELECT id FROM laporan_likes WHERE laporan_id = ? AND ip_address = ?");
        $stmt->bind_param('is', $laporanId, $ip);
        $stmt->execute();
        $found = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $found;
    }

    // === KOMENTAR ===
    public function addKomentar(int $laporanId, ?int $userId, string $nama, string $komentar): bool {
        $stmt = $this->conn->prepare("INSERT INTO laporan_komentar (laporan_id, user_id, nama, komentar) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('iiss', $laporanId, $userId, $nama, $komentar);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getKomentar(int $laporanId): array {
        $stmt = $this->conn->prepare(
            "SELECT lk.*, u.nama AS nama_akun
             FROM laporan_komentar lk
             LEFT JOIN users u ON lk.user_id = u.id
             WHERE lk.laporan_id = ?
             ORDER BY lk.created_at ASC"
        );
        $stmt->bind_param('i', $laporanId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
}
