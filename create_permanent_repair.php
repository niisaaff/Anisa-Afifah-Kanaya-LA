<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('teknisi');

// Get user data
$user_id = $_SESSION['user_id'];
$tiket_id = isset($_GET['tiket_id']) ? $_GET['tiket_id'] : null;
$laporan_id = isset($_GET['laporan_id']) ? $_GET['laporan_id'] : null;

if (!$tiket_id || !$laporan_id) {
    $_SESSION['error'] = "ID Tiket atau Laporan tidak valid!";
    header('Location: view_tiket.php');
    exit();
}

// Cek apakah tiket dan laporan ada dan berjenis temporary
$stmt = $pdo->prepare("
    SELECT t.*, l.alamat, l.latitude, l.longitude, r.jenis_perbaikan, r.catatan as catatan_lama
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN laporan r ON t.id_tiket = r.id_tiket
    JOIN penugasan p ON t.id_tiket = p.id_tiket
    WHERE t.id_tiket = ? AND r.id_laporan = ? AND p.id_teknisi = ? AND r.jenis_perbaikan = 'temporary'
");
$stmt->execute([$tiket_id, $laporan_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    $_SESSION['error'] = "Tiket tidak ditemukan atau bukan perbaikan temporary!";
    header('Location: view_tiket.php');
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $catatan = isset($_POST['catatan']) ? $_POST['catatan'] : '';
    
    // Handle file upload
    $dokumentasi = null;
    if (isset($_FILES['dokumentasi']) && $_FILES['dokumentasi']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['dokumentasi']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = "permanent_" . uniqid() . "." . $ext;
            $upload_path = "../../uploads/" . $new_filename;
            
            if (move_uploaded_file($_FILES['dokumentasi']['tmp_name'], $upload_path)) {
                $dokumentasi = $new_filename;
            } else {
                $_SESSION['error'] = "Error saat mengupload file!";
                header('Location: create_permanent_repair.php?tiket_id=' . $tiket_id . '&laporan_id=' . $laporan_id);
                exit();
            }
        } else {
            $_SESSION['error'] = "Format file tidak didukung! Gunakan JPG, JPEG, atau PNG.";
            header('Location: create_permanent_repair.php?tiket_id=' . $tiket_id . '&laporan_id=' . $laporan_id);
            exit();
        }
    }
    
    // Update jenis perbaikan menjadi permanent di laporan
    $stmt = $pdo->prepare("UPDATE laporan SET jenis_perbaikan = 'permanent', catatan = ?, dokumentasi = ? WHERE id_laporan = ?");
    $stmt->execute([$catatan, $dokumentasi, $laporan_id]);
    
    // Tetapkan status tiket tetap selesai
    $stmt = $pdo->prepare("UPDATE tiket SET status = 'selesai' WHERE id_tiket = ?");
    $stmt->execute([$tiket_id]);
    
    $_SESSION['success'] = "Laporan perbaikan permanen berhasil disimpan!";
    header('Location: view_tiket.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Perbaikan Permanen - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --permanent-telkom-red: #E31E24;
            --permanent-telkom-dark-red: #B71C1C;
            --permanent-telkom-light-red: #FFEBEE;
            --permanent-telkom-gray: #F5F5F5;
            --permanent-telkom-dark-gray: #424242;
            --permanent-telkom-white: #FFFFFF;
            --permanent-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --permanent-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --permanent-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --permanent-border-radius: 12px;
            --permanent-border-radius-small: 8px;
            --permanent-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .permanent-main-content {
            padding: 110px 25px 25px;
            transition: var(--permanent-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .permanent-header-section {
            background: linear-gradient(135deg, var(--permanent-telkom-red) 0%, var(--permanent-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--permanent-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--permanent-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .permanent-header-section::before {
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

        .permanent-header-section::after {
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

        .permanent-header-content {
            position: relative;
            z-index: 2;
        }

        .permanent-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .permanent-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .permanent-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .permanent-card {
            background: var(--permanent-telkom-white);
            border: none;
            border-radius: var(--permanent-border-radius);
            box-shadow: var(--permanent-shadow-light);
            transition: var(--permanent-transition);
            overflow: hidden;
            border-left: 4px solid var(--permanent-telkom-red);
            margin-bottom: 2rem;
        }

        .permanent-card:hover {
            box-shadow: var(--permanent-shadow-medium);
            transform: translateY(-2px);
        }

        .permanent-card-header {
            background: linear-gradient(135deg, var(--permanent-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--permanent-telkom-red);
            padding: 1.5rem;
            border-radius: var(--permanent-border-radius) var(--permanent-border-radius) 0 0 !important;
        }

        .permanent-card-title {
            color: var(--permanent-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .permanent-card-body {
            padding: 2rem;
        }

        /* Info Grid */
        .permanent-info-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .permanent-info-item {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            border-radius: var(--permanent-border-radius-small);
            padding: 1rem;
        }

        .permanent-info-label {
            font-weight: 600;
            color: var(--permanent-telkom-red);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .permanent-info-value {
            color: var(--permanent-telkom-dark-gray);
            background: white;
            padding: 0.75rem;
            border-radius: var(--permanent-border-radius-small);
            border: 1px solid #e0e0e0;
            word-wrap: break-word;
        }

        /* Form Styles */
        .permanent-form-group {
            margin-bottom: 1.5rem;
        }

        .permanent-form-label {
            font-weight: 500;
            color: var(--permanent-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .permanent-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--permanent-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--permanent-transition);
            background: #fafafa;
            width: 100%;
        }

        .permanent-form-control:focus {
            border-color: var(--permanent-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .permanent-form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Styles */
        .permanent-btn {
            border-radius: var(--permanent-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--permanent-transition);
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
        }

        .permanent-btn-primary {
            background: linear-gradient(135deg, var(--permanent-telkom-red) 0%, var(--permanent-telkom-dark-red) 100%);
            color: white;
        }

        .permanent-btn-primary:hover {
            background: linear-gradient(135deg, var(--permanent-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--permanent-shadow-medium);
            color: white;
        }

        .permanent-btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .permanent-btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
        }

        .permanent-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .permanent-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            color: white;
        }

        /* File Upload Styles */
        .permanent-file-upload {
            position: relative;
            width: 100%;
        }

        .permanent-file-upload-label {
            display: block;
            border: 2px dashed #e9ecef;
            border-radius: var(--permanent-border-radius-small);
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--permanent-transition);
            background: #fafafa;
        }

        .permanent-file-upload-label:hover {
            border-color: var(--permanent-telkom-red);
            background: var(--permanent-telkom-light-red);
        }

        .permanent-file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            right: 0;
            bottom: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .permanent-file-upload-icon {
            font-size: 2.5rem;
            color: var(--permanent-telkom-red);
            margin-bottom: 1rem;
        }

        .permanent-file-upload-text {
            color: var(--permanent-telkom-dark-gray);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .permanent-file-upload-info {
            color: #6c757d;
            font-size: 0.85rem;
        }

        /* File Selected */
        .permanent-file-selected {
            margin-top: 1rem;
            padding: 0.75rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: var(--permanent-border-radius-small);
            display: none;
        }

        .permanent-file-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--permanent-telkom-dark-gray);
        }

        /* Alert Styles */
        .permanent-alert {
            border: none;
            border-radius: var(--permanent-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .permanent-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .permanent-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .permanent-alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .permanent-alert-info {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        /* Loading Animation */
        .permanent-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: permanent-spin 1s ease-in-out infinite;
        }

        @keyframes permanent-spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .permanent-main-content {
                padding: 110px 20px 25px;
            }
            
            .permanent-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .permanent-main-content {
                padding: 110px 15px 25px;
            }
            
            .permanent-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .permanent-header-title {
                font-size: 1.8rem;
            }
            
            .permanent-header-subtitle {
                font-size: 1rem;
            }
            
            .permanent-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .permanent-main-content {
                padding: 110px 10px 25px;
            }
            
            .permanent-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .permanent-header-title {
                font-size: 1.5rem;
            }
            
            .permanent-card-body {
                padding: 1rem;
            }

            .permanent-file-upload-label {
                padding: 1.5rem 1rem;
            }

            .permanent-file-upload-icon {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .permanent-header-title {
                font-size: 1.3rem;
            }
            
            .permanent-card-body {
                padding: 0.75rem;
            }
            
            .permanent-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .permanent-file-upload-label {
                padding: 1rem;
            }

            .permanent-file-upload-icon {
                font-size: 1.5rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .permanent-btn:hover,
            .permanent-card:hover,
            .permanent-file-upload-label:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .permanent-header-section,
            .permanent-btn {
                display: none !important;
            }
            
            .permanent-main-content {
                padding: 0;
            }
            
            .permanent-card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <?php 
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Teknisi';
        $userRole = 'teknisi';
        include('../../includes/sidebar.php'); 
        showSidebar($userRole);
    ?>

    <div class="content-wrapper">
        <?php include('../../includes/topbar.php'); ?>
        <?php showTopbar($userRole, $username); ?>
        
        <div class="permanent-main-content">
            <!-- Header Section -->
            <div class="permanent-header-section">
                <div class="permanent-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="permanent-header-content">
                    <h1 class="permanent-header-title">
                        <i class="fas fa-tools me-3"></i>
                        Perbaikan Permanen
                    </h1>
                    <p class="permanent-header-subtitle">
                        Lengkapi formulir untuk melaporkan perbaikan permanen - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="permanent-alert permanent-alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="permanent-alert permanent-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="permanent-alert permanent-alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <!-- Tiket Info Card -->
                <div class="col-lg-5">
                    <div class="permanent-card h-100">
                        <div class="permanent-card-header">
                            <h5 class="permanent-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informasi Tiket
                            </h5>
                        </div>
                        <div class="permanent-card-body">
                            <div class="permanent-info-grid">
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-hashtag"></i>
                                        ID Tiket
                                    </div>
                                    <div class="permanent-info-value">
                                        <strong>#<?= $data['id_tiket'] ?></strong>
                                    </div>
                                </div>
                                
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Jenis Gangguan
                                    </div>
                                    <div class="permanent-info-value">
                                        <?= htmlspecialchars($data['jenis_gangguan']) ?>
                                    </div>
                                </div>
                                
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Lokasi
                                    </div>
                                    <div class="permanent-info-value">
                                        <?= htmlspecialchars($data['alamat']) ?>
                                    </div>
                                </div>
                                
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-file-alt"></i>
                                        Deskripsi
                                    </div>
                                    <div class="permanent-info-value">
                                        <?= htmlspecialchars($data['deskripsi']) ?>
                                    </div>
                                </div>
                                
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-calendar-plus"></i>
                                        Tanggal Tiket
                                    </div>
                                    <div class="permanent-info-value">
                                        <?= date('d M Y H:i', strtotime($data['created_at'])) ?>
                                    </div>
                                </div>
                                
                                <div class="permanent-info-item">
                                    <div class="permanent-info-label">
                                        <i class="fas fa-sticky-note"></i>
                                        Catatan Perbaikan Temporary
                                    </div>
                                    <div class="permanent-info-value">
                                        <?= htmlspecialchars($data['catatan_lama']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(!empty($data['latitude']) && !empty($data['longitude'])): ?>
                                <a href="https://www.google.com/maps?q=<?= $data['latitude'] ?>,<?= $data['longitude'] ?>" 
                                   class="permanent-btn permanent-btn-info" target="_blank">
                                    <i class="fas fa-map-marked-alt"></i>Lihat di Google Maps
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Form Card -->
                <div class="col-lg-7">
                    <div class="permanent-card h-100">
                        <div class="permanent-card-header">
                            <h5 class="permanent-card-title">
                                <i class="fas fa-clipboard-list"></i>
                                Formulir Perbaikan Permanen
                            </h5>
                        </div>
                        <div class="permanent-card-body">
                            <form method="POST" enctype="multipart/form-data" id="permanentForm">
                                <div class="permanent-form-group">
                                    <label for="catatan" class="permanent-form-label">
                                        <i class="fas fa-file-alt me-2"></i>Catatan Perbaikan <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="permanent-form-control permanent-form-textarea" id="catatan" name="catatan" required 
                                        placeholder="Jelaskan secara detail perbaikan permanen yang dilakukan..."><?= isset($_POST['catatan']) ? htmlspecialchars($_POST['catatan']) : '' ?></textarea>
                                    <small class="text-muted mt-2 d-block">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Berikan penjelasan yang jelas dan lengkap tentang perbaikan yang dilakukan.
                                    </small>
                                </div>
                                
                                <div class="permanent-form-group">
                                    <label class="permanent-form-label">
                                        <i class="fas fa-camera me-2"></i>Dokumentasi <span class="text-danger">*</span>
                                    </label>
                                    <div class="permanent-file-upload">
                                        <label for="dokumentasi" class="permanent-file-upload-label">
                                            <div class="permanent-file-upload-icon">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                            </div>
                                            <div class="permanent-file-upload-text">
                                                Pilih file foto atau seret ke sini
                                            </div>
                                            <div class="permanent-file-upload-info">
                                                Format yang didukung: JPG, JPEG, PNG (Maksimal 5MB)
                                            </div>
                                        </label>
                                        <input type="file" class="permanent-file-upload-input" id="dokumentasi" name="dokumentasi" required accept="image/*">
                                        <div id="file-selected" class="permanent-file-selected">
                                            <div class="permanent-file-name">
                                                <i class="fas fa-file-image text-success me-2"></i>
                                                <span id="file-name"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="permanent-alert permanent-alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Setelah menyimpan, status perbaikan akan diubah dari Temporary menjadi Permanent.
                                </div>
                                
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <a href="view_tiket.php" class="permanent-btn permanent-btn-secondary">
                                            <i class="fas fa-arrow-left"></i>Kembali
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="permanent-btn permanent-btn-primary">
                                            <i class="fas fa-save"></i>Simpan Perbaikan Permanen
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // File input handling
            const fileInput = document.getElementById('dokumentasi');
            const fileSelected = document.getElementById('file-selected');
            const fileName = document.getElementById('file-name');
            const uploadLabel = document.querySelector('.permanent-file-upload-label');
            
            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    fileName.textContent = file.name;
                    fileSelected.style.display = 'block';
                    uploadLabel.style.borderColor = 'var(--permanent-telkom-red)';
                    uploadLabel.style.background = 'var(--permanent-telkom-light-red)';
                } else {
                    fileSelected.style.display = 'none';
                    uploadLabel.style.borderColor = '#e9ecef';
                    uploadLabel.style.background = '#fafafa';
                }
            });

            // Form validation and loading state
            const permanentForm = document.getElementById('permanentForm');
            permanentForm.addEventListener('submit', function(e) {
                const catatan = document.getElementById('catatan').value.trim();
                const dokumentasi = document.getElementById('dokumentasi').files[0];
                
                if (!catatan) {
                    e.preventDefault();
                    alert('Catatan perbaikan harus diisi!');
                    return false;
                }
                
                if (!dokumentasi) {
                    e.preventDefault();
                    alert('Dokumentasi foto harus diupload!');
                    return false;
                }

                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="permanent-loading"></span> Menyimpan...';
                submitBtn.disabled = true;

                // Re-enable after 10 seconds in case of error
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            });

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.permanent-alert');
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
