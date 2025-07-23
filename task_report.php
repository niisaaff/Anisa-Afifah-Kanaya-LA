<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('teknisi');

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Teknisi';
$userRole = 'teknisi';

// Default filter values
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Prepare the SQL query with filters
$sql = "
    SELECT t.*, l.alamat, l.latitude, l.longitude,
           r.id_laporan as laporan_id, r.jenis_perbaikan, r.selesai_pada, r.dokumentasi, r.catatan,
           p.created_at as tanggal_penugasan
    FROM tiket t
    JOIN penugasan p ON t.id_tiket = p.id_tiket
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    LEFT JOIN laporan r ON t.id_tiket = r.id_tiket
    WHERE p.id_teknisi = ?
";

// Add status filter if selected
if ($status !== 'all') {
    if ($status === 'completed') {
        $sql .= " AND t.status = 'selesai'";
    } elseif ($status === 'in_progress') {
        $sql .= " AND t.status = 'on progress'";
    } elseif ($status === 'open') {
        $sql .= " AND t.status = 'open'";
    }
}

// Add month and year filter
$sql .= " AND MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?";

// Add order by
$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id'], $month, $year]);
$tasks = $stmt->fetchAll();

// Get count of tasks by status
$stmt_count = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN t.status = 'selesai' THEN 1 END) as completed,
        COUNT(CASE WHEN t.status = 'on progress' THEN 1 END) as in_progress,
        COUNT(CASE WHEN t.status = 'open' THEN 1 END) as open
    FROM tiket t
    JOIN penugasan p ON t.id_tiket = p.id_tiket
    WHERE p.id_teknisi = ? AND MONTH(t.created_at) = ? AND YEAR(t.created_at) = ?
");
$stmt_count->execute([$_SESSION['user_id'], $month, $year]);
$task_count = $stmt_count->fetch();

// Get available years for the dropdown
$stmt_years = $pdo->prepare("
    SELECT DISTINCT YEAR(t.created_at) as year
    FROM tiket t
    JOIN penugasan p ON t.id_tiket = p.id_tiket
    WHERE p.id_teknisi = ?
    ORDER BY year DESC
");
$stmt_years->execute([$_SESSION['user_id']]);
$available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);

// If no years found, add current year
if (empty($available_years)) {
    $available_years = [date('Y')];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Laporan Tugas - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
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
        .report-main-content {
            padding: 110px 25px 25px;
            transition: var(--report-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
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

        .report-header-section::after {
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

        /* Card Styles */
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
            border-radius: var(--report-border-radius) var(--report-border-radius) 0 0 !important;
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

        /* Stats Cards */
        .report-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .report-stats-card {
            background: var(--report-telkom-white);
            border-radius: var(--report-border-radius);
            box-shadow: var(--report-shadow-light);
            padding: 1.5rem;
            transition: var(--report-transition);
            position: relative;
            overflow: hidden;
        }

        .report-stats-card:hover {
            box-shadow: var(--report-shadow-medium);
            transform: translateY(-2px);
        }

        .report-stats-card.completed {
            border-left: 4px solid #28a745;
        }

        .report-stats-card.in-progress {
            border-left: 4px solid #ffc107;
        }

        .report-stats-card.open {
            border-left: 4px solid #dc3545;
        }

        .report-stats-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2.5rem;
            opacity: 0.1;
        }

        .report-stats-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .report-stats-label {
            color: var(--report-telkom-dark-gray);
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Form Styles */
        .report-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--report-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--report-transition);
            background: #fafafa;
        }

        .report-form-control:focus {
            border-color: var(--report-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .report-form-label {
            font-weight: 500;
            color: var(--report-telkom-dark-gray);
            margin-bottom: 0.5rem;
        }

        /* Button Styles */
        .report-btn {
            border-radius: var(--report-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--report-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .report-btn-primary {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
        }

        .report-btn-primary:hover {
            background: linear-gradient(135deg, var(--report-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--report-shadow-medium);
            color: white;
        }

        .report-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .report-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            color: white;
        }

        .report-btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        /* Badge Styles */
        .report-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-badge-open {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .report-badge-on-progress {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .report-badge-selesai {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .report-badge-temporary {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
        }

        .report-badge-permanent {
            background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
            color: white;
        }

        .report-badge-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        /* Enhanced Table Responsive Styles */
        .report-table {
            border-radius: var(--report-border-radius-small);
            overflow: hidden;
            box-shadow: var(--report-shadow-light);
            width: 100% !important;
        }

        .report-table thead th {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .report-table tbody tr {
            transition: var(--report-transition);
        }

        .report-table tbody tr:hover {
            background-color: var(--report-telkom-light-red);
        }

        .report-table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f1f3f4;
        }

        /* Mobile Responsive Table */
        @media (max-width: 768px) {
            .table-responsive {
                border: none;
            }
            
            .report-table,
            .report-table thead,
            .report-table tbody,
            .report-table th,
            .report-table td,
            .report-table tr {
                display: block;
            }
            
            .report-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .report-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                padding: 10px;
                border-radius: var(--report-border-radius-small);
                background: white;
                box-shadow: var(--report-shadow-light);
            }
            
            .report-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding: 10px 10px 10px 50% !important;
                text-align: left;
            }
            
            .report-table td:before {
                content: attr(data-label) ": ";
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                color: var(--report-telkom-red);
            }
            
            .report-table td:last-child {
                border-bottom: 0;
            }
            
            .report-action-buttons {
                flex-direction: row;
                gap: 0.5rem;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
            
            .report-btn-sm {
                width: auto;
                margin-bottom: 0;
                padding: 0.3rem 0.6rem;
                font-size: 0.75rem;
            }
        }

        @media (max-width: 576px) {
            .report-table td {
                padding: 8px 8px 8px 45% !important;
                font-size: 0.85rem;
            }
            
            .report-table td:before {
                width: 40%;
                font-size: 0.8rem;
            }
            
            .report-action-buttons {
                flex-direction: column;
                gap: 0.25rem;
            }
            
            .report-btn-sm {
                width: 100%;
                justify-content: center;
                margin-bottom: 0.25rem;
            }
        }

        /* Alert Styles */
        .report-alert {
            border: none;
            border-radius: var(--report-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .report-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .report-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .report-alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Empty State */
        .report-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--report-border-radius);
            box-shadow: var(--report-shadow-light);
        }

        .report-empty-state i {
            font-size: 4rem;
            color: var(--report-telkom-red);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .report-empty-state h5 {
            color: var(--report-telkom-dark-gray);
            margin-bottom: 1rem;
        }

        .report-empty-state p {
            color: #6c757d;
            margin-bottom: 0;
        }

        /* Modal Styles */
        .report-modal-content {
            border: none;
            border-radius: var(--report-border-radius);
            box-shadow: var(--report-shadow-heavy);
        }

        .report-modal-header {
            background: linear-gradient(135deg, var(--report-telkom-red) 0%, var(--report-telkom-dark-red) 100%);
            color: white;
            border-radius: var(--report-border-radius) var(--report-border-radius) 0 0;
        }

        .report-modal-title {
            color: white;
            font-weight: 600;
        }

        .report-task-info-group {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--report-border-radius-small);
            border-left: 4px solid var(--report-telkom-red);
        }

        .report-task-info-label {
            font-weight: 600;
            color: var(--report-telkom-red);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-task-info-value {
            color: var(--report-telkom-dark-gray);
        }

        .report-task-images {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .report-task-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: var(--report-border-radius-small);
            border: 2px solid #dee2e6;
            cursor: pointer;
            transition: var(--report-transition);
        }

        .report-task-image:hover {
            transform: scale(1.05);
            box-shadow: var(--report-shadow-medium);
        }

        /* DataTables Responsive Enhancement */
        .dataTables_wrapper {
            width: 100%;
        }

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: center;
                margin-bottom: 1rem;
            }
            
            .dataTables_wrapper .dataTables_filter input {
                width: 100%;
                margin-left: 0;
                margin-top: 0.5rem;
            }
            
            .dataTables_wrapper .dataTables_paginate {
                text-align: center;
            }
            
            .dataTables_wrapper .dataTables_info {
                text-align: center;
                font-size: 0.85rem;
            }
        }

        /* DataTables Custom Styles Enhancement */
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: var(--report-border-radius-small);
            border: 1px solid #dee2e6;
            background: white;
            color: var(--report-telkom-dark-gray);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--report-telkom-red) !important;
            color: white !important;
            border-color: var(--report-telkom-red) !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: var(--report-telkom-dark-red) !important;
            color: white !important;
            border-color: var(--report-telkom-dark-red) !important;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e9ecef;
            border-radius: var(--report-border-radius-small);
            padding: 0.5rem 0.75rem;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--report-telkom-red);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #e9ecef;
            border-radius: var(--report-border-radius-small);
            padding: 0.25rem 0.5rem;
        }

        /* Action Buttons */
        .report-action-buttons {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .report-main-content {
                padding: 110px 20px 25px;
            }
            
            .report-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .report-main-content {
                padding: 110px 15px 25px;
            }
            
            .report-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .report-header-title {
                font-size: 1.8rem;
            }
            
            .report-header-subtitle {
                font-size: 1rem;
            }
            
            .report-card-body {
                padding: 1.5rem;
            }

            .report-stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .report-main-content {
                padding: 110px 10px 25px;
            }
            
            .report-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .report-header-title {
                font-size: 1.5rem;
            }
            
            .report-card-body {
                padding: 1rem;
            }

            .report-task-image {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 576px) {
            .report-header-title {
                font-size: 1.3rem;
            }
            
            .report-card-body {
                padding: 0.75rem;
            }
            
            .report-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .report-btn-sm {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }

            .report-stats-value {
                font-size: 2rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .report-btn:hover,
            .report-card:hover,
            .report-stats-card:hover,
            .report-task-image:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .report-header-section,
            .report-btn,
            .report-action-buttons {
                display: none !important;
            }
            
            .report-main-content {
                padding: 0;
            }
            
            .report-card {
                box-shadow: none;
                border: 1px solid #ddd;
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
            <!-- Header Section -->
            <div class="report-header-section">
                <div class="report-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="report-header-content">
                    <h1 class="report-header-title">
                        <i class="fas fa-clipboard-list me-3"></i>
                        Laporan Tugas
                    </h1>
                    <p class="report-header-subtitle">
                        Ringkasan tugas yang telah ditangani - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="report-alert report-alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="report-alert report-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="report-alert report-alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filter Card -->
            <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title">
                        <i class="fas fa-filter"></i>
                        Filter Laporan
                    </h5>
                </div>
                <div class="report-card-body">
                    <form action="" method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="report-form-label">
                                <i class="fas fa-calendar-day me-2"></i>Bulan
                            </label>
                            <select class="report-form-control" id="month" name="month">
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>
                                        <?= date('F', mktime(0, 0, 0, $i, 1)) ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="report-form-label">
                                <i class="fas fa-calendar-alt me-2"></i>Tahun
                            </label>
                            <select class="report-form-control" id="year" name="year">
                                <?php foreach($available_years as $y): ?>
                                    <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="report-form-label">
                                <i class="fas fa-flag me-2"></i>Status
                            </label>
                            <select class="report-form-control" id="status" name="status">
                                <option value="all" <?= $status == 'all' ? 'selected' : '' ?>>Semua</option>
                                <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Selesai</option>
                                <option value="in_progress" <?= $status == 'in_progress' ? 'selected' : '' ?>>On Progress</option>
                                <option value="open" <?= $status == 'open' ? 'selected' : '' ?>>Open</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="report-btn report-btn-primary w-100">
                                <i class="fas fa-filter"></i>Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="report-stats-grid">
                <div class="report-stats-card completed">
                    <i class="fas fa-check-circle report-stats-icon" style="color: #28a745;"></i>
                    <div class="report-stats-value" style="color: #28a745;"><?= $task_count['completed'] ?? 0 ?></div>
                    <div class="report-stats-label">Tugas Selesai</div>
                </div>
                <div class="report-stats-card in-progress">
                    <i class="fas fa-clock report-stats-icon" style="color: #ffc107;"></i>
                    <div class="report-stats-value" style="color: #ffc107;"><?= $task_count['in_progress'] ?? 0 ?></div>
                    <div class="report-stats-label">Tugas Dalam Proses</div>
                </div>
                <div class="report-stats-card open">
                    <i class="fas fa-exclamation-circle report-stats-icon" style="color: #dc3545;"></i>
                    <div class="report-stats-value" style="color: #dc3545;"><?= $task_count['open'] ?? 0 ?></div>
                    <div class="report-stats-label">Tugas Terbuka</div>
                </div>
            </div>
            
            <!-- Tasks Table -->
            <div class="report-card">
                <div class="report-card-header">
                    <h5 class="report-card-title">
                        <i class="fas fa-list"></i>
                        Daftar Tugas (<?= count($tasks) ?>)
                    </h5>
                </div>
                <div class="report-card-body">
                    <?php if(count($tasks) > 0): ?>
                        <div class="table-responsive">
                            <table id="taskTable" class="report-table table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID Tiket</th>
                                        <th>Jenis Gangguan</th>
                                        <th>Lokasi</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Status</th>
                                        <th>Jenis Perbaikan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($tasks as $task): ?>
                                        <tr>
                                            <td data-label="ID Tiket">
                                                <span class="fw-bold">#<?= $task['id_tiket'] ?></span>
                                            </td>
                                            <td data-label="Jenis Gangguan">
                                                <strong><?= htmlspecialchars($task['jenis_gangguan']) ?></strong>
                                            </td>
                                            <td data-label="Lokasi">
                                                <small class="text-muted">
                                                    <?= mb_strimwidth(htmlspecialchars($task['alamat']), 0, 40, "...") ?>
                                                </small>
                                            </td>
                                            <td data-label="Tanggal Dibuat">
                                                <small><?= date('d M Y H:i', strtotime($task['created_at'])) ?></small>
                                            </td>
                                            <td data-label="Status">
                                                <span class="report-badge report-badge-<?= str_replace(' ', '-', $task['status']) ?>">
                                                    <i class="fas <?= $task['status'] == 'selesai' ? 'fa-check' : ($task['status'] == 'on progress' ? 'fa-cogs' : 'fa-exclamation-circle') ?>"></i>
                                                    <?= ucfirst($task['status']) ?>
                                                </span>
                                            </td>
                                            <td data-label="Jenis Perbaikan">
                                                <?php if(isset($task['jenis_perbaikan'])): ?>
                                                    <span class="report-badge report-badge-<?= $task['jenis_perbaikan'] ?>">
                                                        <i class="fas <?= $task['jenis_perbaikan'] == 'temporary' ? 'fa-clock' : 'fa-check-double' ?>"></i>
                                                        <?= ucfirst($task['jenis_perbaikan']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="report-badge report-badge-secondary">
                                                        <i class="fas fa-minus"></i>
                                                        Belum ada
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Aksi">
                                                <div class="report-action-buttons">
                                                    <button class="report-btn report-btn-info report-btn-sm" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#taskDetailModal<?= $task['id_tiket'] ?>">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </button>
                                                    
                                                    <?php if(!empty($task['latitude']) && !empty($task['longitude'])): ?>
                                                        <a href="https://www.google.com/maps?q=<?= $task['latitude'] ?>,<?= $task['longitude'] ?>" 
                                                           target="_blank" class="report-btn report-btn-primary report-btn-sm">
                                                            <i class="fas fa-map-marker-alt"></i> Maps
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <!-- No Tasks Message -->
                        <div class="report-empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5>Belum Ada Tugas</h5>
                            <p>
                                Tidak ada tugas yang sesuai dengan filter yang dipilih.
                                Silakan ubah filter atau periksa kembali nanti.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <!-- Task Detail Modals -->
    <?php foreach($tasks as $task): ?>
    <div class="modal fade" id="taskDetailModal<?= $task['id_tiket'] ?>" tabindex="-1" aria-labelledby="taskDetailModalLabel<?= $task['id_tiket'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="report-modal-content modal-content">
                <div class="report-modal-header modal-header">
                    <h5 class="report-modal-title modal-title" id="taskDetailModalLabel<?= $task['id_tiket'] ?>">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Detail Tiket #<?= $task['id_tiket'] ?> - <?= htmlspecialchars($task['jenis_gangguan']) ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Jenis Gangguan
                                </div>
                                <div class="report-task-info-value"><?= htmlspecialchars($task['jenis_gangguan']) ?></div>
                            </div>
                            
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-file-alt"></i>
                                    Deskripsi
                                </div>
                                <div class="report-task-info-value"><?= htmlspecialchars($task['deskripsi']) ?></div>
                            </div>
                            
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Lokasi
                                </div>
                                <div class="report-task-info-value"><?= htmlspecialchars($task['alamat']) ?></div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-calendar-plus"></i>
                                    Tanggal Tiket Dibuat
                                </div>
                                <div class="report-task-info-value"><?= date('d M Y H:i', strtotime($task['created_at'])) ?></div>
                            </div>
                            
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-calendar-check"></i>
                                    Tanggal Ditugaskan
                                </div>
                                <div class="report-task-info-value"><?= date('d M Y H:i', strtotime($task['tanggal_penugasan'])) ?></div>
                            </div>
                            
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-flag"></i>
                                    Status
                                </div>
                                <div class="report-task-info-value">
                                    <span class="report-badge report-badge-<?= str_replace(' ', '-', $task['status']) ?>">
                                        <i class="fas <?= $task['status'] == 'selesai' ? 'fa-check' : ($task['status'] == 'on progress' ? 'fa-cogs' : 'fa-exclamation-circle') ?>"></i>
                                        <?= ucfirst($task['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <?php if(isset($task['jenis_perbaikan'])): ?>
                        <h5 class="mb-3">
                            <i class="fas fa-tools me-2"></i>
                            Informasi Perbaikan
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="report-task-info-group">
                                    <div class="report-task-info-label">
                                        <i class="fas fa-wrench"></i>
                                        Jenis Perbaikan
                                    </div>
                                    <div class="report-task-info-value">
                                        <span class="report-badge report-badge-<?= $task['jenis_perbaikan'] ?>">
                                            <i class="fas <?= $task['jenis_perbaikan'] == 'temporary' ? 'fa-clock' : 'fa-check-double' ?>"></i>
                                            <?= ucfirst($task['jenis_perbaikan']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if(isset($task['catatan'])): ?>
                                    <div class="report-task-info-group">
                                        <div class="report-task-info-label">
                                            <i class="fas fa-sticky-note"></i>
                                            Catatan
                                        </div>
                                        <div class="report-task-info-value"><?= htmlspecialchars($task['catatan']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="report-task-info-group">
                                    <div class="report-task-info-label">
                                        <i class="fas fa-calendar-check"></i>
                                        Tanggal Selesai
                                    </div>
                                    <div class="report-task-info-value">
                                        <?= isset($task['selesai_pada']) ? date('d M Y H:i', strtotime($task['selesai_pada'])) : 'Belum selesai' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(isset($task['dokumentasi']) && !empty($task['dokumentasi'])): ?>
                            <div class="report-task-info-group">
                                <div class="report-task-info-label">
                                    <i class="fas fa-camera"></i>
                                    Dokumentasi
                                </div>
                                <div class="report-task-images">
                                    <img src="../../uploads/<?= $task['dokumentasi'] ?>" 
                                         alt="Dokumentasi" 
                                         class="report-task-image"
                                         data-bs-toggle="modal" 
                                         data-bs-target="#imageModal<?= $task['id_tiket'] ?>">
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="report-alert report-alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            Belum ada laporan perbaikan untuk tiket ini.
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($task['latitude']) && !empty($task['longitude'])): ?>
                        <hr>
                        <div class="text-center mt-3">
                            <a href="https://www.google.com/maps?q=<?= $task['latitude'] ?>,<?= $task['longitude'] ?>" 
                               target="_blank" class="report-btn report-btn-primary">
                                <i class="fas fa-map-marked-alt"></i>Lihat di Google Maps
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="report-btn report-btn-primary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <?php if(isset($task['dokumentasi']) && !empty($task['dokumentasi'])): ?>
    <div class="modal fade" id="imageModal<?= $task['id_tiket'] ?>" tabindex="-1" aria-labelledby="imageModalLabel<?= $task['id_tiket'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel<?= $task['id_tiket'] ?>">
                        <i class="fas fa-image me-2"></i>Dokumentasi Tiket #<?= $task['id_tiket'] ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../../uploads/<?= $task['dokumentasi'] ?>" class="img-fluid rounded" alt="Dokumentasi">
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with enhanced responsive design
            $('#taskTable').DataTable({
                responsive: {
                    details: {
                        type: 'column',
                        target: 'tr'
                    }
                },
                order: [[3, 'desc']], // Sort by created_at column by default
                pageLength: 10,
                autoWidth: false,
                language: {
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ada data yang ditemukan",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                    infoEmpty: "Tidak ada data tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    search: "Cari:",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                },
                columnDefs: [
                    { responsivePriority: 1, targets: 0 }, // ID Tiket
                    { responsivePriority: 2, targets: 1 }, // Jenis Gangguan
                    { responsivePriority: 3, targets: -1 }, // Aksi
                    { responsivePriority: 4, targets: 4 }, // Status
                    { orderable: false, targets: -1 } // Disable ordering on action column
                ],
                drawCallback: function() {
                    // Re-initialize tooltips or other components if needed
                    $('[data-bs-toggle="tooltip"]').tooltip();
                }
            });
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('.report-alert').fadeOut();
            }, 5000);

            // Handle window resize for responsive table
            $(window).on('resize', function() {
                if ($.fn.DataTable.isDataTable('#taskTable')) {
                    $('#taskTable').DataTable().columns.adjust().responsive.recalc();
                }
            });
            
            // Handle orientation change for mobile devices
            $(window).on('orientationchange', function() {
                setTimeout(function() {
                    if ($.fn.DataTable.isDataTable('#taskTable')) {
                        $('#taskTable').DataTable().columns.adjust().responsive.recalc();
                    }
                }, 500);
            });
        });

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
