<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Clear all output buffers and hide PHP errors for a better Excel export
while (ob_get_level()) ob_end_clean();
error_reporting(0);
ini_set('display_errors', 0);

// FILTERS
$filter_month = isset($_GET['month']) ? trim($_GET['month']) : '';
$filter_year = isset($_GET['year']) ? trim($_GET['year']) : '';
$filter_jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query and params according to mitratel_monitoring.sql entities
$where_conditions = [];
$params = [];
if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "MONTH(l.selesai_pada) = ? AND YEAR(l.selesai_pada) = ?";
    $params[] = (int)$filter_month;
    $params[] = (int)$filter_year;
} elseif (!empty($filter_year)) {
    $where_conditions[] = "YEAR(l.selesai_pada) = ?";
    $params[] = (int)$filter_year;
}
if (!empty($filter_jenis)) {
    $where_conditions[] = "l.jenis_perbaikan = ?";
    $params[] = $filter_jenis;
}
if (!empty($search)) {
    $where_conditions[] = "(t.jenis_gangguan LIKE ? OR lok.alamat LIKE ? OR a.nama_lengkap LIKE ? OR tek.nama_lengkap LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}
$where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Query data laporan, sesuai tabel asli
$reports_query = $pdo->prepare("
    SELECT 
        l.id_laporan,
        l.id_tiket,
        l.jenis_perbaikan,
        l.catatan,
        l.selesai_pada,
        t.jenis_gangguan,
        t.deskripsi as tiket_deskripsi,
        t.created_at as tiket_created,
        lok.alamat,
        a.nama_lengkap as admin_nama,
        tek.nama_lengkap as teknisi_nama,
        p.created_at as assigned_date,
        DATEDIFF(l.selesai_pada, t.created_at) as completion_days
    FROM laporan l
    JOIN tiket t ON l.id_tiket = t.id_tiket
    JOIN lokasi lok ON t.id_lokasi = lok.id_lokasi
    JOIN admin a ON t.id_admin = a.id_admin
    LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
    $where_clause
    ORDER BY l.selesai_pada DESC
");
$reports_query->execute(array_values($params));
$reports_list = $reports_query->fetchAll();

// Periode string untuk nama file
if (!empty($filter_month) && !empty($filter_year)) {
    $period = date('F_Y', mktime(0, 0, 0, $filter_month, 1, $filter_year));
} elseif (!empty($filter_year)) {
    $period = 'Tahun_' . $filter_year;
} else {
    $period = 'Semua_Periode';
}

// Filename & Excel headers
$filename = 'Laporan_Perbaikan_' . $period . '_' . date('YmdHis') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Perbaikan</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style>
        table { border-collapse:collapse; width:100%; font-family:Arial,sans-serif;}
        .header-title {background:#E31E24; color:white; font-weight:bold; text-align:center; font-size:16px; padding:10px; border:1px solid #000;}
        .sub-header {background:#FFEBEE; color:#E31E24; font-weight:bold; text-align:center; font-size:12px; padding:8px; border:1px solid #000;}
        .column-header {background:#E31E24; color:white; font-weight:bold; text-align:center; font-size:11px; padding:8px; border:1px solid #000; white-space:nowrap;}
        .data-cell {border:1px solid #000;padding:5px;font-size:10px;vertical-align:top;}
        .center {text-align:center;}
        .summary-header {background:#E31E24; color:white; font-weight:bold; text-align:left; padding:5px; border:1px solid #000;}
        .summary-value {background:#E31E24; color:white; font-weight:bold; text-align:center; padding:5px; border:1px solid #000;}
        .footer-cell {text-align:center; font-size:10px; color:#666; padding:10px; border:1px solid #ccc; font-style:italic;}
    </style>
</head>
<body>
    <table>
        <!-- Title Row -->
        <tr>
            <td colspan="12" class="header-title">
                LAPORAN PERBAIKAN TIKET GANGGUAN<br/>
                PT TELKOM AKSES
            </td>
        </tr>
        <!-- Sub Header -->
        <tr>
            <td colspan="12" class="sub-header">
                Periode: <?= htmlspecialchars($period) ?><br/>
                Dicetak pada: <?= date('d/m/Y H:i:s') ?> WIB
            </td>
        </tr>
        <tr><td colspan="12" style="height: 15px; border: none;"></td></tr>
        <!-- Column Headers -->
        <tr>
            <td class="column-header">NO</td>
            <td class="column-header">ID LAPORAN</td>
            <td class="column-header">ID TIKET</td>
            <td class="column-header">JENIS GANGGUAN</td>
            <td class="column-header">JENIS PERBAIKAN</td>
            <td class="column-header">TEKNISI</td>
            <td class="column-header">LOKASI</td>
            <td class="column-header">TANGGAL SELESAI</td>
            <td class="column-header">DURASI (HARI)</td>
            <td class="column-header">ADMIN PEMBUAT</td>
            <td class="column-header">DESKRIPSI TIKET</td>
            <td class="column-header">CATATAN PERBAIKAN</td>
        </tr>
        <?php if (count($reports_list) > 0): ?>
            <?php foreach ($reports_list as $idx => $report): ?>
                <tr>
                    <td class="data-cell center"><?= $idx + 1 ?></td>
                    <td class="data-cell center"><?= htmlspecialchars($report['id_laporan']) ?></td>
                    <td class="data-cell center"><?= htmlspecialchars($report['id_tiket']) ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['jenis_gangguan']) ?></td>
                    <td class="data-cell center"><?= $report['jenis_perbaikan'] == 'temporary' ? 'Sementara' : 'Permanen' ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['teknisi_nama'] ?: 'Tidak ada') ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['alamat']) ?></td>
                    <td class="data-cell center"><?= date('d/m/Y H:i', strtotime($report['selesai_pada'])) ?></td>
                    <td class="data-cell center"><?= $report['completion_days'] ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['admin_nama']) ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['tiket_deskripsi']) ?></td>
                    <td class="data-cell"><?= htmlspecialchars($report['catatan'] ?: 'Tidak ada catatan') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="12" class="data-cell center" style="padding: 20px; font-style: italic;">
                    Tidak ada data laporan yang sesuai dengan filter
                </td>
            </tr>
        <?php endif; ?>
        <tr><td colspan="12" style="height: 15px; border: none;"></td></tr>
        <!-- Summary Rows -->
        <tr>
            <td colspan="8" class="summary-header">TOTAL LAPORAN:</td>
            <td class="summary-value"><?= count($reports_list) ?></td>
            <td colspan="3" class="summary-header"></td>
        </tr>
        <tr>
            <td colspan="8" class="summary-header">PERBAIKAN SEMENTARA:</td>
            <td class="summary-value"><?= count(array_filter($reports_list, function($r) { return $r['jenis_perbaikan'] == 'temporary'; })) ?></td>
            <td colspan="3" class="summary-header"></td>
        </tr>
        <tr>
            <td colspan="8" class="summary-header">PERBAIKAN PERMANEN:</td>
            <td class="summary-value"><?= count(array_filter($reports_list, function($r) { return $r['jenis_perbaikan'] == 'permanent'; })) ?></td>
            <td colspan="3" class="summary-header"></td>
        </tr>
        <tr>
            <td colspan="8" class="summary-header">RATA-RATA HARI PENYELESAIAN:</td>
            <td class="summary-value"><?= count($reports_list) > 0 ? number_format(array_sum(array_column($reports_list, 'completion_days')) / count($reports_list), 1) : 0 ?></td>
            <td colspan="3" class="summary-header"></td>
        </tr>
        <tr><td colspan="12" style="height: 20px; border: none;"></td></tr>
        <!-- Footer -->
        <tr>
            <td colspan="12" class="footer-cell">
                Laporan ini dibuat secara otomatis oleh Sistem Manajemen Tiket PT Telkom Akses<br/>
                Dicetak oleh: Supervisor | Tanggal: <?= date('d F Y, H:i:s') ?> WIB
            </td>
        </tr>
    </table>
</body>
</html>
<?php exit(); ?>
