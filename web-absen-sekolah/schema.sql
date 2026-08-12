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
  `class_name` VARCHAR(50) DEFAULT NULL COMMENT 'Kelas siswa, misal: 10 IPA 1',
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
  `device_info` VARCHAR(255) DEFAULT NULL,
  UNIQUE KEY `unique_student_session` (`session_id`, `siswa_id`),
  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Initial Demo Users
-- Passwords for demo accounts:
-- guru123 / siswa123
-- --------------------------------------------------------

INSERT INTO `users` (`id`, `identity_number`, `name`, `email`, `password`, `role`, `class_name`, `avatar`) VALUES
(1, '198501012010011001', 'Dra. Siti Aminah, M.Pd.', 'guru@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'guru', NULL, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80'),
(2, '22231001', 'Budi Santoso', 'budi@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80'),
(3, '22231002', 'Anisa Rahmawati', 'anisa@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80'),
(4, '22231003', 'Rizky Pratama', 'rizky@sekolah.sch.id', '$2y$10$45z2cR.xS5L7gO6H2N9O5.a3p7y5x5y5y5y5y5y5y5y5y5y5y5y5y', 'siswa', '10 IPA 2', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80');

-- Sample Active Session
INSERT INTO `sessions` (`id`, `guru_id`, `subject`, `class_name`, `journal_topic`, `journal_notes`, `token`, `valid_minutes`, `expires_at`, `status`) VALUES
(1, 1, 'Matematika Wajib', '10 IPA 1', 'Bab 2: Persamaan & Fungsi Kuadrat', 'Penyampaian teori grafik fungsi kuadrat dan latihan soal kelompok.', 'DEMO-TOKEN-MATEMATIKA-10IPA1', 60, DATE_ADD(NOW(), INTERVAL 1 HOUR), 'active');

-- Sample Attendance
INSERT INTO `attendances` (`session_id`, `siswa_id`, `status`) VALUES
(1, 2, 'hadir');
