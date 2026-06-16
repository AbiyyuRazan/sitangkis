CREATE DATABASE IF NOT EXISTS sitangkis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sitangkis;

DROP TABLE IF EXISTS realisasi;
DROP TABLE IF EXISTS anggaran;
DROP TABLE IF EXISTS sumber_pendapatan;
DROP TABLE IF EXISTS desa_info;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('bendahara','sekdes','kades') NOT NULL,
    aktif       TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE desa_info (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nama_desa       VARCHAR(100) DEFAULT 'Desa Sumber Jaya',
    kepala_desa     VARCHAR(100) DEFAULT 'H. Sukarman',
    tahun_anggaran  YEAR DEFAULT 2026,
    total_apbdes    BIGINT DEFAULT 900000000
) ENGINE=InnoDB;

CREATE TABLE anggaran (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nama_program VARCHAR(200) NOT NULL,
    kategori     ENUM('Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan') NOT NULL,
    jumlah       BIGINT NOT NULL DEFAULT 0,
    tahun        YEAR NOT NULL,
    keterangan   TEXT,
    dibuat_oleh  INT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE realisasi (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    anggaran_id     INT,
    nama_kegiatan   VARCHAR(200) NOT NULL,
    kategori        ENUM('Infrastruktur','Pendidikan','Kesehatan','Administrasi','Pemberdayaan') NOT NULL,
    jumlah          BIGINT NOT NULL DEFAULT 0,
    tanggal         DATE NOT NULL,
    status          ENUM('Proses','Selesai','Batal') DEFAULT 'Proses',
    file_bukti      VARCHAR(255),
    keterangan      TEXT,
    dibuat_oleh     INT,
    divalidasi_oleh INT,
    divalidasi_at   TIMESTAMP NULL,
    is_publik       TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dibuat_oleh) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (divalidasi_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE sumber_pendapatan (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    sumber VARCHAR(100) NOT NULL,
    jumlah BIGINT NOT NULL,
    tahun  YEAR NOT NULL
) ENGINE=InnoDB;

-- =============================================
-- PASSWORD SEMUA AKUN = admin123
-- Hash dibuat ulang via generate_password.php
-- Untuk sementara, password disimpan plain dulu
-- lalu diupdate otomatis oleh setup.php
-- =============================================
INSERT INTO users (nama, email, password, role) VALUES
('Siti Nurhaliza', 'bendahara@desa.go.id', 'GANTI_HASH', 'bendahara'),
('Budi Santoso',   'sekdes@desa.go.id',    'GANTI_HASH', 'sekdes'),
('H. Sukarman',    'kades@desa.go.id',     'GANTI_HASH', 'kades');

INSERT INTO desa_info (nama_desa, kepala_desa, tahun_anggaran, total_apbdes) VALUES
('Desa Sumber Jaya', 'H. Sukarman', 2026, 900000000);

INSERT INTO anggaran (nama_program, kategori, jumlah, tahun, dibuat_oleh) VALUES
('Pembangunan & Pemeliharaan Jalan Desa',    'Infrastruktur', 500000000, 2026, 1),
('Bantuan Pendidikan & Operasional Sekolah', 'Pendidikan',    250000000, 2026, 1),
('Kesehatan Masyarakat & Posyandu',          'Kesehatan',     150000000, 2026, 1);

INSERT INTO realisasi (nama_kegiatan, kategori, jumlah, tanggal, status, dibuat_oleh, divalidasi_oleh, divalidasi_at, is_publik) VALUES
('Pembangunan Jalan Desa RT 05',      'Infrastruktur', 125000000, '2026-03-28', 'Selesai', 2, 3, NOW(), 1),
('Bantuan Operasional Sekolah',       'Pendidikan',     45000000, '2026-03-25', 'Selesai', 2, 3, NOW(), 1),
('Pengadaan Alat Kesehatan Posyandu', 'Kesehatan',      18500000, '2026-03-20', 'Proses',  1, NULL, NULL, 0),
('Renovasi Balai Desa',               'Infrastruktur',  75000000, '2026-03-15', 'Proses',  2, NULL, NULL, 0),
('Program Pelatihan Guru PAUD',       'Pendidikan',     12000000, '2026-03-10', 'Batal',   1, NULL, NULL, 0),
('Pembangunan MCK Umum',              'Infrastruktur',  32000000, '2026-03-05', 'Selesai', 2, 3, NOW(), 1);

INSERT INTO sumber_pendapatan (sumber, jumlah, tahun) VALUES
('Dana Desa', 650000000, 2026),
('ADD',       180000000, 2026),
('PDRD',       70000000, 2026);
