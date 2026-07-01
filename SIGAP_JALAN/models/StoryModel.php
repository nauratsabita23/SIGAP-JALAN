<?php
require_once __DIR__ . '/../connection.php';

class StoryModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConn();
    }

    // Ambil stories aktif (belum expired) — termasuk guest
    public function getActiveStories(): array {
        $result = $this->conn->query(
            "SELECT s.*,
                    COALESCE(u.nama, s.nama_guest, 'Warga Anonim') AS nama,
                    u.avatar
             FROM stories s
             LEFT JOIN users u ON s.user_id = u.id
             WHERE s.expires_at > NOW()
             ORDER BY s.created_at DESC"
        );
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Ambil stories per user/guest (grouped)
    public function getActiveStoriesGrouped(): array {
        $stories = $this->getActiveStories();
        $grouped = [];
        foreach ($stories as $s) {
            // Gunakan user_id jika ada, atau nama guest sebagai key
            $key = $s['user_id'] ? 'u_' . $s['user_id'] : 'g_' . md5($s['nama']);
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'user_id' => $s['user_id'],
                    'nama'    => $s['nama'],
                    'avatar'  => $s['avatar'] ?? null,
                    'stories' => [],
                ];
            }
            $grouped[$key]['stories'][] = $s;
        }
        return array_values($grouped);
    }

    public function create(?int $userId, ?string $foto, ?string $caption, ?string $namaGuest = null): bool {
        $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        $stmt = $this->conn->prepare(
            "INSERT INTO stories (user_id, foto, caption, expires_at, nama_guest) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $userId, $foto, $caption, $expires, $namaGuest);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id, int $userId): bool {
        $stmt = $this->conn->prepare("DELETE FROM stories WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $id, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function addView(int $storyId, string $ip): void {
        $stmt = $this->conn->prepare(
            "INSERT IGNORE INTO story_views (story_id, ip_address) VALUES (?, ?)"
        );
        $stmt->bind_param('is', $storyId, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
