<?php
require_once __DIR__ . '/../connection.php';

class UserModel {
    private mysqli $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConn();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->conn->prepare("SELECT id, nama, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT id, nama, email, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    public function emailExists(string $email): bool {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function create(string $nama, string $email, string $password): bool {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->conn->prepare("INSERT INTO users (nama, email, password, role, created_at) VALUES (?, ?, ?, 'warga', NOW())");
        $stmt->bind_param('sss', $nama, $email, $hash);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getAll(string $search = ''): array {
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $this->conn->prepare("SELECT id, nama, email, role, created_at FROM users WHERE nama LIKE ? OR email LIKE ? ORDER BY id ASC");
            $stmt->bind_param('ss', $like, $like);
        } else {
            $stmt = $this->conn->prepare("SELECT id, nama, email, role, created_at FROM users ORDER BY id ASC");
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function update(int $id, string $nama, string $email, string $role): bool {
        $stmt = $this->conn->prepare("UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?");
        $stmt->bind_param('sssi', $nama, $email, $role, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function delete(int $id): bool {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function count(): int {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM users");
        return (int)($result->fetch_assoc()['total'] ?? 0);
    }
}
