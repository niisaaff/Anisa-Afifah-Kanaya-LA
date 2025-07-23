<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor';
$userRole = 'supervisor';

// Statistik tiket
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN t.status = 'open' THEN 1 END) as open_tickets,
        COUNT(CASE WHEN t.status = 'on progress' THEN 1 END) as progress_tickets,
        COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as completed_tickets,
        COUNT(*) as total_tickets
    FROM tiket t
");
$stmt->execute();
$ticket_stats = $stmt->fetch();

// Pending approvals
$stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM laporan_pending WHERE status_approval = 'pending'");
$stmt->execute();
$pending_approvals = $stmt->fetch()['pending_count'];

// Kinerja teknisi
$stmt = $pdo->prepare("
    SELECT 
        tk.nama_lengkap,
        tk.id_teknisi,
        COUNT(DISTINCT p.id_tiket) as total_assigned,
        COUNT(DISTINCT CASE WHEN t.status = 'selesai' THEN p.id_tiket END) as completed,
        COUNT(DISTINCT l.id_laporan) as reports_made,
        ROUND(
            (COUNT(DISTINCT CASE WHEN t.status = 'selesai' THEN p.id_tiket END) * 100.0 /
            NULLIF(COUNT(DISTINCT p.id_tiket), 0)), 1
        ) as completion_rate
    FROM teknisi tk
    LEFT JOIN penugasan p ON tk.id_teknisi = p.id_teknisi
    LEFT JOIN tiket t ON p.id_tiket = t.id_tiket
    LEFT JOIN laporan l ON t.id_tiket = l.id_tiket AND l.id_teknisi = tk.id_teknisi
    GROUP BY tk.id_teknisi, tk.nama_lengkap
    ORDER BY completion_rate DESC
");
$stmt->execute();
$technician_performance = $stmt->fetchAll();

// Lokasi dengan masalah terbanyak
$stmt = $pdo->prepare("
    SELECT 
        l.alamat,
        l.latitude,
        l.longitude,
        COUNT(t.id_tiket) as issue_count,
        COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as resolved_count
    FROM lokasi l
    LEFT JOIN tiket t ON l.id_lokasi = t.id_lokasi
    GROUP BY l.id_lokasi, l.alamat, l.latitude, l.longitude
    HAVING issue_count > 0
    ORDER BY issue_count DESC
    LIMIT 10
");
$stmt->execute();
$problem_locations = $stmt->fetchAll();

// Data lokasi dan teknisi untuk map
$stmt = $pdo->prepare("
    SELECT 
        t.id_tiket,
        t.jenis_gangguan,
        t.deskripsi,
        t.status,
        t.created_at,
        l.alamat,
        l.latitude,
        l.longitude,
        tk.nama_lengkap as teknisi_nama,
        lap.jenis_perbaikan
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN teknisi tk ON p.id_teknisi = tk.id_teknisi
    LEFT JOIN laporan lap ON t.id_tiket = lap.id_tiket
    WHERE l.latitude != 0 AND l.longitude != 0
    ORDER BY t.created_at DESC
");
$stmt->execute();
$map_data = $stmt->fetchAll();

// Aktivitas terakhir 7 hari
$stmt = $pdo->prepare("
    SELECT 
        'ticket_created' as activity_type,
        t.id_tiket as item_id,
        t.jenis_gangguan as title,
        l.alamat as description,
        t.created_at as activity_time,
        'Tiket Baru' as status_text,
        'danger' as status_color
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'report_completed' as activity_type,
        lap.id_laporan as item_id,
        CONCAT('Laporan #', t.id_tiket) as title,
        CONCAT('Perbaikan ', lap.jenis_perbaikan) as description,
        lap.selesai_pada as activity_time,
        'Selesai' as status_text,
        'success' as status_color
    FROM laporan lap
    JOIN tiket t ON lap.id_tiket = t.id_tiket
    WHERE lap.selesai_pada >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY activity_time DESC
    LIMIT 20
");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Statistik jenis gangguan
$stmt = $pdo->prepare("
    SELECT 
        jenis_gangguan,
        COUNT(*) as count,
        COUNT(CASE WHEN status = 'selesai' THEN 1 END) as resolved
    FROM tiket
    GROUP BY jenis_gangguan
    ORDER BY count DESC
");
$stmt->execute();
$issue_types = $stmt->fetchAll();
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Supervisor - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --supervisor-telkom-red: #E31E24;
            --supervisor-telkom-dark-red: #B71C1C;
            --supervisor-telkom-light-red: #FFEBEE;
            --supervisor-telkom-gray: #F5F5F5;
            --supervisor-telkom-dark-gray: #424242;
            --supervisor-telkom-white: #FFFFFF;
            --supervisor-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --supervisor-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --supervisor-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --supervisor-border-radius: 12px;
            --supervisor-border-radius-small: 8px;
            --supervisor-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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

        .supervisor-main-content {
            padding: 110px 25px 25px;
            transition: var(--supervisor-transition);
            min-height: calc(100vh - 45px);
        }

        .supervisor-header-section {
            background: linear-gradient(135deg, var(--supervisor-telkom-red) 0%, var(--supervisor-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--supervisor-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--supervisor-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .supervisor-header-section::before {
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

        .supervisor-header-content {
            position: relative;
            z-index: 2;
        }

        .supervisor-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .supervisor-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .supervisor-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        .supervisor-stats-card {
            background: var(--supervisor-telkom-white);
            border: none;
            border-radius: var(--supervisor-border-radius);
            box-shadow: var(--supervisor-shadow-light);
            transition: var(--supervisor-transition);
            overflow: hidden;
            margin-bottom: 2rem;
            height: 100%;
        }

        .supervisor-stats-card:hover {
            box-shadow: var(--supervisor-shadow-medium);
            transform: translateY(-2px);
        }

        .supervisor-stats-card-body {
            padding: 1.5rem;
            text-align: center;
        }

        .supervisor-stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .supervisor-stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .supervisor-stats-label {
            color: #6c757d;
            font-weight: 500;
        }

        .supervisor-stats-open {
            border-left: 4px solid #dc3545;
        }

        .supervisor-stats-open .supervisor-stats-icon {
            color: #dc3545;
        }

        .supervisor-stats-progress {
            border-left: 4px solid #ffc107;
        }

        .supervisor-stats-progress .supervisor-stats-icon {
            color: #ffc107;
        }

        .supervisor-stats-completed {
            border-left: 4px solid #28a745;
        }

        .supervisor-stats-completed .supervisor-stats-icon {
            color: #28a745;
        }

        .supervisor-stats-pending {
            border-left: 4px solid #17a2b8;
        }

        .supervisor-stats-pending .supervisor-stats-icon {
            color: #17a2b8;
        }

        .supervisor-card {
            background: var(--supervisor-telkom-white);
            border: none;
            border-radius: var(--supervisor-border-radius);
            box-shadow: var(--supervisor-shadow-light);
            transition: var(--supervisor-transition);
            overflow: hidden;
            margin-bottom: 2rem;
            height: 100%;
        }

        .supervisor-card:hover {
            box-shadow: var(--supervisor-shadow-medium);
        }

        .supervisor-card-header {
            background: linear-gradient(135deg, var(--supervisor-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--supervisor-telkom-red);
            padding: 1rem 1.5rem;
        }

        .supervisor-card-title {
            color: var(--supervisor-telkom-red);
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .supervisor-card-body {
            padding: 1.5rem;
            height: calc(100% - 60px);
            overflow-y: auto;
        }

        #map {
            height: 400px;
            border-radius: var(--supervisor-border-radius-small);
            box-shadow: var(--supervisor-shadow-light);
        }

        .supervisor-map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            padding: 0.5rem;
            border-radius: var(--supervisor-border-radius-small);
            box-shadow: var(--supervisor-shadow-light);
        }

        .supervisor-map-legend {
            position: absolute;
            bottom: 10px;
            left: 10px;
            z-index: 1000;
            background: white;
            padding: 1rem;
            border-radius: var(--supervisor-border-radius-small);
            box-shadow: var(--supervisor-shadow-light);
        }

        .supervisor-legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .supervisor-legend-item:last-child {
            margin-bottom: 0;
        }

        .supervisor-legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .supervisor-performance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: var(--supervisor-border-radius-small);
            margin-bottom: 0.75rem;
            border-left: 3px solid var(--supervisor-telkom-red);
        }

        .supervisor-performance-name {
            font-weight: 500;
            color: var(--supervisor-telkom-dark-gray);
        }

        .supervisor-performance-stats {
            text-align: right;
            font-size: 0.9rem;
        }

        .supervisor-performance-rate {
            font-weight: 600;
            color: var(--supervisor-telkom-red);
        }

        .supervisor-activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            border-bottom: 1px solid #e9ecef;
        }

        .supervisor-activity-item:last-child {
            border-bottom: none;
        }

        .supervisor-activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .supervisor-activity-content {
            flex: 1;
        }

        .supervisor-activity-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .supervisor-activity-description {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .supervisor-activity-time {
            color: #6c757d;
            font-size: 0.8rem;
            text-align: right;
        }

        .supervisor-location-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: var(--supervisor-border-radius-small);
            margin-bottom: 0.75rem;
            border-left: 3px solid #ffc107;
        }

        .supervisor-location-info {
            flex: 1;
        }

        .supervisor-location-address {
            font-weight: 500;
            color: var(--supervisor-telkom-dark-gray);
            margin-bottom: 0.25rem;
        }

        .supervisor-location-stats {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .supervisor-location-count {
            background: #ffc107;
            color: #212529;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .supervisor-chart-container {
            position: relative;
            height: 300px;
        }

        .supervisor-btn {
            border-radius: var(--supervisor-border-radius-small);
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: var(--supervisor-transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .supervisor-btn-primary {
            background: linear-gradient(135deg, var(--supervisor-telkom-red) 0%, var(--supervisor-telkom-dark-red) 100%);
            color: white;
        }

        .supervisor-btn-primary:hover {
            background: linear-gradient(135deg, var(--supervisor-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--supervisor-shadow-medium);
            color: white;
        }

        @media (max-width: 768px) {
            .supervisor-main-content {
                padding: 110px 15px 25px;
            }
            
            .supervisor-header-section {
                padding: 1.5rem;
            }
            
            .supervisor-header-title {
                font-size: 1.8rem;
            }
            
            .supervisor-card-body {
                padding: 1rem;
            }
            
            #map {
                height: 300px;
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
        
        <div class="supervisor-main-content">
            <!-- Header Section -->
            <div class="supervisor-header-section">
                <div class="supervisor-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="supervisor-header-content">
                    <h1 class="supervisor-header-title">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Dashboard Supervisor
                    </h1>
                    <p class="supervisor-header-subtitle">
                        Selamat datang, <?= htmlspecialchars($username) ?>! Pantau kinerja teknisi dan status perbaikan secara real-time
                    </p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="supervisor-stats-card supervisor-stats-open">
                        <div class="supervisor-stats-card-body">
                            <div class="supervisor-stats-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="supervisor-stats-number"><?= $ticket_stats['open_tickets'] ?></div>
                            <div class="supervisor-stats-label">Tiket Terbuka</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="supervisor-stats-card supervisor-stats-progress">
                        <div class="supervisor-stats-card-body">
                            <div class="supervisor-stats-icon">
                                <i class="fas fa-cogs"></i>
                            </div>
                            <div class="supervisor-stats-number"><?= $ticket_stats['progress_tickets'] ?></div>
                            <div class="supervisor-stats-label">Sedang Dikerjakan</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="supervisor-stats-card supervisor-stats-completed">
                        <div class="supervisor-stats-card-body">
                            <div class="supervisor-stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="supervisor-stats-number"><?= $ticket_stats['completed_tickets'] ?></div>
                            <div class="supervisor-stats-label">Selesai</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="supervisor-stats-card supervisor-stats-pending">
                        <div class="supervisor-stats-card-body">
                            <div class="supervisor-stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="supervisor-stats-number"><?= $pending_approvals ?></div>
                            <div class="supervisor-stats-label">Menunggu Approval</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Maps and Analytics Row -->
            <div class="row g-4 mb-4">
                <!-- Map Section -->
                <div class="col-lg-8">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-map-marked-alt"></i>
                                Peta Lokasi Gangguan
                            </h5>
                        </div>
                        <div class="supervisor-card-body p-0 position-relative">
                            <div id="map"></div>
                            <div class="supervisor-map-legend">
                                <div class="supervisor-legend-item">
                                    <div class="supervisor-legend-color" style="background-color: #dc3545;"></div>
                                    <span>Tiket Terbuka</span>
                                </div>
                                <div class="supervisor-legend-item">
                                    <div class="supervisor-legend-color" style="background-color: #ffc107;"></div>
                                    <span>Sedang Dikerjakan</span>
                                </div>
                                <div class="supervisor-legend-item">
                                    <div class="supervisor-legend-color" style="background-color: #28a745;"></div>
                                    <span>Selesai</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Issue Types Chart -->
                <div class="col-lg-4">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-chart-pie"></i>
                                Jenis Gangguan
                            </h5>
                        </div>
                        <div class="supervisor-card-body">
                            <div class="supervisor-chart-container">
                                <canvas id="issueTypesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance and Activities Row -->
            <div class="row g-4 mb-4">
                <!-- Technician Performance -->
                <div class="col-lg-4">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-user-cog"></i>
                                Kinerja Teknisi
                            </h5>
                        </div>
                        <div class="supervisor-card-body">
                            <?php foreach ($technician_performance as $tech): ?>
                                <div class="supervisor-performance-item">
                                    <div>
                                        <div class="supervisor-performance-name">
                                            <?= htmlspecialchars($tech['nama_lengkap']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= $tech['completed'] ?>/<?= $tech['total_assigned'] ?> tiket selesai
                                        </small>
                                    </div>
                                    <div class="supervisor-performance-stats">
                                        <div class="supervisor-performance-rate">
                                            <?= $tech['completion_rate'] ?? 0 ?>%
                                        </div>
                                        <small class="text-muted">
                                            <?= $tech['reports_made'] ?> laporan
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($technician_performance)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <p>Belum ada data kinerja teknisi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Problem Locations -->
                <div class="col-lg-4">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-map-pin"></i>
                                Lokasi Gangguan Terbanyak
                            </h5>
                        </div>
                        <div class="supervisor-card-body">
                            <?php foreach (array_slice($problem_locations, 0, 5) as $location): ?>
                                <div class="supervisor-location-item">
                                    <div class="supervisor-location-info">
                                        <div class="supervisor-location-address">
                                            <?= htmlspecialchars(substr($location['alamat'], 0, 50)) ?>...
                                        </div>
                                        <div class="supervisor-location-stats">
                                            <?= $location['resolved_count'] ?>/<?= $location['issue_count'] ?> diselesaikan
                                        </div>
                                    </div>
                                    <div class="supervisor-location-count">
                                        <?= $location['issue_count'] ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($problem_locations)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                                    <p>Belum ada data lokasi gangguan</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="col-lg-4">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-history"></i>
                                Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="supervisor-card-body">
                            <?php foreach (array_slice($recent_activities, 0, 8) as $activity): ?>
                                <div class="supervisor-activity-item">
                                    <div class="supervisor-activity-icon bg-<?= $activity['status_color'] ?>">
                                        <i class="fas <?= $activity['activity_type'] == 'ticket_created' ? 'fa-plus' : 'fa-check' ?>"></i>
                                    </div>
                                    <div class="supervisor-activity-content">
                                        <div class="supervisor-activity-title">
                                            <?= htmlspecialchars($activity['title']) ?>
                                        </div>
                                        <div class="supervisor-activity-description">
                                            <?= htmlspecialchars($activity['description']) ?>
                                        </div>
                                    </div>
                                    <div class="supervisor-activity-time">
                                        <?= date('d M H:i', strtotime($activity['activity_time'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($recent_activities)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <p>Belum ada aktivitas terbaru</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-4">
                <div class="col-12">
                    <div class="supervisor-card">
                        <div class="supervisor-card-header">
                            <h5 class="supervisor-card-title">
                                <i class="fas fa-bolt"></i>
                                Aksi Cepat
                            </h5>
                        </div>
                        <div class="supervisor-card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="approve_task.php" class="supervisor-btn supervisor-btn-primary w-100">
                                        <i class="fas fa-check-circle"></i>
                                        Approve Laporan
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="report.php" class="supervisor-btn supervisor-btn-primary w-100">
                                        <i class="fas fa-file-alt"></i>
                                        Lihat Laporan
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="monitor_teknisi.php" class="supervisor-btn supervisor-btn-primary w-100">
                                        <i class="fas fa-users-cog"></i>
                                        Monitor Teknisi
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="progress_report.php" class="supervisor-btn supervisor-btn-primary w-100">
                                        <i class="fas fa-chart-line"></i>
                                        Progress Report
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize Map
        const map = L.map('map').setView([-2.9760, 104.7458], 12);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Map data from PHP
        const mapData = <?= json_encode($map_data) ?>;

        // Add markers to map
        mapData.forEach(function(item) {
            if (item.latitude && item.longitude) {
                let color = '#dc3545'; // red for open
                if (item.status === 'on progress') color = '#ffc107'; // yellow
                if (item.status === 'selesai') color = '#28a745'; // green

                const marker = L.circleMarker([parseFloat(item.latitude), parseFloat(item.longitude)], {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.7,
                    radius: 8
                }).addTo(map);

                const popupContent = `
                    <div style="min-width: 200px;">
                        <h6 style="margin-bottom: 10px; color: #E31E24;">
                            <i class="fas fa-ticket-alt"></i> Tiket #${item.id_tiket}
                        </h6>
                        <p style="margin-bottom: 5px;"><strong>Jenis:</strong> ${item.jenis_gangguan}</p>
                        <p style="margin-bottom: 5px;"><strong>Status:</strong> 
                            <span style="color: ${color}; font-weight: bold;">${item.status}</span>
                        </p>
                        <p style="margin-bottom: 5px;"><strong>Teknisi:</strong> ${item.teknisi_nama || 'Belum ditugaskan'}</p>
                        <p style="margin-bottom: 5px;"><strong>Lokasi:</strong> ${item.alamat}</p>
                        <p style="margin-bottom: 5px;"><strong>Dibuat:</strong> ${new Date(item.created_at).toLocaleDateString('id-ID')}</p>
                        ${item.jenis_perbaikan ? `<p style="margin-bottom: 0;"><strong>Perbaikan:</strong> ${item.jenis_perbaikan}</p>` : ''}
                    </div>
                `;

                marker.bindPopup(popupContent);
            }
        });

        // Issue Types Chart
        const issueTypesData = <?= json_encode($issue_types) ?>;
        const ctx = document.getElementById('issueTypesChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: issueTypesData.map(item => item.jenis_gangguan),
                datasets: [{
                    data: issueTypesData.map(item => item.count),
                    backgroundColor: [
                        '#E31E24',
                        '#ffc107',
                        '#28a745',
                        '#17a2b8',
                        '#6f42c1',
                        '#fd7e14'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            usePointStyle: true,
                            font: {
                                size: 12
                            }
                        }
                    }
                }
            }
        });

        // Auto refresh every 5 minutes
        setInterval(() => {
            location.reload();
        }, 300000);

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
