<?php
/**
 * API Endpoint: Export Attendance Recap to Microsoft Excel (.xls)
 * Website Absensi Sekolah (QR Code)
 */
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'guru') {
    die('Akses ditolak. Silakan login sebagai Guru.');
}

$currentUser = getCurrentUser();
$className = trim($_GET['class_name'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

try {
    $pdo = getDBConnection();
    autoInitializeDatabase($pdo);

    if (empty($startDate)) {
        $startDate = date('Y-m-01');
    }
    if (empty($endDate)) {
        $endDate = date('Y-m-t');
    }

    // Build Session Filter Query
    $sessionParams = ['guru_id' => $currentUser['id']];
    $sessionWhere = "WHERE `guru_id` = :guru_id AND DATE(`created_at`) BETWEEN :start_date AND :end_date";
    $sessionParams['start_date'] = $startDate;
    $sessionParams['end_date'] = $endDate;

    if (!empty($className) && strtolower($className) !== 'all') {
        $sessionWhere .= " AND LOWER(TRIM(`class_name`)) = LOWER(TRIM(:class_name))";
        $sessionParams['class_name'] = $className;
    }

    $stmtTotalSessions = $pdo->prepare("SELECT COUNT(*) FROM `sessions` $sessionWhere");
    $stmtTotalSessions->execute($sessionParams);
    $totalSessions = intval($stmtTotalSessions->fetchColumn());

    // Build Students Query
    $studentWhere = "WHERE u.role = 'siswa'";
    $studentParams = [
        'guru_id' => $currentUser['id'],
        'start_date' => $startDate,
        'end_date' => $endDate
    ];

    if (!empty($className) && strtolower($className) !== 'all') {
        $studentWhere .= " AND LOWER(TRIM(u.class_name)) = LOWER(TRIM(:class_name))";
        $studentParams['class_name'] = $className;
    }

    $query = "
        SELECT 
            u.id as siswa_id,
            u.identity_number as siswa_nis,
            u.name as siswa_name,
            u.class_name,
            COUNT(CASE WHEN a.status = 'hadir' THEN 1 END) as total_hadir,
            COUNT(CASE WHEN a.status = 'izin' THEN 1 END) as total_izin,
            COUNT(CASE WHEN a.status = 'sakit' THEN 1 END) as total_sakit,
            COUNT(CASE WHEN a.status = 'alpa' THEN 1 END) as total_alpa
        FROM `users` u
        LEFT JOIN `attendances` a ON a.siswa_id = u.id 
            AND a.session_id IN (
                SELECT s.id FROM `sessions` s 
                WHERE s.guru_id = :guru_id AND DATE(s.created_at) BETWEEN :start_date AND :end_date
            )
        $studentWhere
        GROUP BY u.id
        ORDER BY u.class_name ASC, u.name ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($studentParams);
    $students = $stmt->fetchAll();

    // Prepare filename
    $cleanClassStr = (!empty($className) && strtolower($className) !== 'all') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $className) : 'Semua_Kelas';
    $filename = "Rekap_Absensi_Sekolah_" . $cleanClassStr . "_" . date('Y-m-d') . ".xls";

    // Set Download Headers for Microsoft Excel
    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Output Styled HTML Spreadsheet Table compatible with Excel
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <!--[if gte mso 9]>
        <xml>
            <x:ExcelWorkbook>
                <x:ExcelWorksheets>
                    <x:ExcelWorksheet>
                        <x:Name>Rekap Absensi</x:Name>
                        <x:WorksheetOptions>
                            <x:DisplayGridlines/>
                        </x:WorksheetOptions>
                    </x:ExcelWorksheet>
                </x:ExcelWorksheets>
            </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            body { font-family: Calibri, Arial, sans-serif; font-size: 11pt; }
            .title { font-size: 16pt; font-weight: bold; text-align: center; color: #1e3a8a; }
            .subtitle { font-size: 11pt; text-align: center; color: #475569; margin-bottom: 15px; }
            .meta-table { margin-bottom: 20px; font-size: 10pt; }
            .meta-table td { padding: 4px 8px; }
            .header-cell { background-color: #1e40af; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #0f172a; padding: 8px; }
            .data-cell { border: 1px solid #cbd5e1; padding: 6px 8px; vertical-align: middle; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
            .badge-hadir { background-color: #dcfce7; color: #166534; font-weight: bold; }
            .badge-izin { background-color: #fef3c7; color: #92400e; font-weight: bold; }
            .badge-sakit { background-color: #f3e8ff; color: #6b21a8; font-weight: bold; }
            .badge-alpa { background-color: #ffe4e6; color: #9f1239; font-weight: bold; }
        </style>
    </head>
    <body>
        
        <div class="title">REKAPITULASI PRESENSI SISWA - EDUSCANQR</div>
        <div class="subtitle">Sistem Absensi Digital Berbasis QR Code</div>

        <table class="meta-table">
            <tr>
                <td><b>Guru Pengajar:</b></td>
                <td><?= htmlspecialchars($currentUser['name']) ?> (<?= htmlspecialchars($currentUser['identity_number']) ?>)</td>
                <td><b>Periode Absensi:</b></td>
                <td><?= date('d M Y', strtotime($startDate)) ?> s/d <?= date('d M Y', strtotime($endDate)) ?></td>
            </tr>
            <tr>
                <td><b>Target Kelas:</b></td>
                <td><?= (!empty($className) && strtolower($className) !== 'all') ? htmlspecialchars($className) : 'Semua Kelas' ?></td>
                <td><b>Total Sesi Dilaksanakan:</b></td>
                <td><?= $totalSessions ?> Sesi</td>
            </tr>
            <tr>
                <td><b>Tanggal Diekspor:</b></td>
                <td><?= date('d F Y, H:i:s') ?> WIB</td>
                <td><b>Total Siswa Terdaftar:</b></td>
                <td><?= count($students) ?> Siswa</td>
            </tr>
        </table>

        <table border="1">
            <thead>
                <tr>
                    <th class="header-cell">No</th>
                    <th class="header-cell">NIS / NIP</th>
                    <th class="header-cell">Nama Lengkap Siswa</th>
                    <th class="header-cell">Kelas</th>
                    <th class="header-cell">Hadir</th>
                    <th class="header-cell">Izin</th>
                    <th class="header-cell">Sakit</th>
                    <th class="header-cell">Alpa</th>
                    <th class="header-cell">% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="9" class="data-cell text-center">Tidak ada data siswa yang ditemukan untuk kriteria filter ini.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($students as $st): 
                        $hadir = intval($st['total_hadir']);
                        $izin = intval($st['total_izin']);
                        $sakit = intval($st['total_sakit']);
                        $alpa = intval($st['total_alpa']);
                        $pct = $totalSessions > 0 ? round(($hadir / $totalSessions) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td class="data-cell text-center"><?= $no++ ?></td>
                            <td class="data-cell text-center" style="mso-number-format:'\@';"><?= htmlspecialchars($st['siswa_nis']) ?></td>
                            <td class="data-cell font-bold"><?= htmlspecialchars($st['siswa_name']) ?></td>
                            <td class="data-cell text-center"><?= htmlspecialchars($st['class_name']) ?></td>
                            <td class="data-cell text-center badge-hadir"><?= $hadir ?></td>
                            <td class="data-cell text-center badge-izin"><?= $izin ?></td>
                            <td class="data-cell text-center badge-sakit"><?= $sakit ?></td>
                            <td class="data-cell text-center badge-alpa"><?= $alpa ?></td>
                            <td class="data-cell text-center font-bold"><?= $pct ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </body>
    </html>
    <?php
} catch (Exception $e) {
    die('Gagal mengekspor file Excel: ' . $e->getMessage());
}
