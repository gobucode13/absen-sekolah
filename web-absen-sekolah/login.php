<?php
/**
 * Login Page - Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'guru') {
        header('Location: guru_dashboard.php');
    } else {
        header('Location: siswa_dashboard.php');
    }
    exit;
}

$error = '';
$activeRole = $_POST['role'] ?? 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'siswa';

    if (empty($identifier) || empty($password)) {
        $error = 'Silakan isi nomor identitas (NIP/NIS) dan password.';
    } else {
        $result = loginUser($identifier, $password, $role);
        if ($result['success']) {
            if ($role === 'guru') {
                header('Location: guru_dashboard.php');
            } else {
                header('Location: siswa_dashboard.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

$pageTitle = 'Login - EduScanQR Absensi';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-slate-950 text-slate-100 min-h-full flex items-center justify-center p-4 relative overflow-x-hidden">

    <!-- Background Animated Elements -->
    <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-600/20 rounded-full filter blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/20 rounded-full filter blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-md my-8 relative z-10">
        
        <!-- Header Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30 mb-4">
                <i data-lucide="qr-code" class="w-9 h-9"></i>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-white">EduScan<span class="text-blue-500">QR</span></h1>
            <p class="text-slate-400 text-sm mt-1">Sistem Absensi Digital Berbasis QR Code</p>
        </div>

        <!-- Card Container -->
        <div class="glass-dark p-8 rounded-3xl border border-slate-800 shadow-2xl">
            
            <!-- Alert Error -->
            <?php if ($error): ?>
                <div class="mb-6 p-4 rounded-2xl bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-start gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">
                
                <!-- Role Selector Tabs -->
                <div class="mb-6">
                    <label class="block text-xs font-semibold uppercase text-slate-400 mb-2 tracking-wider">Pilih Hak Akses Log In</label>
                    <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-900/90 rounded-2xl border border-slate-800">
                        <button type="button" onclick="selectRole('siswa')" id="tabSiswa" class="role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-400 hover:text-white">
                            <i data-lucide="graduation-cap" class="w-4 h-4"></i> Siswa
                        </button>
                        <button type="button" onclick="selectRole('guru')" id="tabGuru" class="role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-400 hover:text-white">
                            <i data-lucide="user-check" class="w-4 h-4"></i> Guru
                        </button>
                    </div>
                    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($activeRole) ?>">
                </div>

                <!-- Input Identifier -->
                <div class="mb-5">
                    <label for="identifier" id="labelIdentifier" class="block text-xs font-semibold uppercase text-slate-300 mb-2">NIS / Email Siswa</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <input type="text" name="identifier" id="identifier" required placeholder="Masukkan NIS atau Email" 
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition text-sm">
                    </div>
                </div>

                <!-- Input Password -->
                <div class="mb-6">
                    <label for="password" class="block text-xs font-semibold uppercase text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i data-lucide="lock" class="w-5 h-5"></i>
                        </div>
                        <input type="password" name="password" id="password" required placeholder="••••••••" 
                            class="w-full pl-11 pr-4 py-3 bg-slate-900/80 border border-slate-700/80 rounded-2xl text-slate-100 placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition text-sm">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl shadow-lg shadow-blue-600/30 transition duration-200 flex items-center justify-center gap-2 group">
                    <span>Masuk ke Akun</span>
                    <i data-lucide="arrow-right" class="w-4 h-4 group-hover:translate-x-1 transition-transform"></i>
                </button>
            </form>

            <!-- Quick Demo Accounts -->
            <div class="mt-8 pt-6 border-t border-slate-800/80">
                <p class="text-xs text-center text-slate-400 font-semibold mb-3">⚡ Akses Cepat Demo:</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="fillDemo('guru')" class="p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 text-left transition group">
                        <div class="text-[11px] font-bold text-purple-400 group-hover:text-purple-300">Akun Guru</div>
                        <div class="text-[10px] text-slate-400">NIP: 19850101...</div>
                    </button>
                    <button type="button" onclick="fillDemo('siswa')" class="p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/60 text-left transition group">
                        <div class="text-[11px] font-bold text-emerald-400 group-hover:text-emerald-300">Akun Siswa</div>
                        <div class="text-[10px] text-slate-400">NIS: 22231001</div>
                    </button>
                </div>
            </div>

        </div>

    </div>

<script>
    lucide.createIcons();

    function selectRole(role) {
        document.getElementById('roleInput').value = role;
        const tabSiswa = document.getElementById('tabSiswa');
        const tabGuru = document.getElementById('tabGuru');
        const labelIdentifier = document.getElementById('labelIdentifier');
        const inputIdentifier = document.getElementById('identifier');

        if (role === 'guru') {
            tabGuru.className = "role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-md shadow-purple-500/20";
            tabSiswa.className = "role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-400 hover:text-white";
            labelIdentifier.innerText = "NIP / Email Guru";
            inputIdentifier.placeholder = "Masukkan NIP (19850101...)";
        } else {
            tabSiswa.className = "role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/20";
            tabGuru.className = "role-tab py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-slate-400 hover:text-white";
            labelIdentifier.innerText = "NIS / Email Siswa";
            inputIdentifier.placeholder = "Masukkan NIS (22231001...)";
        }
    }

    function fillDemo(role) {
        selectRole(role);
        if (role === 'guru') {
            document.getElementById('identifier').value = '198501012010011001';
            document.getElementById('password').value = 'guru123';
        } else {
            document.getElementById('identifier').value = '22231001';
            document.getElementById('password').value = 'siswa123';
        }
    }

    // Init active tab
    selectRole('<?= htmlspecialchars($activeRole) ?>');
</script>
</body>
</html>
