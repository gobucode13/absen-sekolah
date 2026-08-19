<?php
/**
 * Global Header Template
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Absensi Sekolah - Smart QR Attendance';
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#eff6ff',
                100: '#dbeafe',
                500: '#3b82f6',
                600: '#2563eb',
                700: '#1d4ed8',
                900: '#1e3a8a',
              }
            }
          }
        }
      }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/custom.css">

    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <!-- HTML5 QR Code Scanner Library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-full flex flex-col justify-between">

<?php if ($currentUser): ?>
<!-- Navigation Header -->
<header class="sticky top-0 z-40 bg-white/80 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Brand Logo -->
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-500/20 group-hover:scale-105 transition-transform">
                    <i data-lucide="qr-code" class="w-6 h-6"></i>
                </div>
                <div>
                    <span class="font-bold text-lg text-slate-900 leading-tight block">EduScan<span class="text-blue-600">QR</span></span>
                    <span class="text-xs text-slate-500 block font-medium">Sistem Absensi Digital</span>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-1">
                <?php if ($currentUser['role'] === 'guru'): ?>
                    <a href="guru_dashboard.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/80 transition flex items-center gap-2">
                        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard Guru
                    </a>
                <?php else: ?>
                    <a href="siswa_dashboard.php" class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 hover:text-blue-600 hover:bg-slate-100/80 transition flex items-center gap-2">
                        <i data-lucide="scan-line" class="w-4 h-4"></i> Scan Absen
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Profile & Action -->
            <div class="flex items-center gap-3">
                <a href="profile.php" class="flex items-center gap-3 bg-slate-100/80 hover:bg-slate-200/80 border border-slate-200 px-3 py-1.5 rounded-full transition group" title="Klik untuk Edit Profil & Password">
                    <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover border border-white shadow-sm group-hover:scale-105 transition">
                    <div class="text-left hidden sm:block">
                        <div class="text-xs font-bold text-slate-900 leading-tight group-hover:text-blue-600 transition"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="text-[10px] text-slate-500 capitalize flex items-center gap-1 font-medium">
                            <span class="inline-block w-2 h-2 rounded-full <?= $currentUser['role'] === 'guru' ? 'bg-purple-500' : 'bg-emerald-500' ?>"></span>
                            <?= $currentUser['role'] ?> <?= $currentUser['class_name'] ? '(' . htmlspecialchars($currentUser['class_name']) . ')' : '' ?>
                        </div>
                    </div>
                    <i data-lucide="user-cog" class="w-4 h-4 text-slate-400 group-hover:text-blue-600 transition ml-1 hidden sm:inline-block"></i>
                </a>

                <!-- Logout Button -->
                <a href="logout.php" class="p-2.5 rounded-xl text-slate-500 hover:text-red-600 hover:bg-red-50 transition" title="Keluar dari Akun">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                </a>
            </div>

        </div>
    </div>
</header>
<?php endif; ?>

<main class="flex-grow">
