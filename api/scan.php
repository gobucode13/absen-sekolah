<?php
/**
 * API Endpoint: Process QR Code Scan by Student
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'siswa') {
    echo json_encode(['success' => false, 'message' => 'Hanya siswa yang dapat melakukan absensi scan QR.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$token = trim($_POST['token'] ?? '');

if (empty($token)) {
    echo json_encode(['success' => false, 'message' => 'Token QR Code tidak terdeteksi. Silakan coba lagi.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Find Session by Token
    $stmtSession = $pdo->prepare("
        SELECT s.*, g.name as guru_name 
        FROM `sessions` s
        JOIN `users` g ON s.guru_id = g.id
        WHERE s.token = :token LIMIT 1
    ");
    $stmtSession->execute(['token' => $token]);
    $session = $stmtSession->fetch();

    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => 'QR Code tidak terdaftar dalam sistem.'
        ]);
        exit;
    }

    // Check if session is expired or closed
    $now = time();
    $expiresAtTimestamp = strtotime($session['expires_at']);

    if ($session['status'] !== 'active' || $now > $expiresAtTimestamp) {
        // Auto update status to expired if needed
        if ($session['status'] === 'active') {
            $updateStmt = $pdo->prepare("UPDATE `sessions` SET `status` = 'expired' WHERE `id` = :id");
            $updateStmt->execute(['id' => $session['id']]);
        }
        echo json_encode([
            'success' => false,
            'message' => 'Sesi absensi sudah kedaluwarsa atau ditutup oleh guru.'
        ]);
        exit;
    }

    // Optional Check: Is the student from the specified class?
    // If session class does not match student class (case insensitive check)
    if (!empty($session['class_name']) && !empty($currentUser['class_name'])) {
        if (strcasecmp(trim($session['class_name']), trim($currentUser['class_name'])) !== 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Sesi absensi ini diperuntukkan untuk kelas ' . htmlspecialchars($session['class_name']) . '. Kelas Anda adalah ' . htmlspecialchars($currentUser['class_name']) . '.'
            ]);
            exit;
        }
    }

    // Check if student has already recorded attendance for this session
    $stmtCheck = $pdo->prepare("SELECT * FROM `attendances` WHERE `session_id` = :session_id AND `siswa_id` = :siswa_id LIMIT 1");
    $stmtCheck->execute([
        'session_id' => $session['id'],
        'siswa_id' => $currentUser['id']
    ]);
    $existing = $stmtCheck->fetch();

    if ($existing) {
        echo json_encode([
            'success' => false,
            'already_scanned' => true,
            'message' => 'Anda sudah melakukan absensi untuk sesi ini pada ' . date('H:i:s, d M Y', strtotime($existing['scanned_at'])) . '.'
        ]);
        exit;
    }

    // Record Attendance
    $deviceInfo = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Browser Scan', 0, 250);
    $stmtInsert = $pdo->prepare("
        INSERT INTO `attendances` (`session_id`, `siswa_id`, `status`, `device_info`)
        VALUES (:session_id, :siswa_id, 'hadir', :device_info)
    ");
    $stmtInsert->execute([
        'session_id' => $session['id'],
        'siswa_id' => $currentUser['id'],
        'device_info' => $deviceInfo
    ]);

    $scannedAt = date('d M Y - H:i:s');

    echo json_encode([
        'success' => true,
        'message' => 'Absensi Berhasil Dicatat!',
        'data' => [
            'student_name' => $currentUser['name'],
            'student_nis' => $currentUser['identity_number'],
            'subject' => $session['subject'],
            'class_name' => $session['class_name'],
            'guru_name' => $session['guru_name'],
            'time' => $scannedAt,
            'status' => 'Hadir'
        ]
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == '23000') {
        echo json_encode([
            'success' => false,
            'message' => 'Anda sudah mencatat kehadiran untuk sesi absensi ini.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
        ]);
    }
}
