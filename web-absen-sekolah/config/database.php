<?php
/**
 * Database Connection & Global Configuration
 * Website Absensi Sekolah (QR Code)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'absensi_sekolah');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to connect without DB name to auto-create or show warning
            try {
                $dsnNoDb = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                $tmpPdo = new PDO($dsnNoDb, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Reconnect to newly created DB
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                // Auto-create tables if missing
                autoInitializeDatabase($pdo);

            } catch (PDOException $e2) {
                die("
                <div style='font-family: system-ui, sans-serif; max-width: 600px; margin: 50px auto; padding: 30px; border-radius: 16px; background: #FFF5F5; border: 1px solid #FEB2B2; color: #9B2C2C;'>
                    <h2 style='margin-top:0;'>⚠️ Gagal Terhubung ke Database MySQL</h2>
                    <p><b>Pesan Error:</b> " . htmlspecialchars($e2->getMessage()) . "</p>
                    <hr style='border:0; border-top:1px solid #FEB2B2; margin:20px 0;'>
                    <p><b>Petunjuk Pengaturan:</b></p>
                    <ol style='padding-left:20px; line-height: 1.6;'>
                        <li>Pastikan MySQL server (XAMPP / Laragon) sudah berjalan.</li>
                        <li>Pastikan kredensial di <code>config/database.php</code> sudah sesuai (User: <b>root</b>, Pass: <i>kosong/sesuai setup</i>).</li>
                        <li>Impor file <code>schema.sql</code> ke phpMyAdmin jika belum memiliki tabel.</li>
                    </ol>
                </div>
                ");
            }
        }
    }
    return $pdo;
}

/**
 * Auto Initialize Tables if database is fresh
 */
function autoInitializeDatabase($pdo) {
    $createUsers = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `identity_number` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('guru', 'siswa') NOT NULL DEFAULT 'siswa',
        `class_name` VARCHAR(50) DEFAULT NULL,
        `avatar` VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $createSessions = "CREATE TABLE IF NOT EXISTS `sessions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `guru_id` INT NOT NULL,
        `subject` VARCHAR(100) NOT NULL,
        `class_name` VARCHAR(50) NOT NULL,
        `journal_topic` VARCHAR(255) DEFAULT NULL,
        `journal_notes` TEXT DEFAULT NULL,
        `token` VARCHAR(64) NOT NULL UNIQUE,
        `valid_minutes` INT NOT NULL DEFAULT 15,
        `expires_at` DATETIME NOT NULL,
        `status` ENUM('active', 'expired', 'closed') NOT NULL DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $createAttendances = "CREATE TABLE IF NOT EXISTS `attendances` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `session_id` INT NOT NULL,
        `siswa_id` INT NOT NULL,
        `scanned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `status` ENUM('hadir', 'izin', 'sakit', 'alpa') NOT NULL DEFAULT 'hadir',
        `device_info` VARCHAR(255) DEFAULT NULL,
        UNIQUE KEY `unique_student_session` (`session_id`, `siswa_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($createUsers);
    $pdo->exec($createSessions);
    $pdo->exec($createAttendances);

    // Auto-migration: Check if journal_topic column exists in sessions table
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM `sessions` LIKE 'journal_topic'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `sessions` ADD COLUMN `journal_topic` VARCHAR(255) DEFAULT NULL AFTER `class_name`");
            $pdo->exec("ALTER TABLE `sessions` ADD COLUMN `journal_notes` TEXT DEFAULT NULL AFTER `journal_topic`");
        }
    } catch (Exception $e) {
        // Silently skip if table or column alteration issue occurs
    }

    // Seed default users if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM `users`");
    if ($stmt->fetchColumn() == 0) {
        $guruPass = password_hash('guru123', PASSWORD_DEFAULT);
        $siswaPass = password_hash('siswa123', PASSWORD_DEFAULT);

        $seedSql = "INSERT INTO `users` (`identity_number`, `name`, `email`, `password`, `role`, `class_name`, `avatar`) VALUES
        ('198501012010011001', 'Dra. Siti Aminah, M.Pd.', 'guru@sekolah.sch.id', '$guruPass', 'guru', NULL, 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=150&q=80'),
        ('22231001', 'Budi Santoso', 'budi@sekolah.sch.id', '$siswaPass', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=150&q=80'),
        ('22231002', 'Anisa Rahmawati', 'anisa@sekolah.sch.id', '$siswaPass', 'siswa', '10 IPA 1', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=150&q=80'),
        ('22231003', 'Rizky Pratama', 'rizky@sekolah.sch.id', '$siswaPass', 'siswa', '10 IPA 2', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80');";
        
        $pdo->exec($seedSql);
    }
}
