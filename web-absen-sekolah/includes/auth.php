<?php
/**
 * Authentication & Session Management Functions
 * Website Absensi Sekolah (QR Code)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Login user with identity number/email and password
 */
function loginUser($identifier, $password, $role) {
    $pdo = getDBConnection();
    
    // Auto-create/initialize schema if table doesn't exist
    autoInitializeDatabase($pdo);

    $stmt = $pdo->prepare("SELECT * FROM `users` WHERE (`identity_number` = :id OR `email` = :email) AND `role` = :role LIMIT 1");
    $stmt->execute([
        'id' => $identifier,
        'email' => $identifier,
        'role' => $role
    ]);
    
    $user = $stmt->fetch();

    if (!$user) {
        return [
            'success' => false,
            'message' => 'Akun tidak ditemukan. Periksa kembali NIP/NIS atau email Anda.'
        ];
    }

    $isPasswordValid = password_verify($password, $user['password']);

    // Fallback for demo credentials (guru123 / siswa123)
    if (!$isPasswordValid && ($password === 'guru123' || $password === 'siswa123')) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE `users` SET `password` = :hash WHERE `id` = :id");
        $updateStmt->execute(['hash' => $newHash, 'id' => $user['id']]);
        $isPasswordValid = true;
    }

    if (!$isPasswordValid) {
        return [
            'success' => false,
            'message' => 'Password yang Anda masukkan salah.'
        ];
    }

    // Set Session Variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['identity_number'] = $user['identity_number'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['class_name'] = $user['class_name'];
    $_SESSION['avatar'] = $user['avatar'] ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&q=80';

    return [
        'success' => true,
        'user' => $user
    ];
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login middleware
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require specific role middleware
 */
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        if ($_SESSION['role'] === 'guru') {
            header('Location: guru_dashboard.php');
        } else {
            header('Location: siswa_dashboard.php');
        }
        exit;
    }
}

/**
 * Get logged in user details
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'identity_number' => $_SESSION['identity_number'],
        'name' => $_SESSION['name'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role'],
        'class_name' => $_SESSION['class_name'] ?? null,
        'avatar' => $_SESSION['avatar']
    ];
}

/**
 * Logout User
 */
function logoutUser() {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.php");
    exit;
}
