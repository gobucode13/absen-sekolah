<?php
/**
 * Siswa Dashboard & QR Code Scanner
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/includes/auth.php';
requireRole('siswa');

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fetch Personal Attendance History for this student
$stmtHistory = $pdo->prepare("
    SELECT a.*, s.subject, s.class_name, g.name as guru_name
    FROM `attendances` a
    JOIN `sessions` s ON a.session_id = s.id
    JOIN `users` g ON s.guru_id = g.id
    WHERE a.siswa_id = :siswa_id
    ORDER BY a.scanned_at DESC
");
$stmtHistory->execute(['siswa_id' => $currentUser['id']]);
$myAttendances = $stmtHistory->fetchAll();

// Statistics
$totalHadir = count($myAttendances);

$pageTitle = 'Dashboard Siswa - Scan Absensi QR';
include __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- Welcome & Student Card -->
    <div class="glass-card p-6 md:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div class="flex items-center gap-4">
            <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" class="w-16 h-16 rounded-2xl object-cover border-2 border-blue-500 shadow-md">
            <div>
                <span class="inline-block px-3 py-0.5 rounded-full bg-blue-50 text-blue-600 text-xs font-bold mb-1">
                    Siswa Kelas <?= htmlspecialchars($currentUser['class_name']) ?>
                </span>
                <h1 class="text-2xl font-extrabold text-slate-900"><?= htmlspecialchars($currentUser['name']) ?></h1>
                <p class="text-xs text-slate-500 font-medium mt-0.5">NIS: <b><?= htmlspecialchars($currentUser['identity_number']) ?></b> &bull; Email: <?= htmlspecialchars($currentUser['email']) ?></p>
            </div>
        </div>

        <div class="flex items-center gap-4 w-full md:w-auto">
            <div class="flex-1 md:flex-initial px-5 py-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-center">
                <div class="text-xl font-black text-emerald-700"><?= number_format($totalHadir) ?></div>
                <div class="text-[11px] text-emerald-800 font-semibold">Total Presensi Hadir</div>
            </div>
        </div>
    </div>

    <!-- QR Scanner & Manual Token Input Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Camera QR Code Scanner -->
        <div class="lg:col-span-7 space-y-4">
            <div class="glass-card p-6 rounded-3xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="scan-line" class="w-5 h-5 text-blue-600"></i>
                            Pindai QR Code Absensi
                        </h2>
                        <p class="text-xs text-slate-500">Arahkan kamera ke layar proyeksi QR Code yang ditampilkan oleh Guru</p>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <button id="btnToggleCamera" onclick="toggleCamera()" class="py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                            <i data-lucide="camera" class="w-4 h-4"></i> Live Kamera
                        </button>
                        <label class="py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-2 cursor-pointer border border-slate-200">
                            <i data-lucide="upload" class="w-4 h-4 text-slate-600"></i> Ambil Foto / Upload QR
                            <input type="file" id="qrFileInput" accept="image/*" capture="environment" onchange="handleQrFile(this)" class="hidden">
                        </label>
                    </div>
                </div>

                <!-- Camera Container Viewport -->
                <div id="scannerBox" class="relative rounded-2xl overflow-hidden bg-slate-900 min-h-[300px] flex items-center justify-center border-2 border-dashed border-slate-700">
                    <div id="scanPlaceholder" class="text-center p-8 space-y-3 text-slate-400">
                        <div class="w-16 h-16 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-blue-400">
                            <i data-lucide="qr-code" class="w-8 h-8"></i>
                        </div>
                        <p class="text-xs max-w-xs mx-auto">Gunakan <b>"Live Kamera"</b> atau klik <b>"Ambil Foto / Upload QR"</b> untuk memindai presensi.</p>
                    </div>

                    <!-- Scan Laser Animation Overlay (Active when camera is scanning) -->
                    <div id="scanLaser" class="scan-laser hidden"></div>

                    <!-- HTML5 QR Scanner Video Canvas Target -->
                    <div id="reader" class="w-full h-full"></div>
                </div>

                <div class="mt-4 p-3 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-2.5">
                    <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5 text-amber-600"></i>
                    <span><b>Tips:</b> Jika Live Kamera terblokir karena koneksi IP local (HTTP), gunakan tombol <b>"Ambil Foto / Upload QR"</b> atau masukkan <b>Token Manual</b>.</span>
                </div>
            </div>
        </div>

        <!-- Right: Manual Token Fallback -->
        <div class="lg:col-span-5 space-y-4">
            <div class="glass-card p-6 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                        <i data-lucide="keyboard" class="w-5 h-5 text-indigo-600"></i>
                        Input Token Manual
                    </h3>
                    <p class="text-xs text-slate-500">Kamera bermasalah? Ketik kode token unik dari layar guru di bawah ini.</p>
                </div>

                <form id="manualScanForm" onsubmit="handleManualToken(event)" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Kode Token Absensi</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i data-lucide="key" class="w-4 h-4"></i>
                            </div>
                            <input type="text" id="manualTokenInput" required placeholder="Contoh: DEMO-TOKEN-MATEMATIKA-10IPA1" 
                                class="w-full pl-10 pr-4 py-3 rounded-2xl bg-slate-50 border border-slate-200 text-sm font-mono focus:outline-none focus:border-blue-500 uppercase tracking-wider">
                        </div>
                    </div>

                    <button type="submit" id="btnSubmitManual" class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white font-bold rounded-2xl text-sm shadow-md shadow-indigo-500/20 transition flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Kirim Absensi
                    </button>
                </form>
            </div>
        </div>

    </div>

    <!-- Personal Attendance Log Table -->
    <div class="glass-card rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <div class="p-6 border-b border-slate-200/80">
            <h3 class="text-lg font-bold text-slate-900">Riwayat Kehadiran Anda</h3>
            <p class="text-xs text-slate-500">Catatan lengkap absensi yang telah berhasil Anda lakukan</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-100/70 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Mata Pelajaran</th>
                        <th class="py-3.5 px-6">Guru Pengajar</th>
                        <th class="py-3.5 px-6">Waktu Presensi</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 text-sm">
                    <?php if (empty($myAttendances)): ?>
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 text-sm">Belum ada riwayat presensi. Silakan lakukan scan QR Code di atas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myAttendances as $att): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-4 px-6 font-semibold text-slate-900"><?= htmlspecialchars($att['subject']) ?></td>
                                <td class="py-4 px-6 text-slate-600"><?= htmlspecialchars($att['guru_name']) ?></td>
                                <td class="py-4 px-6 text-slate-500 text-xs"><?= date('d M Y - H:i:s', strtotime($att['scanned_at'])) ?></td>
                                <td class="py-4 px-6 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full badge-hadir text-xs font-bold">
                                        <?= ucfirst($att['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Scan Result Feedback -->
<div id="resultModal" class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl relative animate-scale-up">
        
        <div id="modalIconContainer" class="w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4">
            <!-- Icon dynamically inserted -->
        </div>

        <h3 id="modalTitle" class="text-xl font-bold text-slate-900"></h3>
        <p id="modalMessage" class="text-xs text-slate-500 mt-1 mb-6"></p>

        <div id="modalDetails" class="hidden p-4 rounded-2xl bg-slate-50 border border-slate-200 text-left text-xs space-y-2 mb-6">
            <div class="flex justify-between"><span class="text-slate-400">Siswa:</span> <span id="resStudent" class="font-bold text-slate-800"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Mata Pelajaran:</span> <span id="resSubject" class="font-bold text-blue-600"></span></div>
            <div class="flex justify-between"><span class="text-slate-400">Waktu Scan:</span> <span id="resTime" class="font-bold text-slate-700"></span></div>
        </div>

        <button onclick="closeResultModal()" class="w-full py-3 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-2xl text-sm transition">
            Tutup
        </button>
    </div>
</div>

<script>
    let html5QrCode = null;
    let isCameraRunning = false;

    function toggleCamera() {
        if (isCameraRunning) {
            stopCamera();
        } else {
            startCamera();
        }
    }

    function startCamera() {
        document.getElementById('scanPlaceholder').classList.add('hidden');
        document.getElementById('scanLaser').classList.remove('hidden');

        html5QrCode = new Html5Qrcode("reader");
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };

        html5QrCode.start({ facingMode: "environment" }, config, onScanSuccess)
        .then(() => {
            isCameraRunning = true;
            document.getElementById('btnToggleCamera').innerHTML = `<i data-lucide="camera-off" class="w-4 h-4"></i> Matikan Kamera`;
            document.getElementById('btnToggleCamera').className = "py-2.5 px-4 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md transition flex items-center gap-2";
            lucide.createIcons();
        })
        .catch(err => {
            alert('Gagal mengaktifkan kamera. Pastikan memberikan izin kamera atau gunakan fitur Token Manual.');
            stopCamera();
        });
    }

    function stopCamera() {
        if (html5QrCode && isCameraRunning) {
            html5QrCode.stop().then(() => {
                html5QrCode.clear();
                isCameraRunning = false;
                document.getElementById('scanPlaceholder').classList.remove('hidden');
                document.getElementById('scanLaser').classList.add('hidden');
                document.getElementById('btnToggleCamera').innerHTML = `<i data-lucide="camera" class="w-4 h-4"></i> Mulai Kamera`;
                document.getElementById('btnToggleCamera').className = "py-2.5 px-4 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition flex items-center gap-2";
                lucide.createIcons();
            });
        }
    }

    function handleQrFile(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];
        
        if (isCameraRunning) {
            stopCamera();
        }

        const html5QrCodeFile = new Html5Qrcode("reader");
        html5QrCodeFile.scanFile(file, true)
        .then(decodedText => {
            submitToken(decodedText);
            input.value = '';
        })
        .catch(err => {
            alert('Gagal membaca QR Code dari foto/gambar. Pastikan foto QR Code terlihat jelas dan terang.');
            input.value = '';
        });
    }

    function onScanSuccess(decodedText) {
        // Pause scanner briefly to avoid double trigger
        if (html5QrCode) {
            html5QrCode.pause(true);
        }
        submitToken(decodedText);
    }

    async function handleManualToken(e) {
        e.preventDefault();
        const tokenInput = document.getElementById('manualTokenInput').value.trim();
        if (tokenInput) {
            submitToken(tokenInput);
        }
    }

    async function submitToken(token) {
        const formData = new FormData();
        formData.append('token', token);

        try {
            const response = await fetch('api/scan.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            showResultModal(result);

        } catch (error) {
            showResultModal({
                success: false,
                message: 'Terjadi gangguan koneksi jaringan.'
            });
        } finally {
            if (html5QrCode && isCameraRunning) {
                setTimeout(() => {
                    try { html5QrCode.resume(); } catch(e){}
                }, 2000);
            }
        }
    }

    function showResultModal(result) {
        const modal = document.getElementById('resultModal');
        const iconBox = document.getElementById('modalIconContainer');
        const title = document.getElementById('modalTitle');
        const msg = document.getElementById('modalMessage');
        const details = document.getElementById('modalDetails');

        if (result.success) {
            iconBox.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-emerald-100 text-emerald-600";
            iconBox.innerHTML = `<i data-lucide="check-circle-2" class="w-10 h-10"></i>`;
            title.innerText = "Absensi Berhasil!";
            msg.innerText = result.message;

            document.getElementById('resStudent').innerText = result.data.student_name;
            document.getElementById('resSubject').innerText = result.data.subject;
            document.getElementById('resTime').innerText = result.data.time;
            details.classList.remove('hidden');

        } else {
            iconBox.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center mb-4 bg-red-100 text-red-600";
            iconBox.innerHTML = `<i data-lucide="alert-triangle" class="w-10 h-10"></i>`;
            title.innerText = "Absensi Gagal";
            msg.innerText = result.message;
            details.classList.add('hidden');
        }

        lucide.createIcons();
        modal.classList.remove('hidden');
    }

    function closeResultModal() {
        document.getElementById('resultModal').classList.add('hidden');
        window.location.reload(); // Refresh to update attendance history table
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
