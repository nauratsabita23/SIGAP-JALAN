-- ============================================================
-- SIGAP JALAN — Database Schema (Versi Baru)
-- ============================================================

CREATE DATABASE IF NOT EXISTS sigap_jalan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigap_jalan;

-- Tabel users (warga & admin)
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nama       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('warga','admin') NOT NULL DEFAULT 'warga',
    avatar     VARCHAR(200)  NULL,
    bio        VARCHAR(300)  NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel laporan (beranda publik - tidak perlu login)
CREATE TABLE IF NOT EXISTS laporan (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NULL COMMENT 'NULL = laporan anonim dari warga tanpa login',
    nama_pelapor    VARCHAR(100)  NOT NULL DEFAULT 'Warga Anonim',
    jenis_kerusakan VARCHAR(200)  NOT NULL,
    deskripsi       TEXT,
    tingkat         ENUM('ringan','sedang','berat') NOT NULL DEFAULT 'sedang',
    status          ENUM('menunggu','diproses','selesai','ditolak') NOT NULL DEFAULT 'menunggu',
    lokasi_nama     VARCHAR(300),
    latitude        DOUBLE,
    longitude       DOUBLE,
    foto            VARCHAR(200),
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel likes pada laporan (seperti Instagram)
CREATE TABLE IF NOT EXISTS laporan_likes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    user_id    INT NULL COMMENT 'NULL = like dari sesi anonim',
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (laporan_id, ip_address),
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel komentar pada laporan
CREATE TABLE IF NOT EXISTS laporan_komentar (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    user_id    INT NULL,
    nama       VARCHAR(100) NOT NULL DEFAULT 'Warga',
    komentar   TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel story (24 jam, seperti Instagram/Facebook)
CREATE TABLE IF NOT EXISTS stories (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    foto       VARCHAR(200),
    caption    VARCHAR(500),
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel penonton story
CREATE TABLE IF NOT EXISTS story_views (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    story_id   INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_view (story_id, ip_address),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- Password untuk semua akun: password
-- ============================================================

INSERT IGNORE INTO users (id, nama, email, password, role, bio) VALUES
(1, 'Administrator',  'admin@sigap.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin SIGAP Jalan Kota Parepare'),
(2, 'Budi Santoso',   'budi@warga.id',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warga', 'Warga Parepare, peduli infrastruktur'),
(3, 'Siti Rahayu',    'siti@warga.id',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'warga', 'Ibu rumah tangga, aktif melaporkan jalan rusak');

INSERT IGNORE INTO laporan (id, user_id, nama_pelapor, jenis_kerusakan, deskripsi, tingkat, status, lokasi_nama, latitude, longitude, created_at) VALUES
(1, 2, 'Budi Santoso',  'Lubang Besar',   'Terdapat lubang sangat besar di tengah jalan, sangat berbahaya bagi pengendara motor.', 'berat',  'diproses', 'Jl. Pemuda, Parepare',           -4.0095, 119.623, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(2, 2, 'Budi Santoso',  'Aspal Retak',    'Aspal retak panjang di bahu jalan kiri sepanjang 50 meter.',                             'sedang', 'selesai',  'Jl. Jend Ahmad Yani, Parepare',  -4.0112, 119.629, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 3, 'Siti Rahayu',   'Drainase Rusak', 'Saluran drainase tersumbat dan rusak menyebabkan genangan air saat hujan.',               'ringan', 'selesai',  'Jl. Swaka Alam Lestari',         -4.0134, 119.631, DATE_SUB(NOW(), INTERVAL 10 HOUR)),
(4, NULL, 'Warga Anonim','Lubang Sedang',  'Lubang besar di tengah jalur kiri, berbahaya bagi pengendara motor.',                    'berat',  'menunggu', 'Jl. Mangga, Parepare',           -4.0078, 119.618, DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Tabel multi-foto per laporan
CREATE TABLE IF NOT EXISTS laporan_foto (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    foto       VARCHAR(200) NOT NULL,
    urutan     INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Story guest (tanpa login): user_id nullable, tambah nama_guest
ALTER TABLE stories MODIFY COLUMN user_id INT NULL;
ALTER TABLE stories ADD COLUMN IF NOT EXISTS nama_guest VARCHAR(100) NULL;
