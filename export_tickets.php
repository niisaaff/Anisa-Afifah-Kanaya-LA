<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Get filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : '';
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_conditions = ["t.status = 'open'"];
$params = [];

if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?";
    $params[] = $filter_month;
    $params[] = $filter_year;
}

if (!empty($search)) {
    $where_conditions[] = "(t.jenis_gangguan LIKE ? OR l.alamat LIKE ? OR a.username LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get tickets data (disesuaikan ke mitratel_monitoring.sql)
$tiket_query = $pdo->prepare("
    SELECT t.id_tiket, t.jenis_gangguan, t.deskripsi, t.created_at, t.status,
           l.alamat, a.username as created_by
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN admin a ON t.id_admin = a.id_admin
    WHERE $where_clause
    ORDER BY t.created_at DESC
");
$tiket_query->execute($params);
$tiket_list = $tiket_query->fetchAll();

// Set filename
$month_name = !empty($filter_month) ? date('F', mktime(0, 0, 0, $filter_month, 1)) : 'Semua';
$year_name = !empty($filter_year) ? $filter_year : 'Tahun';
$filename = 'Tiket_Belum_Ditugaskan_' . $month_name . '_' . $year_name . '_' . date('YmdHis') . '.xls';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Start output
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="ProgId" content="Excel.Sheet">
    <meta name="Generator" content="Microsoft Excel 15">
    <style>
        .header {
            background-color: #E31E24;
            color: white;
            font-weight: bold;
            text-align: center;
            border: 1px solid #000;
        }
        .data {
            border: 1px solid #000;
            text-align: left;
            vertical-align: top;
        }
        .center {
            text-align: center;
        }
        .number {
            text-align: right;
        }
    </style>
</head>
<body>
    <table border="1" cellpadding="5" cellspacing="0">
        <!-- Title Row -->
        <tr>
            <td colspan="7" class="header" style="font-size: 16px; padding: 10px;">
                <strong>LAPORAN TIKET BELUM DITUGASKAN - PT TELKOM AKSES</strong>
            </td>
        </tr>
        <tr>
            <td colspan="7" class="center" style="padding: 5px;">
                <strong>Periode: <?= $month_name ?> <?= $year_name ?></strong><br>
                <small>Dicetak pada: <?= date('d/m/Y H:i:s') ?></small>
            </td>
        </tr>
        <tr><td colspan="7"></td></tr>
        
        <!-- Header Row -->
        <tr>
            <td class="header">ID TIKET</td>
            <td class="header">JENIS GANGGUAN</td>
            <td class="header">LOKASI</td>
            <td class="header">DESKRIPSI</td>
            <td class="header">DIBUAT OLEH</td>
            <td class="header">TANGGAL DIBUAT</td>
            <td class="header">STATUS</td>
        </tr>
        
        <!-- Data Rows -->
        <?php if (count($tiket_list) > 0): ?>
            <?php foreach ($tiket_list as $index => $tiket): ?>
                <tr>
                    <td class="data center"><?= htmlspecialchars($tiket['id_tiket']) ?></td>
                    <td class="data"><?= htmlspecialchars($tiket['jenis_gangguan']) ?></td>
                    <td class="data"><?= htmlspecialchars($tiket['alamat']) ?></td>
                    <td class="data"><?= htmlspecialchars($tiket['deskripsi']) ?></td>
                    <td class="data"><?= htmlspecialchars($tiket['created_by']) ?></td>
                    <td class="data center"><?= date('d/m/Y H:i', strtotime($tiket['created_at'])) ?></td>
                    <td class="data center">Belum Ditugaskan</td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="data center" style="padding: 20px; font-style: italic;">
                    Tidak ada data tiket yang sesuai dengan filter
                </td>
            </tr>
        <?php endif; ?>
        
        <!-- Summary Row -->
        <tr><td colspan="7"></td></tr>
        <tr>
            <td colspan="6" class="header">TOTAL TIKET BELUM DITUGASKAN:</td>
            <td class="header center"><?= count($tiket_list) ?></td>
        </tr>
        
        <!-- Footer -->
        <tr><td colspan="7"></td></tr>
        <tr>
            <td colspan="7" class="center" style="font-size: 10px; color: #666;">
                <em>Laporan ini dibuat secara otomatis oleh Sistem Manajemen Tiket PT Telkom Akses</em>
            </td>
        </tr>
    </table>
</body>
</html>
