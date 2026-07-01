<?php
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private UserModel $userModel;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->userModel = new UserModel();
    }

    public function isLoggedIn(): bool {
        return isset($_SESSION['user']);
    }

    public function getUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    public function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public function requireAdmin(): void {
        $this->requireLogin();
        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            header('Location: index.php');
            exit;
        }
    }

    public function login(string $email, string $password): array {
        if (empty($email) || empty($password)) {
            return ['ok' => false, 'msg' => 'Email dan password wajib diisi.'];
        }
        $user = $this->userModel->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'    => $user['id'],
                'nama'  => $user['nama'],
                'email' => $user['email'],
                'role'  => $user['role'],
                'avatar'=> $user['avatar'] ?? null,
            ];
            return ['ok' => true];
        }
        return ['ok' => false, 'msg' => 'Email atau password salah.'];
    }

    public function register(string $nama, string $email, string $password, string $konfirm): array {
        if (empty($nama) || empty($email) || empty($password)) {
            return ['ok' => false, 'msg' => 'Semua field wajib diisi.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'msg' => 'Format email tidak valid.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'msg' => 'Password minimal 6 karakter.'];
        }
        if ($password !== $konfirm) {
            return ['ok' => false, 'msg' => 'Konfirmasi password tidak cocok.'];
        }
        if ($this->userModel->emailExists($email)) {
            return ['ok' => false, 'msg' => 'Email sudah terdaftar.'];
        }
        $ok = $this->userModel->create($nama, $email, $password);
        return $ok
            ? ['ok' => true, 'msg' => 'Akun berhasil dibuat! Silakan login.']
            : ['ok' => false, 'msg' => 'Gagal mendaftar. Coba lagi.'];
    }

    public function logout(): void {
        session_destroy();
        header('Location: index.php');
        exit;
    }
}
