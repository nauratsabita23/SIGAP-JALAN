<?php
require_once __DIR__ . '/../models/UserModel.php';

class UserController {
    private UserModel $model;

    public function __construct() {
        $this->model = new UserModel();
    }

    public function getAll(string $search = ''): array {
        return $this->model->getAll($search);
    }

    public function getById(int $id): ?array {
        return $this->model->findById($id);
    }

    // Alias agar konsisten dengan panggilan di users.php
    public function findById(int $id): ?array {
        return $this->model->findById($id);
    }

    public function update(int $id, string $nama, string $email, string $role, int $currentUserId): array {
        if (empty($nama) || empty($email)) {
            return ['ok' => false, 'msg' => 'Nama dan email tidak boleh kosong.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Format email tidak valid.'];
        }
        $ok = $this->model->update($id, $nama, $email, $role);
        return $ok
            ? ['ok' => true, 'msg' => 'Data pengguna berhasil diperbarui.']
            : ['ok' => false, 'msg' => 'Gagal update data.'];
    }

    public function delete(int $id, int $currentUserId): array {
        if ($id === $currentUserId) {
            return ['ok' => false, 'msg' => 'Tidak bisa menghapus akun sendiri.'];
        }
        $ok = $this->model->delete($id);
        return $ok
            ? ['ok' => true, 'msg' => 'Pengguna berhasil dihapus.']
            : ['ok' => false, 'msg' => 'Gagal menghapus pengguna.'];
    }

    public function count(): int {
        return $this->model->count();
    }
}
