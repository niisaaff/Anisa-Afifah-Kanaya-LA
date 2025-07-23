<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('teknisi');

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Teknisi';
$userRole = 'teknisi';

// Ambil tiket aktif, penugasan lanjutan untuk permanent repair, dan tiket yang ditolak
// Exclude tiket yang sudah dikirim ulang (status pending)
$stmt = $pdo->prepare("
    SELECT DISTINCT t.*, l.alamat, l.latitude, l.longitude,
           r.id_laporan as laporan_id, r.jenis_perbaikan,
           lp_rejected.status_approval as rejected_status, 
           lp_rejected.catatan_supervisor, 
           lp_rejected.id_laporan_pending,
           lp_pending.status_approval as pending_status,
           CASE 
               WHEN lp_rejected.status_approval = 'rejected' AND lp_pending.status_approval IS NULL THEN 'rejected'
               ELSE t.status 
           END as display_status
    FROM tiket t
    JOIN penugasan p ON t.id_tiket = p.id_tiket
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    LEFT JOIN laporan r ON t.id_tiket = r.id_tiket
    LEFT JOIN laporan_pending lp_rejected ON t.id_tiket = lp_rejected.id_tiket AND lp_rejected.status_approval = 'rejected'
    LEFT JOIN laporan_pending lp_pending ON t.id_tiket = lp_pending.id_tiket AND lp_pending.status_approval = 'pending'
    WHERE p.id_teknisi = ? 
    AND (
        t.status != 'selesai' 
        OR (t.status = 'selesai' AND r.jenis_perbaikan = 'temporary')
        OR (lp_rejected.status_approval = 'rejected' AND lp_pending.status_approval IS NULL)
    )
    AND lp_pending.status_approval IS NULL
    ORDER BY 
        CASE 
            WHEN lp_rejected.status_approval = 'rejected' AND lp_pending.status_approval IS NULL THEN 1
            WHEN t.status = 'open' THEN 2
            WHEN t.status = 'on progress' THEN 3
            WHEN t.status = 'selesai' AND r.jenis_perbaikan = 'temporary' THEN 4
        END,
        t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tiket = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tiket Aktif - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --teknisi-telkom-red: #E31E24;
            --teknisi-telkom-dark-red: #B71C1C;
            --teknisi-telkom-light-red: #FFEBEE;
            --teknisi-telkom-gray: #F5F5F5;
            --teknisi-telkom-dark-gray: #424242;
            --teknisi-telkom-white: #FFFFFF;
            --teknisi-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --teknisi-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --teknisi-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --teknisi-border-radius: 12px;
            --teknisi-border-radius-small: 8px;
            --teknisi-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .teknisi-main-content {
            padding: 110px 25px 25px;
            transition: var(--teknisi-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .teknisi-header-section {
            background: linear-gradient(135deg, var(--teknisi-telkom-red) 0%, var(--teknisi-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--teknisi-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--teknisi-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .teknisi-header-section::before {
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

        .teknisi-header-section::after {
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

        .teknisi-header-content {
            position: relative;
            z-index: 2;
        }

        .teknisi-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .teknisi-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .teknisi-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .teknisi-card {
            background: var(--teknisi-telkom-white);
            border: none;
            border-radius: var(--teknisi-border-radius);
            box-shadow: var(--teknisi-shadow-light);
            transition: var(--teknisi-transition);
            overflow: hidden;
            border-left: 4px solid var(--teknisi-telkom-red);
            margin-bottom: 2rem;
        }

        .teknisi-card:hover {
            box-shadow: var(--teknisi-shadow-medium);
            transform: translateY(-2px);
        }

        .teknisi-card-rejected {
            border-left: 4px solid #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);
        }

        .teknisi-card-header {
            background: linear-gradient(135deg, var(--teknisi-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--teknisi-telkom-red);
            padding: 1.5rem;
            border-radius: var(--teknisi-border-radius) var(--teknisi-border-radius) 0 0 !important;
        }

        .teknisi-card-header-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #fafafa 100%);
            border-bottom: 2px solid #dc3545;
        }

        .teknisi-card-title {
            color: var(--teknisi-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .teknisi-card-title-rejected {
            color: #dc3545;
        }

        .teknisi-card-body {
            padding: 2rem;
        }

        /* Tiket ID Badge */
        .teknisi-tiket-id {
            background: linear-gradient(135deg, var(--teknisi-telkom-red) 0%, var(--teknisi-telkom-dark-red) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .teknisi-tiket-id-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        /* Info Grid */
        .teknisi-info-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .teknisi-info-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: var(--teknisi-border-radius-small);
            padding: 1rem;
        }

        .teknisi-info-label {
            font-weight: 600;
            color: var(--teknisi-telkom-red);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .teknisi-info-label-rejected {
            color: #dc3545;
        }

        .teknisi-info-value {
            color: var(--teknisi-telkom-dark-gray);
            background: white;
            padding: 0.75rem;
            border-radius: var(--teknisi-border-radius-small);
            border: 1px solid #e0e0e0;
            word-wrap: break-word;
        }

        /* Status Badge */
        .teknisi-status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .teknisi-status-open {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .teknisi-status-progress {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .teknisi-status-temporary {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
            color: white;
        }

        .teknisi-status-rejected {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        /* Rejection Notice */
        .teknisi-rejection-notice {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            border: 1px solid #f1aeb5;
            border-radius: var(--teknisi-border-radius-small);
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #dc3545;
        }

        .teknisi-rejection-title {
            color: #721c24;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .teknisi-rejection-text {
            color: #721c24;
            margin: 0;
            font-size: 0.9rem;
        }

        /* Button Styles */
        .teknisi-btn {
            border-radius: var(--teknisi-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--teknisi-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            width: 100%;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .teknisi-btn-primary {
            background: linear-gradient(135deg, var(--teknisi-telkom-red) 0%, var(--teknisi-telkom-dark-red) 100%);
            color: white;
        }

        .teknisi-btn-primary:hover {
            background: linear-gradient(135deg, var(--teknisi-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--teknisi-shadow-medium);
            color: white;
        }

        .teknisi-btn-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .teknisi-btn-success:hover {
            background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
            color: white;
        }

        .teknisi-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .teknisi-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            color: white;
        }

        .teknisi-btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .teknisi-btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            color: #212529;
        }

        /* Alert Styles */
        .teknisi-alert {
            border: none;
            border-radius: var(--teknisi-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .teknisi-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .teknisi-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .teknisi-alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .teknisi-alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Empty State */
        .teknisi-empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--teknisi-border-radius);
            box-shadow: var(--teknisi-shadow-light);
            margin-top: 2rem;
        }

        .teknisi-empty-state i {
            font-size: 4rem;
            color: var(--teknisi-telkom-red);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .teknisi-empty-state h5 {
            color: var(--teknisi-telkom-dark-gray);
            margin-bottom: 1rem;
        }

        .teknisi-empty-state p {
            color: #6c757d;
            margin-bottom: 0;
        }

        /* Action Buttons Container */
        .teknisi-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .teknisi-main-content {
                padding: 110px 20px 25px;
            }
            
            .teknisi-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .teknisi-main-content {
                padding: 110px 15px 25px;
            }
            
            .teknisi-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .teknisi-header-title {
                font-size: 1.8rem;
            }
            
            .teknisi-header-subtitle {
                font-size: 1rem;
            }
            
            .teknisi-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .teknisi-main-content {
                padding: 110px 10px 25px;
            }
            
            .teknisi-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .teknisi-header-title {
                font-size: 1.5rem;
            }
            
            .teknisi-card-body {
                padding: 1rem;
            }

            .teknisi-card-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .teknisi-tiket-id {
                align-self: flex-start;
            }
        }

        @media (max-width: 576px) {
            .teknisi-header-title {
                font-size: 1.3rem;
            }
            
            .teknisi-card-body {
                padding: 0.75rem;
            }
            
            .teknisi-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .teknisi-info-item {
                padding: 0.75rem;
            }

            .teknisi-info-value {
                padding: 0.5rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .teknisi-btn:hover,
            .teknisi-card:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .teknisi-header-section,
            .teknisi-btn,
            .teknisi-actions {
                display: none !important;
            }
            
            .teknisi-main-content {
                padding: 0;
            }
            
            .teknisi-card {
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
        
        <div class="teknisi-main-content">
            <!-- Header Section -->
            <div class="teknisi-header-section">
                <div class="teknisi-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="teknisi-header-content">
                    <h1 class="teknisi-header-title">
                        <i class="fas fa-user-hard-hat me-3"></i>
                        Selamat Bekerja, <?= htmlspecialchars($username) ?>
                    </h1>
                    <p class="teknisi-header-subtitle">
                        Berikut adalah daftar tiket aktif yang perlu Anda tangani - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="teknisi-alert teknisi-alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="teknisi-alert teknisi-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="teknisi-alert teknisi-alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Info Alert for Pending Reports -->
            <?php 
            $stmt_pending = $pdo->prepare("
                SELECT COUNT(*) as pending_count 
                FROM laporan_pending lp 
                JOIN tiket t ON lp.id_tiket = t.id_tiket 
                JOIN penugasan p ON t.id_tiket = p.id_tiket 
                WHERE p.id_teknisi = ? AND lp.status_approval = 'pending'
            ");
            $stmt_pending->execute([$_SESSION['user_id']]);
            $pending_count = $stmt_pending->fetch()['pending_count'];
            
            if($pending_count > 0): ?>
                <div class="teknisi-alert teknisi-alert-info">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Info:</strong> Anda memiliki <?= $pending_count ?> laporan yang sedang menunggu approval dari supervisor.
                </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <?php if(count($tiket) > 0): ?>
                    <?php foreach ($tiket as $t): ?>
                        <div class="col-lg-6 col-md-6">
                            <div class="teknisi-card h-100 <?= $t['display_status'] == 'rejected' ? 'teknisi-card-rejected' : '' ?>">
                                <div class="teknisi-card-header <?= $t['display_status'] == 'rejected' ? 'teknisi-card-header-rejected' : '' ?>">
                                    <div class="teknisi-card-title <?= $t['display_status'] == 'rejected' ? 'teknisi-card-title-rejected' : '' ?>">
                                        <span>
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            <?= htmlspecialchars($t['jenis_gangguan']) ?>
                                        </span>
                                        <span class="teknisi-tiket-id <?= $t['display_status'] == 'rejected' ? 'teknisi-tiket-id-rejected' : '' ?>">
                                            <i class="fas fa-hashtag"></i>
                                            <?= $t['id_tiket'] ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="teknisi-card-body">
                                    <!-- Rejection Notice -->
                                    <?php if($t['display_status'] == 'rejected'): ?>
                                        <div class="teknisi-rejection-notice">
                                            <div class="teknisi-rejection-title">
                                                <i class="fas fa-times-circle"></i>
                                                Laporan Ditolak Supervisor
                                            </div>
                                            <p class="teknisi-rejection-text">
                                                <strong>Catatan:</strong> <?= htmlspecialchars($t['catatan_supervisor']) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="teknisi-info-grid">
                                        <div class="teknisi-info-item">
                                            <div class="teknisi-info-label <?= $t['display_status'] == 'rejected' ? 'teknisi-info-label-rejected' : '' ?>">
                                                <i class="fas fa-map-marker-alt"></i>
                                                Lokasi Gangguan
                                            </div>
                                            <div class="teknisi-info-value">
                                                <?= htmlspecialchars($t['alamat']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="teknisi-info-item">
                                            <div class="teknisi-info-label <?= $t['display_status'] == 'rejected' ? 'teknisi-info-label-rejected' : '' ?>">
                                                <i class="fas fa-file-alt"></i>
                                                Deskripsi
                                            </div>
                                            <div class="teknisi-info-value">
                                                <?= htmlspecialchars($t['deskripsi']) ?>
                                            </div>
                                        </div>
                                        
                                        <div class="teknisi-info-item">
                                            <div class="teknisi-info-label <?= $t['display_status'] == 'rejected' ? 'teknisi-info-label-rejected' : '' ?>">
                                                <i class="fas fa-calendar-alt"></i>
                                                Tanggal Dibuat
                                            </div>
                                            <div class="teknisi-info-value">
                                                <?= date('d M Y H:i', strtotime($t['created_at'])) ?> WIB
                                            </div>
                                        </div>
                                        
                                        <div class="teknisi-info-item">
                                            <div class="teknisi-info-label <?= $t['display_status'] == 'rejected' ? 'teknisi-info-label-rejected' : '' ?>">
                                                <i class="fas fa-flag"></i>
                                                Status Tiket
                                            </div>
                                            <div class="teknisi-info-value">
                                                <?php if($t['display_status'] == 'rejected'): ?>
                                                    <span class="teknisi-status-badge teknisi-status-rejected">
                                                        <i class="fas fa-times-circle"></i>
                                                        Laporan Ditolak
                                                    </span>
                                                <?php elseif($t['status'] == 'selesai' && isset($t['jenis_perbaikan']) && $t['jenis_perbaikan'] == 'temporary'): ?>
                                                    <span class="teknisi-status-badge teknisi-status-temporary">
                                                        <i class="fas fa-clock"></i>
                                                        Menunggu Perbaikan Permanent
                                                    </span>
                                                <?php else: ?>
                                                    <span class="teknisi-status-badge <?= $t['status'] == 'open' ? 'teknisi-status-open' : 'teknisi-status-progress' ?>">
                                                        <i class="fas <?= $t['status'] == 'open' ? 'fa-exclamation-circle' : 'fa-cogs' ?>"></i>
                                                        <?= ucfirst($t['status']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <?php if(isset($t['jenis_perbaikan']) && $t['jenis_perbaikan'] == 'temporary'): ?>
                                        <div class="teknisi-info-item">
                                            <div class="teknisi-info-label <?= $t['display_status'] == 'rejected' ? 'teknisi-info-label-rejected' : '' ?>">
                                                <i class="fas fa-wrench"></i>
                                                Jenis Perbaikan
                                            </div>
                                            <div class="teknisi-info-value">
                                                <span class="teknisi-status-badge teknisi-status-temporary">
                                                    <i class="fas fa-tools"></i>
                                                    Temporary
                                                </span>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="teknisi-actions">
                                        <?php if($t['display_status'] == 'rejected'): ?>
                                            <a href="update_status.php?tiket_id=<?= $t['id_tiket'] ?>" class="teknisi-btn teknisi-btn-warning">
                                                <i class="fas fa-redo"></i>
                                                Perbaiki & Kirim Ulang Laporan
                                            </a>
                                        <?php elseif($t['status'] != 'selesai'): ?>
                                            <a href="update_status.php?tiket_id=<?= $t['id_tiket'] ?>" class="teknisi-btn teknisi-btn-primary">
                                                <i class="fas fa-edit"></i>
                                                Update Status Tiket
                                            </a>
                                        <?php elseif(isset($t['jenis_perbaikan']) && $t['jenis_perbaikan'] == 'temporary'): ?>
                                            <a href="create_permanent_repair.php?tiket_id=<?= $t['id_tiket'] ?>&laporan_id=<?= $t['laporan_id'] ?>" class="teknisi-btn teknisi-btn-success">
                                                <i class="fas fa-tools"></i>
                                                Buat Laporan Perbaikan Permanent
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($t['latitude']) && !empty($t['longitude'])): ?>
                                            <a href="https://www.google.com/maps?q=<?= $t['latitude'] ?>,<?= $t['longitude'] ?>" 
                                               target="_blank" class="teknisi-btn teknisi-btn-info">
                                                <i class="fas fa-map-marked-alt"></i>
                                                Lihat di Google Maps
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- No Tickets Message -->
                    <div class="col-12">
                        <div class="teknisi-empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h5>Tidak Ada Tiket Aktif</h5>
                            <p class="text-muted">Saat ini tidak ada tiket yang perlu ditangani. Silakan periksa kembali nanti.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.teknisi-alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });

            // Add loading state to action buttons
            const actionButtons = document.querySelectorAll('.teknisi-btn');
            actionButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    if (!this.href.includes('google.com/maps')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                        this.style.pointerEvents = 'none';
                        
                        // Re-enable after 3 seconds in case of error
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.style.pointerEvents = 'auto';
                        }, 3000);
                    }
                });
            });
        });

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
