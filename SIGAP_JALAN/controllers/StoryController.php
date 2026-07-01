<?php
require_once __DIR__ . '/../models/StoryModel.php';

class StoryController {
    private StoryModel $model;

    public function __construct() {
        $this->model = new StoryModel();
    }

    public function getActiveGrouped(): array {
        return $this->model->getActiveStoriesGrouped();
    }

    public function recordView(int $storyId, string $ip): void {
        if ($storyId > 0) {
            $this->model->addView($storyId, $ip);
        }
    }

    // Bisa tanpa login (userId = null, namaGuest diisi)
    public function create(?int $userId, ?string $caption, array $files, ?string $namaGuest = null): array {
        $foto    = null;
        $caption = trim($caption ?? '');

        if (!empty($files['story_foto']['name'])) {
            $ext     = strtolower(pathinfo($files['story_foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                return ['ok' => false, 'msg' => 'Format file tidak didukung (jpg/png/webp/gif).'];
            }
            if ($files['story_foto']['size'] > 5 * 1024 * 1024) {
                return ['ok' => false, 'msg' => 'Ukuran file maksimal 5 MB.'];
            }
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fname = 'story_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($files['story_foto']['tmp_name'], $uploadDir . $fname)) {
                return ['ok' => false, 'msg' => 'Gagal mengupload foto.'];
            }
            $foto = $fname;
        }

        if (!$foto && empty($caption)) {
            return ['ok' => false, 'msg' => 'Story harus berisi foto atau caption.'];
        }

        $nama = $userId ? null : (trim($namaGuest) ?: 'Warga Anonim');
        $ok   = $this->model->create($userId, $foto, $caption ?: null, $nama);
        return $ok
            ? ['ok' => true,  'msg' => 'Story berhasil dibagikan!']
            : ['ok' => false, 'msg' => 'Gagal menyimpan story.'];
    }

    public function delete(int $storyId, int $userId): array {
        $ok = $this->model->delete($storyId, $userId);
        return $ok
            ? ['ok' => true,  'msg' => 'Story berhasil dihapus.']
            : ['ok' => false, 'msg' => 'Gagal menghapus story.'];
    }
}
