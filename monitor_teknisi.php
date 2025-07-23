<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Handle logout
if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: ../../index.php');
    exit();
}

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor User';
$userRole = 'supervisor';

// Filter parameters
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Build date filter conditions
$date_filter = "";
$date_params = [];
if (!empty($filter_month) && !empty($filter_year)) {
    $date_filter = "AND MONTH(p.created_at) = ? AND YEAR(p.created_at) = ?";
    $date_params = [$filter_month, $filter_year];
}

// --- TEKNISI STATISTICS ---
$teknisi_query = $pdo->prepare("
    SELECT 
        tk.id_teknisi,
        tk.username,
        tk.nama_lengkap,
        tk.foto,
        tk.created_at as registered_date,
        COUNT(DISTINCT p.id_tiket) as total_assigned,
        COUNT(DISTINCT CASE WHEN t.status = 'on progress' THEN p.id_tiket END) as in_progress,
        COUNT(DISTINCT CASE WHEN t.status = 'selesai' THEN p.id_tiket END) as completed,
        COUNT(DISTINCT CASE WHEN t.status = 'open' THEN p.id_tiket END) as not_started,
        AVG(CASE 
            WHEN t.status = 'selesai' AND l.selesai_pada IS NOT NULL 
            THEN DATEDIFF(l.selesai_pada, p.created_at) 
            ELSE NULL 
        END) as avg_completion_days,
        MAX(p.created_at) as last_assignment_date
    FROM teknisi tk
    LEFT JOIN penugasan p ON tk.id_teknisi = p.id_teknisi $date_filter
    LEFT JOIN tiket t ON p.id_tiket = t.id_tiket
    LEFT JOIN laporan l ON t.id_tiket = l.id_tiket AND l.id_teknisi = tk.id_teknisi
    GROUP BY tk.id_teknisi, tk.username, tk.nama_lengkap, tk.foto, tk.created_at
    ORDER BY tk.nama_lengkap ASC
");
$teknisi_query->execute($date_params);
$teknisi_list = $teknisi_query->fetchAll();

// --- OVERALL STATISTICS ---
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT tk.id_teknisi) as total_teknisi,
        COUNT(DISTINCT p.id_tiket) as total_tiket_assigned,
        COUNT(DISTINCT CASE WHEN t.status = 'on progress' THEN p.id_tiket END) as total_in_progress,
        COUNT(DISTINCT CASE WHEN t.status = 'selesai' THEN p.id_tiket END) as total_completed,
        AVG(CASE 
            WHEN t.status = 'selesai' AND l.selesai_pada IS NOT NULL 
            THEN DATEDIFF(l.selesai_pada, p.created_at) 
            ELSE NULL 
        END) as overall_avg_days
    FROM teknisi tk
    LEFT JOIN penugasan p ON tk.id_teknisi = p.id_teknisi $date_filter
    LEFT JOIN tiket t ON p.id_tiket = t.id_tiket
    LEFT JOIN laporan l ON t.id_tiket = l.id_tiket AND l.id_teknisi = tk.id_teknisi
");
$stats_query->execute($date_params);
$overall_stats = $stats_query->fetch();

// --- RECENT ACTIVITIES ---
$activity_query = $pdo->prepare("
    SELECT 
        tk.nama_lengkap as teknisi_nama,
        tk.foto,
        t.id_tiket,
        t.jenis_gangguan,
        t.status,
        p.created_at as assigned_date,
        l.selesai_pada,
        CASE 
            WHEN l.selesai_pada IS NOT NULL THEN 'completed'
            WHEN t.status = 'on progress' THEN 'working'
            ELSE 'assigned'
        END as activity_type
    FROM penugasan p
    JOIN teknisi tk ON p.id_teknisi = tk.id_teknisi
    JOIN tiket t ON p.id_tiket = t.id_tiket
    LEFT JOIN laporan l ON t.id_tiket = l.id_tiket AND l.id_teknisi = tk.id_teknisi
    WHERE 1=1 $date_filter
    ORDER BY COALESCE(l.selesai_pada, p.created_at) DESC
    LIMIT 10
");
$activity_query->execute($date_params);
$recent_activities = $activity_query->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Monitor Teknisi - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --monitor-telkom-red: #E31E24;
            --monitor-telkom-dark-red: #B71C1C;
            --monitor-telkom-light-red: #FFEBEE;
            --monitor-telkom-gray: #F5F5F5;
            --monitor-telkom-dark-gray: #424242;
            --monitor-telkom-white: #FFFFFF;
            --monitor-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --monitor-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --monitor-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --monitor-border-radius: 12px;
            --monitor-border-radius-small: 8px;
            --monitor-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .monitor-main-content {
            padding: 110px 25px 25px;
            transition: var(--monitor-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .monitor-header-section {
            background: linear-gradient(135deg, var(--monitor-telkom-red) 0%, var(--monitor-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--monitor-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--monitor-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .monitor-header-section::before {
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

        .monitor-header-section::after {
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

        .monitor-header-content {
            position: relative;
            z-index: 2;
        }

        .monitor-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .monitor-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .monitor-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Statistics Cards */
        .monitor-stats-container {
            margin-bottom: 2rem;
        }

        .monitor-stat-card {
            background: var(--monitor-telkom-white);
            border-radius: var(--monitor-border-radius);
            padding: 1.5rem;
            box-shadow: var(--monitor-shadow-light);
            transition: var(--monitor-transition);
            border-left: 4px solid var(--monitor-telkom-red);
            text-align: center;
            height: 100%;
        }

        .monitor-stat-card:hover {
            box-shadow: var(--monitor-shadow-medium);
            transform: translateY(-2px);
        }

        .monitor-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--monitor-telkom-red);
            margin-bottom: 0.5rem;
        }

        .monitor-stat-label {
            color: var(--monitor-telkom-dark-gray);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Card Styles */
        .monitor-card {
            background: var(--monitor-telkom-white);
            border: none;
            border-radius: var(--monitor-border-radius);
            box-shadow: var(--monitor-shadow-light);
            transition: var(--monitor-transition);
            overflow: hidden;
            border-left: 4px solid var(--monitor-telkom-red);
            margin-bottom: 2rem;
        }

        .monitor-card:hover {
            box-shadow: var(--monitor-shadow-medium);
            transform: translateY(-2px);
        }

        .monitor-card-header {
            background: linear-gradient(135deg, var(--monitor-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--monitor-telkom-red);
            padding: 1.5rem;
            border-radius: var(--monitor-border-radius) var(--monitor-border-radius) 0 0 !important;
        }

        .monitor-card-title {
            color: var(--monitor-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .monitor-card-body {
            padding: 2rem;
        }

        /* Filter Section */
        .monitor-filter-section {
            background: var(--monitor-telkom-white);
            border-radius: var(--monitor-border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--monitor-shadow-light);
            border-left: 4px solid var(--monitor-telkom-red);
        }

        .monitor-filter-title {
            color: var(--monitor-telkom-red);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Form Styles */
        .monitor-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--monitor-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--monitor-transition);
            background: #fafafa;
            width: 100%;
        }

        .monitor-form-control:focus {
            border-color: var(--monitor-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        /* Button Styles */
        .monitor-btn {
            border-radius: var(--monitor-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--monitor-transition);
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

        .monitor-btn-primary {
            background: linear-gradient(135deg, var(--monitor-telkom-red) 0%, var(--monitor-telkom-dark-red) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.3);
        }

        .monitor-btn-primary:hover {
            background: linear-gradient(135deg, var(--monitor-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .monitor-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .monitor-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .monitor-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-width: 100px;
            font-weight: 600;
        }

        /* Teknisi Card */
        .monitor-teknisi-card {
            background: white;
            border-radius: var(--monitor-border-radius);
            padding: 1.5rem;
            box-shadow: var(--monitor-shadow-light);
            transition: var(--monitor-transition);
            border-left: 4px solid var(--monitor-telkom-red);
            margin-bottom: 1.5rem;
        }

        .monitor-teknisi-card:hover {
            box-shadow: var(--monitor-shadow-medium);
            transform: translateY(-2px);
        }

        .monitor-teknisi-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .monitor-teknisi-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--monitor-telkom-red);
        }

        .monitor-teknisi-info h5 {
            color: var(--monitor-telkom-red);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .monitor-teknisi-info small {
            color: #6c757d;
        }

        .monitor-teknisi-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }

        .monitor-stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--monitor-border-radius-small);
            border: 1px solid #e9ecef;
        }

        .monitor-stat-item-number {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .monitor-stat-item-label {
            font-size: 0.8rem;
            color: #6c757d;
            font-weight: 500;
        }

        .stat-total { color: #007bff; }
        .stat-progress { color: #ffc107; }
        .stat-completed { color: #28a745; }
        .stat-pending { color: #dc3545; }
        .stat-avg { color: #6f42c1; }

        /* Activity Timeline */
        .monitor-activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--monitor-border-radius-small);
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .monitor-activity-item.assigned { border-left-color: #17a2b8; }
        .monitor-activity-item.working { border-left-color: #ffc107; }
        .monitor-activity-item.completed { border-left-color: #28a745; }

        .monitor-activity-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .monitor-activity-content {
            flex: 1;
        }

        .monitor-activity-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .monitor-activity-desc {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .monitor-activity-time {
            font-size: 0.8rem;
            color: #adb5bd;
        }

        /* Progress Bars */
        .monitor-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .monitor-progress-bar {
            height: 100%;
            transition: width 0.6s ease;
        }

        .progress-completed { background: #28a745; }
        .progress-in-progress { background: #ffc107; }
        .progress-pending { background: #dc3545; }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .monitor-main-content {
                padding: 110px 20px 25px;
            }
            
            .monitor-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .monitor-main-content {
                padding: 110px 15px 25px;
            }
            
            .monitor-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .monitor-header-title {
                font-size: 1.8rem;
            }
            
            .monitor-card-body {
                padding: 1.5rem;
            }
            
            .monitor-teknisi-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .monitor-main-content {
                padding: 110px 10px 25px;
            }
            
            .monitor-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .monitor-header-title {
                font-size: 1.5rem;
            }
            
            .monitor-card-body {
                padding: 1rem;
            }
            
            .monitor-teknisi-stats {
                grid-template-columns: 1fr;
            }
            
            .monitor-teknisi-header {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            .monitor-header-title {
                font-size: 1.3rem;
            }
            
            .monitor-btn {
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

        <div class="monitor-main-content">
            <!-- Header Section -->
            <div class="monitor-header-section">
                <div class="monitor-telkom-logo">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="monitor-header-content">
                    <h1 class="monitor-header-title">
                        <i class="fas fa-chart-line me-3"></i>
                        Monitor Kinerja Teknisi
                    </h1>
                    <p class="monitor-header-subtitle">
                        Pantau dan analisis kinerja teknisi dalam menangani tiket gangguan - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Overall Statistics -->
            <div class="monitor-stats-container">
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <div class="monitor-stat-card">
                            <div class="monitor-stat-number"><?= $overall_stats['total_teknisi'] ?></div>
                            <div class="monitor-stat-label">
                                <i class="fas fa-users me-1"></i>
                                Total Teknisi Aktif
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitor-stat-card">
                            <div class="monitor-stat-number"><?= $overall_stats['total_tiket_assigned'] ?></div>
                            <div class="monitor-stat-label">
                                <i class="fas fa-ticket-alt me-1"></i>
                                Total Tiket Ditugaskan
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitor-stat-card">
                            <div class="monitor-stat-number"><?= $overall_stats['total_in_progress'] ?></div>
                            <div class="monitor-stat-label">
                                <i class="fas fa-cog me-1"></i>
                                Sedang Dikerjakan
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="monitor-stat-card">
                            <div class="monitor-stat-number"><?= number_format($overall_stats['overall_avg_days'] ?? 0, 1) ?></div>
                            <div class="monitor-stat-label">
                                <i class="fas fa-clock me-1"></i>
                                Rata-rata Hari Selesai
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="monitor-filter-section">
                <h5 class="monitor-filter-title">
                    <i class="fas fa-filter"></i>
                    Filter Periode Monitoring
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="month" class="form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Bulan
                            </label>
                            <select class="monitor-form-control" id="month" name="month">
                                <option value="">Semua Bulan</option>
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= sprintf('%02d', $m) ?>" <?= $filter_month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">
                                <i class="fas fa-calendar me-1"></i>Tahun
                            </label>
                            <select class="monitor-form-control" id="year" name="year">
                                <option value="">Semua Tahun</option>
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="monitor-btn monitor-btn-primary monitor-btn-sm">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                                <a href="?" class="monitor-btn monitor-btn-info monitor-btn-sm">
                                    <i class="fas fa-refresh"></i>
                                    Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Teknisi Performance Cards -->
            <div class="monitor-card">
                <div class="monitor-card-header">
                    <h5 class="monitor-card-title">
                        <i class="fas fa-users-cog"></i>
                        Kinerja Individual Teknisi
                        <span class="badge bg-info ms-2"><?= count($teknisi_list) ?> Teknisi</span>
                    </h5>
                </div>
                <div class="monitor-card-body">
                    <?php if(count($teknisi_list) > 0): ?>
                        <?php foreach($teknisi_list as $teknisi): ?>
                            <?php 
                            $completion_rate = $teknisi['total_assigned'] > 0 ? 
                                ($teknisi['completed'] / $teknisi['total_assigned']) * 100 : 0;
                            ?>
                            <div class="monitor-teknisi-card">
                                <div class="monitor-teknisi-header">
                                    <img src="../../uploads/users/<?= htmlspecialchars($teknisi['foto']) ?>" 
                                         alt="<?= htmlspecialchars($teknisi['nama_lengkap']) ?>" 
                                         class="monitor-teknisi-avatar"
                                         onerror="this.src='../../uploads/default.svg'">
                                    <div class="monitor-teknisi-info">
                                        <h5><?= htmlspecialchars($teknisi['nama_lengkap']) ?></h5>
                                        <small>@<?= htmlspecialchars($teknisi['username']) ?></small>
                                        <br>
                                        <small>
                                            <i class="fas fa-calendar-plus"></i>
                                            Bergabung: <?= date('d M Y', strtotime($teknisi['registered_date'])) ?>
                                        </small>
                                        <?php if($teknisi['last_assignment_date']): ?>
                                        <br>
                                        <small>
                                            <i class="fas fa-clock"></i>
                                            Tugas Terakhir: <?= date('d M Y', strtotime($teknisi['last_assignment_date'])) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="monitor-teknisi-stats">
                                    <div class="monitor-stat-item">
                                        <div class="monitor-stat-item-number stat-total">
                                            <?= $teknisi['total_assigned'] ?>
                                        </div>
                                        <div class="monitor-stat-item-label">Total Ditugaskan</div>
                                    </div>
                                    <div class="monitor-stat-item">
                                        <div class="monitor-stat-item-number stat-progress">
                                            <?= $teknisi['in_progress'] ?>
                                        </div>
                                        <div class="monitor-stat-item-label">Sedang Dikerjakan</div>
                                    </div>
                                    <div class="monitor-stat-item">
                                        <div class="monitor-stat-item-number stat-completed">
                                            <?= $teknisi['completed'] ?>
                                        </div>
                                        <div class="monitor-stat-item-label">Selesai</div>
                                    </div>
                                    <div class="monitor-stat-item">
                                        <div class="monitor-stat-item-number stat-pending">
                                            <?= $teknisi['not_started'] ?>
                                        </div>
                                        <div class="monitor-stat-item-label">Belum Dikerjakan</div>
                                    </div>
                                    <div class="monitor-stat-item">
                                        <div class="monitor-stat-item-number stat-avg">
                                            <?= number_format($teknisi['avg_completion_days'] ?? 0, 1) ?>
                                        </div>
                                        <div class="monitor-stat-item-label">Rata-rata Hari</div>
                                    </div>
                                </div>
                                
                                <?php if($teknisi['total_assigned'] > 0): ?>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small>Tingkat Penyelesaian</small>
                                        <small><?= number_format($completion_rate, 1) ?>%</small>
                                    </div>
                                    <div class="monitor-progress">
                                        <div class="monitor-progress-bar progress-completed" 
                                             style="width: <?= $completion_rate ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada data teknisi yang ditemukan untuk periode yang dipilih.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="monitor-card">
                <div class="monitor-card-header">
                    <h5 class="monitor-card-title">
                        <i class="fas fa-history"></i>
                        Aktivitas Terkini
                    </h5>
                </div>
                <div class="monitor-card-body">
                    <?php if(count($recent_activities) > 0): ?>
                        <?php foreach($recent_activities as $activity): ?>
                            <div class="monitor-activity-item <?= $activity['activity_type'] ?>">
                                <img src="../../uploads/users/<?= htmlspecialchars($activity['foto']) ?>" 
                                     alt="<?= htmlspecialchars($activity['teknisi_nama']) ?>" 
                                     class="monitor-activity-avatar"
                                     onerror="this.src='../../uploads/default.svg'">
                                <div class="monitor-activity-content">
                                    <div class="monitor-activity-title">
                                        <?= htmlspecialchars($activity['teknisi_nama']) ?>
                                        <?php if($activity['activity_type'] == 'completed'): ?>
                                            <span class="badge bg-success ms-2">Selesai</span>
                                        <?php elseif($activity['activity_type'] == 'working'): ?>
                                            <span class="badge bg-warning ms-2">Sedang Dikerjakan</span>
                                        <?php else: ?>
                                            <span class="badge bg-info ms-2">Ditugaskan</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="monitor-activity-desc">
                                        Tiket #<?= $activity['id_tiket'] ?> - <?= htmlspecialchars($activity['jenis_gangguan']) ?>
                                    </div>
                                    <div class="monitor-activity-time">
                                        <?php if($activity['activity_type'] == 'completed' && $activity['selesai_pada']): ?>
                                            Diselesaikan: <?= date('d M Y H:i', strtotime($activity['selesai_pada'])) ?>
                                        <?php else: ?>
                                            Ditugaskan: <?= date('d M Y H:i', strtotime($activity['assigned_date'])) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Tidak ada aktivitas terkini untuk periode yang dipilih.</p>
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
            // Auto-refresh functionality
            let autoRefreshInterval;
            const enableAutoRefresh = false; // Set to true if you want auto-refresh
            
            if (enableAutoRefresh) {
                autoRefreshInterval = setInterval(() => {
                    location.reload();
                }, 300000); // Refresh every 5 minutes
            }

            // Animate progress bars
            const progressBars = document.querySelectorAll('.monitor-progress-bar');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });

            // Animate stat numbers
            const statNumbers = document.querySelectorAll('.monitor-stat-number, .monitor-stat-item-number');
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

            // Cleanup on page unload
            window.addEventListener('beforeunload', function() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            });

            // Enhanced tooltips for better UX
            const statItems = document.querySelectorAll('.monitor-stat-item');
            statItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.05)';
                    this.style.boxShadow = '0 4px 15px rgba(227, 30, 36, 0.2)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
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
