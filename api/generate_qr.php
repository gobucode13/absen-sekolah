<?php
/**
 * API Endpoint: Generate QR Session
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

$subject = trim($_POST['subject'] ?? '');
$className = trim($_POST['class_name'] ?? '');
$journalTopic = trim($_POST['journal_topic'] ?? '');
$journalNotes = trim($_POST['journal_notes'] ?? '');
$validMinutes = intval($_POST['valid_minutes'] ?? 15);

if (empty($subject) || empty($className)) {
    echo json_encode(['success' => false, 'message' => 'Mata pelajaran dan kelas harus diisi.']);
    exit;
}

if (empty($journalTopic)) {
    echo json_encode(['success' => false, 'message' => 'Materi / Topik Pembelajaran Jurnal KBM harus diisi.']);
    exit;
}

if ($validMinutes < 1 || $validMinutes > 180) {
    $validMinutes = 15;
}

try {
    $pdo = getDBConnection();

    // Deactivate existing active sessions for this guru & class (optional clean up)
    $stmtClose = $pdo->prepare("UPDATE `sessions` SET `status` = 'closed' WHERE `guru_id` = :guru_id AND `class_name` = :class_name AND `status` = 'active'");
    $stmtClose->execute([
        'guru_id' => $_SESSION['user_id'],
        'class_name' => $className
    ]);

    // Generate unique secure token
    $token = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8)) . '-' . bin2hex(random_bytes(8));

    // Calculate expiration datetime
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$validMinutes minutes"));

    $stmtInsert = $pdo->prepare("INSERT INTO `sessions` (`guru_id`, `subject`, `class_name`, `journal_topic`, `journal_notes`, `token`, `valid_minutes`, `expires_at`, `status`) VALUES (:guru_id, :subject, :class_name, :journal_topic, :journal_notes, :token, :valid_minutes, :expires_at, 'active')");
    
    $stmtInsert->execute([
        'guru_id' => $_SESSION['user_id'],
        'subject' => $subject,
        'class_name' => $className,
        'journal_topic' => $journalTopic,
        'journal_notes' => $journalNotes,
        'token' => $token,
        'valid_minutes' => $validMinutes,
        'expires_at' => $expiresAt
    ]);

    $sessionId = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Sesi absensi berhasil dibuat.',
        'data' => [
            'session_id' => $sessionId,
            'subject' => $subject,
            'class_name' => $className,
            'token' => $token,
            'valid_minutes' => $validMinutes,
            'expires_at' => $expiresAt,
            'display_url' => 'generate_qr_page.php?session_id=' . $sessionId
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal membuat sesi: ' . $e->getMessage()]);
}
