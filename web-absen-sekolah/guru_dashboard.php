<?php
/**
 * Guru Dashboard - Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';
requireRole('guru');

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Action: Close a session manually
if (isset($_GET['action']) && $_GET['action'] === 'close' && isset($_GET['session_id'])) {
    $closeId = intval($_GET['session_id']);
    $stmtClose = $pdo->prepare("UPDATE `sessions` SET `status` = 'closed' WHERE `id` = :id AND `guru_id` = :guru_id");
    $stmtClose->execute(['id' => $closeId, 'guru_id' => $currentUser['id']]);
    header('Location: guru_dashboard.php?msg=session_closed');
    exit;
}

// Fetch Today's Overview Stats
$todayDate = date('Y-m-d');
$stmtSessionsCount = $pdo->prepare("SELECT COUNT(*) FROM `sessions` WHERE `guru_id` = :guru_id AND DATE(`created_at`) = :todayDate");
$stmtSessionsCount->execute(['guru_id' => $currentUser['id'], 'todayDate' => $todayDate]);
$totalSessionsToday = $stmtSessionsCount->fetchColumn();

$stmtHadirCount = $pdo->prepare("
    SELECT COUNT(a.id) 
    FROM `attendances` a
    JOIN `sessions` s ON a.session_id = s.id
    WHERE s.guru_id = :guru_id AND DATE(a.scanned_at) = :todayDate
");
$stmtHadirCount->execute(['guru_id' => $currentUser['id'], 'todayDate' => $todayDate]);
$totalHadirToday = $stmtHadirCount->fetchColumn();

// Fetch Active Sessions
$stmtActive = $pdo->prepare("
    SELECT s.*, 
    (SELECT COUNT(*) FROM `attendances` a WHERE a.session_id = s.id) as total_hadir
    FROM `sessions` s
    WHERE s.guru_id = :guru_id AND s.status = 'active' AND s.expires_at > NOW()
    ORDER BY s.id DESC
");
$stmtActive->execute(['guru_id' => $currentUser['id']]);
$activeSessions = $stmtActive->fetchAll();

// Fetch Session History
$stmtHistory = $pdo->prepare("
    SELECT s.*, 
    (SELECT COUNT(*) FROM `attendances` a WHERE a.session_id = s.id) as total_hadir
    FROM `sessions` s
    WHERE s.guru_id = :guru_id
    ORDER BY s.id DESC
    LIMIT 15
");
$stmtHistory->execute(['guru_id' => $currentUser['id']]);
$allSessions = $stmtHistory->fetchAll();

// Fetch Recent Attendances Log
$stmtLogs = $pdo->prepare("
    SELECT a.*, u.name as siswa_name, u.identity_number as siswa_nis, u.class_name, s.subject
    FROM `attendances` a
    JOIN `users` u ON a.siswa_id = u.id
    JOIN `sessions` s ON a.session_id = s.id
    WHERE s.guru_id = :guru_id
    ORDER BY a.scanned_at DESC
    LIMIT 10
");
$stmtLogs->execute(['guru_id' => $currentUser['id']]);
$recentLogs = $stmtLogs->fetchAll();

// Fetch Available Classes from Database (Users table)
$stmtClasses = $pdo->query("SELECT DISTINCT `class_name` FROM `users` WHERE `class_name` IS NOT NULL AND `class_name` != '' ORDER BY `class_name` ASC");
$availableClasses = $stmtClasses->fetchAll(PDO::FETCH_COLUMN);
if (empty($availableClasses)) {
    $availableClasses = ['X TKR', 'X KKR', 'XI TKR', 'XI BDPO', 'XII TKR', 'XII BDPO', '10 IPA 1', '10 IPA 2'];
}

$pageTitle = 'Dashboard Guru - EduScanQR';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Sidebar Navigation & Main Tab Layout -->
    <div class="flex flex-col lg:flex-row items-start gap-8">
        
        <!-- Sidebar Navigation (Kiri) -->
        <div class="w-full lg:w-64 flex-shrink-0 space-y-4">
            <div class="glass-card p-3 rounded-3xl border border-slate-200/80 shadow-sm space-y-1 bg-white">
                <div class="px-3 py-2 text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Navigasi Utama</div>
                
                <button onclick="switchGuruTab('sessions')" id="tabBtnSessions" class="w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 bg-blue-600 text-white shadow-md shadow-blue-500/20">
                    <i data-lucide="qr-code" class="w-4 h-4"></i>
                    <span>Sesi & Scanner QR</span>
                </button>
                
                <button onclick="switchGuruTab('recap')" id="tabBtnRecap" class="w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 text-slate-600 hover:bg-slate-100 hover:text-blue-600">
                    <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                    <span>Rekap & Ekspor Excel</span>
                </button>

                <a href="profile.php" class="w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 text-slate-600 hover:bg-slate-100 hover:text-blue-600">
                    <i data-lucide="user-cog" class="w-4 h-4 text-slate-500"></i>
                    <span>Pengaturan Profil</span>
                </a>
            </div>

            <!-- Quick Action Box -->
            <div class="glass-card p-5 rounded-3xl border border-slate-200/80 bg-gradient-to-br from-blue-50 to-indigo-50/70 space-y-3">
                <div class="text-xs font-bold text-blue-900 flex items-center gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 text-blue-600"></i>
                    <span>Aksi Cepat</span>
                </div>
                <p class="text-[11px] text-slate-600 leading-relaxed">Buat sesi presensi kelas baru dan tampilkan QR Code ke proyektor kelas.</p>
                <button onclick="openCreateModal()" class="w-full py-2.5 px-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i> Sesi Baru
                </button>
            </div>
        </div>

        <!-- Main Tab Content (Kanan) -->
        <div class="flex-1 w-full space-y-8">

            <!-- TAB 1: Sesi & Scanner QR Code -->
            <div id="tabContentSessions" class="space-y-8">
                <!-- Welcome Hero Banner -->
                <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white p-8 shadow-xl">
                    <div class="absolute top-0 right-0 -mr-12 -mt-12 w-64 h-64 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-300 text-xs font-semibold">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Panel Guru & Pengajar
                            </div>
                            <h1 class="text-3xl font-extrabold tracking-tight">Selamat Datang, <?= htmlspecialchars($currentUser['name']) ?>! 👋</h1>
                            <p class="text-slate-300 text-sm max-w-xl">Kelola absensi kelas secara otomatis dengan QR Code interaktif. Siswa dapat melakukan scan langsung melalui smartphone.</p>
                        </div>
                        <div>
                            <button onclick="openCreateModal()" class="px-6 py-3.5 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white font-bold rounded-2xl shadow-lg shadow-blue-500/30 transition transform hover:-translate-y-0.5 flex items-center gap-2 text-sm">
                                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                                Buat Sesi Absensi Baru
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass-card p-6 rounded-3xl border border-slate-200/80 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="calendar-check" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalSessionsToday) ?></div>
                            <div class="text-xs text-slate-500 font-medium mt-0.5">Sesi Dibuat Hari Ini</div>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-3xl border border-slate-200/80 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="users" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900"><?= number_format($totalHadirToday) ?></div>
                            <div class="text-xs text-slate-500 font-medium mt-0.5">Siswa Hadir Hari Ini</div>
                        </div>
                    </div>

                    <div class="glass-card p-6 rounded-3xl border border-slate-200/80 flex items-center gap-5">
                        <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="radio" class="w-7 h-7"></i>
                        </div>
                        <div>
                            <div class="text-2xl font-extrabold text-slate-900"><?= count($activeSessions) ?></div>
                            <div class="text-xs text-slate-500 font-medium mt-0.5">Sesi Berjalan (Aktif)</div>
                        </div>
                    </div>
                </div>

                <!-- Active Sessions Spotlight -->
                <?php if (!empty($activeSessions)): ?>
                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        Sesi QR Code Sedang Aktif
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($activeSessions as $active): ?>
                            <div class="glass-card p-6 rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-50/50 to-white relative overflow-hidden shadow-sm">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-2">
                                            <?= htmlspecialchars($active['class_name']) ?>
                                        </span>
                                        <h3 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($active['subject']) ?></h3>
                                        <p class="text-xs text-slate-500 mt-1">Berakhir pada: <b><?= date('H:i:s, d M Y', strtotime($active['expires_at'])) ?></b></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-3xl font-black text-emerald-600"><?= $active['total_hadir'] ?></div>
                                        <div class="text-[11px] text-slate-500 font-medium">Siswa Hadir</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 pt-4 border-t border-slate-200/80">
                                    <a href="generate_qr_page.php?session_id=<?= $active['id'] ?>" target="_blank" class="flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs text-center transition flex items-center justify-center gap-2">
                                        <i data-lucide="qr-code" class="w-4 h-4"></i> Tampilkan Layar QR
                                    </a>
                                    <a href="guru_dashboard.php?action=close&session_id=<?= $active['id'] ?>" onclick="return confirm('Tutup sesi absensi ini sekarang?')" class="py-2.5 px-4 bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 font-bold rounded-xl text-xs transition border border-slate-200">
                                        Tutup Sesi
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Table of All Sessions History -->
                <div class="glass-card rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
                    <div class="p-6 border-b border-slate-200/80 flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Riwayat Sesi Absensi</h3>
                            <p class="text-xs text-slate-500">Daftar sesi absensi yang telah dibuat sebelumnya</p>
                        </div>
                        <button onclick="openCreateModal()" class="py-2 px-4 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-xl text-xs font-bold transition flex items-center gap-2">
                            <i data-lucide="plus" class="w-4 h-4"></i> Sesi Baru
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100/70 text-slate-500 text-xs font-bold uppercase tracking-wider">
                                    <th class="py-3.5 px-6">Mata Pelajaran</th>
                                    <th class="py-3.5 px-6">Kelas</th>
                                    <th class="py-3.5 px-6">Durasi</th>
                                    <th class="py-3.5 px-6">Dibuat Pada</th>
                                    <th class="py-3.5 px-6 text-center">Jumlah Hadir</th>
                                    <th class="py-3.5 px-6 text-center">Status</th>
                                    <th class="py-3.5 px-6 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 text-sm">
                                <?php if (empty($allSessions)): ?>
                                    <tr>
                                        <td colspan="7" class="py-8 text-center text-slate-400 text-sm">Belum ada sesi absensi yang dibuat.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allSessions as $s): ?>
                                        <tr class="hover:bg-slate-50/80 transition">
                                            <td class="py-4 px-6">
                                                <div class="font-semibold text-slate-900"><?= htmlspecialchars($s['subject']) ?></div>
                                                <?php if (!empty($s['journal_topic'])): ?>
                                                    <div class="text-xs text-blue-600 font-medium flex items-center gap-1 mt-0.5">
                                                        <i data-lucide="book-open" class="w-3 h-3"></i>
                                                        <span class="truncate max-w-[180px]" title="<?= htmlspecialchars($s['journal_topic']) ?>"><?= htmlspecialchars($s['journal_topic']) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6 font-medium text-slate-600"><?= htmlspecialchars($s['class_name']) ?></td>
                                            <td class="py-4 px-6 text-slate-500 text-xs"><?= $s['valid_minutes'] ?> Menit</td>
                                            <td class="py-4 px-6 text-slate-500 text-xs"><?= date('d M Y, H:i', strtotime($s['created_at'])) ?></td>
                                            <td class="py-4 px-6 text-center">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700">
                                                    <?= $s['total_hadir'] ?> Siswa
                                                </span>
                                            </td>
                                            <td class="py-4 px-6 text-center">
                                                <?php if ($s['status'] === 'active' && strtotime($s['expires_at']) > time()): ?>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-500">
                                                        Selesai
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-6 text-right space-x-1.5 flex items-center justify-end">
                                                <button onclick="openAttendanceManageModal(<?= $s['id'] ?>, <?= htmlspecialchars(json_encode($s['subject'])) ?>, <?= htmlspecialchars(json_encode($s['class_name'])) ?>)" class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2.5 py-1.5 rounded-lg transition" title="Kelola / Edit Presensi & Keterangan Izin/Sakit">
                                                    <i data-lucide="user-cog" class="w-3.5 h-3.5"></i> Edit Absen
                                                </button>
                                                <?php if (!empty($s['journal_topic'])): ?>
                                                    <button onclick="openJournalModal(<?= htmlspecialchars(json_encode($s['journal_topic'])) ?>, <?= htmlspecialchars(json_encode($s['journal_notes'] ?? '')) ?>, <?= htmlspecialchars(json_encode($s['subject'])) ?>, <?= htmlspecialchars(json_encode($s['class_name'])) ?>, <?= htmlspecialchars(json_encode(date('d M Y, H:i', strtotime($s['created_at'])))) ?>, <?= $s['total_hadir'] ?>)" class="inline-flex items-center gap-1 text-xs font-bold text-slate-600 hover:text-blue-600 bg-slate-100 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition">
                                                        <i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i> Jurnal
                                                    </button>
                                                <?php endif; ?>
                                                <a href="generate_qr_page.php?session_id=<?= $s['id'] ?>" target="_blank" class="inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2.5 py-1.5 rounded-lg transition">
                                                    <i data-lucide="qr-code" class="w-3.5 h-3.5"></i> Layar QR
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Attendance Activity Log -->
                <div class="glass-card rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-slate-900 mb-1">Aktivitas Absensi Terbaru</h3>
                    <p class="text-xs text-slate-500 mb-6">Log realtime siswa yang baru saja melakukan scan QR Code</p>

                    <div class="space-y-3">
                        <?php if (empty($recentLogs)): ?>
                            <p class="text-center text-slate-400 text-sm py-4">Belum ada siswa yang melakukan scan hari ini.</p>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-50/80 border border-slate-200/60 hover:bg-white transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-500/10 text-blue-600 flex items-center justify-center font-bold text-sm">
                                            <i data-lucide="user-check" class="w-5 h-5"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900"><?= htmlspecialchars($log['siswa_name']) ?> <span class="text-xs font-normal text-slate-500">(<?= htmlspecialchars($log['siswa_nis']) ?>)</span></div>
                                            <div class="text-xs text-slate-500 font-medium"><?= htmlspecialchars($log['subject']) ?> &bull; Kelas <?= htmlspecialchars($log['class_name']) ?></div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-block px-3 py-1 rounded-full badge-hadir text-xs font-bold">Hadir</span>
                                        <div class="text-[10px] text-slate-400 mt-1"><?= date('H:i:s, d M Y', strtotime($log['scanned_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Rekapitulasi & Ekspor Excel -->
            <div id="tabContentRecap" class="space-y-6 hidden">
                <!-- Header Banner Rekap -->
                <div class="glass-card p-6 rounded-3xl border border-slate-200/80 bg-gradient-to-r from-slate-900 to-blue-950 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold uppercase tracking-wider">Laporan Kehadiran Siswa</span>
                        <h2 class="text-2xl font-bold mt-1 text-white">Rekapitulasi Presensi & Ekspor Excel</h2>
                        <p class="text-xs text-slate-400 mt-1">Filter data presensi per kelas & tanggal, lalu unduh dalam format Microsoft Excel (.xls).</p>
                    </div>
                    <div>
                        <button onclick="downloadExcelRecap()" class="px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 transition flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i> Download File Excel (.xls)
                        </button>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="glass-card p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4 bg-white">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i data-lucide="filter" class="w-4 h-4 text-blue-600"></i> Filter Rekapitulasi Presensi
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Target Kelas</label>
                            <select id="recapFilterClass" onchange="loadRecapData()" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold focus:outline-none focus:border-blue-500">
                                <option value="all">Semua Kelas</option>
                                <?php foreach ($availableClasses as $classItem): ?>
                                    <option value="<?= htmlspecialchars($classItem) ?>"><?= htmlspecialchars($classItem) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Mulai</label>
                            <input type="date" id="recapFilterStartDate" value="<?= date('Y-m-01') ?>" onchange="loadRecapData()" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Tanggal Selesai</label>
                            <input type="date" id="recapFilterEndDate" value="<?= date('Y-m-t') ?>" onchange="loadRecapData()" class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold focus:outline-none focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Cari Nama / NIS</label>
                            <input type="text" id="recapSearchInput" onkeyup="filterRecapTableRows()" placeholder="Ketik nama / NIS..." class="w-full px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Live Preview Table -->
                <div class="glass-card rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm bg-white">
                    <div class="p-4 bg-slate-50 border-b border-slate-200/80 flex items-center justify-between">
                        <div class="text-xs font-bold text-slate-700 flex items-center gap-2">
                            <i data-lucide="table" class="w-4 h-4 text-blue-600"></i>
                            <span>Hasil Rekapitulasi Presensi</span>
                        </div>
                        <div class="text-xs text-slate-500 font-medium" id="recapTotalInfo">Memuat...</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3 px-4 text-center w-12">No</th>
                                    <th class="py-3 px-4">NIS</th>
                                    <th class="py-3 px-4">Nama Siswa</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4 text-center text-emerald-700 bg-emerald-50">Hadir</th>
                                    <th class="py-3 px-4 text-center text-amber-700 bg-amber-50">Izin</th>
                                    <th class="py-3 px-4 text-center text-purple-700 bg-purple-50">Sakit</th>
                                    <th class="py-3 px-4 text-center text-rose-700 bg-rose-50">Alpa</th>
                                    <th class="py-3 px-4 text-center">Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody id="recapTableBody" class="divide-y divide-slate-100">
                                <!-- Dynamic Rows -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal Create QR Code Session & Jurnal KBM -->
<div id="createModal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative max-h-[90vh] overflow-y-auto">
        <button onclick="closeCreateModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                <i data-lucide="notebook-pen" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Buat Sesi & Isi Jurnal KBM</h3>
                <p class="text-xs text-slate-500">Lengkapi jurnal pembelajaran & tentukan durasi sesi QR</p>
            </div>
        </div>

        <form id="createSessionForm" onsubmit="handleCreateSession(event)" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Mata Pelajaran</label>
                    <input type="text" name="subject" required placeholder="Contoh: Matematika Wajib" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Target Kelas</label>
                    <select name="class_name" required class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-blue-500">
                        <option value="" disabled selected>-- Pilih Target Kelas --</option>
                        <?php foreach ($availableClasses as $classItem): ?>
                            <option value="<?= htmlspecialchars($classItem) ?>"><?= htmlspecialchars($classItem) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Field Jurnal KBM -->
            <div class="p-4 bg-blue-50/50 rounded-2xl border border-blue-100/80 space-y-3">
                <div class="flex items-center gap-2 text-xs font-bold text-blue-800 uppercase tracking-wider">
                    <i data-lucide="book-open" class="w-4 h-4 text-blue-600"></i>
                    <span>Informasi Jurnal KBM</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Materi / Topik Pembelajaran <span class="text-red-500">*</span></label>
                    <input type="text" name="journal_topic" required placeholder="Contoh: Bab 2 - Persamaan & Fungsi Kuadrat" class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-sm focus:outline-none focus:border-blue-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Ringkasan KBM <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <textarea name="journal_notes" rows="3" placeholder="Contoh: Penyampaian teori grafik fungsi kuadrat, diskusi kelompok, dan tugas latihan soal hal 45." class="w-full px-4 py-2.5 rounded-xl bg-white border border-slate-200 text-sm focus:outline-none focus:border-blue-500"></textarea>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Masa Berlaku Sesi (Menit)</label>
                <select name="valid_minutes" class="w-full px-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-sm focus:outline-none focus:border-blue-500">
                    <option value="5">5 Menit (Cepat)</option>
                    <option value="10">10 Menit</option>
                    <option value="15" selected>15 Menit (Direkomendasikan)</option>
                    <option value="30">30 Menit</option>
                    <option value="60">60 Menit</option>
                </select>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeCreateModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-2xl text-sm transition">Batal</button>
                <button type="submit" id="btnSubmitSession" class="flex-1 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-2xl text-sm shadow-md shadow-blue-500/20 transition flex items-center justify-center gap-2">
                    <span>Simpan & Tampilkan QR</span>
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal View Detail Jurnal KBM -->
<div id="journalModal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 relative">
        <button onclick="closeJournalModal()" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 p-1">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                <i data-lucide="book-marked" class="w-6 h-6"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-900">Detail Jurnal KBM</h3>
                <p class="text-xs text-slate-500" id="journalMetaInfo">Informasi Pembelajaran</p>
            </div>
        </div>

        <div class="space-y-4 text-sm">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Materi / Topik Pembelajaran</div>
                <div id="viewJournalTopic" class="font-bold text-slate-900 text-base"></div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Catatan / Ringkasan KBM</div>
                <div id="viewJournalNotes" class="text-slate-700 whitespace-pre-line text-sm leading-relaxed mt-1"></div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="p-3 rounded-xl bg-blue-50/60 border border-blue-100 text-center">
                    <div class="text-[10px] font-bold text-blue-600 uppercase">Total Siswa Hadir</div>
                    <div id="viewJournalStudents" class="text-lg font-extrabold text-blue-900 mt-0.5"></div>
                </div>
                <div class="p-3 rounded-xl bg-slate-100/70 border border-slate-200 text-center">
                    <div class="text-[10px] font-bold text-slate-500 uppercase">Waktu Pelaksanaan</div>
                    <div id="viewJournalDate" class="text-xs font-bold text-slate-700 mt-1"></div>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
            <button onclick="closeJournalModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">Tutup</button>
        </div>
    </div>
</div>

<!-- Modal Manage Attendance & Notes -->
<div id="manageAttendanceModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-3xl w-full max-h-[90vh] flex flex-col p-6 shadow-2xl border border-slate-100 relative animate-in fade-in zoom-in duration-200">
        
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold">
                    <i data-lucide="user-cog" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900" id="manageAttendanceTitle">Kelola Presensi Siswa</h3>
                    <p class="text-xs text-slate-500" id="manageAttendanceSub">Edit status kehadiran & catatan keterangan izin/sakit</p>
                </div>
            </div>
            <button onclick="closeAttendanceManageModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Summary Bar -->
        <div class="grid grid-cols-5 gap-2 my-4" id="manageSummaryContainer">
            <!-- Dynamically Populated -->
        </div>

        <!-- Student List Table (Scrollable) -->
        <div class="overflow-y-auto flex-1 pr-1 border border-slate-100 rounded-2xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold sticky top-0 border-b border-slate-200">
                    <tr>
                        <th class="py-3 px-4">Nama Siswa</th>
                        <th class="py-3 px-4">Status Presensi</th>
                        <th class="py-3 px-4">Keterangan (Izin / Sakit / Alpa)</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="manageStudentListBody" class="divide-y divide-slate-100">
                    <!-- Dynamic Rows -->
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Perubahan disimpan secara langsung.</span>
            <button onclick="closeAttendanceManageModal()" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-xl transition">Selesai</button>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('createModal').classList.remove('hidden');
    }

    function closeCreateModal() {
        document.getElementById('createModal').classList.add('hidden');
    }

    function openJournalModal(topic, notes, subject, className, dateStr, count) {
        document.getElementById('viewJournalTopic').innerText = topic || '-';
        document.getElementById('viewJournalNotes').innerText = notes || 'Tidak ada catatan tambahan.';
        document.getElementById('journalMetaInfo').innerText = `${subject} • Kelas ${className}`;
        document.getElementById('viewJournalStudents').innerText = `${count} Siswa`;
        document.getElementById('viewJournalDate').innerText = dateStr;
        document.getElementById('journalModal').classList.remove('hidden');
    }

    function closeJournalModal() {
        document.getElementById('journalModal').classList.add('hidden');
    }

    let currentManageSessionId = null;

    async function openAttendanceManageModal(sessionId, subject, className) {
        currentManageSessionId = sessionId;
        document.getElementById('manageAttendanceTitle').innerText = `Kelola Presensi - ${subject}`;
        document.getElementById('manageAttendanceSub').innerText = `Kelas: ${className} • Mengubah status & keterangan izin/sakit`;
        document.getElementById('manageAttendanceModal').classList.remove('hidden');
        
        await loadSessionStudents(sessionId);
    }

    function closeAttendanceManageModal() {
        document.getElementById('manageAttendanceModal').classList.add('hidden');
        currentManageSessionId = null;
    }

    async function loadSessionStudents(sessionId) {
        const listBody = document.getElementById('manageStudentListBody');
        listBody.innerHTML = `<tr><td colspan="4" class="py-8 text-center text-slate-400"><i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto mb-2 text-blue-500"></i>Memuat data siswa...</td></tr>`;
        lucide.createIcons();

        try {
            const resp = await fetch(`api/get_session_attendances.php?session_id=${sessionId}`);
            const result = await resp.json();

            if (!result.success) {
                listBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-red-500 font-semibold">${result.message}</td></tr>`;
                return;
            }

            const data = result.data;
            renderSummaryBar(data.summary);
            renderStudentTableRows(data.students, sessionId);
        } catch (err) {
            listBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-red-500 font-semibold">Gagal memuat data presensi.</td></tr>`;
        }
    }

    function renderSummaryBar(s) {
        const container = document.getElementById('manageSummaryContainer');
        container.innerHTML = `
            <div class="p-2 rounded-xl bg-blue-50 text-blue-800 border border-blue-100 text-center">
                <div class="text-[10px] font-bold uppercase">Hadir</div>
                <div class="text-base font-extrabold">${s.hadir}</div>
            </div>
            <div class="p-2 rounded-xl bg-amber-50 text-amber-800 border border-amber-100 text-center">
                <div class="text-[10px] font-bold uppercase">Izin</div>
                <div class="text-base font-extrabold">${s.izin}</div>
            </div>
            <div class="p-2 rounded-xl bg-purple-50 text-purple-800 border border-purple-100 text-center">
                <div class="text-[10px] font-bold uppercase">Sakit</div>
                <div class="text-base font-extrabold">${s.sakit}</div>
            </div>
            <div class="p-2 rounded-xl bg-rose-50 text-rose-800 border border-rose-100 text-center">
                <div class="text-[10px] font-bold uppercase">Alpa</div>
                <div class="text-base font-extrabold">${s.alpa}</div>
            </div>
            <div class="p-2 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 text-center">
                <div class="text-[10px] font-bold uppercase">Belum Absen</div>
                <div class="text-base font-extrabold">${s.belum_absen}</div>
            </div>
        `;
    }

    function renderStudentTableRows(students, sessionId) {
        const listBody = document.getElementById('manageStudentListBody');
        if (students.length === 0) {
            listBody.innerHTML = `<tr><td colspan="4" class="py-6 text-center text-slate-400">Tidak ada siswa terdaftar di kelas ini.</td></tr>`;
            return;
        }

        listBody.innerHTML = students.map(s => {
            const currentStatus = s.status ? s.status.toLowerCase() : 'belum_absen';
            const notesValue = s.notes ? s.notes : '';
            
            return `
                <tr class="hover:bg-slate-50/80 transition" id="studentRow_${s.siswa_id}">
                    <td class="py-3 px-4">
                        <div class="font-bold text-slate-900">${escapeHtml(s.siswa_name)}</div>
                        <div class="text-[10px] text-slate-400">NIS: ${escapeHtml(s.siswa_nis)}</div>
                    </td>
                    <td class="py-3 px-4">
                        <select id="selectStatus_${s.siswa_id}" class="px-3 py-1.5 rounded-xl border border-slate-200 bg-white font-bold text-xs focus:outline-none focus:border-blue-500">
                            <option value="hadir" ${currentStatus === 'hadir' ? 'selected' : ''}>🟢 Hadir</option>
                            <option value="izin" ${currentStatus === 'izin' ? 'selected' : ''}>🟡 Izin</option>
                            <option value="sakit" ${currentStatus === 'sakit' ? 'selected' : ''}>🟣 Sakit</option>
                            <option value="alpa" ${currentStatus === 'alpa' ? 'selected' : ''}>🔴 Alpa</option>
                            ${currentStatus === 'belum_absen' ? '<option value="belum_absen" selected disabled>⚪ Belum Absen</option>' : ''}
                        </select>
                    </td>
                    <td class="py-3 px-4">
                        <input type="text" id="inputNotes_${s.siswa_id}" value="${escapeHtml(notesValue)}" placeholder="Contoh: Surat Dokter / Izin keluarga" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 bg-slate-50 text-xs focus:bg-white focus:outline-none focus:border-blue-500">
                    </td>
                    <td class="py-3 px-4 text-right">
                        <button onclick="saveStudentAttendance(${sessionId}, ${s.siswa_id})" id="btnSave_${s.siswa_id}" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs transition inline-flex items-center gap-1 ml-auto">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        lucide.createIcons();
    }

    async function saveStudentAttendance(sessionId, siswaId) {
        const statusSelect = document.getElementById(`selectStatus_${siswaId}`);
        const notesInput = document.getElementById(`inputNotes_${siswaId}`);
        const saveBtn = document.getElementById(`btnSave_${siswaId}`);

        const status = statusSelect.value;
        const notes = notesInput.value;

        if (status === 'belum_absen') {
            alert('Pilih status kehadiran terlebih dahulu (Hadir, Izin, Sakit, atau Alpa).');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i>`;
        lucide.createIcons();

        try {
            const formData = new FormData();
            formData.append('session_id', sessionId);
            formData.append('siswa_id', siswaId);
            formData.append('status', status);
            formData.append('notes', notes);

            const resp = await fetch('api/update_attendance.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                saveBtn.innerHTML = `<i data-lucide="check-circle" class="w-3.5 h-3.5 text-emerald-300"></i> Tersimpan`;
                saveBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                saveBtn.classList.add('bg-emerald-600');
                
                setTimeout(() => {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan`;
                    saveBtn.classList.remove('bg-emerald-600');
                    saveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    lucide.createIcons();
                }, 1800);

                await loadSessionStudents(sessionId);
            } else {
                alert(result.message || 'Gagal memperbarui status.');
                saveBtn.disabled = false;
                saveBtn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan`;
                lucide.createIcons();
            }
        } catch (err) {
            alert('Terjadi kesalahan koneksi.');
            saveBtn.disabled = false;
            saveBtn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan`;
            lucide.createIcons();
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    async function handleCreateSession(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitSession');
        btn.disabled = true;
        btn.innerText = "Memproses...";

        const formData = new FormData(e.target);

        try {
            const resp = await fetch('api/generate_qr.php', {
                method: 'POST',
                body: formData
            });
            const result = await resp.json();

            if (result.success) {
                window.location.href = result.data.display_url;
            } else {
                alert(result.message || 'Gagal membuat sesi absensi.');
                btn.disabled = false;
                btn.innerText = "Simpan & Tampilkan QR";
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan.');
            btn.disabled = false;
            btn.innerText = "Simpan & Tampilkan QR";
        }
    }

    function switchGuruTab(tabName) {
        const tabBtnSessions = document.getElementById('tabBtnSessions');
        const tabBtnRecap = document.getElementById('tabBtnRecap');
        const tabContentSessions = document.getElementById('tabContentSessions');
        const tabContentRecap = document.getElementById('tabContentRecap');

        if (tabName === 'sessions') {
            tabBtnSessions.className = "w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 bg-blue-600 text-white shadow-md shadow-blue-500/20";
            tabBtnRecap.className = "w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 text-slate-600 hover:bg-slate-100 hover:text-blue-600";
            tabContentSessions.classList.remove('hidden');
            tabContentRecap.classList.add('hidden');
        } else {
            tabBtnRecap.className = "w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 bg-blue-600 text-white shadow-md shadow-blue-500/20";
            tabBtnSessions.className = "w-full px-4 py-3 rounded-2xl text-xs font-bold transition flex items-center gap-3 text-slate-600 hover:bg-slate-100 hover:text-blue-600";
            tabContentRecap.classList.remove('hidden');
            tabContentSessions.classList.add('hidden');
            
            loadRecapData();
        }
    }

    let cachedRecapStudents = [];

    async function loadRecapData() {
        const className = document.getElementById('recapFilterClass').value;
        const startDate = document.getElementById('recapFilterStartDate').value;
        const endDate = document.getElementById('recapFilterEndDate').value;
        const body = document.getElementById('recapTableBody');

        body.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400"><i data-lucide="loader-2" class="w-5 h-5 animate-spin mx-auto mb-2 text-blue-500"></i>Memuat data rekapitulasi...</td></tr>`;
        lucide.createIcons();

        try {
            const resp = await fetch(`api/get_recap_data.php?class_name=${encodeURIComponent(className)}&start_date=${startDate}&end_date=${endDate}`);
            const result = await resp.json();

            if (!result.success) {
                body.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-semibold">${result.message}</td></tr>`;
                return;
            }

            const data = result.data;
            cachedRecapStudents = data.students;
            document.getElementById('recapTotalInfo').innerText = `Total Sesi: ${data.total_sessions} • Total Siswa: ${data.students.length}`;
            renderRecapTableRows(cachedRecapStudents, data.total_sessions);
        } catch (err) {
            body.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 font-semibold">Gagal memuat rekapitulasi.</td></tr>`;
        }
    }

    function renderRecapTableRows(students, totalSessions) {
        const body = document.getElementById('recapTableBody');
        if (students.length === 0) {
            body.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400">Tidak ada data siswa ditemukan untuk kriteria filter ini.</td></tr>`;
            return;
        }

        let no = 1;
        body.innerHTML = students.map(s => {
            const pct = s.percentage;

            return `
                <tr class="hover:bg-slate-50/80 transition recap-row">
                    <td class="py-3 px-4 text-center text-slate-400 font-mono">${no++}</td>
                    <td class="py-3 px-4 font-mono font-semibold text-slate-600">${escapeHtml(s.siswa_nis)}</td>
                    <td class="py-3 px-4 font-bold text-slate-900 search-name">${escapeHtml(s.siswa_name)}</td>
                    <td class="py-3 px-4 font-medium text-slate-600">${escapeHtml(s.class_name)}</td>
                    <td class="py-3 px-4 text-center font-bold text-emerald-700 bg-emerald-50/50">${s.hadir}</td>
                    <td class="py-3 px-4 text-center font-bold text-amber-700 bg-amber-50/50">${s.izin}</td>
                    <td class="py-3 px-4 text-center font-bold text-purple-700 bg-purple-50/50">${s.sakit}</td>
                    <td class="py-3 px-4 text-center font-bold text-rose-700 bg-rose-50/50">${s.alpa}</td>
                    <td class="py-3 px-4 text-center font-bold text-slate-900">${pct}%</td>
                </tr>
            `;
        }).join('');
    }

    function filterRecapTableRows() {
        const query = document.getElementById('recapSearchInput').value.toLowerCase();
        const rows = document.querySelectorAll('.recap-row');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }

    function downloadExcelRecap() {
        const className = document.getElementById('recapFilterClass').value;
        const startDate = document.getElementById('recapFilterStartDate').value;
        const endDate = document.getElementById('recapFilterEndDate').value;
        
        window.location.href = `api/export_excel.php?class_name=${encodeURIComponent(className)}&start_date=${startDate}&end_date=${endDate}`;
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
