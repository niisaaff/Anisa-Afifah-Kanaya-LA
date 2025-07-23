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
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$filter_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
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

// Get tickets data (tabel sesuai SQL)
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

// Get statistics (tabel sesuai SQL)
$stats_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total_open,
        COUNT(CASE WHEN MONTH(created_at) = ? AND YEAR(created_at) = ? THEN 1 END) as monthly_open
    FROM tiket 
    WHERE status = 'open'
");
$stats_query->execute([$filter_month, $filter_year]);
$stats = $stats_query->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>View Tiket - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --view-telkom-red: #E31E24;
            --view-telkom-dark-red: #B71C1C;
            --view-telkom-light-red: #FFEBEE;
            --view-telkom-gray: #F5F5F5;
            --view-telkom-dark-gray: #424242;
            --view-telkom-white: #FFFFFF;
            --view-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --view-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --view-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --view-border-radius: 12px;
            --view-border-radius-small: 8px;
            --view-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .view-main-content {
            padding: 110px 25px 25px;
            transition: var(--view-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .view-header-section {
            background: linear-gradient(135deg, var(--view-telkom-red) 0%, var(--view-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--view-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--view-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .view-header-section::before {
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

        .view-header-section::after {
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

        .view-header-content {
            position: relative;
            z-index: 2;
        }

        .view-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .view-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .view-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Statistics Cards */
        .view-stats-container {
            margin-bottom: 2rem;
        }

        .view-stat-card {
            background: var(--view-telkom-white);
            border-radius: var(--view-border-radius);
            padding: 1.5rem;
            box-shadow: var(--view-shadow-light);
            transition: var(--view-transition);
            border-left: 4px solid var(--view-telkom-red);
            text-align: center;
        }

        .view-stat-card:hover {
            box-shadow: var(--view-shadow-medium);
            transform: translateY(-2px);
        }

        .view-stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--view-telkom-red);
            margin-bottom: 0.5rem;
        }

        .view-stat-label {
            color: var(--view-telkom-dark-gray);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Card Styles */
        .view-card {
            background: var(--view-telkom-white);
            border: none;
            border-radius: var(--view-border-radius);
            box-shadow: var(--view-shadow-light);
            transition: var(--view-transition);
            overflow: hidden;
            border-left: 4px solid var(--view-telkom-red);
            margin-bottom: 2rem;
        }

        .view-card:hover {
            box-shadow: var(--view-shadow-medium);
            transform: translateY(-2px);
        }

        .view-card-header {
            background: linear-gradient(135deg, var(--view-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--view-telkom-red);
            padding: 1.5rem;
            border-radius: var(--view-border-radius) var(--view-border-radius) 0 0 !important;
        }

        .view-card-title {
            color: var(--view-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-card-body {
            padding: 2rem;
        }

        /* Filter Section */
        .view-filter-section {
            background: var(--view-telkom-white);
            border-radius: var(--view-border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--view-shadow-light);
            border-left: 4px solid var(--view-telkom-red);
        }

        .view-filter-title {
            color: var(--view-telkom-red);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Form Styles */
        .view-form-group {
            margin-bottom: 1rem;
        }

        .view-form-label {
            font-weight: 500;
            color: var(--view-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .view-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--view-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--view-transition);
            background: #fafafa;
            width: 100%;
        }

        .view-form-control:focus {
            border-color: var(--view-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        /* Button Styles */
        .view-btn {
            border-radius: var(--view-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--view-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            min-width: 120px;
            justify-content: center;
        }

        .view-btn-primary {
            background: linear-gradient(135deg, var(--view-telkom-red) 0%, var(--view-telkom-dark-red) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.3);
        }

        .view-btn-primary:hover {
            background: linear-gradient(135deg, var(--view-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .view-btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .view-btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, #17a2b8 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .view-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .view-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .view-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-width: 100px;
            font-weight: 600;
        }

        /* Action Button Container */
        .view-action-container {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Improved Action Buttons */
        .view-btn-action {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            min-width: 110px;
            justify-content: center;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
        }

        .view-btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .view-btn-action:hover::before {
            left: 100%;
        }

        .view-btn-detail {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-left: 4px solid #117a8b;
        }

        .view-btn-detail:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
            color: white;
        }

        /* Icon Animation */
        .view-btn-action i {
            transition: transform 0.3s ease;
        }

        .view-btn-action:hover i {
            transform: scale(1.1) rotate(5deg);
        }

        /* Table Styles */
        .view-table {
            margin-bottom: 0;
            background: white;
            border-radius: var(--view-border-radius-small);
            overflow: hidden;
            box-shadow: var(--view-shadow-light);
        }

        .view-table thead th {
            background: linear-gradient(135deg, var(--view-telkom-red) 0%, var(--view-telkom-dark-red) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            font-size: 0.9rem;
        }

        .view-table tbody tr {
            transition: var(--view-transition);
        }

        .view-table tbody tr:hover {
            background: var(--view-telkom-light-red);
        }

        .view-table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        /* Badge Styles */
        .view-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .view-badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #856404;
        }

        /* No Tickets Message */
        .view-no-tickets-message {
            padding: 3rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--view-border-radius);
            margin-top: 1.5rem;
            box-shadow: var(--view-shadow-light);
        }

        .view-no-tickets-icon {
            font-size: 4rem;
            color: #d1d1d1;
            margin-bottom: 1rem;
        }

        .view-no-tickets-title {
            color: var(--view-telkom-dark-gray);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .view-no-tickets-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .view-main-content {
                padding: 110px 20px 25px;
            }
            
            .view-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .view-main-content {
                padding: 110px 15px 25px;
            }
            
            .view-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .view-header-title {
                font-size: 1.8rem;
            }
            
            .view-header-subtitle {
                font-size: 1rem;
            }
            
            .view-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .view-main-content {
                padding: 110px 10px 25px;
            }
            
            .view-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .view-header-title {
                font-size: 1.5rem;
            }
            
            .view-card-body {
                padding: 1rem;
            }

            .view-table {
                font-size: 0.85rem;
            }

            .view-btn-action {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
                min-width: 90px;
            }

            .view-action-container {
                flex-direction: column;
                gap: 0.3rem;
            }
        }

        @media (max-width: 576px) {
            .view-header-title {
                font-size: 1.3rem;
            }
            
            .view-card-body {
                padding: 0.75rem;
            }
            
            .view-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .view-btn-action {
                padding: 0.5rem 0.8rem;
                font-size: 0.7rem;
                min-width: 85px;
                gap: 0.4rem;
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
        
        <div class="view-main-content">
            <!-- Header Section -->
            <div class="view-header-section">
                <div class="view-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="view-header-content">
                    <h1 class="view-header-title">
                        <i class="fas fa-eye me-3"></i>
                        View Tiket Belum Ditugaskan
                    </h1>
                    <p class="view-header-subtitle">
                        Monitor dan kelola tiket yang belum mendapat penugasan teknisi - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Statistics Section -->
            <div class="view-stats-container">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="view-stat-card">
                            <div class="view-stat-number"><?= $stats['total_open'] ?></div>
                            <div class="view-stat-label">
                                <i class="fas fa-ticket-alt me-1"></i>
                                Total Tiket Belum Ditugaskan
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="view-stat-card">
                            <div class="view-stat-number"><?= $stats['monthly_open'] ?></div>
                            <div class="view-stat-label">
                                <i class="fas fa-calendar me-1"></i>
                                Tiket Bulan <?= date('F Y', mktime(0, 0, 0, $filter_month, 1, $filter_year)) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="view-filter-section">
                <h5 class="view-filter-title">
                    <i class="fas fa-filter"></i>
                    Filter & Export Data
                </h5>
                <form method="GET" action="">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="view-form-label">
                                <i class="fas fa-calendar-alt me-1"></i>Bulan
                            </label>
                            <select class="view-form-control" id="month" name="month">
                                <option value="">Semua Bulan</option>
                                <?php for($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= sprintf('%02d', $m) ?>" <?= $filter_month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="view-form-label">
                                <i class="fas fa-calendar me-1"></i>Tahun
                            </label>
                            <select class="view-form-control" id="year" name="year">
                                <option value="">Semua Tahun</option>
                                <?php for($y = date('Y'); $y >= 2020; $y--): ?>
                                    <option value="<?= $y ?>" <?= $filter_year == $y ? 'selected' : '' ?>>
                                        <?= $y ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="view-form-label">
                                <i class="fas fa-search me-1"></i>Pencarian
                            </label>
                            <input type="text" class="view-form-control" id="search" name="search" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Cari berdasarkan jenis gangguan, lokasi, atau pembuat...">
                        </div>
                        <div class="col-md-2">
                            <label class="view-form-label">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="view-btn view-btn-primary view-btn-sm">
                                    <i class="fas fa-search"></i>
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="mt-3">
                    <a href="export_tickets.php?month=<?= $filter_month ?>&year=<?= $filter_year ?>&search=<?= urlencode($search) ?>" 
                       class="view-btn view-btn-success view-btn-sm" target="_blank">
                        <i class="fas fa-file-excel"></i>
                        Export Excel
                    </a>
                    <a href="?" class="view-btn view-btn-info view-btn-sm">
                        <i class="fas fa-refresh"></i>
                        Reset Filter
                    </a>
                </div>
            </div>
            
            <!-- Tickets Table -->
            <div class="view-card">
                <div class="view-card-header">
                    <h5 class="view-card-title">
                        <i class="fas fa-list"></i>
                        Daftar Tiket Belum Ditugaskan
                        <span class="badge bg-warning ms-2"><?= count($tiket_list) ?> Tiket</span>
                    </h5>
                </div>
                <div class="view-card-body">
                    <?php if(count($tiket_list) > 0): ?>
                        <div class="table-responsive">
                            <table class="view-table table" id="tiketTable">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                        <th><i class="fas fa-tools me-1"></i>Jenis Gangguan</th>
                                        <th><i class="fas fa-map-marker-alt me-1"></i>Lokasi</th>
                                        <th><i class="fas fa-align-left me-1"></i>Deskripsi</th>
                                        <th><i class="fas fa-user me-1"></i>Dibuat Oleh</th>
                                        <th><i class="fas fa-calendar me-1"></i>Tanggal</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                        <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tiket_list as $tiket): ?>
                                        <tr>
                                            <td><strong>#<?= $tiket['id_tiket'] ?></strong></td>
                                            <td><?= htmlspecialchars($tiket['jenis_gangguan']) ?></td>
                                            <td><?= mb_strimwidth(htmlspecialchars($tiket['alamat']), 0, 50, "...") ?></td>
                                            <td><?= mb_strimwidth(htmlspecialchars($tiket['deskripsi']), 0, 60, "...") ?></td>
                                            <td><?= htmlspecialchars($tiket['created_by']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($tiket['created_at'])) ?></td>
                                            <td>
                                                <span class="view-badge view-badge-pending">
                                                    <i class="fas fa-clock"></i>
                                                    Belum Ditugaskan
                                                </span>
                                            </td>
                                            <td>
                                                <div class="view-action-container">
                                                    <a href="detail_tiket.php?id=<?= $tiket['id_tiket'] ?>" 
                                                       class="view-btn-action view-btn-detail">
                                                        <i class="fas fa-eye"></i>
                                                        <span>Detail</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- No Tickets Message -->
                        <div class="view-no-tickets-message">
                            <div class="view-no-tickets-icon">
                                <i class="fas fa-ticket-alt"></i>
                            </div>
                            <h5 class="view-no-tickets-title">Tidak ada tiket yang ditemukan</h5>
                            <p class="view-no-tickets-subtitle">
                                <?php if(!empty($search) || !empty($filter_month)): ?>
                                    Tidak ada tiket yang sesuai dengan filter yang diterapkan.
                                <?php else: ?>
                                    Semua tiket sudah ditugaskan ke teknisi atau belum ada tiket yang dibuat.
                                <?php endif; ?>
                            </p>
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
            // Enhanced table search functionality
            const searchInput = document.getElementById('search');
            const tiketTable = document.getElementById('tiketTable');
            
            if (searchInput && tiketTable) {
                // Real-time search as user types
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        const value = this.value.toLowerCase();
                        const rows = tiketTable.querySelectorAll('tbody tr');
                        
                        rows.forEach(function(row) {
                            const text = row.textContent.toLowerCase();
                            const isVisible = text.indexOf(value) > -1;
                            row.style.display = isVisible ? '' : 'none';
                            
                            // Add highlight effect
                            if (isVisible && value.length > 0) {
                                row.style.backgroundColor = 'rgba(227, 30, 36, 0.05)';
                            } else {
                                row.style.backgroundColor = '';
                            }
                        });
                        
                        // Update visible count
                        const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
                        updateVisibleCount(visibleRows.length);
                    }, 300);
                });
            }

            // Update visible count function
            function updateVisibleCount(count) {
                const badge = document.querySelector('.badge.bg-warning');
                if (badge) {
                    badge.textContent = count + ' Tiket';
                }
            }

            // Export confirmation
            const exportBtn = document.querySelector('a[href*="export_tickets.php"]');
            if (exportBtn) {
                exportBtn.addEventListener('click', function(e) {
                    const count = document.querySelectorAll('#tiketTable tbody tr').length;
                    if (count === 0) {
                        e.preventDefault();
                        alert('Tidak ada data untuk diekspor!');
                        return false;
                    }
                    
                    // Show loading indicator
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengunduh...';
                    this.style.pointerEvents = 'none';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.pointerEvents = 'auto';
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>
