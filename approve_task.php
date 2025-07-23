<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Data navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor';
$userRole = 'supervisor';
$supervisor_id = $_SESSION['user_id'];

// Approval/reject logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $laporan_id = (int)$_POST['laporan_id'];
    $catatan_supervisor = htmlspecialchars($_POST['catatan_supervisor']);
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("SELECT * FROM laporan_pending WHERE id_laporan_pending = ?");
        $stmt->execute([$laporan_id]);
        $laporan = $stmt->fetch();
        
        if ($laporan) {
            // Move data ke laporan (tabel utama)
            $stmt = $pdo->prepare("
                INSERT INTO laporan (id_tiket, id_teknisi, jenis_perbaikan, dokumentasi, catatan) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $laporan['id_tiket'],
                $laporan['id_teknisi'],
                $laporan['jenis_perbaikan'],
                $laporan['dokumentasi'],
                $laporan['catatan']
            ]);
            
            // Update status laporan_pending
            $stmt = $pdo->prepare("
                UPDATE laporan_pending 
                SET status_approval = 'approved', catatan_supervisor = ?, id_supervisor = ? 
                WHERE id_laporan_pending = ?
            ");
            $stmt->execute([$catatan_supervisor, $supervisor_id, $laporan_id]);
            
            $_SESSION['success'] = "✅ Laporan berhasil disetujui dan disimpan!";
        }
    } elseif ($action === 'reject') {
        // Update status laporan_pending & reset tiket ke on progress
        $stmt = $pdo->prepare("
            UPDATE laporan_pending 
            SET status_approval = 'rejected', catatan_supervisor = ?, id_supervisor = ? 
            WHERE id_laporan_pending = ?
        ");
        $stmt->execute([$catatan_supervisor, $supervisor_id, $laporan_id]);
        
        $stmt = $pdo->prepare("SELECT id_tiket FROM laporan_pending WHERE id_laporan_pending = ?");
        $stmt->execute([$laporan_id]);
        $tiket = $stmt->fetch();
        
        if ($tiket) {
            $stmt = $pdo->prepare("UPDATE tiket SET status = 'on progress' WHERE id_tiket = ?");
            $stmt->execute([$tiket['id_tiket']]);
        }
        
        $_SESSION['success'] = "❌ Laporan ditolak. Tiket dikembalikan ke teknisi!";
    }
    
    header("Location: approve_task.php");
    exit();
}

// List laporan_pending menunggu approval
$stmt = $pdo->prepare("
    SELECT lp.*, t.jenis_gangguan, t.deskripsi, l.alamat, tk.nama_lengkap as teknisi_nama
    FROM laporan_pending lp
    JOIN tiket t ON lp.id_tiket = t.id_tiket
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN teknisi tk ON lp.id_teknisi = tk.id_teknisi
    WHERE lp.status_approval = 'pending'
    ORDER BY lp.created_at DESC
");
$stmt->execute();
$pending_reports = $stmt->fetchAll();

// Statistik laporan_pending
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN status_approval = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status_approval = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status_approval = 'rejected' THEN 1 END) as rejected
    FROM laporan_pending
");
$stmt->execute();
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Approve Task - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --approve-telkom-red: #E31E24;
            --approve-telkom-dark-red: #B71C1C;
            --approve-telkom-light-red: #FFEBEE;
            --approve-telkom-gray: #F5F5F5;
            --approve-telkom-dark-gray: #424242;
            --approve-telkom-white: #FFFFFF;
            --approve-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --approve-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --approve-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --approve-border-radius: 12px;
            --approve-border-radius-small: 8px;
            --approve-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .approve-main-content {
            padding: 110px 25px 25px;
            transition: var(--approve-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .approve-header-section {
            background: linear-gradient(135deg, var(--approve-telkom-red) 0%, var(--approve-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--approve-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--approve-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .approve-header-section::before {
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

        .approve-header-section::after {
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

        .approve-header-content {
            position: relative;
            z-index: 2;
        }

        .approve-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .approve-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .approve-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Statistics Cards */
        .approve-stats-card {
            background: var(--approve-telkom-white);
            border: none;
            border-radius: var(--approve-border-radius);
            box-shadow: var(--approve-shadow-light);
            transition: var(--approve-transition);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .approve-stats-card:hover {
            box-shadow: var(--approve-shadow-medium);
            transform: translateY(-2px);
        }

        .approve-stats-card-body {
            padding: 1.5rem;
            text-align: center;
        }

        .approve-stats-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .approve-stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .approve-stats-label {
            color: #6c757d;
            font-weight: 500;
        }

        .approve-stats-pending {
            border-left: 4px solid #ffc107;
        }

        .approve-stats-pending .approve-stats-icon {
            color: #ffc107;
        }

        .approve-stats-approved {
            border-left: 4px solid #28a745;
        }

        .approve-stats-approved .approve-stats-icon {
            color: #28a745;
        }

        .approve-stats-rejected {
            border-left: 4px solid #dc3545;
        }

        .approve-stats-rejected .approve-stats-icon {
            color: #dc3545;
        }

        /* Card Styles */
        .approve-card {
            background: var(--approve-telkom-white);
            border: none;
            border-radius: var(--approve-border-radius);
            box-shadow: var(--approve-shadow-light);
            transition: var(--approve-transition);
            overflow: hidden;
            border-left: 4px solid var(--approve-telkom-red);
            margin-bottom: 2rem;
        }

        .approve-card:hover {
            box-shadow: var(--approve-shadow-medium);
            transform: translateY(-2px);
        }

        .approve-card-header {
            background: linear-gradient(135deg, var(--approve-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--approve-telkom-red);
            padding: 1.5rem;
            border-radius: var(--approve-border-radius) var(--approve-border-radius) 0 0 !important;
        }

        .approve-card-title {
            color: var(--approve-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .approve-card-body {
            padding: 2rem;
        }

        /* Report Item */
        .approve-report-item {
            background: #f8f9fa;
            border-radius: var(--approve-border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #ffc107;
        }

        .approve-report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .approve-report-title {
            font-weight: 600;
            color: var(--approve-telkom-dark-gray);
            font-size: 1.1rem;
        }

        .approve-report-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .approve-report-details {
            margin-bottom: 1.5rem;
        }

        .approve-detail-item {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .approve-detail-label {
            font-weight: 500;
            min-width: 120px;
            color: var(--approve-telkom-dark-gray);
        }

        .approve-detail-value {
            color: #333;
            flex: 1;
        }

        /* Button Styles */
        .approve-btn {
            border-radius: var(--approve-border-radius-small);
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: var(--approve-transition);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            margin-right: 0.5rem;
        }

        .approve-btn-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .approve-btn-success:hover {
            background: linear-gradient(135deg, #1e7e34 0%, #155724 100%);
            transform: translateY(-1px);
            box-shadow: var(--approve-shadow-medium);
            color: white;
        }

        .approve-btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .approve-btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #a71e2a 100%);
            transform: translateY(-1px);
            box-shadow: var(--approve-shadow-medium);
            color: white;
        }

        .approve-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .approve-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #0f6674 100%);
            color: white;
        }

        /* Modal Styles */
        .approve-modal-content {
            border-radius: var(--approve-border-radius);
            border: none;
            box-shadow: var(--approve-shadow-heavy);
        }

        .approve-modal-header {
            background: linear-gradient(135deg, var(--approve-telkom-red) 0%, var(--approve-telkom-dark-red) 100%);
            color: white;
            border-radius: var(--approve-border-radius) var(--approve-border-radius) 0 0;
        }

        .approve-modal-title {
            font-weight: 600;
        }

        .approve-form-group {
            margin-bottom: 1.5rem;
        }

        .approve-form-label {
            font-weight: 500;
            color: var(--approve-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .approve-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--approve-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--approve-transition);
            background: #fafafa;
            width: 100%;
        }

        .approve-form-control:focus {
            border-color: var(--approve-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .approve-form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        /* Empty State */
        .approve-empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .approve-empty-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .approve-empty-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .approve-empty-subtitle {
            font-size: 1rem;
        }

        /* Image Gallery */
        .approve-image-gallery {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .approve-image-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--approve-border-radius-small);
            cursor: pointer;
            transition: var(--approve-transition);
        }

        .approve-image-thumb:hover {
            transform: scale(1.1);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .approve-main-content {
                padding: 110px 20px 25px;
            }
            
            .approve-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .approve-main-content {
                padding: 110px 15px 25px;
            }
            
            .approve-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .approve-header-title {
                font-size: 1.8rem;
            }
            
            .approve-header-subtitle {
                font-size: 1rem;
            }
            
            .approve-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .approve-main-content {
                padding: 110px 10px 25px;
            }
            
            .approve-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .approve-header-title {
                font-size: 1.5rem;
            }
            
            .approve-card-body {
                padding: 1rem;
            }

            .approve-report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .approve-detail-item {
                flex-direction: column;
            }

            .approve-detail-label {
                min-width: auto;
                margin-bottom: 0.25rem;
            }
        }

        @media (max-width: 576px) {
            .approve-header-title {
                font-size: 1.3rem;
            }
            
            .approve-card-body {
                padding: 0.75rem;
            }
            
            .approve-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.85rem;
                margin-bottom: 0.5rem;
            }

            .approve-report-item {
                padding: 1rem;
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
        
        <div class="approve-main-content">
            <!-- Header Section -->
            <div class="approve-header-section">
                <div class="approve-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="approve-header-content">
                    <h1 class="approve-header-title">
                        <i class="fas fa-check-circle me-3"></i>
                        Approve Task
                    </h1>
                    <p class="approve-header-subtitle">
                        Review dan setujui laporan perbaikan dari teknisi - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="approve-stats-card approve-stats-pending">
                        <div class="approve-stats-card-body">
                            <div class="approve-stats-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="approve-stats-number"><?= $stats['pending'] ?></div>
                            <div class="approve-stats-label">Menunggu Approval</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="approve-stats-card approve-stats-approved">
                        <div class="approve-stats-card-body">
                            <div class="approve-stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="approve-stats-number"><?= $stats['approved'] ?></div>
                            <div class="approve-stats-label">Disetujui</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="approve-stats-card approve-stats-rejected">
                        <div class="approve-stats-card-body">
                            <div class="approve-stats-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="approve-stats-number"><?= $stats['rejected'] ?></div>
                            <div class="approve-stats-label">Ditolak</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Reports -->
            <div class="approve-card">
                <div class="approve-card-header">
                    <h5 class="approve-card-title">
                        <i class="fas fa-list-check"></i>
                        Laporan Menunggu Approval
                    </h5>
                </div>
                <div class="approve-card-body">
                    <?php if (empty($pending_reports)): ?>
                        <div class="approve-empty-state">
                            <div class="approve-empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="approve-empty-title">Tidak Ada Laporan Pending</div>
                            <div class="approve-empty-subtitle">
                                Semua laporan sudah diproses atau belum ada laporan baru
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_reports as $report): ?>
                            <div class="approve-report-item">
                                <div class="approve-report-header">
                                    <div class="approve-report-title">
                                        <i class="fas fa-ticket-alt me-2"></i>
                                        Tiket #<?= $report['id_tiket'] ?> - <?= htmlspecialchars($report['jenis_gangguan']) ?>
                                    </div>
                                    <div class="approve-report-date">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d M Y H:i', strtotime($report['created_at'])) ?>
                                    </div>
                                </div>

                                <div class="approve-report-details">
                                    <div class="approve-detail-item">
                                        <span class="approve-detail-label">
                                            <i class="fas fa-user me-1"></i>Teknisi:
                                        </span>
                                        <span class="approve-detail-value"><?= htmlspecialchars($report['teknisi_nama']) ?></span>
                                    </div>
                                    <div class="approve-detail-item">
                                        <span class="approve-detail-label">
                                            <i class="fas fa-map-marker-alt me-1"></i>Lokasi:
                                        </span>
                                        <span class="approve-detail-value"><?= htmlspecialchars($report['alamat']) ?></span>
                                    </div>
                                    <div class="approve-detail-item">
                                        <span class="approve-detail-label">
                                            <i class="fas fa-tools me-1"></i>Jenis Perbaikan:
                                        </span>
                                        <span class="approve-detail-value">
                                            <span class="badge bg-<?= $report['jenis_perbaikan'] == 'permanent' ? 'success' : 'warning' ?>">
                                                <?= ucfirst($report['jenis_perbaikan']) ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="approve-detail-item">
                                        <span class="approve-detail-label">
                                            <i class="fas fa-file-alt me-1"></i>Catatan:
                                        </span>
                                                                                <span class="approve-detail-value"><?= htmlspecialchars($report['catatan']) ?></span>
                                    </div>
                                    <?php if (!empty($report['dokumentasi'])): ?>
                                        <div class="approve-detail-item">
                                            <span class="approve-detail-label">
                                                <i class="fas fa-images me-1"></i>Dokumentasi:
                                            </span>
                                            <span class="approve-detail-value">
                                                <div class="approve-image-gallery">
                                                    <?php 
                                                    $images = explode(',', $report['dokumentasi']);
                                                    foreach ($images as $image): 
                                                        if (!empty(trim($image))):
                                                    ?>
                                                        <img src="../../uploads/<?= trim($image) ?>" 
                                                             alt="Dokumentasi" 
                                                             class="approve-image-thumb"
                                                             onclick="showImageModal('../../uploads/<?= trim($image) ?>')">
                                                    <?php 
                                                        endif;
                                                    endforeach; 
                                                    ?>
                                                </div>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" 
                                            class="approve-btn approve-btn-success" 
                                            onclick="showApprovalModal(<?= $report['id_laporan_pending'] ?>, 'approve')">
                                        <i class="fas fa-check"></i>Setujui
                                    </button>
                                    <button type="button" 
                                            class="approve-btn approve-btn-danger" 
                                            onclick="showApprovalModal(<?= $report['id_laporan_pending'] ?>, 'reject')">
                                        <i class="fas fa-times"></i>Tolak
                                    </button>
                                    <button type="button" 
                                            class="approve-btn approve-btn-info" 
                                            onclick="showDetailModal(<?= $report['id_laporan_pending'] ?>)">
                                        <i class="fas fa-eye"></i>Detail
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content approve-modal-content">
                <div class="modal-header approve-modal-header">
                    <h5 class="modal-title approve-modal-title" id="approvalModalTitle">
                        <i class="fas fa-check-circle me-2"></i>Konfirmasi Approval
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="approvalForm">
                    <div class="modal-body">
                        <input type="hidden" name="laporan_id" id="modalLaporanId">
                        <input type="hidden" name="action" id="modalAction">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="approvalMessage">Apakah Anda yakin ingin menyetujui laporan ini?</span>
                        </div>

                        <div class="approve-form-group">
                            <label class="approve-form-label" for="catatan_supervisor">
                                <i class="fas fa-comment me-2"></i>Catatan Supervisor
                            </label>
                            <textarea name="catatan_supervisor" 
                                      id="catatan_supervisor" 
                                      class="approve-form-control approve-form-textarea" 
                                      placeholder="Berikan catatan atau alasan untuk keputusan Anda..." 
                                      required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="approve-btn approve-btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>Batal
                        </button>
                        <button type="submit" class="approve-btn" id="confirmButton">
                            <i class="fas fa-check"></i>Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content approve-modal-content">
                <div class="modal-header approve-modal-header">
                    <h5 class="modal-title approve-modal-title">
                        <i class="fas fa-info-circle me-2"></i>Detail Laporan
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="detailModalBody">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Dokumentasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="Dokumentasi" class="img-fluid">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show approval modal
        function showApprovalModal(laporanId, action) {
            document.getElementById('modalLaporanId').value = laporanId;
            document.getElementById('modalAction').value = action;
            
            const title = document.getElementById('approvalModalTitle');
            const message = document.getElementById('approvalMessage');
            const confirmButton = document.getElementById('confirmButton');
            
            if (action === 'approve') {
                title.innerHTML = '<i class="fas fa-check-circle me-2"></i>Setujui Laporan';
                message.textContent = 'Apakah Anda yakin ingin menyetujui laporan ini? Laporan akan disimpan ke dalam sistem.';
                confirmButton.className = 'approve-btn approve-btn-success';
                confirmButton.innerHTML = '<i class="fas fa-check"></i>Setujui';
            } else {
                title.innerHTML = '<i class="fas fa-times-circle me-2"></i>Tolak Laporan';
                message.textContent = 'Apakah Anda yakin ingin menolak laporan ini? Tiket akan dikembalikan ke teknisi.';
                confirmButton.className = 'approve-btn approve-btn-danger';
                confirmButton.innerHTML = '<i class="fas fa-times"></i>Tolak';
            }
            
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }

        // Show detail modal
        function showDetailModal(laporanId) {
            // You can implement AJAX call here to get detailed information
            const detailBody = document.getElementById('detailModalBody');
            detailBody.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            new bootstrap.Modal(document.getElementById('detailModal')).show();
            
            // Simulate loading detail (replace with actual AJAX call)
            setTimeout(() => {
                detailBody.innerHTML = `
                    <div class="approve-detail-item">
                        <span class="approve-detail-label">ID Laporan:</span>
                        <span class="approve-detail-value">#${laporanId}</span>
                    </div>
                    <div class="approve-detail-item">
                        <span class="approve-detail-label">Status:</span>
                        <span class="approve-detail-value">
                            <span class="badge bg-warning">Menunggu Approval</span>
                        </span>
                    </div>
                    <p class="text-muted">Detail lengkap laporan akan ditampilkan di sini.</p>
                `;
            }, 1000);
        }

        // Show image modal
        function showImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        // Form submission with loading state
        document.getElementById('approvalForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="approve-loading"></span> Memproses...';
            submitBtn.disabled = true;

            // Re-enable after 10 seconds in case of error
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 10000);
        });

        // Auto-refresh page every 30 seconds to check for new reports
        setInterval(() => {
            // Only refresh if no modal is open
            if (!document.querySelector('.modal.show')) {
                location.reload();
            }
        }, 30000);

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>

