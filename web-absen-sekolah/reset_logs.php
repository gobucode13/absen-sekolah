<?php
/**
 * Script Reset / Hapus Log & Riwayat Absensi (Testing Data Clear)
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';

// Memastikan hanya user logged-in yang bisa menjalankan (opsional) atau dapat diakses langsung untuk maintenance
$pdo = getDBConnection();
$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    try {
        // Disable foreign key checks momentarily to clean tables safely
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE `attendances`");
        $pdo->exec("TRUNCATE TABLE `sessions`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

        $status = 'success';
        $message = 'Semua log riwayat absensi siswa dan sesi presensi KBM guru berhasil dihapus bersih!';
    } catch (Exception $e) {
        $status = 'error';
        $message = 'Gagal mereset riwayat: ' . $e->getMessage();
    }
}

// Check current total counts
$stmtAttendances = $pdo->query("SELECT COUNT(*) FROM `attendances`");
$totalAttendances = $stmtAttendances->fetchColumn();

$stmtSessions = $pdo->query("SELECT COUNT(*) FROM `sessions`");
$totalSessions = $stmtSessions->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Log & Riwayat Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4 antialiased">

    <div class="max-w-md w-full bg-white rounded-3xl p-6 md:p-8 shadow-xl border border-slate-200/80 space-y-6">
        <div class="text-center space-y-2">
            <div class="w-14 h-14 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto shadow-inner">
                <i data-lucide="trash-2" class="w-7 h-7"></i>
            </div>
            <h1 class="text-xl font-extrabold text-slate-900">Reset Log & Riwayat Absensi</h1>
            <p class="text-xs text-slate-500">Gunakan fitur ini untuk membersihkan data absensi dan sesi pengujian/testing.</p>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold space-y-2">
                <div class="flex items-center gap-2 text-sm font-bold text-emerald-700">
                    <i data-lucide="check-circle-2" class="w-5 h-5"></i> Pembersihan Berhasil!
                </div>
                <p><?= htmlspecialchars($message) ?></p>
                <div class="pt-2">
                    <a href="index.php" class="inline-block w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-center rounded-xl font-bold transition">Kembali ke Beranda / Dashboard</a>
                </div>
            </div>
        <?php elseif ($status === 'error'): ?>
            <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs font-semibold flex items-center gap-2">
                <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
                <span><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Statistics Preview -->
        <div class="grid grid-cols-2 gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center">
            <div class="p-2">
                <div class="text-2xl font-black text-slate-800"><?= number_format($totalSessions) ?></div>
                <div class="text-[11px] text-slate-500 font-medium">Sesi Absensi Guru</div>
            </div>
            <div class="p-2 border-l border-slate-200">
                <div class="text-2xl font-black text-slate-800"><?= number_format($totalAttendances) ?></div>
                <div class="text-[11px] text-slate-500 font-medium">Presensi Siswa</div>
            </div>
        </div>

        <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs space-y-1">
            <div class="font-bold flex items-center gap-1.5">
                <i data-lucide="info" class="w-4 h-4 text-amber-600"></i> Informasi Penting:
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-amber-700 text-[11px] pl-1">
                <li>Tindakan ini <b>TIDAK menghapus akun</b> Guru atau Siswa.</li>
                <li>Tindakan ini menghapus semua riwayat presensi siswa & sesi QR kelas.</li>
            </ul>
        </div>

        <form action="reset_logs.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH riwayat absensi? Data yang dihapus tidak dapat dikembalikan.');">
            <button type="submit" name="confirm_reset" value="1" class="w-full py-3.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-lg shadow-red-500/25 transition flex items-center justify-center gap-2">
                <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus Seluruh Riwayat Absensi Sekarang
            </button>
        </form>

        <div class="text-center">
            <a href="index.php" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition">Batal / Kembali</a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
