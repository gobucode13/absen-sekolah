<?php
/**
 * API Endpoint: Get All Student Attendances for a Session (Guru View)
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'guru') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login sebagai Guru.']);
    exit;
}

$sessionId = intval($_GET['session_id'] ?? $_POST['session_id'] ?? 0);

if ($sessionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID Sesi tidak valid.']);
    exit;
}

try {
    $pdo = getDBConnection();
    autoInitializeDatabase($pdo);

    // Verify Session
    $stmtSession = $pdo->prepare("SELECT * FROM `sessions` WHERE `id` = :id AND `guru_id` = :guru_id LIMIT 1");
    $stmtSession->execute([
        'id' => $sessionId,
        'guru_id' => $_SESSION['user_id']
    ]);
    $session = $stmtSession->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Sesi absensi tidak ditemukan atau Anda tidak memiliki akses.']);
        exit;
    }

    // Fetch All Students in target class and their attendance status
    $stmtStudents = $pdo->prepare("
        SELECT 
            u.id as siswa_id,
            u.name as siswa_name,
            u.identity_number as siswa_nis,
            u.class_name,
            u.avatar,
            COALESCE(a.status, 'belum_absen') as status,
            a.scanned_at,
            a.notes
        FROM `users` u
        LEFT JOIN `attendances` a ON a.siswa_id = u.id AND a.session_id = :session_id
        WHERE u.role = 'siswa' AND LOWER(TRIM(u.class_name)) = LOWER(TRIM(:class_name))
        ORDER BY u.name ASC
    ");

    $stmtStudents->execute([
        'session_id' => $sessionId,
        'class_name' => $session['class_name']
    ]);
    $students = $stmtStudents->fetchAll();

    // Summary Counts
    $summary = [
        'total' => count($students),
        'hadir' => 0,
        'izin' => 0,
        'sakit' => 0,
        'alpa' => 0,
        'belum_absen' => 0
    ];

    foreach ($students as $st) {
        $stStatus = strtolower($st['status']);
        if (isset($summary[$stStatus])) {
            $summary[$stStatus]++;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'session' => [
                'id' => $session['id'],
                'subject' => $session['subject'],
                'class_name' => $session['class_name'],
                'journal_topic' => $session['journal_topic'],
                'status' => $session['status']
            ],
            'summary' => $summary,
            'students' => $students
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
