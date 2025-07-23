<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor User';
$userRole = 'supervisor';

// Filter
$filter_month = isset($_GET['month']) ? trim($_GET['month']) : '';
$filter_year = isset($_GET['year']) ? trim($_GET['year']) : '';
$filter_jenis = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query conditions
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

$where_clause = '';
if (count($where_conditions) > 0) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}
$params = array_values($params);

// Pull full laporan data, join ke admin/teknisi sesuai struktur mitratel_monitoring.sql
$reports_query = $pdo->prepare("
    SELECT 
        l.id_laporan,
        l.id_tiket,
        l.jenis_perbaikan,
        l.dokumentasi,
        l.catatan,
        l.selesai_pada,
        t.jenis_gangguan,
        t.deskripsi as tiket_deskripsi,
        t.created_at as tiket_created,
        t.status,
        lok.alamat,
        lok.latitude,
        lok.longitude,
        a.nama_lengkap as admin_nama,
        a.username as admin_username,
        tek.nama_lengkap as teknisi_nama,
        tek.username as teknisi_username,
        tek.foto as teknisi_foto,
        tek.id_teknisi,
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

$reports_query->execute($params);
$reports_list = $reports_query->fetchAll();

// Statistik
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total_reports,
        COUNT(CASE WHEN l.jenis_perbaikan = 'temporary' THEN 1 END) as temporary_repairs,
        COUNT(CASE WHEN l.jenis_perbaikan = 'permanent' THEN 1 END) as permanent_repairs,
        AVG(DATEDIFF(l.selesai_pada, t.created_at)) as avg_completion_days
    FROM laporan l
    JOIN tiket t ON l.id_tiket = t.id_tiket
    JOIN lokasi lok ON t.id_lokasi = lok.id_lokasi
    JOIN admin a ON t.id_admin = a.id_admin
    LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
    $where_clause
");
$stats_query->execute($params);
$stats = $stats_query->fetch();

// Handle penugasan kembali untuk tiket temporary
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reassign') {
    $id_tiket = (int)$_POST['id_tiket'];
    $id_teknisi = (int)$_POST['id_teknisi'];
    
    try {
        $pdo->beginTransaction();
        
        // Pastikan tiket ada dan memiliki laporan temporary
        $check_query = $pdo->prepare("
            SELECT l.*, t.jenis_gangguan, t.deskripsi, lok.alamat, tek.nama_lengkap as current_teknisi
            FROM laporan l
            JOIN tiket t ON l.id_tiket = t.id_tiket
            JOIN lokasi lok ON t.id_lokasi = lok.id_lokasi
            LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
            LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
            WHERE l.id_tiket = ? AND l.jenis_perbaikan = 'temporary'
        ");
        $check_query->execute([$id_tiket]);
        $tiket_info = $check_query->fetch();
        
        if (!$tiket_info) {
            throw new Exception('Tiket tidak ditemukan atau bukan perbaikan temporary');
        }
        
        // Update status tiket kembali ke 'on progress'
        $update_tiket = $pdo->prepare("UPDATE tiket SET status = 'on progress' WHERE id_tiket = ?");
        $update_tiket->execute([$id_tiket]);
        
        // Hapus laporan lama
        $delete_laporan = $pdo->prepare("DELETE FROM laporan WHERE id_tiket = ?");
        $delete_laporan->execute([$id_tiket]);
        
        // Update penugasan dengan teknisi baru
        $update_penugasan = $pdo->prepare("
            UPDATE penugasan 
            SET id_teknisi = ?, created_at = CURRENT_TIMESTAMP 
            WHERE id_tiket = ?
        ");
        $update_penugasan->execute([$id_teknisi, $id_tiket]);
        
        // Ambil informasi teknisi baru
        $teknisi_query = $pdo->prepare("SELECT nama_lengkap FROM teknisi WHERE id_teknisi = ?");
        $teknisi_query->execute([$id_teknisi]);
        $teknisi_info = $teknisi_query->fetch();
        
        // Buat notifikasi untuk teknisi baru
        $prioritas = ($tiket_info['jenis_gangguan'] === 'Critical') ? 'TINGGI' : 'NORMAL';
        $status_prioritas = ($tiket_info['jenis_gangguan'] === 'Critical') ? 'SEGERA DITANGANI' : 'PERLU DITANGANI';
        
        $notifikasi_message = "Penugasan Ulang Tiket Temporary\n" .
                             "Tiket #{$id_tiket}\n" .
                             "Jenis Gangguan: {$tiket_info['jenis_gangguan']}\n" .
                             "Lokasi: " . substr($tiket_info['alamat'], 0, 50) . "\n" .
                             "Deskripsi: " . substr($tiket_info['deskripsi'], 0, 100) . "\n" .
                             "Tanggal: " . date('d M Y H:i') . "\n" .
                             "Status: {$status_prioritas}\n" .
                             "Prioritas: {$prioritas}\n" .
                             "Catatan: Perbaikan sebelumnya bersifat temporary, diperlukan perbaikan lanjutan";
        
        $insert_notifikasi = $pdo->prepare("
            INSERT INTO notifikasi (id_teknisi, judul, pesan, status_baca, created_at) 
            VALUES (?, 'Penugasan Ulang Tiket Temporary', ?, 'unread', CURRENT_TIMESTAMP)
        ");
        $insert_notifikasi->execute([$id_teknisi, $notifikasi_message]);
        
        $pdo->commit();
        
        // Set success message
        $_SESSION['success_message'] = "Tiket #{$id_tiket} berhasil ditugaskan ulang kepada " . $teknisi_info['nama_lengkap'] . ". Notifikasi telah dikirim.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error_message'] = "Gagal melakukan penugasan ulang: " . $e->getMessage();
    }
    
    // Redirect untuk menghindari resubmission
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// Ambil daftar teknisi untuk dropdown penugasan
$teknisi_query = $pdo->prepare("SELECT id_teknisi, nama_lengkap, username FROM teknisi ORDER BY nama_lengkap");
$teknisi_query->execute();
$teknisi_list = $teknisi_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Perbaikan - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css">
    <style>
        :root {
            --report-telkom-red: #E31E24;
            --report-telkom-dark-red: #B71C1C;
            --report-telkom-light-red: #FFEBEE;
            --report-telkom-gray: #F5F5F5;
            --report-telkom-dark-gray: #424242;
            --report-telkom-white: #FFFFFF;
            --report-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --report-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --report-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --report-border-radius: 12px;
            --report-border-radius-small: 8px;
            --report-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: #212529;
            line-height: 1.6;
            overflow-x: hidden;
        }

        .report-main-content {
            padding: 110px 25px 25px;
            transition: var(--report-transition);
            min-height: calc(100vh - 45px);
        }

        .report-header-section {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--report-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--report-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .report-header-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }

        .report-header-content {
            position: relative;
            z-index: 2;
        }

        .report-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .report-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .report-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        .report-stats-container {
            margin-bottom: 2rem;
        }

        .report-stat-card {
            background: var(--report-telkom-white);
            border-radius: var(--report-border-radius);
            padding: 1.5rem;
            box-shadow: var(--report-shadow-light);
            transition: var(--report-transition);
            border-left: 4px solid var(--report-telkom-red);
            text-align: center;
            height: 100%;
        }

        .report-stat-card:hover {
            box-shadow: var(--report-shadow-medium);
            transform: translateY(-2px);
        }

        .report-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--report-telkom-red);
            margin-bottom: 0.5rem;
        }

        .report-stat-label {
            color: var(--report-telkom-dark-gray);
            font-weight: 500;
            font-size: 0.9rem;
        }

        .report-card {
            background: var(--report-telkom-white);
            border: none;
            border-radius: var(--report-border-radius);
            box-shadow: var(--report-shadow-light);
            transition: var(--report-transition);
            overflow: hidden;
            border-left: 4px solid var(--report-telkom-red);
            margin-bottom: 2rem;
        }

        .report-card:hover {
            box-shadow: var(--report-shadow-medium);
            transform: translateY(-2px);
        }

        .report-card-header {
            background: linear-gradient(135deg, var(--report-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--report-telkom-red);
            padding: 1.5rem;
        }

        .report-card-title {
            color: var(--report-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-card-body {
            padding: 2rem;
        }

        .report-filter-section {
            background: var(--report-telkom-white);
            border-radius: var(--report-border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--report-shadow-light);
            border-left: 4px solid var(--report-telkom-red);
        }

        .report-filter-title {
            color: var(--report-telkom-red);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--report-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--report-transition);
            background: #fafafa;
            width: 100%;
        }

        .report-form-control:focus {
            border-color: var(--report-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .report-btn {
            border-radius: var(--report-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--report-transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            min-width: 120px;
            justify-content: center;
        }

        .report-btn-primary {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.3);
        }

        .report-btn-primary:hover {
            background: linear-gradient(135deg, var(--report-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .report-btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .report-btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .report-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .report-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .report-btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
        }

        .report-btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
            color: #212529;
        }

        .report-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-width: 100px;
            font-weight: 600;
        }

        .report-table {
            margin-bottom: 0;
            background: white;
            border-radius: var(--report-border-radius-small);
            overflow: hidden;
            box-shadow: var(--report-shadow-light);
        }

        .report-table thead th {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            font-size: 0.9rem;
            text-align: center;
            vertical-align: middle;
        }

        .report-table tbody tr {
            transition: var(--report-transition);
        }

        .report-table tbody tr:hover {
            background: var(--report-telkom-light-red);
        }

        .report-table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.9rem;
        }

        .report-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-temporary {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .badge-permanent {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }

        .report-btn-action {
            padding: 0.5rem 1rem;
            border-radius: var(--report-border-radius-small);
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            transition: var(--report-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin: 0.25rem;
            border: none;
        }

        .report-btn-detail {
            background: #17a2b8;
            color: white;
        }

        .report-btn-detail:hover {
            background: #138496;
            color: white;
            transform: translateY(-1px);
        }

        .report-btn-reassign {
            background: #ffc107;
            color: #212529;
        }

        .report-btn-reassign:hover {
            background: #e0a800;
            color: #212529;
            transform: translateY(-1px);
        }

        .report-doc-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--report-border-radius-small);
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: var(--report-transition);
        }

        .report-doc-img:hover {
            transform: scale(1.1);
            border-color: var(--report-telkom-red);
        }

        .modal-content {
            border-radius: var(--report-border-radius);
            border: none;
            box-shadow: var(--report-shadow-heavy);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            border-radius: var(--report-border-radius) var(--report-border-radius) 0 0;
        }

        .report-no-data {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .report-no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #d1d1d1;
        }

        .alert-modern {
            border-radius: var(--report-border-radius);
            border-left-width: 4px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        @media (max-width: 768px) {
            .report-main-content {
                padding: 110px 10px 25px;
            }
            
            .report-header-title {
                font-size: 1.5rem;
            }
            
            .report-table {
                font-size: 0.8rem;
            }
            
            .report-table td, .report-table th {
                padding: 0.5rem 0.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include('../../includes/sidebar.php'); ?> 
    <?php showSidebar($userRole); ?>

    <div class="content-wrapper">
        <?php include('../../includes/topbar.php'); ?>
        <?php showTopbar($userRole, $username); ?>
        
        <div class="report-main-content">
            <!-- Alert Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-modern">
                    <i class="fas fa-check-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['success_message']) ?></span>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-modern">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?= htmlspecialchars($_SESSION['error_message']) ?></span>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Header Section -->
            <div class="report-header-section">
                <div class="report-telkom-logo">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="report-header-content">
                    <h1 class="report-header-title">
                        <i class="fas fa-clipboard-check me-3"></i>
                        Laporan Perbaikan
                    </h1>
                    <p class="report-header-subtitle">
                        Laporan lengkap hasil perbaikan tiket gangguan yang telah diselesaikan - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Statistics -->
            <div class="report-stats-container">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="report-stat-card">
                            <div class="report-stat-number"><?= $stats['total_reports'] ?></div>
                            <div class="report-stat-label">
                                <i class="fas fa-clipboard-check me-1"></i>
                                Total Laporan
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="report-stat-card">
                            <div class="report-stat-number"><?= $stats['temporary_repairs'] ?></div>
                            <div class="report-stat-label">
                                <i class="fas fa-tools me-1"></i>
                                Perbaikan Sementara
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="report-stat-card">
                            <div class="report-stat-number"><?= $stats['permanent_repairs'] ?></div>
                            <div class="report-stat-label">
                                <i class="fas fa-check-circle me-1"></i>
                                Perbaikan Permanen
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="report-stat-card">
                            <div class="report-stat-number"><?= number_format($stats['avg_completion_days'] ?? 0, 1) ?></div>
                            <div class="report-stat-label">
                                <i class="fas fa-calendar-check me-1"></i>
                                Rata-rata Hari Selesai
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="report-filter-section">
                <h5 class="report-filter-title">
                    <i class="fas fa-filter"></i>
                    Filter & Export Laporan
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="jenis" class="form-label">
                                <i class="fas fa-wrench me-1"></i>Jenis Perbaikan
                            </label>
                            <select class="report-form-control" id="jenis" name="jenis">
                                <option value="">Semua Jenis</option>
                                <option value="temporary" <?= $filter_jenis == 'temporary' ? 'selected' : '' ?>>Sementara</option>
                                <option value="permanent" <?= $filter_jenis == 'permanent' ? 'selected' : '' ?>>Permanen</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="month" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Bulan
                            </label>
                            <select class="report-form-control" id="month" name="month">
                                <option value="">Semua Bulan</option>
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= sprintf('%02d', $m) ?>" <?= $filter_month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Tahun
                            </label>
                            <select class="report-form-control" id="year" name="year">
                                <option value="">Semua Tahun</option>
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">
                                <i class="fas fa-search me-1"></i>Pencarian
                            </label>
                            <input type="text" class="report-form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari berdasarkan gangguan, lokasi, teknisi...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="report-btn report-btn-primary report-btn-sm">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <a href="export_report.php?month=<?= $filter_month ?>&year=<?= $filter_year ?>&jenis=<?= $filter_jenis ?>&search=<?= urlencode($search) ?>" 
                       class="report-btn report-btn-success report-btn-sm" target="_blank">
                        <i class="fas fa-file-excel"></i>
                        Export Excel
                    </a>
                    <a href="?" class="report-btn report-btn-info report-btn-sm">
                        <i class="fas fa-refresh"></i>
                        Reset Filter
                    </a>
                </div>
            </div>
            
            <!-- Reports Table -->
            <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title">
                        <i class="fas fa-table"></i>
                        Tabel Laporan Perbaikan
                        <span class="badge bg-info ms-2"><?= count($reports_list) ?> Laporan</span>
                    </h5>
                </div>
                <div class="report-card-body">
                    <?php if(count($reports_list) > 0): ?>
                        <div class="table-responsive">
                            <table class="report-table table" id="reportTable">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>No</th>
                                        <th><i class="fas fa-clipboard me-1"></i>ID Laporan</th>
                                        <th><i class="fas fa-ticket-alt me-1"></i>ID Tiket</th>
                                        <th><i class="fas fa-tools me-1"></i>Jenis Gangguan</th>
                                        <th><i class="fas fa-wrench me-1"></i>Jenis Perbaikan</th>
                                        <th><i class="fas fa-user-cog me-1"></i>Teknisi</th>
                                        <th><i class="fas fa-map-marker-alt me-1"></i>Lokasi</th>
                                        <th><i class="fas fa-calendar-check me-1"></i>Selesai</th>
                                        <th><i class="fas fa-clock me-1"></i>Durasi</th>
                                        <th><i class="fas fa-camera me-1"></i>Dokumentasi</th>
                                        <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($reports_list as $index => $report): ?>
                                        <tr>
                                            <td class="text-center"><?= $index + 1 ?></td>
                                            <td class="text-center"><strong>#<?= $report['id_laporan'] ?></strong></td>
                                            <td class="text-center"><strong>#<?= $report['id_tiket'] ?></strong></td>
                                            <td><?= htmlspecialchars($report['jenis_gangguan']) ?></td>
                                            <td class="text-center">
                                                <span class="report-badge badge-<?= $report['jenis_perbaikan'] ?>">
                                                    <?php if($report['jenis_perbaikan'] == 'temporary'): ?>
                                                        <i class="fas fa-tools"></i>
                                                        Sementara
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle"></i>
                                                        Permanen
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($report['teknisi_nama'] ?: 'Tidak ada') ?></td>
                                            <td><?= mb_strimwidth(htmlspecialchars($report['alamat']), 0, 50, "...") ?></td>
                                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($report['selesai_pada'])) ?></td>
                                            <td class="text-center"><?= $report['completion_days'] ?> hari</td>
                                            <td class="text-center">
                                                <?php if($report['dokumentasi']): ?>
                                                    <img src="../../uploads/<?= htmlspecialchars($report['dokumentasi']) ?>" 
                                                         alt="Dokumentasi" 
                                                         class="report-doc-img"
                                                         onclick="showImageModal('../../uploads/<?= htmlspecialchars($report['dokumentasi']) ?>', 'Dokumentasi Laporan #<?= $report['id_laporan'] ?>')">
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <button class="report-btn-action report-btn-detail" 
                                                        onclick="showDetailModal(<?= htmlspecialchars(json_encode($report)) ?>)">
                                                    <i class="fas fa-eye"></i>
                                                    Detail
                                                </button>
                                                <?php if($report['jenis_perbaikan'] == 'temporary'): ?>
                                                    <button class="report-btn-action report-btn-reassign" 
                                                            onclick="showReassignModal(<?= $report['id_tiket'] ?>, '<?= htmlspecialchars($report['jenis_gangguan']) ?>', '<?= htmlspecialchars($report['alamat']) ?>')">
                                                        <i class="fas fa-redo"></i>
                                                        Tugaskan Ulang
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="report-no-data">
                            <i class="fas fa-clipboard"></i>
                            <h5>Tidak ada laporan perbaikan</h5>
                            <p>Tidak ada laporan yang sesuai dengan filter yang diterapkan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Detail Laporan Perbaikan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Reassign Modal -->
    <div class="modal fade" id="reassignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-redo me-2"></i>
                        Penugasan Ulang Tiket Temporary
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body" id="reassignModalBody">
                        <input type="hidden" name="action" value="reassign">
                        <input type="hidden" name="id_tiket" id="reassign_id_tiket">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Perhatian!</strong> Perbaikan sebelumnya bersifat sementara. Tiket akan dikembalikan ke status "on progress" dan ditugaskan ulang kepada teknisi yang dipilih.
                        </div>
                        
                        <div id="ticketInfo" class="mb-3">
                            <!-- Ticket info will be loaded here -->
                        </div>
                        
                        <div class="mb-3">
                            <label for="reassign_teknisi" class="form-label">
                                <i class="fas fa-user-cog me-1"></i>
                                Pilih Teknisi Baru
                            </label>
                            <select class="report-form-control" id="reassign_teknisi" name="id_teknisi" required>
                                <option value="">-- Pilih Teknisi --</option>
                                <?php foreach($teknisi_list as $teknisi): ?>
                                    <option value="<?= $teknisi['id_teknisi'] ?>">
                                        <?= htmlspecialchars($teknisi['nama_lengkap']) ?> (<?= htmlspecialchars($teknisi['username']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="report-btn report-btn-info" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>
                            Batal
                        </button>
                        <button type="submit" class="report-btn report-btn-warning">
                            <i class="fas fa-redo me-1"></i>
                            Tugaskan Ulang
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">Dokumentasi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imageModalImg" src="" alt="Dokumentasi" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stat numbers
            const statNumbers = document.querySelectorAll('.report-stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseFloat(stat.textContent);
                if (!isNaN(finalValue)) {
                    let currentValue = 0;
                    const increment = finalValue / 50;
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            stat.textContent = finalValue % 1 === 0 ? finalValue : finalValue.toFixed(1);
                            clearInterval(timer);
                        } else {
                            stat.textContent = currentValue % 1 === 0 ? Math.floor(currentValue) : currentValue.toFixed(1);
                        }
                    }, 30);
                }
            });

            // Real-time search functionality
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const value = this.value.toLowerCase();
                        const rows = document.querySelectorAll('#reportTable tbody tr');
                        
                        rows.forEach(function(row) {
                            const text = row.textContent.toLowerCase();
                            const isVisible = text.indexOf(value) > -1;
                            row.style.display = isVisible ? '' : 'none';
                        });
                        
                        // Update visible count
                        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                        updateVisibleCount(visibleRows.length);
                    }, 300);
                });
            }

            // Update visible count function
            function updateVisibleCount(count) {
                const badge = document.querySelector('.badge.bg-info');
                if (badge) {
                    badge.textContent = count + ' Laporan';
                }
            }

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Show detail modal
        function showDetailModal(report) {
            const modalBody = document.getElementById('detailModalBody');
            const completionDays = report.completion_days || 0;
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Informasi Laporan</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>ID Laporan:</strong></td><td>#${report.id_laporan}</td></tr>
                            <tr><td><strong>ID Tiket:</strong></td><td>#${report.id_tiket}</td></tr>
                            <tr><td><strong>Jenis Perbaikan:</strong></td><td>${report.jenis_perbaikan === 'temporary' ? 'Sementara' : 'Permanen'}</td></tr>
                            <tr><td><strong>Selesai Pada:</strong></td><td>${new Date(report.selesai_pada).toLocaleString('id-ID')}</td></tr>
                            <tr><td><strong>Durasi Pengerjaan:</strong></td><td>${completionDays} hari</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Informasi Tiket</h6>
                        <table class="table table-borderless">
                            <tr><td><strong>Jenis Gangguan:</strong></td><td>${report.jenis_gangguan}</td></tr>
                            <tr><td><strong>Admin Pembuat:</strong></td><td>${report.admin_nama}</td></tr>
                            <tr><td><strong>Teknisi:</strong></td><td>${report.teknisi_nama || 'Tidak ada'}</td></tr>
                            <tr><td><strong>Dibuat:</strong></td><td>${new Date(report.tiket_created).toLocaleString('id-ID')}</td></tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Deskripsi Tiket</h6>
                        <p class="bg-light p-3 rounded">${report.tiket_deskripsi}</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Lokasi</h6>
                        <p class="bg-light p-3 rounded">
                            <i class="fas fa-map-marker-alt text-danger"></i> ${report.alamat}
                            ${report.latitude && report.longitude ? `<br><small class="text-muted">Koordinat: ${report.latitude}, ${report.longitude}</small>` : ''}
                        </p>
                    </div>
                </div>
                
                ${report.catatan ? `
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Catatan Perbaikan</h6>
                        <p class="bg-light p-3 rounded">${report.catatan.replace(/\n/g, '<br>')}</p>
                    </div>
                </div>
                ` : ''}
                
                ${report.dokumentasi ? `
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">Dokumentasi</h6>
                        <img src="../../uploads/${report.dokumentasi}" alt="Dokumentasi" class="img-fluid rounded" style="max-height: 300px; cursor: pointer;" onclick="showImageModal('../../uploads/${report.dokumentasi}', 'Dokumentasi Laporan #${report.id_laporan}')">
                    </div>
                </div>
                ` : ''}
            `;
            
            new bootstrap.Modal(document.getElementById('detailModal')).show();
        }

        // Show reassign modal
        function showReassignModal(idTiket, jenisGangguan, alamat) {
            document.getElementById('reassign_id_tiket').value = idTiket;
            
            // Update ticket info
            document.getElementById('ticketInfo').innerHTML = `
                <div class="bg-light p-3 rounded">
                    <h6 class="mb-2">Informasi Tiket:</h6>
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td><strong>ID Tiket:</strong></td><td>#${idTiket}</td></tr>
                        <tr><td><strong>Jenis Gangguan:</strong></td><td>${jenisGangguan}</td></tr>
                        <tr><td><strong>Lokasi:</strong></td><td>${alamat}</td></tr>
                    </table>
                </div>
            `;
            
            // Reset teknisi selection
            document.getElementById('reassign_teknisi').value = '';
            
            new bootstrap.Modal(document.getElementById('reassignModal')).show();
        }

        // Show image modal
        function showImageModal(imageSrc, title) {
            document.getElementById('imageModalImg').src = imageSrc;
            document.getElementById('imageModalTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Confirm reassignment with SweetAlert
        document.querySelector('#reassignModal form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const teknisiSelect = document.getElementById('reassign_teknisi');
            const teknisiName = teknisiSelect.options[teknisiSelect.selectedIndex].text;
            const idTiket = document.getElementById('reassign_id_tiket').value;
            
            Swal.fire({
                title: 'Konfirmasi Penugasan Ulang',
                html: `Apakah Anda yakin ingin menugaskan ulang tiket <strong>#${idTiket}</strong> kepada <strong>${teknisiName}</strong>?<br><br>
                       <small class="text-muted">Tindakan ini akan:</small><br>
                       <small>• Mengubah status tiket kembali ke "on progress"</small><br>
                       <small>• Menghapus laporan temporary yang sudah ada</small><br>
                       <small>• Mengirim notifikasi kepada teknisi terpilih</small>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-redo me-1"></i> Ya, Tugaskan Ulang',
                cancelButtonText: '<i class="fas fa-times me-1"></i> Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang melakukan penugasan ulang',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>
