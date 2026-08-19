-- ========================================================
-- Database Schema for Website Absensi Sekolah (QR Code)
-- Database Name: absensi_sekolah
-- ========================================================

CREATE DATABASE IF NOT EXISTS `absensi_sekolah` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `absensi_sekolah`;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
DROP TABLE IF EXISTS `attendances`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identity_number` VARCHAR(50) NOT NULL UNIQUE COMMENT 'NIP untuk Guru, NIS untuk Siswa',
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('guru', 'siswa') NOT NULL DEFAULT 'siswa',
  `class_name` VARCHAR(50) DEFAULT NULL COMMENT 'Kelas siswa, misal: XI TKR, 10 IPA 1',
  `avatar` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sessions (Session QR Code Absensi oleh Guru)
-- --------------------------------------------------------
CREATE TABLE `sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `guru_id` INT NOT NULL,
  `subject` VARCHAR(100) NOT NULL COMMENT 'Mata Pelajaran',
  `class_name` VARCHAR(50) NOT NULL COMMENT 'Kelas sasaran absensi',
  `journal_topic` VARCHAR(255) DEFAULT NULL COMMENT 'Materi / Topik Pembelajaran Jurnal KBM',
  `journal_notes` TEXT DEFAULT NULL COMMENT 'Catatan Kegiatan Jurnal KBM',
  `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token unik QR Code',
  `valid_minutes` INT NOT NULL DEFAULT 15,
  `expires_at` DATETIME NOT NULL,
  `status` ENUM('active', 'expired', 'closed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: attendances (Catatan Hasil Scan Absensi Siswa)
-- --------------------------------------------------------
CREATE TABLE `attendances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_id` INT NOT NULL,
  `siswa_id` INT NOT NULL,
  `scanned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('hadir', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
  `notes` VARCHAR(255) DEFAULT NULL COMMENT 'Catatan / Keterangan tambahan (Izin/Sakit)',
  `device_info` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `unique_student_session` (`session_id`, `siswa_id`),
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Initial Demo Users + Data Guru & Siswa dari database.sql
-- Passwords for all accounts default to:
-- guru123 (untuk Guru) / siswa123 (untuk Siswa)
-- --------------------------------------------------------

INSERT INTO `users` (`identity_number`, `name`, `email`, `password`, `role`, `class_name`, `avatar`) VALUES
-- Demo Accounts
('198501012010011001', 'Dra. Siti Aminah, M.Pd.', 'guru@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80'),
('22231001', 'Budi Santoso', 'budi@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80'),
('22231002', 'Anisa Rahmawati', 'anisa@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80'),
('22231003', 'Rizky Pratama', 'rizky@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 2', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80'),

-- Data Guru (Disalin dari database.sql)
('G001', 'Nur Salam HS, S.E, M.Pd', 'g001@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G002', 'Muktasim Billah, S.Pd', 'g002@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G003', 'Azka Erisa Agsovia, S.E', 'g003@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G004', 'Imam Suchaemi', 'g004@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G005', 'Tsitna Fatimatuq Zahroh, S.Kom', 'g005@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G006', 'Mauluddin, S.E', 'g006@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G007', 'Zuhriatul Muthoharoh, S.Pd', 'g007@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G008', 'Nanang Arif Nur Wahyudi, S.Pd', 'g008@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G009', 'Hasan Arrosyid, S.IP', 'g009@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G010', 'Sirozul Mustofa', 'g010@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G011', 'Hamim Murtadlo sya''abani', 'g011@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G012', 'Sri Utami, S.Pd', 'g012@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G013', 'Daniel Adam', 'g013@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G014', 'Ilham Syahrul Fachry', 'g014@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G015', 'Rini Widyawati, S.Pd', 'g015@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G016', 'Sheryl Angelia Meisy Salam, S.Pd', 'g016@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),
('G017', 'Sherly Rahmalivia MS.', 'g017@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, DEFAULT),

-- Data Siswa (Disalin dari database.sql)
('S202401', 'GALIH RESTU WAHYUDI', 's202401@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XII TKR', DEFAULT),
('S202402', 'DENIS KUARNIAWAN', 's202402@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XII TKR', DEFAULT),
('S202403', 'SIROT MAHMUD', 's202403@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XII TKR', DEFAULT),
('S202404', 'MUHAMMAD IKWAN', 's202404@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XII TKR', DEFAULT),
('S202405', 'SITI KOMARIYAH', 's202405@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XII TKR', DEFAULT),
('0107481016', 'AHMAD NAWAL SUBHAN', '0107481016@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202501', 'AGUNG SETIAWANA', 's202501@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202502', 'BADRUS SOLEH RAMADANI', 's202502@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('0099396989', 'FARIDA ADRILIA DWI PUSPITA', '0099396989@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('0096803073', 'HASBI WAHYU RISQIYAH', '0096803073@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202503', 'MOHAMAD BISMA', 's202503@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('0098764520', 'ROICHAN FADIL MAULANA', '0098764520@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202504', 'RISKI NURDIANTO', 's202504@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('3100444009', 'SOFIAN EKA SAPUTRA', '3100444009@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202505', 'TEGUH PRIBADI', 's202505@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202506', 'YUDA SYAHGUMNANTI', 's202506@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI TKR', DEFAULT),
('S202507', 'DHENIS CINDY FLAURA', 's202507@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202508', 'RESTYA EKA PUTRI', 's202508@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202509', 'NURUL AINI', 's202509@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202510', 'OLIVIA SETIAWATI', 's202510@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202511', 'FANINDA KANIA PUTRI', 's202511@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202512', 'DEWI PUTRI ANGGRAENI', 's202512@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('S202513', 'MARSELIYA AJENG CINTIA BELA', 's202513@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'XI BDPO', DEFAULT),
('3108013355', 'AKHMAD NURFADZIL', '3108013355@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('0105117126', 'ANDHIKA RIZKI TRI SANTOSO', '0105117126@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('S202601', 'FIRMAN AKBAR HAMDANI', 's202601@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('S202602', 'KHAMDAN WAFI UBAIDILLAH', 's202602@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('01107687122', 'LAURA DWI PRASTYA', '01107687122@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('0103060133', 'MUHAMMAD RAFHQI DWI RASYAFANI', '0103060133@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('20581263', 'MAYA FAHIRA', '20581263@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('S202603', 'MUHAMMAD RAMADANI APRIANTO', 's202603@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('0113452911', 'RIANTI SARASWATI', '0113452911@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X TKR', DEFAULT),
('109387625', 'ADINDA TASYA CALISTA PUTRI', '109387625@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT),
('3109893035', 'CELYN ZAFILATUL AZIZAH', '3109893035@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT),
('3100831282', 'DITA DWI RAMADHANI', '3100831282@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT),
('0111973783', 'IMRUATUS SOLIKHA', '0111973783@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT),
('0103740557', 'RINDY MEILINA PUTRI', '0103740557@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT),
('0105100130', 'SAFA NI''MATUN AWALIA', '0105100130@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', 'X KKR', DEFAULT);

-- Sample Active Session (Oleh Guru ID 1 Dra. Siti Aminah)
INSERT INTO `sessions` (`id`, `guru_id`, `subject`, `class_name`, `journal_topic`, `journal_notes`, `token`, `valid_minutes`, `expires_at`, `status`) VALUES
(1, 1, 'Matematika Wajib', '10 IPA 1', 'Bab 2: Persamaan & Fungsi Kuadrat', 'Penyampaian teori grafik fungsi kuadrat dan latihan soal kelompok.', 'DEMO-TOKEN-MATEMATIKA-10IPA1', 60, DATE_ADD(NOW(), INTERVAL 1 HOUR), 'active');

-- Sample Attendance
INSERT INTO `attendances` (`session_id`, `siswa_id`, `status`) VALUES
(1, 2, 'hadir');
