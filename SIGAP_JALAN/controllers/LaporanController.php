<?php
require_once __DIR__ . '/../models/LaporanModel.php';

class LaporanController {
    private LaporanModel $model;

    public function __construct() {
        $this->model = new LaporanModel();
    }

    // Submit laporan — user_id bisa null (tanpa login), support multi-foto
    public function submit(?int $userId, string $namaPelapor, array $post, array $files): array {
        $jenis   = trim($post['jenis_kerusakan'] ?? '');
        $deskr   = trim($post['deskripsi'] ?? '');
        $tingkat = $post['tingkat'] ?? 'sedang';
        $lokasi  = trim($post['lokasi_nama'] ?? '');
        $lat     = ($post['latitude'] ?? '') !== '' ? (float)$post['latitude'] : null;
        $lon     = ($post['longitude'] ?? '') !== '' ? (float)$post['longitude'] : null;

        if (empty($jenis)) {
            return ['ok' => false, 'msg' => 'Jenis kerusakan wajib diisi.'];
        }
        if (empty($lokasi)) {
            return ['ok' => false, 'msg' => 'Lokasi wajib diisi.'];
        }
        if (!in_array($tingkat, ['ringan', 'sedang', 'berat'])) {
            $tingkat = 'sedang';
        }

        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

        // Kumpulkan semua foto (bisa multiple)
        $fotoFiles = [];
        if (!empty($files['foto']['name'])) {
            // Cek apakah multiple atau single
            if (is_array($files['foto']['name'])) {
                foreach ($files['foto']['name'] as $i => $fname) {
                    if (empty($fname)) continue;
                    $fotoFiles[] = [
                        'name'     => $fname,
                        'tmp_name' => $files['foto']['tmp_name'][$i],
                        'size'     => $files['foto']['size'][$i],
                        'error'    => $files['foto']['error'][$i],
                    ];
                }
            } else {
                $fotoFiles[] = $files['foto'];
            }
        }

        // Validasi & upload semua foto
        $uploadedFotos = [];
        foreach ($fotoFiles as $f) {
            if ($f['error'] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($f['size'] > 5 * 1024 * 1024) continue;
            $namaFile = uniqid('foto_') . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $uploadDir . $namaFile)) {
                $uploadedFotos[] = $namaFile;
            }
        }

        // Foto pertama disimpan di kolom foto (backward compat), sisanya di laporan_foto
        $fotoUtama = $uploadedFotos[0] ?? null;
        $ok = $this->model->create($userId, $namaPelapor, $jenis, $deskr, $tingkat, $lokasi, $lat, $lon, $fotoUtama);
        if (!$ok) return ['ok' => false, 'msg' => 'Gagal menyimpan laporan.'];

        // Simpan semua foto ke tabel laporan_foto
        if (!empty($uploadedFotos)) {
            $laporanId = $this->model->getLastInsertId();
            foreach ($uploadedFotos as $i => $fname) {
                $this->model->addFoto($laporanId, $fname, $i);
            }
        }

        return ['ok' => true, 'msg' => 'Laporan berhasil dikirim! Terima kasih.'];
    }

    public function getFotoLaporan(int $laporanId): array {
        return $this->model->getFotoByLaporanId($laporanId);
    }

    public function updateStatus(int $id, string $status): bool {
        $allowed = ['menunggu', 'diproses', 'selesai', 'ditolak'];
        if (!in_array($status, $allowed)) return false;
        return $this->model->updateStatus($id, $status);
    }

    public function updateTingkat(int $id, string $tingkat): bool {
        return $this->model->updateTingkat($id, $tingkat);
    }

    public function delete(int $id): bool {
        return $this->model->delete($id);
    }

    public function getAll(int $limit = 20): array {
        return $this->model->getAll($limit);
    }

    public function getById(int $id): ?array {
        return $this->model->getById($id);
    }

    public function getStats(): array {
        return $this->model->getStats();
    }

    public function getStatsTingkat(): array {
        return $this->model->getStatsTingkat();
    }

    public function getRecentActivity(int $limit = 5): array {
        return $this->model->getRecentActivity($limit);
    }

    public function getByUserId(int $userId): array {
        return $this->model->getByUserId($userId);
    }

    // === LIKE ===
    public function toggleLike(int $laporanId, string $ip, ?int $userId): array {
        if ($laporanId <= 0) return ['error' => 'invalid'];
        return $this->model->toggleLike($laporanId, $ip, $userId);
    }

    public function isLiked(int $laporanId, string $ip): bool {
        return $this->model->isLiked($laporanId, $ip);
    }

    public function getLikeCount(int $laporanId): int {
        return $this->model->getLikeCount($laporanId);
    }

    // === KOMENTAR ===
    public function addKomentar(int $laporanId, ?int $userId, string $nama, string $komentar): array {
        $nama    = trim($nama) ?: 'Warga';
        $komentar = trim($komentar);
        if ($laporanId <= 0 || empty($komentar)) {
            return ['ok' => false, 'msg' => 'Data tidak lengkap.'];
        }
        $ok = $this->model->addKomentar($laporanId, $userId, htmlspecialchars($nama), htmlspecialchars($komentar));
        if (!$ok) return ['ok' => false, 'msg' => 'Gagal menyimpan komentar.'];
        return ['ok' => true, 'data' => $this->model->getKomentar($laporanId)];
    }

    public function getKomentar(int $laporanId): array {
        return $this->model->getKomentar($laporanId);
    }

    // === FEED DENGAN LIKE STATUS ===
    public function getFeed(int $limit, string $ip): array {
        $list = $this->model->getAll($limit);
        foreach ($list as &$l) {
            $l['is_liked'] = $this->model->isLiked($l['id'], $ip);
        }
        unset($l);
        return $list;
    }

    public function getStatusTimeline(array $laporan): array {
        $steps = [
            ['key' => 'menunggu',  'label' => 'Laporan Diterima',                  'icon' => '✓', 'desc' => 'Laporan berhasil dikirim dan tercatat dalam sistem.'],
            ['key' => 'diproses',  'label' => 'Verifikasi & Tindak Lanjut',        'icon' => '⚙', 'desc' => 'Laporan diverifikasi dan diteruskan ke dinas terkait.'],
            ['key' => 'perbaikan', 'label' => 'Perbaikan Lapangan',                'icon' => '🔧', 'desc' => 'Tim sedang melakukan perbaikan di lokasi.'],
            ['key' => 'selesai',   'label' => 'Selesai Diperbaiki',                'icon' => '✓', 'desc' => 'Perbaikan telah selesai dilaksanakan.'],
        ];
        $statusOrder = ['menunggu' => 1, 'diproses' => 2, 'selesai' => 4, 'ditolak' => 99];
        $keyOrder    = ['menunggu' => 1, 'diproses' => 2, 'perbaikan' => 3, 'selesai' => 4];
        $current = $statusOrder[$laporan['status']] ?? 1;

        foreach ($steps as &$step) {
            $stepNum = $keyOrder[$step['key']] ?? 1;
            $step['done']   = ($current >= $stepNum && $laporan['status'] !== 'ditolak') || $laporan['status'] === 'selesai';
            $step['active'] = false;
            if ($laporan['status'] === 'menunggu' && $step['key'] === 'menunggu') $step['active'] = true;
            if ($laporan['status'] === 'diproses' && $step['key'] === 'diproses')  $step['active'] = true;
            if ($laporan['status'] === 'selesai'  && $step['key'] === 'selesai')   $step['active'] = true;
            if ($step['key'] === 'menunggu') $step['done'] = true;
        }
        return $steps;
    }
}
