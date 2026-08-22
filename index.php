<?php
/**
 * Main Index Router
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
if ($user['role'] === 'guru') {
    header('Location: guru_dashboard.php');
    exit;
} else {
    header('Location: siswa_dashboard.php');
    exit;
}
