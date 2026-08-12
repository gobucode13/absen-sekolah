<?php
/**
 * Fullscreen / Live Proyeksi QR Code Display for Guru
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';
requireRole('guru');

$sessionId = intval($_GET['session_id'] ?? 0);
if ($sessionId <= 0) {
    header('Location: guru_dashboard.php');
    exit;
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM `sessions` WHERE `id` = :id AND `guru_id` = :guru_id LIMIT 1");
$stmt->execute(['id' => $sessionId, 'guru_id' => $_SESSION['user_id']]);
$session = $stmt->fetch();

if (!$session) {
    die("Sesi tidak ditemukan atau Anda tidak memiliki akses.");
}

$pageTitle = 'Layar Absensi QR Code - ' . htmlspecialchars($session['subject']);
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
    <!-- QRCode JS Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body class="bg-slate-950 text-white min-h-full flex flex-col justify-between p-4 md:p-8 relative overflow-x-hidden">

    <!-- Top Navigation Header -->
    <div class="flex items-center justify-between gap-4 max-w-7xl mx-auto w-full z-10">
        <a href="guru_dashboard.php" class="px-4 py-2.5 rounded-2xl bg-slate-900 border border-slate-800 hover:bg-slate-800 text-slate-300 hover:text-white font-semibold text-xs transition flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali ke Dashboard
        </a>
        <div class="flex items-center gap-3">
            <span id="sessionBadge" class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span> SESI AKTIF
            </span>
            <button onclick="toggleFullScreen()" class="px-4 py-2.5 rounded-2xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow-lg shadow-blue-600/30 transition flex items-center gap-2">
                <i data-lucide="maximize" class="w-4 h-4"></i> Fullscreen
            </button>
        </div>
    </div>

    <!-- Main Content Container -->
    <div class="max-w-7xl mx-auto w-full my-auto py-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center z-10">
        
        <!-- Left Column: Session Info & QR Code Display -->
        <div class="lg:col-span-7 text-center space-y-6">
            <div>
                <span class="px-4 py-1.5 rounded-full bg-blue-500/10 border border-blue-400/30 text-blue-400 text-xs font-extrabold uppercase tracking-widest">
                    KELAS <?= htmlspecialchars($session['class_name']) ?>
                </span>
                <h1 class="text-3xl md:text-5xl font-black tracking-tight mt-3 text-white"><?= htmlspecialchars($session['subject']) ?></h1>
                
                <?php if (!empty($session['journal_topic'])): ?>
                <div class="mt-4 max-w-xl mx-auto p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-left backdrop-blur-sm">
                    <div class="flex items-center gap-2 text-xs font-bold text-blue-400 uppercase tracking-wider mb-1">
                        <i data-lucide="book-open" class="w-4 h-4"></i>
                        <span>Jurnal KBM: <?= htmlspecialchars($session['journal_topic']) ?></span>
                    </div>
                    <?php if (!empty($session['journal_notes'])): ?>
                        <p class="text-xs text-slate-300 mt-1 pl-6 leading-relaxed"><?= nl2br(htmlspecialchars($session['journal_notes'])) ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <p class="text-slate-400 text-sm mt-3">Arahkan kamera smartphone ke QR Code di bawah untuk absensi</p>
            </div>

            <!-- QR Display Container -->
            <div class="inline-block p-6 rounded-3xl bg-white shadow-2xl shadow-blue-500/10 border-4 border-blue-500/30 relative group">
                <div id="qrcodeCanvas" class="flex justify-center items-center"></div>
                <div class="mt-4 pt-3 border-t border-slate-100 text-slate-800">
                    <div class="text-[11px] text-slate-500 font-bold uppercase tracking-wider">Kode Manual Token</div>
                    <div class="font-mono text-base font-extrabold tracking-widest text-blue-600 mt-0.5 select-all"><?= htmlspecialchars($session['token']) ?></div>
                </div>
            </div>

            <!-- Countdown Timer & Stats -->
            <div class="flex items-center justify-center gap-6">
                <div class="px-6 py-3 rounded-2xl bg-slate-900/90 border border-slate-800 text-center">
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Sisa Waktu</div>
                    <div id="countdownTimer" class="text-2xl font-mono font-black text-amber-400 mt-0.5">--:--</div>
                </div>
                <div class="px-6 py-3 rounded-2xl bg-slate-900/90 border border-slate-800 text-center">
                    <div class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">Total Siswa Scan</div>
                    <div id="scannedCount" class="text-2xl font-mono font-black text-emerald-400 mt-0.5">0</div>
                </div>
            </div>
        </div>

        <!-- Right Column: Real-time Attendee Feed -->
        <div class="lg:col-span-5 bg-slate-900/90 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="user-check" class="w-5 h-5 text-emerald-400"></i>
                    <h3 class="font-bold text-white text-base">Siswa Yang Sudah Scan</h3>
                </div>
                <span class="text-xs text-slate-400 font-medium">Real-Time Feed</span>
            </div>

            <!-- Attendee List Box -->
            <div id="attendeesList" class="space-y-3 max-h-[380px] overflow-y-auto pr-2">
                <div class="text-center py-12 text-slate-500 text-sm flex flex-col items-center gap-2">
                    <i data-lucide="loader-2" class="w-6 h-6 animate-spin text-blue-500"></i>
                    <span>Menunggu scan siswa...</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <div class="text-center text-xs text-slate-500 z-10">
        EduScanQR &bull; Kode Token Sesi: <code class="text-slate-400"><?= htmlspecialchars($session['token']) ?></code>
    </div>

<script>
    lucide.createIcons();

    const sessionId = <?= $session['id'] ?>;
    const token = "<?= htmlspecialchars($session['token']) ?>";
    let remainingSeconds = <?= max(0, strtotime($session['expires_at']) - time()) ?>;

    // Render QR Code
    const qrcodeContainer = document.getElementById("qrcodeCanvas");
    new QRCode(qrcodeContainer, {
        text: token,
        width: 240,
        height: 240,
        colorDark : "#0f172a",
        colorLight : "#ffffff",
        correctLevel : QRCode.CorrectLevel.H
    });

    // Fullscreen Handler
    function toggleFullScreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    // Polling Stats
    async function fetchStats() {
        try {
            const response = await fetch(`api/get_session_stats.php?session_id=${sessionId}`);
            const res = await response.json();

            if (res.success) {
                const data = res.data;

                // Sync remaining seconds from server
                if (typeof data.session.remaining_seconds === 'number') {
                    remainingSeconds = data.session.remaining_seconds;
                }

                // Update Total Count
                document.getElementById('scannedCount').innerText = data.total_hadir;

                // Update Session Status Badge
                const badge = document.getElementById('sessionBadge');
                if (data.session.status === 'expired' || remainingSeconds <= 0) {
                    badge.className = "inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-xs font-bold";
                    badge.innerHTML = `<span class="w-2.5 h-2.5 rounded-full bg-red-500"></span> KEDALUWARSA`;
                }

                // Update Attendees List
                const listContainer = document.getElementById('attendeesList');
                if (data.recent_attendees.length === 0) {
                    listContainer.innerHTML = `
                        <div class="text-center py-12 text-slate-500 text-sm flex flex-col items-center gap-2">
                            <i data-lucide="qr-code" class="w-8 h-8 text-slate-600 animate-pulse"></i>
                            <span>Belum ada siswa yang melakukan scan.</span>
                        </div>
                    `;
                } else {
                    let html = '';
                    data.recent_attendees.forEach(item => {
                        const timeFormatted = new Date(item.scanned_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        html += `
                            <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-800/80 border border-slate-700/60 hover:bg-slate-800 transition transform animate-fade-in">
                                <div class="flex items-center gap-3">
                                    <img src="${item.avatar}" class="w-9 h-9 rounded-full object-cover border border-slate-600">
                                    <div>
                                        <div class="text-xs font-bold text-white">${item.name}</div>
                                        <div class="text-[10px] text-slate-400">NIS: ${item.identity_number}</div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 text-[10px] font-bold border border-emerald-500/30">Hadir</span>
                                    <div class="text-[10px] text-slate-500 mt-0.5">${timeFormatted}</div>
                                </div>
                            </div>
                        `;
                    });
                    listContainer.innerHTML = html;
                }
            }
        } catch (e) {
            console.error(e);
        }
    }

    // Countdown Timer Loop
    function updateCountdown() {
        if (remainingSeconds <= 0) {
            document.getElementById("countdownTimer").innerText = "00:00";
            return;
        }

        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;

        document.getElementById("countdownTimer").innerText = 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + 
            (seconds < 10 ? "0" + seconds : seconds);

        remainingSeconds--;
    }

    // Initial load and interval loops
    updateCountdown();
    fetchStats();
    setInterval(updateCountdown, 1000);
    setInterval(fetchStats, 3000);
</script>
</body>
</html>
