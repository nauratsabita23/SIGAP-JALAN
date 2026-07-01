-- Jalankan query ini jika tabel laporan_foto belum ada
-- Untuk support multi-foto per laporan

CREATE TABLE IF NOT EXISTS laporan_foto (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    foto       VARCHAR(200) NOT NULL,
    urutan     INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (laporan_id) REFERENCES laporan(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Story bisa dibuat tanpa login (user_id NULL)
ALTER TABLE stories MODIFY COLUMN user_id INT NULL;
ALTER TABLE stories ADD COLUMN IF NOT EXISTS nama_guest VARCHAR(100) NULL;
