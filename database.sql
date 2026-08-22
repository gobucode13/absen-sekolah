-- ========================================
-- DATABASE SISTEM ABSENSI SMK NURUSSALAM
-- VERSI FINAL - HAR SUDAH SESUAI
-- ========================================

DROP DATABASE IF EXISTS smk_absensi;
CREATE DATABASE smk_absensi CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE smk_absensi;

-- ========================================
-- 1. TABEL GURU
-- ========================================
CREATE TABLE guru (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    mata_pelajaran_utama VARCHAR(50),
    no_telepon VARCHAR(15)
);

-- ========================================
-- 2. TABEL SISWA
-- ========================================
CREATE TABLE siswa (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nis VARCHAR(20) UNIQUE NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    kelas VARCHAR(10) NOT NULL,
    jurusan VARCHAR(50) NOT NULL,
    angkatan INT,
    no_telepon_ortu VARCHAR(15)
);

-- ========================================
-- 3. TABEL MATA PELAJARAN
-- ========================================
CREATE TABLE mata_pelajaran (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kode_mapel VARCHAR(20) UNIQUE NOT NULL,
    nama_mapel VARCHAR(100) NOT NULL,
    jenis_mapel VARCHAR(20) DEFAULT 'Produktif'
);

-- ========================================
-- 4. TABEL JADWAL
-- ========================================
CREATE TABLE jadwal (
    id INT PRIMARY KEY AUTO_INCREMENT,
    guru_id INT,
    mapel_id INT,
    kelas_target VARCHAR(50) NOT NULL,
    hari VARCHAR(20) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    ruangan VARCHAR(50),
    FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE CASCADE,
    FOREIGN KEY (mapel_id) REFERENCES mata_pelajaran(id) ON DELETE CASCADE
);

-- ========================================
-- 5. TABEL ABSENSI
-- ========================================
CREATE TABLE absensi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    siswa_id INT,
    jadwal_id INT,
    tanggal DATE NOT NULL,
    jam_absen TIME NOT NULL,
    status VARCHAR(20) DEFAULT 'Hadir',
    keterangan TEXT,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON DELETE CASCADE,
    UNIQUE KEY unique_absensi (siswa_id, jadwal_id, tanggal)
);

-- ========================================
-- 6. DATA GURU (LENGKAP)
-- ========================================
INSERT INTO guru (nip, nama_lengkap, mata_pelajaran_utama) VALUES
('G001', 'Nur Salam HS, S.E, M.Pd', 'Produk Kreatif'),
('G002', 'Muktasim Billah, S.Pd', NULL),
('G003', 'Azka Erisa Agsovia, S.E', 'Dasar Program 1'),
('G004', 'Imam Suchaemi', 'Dasar Program 1'),
('G005', 'Tsitna Fatimatuq Zahroh, S.Kom', NULL),
('G006', 'Mauluddin, S.E', 'Bisnis Ritel'),
('G007', 'Zuhriatul Muthoharoh, S.Pd', 'PAI'),
('G008', 'Nanang Arif Nur Wahyudi, S.Pd', 'Dasar Program 2'),
('G009', 'Hasan Arrosyid, S.IP', 'Penataan Produk'),
('G010', 'Sirozul Mustofa', 'PKN'),
('G011', 'Hamim Murtadlo sya''abani', 'Informatika'),
('G012', 'Sri Utami, S.Pd', 'Produk Kreatif'),
('G013', 'Daniel Adam', 'Matematika'),
('G014', 'Ilham Syahrul Fachry', 'Matematika'),
('G015', 'Rini Widyawati, S.Pd', 'Sejarah Indonesia'),
('G016', 'Sheryl Angelia Meisy Salam, S.Pd', 'Bahasa Indonesia'),
('G017', 'Sherly Rahmalivia MS.', 'Bahasa Inggris');

-- ========================================
-- 7. DATA MATA PELAJARAN
-- ========================================
INSERT INTO mata_pelajaran (kode_mapel, nama_mapel, jenis_mapel) VALUES
('PKW', 'Produk Kreatif dan Kewirausahaan', 'Produktif'),
('SEJ', 'Sejarah Indonesia', 'Normatif'),
('BIN', 'Bahasa Indonesia', 'Normatif'),
('BIG', 'Bahasa Inggris', 'Normatif'),
('DDPK1', 'Dasar Dasar Program Keahlian 1', 'Produktif'),
('DDPK2', 'Dasar Dasar Program Keahlian 2', 'Produktif'),
('BISNIS', 'Bisnis Online', 'Produktif'),
('ADMTR', 'Administrasi Transaksi', 'Produktif'),
('PBR', 'Pengelolaan Bisnis Ritel', 'Produktif'),
('PAI', 'PAI', 'Normatif'),
('PTP', 'Penataan Produk', 'Produktif'),
('PKN', 'PKN', 'Normatif'),
('PPKN', 'Pendidikan Pancasila', 'Normatif'),
('INF', 'Informatika', 'Produktif'),
('MTK', 'Matematika', 'Normatif'),
('PJOK', 'PJOK', 'Normatif'),
('KK1', 'kk 1', 'Produktif'),
('KK2', 'kk 2', 'Produktif'),
('KKA', 'KKA', 'Produktif'),
('MP', 'MP', 'Produktif'),
('MPMP', 'MPMP', 'Produktif');

-- ========================================
-- 8. DATA SISWA (dari file .txt)
-- ========================================
INSERT INTO siswa (nis, nama_lengkap, kelas, jurusan, angkatan) VALUES
('KOSONG', 'GALIH RESTU WAHYUDI', 'XII', 'TKR', 2024),
('KOSONG', 'DENIS KUARNIAWAN', 'XII', 'TKR', 2024),
('KOSONG', 'SIROT MAHMUD', 'XII', 'TKR', 2024),
('KOSONG', 'MUHAMMAD IKWAN', 'XII', 'TKR', 2024),
('KOSONG', 'SITI KOMARIYAH', 'XII', 'TKR', 2024),
('0107481016', 'AHMAD NAWAL SUBHAN', 'XI', 'TKR', 2025),
('KOSONG', 'AGUNG SETIAWANA', 'XI', 'TKR', 2025),
('KOSONG', 'BADRUS SOLEH RAMADANI', 'XI', 'TKR', 2025),
('0099396989', 'FARIDA ADRILIA DWI PUSPITA', 'XI', 'TKR', 2025),
('0096803073', 'HASBI WAHYU RISQIYAH', 'XI', 'TKR', 2025),
('KOSONG', 'MOHAMAD BISMA', 'XI', 'TKR', 2025),
('0098764520', 'ROICHAN FADIL MAULANA', 'XI', 'TKR', 2025),
('KOSONG', 'RISKI NURDIANTO', 'XI', 'TKR', 2025),
('3100444009', 'SOFIAN EKA SAPUTRA', 'XI', 'TKR', 2025),
('KOSONG', 'TEGUH PRIBADI', 'XI', 'TKR', 2025),
('KOSONG', 'YUDA SYAHGUMNANTI', 'XI', 'TKR', 2025),
('KOSONG', 'DHENIS CINDY FLAURA', 'XI', 'BDPO', 2025),
('KOSONG', 'RESTYA EKA PUTRI', 'XI', 'BDPO', 2025),
('KOSONG', 'NURUL AINI', 'XI', 'BDPO', 2025),
('KOSONG', 'OLIVIA SETIAWATI', 'XI', 'BDPO', 2025),
('KOSONG', 'FANINDA KANIA PUTRI', 'XI', 'BDPO', 2025),
('KOSONG', 'DEWI PUTRI ANGGRAENI', 'XI', 'BDPO', 2025),
('KOSONG', 'MARSELIYA AJENG CINTIA BELA', 'XI', 'BDPO', 2025),
('3108013355', 'AKHMAD NURFADZIL', 'X', 'TKR', 2026),
('0105117126', 'ANDHIKA RIZKI TRI SANTOSO', 'X', 'TKR', 2026),
('KOSONG', 'FIRMAN AKBAR HAMDANI', 'X', 'TKR', 2026),
('KOSONG', 'KHAMDAN WAFI UBAIDILLAH', 'X', 'TKR', 2026),
('01107687122', 'LAURA DWI PRASTYA', 'X', 'TKR', 2026),
('0103060133', 'MUHAMMAD RAFHQI DWI RASYAFANI', 'X', 'TKR', 2026),
('20581263', 'MAYA FAHIRA', 'X', 'TKR', 2026),
('KOSONG', 'MUHAMMAD RAMADANI APRIANTO', 'X', 'TKR', 2026),
('0113452911', 'RIANTI SARASWATI', 'X', 'TKR', 2026),
('109387625', 'ADINDA TASYA CALISTA PUTRI', 'X', 'KKR', 2026),
('3109893035', 'CELYN ZAFILATUL AZIZAH', 'X', 'KKR', 2026),
('3100831282', 'DITA DWI RAMADHANI', 'X', 'KKR', 2026),
('0111973783', 'IMRUATUS SOLIKHA', 'X', 'KKR', 2026),
('0103740557', 'RINDY MEILINA PUTRI', 'X', 'KKR', 2026),
('0105100130', 'SAFA NI''MATUN AWALIA', 'X', 'KKR', 2026);

-- ========================================
-- 9. DATA JADWAL (HARI SUDAH SESUAI KOLOM)
-- ========================================

-- MAPPING KOLOM:
-- Kolom 1 = Senin, Kolom 2 = Selasa, Kolom 3 = Rabu, Kolom 4 = Kamis, Kolom 5 = Jumat, Kolom 6 = Sabtu

-- ========================================
-- A. JADWAL NUR SALAM (G001) - HALAMAN 1
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G001'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PKW'), 'TKRO XI/BDPO XI', 'Senin', '10:20:00', '11:05:00');

-- ========================================
-- B. JADWAL AZKA (G003) - HALAMAN 5
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK1'), 'TKKR X', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK1'), 'TKKR X', 'Selasa', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BISNIS'), 'BDPO XII', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BISNIS'), 'BDPO XII', 'Selasa', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='ADMTR'), 'BDPO XII', 'Rabu', '10:20:00', '11:05:00'),
-- Row 6, 11:05-11:50, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='ADMTR'), 'BDPO XII', 'Senin', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='ADMTR'), 'BDPO XII', 'Selasa', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G003'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='ADMTR'), 'BDPO XII', 'Rabu', '11:05:00', '11:50:00');

-- ========================================
-- C. JADWAL IMAM (G004) - HALAMAN 6
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK1'), 'TKRO X', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XII', 'Selasa', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XII', 'Rabu', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XII', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK1'), 'TKRO X', 'Selasa', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XI', 'Rabu', '10:20:00', '11:05:00'),
-- Row 6, 11:05-11:50, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XII', 'Senin', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO X', 'Selasa', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G004'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK1'), 'TKRO XI', 'Rabu', '11:05:00', '11:50:00');

-- ========================================
-- D. JADWAL MAULUDDIN (G006) - HALAMAN 8
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G006'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='ADMTR'), 'BDPO XII', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G006'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PBR'), 'BDPO XII', 'Selasa', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G006'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PBR'), 'BDPO XII', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G006'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BISNIS'), 'BDPO XII', 'Selasa', '10:20:00', '11:05:00');

-- ========================================
-- E. JADWAL ZUHRIATUL (G007) - HALAMAN 9
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G007'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PAI'), 'TKKR X', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G007'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PTP'), 'TKRO X', 'Selasa', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G007'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PTP'), 'BDPO XI', 'Rabu', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G007'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PAI'), 'BDPO XII/TKRO XII', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G007'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PAI'), 'BDPO XII/TKRO XI', 'Selasa', '10:20:00', '11:05:00');

-- ========================================
-- F. JADWAL NANANG (G008) - HALAMAN 10
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK2'), 'TKRO XII', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK2'), 'TKRO XII', 'Selasa', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK2'), 'TKRO X', 'Rabu', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 4 (Kamis)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK2'), 'TKRO X', 'Kamis', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK2'), 'TKRO XII', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G008'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KK2'), 'TKRO XI', 'Selasa', '10:20:00', '11:05:00');

-- ========================================
-- G. JADWAL HASAN (G009) - HALAMAN 11
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G009'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PTP'), 'BDPO XII', 'Senin', '07:00:00', '07:45:00');

-- ========================================
-- H. JADWAL SIROZUL (G010) - HALAMAN 13
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G010'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PKN'), 'BDPO XII/TKRO XII', 'Senin', '07:00:00', '07:45:00'),
-- Row 3, 08:30-09:15, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G010'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PPKN'), 'TKKR X/TKRO X', 'Senin', '08:30:00', '09:15:00');

-- ========================================
-- I. JADWAL HAMIM (G011) - HALAMAN 14
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G011'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='INF'), 'TKKR X', 'Senin', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G011'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KKA'), 'TKKR X', 'Senin', '10:20:00', '11:05:00'),
-- Row 6, 11:05-11:50, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G011'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KKA'), 'TKKR X', 'Senin', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G011'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='KKA'), 'TKRO X', 'Selasa', '11:05:00', '11:50:00');

-- ========================================
-- J. JADWAL SRI UTAMI (G012) - HALAMAN 15
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G012'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PKW'), 'BDPO XII/TKRO XII', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G012'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PBR'), 'BDPO XI', 'Selasa', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G012'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PBR'), 'BDPO XI', 'Senin', '10:20:00', '11:05:00');

-- ========================================
-- K. JADWAL DANIEL (G013) - HALAMAN 16
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK2'), 'TKKR X', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XII', 'Selasa', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XI', 'Rabu', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XI', 'Senin', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='DDPK2'), 'TKKR X', 'Selasa', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XII', 'Rabu', '10:20:00', '11:05:00'),
-- Row 6, 11:05-11:50, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XI', 'Senin', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR X', 'Selasa', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 3 (Rabu)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'TKKR XII', 'Rabu', '11:05:00', '11:50:00'),
-- Row 6, 11:05-11:50, Kolom 4 (Kamis)
((SELECT id FROM guru WHERE nip='G013'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MP'), 'BDPO XII/TKKR XII', 'Kamis', '11:05:00', '11:50:00');

-- ========================================
-- L. JADWAL ILHAM (G014) - HALAMAN 17
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G014'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MTK'), 'BDPO XI/TKRO XI', 'Senin', '07:00:00', '07:45:00'),
-- Row 1, 07:00-07:45, Kolom 2 (Selasa)
((SELECT id FROM guru WHERE nip='G014'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='PJOK'), 'BDPO XI/TKRO XI', 'Selasa', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G014'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='MTK'), 'TKKR XI/TKRO X', 'Senin', '10:20:00', '11:05:00');

-- ========================================
-- M. JADWAL BU RINI (G015) - HALAMAN 3
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 3, 08:30-09:15, Kolom 1 (Senin)
((SELECT id FROM guru WHERE nip='G015'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='SEJ'), 'TKKR X/TKRO X', 'Senin', '08:30:00', '09:15:00');

-- ========================================
-- N. JADWAL SHERYL (G016) - BAHASA INDONESIA (DARI GAMBAR 1)
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 4 (Kamis)
((SELECT id FROM guru WHERE nip='G016'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIN'), 'BDPO XII/TKRO XII', 'Kamis', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 4 (Kamis)
((SELECT id FROM guru WHERE nip='G016'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIN'), 'BDPO XII/TKRO XI', 'Kamis', '10:20:00', '11:05:00'),
-- Row 5, 10:20-11:05, Kolom 5 (Jumat)
((SELECT id FROM guru WHERE nip='G016'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIN'), 'TKKR X/TKRO X', 'Jumat', '10:20:00', '11:05:00');

-- ========================================
-- O. JADWAL SHERLY (G017) - BAHASA INGGRIS (DARI GAMBAR 2)
-- ========================================
INSERT INTO jadwal (guru_id, mapel_id, kelas_target, hari, jam_mulai, jam_selesai) VALUES
-- Row 1, 07:00-07:45, Kolom 4 (Kamis) - SUDAH BENAR
((SELECT id FROM guru WHERE nip='G017'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIG'), 'BDPO XI/TKRO XI', 'Kamis', '07:00:00', '07:45:00'),
-- Row 5, 10:20-11:05, Kolom 4 (Kamis) - INI YANG SEBELUMNYA SALAH (SAYA TULIS JUMAT)
((SELECT id FROM guru WHERE nip='G017'), (SELECT id FROM mata_pelajaran WHERE kode_mapel='BIG'), 'TKKR X/TKRO X', 'Kamis', '10:20:00', '11:05:00');

-- ========================================
-- SELESAI
-- ========================================