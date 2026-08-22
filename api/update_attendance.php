<?php
/**
 * API Endpoint: Update Attendance Status & Notes by Guru
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'guru') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login sebagai Guru.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

$sessionId = intval($_POST['session_id'] ?? 0);
$siswaId = intval($_POST['siswa_id'] ?? 0);
$status = strtolower(trim($_POST['status'] ?? ''));
$notes = trim($_POST['notes'] ?? '');

$allowedStatuses = ['hadir', 'izin', 'sakit', 'alpa'];

if ($sessionId <= 0 || $siswaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sesi atau Siswa tidak valid.']);
    exit;
}

if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Status presensi tidak valid. Pilihan: Hadir, Izin, Sakit, Alpa.']);
    exit;
}

try {
    $pdo = getDBConnection();
    autoInitializeDatabase($pdo);

    // Verify Guru owns the session
    $stmtSession = $pdo->prepare("SELECT `id`, `class_name` FROM `sessions` WHERE `id` = :id AND `guru_id` = :guru_id LIMIT 1");
    $stmtSession->execute([
        'id' => $sessionId,
        'guru_id' => $_SESSION['user_id']
    ]);
    $session = $stmtSession->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Sesi absensi tidak ditemukan atau Anda tidak berhak mengubahnya.']);
        exit;
    }

    // Upsert attendance record
    $stmtUpsert = $pdo->prepare("
        INSERT INTO `attendances` (`session_id`, `siswa_id`, `status`, `notes`, `scanned_at`, `device_info`)
        VALUES (:session_id, :siswa_id, :status, :notes, NOW(), 'Diubah Manual oleh Guru')
        ON DUPLICATE KEY UPDATE 
            `status` = VALUES(`status`),
            `notes` = VALUES(`notes`),
            `scanned_at` = NOW()
    ");

    $stmtUpsert->execute([
        'session_id' => $sessionId,
        'siswa_id' => $siswaId,
        'status' => $status,
        'notes' => $notes !== '' ? $notes : null
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Status presensi berhasil diperbarui.',
        'data' => [
            'session_id' => $sessionId,
            'siswa_id' => $siswaId,
            'status' => $status,
            'notes' => $notes
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal memperbarui presensi: ' . $e->getMessage()]);
}
