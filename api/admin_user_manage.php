<?php
/**
 * API Endpoint: Admin User Management (Reset Password, Edit, Add, Delete)
 * Website Absensi Sekolah (QR Code)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak. Hanya Administrator yang dapat mengakses fitur ini.']);
    exit;
}

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$pdo = getDBConnection();
autoInitializeDatabase($pdo);

switch ($action) {
    case 'get_users':
        $search = trim($_GET['search'] ?? '');
        $roleFilter = trim($_GET['role'] ?? '');
        
        $sql = "SELECT `id`, `identity_number`, `name`, `email`, `role`, `class_name`, `avatar`, `created_at` FROM `users` WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (`name` LIKE :search OR `identity_number` LIKE :search OR `email` LIKE :search)";
            $params['search'] = "%$search%";
        }

        if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'guru', 'siswa'])) {
            $sql .= " AND `role` = :role";
            $params['role'] = $roleFilter;
        }

        $sql .= " ORDER BY `role` ASC, `name` ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $users]);
        exit;

    case 'reset_password':
        $userId = intval($_POST['user_id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'User tidak valid.']);
            exit;
        }

        if (strlen($newPassword) < 4) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 4 karakter.']);
            exit;
        }

        $hashedPass = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE `users` SET `password` = :pass WHERE `id` = :id");
        $stmt->execute([
            'pass' => $hashedPass,
            'id' => $userId
        ]);

        echo json_encode(['success' => true, 'message' => 'Password pengguna berhasil diperbarui!']);
        exit;

    case 'update_user':
        $userId = intval($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $identityNumber = trim($_POST['identity_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? 'siswa');
        $className = trim($_POST['class_name'] ?? '');

        if ($userId <= 0 || empty($name) || empty($identityNumber) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua bidang wajib (Nama, NIP/NIS, Email).']);
            exit;
        }

        if (!in_array($role, ['admin', 'guru', 'siswa'])) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid.']);
            exit;
        }

        // Check unique identity_number and email for other users
        $stmtCheck = $pdo->prepare("SELECT `id` FROM `users` WHERE (`identity_number` = :id_num OR `email` = :email) AND `id` != :user_id LIMIT 1");
        $stmtCheck->execute([
            'id_num' => $identityNumber,
            'email' => $email,
            'user_id' => $userId
        ]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'NIP/NIS atau Email sudah digunakan oleh akun lain.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE `users` SET `name` = :name, `identity_number` = :id_num, `email` = :email, `role` = :role, `class_name` = :class_name WHERE `id` = :id");
        $stmt->execute([
            'name' => $name,
            'id_num' => $identityNumber,
            'email' => $email,
            'role' => $role,
            'class_name' => ($role === 'siswa' && !empty($className)) ? $className : null,
            'id' => $userId
        ]);

        echo json_encode(['success' => true, 'message' => 'Data pengguna berhasil diperbarui.']);
        exit;

    case 'add_user':
        $name = trim($_POST['name'] ?? '');
        $identityNumber = trim($_POST['identity_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'siswa');
        $className = trim($_POST['class_name'] ?? '');

        if (empty($name) || empty($identityNumber) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua bidang wajib.']);
            exit;
        }

        if (!in_array($role, ['admin', 'guru', 'siswa'])) {
            echo json_encode(['success' => false, 'message' => 'Role tidak valid.']);
            exit;
        }

        // Check if identity_number or email exists
        $stmtCheck = $pdo->prepare("SELECT `id` FROM `users` WHERE `identity_number` = :id_num OR `email` = :email LIMIT 1");
        $stmtCheck->execute([
            'id_num' => $identityNumber,
            'email' => $email
        ]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'NIP/NIS atau Email sudah terdaftar.']);
            exit;
        }

        $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `users` (`identity_number`, `name`, `email`, `password`, `role`, `class_name`, `avatar`) VALUES (:id_num, :name, :email, :pass, :role, :class_name, 'assets/photo/pfp.jpg')");
        $stmt->execute([
            'id_num' => $identityNumber,
            'name' => $name,
            'email' => $email,
            'pass' => $hashedPass,
            'role' => $role,
            'class_name' => ($role === 'siswa' && !empty($className)) ? $className : null
        ]);

        echo json_encode(['success' => true, 'message' => 'Pengguna baru berhasil ditambahkan!']);
        exit;

    case 'delete_user':
        $userId = intval($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            echo json_encode(['success' => false, 'message' => 'User tidak valid.']);
            exit;
        }

        if ($userId === $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = :id");
        $stmt->execute(['id' => $userId]);

        echo json_encode(['success' => true, 'message' => 'Pengguna berhasil dihapus.']);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal.']);
        exit;
}
