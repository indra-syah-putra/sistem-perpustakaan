-- ============================================================
-- DATABASE: perpustakaan
-- Sistem Informasi Perpustakaan (PHP Native + MySQL)
-- Untuk Sertifikasi Database Administrator
-- ============================================================

CREATE DATABASE IF NOT EXISTS perpustakaan;
USE perpustakaan;

-- ============================================================
-- TABEL USER (untuk login)
-- ============================================================
CREATE TABLE user (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin', 'petugas') NOT NULL DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL KELAS (lookup)
-- ============================================================
CREATE TABLE kelas (
    id_kelas INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(10) NOT NULL UNIQUE,
    tingkatan INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL KATEGORI
-- ============================================================
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL BUKU
-- ============================================================
CREATE TABLE buku (
    id_buku INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    pengarang VARCHAR(150) NOT NULL,
    penerbit VARCHAR(150),
    tahun_terbit YEAR,
    isbn VARCHAR(20) UNIQUE,
    stok INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL BUKU_KATEGORI (relasi many-to-many)
-- ============================================================
CREATE TABLE buku_kategori (
    id_buku INT NOT NULL,
    id_kategori INT NOT NULL,
    PRIMARY KEY (id_buku, id_kategori),
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL ANGGOTA
-- ============================================================
CREATE TABLE anggota (
    id_anggota INT AUTO_INCREMENT PRIMARY KEY,
    no_anggota VARCHAR(20) NOT NULL UNIQUE,
    nisn VARCHAR(20) UNIQUE,
    id_kelas INT,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(100),
    no_telp VARCHAR(20),
    alamat TEXT,
    tgl_daftar DATE NOT NULL DEFAULT (CURRENT_DATE),
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kelas) REFERENCES kelas(id_kelas) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PEMINJAMAN
-- ============================================================
CREATE TABLE peminjaman (
    id_peminjaman INT AUTO_INCREMENT PRIMARY KEY,
    id_anggota INT NOT NULL,
    id_buku INT NOT NULL,
    tgl_pinjam DATE NOT NULL DEFAULT (CURRENT_DATE),
    tgl_jatuh_tempo DATE NOT NULL,
    tgl_kembali DATE NULL,
    status ENUM('dipinjam', 'dikembalikan', 'terlambat') NOT NULL DEFAULT 'dipinjam',
    denda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_anggota) REFERENCES anggota(id_anggota) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_buku) REFERENCES buku(id_buku) ON DELETE RESTRICT ON UPDATE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PEMBAYARAN DENDA (riwayat pembayaran)
-- ============================================================
CREATE TABLE pembayaran_denda (
    id_bayar INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT NOT NULL,
    jumlah DECIMAL(10,2) NOT NULL,
    tgl_bayar DATE NOT NULL DEFAULT (CURRENT_DATE),
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL LOG PEMINJAMAN (untuk trigger)
-- ============================================================
CREATE TABLE log_peminjaman (
    id_log INT AUTO_INCREMENT PRIMARY KEY,
    id_peminjaman INT,
    aksi VARCHAR(50) NOT NULL,
    detail TEXT,
    FOREIGN KEY (id_peminjaman) REFERENCES peminjaman(id_peminjaman) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABEL DOKUMEN (metadata dokumen)
-- ============================================================
CREATE TABLE dokumen (
    id_dokumen INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    jenis_file VARCHAR(50) NOT NULL,
    lokasi_file VARCHAR(255),
    versi VARCHAR(20) DEFAULT '1.0',
    tgl_upload DATE NOT NULL DEFAULT (CURRENT_DATE),
    id_user INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABEL PENGATURAN APLIKASI
-- ============================================================
CREATE TABLE pengaturan (
    id_setting INT AUTO_INCREMENT PRIMARY KEY,
    nama_setting VARCHAR(50) NOT NULL UNIQUE,
    nilai_setting VARCHAR(255) NOT NULL,
    deskripsi VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- INDEX
-- ============================================================
CREATE INDEX idx_peminjaman_status ON peminjaman(status);
CREATE INDEX idx_peminjaman_tgl ON peminjaman(tgl_pinjam);
CREATE INDEX idx_peminjaman_anggota ON peminjaman(id_anggota);
CREATE INDEX idx_peminjaman_buku ON peminjaman(id_buku);
CREATE INDEX idx_buku_judul ON buku(judul);
CREATE INDEX idx_anggota_nama ON anggota(nama);
CREATE INDEX idx_anggota_nisn ON anggota(nisn);
CREATE INDEX idx_anggota_id_kelas ON anggota(id_kelas);

-- ============================================================
-- VIEW
-- ============================================================

-- View: peminjaman aktif (yang belum dikembalikan)
CREATE OR REPLACE VIEW v_peminjaman_aktif AS
SELECT 
    p.id_peminjaman,
    a.no_anggota,
    a.nama AS nama_anggota,
    b.judul AS judul_buku,
    p.tgl_pinjam,
    p.tgl_jatuh_tempo,
    DATEDIFF(CURDATE(), p.tgl_jatuh_tempo) AS hari_terlambat,
    CASE 
        WHEN CURDATE() > p.tgl_jatuh_tempo 
        THEN DATEDIFF(CURDATE(), p.tgl_jatuh_tempo) * (SELECT COALESCE((SELECT CAST(nilai_setting AS UNSIGNED) FROM pengaturan WHERE nama_setting = 'denda_per_hari'), 1000))
        ELSE 0 
    END AS denda_hitung
FROM peminjaman p
JOIN anggota a ON p.id_anggota = a.id_anggota
JOIN buku b ON p.id_buku = b.id_buku
WHERE p.status = 'dipinjam';

-- View: riwayat peminjaman lengkap
CREATE OR REPLACE VIEW v_riwayat_peminjaman AS
SELECT 
    p.id_peminjaman,
    a.no_anggota,
    a.nama AS nama_anggota,
    b.judul AS judul_buku,
    b.isbn,
    p.tgl_pinjam,
    p.tgl_jatuh_tempo,
    p.tgl_kembali,
    p.status,
    p.denda
FROM peminjaman p
JOIN anggota a ON p.id_anggota = a.id_anggota
JOIN buku b ON p.id_buku = b.id_buku
ORDER BY p.tgl_pinjam DESC;

-- View: buku dengan kategori
CREATE OR REPLACE VIEW v_buku_kategori AS
SELECT 
    b.id_buku,
    b.judul,
    b.pengarang,
    b.penerbit,
    b.tahun_terbit,
    b.isbn,
    b.stok,
    GROUP_CONCAT(k.nama_kategori SEPARATOR ', ') AS kategori
FROM buku b
LEFT JOIN buku_kategori bk ON b.id_buku = bk.id_buku
LEFT JOIN kategori k ON bk.id_kategori = k.id_kategori
GROUP BY b.id_buku;

-- View: statistik dashboard
CREATE OR REPLACE VIEW v_statistik AS
SELECT
    (SELECT COUNT(*) FROM buku) AS total_buku,
    (SELECT COUNT(*) FROM anggota WHERE status = 'aktif') AS total_anggota_aktif,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam') AS buku_dipinjam,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'dikembalikan' AND MONTH(tgl_kembali) = MONTH(CURDATE()) AND YEAR(tgl_kembali) = YEAR(CURDATE())) AS pengembalian_bulan_ini,
    (SELECT COUNT(*) FROM peminjaman WHERE status = 'dipinjam' AND tgl_jatuh_tempo < CURDATE()) AS peminjaman_terlambat;

-- ============================================================
-- FUNCTION: hitung_denda
-- ============================================================
DELIMITER $$
CREATE FUNCTION hitung_denda(tgl_jatuh_tempo DATE, tgl_kembali DATE)
RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE hari_terlambat INT;
    DECLARE total_denda DECIMAL(10,2);
    
    SET hari_terlambat = DATEDIFF(tgl_kembali, tgl_jatuh_tempo);
    
    IF hari_terlambat > 0 THEN
        SET total_denda = hari_terlambat * (SELECT COALESCE((SELECT CAST(nilai_setting AS UNSIGNED) FROM pengaturan WHERE nama_setting = 'denda_per_hari'), 1000));
    ELSE
        SET total_denda = 0;
    END IF;
    
    RETURN total_denda;
END$$
DELIMITER ;

-- ============================================================
-- STORED PROCEDURE: pinjam_buku
-- ============================================================
DELIMITER $$
CREATE PROCEDURE pinjam_buku(
    IN p_id_anggota INT,
    IN p_id_buku INT,
    IN p_lama_hari INT,
    OUT p_status VARCHAR(20),
    OUT p_pesan VARCHAR(255)
)
BEGIN
    DECLARE stok_tersedia INT;
    DECLARE status_anggota VARCHAR(20);
    
    SELECT status INTO status_anggota FROM anggota WHERE id_anggota = p_id_anggota;
    
    IF status_anggota != 'aktif' THEN
        SET p_status = 'gagal';
        SET p_pesan = 'Anggota tidak aktif';
    ELSE
        SELECT stok INTO stok_tersedia FROM buku WHERE id_buku = p_id_buku;
        
        IF stok_tersedia > 0 THEN
            START TRANSACTION;
            
            INSERT INTO peminjaman (id_anggota, id_buku, tgl_pinjam, tgl_jatuh_tempo, status)
            VALUES (p_id_anggota, p_id_buku, CURDATE(), DATE_ADD(CURDATE(), INTERVAL p_lama_hari DAY), 'dipinjam');
            
            UPDATE buku SET stok = stok - 1 WHERE id_buku = p_id_buku;
            
            INSERT INTO log_peminjaman (id_peminjaman, aksi, detail)
            VALUES (LAST_INSERT_ID(), 'PINJAM', CONCAT('Anggota ', p_id_anggota, ' meminjam buku ', p_id_buku, ' selama ', p_lama_hari, ' hari'));
            
            COMMIT;
            
            SET p_status = 'sukses';
            SET p_pesan = 'Peminjaman berhasil';
        ELSE
            SET p_status = 'gagal';
            SET p_pesan = 'Stok buku habis';
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- STORED PROCEDURE: kembalikan_buku
-- ============================================================
DELIMITER $$
CREATE PROCEDURE kembalikan_buku(
    IN p_id_peminjaman INT,
    OUT p_status VARCHAR(20),
    OUT p_pesan VARCHAR(255),
    OUT p_denda DECIMAL(10,2)
)
BEGIN
    DECLARE v_id_buku INT;
    DECLARE v_tgl_jatuh_tempo DATE;
    DECLARE v_status_pinjam VARCHAR(20);
    
    SELECT id_buku, tgl_jatuh_tempo, status INTO v_id_buku, v_tgl_jatuh_tempo, v_status_pinjam
    FROM peminjaman WHERE id_peminjaman = p_id_peminjaman;
    
    IF v_status_pinjam = 'dikembalikan' THEN
        SET p_status = 'gagal';
        SET p_pesan = 'Buku sudah dikembalikan sebelumnya';
        SET p_denda = 0;
    ELSE
        SET p_denda = hitung_denda(v_tgl_jatuh_tempo, CURDATE());
        
        START TRANSACTION;
        
        UPDATE peminjaman 
        SET tgl_kembali = CURDATE(), 
            status = IF(p_denda > 0, 'terlambat', 'dikembalikan'), 
            denda = p_denda
        WHERE id_peminjaman = p_id_peminjaman;
        
        UPDATE buku SET stok = stok + 1 WHERE id_buku = v_id_buku;
        
        INSERT INTO log_peminjaman (id_peminjaman, aksi, detail)
        VALUES (p_id_peminjaman, 'KEMBALI', CONCAT('Buku dikembalikan, denda Rp ', p_denda));
        
        COMMIT;
        
        SET p_status = 'sukses';
        IF p_denda > 0 THEN
            SET p_pesan = CONCAT('Buku dikembalikan dengan denda Rp ', p_denda);
        ELSE
            SET p_pesan = 'Buku dikembalikan tepat waktu';
        END IF;
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- STORED PROCEDURE: laporan_peminjaman
-- ============================================================
DELIMITER $$
CREATE PROCEDURE laporan_peminjaman(
    IN p_tgl_awal DATE,
    IN p_tgl_akhir DATE
)
BEGIN
    SELECT 
        p.id_peminjaman,
        a.no_anggota,
        a.nama AS anggota,
        b.judul AS buku,
        p.tgl_pinjam,
        p.tgl_jatuh_tempo,
        p.tgl_kembali,
        p.status,
        p.denda
    FROM peminjaman p
    JOIN anggota a ON p.id_anggota = a.id_anggota
    JOIN buku b ON p.id_buku = b.id_buku
    WHERE p.tgl_pinjam BETWEEN p_tgl_awal AND p_tgl_akhir
    ORDER BY p.tgl_pinjam DESC;
END$$
DELIMITER ;

-- ============================================================
-- TRIGGER: sebelum insert peminjaman
-- ============================================================
DELIMITER $$
CREATE TRIGGER before_insert_peminjaman
BEFORE INSERT ON peminjaman
FOR EACH ROW
BEGIN
    DECLARE stok_tersedia INT;
    DECLARE status_anggota VARCHAR(20);
    
    SELECT stok INTO stok_tersedia FROM buku WHERE id_buku = NEW.id_buku;
    IF stok_tersedia <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Stok buku habis';
    END IF;
    
    SELECT status INTO status_anggota FROM anggota WHERE id_anggota = NEW.id_anggota;
    IF status_anggota != 'aktif' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Anggota tidak aktif';
    END IF;
    
    IF NEW.tgl_jatuh_tempo IS NULL THEN
        SET NEW.tgl_jatuh_tempo = DATE_ADD(NEW.tgl_pinjam, INTERVAL 7 DAY);
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- TRIGGER: setelah update peminjaman
-- ============================================================
DELIMITER $$
CREATE TRIGGER after_update_peminjaman
AFTER UPDATE ON peminjaman
FOR EACH ROW
BEGIN
    IF NEW.status = 'dikembalikan' AND OLD.status = 'dipinjam' THEN
        INSERT INTO log_peminjaman (id_peminjaman, aksi, detail)
        VALUES (NEW.id_peminjaman, 'KEMBALI_TEPAT', CONCAT('Dikembalikan tanggal ', NEW.tgl_kembali));
    END IF;
    
    IF NEW.status = 'terlambat' AND OLD.status = 'dipinjam' THEN
        INSERT INTO log_peminjaman (id_peminjaman, aksi, detail)
        VALUES (NEW.id_peminjaman, 'KEMBALI_TELAT', CONCAT('Terlambat, denda Rp ', NEW.denda));
    END IF;
END$$
DELIMITER ;

-- ============================================================
-- SEED DATA
-- ============================================================

-- User default
INSERT INTO user (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('petugas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Petugas', 'petugas');
-- password untuk kedua user: password

-- Kelas
INSERT INTO kelas (nama_kelas, tingkatan) VALUES
('7', 1),
('8', 2),
('9', 3);

-- Kategori
INSERT INTO kategori (nama_kategori) VALUES
('Fiksi'),
('Non-Fiksi'),
('Teknologi'),
('Sains'),
('Sejarah'),
('Filsafat'),
('Agama'),
('Pendidikan'),
('Novel'),
('Komik');

-- Pengaturan
INSERT INTO pengaturan (nama_setting, nilai_setting, deskripsi) VALUES
('denda_per_hari', '1000', 'Denda keterlambatan per hari (Rupiah)'),
('max_pinjam', '3', 'Maksimal buku yang bisa dipinjam per anggota'),
('max_hari_pinjam', '14', 'Maksimal lama peminjaman (hari)');

-- Buku
INSERT INTO buku (judul, pengarang, penerbit, tahun_terbit, isbn, stok) VALUES
('Laskar Pelangi', 'Andrea Hirata', 'Bentang Pustaka', 2005, '9789793062792', 5),
('Atomic Habits', 'James Clear', 'Gramedia', 2019, '9786020633187', 3),
('Pengantar Teknologi Informasi', 'Abdul Kadir', 'Andi Offset', 2018, '9789792948956', 4),
('Dasar Pemrograman PHP', 'Budi Raharjo', 'Informatika', 2020, '9786237131333', 2),
('Sejarah Dunia yang Disembunyikan', 'Jonathan Black', 'Alvabet', 2010, '9789793062884', 6),
('Filsafat Ilmu', 'Jujun S. Suriasumantri', 'Pustaka Sinar Harapan', 2007, '9789799483045', 3),
('Bumi Manusia', 'Pramoedya Ananta Toer', 'Hasta Mitra', 1980, '9789799731206', 4),
('Sapiens: Riwayat Singkat Umat Manusia', 'Yuval Noah Harari', 'Alvabet', 2014, '9786029193347', 6),
('Clean Code', 'Robert C. Martin', 'Prentice Hall', 2008, '9780132350884', 3),
('The Pragmatic Programmer', 'David Thomas', 'Addison-Wesley', 2019, '9780135957059', 2);

-- Relasi buku -> kategori
INSERT INTO buku_kategori (id_buku, id_kategori) VALUES
(1, 1), (1, 9),
(2, 2), (2, 8),
(3, 3), (3, 8),
(4, 3), (4, 8),
(5, 5), (5, 2),
(6, 6), (6, 8),
(7, 1), (7, 9),
(8, 5), (8, 4),
(9, 3), (9, 2),
(10, 3), (10, 8);

-- Anggota
INSERT INTO anggota (no_anggota, nisn, id_kelas, nama, status, tgl_daftar) VALUES
('AGT007', '1000000001', 1, 'Ahmad Pratama', 'aktif', CURDATE()),
('AGT008', '1000000002', 1, 'Budi Santoso', 'aktif', CURDATE()),
('AGT009', '1000000003', 1, 'Citra Wijaya', 'aktif', CURDATE()),
('AGT010', '1000000004', 1, 'Dewi Kusuma', 'aktif', CURDATE()),
('AGT011', '1000000005', 1, 'Eko Saputra', 'aktif', CURDATE()),
('AGT012', '1000000006', 1, 'Fitri Hidayat', 'aktif', CURDATE()),
('AGT013', '1000000007', 1, 'Galih Nugraha', 'aktif', CURDATE()),
('AGT014', '1000000008', 1, 'Hesti Permadi', 'aktif', CURDATE()),
('AGT015', '1000000009', 1, 'Irwan Suryadi', 'aktif', CURDATE()),
('AGT016', '1000000010', 1, 'Juni Ramadhani', 'aktif', CURDATE()),
('AGT017', '1000000011', 1, 'Kartika Purnama', 'aktif', CURDATE()),
('AGT018', '1000000012', 1, 'Lukman Mahendra', 'aktif', CURDATE()),
('AGT019', '1000000013', 1, 'Mega Lestari', 'aktif', CURDATE()),
('AGT020', '1000000014', 1, 'Nugroho Wahyuni', 'aktif', CURDATE()),
('AGT021', '1000000015', 1, 'Oktavia Setiawan', 'aktif', CURDATE()),
('AGT022', '1000000016', 1, 'Prasetyo Hartono', 'aktif', CURDATE()),
('AGT023', '1000000017', 1, 'Ratna Susanti', 'aktif', CURDATE()),
('AGT024', '1000000018', 1, 'Surya Anggraini', 'aktif', CURDATE()),
('AGT025', '1000000019', 1, 'Tri Prabowo', 'aktif', CURDATE()),
('AGT026', '1000000020', 1, 'Utami Wulandari', 'aktif', CURDATE()),
('AGT027', '1000000021', 1, 'Vina Gunawan', 'aktif', CURDATE()),
('AGT028', '1000000022', 1, 'Wahyu Irawan', 'aktif', CURDATE()),
('AGT029', '1000000023', 1, 'Yuni Utomo', 'aktif', CURDATE()),
('AGT030', '1000000024', 1, 'Zainal Susilo', 'aktif', CURDATE()),
('AGT031', '1000000025', 1, 'Agung Handayani', 'aktif', CURDATE()),
('AGT032', '1000000026', 2, 'Bayu Mulyani', 'aktif', CURDATE()),
('AGT033', '1000000027', 2, 'Cahya Haryanto', 'aktif', CURDATE()),
('AGT034', '1000000028', 2, 'Dian Fauzi', 'aktif', CURDATE()),
('AGT035', '1000000029', 2, 'Endang Ningsih', 'aktif', CURDATE()),
('AGT036', '1000000030', 2, 'Fajar Febrianti', 'aktif', CURDATE()),
('AGT037', '1000000031', 2, 'Gita Kurniawan', 'aktif', CURDATE()),
('AGT038', '1000000032', 2, 'Hendra Ardiansyah', 'aktif', CURDATE()),
('AGT039', '1000000033', 2, 'Intan Pramono', 'aktif', CURDATE()),
('AGT040', '1000000034', 2, 'Joko Sudrajat', 'aktif', CURDATE()),
('AGT041', '1000000035', 2, 'Kurnia Wardhana', 'aktif', CURDATE()),
('AGT042', '1000000036', 2, 'Lina Cahyani', 'aktif', CURDATE()),
('AGT043', '1000000037', 2, 'Mulyadi Fitriani', 'aktif', CURDATE()),
('AGT044', '1000000038', 2, 'Nadia Yulianti', 'aktif', CURDATE()),
('AGT045', '1000000039', 2, 'Oki Nasution', 'aktif', CURDATE()),
('AGT046', '1000000040', 2, 'Putri Sihombing', 'aktif', CURDATE()),
('AGT047', '1000000041', 2, 'Rina Simanjuntak', 'aktif', CURDATE()),
('AGT048', '1000000042', 2, 'Sigit Siregar', 'aktif', CURDATE()),
('AGT049', '1000000043', 2, 'Tina Sitompul', 'aktif', CURDATE()),
('AGT050', '1000000044', 2, 'Ujang Situmorang', 'aktif', CURDATE()),
('AGT051', '1000000045', 2, 'Vera Sinaga', 'aktif', CURDATE()),
('AGT052', '1000000046', 2, 'Wawan Kurniawan', 'aktif', CURDATE()),
('AGT053', '1000000047', 2, 'Yanti Ardiansyah', 'aktif', CURDATE()),
('AGT054', '1000000048', 2, 'Arif Pramono', 'aktif', CURDATE()),
('AGT055', '1000000049', 2, 'Bella Sudrajat', 'aktif', CURDATE()),
('AGT056', '1000000050', 2, 'Candra Wardhana', 'aktif', CURDATE()),
('AGT057', '1000000051', 3, 'Dwi Cahyani', 'aktif', CURDATE()),
('AGT058', '1000000052', 3, 'Eka Fitriani', 'aktif', CURDATE()),
('AGT059', '1000000053', 3, 'Fanni Yulianti', 'aktif', CURDATE()),
('AGT060', '1000000054', 3, 'Gunawan Nasution', 'aktif', CURDATE()),
('AGT061', '1000000055', 3, 'Hana Sihombing', 'aktif', CURDATE()),
('AGT062', '1000000056', 3, 'Indra Simanjuntak', 'aktif', CURDATE()),
('AGT063', '1000000057', 3, 'Jaya Siregar', 'aktif', CURDATE()),
('AGT064', '1000000058', 3, 'Kiki Sitompul', 'aktif', CURDATE()),
('AGT065', '1000000059', 3, 'Laras Situmorang', 'aktif', CURDATE()),
('AGT066', '1000000060', 3, 'Miftah Sinaga', 'aktif', CURDATE()),
('AGT067', '1000000061', 3, 'Novi Pratama', 'aktif', CURDATE()),
('AGT068', '1000000062', 3, 'Oscar Santoso', 'aktif', CURDATE()),
('AGT069', '1000000063', 3, 'Puji Wijaya', 'aktif', CURDATE()),
('AGT070', '1000000064', 3, 'Rizky Kusuma', 'aktif', CURDATE()),
('AGT071', '1000000065', 3, 'Sari Saputra', 'aktif', CURDATE()),
('AGT072', '1000000066', 3, 'Teguh Hidayat', 'aktif', CURDATE()),
('AGT073', '1000000067', 3, 'Umi Nugraha', 'aktif', CURDATE()),
('AGT074', '1000000068', 3, 'Vicky Permadi', 'aktif', CURDATE()),
('AGT075', '1000000069', 3, 'Winda Suryadi', 'aktif', CURDATE()),
('AGT076', '1000000070', 3, 'Yoga Ramadhani', 'aktif', CURDATE()),
('AGT077', '1000000071', 3, 'Adi Purnama', 'aktif', CURDATE()),
('AGT078', '1000000072', 3, 'Bagus Mahendra', 'aktif', CURDATE()),
('AGT079', '1000000073', 3, 'Cici Lestari', 'aktif', CURDATE()),
('AGT080', '1000000074', 3, 'Deni Wahyuni', 'aktif', CURDATE()),
('AGT081', '1000000075', 3, 'Ahmad Setiawan', 'aktif', CURDATE());

-- Peminjaman contoh
INSERT INTO peminjaman (id_anggota, id_buku, tgl_pinjam, tgl_jatuh_tempo, tgl_kembali, status, denda) VALUES
(1, 1, '2026-06-01', '2026-06-08', '2026-06-07', 'dikembalikan', 0),
(7, 3, '2026-06-05', '2026-06-12', NULL, 'dipinjam', 0),
(8, 2, '2026-06-10', '2026-06-17', NULL, 'dipinjam', 0),
(32, 5, '2026-06-15', '2026-06-22', '2026-06-25', 'terlambat', 3000),
(57, 4, '2026-06-20', '2026-06-27', NULL, 'dipinjam', 0);

-- Log contoh
INSERT INTO log_peminjaman (id_peminjaman, aksi, detail) VALUES
(1, 'PINJAM', 'Anggota 1 meminjam buku 1'),
(1, 'KEMBALI_TEPAT', 'Dikembalikan tanggal 2026-06-07'),
(2, 'PINJAM', 'Anggota 7 meminjam buku 3'),
(3, 'PINJAM', 'Anggota 8 meminjam buku 2'),
(4, 'PINJAM', 'Anggota 32 meminjam buku 5'),
(4, 'KEMBALI_TELAT', 'Terlambat, denda Rp 3000'),
(5, 'PINJAM', 'Anggota 57 meminjam buku 4');
