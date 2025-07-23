<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: ../../index.php');
    exit();
}

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor';
$userRole = 'supervisor';

// Filter parameters
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$where_conditions = ["1=1"];
$params = [];

if (!empty($filter_status)) {
    $where_conditions[] = "t.status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_month) && !empty($filter_year)) {
    $where_conditions[] = "MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?";
    $params[] = $filter_month;
    $params[] = $filter_year;
}

if (!empty($search)) {
    $where_conditions[] = "(t.jenis_gangguan LIKE ? OR l.alamat LIKE ? OR a.nama_lengkap LIKE ? OR tek.nama_lengkap LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Get tickets with progress information
$tickets_query = $pdo->prepare("
    SELECT 
        t.id_tiket,
        t.jenis_gangguan,
        t.deskripsi,
        t.status,
        t.created_at,
        l.alamat,
        l.latitude,
        l.longitude,
        a.nama_lengkap as admin_nama,
        a.username as admin_username,
        tek.nama_lengkap as teknisi_nama,
        tek.username as teknisi_username,
        tek.foto as teknisi_foto,
        p.created_at as assigned_date,
        lap.jenis_perbaikan,
        lap.selesai_pada,
        lap.catatan as laporan_catatan,
        lap.dokumentasi,
        CASE 
            WHEN t.status = 'open' THEN 0
            WHEN t.status = 'on progress' THEN 50
            WHEN t.status = 'selesai' THEN 100
            ELSE 0
        END as progress_percentage,
        CASE 
            WHEN t.status = 'selesai' AND lap.selesai_pada IS NOT NULL 
            THEN DATEDIFF(lap.selesai_pada, t.created_at)
            WHEN t.status != 'selesai' 
            THEN DATEDIFF(NOW(), t.created_at)
            ELSE NULL
        END as days_elapsed
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN admin a ON t.id_admin = a.id_admin
    LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
    LEFT JOIN laporan lap ON t.id_tiket = lap.id_tiket AND lap.id_teknisi = tek.id_teknisi
    WHERE $where_clause
    ORDER BY t.created_at DESC
");
$tickets_query->execute($params);
$tickets_list = $tickets_query->fetchAll();

// Get overall statistics
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total_tickets,
        COUNT(CASE WHEN status = 'open' THEN 1 END) as status_open,
        COUNT(CASE WHEN status = 'on progress' THEN 1 END) as status_progress,
        COUNT(CASE WHEN status = 'selesai' THEN 1 END) as status_selesai
    FROM tiket
");
$stats_query->execute();
$stats = $stats_query->fetch();

// Status badge map
$status_badge = [
    'open' => ['bg-warning', 'Belum Dikerjakan', 'fa-clock'],
    'on progress' => ['bg-info', 'Sedang Dikerjakan', 'fa-spinner'],
    'selesai' => ['bg-success', 'Selesai', 'fa-check-circle']
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Progress Report - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --progress-telkom-red: #E31E24;
            --progress-telkom-dark-red: #B71C1C;
            --progress-telkom-light-red: #FFEBEE;
            --progress-telkom-gray: #F5F5F5;
            --progress-telkom-dark-gray: #424242;
            --progress-telkom-white: #FFFFFF;
            --progress-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --progress-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --progress-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --progress-border-radius: 12px;
            --progress-border-radius-small: 8px;
            --progress-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Reset dan Base Styles */
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

        /* Main Content Area */
        .progress-main-content {
            padding: 110px 25px 25px;
            transition: var(--progress-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .progress-header-section {
            background: linear-gradient(135deg, var(--progress-telkom-red) 0%, var(--progress-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--progress-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--progress-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .progress-header-section::before {
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

        .progress-header-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            transform: translate(-50%, 50%);
        }

        .progress-header-content {
            position: relative;
            z-index: 2;
        }

        .progress-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .progress-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Statistics Cards */
        .progress-stats-container {
            margin-bottom: 2rem;
        }

        .progress-stat-card {
            background: var(--progress-telkom-white);
            border-radius: var(--progress-border-radius);
            padding: 1.5rem;
            box-shadow: var(--progress-shadow-light);
            transition: var(--progress-transition);
            border-left: 4px solid var(--progress-telkom-red);
            text-align: center;
            height: 100%;
        }

        .progress-stat-card:hover {
            box-shadow: var(--progress-shadow-medium);
            transform: translateY(-2px);
        }

        .progress-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--progress-telkom-red);
            margin-bottom: 0.5rem;
        }

        .progress-stat-label {
            color: var(--progress-telkom-dark-gray);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Card Styles */
        .progress-card {
            background: var(--progress-telkom-white);
            border: none;
            border-radius: var(--progress-border-radius);
            box-shadow: var(--progress-shadow-light);
            transition: var(--progress-transition);
            overflow: hidden;
            border-left: 4px solid var(--progress-telkom-red);
            margin-bottom: 2rem;
        }

        .progress-card:hover {
            box-shadow: var(--progress-shadow-medium);
            transform: translateY(-2px);
        }

        .progress-card-header {
            background: linear-gradient(135deg, var(--progress-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--progress-telkom-red);
            padding: 1.5rem;
            border-radius: var(--progress-border-radius) var(--progress-border-radius) 0 0 !important;
        }

        .progress-card-title {
            color: var(--progress-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-card-body {
            padding: 2rem;
        }

        /* Filter Section */
        .progress-filter-section {
            background: var(--progress-telkom-white);
            border-radius: var(--progress-border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--progress-shadow-light);
            border-left: 4px solid var(--progress-telkom-red);
        }

        .progress-filter-title {
            color: var(--progress-telkom-red);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Form Styles */
        .progress-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--progress-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--progress-transition);
            background: #fafafa;
            width: 100%;
        }

        .progress-form-control:focus {
            border-color: var(--progress-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        /* Button Styles */
        .progress-btn {
            border-radius: var(--progress-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--progress-transition);
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

        .progress-btn-primary {
            background: linear-gradient(135deg, var(--progress-telkom-red) 0%, var(--progress-telkom-dark-red) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.3);
        }

        .progress-btn-primary:hover {
            background: linear-gradient(135deg, var(--progress-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .progress-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .progress-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .progress-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-width: 100px;
            font-weight: 600;
        }

        /* Ticket Card */
        .progress-ticket-card {
            background: white;
            border-radius: var(--progress-border-radius);
            padding: 1.5rem;
            box-shadow: var(--progress-shadow-light);
            transition: var(--progress-transition);
            border-left: 4px solid;
            margin-bottom: 1.5rem;
        }

        .progress-ticket-card:hover {
            box-shadow: var(--progress-shadow-medium);
            transform: translateY(-2px);
        }

        .progress-ticket-card.status-open { border-left-color: #ffc107; }
        .progress-ticket-card.status-progress { border-left-color: #17a2b8; }
        .progress-ticket-card.status-completed { border-left-color: #28a745; }

        .progress-ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .progress-ticket-info h5 {
            color: var(--progress-telkom-red);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .progress-ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .progress-ticket-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Status Badge */
        .progress-status-badge {
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

        .status-open {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }

        .status-progress {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #17a2b8;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }

        /* Progress Bar */
        .progress-bar-container {
            margin: 1rem 0;
        }

        .progress-bar-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .progress-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            transition: width 0.6s ease;
            border-radius: 4px;
        }

        .progress-0 { background: #dc3545; }
        .progress-50 { background: #ffc107; }
        .progress-100 { background: #28a745; }

        /* Teknisi Info */
        .progress-teknisi-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--progress-border-radius-small);
            margin-top: 1rem;
        }

        .progress-teknisi-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--progress-telkom-red);
        }

        .progress-teknisi-details h6 {
            margin: 0;
            color: var(--progress-telkom-red);
            font-weight: 600;
        }

        .progress-teknisi-details small {
            color: #6c757d;
        }

        /* Action Buttons */
        .progress-action-container {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .progress-btn-action {
            padding: 0.5rem 1rem;
            border-radius: var(--progress-border-radius-small);
            font-weight: 500;
            font-size: 0.8rem;
            text-decoration: none;
            transition: var(--progress-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .progress-btn-detail {
            background: #17a2b8;
            color: white;
        }

        .progress-btn-detail:hover {
            background: #138496;
            color: white;
            transform: translateY(-1px);
        }

        /* Chart Container */
        .progress-chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* No Data Message */
        .progress-no-data {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .progress-no-data i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #d1d1d1;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .progress-main-content {
                padding: 110px 20px 25px;
            }
            
            .progress-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .progress-main-content {
                padding: 110px 15px 25px;
            }
            
            .progress-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .progress-header-title {
                font-size: 1.8rem;
            }
            
            .progress-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .progress-main-content {
                padding: 110px 10px 25px;
            }
            
            .progress-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .progress-header-title {
                font-size: 1.5rem;
            }
            
            .progress-card-body {
                padding: 1rem;
            }
            
            .progress-ticket-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        @media (max-width: 576px) {
            .progress-header-title {
                font-size: 1.3rem;
            }
            
            .progress-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
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
        
        <div class="progress-main-content">
            <!-- Header Section -->
            <div class="progress-header-section">
                <div class="progress-telkom-logo">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="progress-header-content">
                    <h1 class="progress-header-title">
                        <i class="fas fa-tasks me-3"></i>
                        Progress Report Tiket
                    </h1>
                    <p class="progress-header-subtitle">
                        Pantau progress dan status seluruh tiket gangguan secara real-time - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="progress-stats-container">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="progress-stat-card">
                            <div class="progress-stat-number"><?= $stats['total_tickets'] ?></div>
                            <div class="progress-stat-label">
                                <i class="fas fa-ticket-alt me-1"></i>
                                Total Tiket
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="progress-stat-card">
                            <div class="progress-stat-number"><?= $stats['status_open'] ?></div>
                            <div class="progress-stat-label">
                                <i class="fas fa-clock me-1"></i>
                                Tiket Open
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="progress-stat-card">
                            <div class="progress-stat-number"><?= $stats['status_selesai'] ?></div>
                            <div class="progress-stat-label">
                                <i class="fas fa-cog me-1"></i>
                                Selesai
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="progress-stat-card">
                            <div class="progress-stat-number"><?= number_format($stats['avg_completion_days'] ?? 0, 1) ?></div>
                            <div class="progress-stat-label">
                                <i class="fas fa-calendar-check me-1"></i>
                                Rata-rata Hari Selesai
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="progress-filter-section">
                <h5 class="progress-filter-title">
                    <i class="fas fa-filter"></i>
                    Filter & Pencarian Progress Report
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="status" class="form-label">
                                <i class="fas fa-info-circle me-1"></i>Status
                            </label>
                            <select class="progress-form-control" id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="open" <?= $filter_status == 'open' ? 'selected' : '' ?>>Belum Ditugaskan</option>
                                <option value="on progress" <?= $filter_status == 'on progress' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                                <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="month" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Bulan
                            </label>
                            <select class="progress-form-control" id="month" name="month">
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
                            <select class="progress-form-control" id="year" name="year">
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
                            <input type="text" class="progress-form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari berdasarkan jenis gangguan, lokasi, teknisi...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="progress-btn progress-btn-primary progress-btn-sm">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <a href="?" class="progress-btn progress-btn-info progress-btn-sm">
                        <i class="fas fa-refresh"></i>
                        Reset Filter
                    </a>
                </div>
            </div>
            
            <!-- Progress Report Cards -->
            <div class="progress-card">
                <div class="progress-card-header">
                    <h5 class="progress-card-title">
                        <i class="fas fa-list-check"></i>
                        Progress Report Tiket
                        <span class="badge bg-info ms-2"><?= count($tickets_list) ?> Tiket</span>
                    </h5>
                </div>
                <div class="progress-card-body">
                    <?php if(count($tickets_list) > 0): ?>
                        <?php foreach($tickets_list as $ticket): ?>
                            <?php 
                            $status_class = '';
                            $status_text = '';
                            switch($ticket['status']) {
                                case 'open':
                                    $status_class = 'status-open';
                                    $status_text = 'Belum Ditugaskan';
                                    break;
                                case 'on progress':
                                    $status_class = 'status-progress';
                                    $status_text = 'Sedang Dikerjakan';
                                    break;
                                case 'selesai':
                                    $status_class = 'status-completed';
                                    $status_text = 'Selesai';
                                    break;
                            }
                            ?>
                            <div class="progress-ticket-card <?= $status_class ?>">
                                <div class="progress-ticket-header">
                                    <div class="progress-ticket-info">
                                        <h5>
                                            <i class="fas fa-ticket-alt me-2"></i>
                                            Tiket #<?= $ticket['id_tiket'] ?> - <?= htmlspecialchars($ticket['jenis_gangguan']) ?>
                                        </h5>
                                        <div class="progress-ticket-meta">
                                            <span>
                                                <i class="fas fa-user"></i>
                                                Admin: <?= htmlspecialchars($ticket['admin_nama']) ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-calendar"></i>
                                                Dibuat: <?= date('d M Y H:i', strtotime($ticket['created_at'])) ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                <?= $ticket['days_elapsed'] ?> hari
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="progress-status-badge <?= $status_class ?>">
                                            <?php if($ticket['status'] == 'open'): ?>
                                                <i class="fas fa-clock"></i>
                                            <?php elseif($ticket['status'] == 'on progress'): ?>
                                                <i class="fas fa-cog fa-spin"></i>
                                            <?php else: ?>
                                                <i class="fas fa-check-circle"></i>
                                            <?php endif; ?>
                                            <?= $status_text ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="progress-bar-container">
                                    <div class="progress-bar-label">
                                        <span>Progress Penyelesaian</span>
                                        <span><?= $ticket['progress_percentage'] ?>%</span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-bar-fill progress-<?= $ticket['progress_percentage'] ?>" 
                                             style="width: <?= $ticket['progress_percentage'] ?>%"></div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mt-3">
                                    <strong>Deskripsi:</strong>
                                    <p class="mb-2"><?= mb_strimwidth(htmlspecialchars($ticket['deskripsi']), 0, 200, "...") ?></p>
                                </div>
                                
                                <!-- Location -->
                                <div class="mb-3">
                                    <strong>Lokasi:</strong>
                                    <p class="mb-0">
                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                        <?= htmlspecialchars($ticket['alamat']) ?>
                                    </p>
                                </div>
                                
                                <!-- Teknisi Info -->
                                <?php if($ticket['teknisi_nama']): ?>
                                <div class="progress-teknisi-info">
                                    <img src="../../uploads/users/<?= htmlspecialchars($ticket['teknisi_foto']) ?>" 
                                         alt="<?= htmlspecialchars($ticket['teknisi_nama']) ?>" 
                                         class="progress-teknisi-avatar"
                                         onerror="this.src='../../uploads/default.svg'">
                                    <div class="progress-teknisi-details">
                                        <h6><?= htmlspecialchars($ticket['teknisi_nama']) ?></h6>
                                        <small>@<?= htmlspecialchars($ticket['teknisi_username']) ?></small>
                                        <?php if($ticket['assigned_date']): ?>
                                        <br><small>Ditugaskan: <?= date('d M Y H:i', strtotime($ticket['assigned_date'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Laporan Info -->
                                <?php if($ticket['status'] == 'selesai' && $ticket['selesai_pada']): ?>
                                <div class="mt-3 p-3 bg-success bg-opacity-10 rounded">
                                    <h6 class="text-success mb-2">
                                        <i class="fas fa-check-circle"></i>
                                        Laporan Penyelesaian
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small><strong>Jenis Perbaikan:</strong></small>
                                            <p class="mb-1"><?= ucfirst($ticket['jenis_perbaikan']) ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <small><strong>Selesai Pada:</strong></small>
                                            <p class="mb-1"><?= date('d M Y H:i', strtotime($ticket['selesai_pada'])) ?></p>
                                        </div>
                                    </div>
                                    <?php if($ticket['laporan_catatan']): ?>
                                    <small><strong>Catatan:</strong></small>
                                    <p class="mb-0"><?= mb_strimwidth(htmlspecialchars($ticket['laporan_catatan']), 0, 150, "...") ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Action Buttons -->
                                <div class="progress-action-container">
                                    <a href="detail_tiket.php?id=<?= $ticket['id_tiket'] ?>" 
                                       class="progress-btn-action progress-btn-detail">
                                        <i class="fas fa-eye"></i>
                                        Detail Lengkap
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="progress-no-data">
                            <i class="fas fa-chart-bar"></i>
                            <h5>Tidak ada data tiket</h5>
                            <p>Tidak ada tiket yang sesuai dengan filter yang diterapkan.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll('.progress-bar-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });

            // Animate stat numbers
            const statNumbers = document.querySelectorAll('.progress-stat-number');
            statNumbers.forEach(stat => {
                const finalValue = parseInt(stat.textContent);
                if (!isNaN(finalValue)) {
                    let currentValue = 0;
                    const increment = finalValue / 50;
                    const timer = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= finalValue) {
                            stat.textContent = finalValue;
                            clearInterval(timer);
                        } else {
                            stat.textContent = Math.floor(currentValue);
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
                        const tickets = document.querySelectorAll('.progress-ticket-card');
                        
                        tickets.forEach(function(ticket) {
                            const text = ticket.textContent.toLowerCase();
                            const isVisible = text.indexOf(value) > -1;
                            ticket.style.display = isVisible ? 'block' : 'none';
                        });
                    }, 300);
                });
            }

            // Auto-refresh functionality
            let autoRefreshInterval;
            const enableAutoRefresh = false; // Set to true if you want auto-refresh
            
            if (enableAutoRefresh) {
                autoRefreshInterval = setInterval(() => {
                    if (!searchInput.value) {
                        location.reload();
                    }
                }, 300000); // Refresh every 5 minutes
            }

            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+F to focus search
                if (e.ctrlKey && e.key === 'f' && searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                    searchInput.select();
                }
                
                // Ctrl+R for refresh
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    location.reload();
                }
            });
        });
    </script>
</body>
</html>
