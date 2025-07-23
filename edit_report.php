<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('admin');

if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: ../../index.php');
    exit();
}

$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin User';
$userRole = 'admin';

// Cek keberadaan ID laporan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['warning'] = "⚠️ ID Laporan tidak valid";
    header('Location: report.php');
    exit();
}

$report_id = $_GET['id'];

// Ambil detail laporan (disesuaikan dengan struktur database)
$stmt = $pdo->prepare("
    SELECT 
        l.*,
        t.jenis_gangguan,
        t.status AS tiket_status,
        lok.alamat,
        tk.username AS teknisi_name
    FROM 
        laporan l
    JOIN
        tiket t ON l.id_tiket = t.id_tiket
    JOIN
        lokasi lok ON t.id_lokasi = lok.id_lokasi
    LEFT JOIN
        penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN
        teknisi tk ON p.id_teknisi = tk.id_teknisi
    WHERE
        l.id_laporan = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

// Validasi keberadaan data
if (!$report) {
    $_SESSION['warning'] = "⚠️ Laporan tidak ditemukan";
    header('Location: report.php');
    exit();
}

// Handle submit form edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_perbaikan = $_POST['jenis_perbaikan'];
    $catatan = htmlspecialchars($_POST['catatan']);
    $dokumentasi = $report['dokumentasi'];

    if (isset($_FILES['dokumentasi']) && $_FILES['dokumentasi']['size'] > 0) {
        $file = $_FILES['dokumentasi'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid() . '_' . $file_name;
            $upload_path = '../../uploads/' . $new_file_name;

            if ($dokumentasi && file_exists('../../uploads/' . $dokumentasi)) {
                unlink('../../uploads/' . $dokumentasi);
            }
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $dokumentasi = $new_file_name;
            } else {
                $_SESSION['warning'] = "⚠️ Gagal mengunggah file";
            }
        } else {
            $_SESSION['warning'] = "⚠️ Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF";
        }
    }

    $type_changed = $report['jenis_perbaikan'] !== $jenis_perbaikan;

    $stmt = $pdo->prepare("
        UPDATE laporan
        SET jenis_perbaikan = ?, dokumentasi = ?, catatan = ?
        WHERE id_laporan = ?
    ");
    $stmt->execute([$jenis_perbaikan, $dokumentasi, $catatan, $report_id]);

    // Update status tiket bila ganti jenis perbaikan
    if ($type_changed && $jenis_perbaikan === 'permanent') {
        $stmt = $pdo->prepare("UPDATE tiket SET status = 'selesai' WHERE id_tiket = ?");
        $stmt->execute([$report['id_tiket']]);
    } else if ($type_changed && $jenis_perbaikan === 'temporary') {
        $stmt = $pdo->prepare("UPDATE tiket SET status = 'on progress' WHERE id_tiket = ?");
        $stmt->execute([$report['id_tiket']]);
    }

    $_SESSION['success'] = "✅ Laporan berhasil diperbarui";
    header('Location: report.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Edit Laporan - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --edit-telkom-red: #E31E24;
            --edit-telkom-dark-red: #B71C1C;
            --edit-telkom-light-red: #FFEBEE;
            --edit-telkom-gray: #F5F5F5;
            --edit-telkom-dark-gray: #424242;
            --edit-telkom-white: #FFFFFF;
            --edit-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --edit-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --edit-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --edit-border-radius: 12px;
            --edit-border-radius-small: 8px;
            --edit-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .edit-main-content {
            padding: 110px 25px 25px;
            transition: var(--edit-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .edit-header-section {
            background: linear-gradient(135deg, var(--edit-telkom-red) 0%, var(--edit-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--edit-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--edit-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .edit-header-section::before {
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

        .edit-header-section::after {
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

        .edit-header-content {
            position: relative;
            z-index: 2;
        }

        .edit-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .edit-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .edit-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .edit-card {
            background: var(--edit-telkom-white);
            border: none;
            border-radius: var(--edit-border-radius);
            box-shadow: var(--edit-shadow-light);
            transition: var(--edit-transition);
            overflow: hidden;
            border-left: 4px solid var(--edit-telkom-red);
            margin-bottom: 2rem;
        }

        .edit-card:hover {
            box-shadow: var(--edit-shadow-medium);
            transform: translateY(-2px);
        }

        .edit-card-header {
            background: linear-gradient(135deg, var(--edit-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--edit-telkom-red);
            padding: 1.5rem;
            border-radius: var(--edit-border-radius) var(--edit-border-radius) 0 0 !important;
        }

        .edit-card-title {
            color: var(--edit-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-card-body {
            padding: 2rem;
        }

        /* Form Styles */
        .edit-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--edit-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--edit-transition);
            background: #fafafa;
            width: 100%;
        }

        .edit-form-control:focus {
            border-color: var(--edit-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .edit-form-label {
            font-weight: 500;
            color: var(--edit-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .edit-form-group {
            margin-bottom: 1.5rem;
        }

        /* Button Styles */
        .edit-btn {
            border-radius: var(--edit-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--edit-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .edit-btn-primary {
            background: linear-gradient(135deg, var(--edit-telkom-red) 0%, var(--edit-telkom-dark-red) 100%);
            color: white;
        }

        .edit-btn-primary:hover {
            background: linear-gradient(135deg, var(--edit-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--edit-shadow-medium);
            color: white;
        }

        .edit-btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .edit-btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
        }

        /* Alert Styles */
        .edit-alert {
            border: none;
            border-radius: var(--edit-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .edit-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        /* Documentation Styles */
        .edit-current-doc {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 1px solid #2196f3;
            border-radius: var(--edit-border-radius-small);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .edit-current-doc img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--edit-border-radius-small);
            object-fit: cover;
            cursor: pointer;
            transition: var(--edit-transition);
        }

        .edit-current-doc img:hover {
            transform: scale(1.05);
            box-shadow: var(--edit-shadow-medium);
        }

        /* Info Styles */
        .edit-info-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: var(--edit-border-radius-small);
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .edit-info-label {
            font-weight: 600;
            color: var(--edit-telkom-red);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-info-value {
            color: var(--edit-telkom-dark-gray);
            background: white;
            padding: 0.5rem;
            border-radius: var(--edit-border-radius-small);
            border: 1px solid #e0e0e0;
        }

        /* Badge Styles */
        .edit-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .edit-badge-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .edit-badge-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
        }

        .edit-badge-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        /* File Input Styles */
        .edit-file-input {
            border: 2px dashed #e9ecef;
            border-radius: var(--edit-border-radius-small);
            padding: 1rem;
            text-align: center;
            transition: var(--edit-transition);
            background: #fafafa;
        }

        .edit-file-input:hover {
            border-color: var(--edit-telkom-red);
            background: var(--edit-telkom-light-red);
        }

        .edit-file-input input[type="file"] {
            width: 100%;
            padding: 0.5rem;
            border: none;
            background: transparent;
        }

        /* Loading Animation */
        .edit-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: edit-spin 1s ease-in-out infinite;
        }

        @keyframes edit-spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .edit-main-content {
                padding: 110px 20px 25px;
            }
            
            .edit-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .edit-main-content {
                padding: 110px 15px 25px;
            }
            
            .edit-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .edit-header-title {
                font-size: 1.8rem;
            }
            
            .edit-header-subtitle {
                font-size: 1rem;
            }
            
            .edit-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .edit-main-content {
                padding: 110px 10px 25px;
            }
            
            .edit-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .edit-header-title {
                font-size: 1.5rem;
            }
            
            .edit-card-body {
                padding: 1rem;
            }

            .edit-current-doc img {
                max-height: 150px;
            }

            .edit-btn {
                width: 100%;
                justify-content: center;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .edit-header-title {
                font-size: 1.3rem;
            }
            
            .edit-card-body {
                padding: 0.75rem;
            }
            
            .edit-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .edit-current-doc img {
                max-height: 120px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .edit-btn:hover,
            .edit-card:hover,
            .edit-current-doc img:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .edit-header-section,
            .edit-btn {
                display: none !important;
            }
            
            .edit-main-content {
                padding: 0;
            }
            
            .edit-card {
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
        
        <div class="edit-main-content">
            <!-- Header Section -->
            <div class="edit-header-section">
                <div class="edit-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="edit-header-content">
                    <h1 class="edit-header-title">
                        <i class="fas fa-edit me-3"></i>
                        Edit Laporan #<?= $report_id ?>
                    </h1>
                    <p class="edit-header-subtitle">
                        Perbarui informasi laporan perbaikan - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="edit-alert edit-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <!-- Form Edit Laporan -->
                <div class="col-lg-8">
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <h5 class="edit-card-title">
                                <i class="fas fa-edit"></i>
                                Form Edit Laporan
                            </h5>
                        </div>
                        <div class="edit-card-body">
                            <form method="POST" enctype="multipart/form-data" id="editReportForm">
                                <div class="edit-form-group">
                                    <label for="jenis_perbaikan" class="edit-form-label">
                                        <i class="fas fa-tools me-2"></i>Jenis Perbaikan
                                    </label>
                                    <select class="edit-form-control" id="jenis_perbaikan" name="jenis_perbaikan" required>
                                        <option value="temporary" <?= $report['jenis_perbaikan'] === 'temporary' ? 'selected' : '' ?>>Sementara</option>
                                        <option value="permanent" <?= $report['jenis_perbaikan'] === 'permanent' ? 'selected' : '' ?>>Permanen</option>
                                    </select>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Mengubah dari "Sementara" ke "Permanen" akan mengubah status tiket menjadi "Selesai".
                                    </small>
                                </div>
                                
                                <div class="edit-form-group">
                                    <label for="dokumentasi" class="edit-form-label">
                                        <i class="fas fa-camera me-2"></i>Dokumentasi
                                    </label>
                                    
                                    <?php if ($report['dokumentasi']): ?>
                                        <div class="edit-current-doc">
                                            <p class="mb-2 fw-bold">
                                                <i class="fas fa-image me-2"></i>Dokumentasi saat ini:
                                            </p>
                                            <img src="../../uploads/<?= $report['dokumentasi'] ?>" 
                                                 alt="Dokumentasi Laporan"
                                                 data-bs-toggle="modal" 
                                                 data-bs-target="#imageModal">
                                        </div>
                                    <?php else: ?>
                                        <div class="edit-current-doc">
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-image me-2"></i>Belum ada dokumentasi
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="edit-file-input">
                                        <i class="fas fa-cloud-upload-alt mb-2" style="font-size: 2rem; color: var(--edit-telkom-red);"></i>
                                        <p class="mb-2">Pilih file untuk mengganti dokumentasi</p>
                                        <input type="file" class="edit-form-control" id="dokumentasi" name="dokumentasi" accept="image/*">
                                        <small class="text-muted">Format yang didukung: JPG, JPEG, PNG, GIF</small>
                                    </div>
                                </div>
                                
                                <div class="edit-form-group">
                                    <label for="catatan" class="edit-form-label">
                                        <i class="fas fa-file-alt me-2"></i>Catatan Perbaikan
                                    </label>
                                    <textarea class="edit-form-control" id="catatan" name="catatan" rows="6" required 
                                              placeholder="Jelaskan detail perbaikan yang telah dilakukan..."><?= htmlspecialchars($report['catatan']) ?></textarea>
                                </div>
                                
                                <div class="d-flex flex-column flex-md-row gap-3 justify-content-between">
                                    <a href="report.php" class="edit-btn edit-btn-secondary">
                                        <i class="fas fa-arrow-left"></i>Kembali ke Daftar
                                    </a>
                                    <button type="submit" class="edit-btn edit-btn-primary">
                                        <i class="fas fa-save"></i>Simpan Perubahan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Informasi Tiket -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <h5 class="edit-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informasi Tiket
                            </h5>
                        </div>
                        <div class="edit-card-body">
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-hashtag"></i>Tiket ID
                                </div>
                                <div class="edit-info-value">
                                    <strong>#<?= $report['id_tiket'] ?></strong>
                                </div>
                            </div>
                            
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-exclamation-triangle"></i>Jenis Gangguan
                                </div>
                                <div class="edit-info-value">
                                    <?= htmlspecialchars($report['jenis_gangguan']) ?>
                                </div>
                            </div>
                            
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-flag"></i>Status Tiket
                                </div>
                                <div class="edit-info-value">
                                    <?php
                                    $status_class = '';
                                    $status_icon = '';
                                    switch($report['tiket_status']) {
                                        case 'open':
                                            $status_class = 'edit-badge-warning';
                                            $status_icon = 'fas fa-clock';
                                            break;
                                        case 'on progress':
                                            $status_class = 'edit-badge-info';
                                            $status_icon = 'fas fa-cogs';
                                            break;
                                        case 'selesai':
                                            $status_class = 'edit-badge-success';
                                            $status_icon = 'fas fa-check';
                                            break;
                                        default:
                                            $status_class = 'edit-badge-info';
                                            $status_icon = 'fas fa-question';
                                    }
                                    ?>
                                    <span class="edit-badge <?= $status_class ?>">
                                        <i class="<?= $status_icon ?>"></i>
                                        <?= ucfirst($report['tiket_status']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-map-marker-alt"></i>Lokasi
                                </div>
                                <div class="edit-info-value">
                                    <?= htmlspecialchars($report['alamat']) ?>
                                </div>
                            </div>
                            
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-user-hard-hat"></i>Teknisi
                                </div>
                                <div class="edit-info-value">
                                    <?= htmlspecialchars($report['teknisi_name'] ?: 'Belum ditugaskan') ?>
                                </div>
                            </div>
                            
                            <div class="edit-info-item">
                                <div class="edit-info-label">
                                    <i class="fas fa-calendar-check"></i>Selesai Pada
                                </div>
                                <div class="edit-info-value">
                                    <?= date('d F Y, H:i', strtotime($report['selesai_pada'])) ?> WIB
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">
                        <i class="fas fa-image me-2"></i>Dokumentasi Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="../../uploads/<?= $report['dokumentasi'] ?>" class="img-fluid rounded" alt="Dokumentasi">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            document.getElementById('editReportForm').addEventListener('submit', function(e) {
                const catatan = document.getElementById('catatan').value.trim();
                
                if (!catatan) {
                    e.preventDefault();
                    alert('Catatan perbaikan harus diisi!');
                    return false;
                }

                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="edit-loading"></span> Menyimpan...';
                submitBtn.disabled = true;

                // Re-enable after 10 seconds in case of error
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            });

            // File input preview
            document.getElementById('dokumentasi').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // You can add preview functionality here if needed
                        console.log('File selected:', file.name);
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.edit-alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });
        });

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
