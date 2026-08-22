<?php
/**
 * Halaman Edit Profil (Foto & Password)
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$successMsg = '';
$errorMsg = '';

// Handling Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $avatarType = $_POST['avatar_type'] ?? 'file'; // 'file' or 'url'
        $newAvatarPath = $currentUser['avatar'];

        if (empty($name) || empty($email)) {
            $errorMsg = 'Nama dan Email tidak boleh kosong.';
        } else {
            // Handle File Upload if selected
            if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar_file'];
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $fileType = mime_content_type($file['tmp_name']);

                if ($file['size'] > 5 * 1024 * 1024) {
                    $errorMsg = 'Ukuran file foto maksimal 5 MB.';
                } elseif (!in_array($fileType, $allowedTypes) || !in_array($fileExt, $allowedExts)) {
                    $errorMsg = 'Format file tidak didukung. Harap unggah foto berformat JPG, PNG, WEBP, atau GIF.';
                } else {
                    $uploadDir = __DIR__ . '/assets/uploads/avatars/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'avatar_' . $currentUser['id'] . '_' . time() . '.' . $fileExt;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $newAvatarPath = 'assets/uploads/avatars/' . $fileName;
                    } else {
                        $errorMsg = 'Gagal menyimpan file foto yang diunggah.';
                    }
                }
            } elseif (!empty($_POST['avatar_url'])) {
                $url = trim($_POST['avatar_url']);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $newAvatarPath = $url;
                } else {
                    $errorMsg = 'URL Foto tidak valid.';
                }
            }

            if (empty($errorMsg)) {
                try {
                    $stmt = $pdo->prepare("UPDATE `users` SET `name` = :name, `email` = :email, `avatar` = :avatar WHERE `id` = :id");
                    $stmt->execute([
                        'name' => $name,
                        'email' => $email,
                        'avatar' => $newAvatarPath,
                        'id' => $currentUser['id']
                    ]);
                    $successMsg = 'Profil berhasil diperbarui!';
                    $currentUser = getCurrentUser(); // refresh user info
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errorMsg = 'Email sudah digunakan oleh pengguna lain.';
                    } else {
                        $errorMsg = 'Terjadi kesalahan sistem: ' . $e->getMessage();
                    }
                }
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMsg = 'Semua bidang password harus diisi.';
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = 'Password baru minimal 6 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = 'Konfirmasi password baru tidak cocok.';
        } else {
            // Check Current Password
            $stmt = $pdo->prepare("SELECT `password` FROM `users` WHERE `id` = :id LIMIT 1");
            $stmt->execute(['id' => $currentUser['id']]);
            $dbPassword = $stmt->fetchColumn();

            $isValid = password_verify($currentPassword, $dbPassword);

            if (!$isValid) {
                $errorMsg = 'Password saat ini yang Anda masukkan salah.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE `users` SET `password` = :hash WHERE `id` = :id");
                $updateStmt->execute([
                    'hash' => $newHash,
                    'id' => $currentUser['id']
                ]);
                $successMsg = 'Password berhasil diubah!';
            }
        }
    }
}

$pageTitle = 'Edit Profil Saya - EduScanQR';
include __DIR__ . '/includes/header.php';
$backUrl = ($currentUser['role'] === 'guru') ? 'guru_dashboard.php' : 'siswa_dashboard.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <!-- Header Navigation Bar -->
    <div class="flex items-center justify-between">
        <div>
            <a href="<?= $backUrl ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-500 hover:text-blue-600 transition mb-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Dashboard
            </a>
            <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2.5">
                <i data-lucide="user-cog" class="w-7 h-7 text-blue-600"></i>
                Pengaturan Profil Saya
            </h1>
            <p class="text-xs text-slate-500 font-medium">Perbarui informasi diri, foto profil, dan kata sandi akun Anda.</p>
        </div>

        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold capitalize shadow-sm border <?= $currentUser['role'] === 'guru' ? 'bg-purple-50 text-purple-700 border-purple-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200' ?>">
            <span class="w-2 h-2 rounded-full <?= $currentUser['role'] === 'guru' ? 'bg-purple-500' : 'bg-emerald-500' ?>"></span>
            <?= $currentUser['role'] ?> <?= $currentUser['class_name'] ? '('.htmlspecialchars($currentUser['class_name']).')' : '' ?>
        </span>
    </div>

    <!-- Alert Notifications -->
    <?php if ($successMsg): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 flex-shrink-0"></i>
            <span><?= htmlspecialchars($successMsg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($errorMsg): ?>
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-sm font-semibold flex items-center gap-3 shadow-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 flex-shrink-0"></i>
            <span><?= htmlspecialchars($errorMsg) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
        
        <!-- Left Column: Edit Profile & Photo -->
        <div class="md:col-span-7 space-y-6">
            <div class="glass-card p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                        Informasi Diri & Foto Profil
                    </h2>
                    <p class="text-xs text-slate-500">Ubah nama, email, dan foto avatar akun Anda</p>
                </div>

                <form action="profile.php" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="action" value="update_profile">

                    <!-- Avatar Preview & Upload -->
                    <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-slate-50 border border-slate-200/70">
                        <div class="relative group flex-shrink-0">
                            <img id="avatarPreview" src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" class="w-24 h-24 rounded-2xl object-cover border-4 border-white shadow-md">
                            <div class="absolute inset-0 bg-black/40 rounded-2xl flex items-center justify-center opacity-0 group-hover:opacity-100 transition pointer-events-none text-white">
                                <i data-lucide="camera" class="w-6 h-6"></i>
                            </div>
                        </div>

                        <div class="space-y-2 flex-grow text-center sm:text-left">
                            <label class="block text-xs font-bold text-slate-700">Unggah Foto Baru</label>
                            <label class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition cursor-pointer">
                                <i data-lucide="upload" class="w-4 h-4"></i> Pilih Foto dari HP / Komputer
                                <input type="file" name="avatar_file" accept="image/*" onchange="previewImage(this)" class="hidden">
                            </label>
                            <p class="text-[11px] text-slate-400">Format: JPG, PNG, WEBP, GIF. Maksimal 5 MB.</p>
                        </div>
                    </div>

                    <!-- URL Avatar Optional Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Atau Gunakan URL Foto (Opsional)</span>
                            <span class="text-[10px] text-slate-400 font-normal">Contoh: https://...</span>
                        </label>
                        <input type="url" name="avatar_url" placeholder="https://images.unsplash.com/..." value="<?= str_starts_with($currentUser['avatar'], 'http') ? htmlspecialchars($currentUser['avatar']) : '' ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Identity Number (Readonly) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            <?= $currentUser['role'] === 'guru' ? 'NIP / Nomor Identitas (Tetap)' : 'NIS / Nomor Induk Siswa (Tetap)' ?>
                        </label>
                        <input type="text" value="<?= htmlspecialchars($currentUser['identity_number']) ?>" readonly disabled class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 text-xs font-mono font-bold cursor-not-allowed">
                    </div>

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-xs font-bold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required value="<?= htmlspecialchars($currentUser['name']) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Email</label>
                        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($currentUser['email']) ?>" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs font-semibold focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition">
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-lg shadow-blue-500/25 transition flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan Profil
                    </button>
                </form>
            </div>
        </div>

        <!-- Right Column: Change Password -->
        <div class="md:col-span-5 space-y-6">
            <div class="glass-card p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-sm space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
                        <i data-lucide="key-round" class="w-5 h-5 text-amber-500"></i>
                        Ganti Kata Sandi (Password)
                    </h2>
                    <p class="text-xs text-slate-500">Amankan akun Anda dengan password baru</p>
                </div>

                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-xs font-bold text-slate-700 mb-1.5">Password Saat Ini</label>
                        <div class="relative">
                            <input type="password" id="current_password" name="current_password" required placeholder="Masukkan password lama" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition pr-10">
                            <button type="button" onclick="togglePasswordVisibility('current_password', this)" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-xs font-bold text-slate-700 mb-1.5">Password Baru</label>
                        <div class="relative">
                            <input type="password" id="new_password" name="new_password" required minlength="6" placeholder="Minimal 6 karakter" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition pr-10">
                            <button type="button" onclick="togglePasswordVisibility('new_password', this)" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Ulangi password baru" class="w-full px-4 py-3 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition pr-10">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', this)" class="absolute right-3 top-3 text-slate-400 hover:text-slate-600">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-[11px] space-y-1">
                        <div class="font-bold flex items-center gap-1.5">
                            <i data-lucide="shield-alert" class="w-4 h-4 text-amber-600"></i> Tips Keamanan:
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-amber-700 pl-1">
                            <li>Gunakan kombinasi huruf dan angka.</li>
                            <li>Password default bawaan akun: <b><?= $currentUser['role'] === 'guru' ? 'guru123' : 'siswa123' ?></b>.</li>
                        </ul>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-lg shadow-amber-500/25 transition flex items-center justify-center gap-2">
                        <i data-lucide="lock" class="w-4 h-4"></i> Ganti Password Sekarang
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-lucide', 'eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
