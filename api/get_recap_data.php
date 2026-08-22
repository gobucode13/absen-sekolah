<?php
/**
 * API Endpoint: Get Attendance Recap Data (Guru View)
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'guru') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login sebagai Guru.']);
    exit;
}

$className = trim($_GET['class_name'] ?? $_POST['class_name'] ?? '');
$startDate = trim($_GET['start_date'] ?? $_POST['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? $_POST['end_date'] ?? '');

try {
    $pdo = getDBConnection();
    autoInitializeDatabase($pdo);

    // Default Date Range (This Month if not specified)
    if (empty($startDate)) {
        $startDate = date('Y-m-01');
    }
    if (empty($endDate)) {
        $endDate = date('Y-m-t');
    }

    // Build Session Filter Query
    $sessionParams = ['guru_id' => $_SESSION['user_id']];
    $sessionWhere = "WHERE `guru_id` = :guru_id AND DATE(`created_at`) BETWEEN :start_date AND :end_date";
    $sessionParams['start_date'] = $startDate;
    $sessionParams['end_date'] = $endDate;

    if (!empty($className) && strtolower($className) !== 'all') {
        $sessionWhere .= " AND LOWER(TRIM(`class_name`)) = LOWER(TRIM(:class_name))";
        $sessionParams['class_name'] = $className;
    }

    $stmtTotalSessions = $pdo->prepare("SELECT COUNT(*) FROM `sessions` $sessionWhere");
    $stmtTotalSessions->execute($sessionParams);
    $totalSessions = intval($stmtTotalSessions->fetchColumn());

    // Build Students Query
    $studentWhere = "WHERE u.role = 'siswa'";
    $studentParams = [
        'guru_id' => $_SESSION['user_id'],
        'start_date' => $startDate,
        'end_date' => $endDate
    ];

    if (!empty($className) && strtolower($className) !== 'all') {
        $studentWhere .= " AND LOWER(TRIM(u.class_name)) = LOWER(TRIM(:class_name))";
        $studentParams['class_name'] = $className;
    }

    $query = "
        SELECT 
            u.id as siswa_id,
            u.identity_number as siswa_nis,
            u.name as siswa_name,
            u.class_name,
            u.avatar,
            COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
            COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as total_izin,
            COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN a.status = 'alpa' THEN 1 END) as total_alpa
        FROM `users` u
        LEFT JOIN `attendances` a ON a.siswa_id = u.id 
            AND a.session_id IN (
                SELECT s.id FROM `sessions` s 
                WHERE s.guru_id = :guru_id AND DATE(s.created_at) BETWEEN :start_date AND :end_date
            )
        $studentWhere
        GROUP BY u.id
        ORDER BY u.class_name ASC, u.name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($studentParams);
    $studentsRaw = $stmt->fetchAll();

    $students = [];
    foreach ($studentsRaw as $st) {
        $hadir = intval($st['total_hadir']);
        $izin = intval($st['total_izin']);
        $sakit = intval($st['total_sakit']);
        $alpa = intval($st['total_alpa']);
        
        // Calculate percentages
        $percentage = $totalSessions > 0 ? round(($hadir / $totalSessions) * 100, 1) : 0;

        $students[] = [
            'siswa_id' => $st['siswa_id'],
            'siswa_nis' => $st['siswa_nis'],
            'siswa_name' => $st['siswa_name'],
            'class_name' => $st['class_name'],
            'avatar' => $st['avatar'],
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'total_sesi' => $totalSessions,
            'percentage' => $percentage
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'filter' => [
                'class_name' => $className ?: 'Semua Kelas',
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'total_sessions' => $totalSessions,
            'students' => $students
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
