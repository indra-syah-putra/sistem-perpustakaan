-- ============================================================
-- MIGRATION: Pengaturan aplikasi
-- ============================================================

CREATE TABLE IF NOT EXISTS pengaturan (
    id_setting INT AUTO_INCREMENT PRIMARY KEY,
    nama_setting VARCHAR(50) NOT NULL UNIQUE,
    nilai_setting VARCHAR(255) NOT NULL,
    deskripsi VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pengaturan (nama_setting, nilai_setting, deskripsi) VALUES
('denda_per_hari', '1000', 'Denda keterlambatan per hari (Rupiah)'),
('max_pinjam', '3', 'Maksimal buku yang bisa dipinjam per anggota'),
('max_hari_pinjam', '14', 'Maksimal lama peminjaman (hari)')
ON DUPLICATE KEY UPDATE nama_setting = VALUES(nama_setting);
