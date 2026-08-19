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
        `notes` VARCHAR(255) DEFAULT NULL,
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

        $checkNotes = $pdo->query("SHOW COLUMNS FROM `attendances` LIKE 'notes'");
        if ($checkNotes->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `attendances` ADD COLUMN `notes` VARCHAR(255) DEFAULT NULL AFTER `status`");
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
        ('22231003', 'Rizky Pratama', 'rizky@sekolah.sch.id', '$siswaPass', 'siswa', '10 IPA 2', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=150&q=80'),

        ('G001', 'Nur Salam HS, S.E, M.Pd', 'g001@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G002', 'Muktasim Billah, S.Pd', 'g002@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G003', 'Azka Erisa Agsovia, S.E', 'g003@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G004', 'Imam Suchaemi', 'g004@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G005', 'Tsitna Fatimatuq Zahroh, S.Kom', 'g005@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G006', 'Mauluddin, S.E', 'g006@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G007', 'Zuhriatul Muthoharoh, S.Pd', 'g007@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G008', 'Nanang Arif Nur Wahyudi, S.Pd', 'g008@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G009', 'Hasan Arrosyid, S.IP', 'g009@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G010', 'Sirozul Mustofa', 'g010@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G011', 'Hamim Murtadlo sya''abani', 'g011@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G012', 'Sri Utami, S.Pd', 'g012@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G013', 'Daniel Adam', 'g013@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G014', 'Ilham Syahrul Fachry', 'g014@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G015', 'Rini Widyawati, S.Pd', 'g015@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G016', 'Sheryl Angelia Meisy Salam, S.Pd', 'g016@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),
        ('G017', 'Sherly Rahmalivia MS.', 'g017@sekolah.sch.id', '$guruPass', 'guru', NULL, DEFAULT),

        ('S202401', 'GALIH RESTU WAHYUDI', 's202401@sekolah.sch.id', '$siswaPass', 'siswa', 'XII TKR', DEFAULT),
        ('S202402', 'DENIS KUARNIAWAN', 's202402@sekolah.sch.id', '$siswaPass', 'siswa', 'XII TKR', DEFAULT),
        ('S202403', 'SIROT MAHMUD', 's202403@sekolah.sch.id', '$siswaPass', 'siswa', 'XII TKR', DEFAULT),
        ('S202404', 'MUHAMMAD IKWAN', 's202404@sekolah.sch.id', '$siswaPass', 'siswa', 'XII TKR', DEFAULT),
        ('S202405', 'SITI KOMARIYAH', 's202405@sekolah.sch.id', '$siswaPass', 'siswa', 'XII TKR', DEFAULT),
        ('0107481016', 'AHMAD NAWAL SUBHAN', '0107481016@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202501', 'AGUNG SETIAWANA', 's202501@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202502', 'BADRUS SOLEH RAMADANI', 's202502@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('0099396989', 'FARIDA ADRILIA DWI PUSPITA', '0099396989@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('0096803073', 'HASBI WAHYU RISQIYAH', '0096803073@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202503', 'MOHAMAD BISMA', 's202503@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('0098764520', 'ROICHAN FADIL MAULANA', '0098764520@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202504', 'RISKI NURDIANTO', 's202504@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('3100444009', 'SOFIAN EKA SAPUTRA', '3100444009@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202505', 'TEGUH PRIBADI', 's202505@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202506', 'YUDA SYAHGUMNANTI', 's202506@sekolah.sch.id', '$siswaPass', 'siswa', 'XI TKR', DEFAULT),
        ('S202507', 'DHENIS CINDY FLAURA', 's202507@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202508', 'RESTYA EKA PUTRI', 's202508@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202509', 'NURUL AINI', 's202509@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202510', 'OLIVIA SETIAWATI', 's202510@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202511', 'FANINDA KANIA PUTRI', 's202511@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202512', 'DEWI PUTRI ANGGRAENI', 's202512@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('S202513', 'MARSELIYA AJENG CINTIA BELA', 's202513@sekolah.sch.id', '$siswaPass', 'siswa', 'XI BDPO', DEFAULT),
        ('3108013355', 'AKHMAD NURFADZIL', '3108013355@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('0105117126', 'ANDHIKA RIZKI TRI SANTOSO', '0105117126@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('S202601', 'FIRMAN AKBAR HAMDANI', 's202601@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('S202602', 'KHAMDAN WAFI UBAIDILLAH', 's202602@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('01107687122', 'LAURA DWI PRASTYA', '01107687122@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('0103060133', 'MUHAMMAD RAFHQI DWI RASYAFANI', '0103060133@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('20581263', 'MAYA FAHIRA', '20581263@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('S202603', 'MUHAMMAD RAMADANI APRIANTO', 's202603@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('0113452911', 'RIANTI SARASWATI', '0113452911@sekolah.sch.id', '$siswaPass', 'siswa', 'X TKR', DEFAULT),
        ('109387625', 'ADINDA TASYA CALISTA PUTRI', '109387625@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT),
        ('3109893035', 'CELYN ZAFILATUL AZIZAH', '3109893035@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT),
        ('3100831282', 'DITA DWI RAMADHANI', '3100831282@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT),
        ('0111973783', 'IMRUATUS SOLIKHA', '0111973783@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT),
        ('0103740557', 'RINDY MEILINA PUTRI', '0103740557@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT),
        ('0105100130', 'SAFA NI''MATUN AWALIA', '0105100130@sekolah.sch.id', '$siswaPass', 'siswa', 'X KKR', DEFAULT);";
        
        $pdo->exec($seedSql);
    }
}
