<?php
class Database {
    private static ?Database $instance = null;
    public mysqli $conn;

    private string $host = 'localhost';
    private string $user = 'root';
    private string $pass = '';
    private string $name = 'sigap_jalan';

    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->name);
        if ($this->conn->connect_error) {
            die(json_encode([
                'status'  => 'error',
                'message' => 'Koneksi database gagal: ' . $this->conn->connect_error
            ]));
        }
        $this->conn->set_charset('utf8mb4');
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConn(): mysqli {
        return $this->conn;
    }
}

// Global shortcut for legacy compatibility
$conn = Database::getInstance()->getConn();
