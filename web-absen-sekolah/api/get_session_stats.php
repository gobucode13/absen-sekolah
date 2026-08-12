<?php
/**
 * API Endpoint: Get Real-time Session Attendance Stats
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$sessionId = intval($_GET['session_id'] ?? 0);
if ($sessionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Sesi tidak valid.']);
    exit;
}

try {
    $pdo = getDBConnection();

    // Fetch Session Details
    $stmtSession = $pdo->prepare("SELECT * FROM `sessions` WHERE `id` = :id LIMIT 1");
    $stmtSession->execute(['id' => $sessionId]);
    $session = $stmtSession->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Sesi tidak ditemukan.']);
        exit;
    }

    $now = time();
    $expiresAtTimestamp = strtotime($session['expires_at']);
    $remainingSeconds = max(0, $expiresAtTimestamp - $now);

    // Auto update status if expired
    if ($remainingSeconds <= 0 && $session['status'] === 'active') {
        $stmtUpdate = $pdo->prepare("UPDATE `sessions` SET `status` = 'expired' WHERE `id` = :id");
        $stmtUpdate->execute(['id' => $sessionId]);
        $session['status'] = 'expired';
    }

    // Fetch Total Attendance Count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `attendances` WHERE `session_id` = :session_id");
    $stmtCount->execute(['session_id' => $sessionId]);
    $totalHadir = $stmtCount->fetchColumn();

    // Fetch Recent Attendees List
    $stmtList = $pdo->prepare("
        SELECT a.scanned_at, u.name, u.identity_number, u.class_name, u.avatar
        FROM `attendances` a
        JOIN `users` u ON a.siswa_id = u.id
        WHERE a.session_id = :session_id
        ORDER BY a.scanned_at DESC
        LIMIT 10
    ");
    $stmtList->execute(['session_id' => $sessionId]);
    $attendees = $stmtList->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'session' => [
                'id' => $session['id'],
                'subject' => $session['subject'],
                'class_name' => $session['class_name'],
                'token' => $session['token'],
                'status' => $session['status'],
                'expires_at' => $session['expires_at'],
                'remaining_seconds' => $remainingSeconds
            ],
            'total_hadir' => $totalHadir,
            'recent_attendees' => $attendees
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data: ' . $e->getMessage()]);
}
