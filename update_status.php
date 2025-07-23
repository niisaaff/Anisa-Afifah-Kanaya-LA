<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('teknisi');

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Teknisi';
$userRole = 'teknisi';
$teknisi_id = $_SESSION['user_id']; // Ambil ID teknisi dari session

$tiket_id = (int)$_GET['tiket_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $jenis_perbaikan = $_POST['jenis_perbaikan'];
    $catatan = htmlspecialchars($_POST['catatan']);

    // Update status tiket
    $stmt = $pdo->prepare("UPDATE tiket SET status = ? WHERE id_tiket = ?");
    $stmt->execute([$status, $tiket_id]);

    // Simpan laporan ke pending approval jika status selesai
    if ($status === 'selesai') {
        $dokumentasi = [];
        foreach ($_FILES['dokumentasi']['tmp_name'] as $key => $tmp_name) {
            if (!empty($tmp_name)) {
                $target_dir = "../../uploads/";
                $filename = uniqid() . "_" . basename($_FILES['dokumentasi']['name'][$key]);
                move_uploaded_file($tmp_name, $target_dir . $filename);
                $dokumentasi[] = $filename;
            }
        }

        // Simpan ke tabel laporan_pending untuk approval
        $stmt = $pdo->prepare("
            INSERT INTO laporan_pending (id_tiket, id_teknisi, jenis_perbaikan, dokumentasi, catatan, status_approval) 
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $tiket_id,
            $teknisi_id,
            $jenis_perbaikan,
            implode(',', $dokumentasi),
            $catatan
        ]);

        $_SESSION['success'] = "✅ Laporan berhasil dikirim untuk approval supervisor!";
    } else {
        $_SESSION['success'] = "✅ Status berhasil diperbarui!";
    }

    redirect("view_tiket.php");
}

// Ambil data tiket
$stmt = $pdo->prepare("
    SELECT t.*, l.alamat 
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    WHERE t.id_tiket = ?
");
$stmt->execute([$tiket_id]);
$tiket = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Update Status Tiket - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --update-telkom-red: #E31E24;
            --update-telkom-dark-red: #B71C1C;
            --update-telkom-light-red: #FFEBEE;
            --update-telkom-gray: #F5F5F5;
            --update-telkom-dark-gray: #424242;
            --update-telkom-white: #FFFFFF;
            --update-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --update-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --update-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --update-border-radius: 12px;
            --update-border-radius-small: 8px;
            --update-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .update-main-content {
            padding: 110px 25px 25px;
            transition: var(--update-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .update-header-section {
            background: linear-gradient(135deg, var(--update-telkom-red) 0%, var(--update-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--update-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--update-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .update-header-section::before {
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

        .update-header-section::after {
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

        .update-header-content {
            position: relative;
            z-index: 2;
        }

        .update-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .update-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .update-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Breadcrumb */
        .update-breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 2rem;
        }

        .update-breadcrumb-item {
            color: var(--update-telkom-dark-gray);
        }

        .update-breadcrumb-item a {
            color: var(--update-telkom-red);
            text-decoration: none;
            transition: var(--update-transition);
        }

        .update-breadcrumb-item a:hover {
            color: var(--update-telkom-dark-red);
        }

        .update-breadcrumb-item.active {
            color: #6c757d;
        }

        .update-breadcrumb-item + .update-breadcrumb-item::before {
            content: "›";
            color: #6c757d;
            padding: 0 0.5rem;
        }

        /* Card Styles */
        .update-card {
            background: var(--update-telkom-white);
            border: none;
            border-radius: var(--update-border-radius);
            box-shadow: var(--update-shadow-light);
            transition: var(--update-transition);
            overflow: hidden;
            border-left: 4px solid var(--update-telkom-red);
            margin-bottom: 2rem;
        }

        .update-card:hover {
            box-shadow: var(--update-shadow-medium);
            transform: translateY(-2px);
        }

        .update-card-header {
            background: linear-gradient(135deg, var(--update-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--update-telkom-red);
            padding: 1.5rem;
            border-radius: var(--update-border-radius) var(--update-border-radius) 0 0 !important;
        }

        .update-card-title {
            color: var(--update-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-card-body {
            padding: 2rem;
        }

        /* Tiket Info Styles */
        .update-tiket-info-item {
            display: flex;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: var(--update-border-radius-small);
            border-left: 3px solid var(--update-telkom-red);
        }

        .update-tiket-info-label {
            font-weight: 600;
            min-width: 140px;
            color: var(--update-telkom-dark-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-tiket-info-value {
            color: #333;
            flex: 1;
        }

        /* Status Badge */
        .update-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }

        .update-status-open {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .update-status-progress {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        /* Form Styles */
        .update-form-group {
            margin-bottom: 1.5rem;
        }

        .update-form-label {
            font-weight: 500;
            color: var(--update-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .update-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--update-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--update-transition);
            background: #fafafa;
            width: 100%;
        }

        .update-form-control:focus {
            border-color: var(--update-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .update-form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* Button Styles */
        .update-btn {
            border-radius: var(--update-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--update-transition);
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

        .update-btn-primary {
            background: linear-gradient(135deg, var(--update-telkom-red) 0%, var(--update-telkom-dark-red) 100%);
            color: white;
        }

        .update-btn-primary:hover {
            background: linear-gradient(135deg, var(--update-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--update-shadow-medium);
            color: white;
        }

        .update-btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .update-btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
        }

        /* File Upload Styles */
        .update-file-upload {
            position: relative;
            width: 100%;
        }

        .update-file-upload-label {
            display: block;
            border: 2px dashed #e9ecef;
            border-radius: var(--update-border-radius-small);
            padding: 2rem 1rem;
            text-align: center;
            cursor: pointer;
            transition: var(--update-transition);
            background: #fafafa;
        }

        .update-file-upload-label:hover {
            border-color: var(--update-telkom-red);
            background: var(--update-telkom-light-red);
        }

        .update-file-upload-input {
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

        .update-file-upload-icon {
            font-size: 2.5rem;
            color: var(--update-telkom-red);
            margin-bottom: 1rem;
        }

        .update-file-upload-text {
            color: var(--update-telkom-dark-gray);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .update-file-upload-info {
            color: #6c757d;
            font-size: 0.85rem;
        }

        /* File List */
        .update-file-list {
            margin-top: 1rem;
        }

        .update-file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: var(--update-border-radius-small);
            margin-bottom: 0.5rem;
        }

        .update-file-item:last-child {
            margin-bottom: 0;
        }

        .update-file-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .update-file-size {
            background: var(--update-telkom-red);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        /* Hidden Section */
        .update-hidden-section {
            display: none;
            animation: updateFadeIn 0.5s ease-in-out;
        }

        @keyframes updateFadeIn {
            from { 
                opacity: 0; 
                transform: translateY(-10px);
            }
            to { 
                opacity: 1; 
                transform: translateY(0);
            }
        }

        /* Loading Animation */
        .update-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: update-spin 1s ease-in-out infinite;
        }

        @keyframes update-spin {
            to { transform: rotate(360deg); }
        }

        /* Alert Box */
        .update-alert {
            padding: 1rem 1.5rem;
            border-radius: var(--update-border-radius-small);
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }

        .update-alert-info {
            background: #e3f2fd;
            border-color: #2196f3;
            color: #0d47a1;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .update-main-content {
                padding: 110px 20px 25px;
            }
            
            .update-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .update-main-content {
                padding: 110px 15px 25px;
            }
            
            .update-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .update-header-title {
                font-size: 1.8rem;
            }
            
            .update-header-subtitle {
                font-size: 1rem;
            }
            
            .update-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .update-main-content {
                padding: 110px 10px 25px;
            }
            
            .update-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .update-header-title {
                font-size: 1.5rem;
            }
            
            .update-card-body {
                padding: 1rem;
            }

            .update-tiket-info-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .update-tiket-info-label {
                min-width: auto;
                margin-bottom: 0.5rem;
            }

            .update-file-upload-label {
                padding: 1.5rem 1rem;
            }

            .update-file-upload-icon {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .update-header-title {
                font-size: 1.3rem;
            }
            
            .update-card-body {
                padding: 0.75rem;
            }
            
            .update-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .update-file-upload-label {
                padding: 1rem;
            }

            .update-file-upload-icon {
                font-size: 1.5rem;
            }

            .update-file-item {
                padding: 0.5rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .update-btn:hover,
            .update-card:hover,
            .update-file-upload-label:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .update-header-section,
            .update-btn {
                display: none !important;
            }
            
            .update-main-content {
                padding: 0;
            }
            
            .update-card {
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
        
        <div class="update-main-content">
            <!-- Header Section -->
            <div class="update-header-section">
                <div class="update-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="update-header-content">
                    <h1 class="update-header-title">
                        <i class="fas fa-edit me-3"></i>
                        Update Status Tiket #<?= $tiket_id ?>
                    </h1>
                    <p class="update-header-subtitle">
                        Perbarui status dan buat laporan perbaikan - PT Telkom Akses
                    </p>
                </div>
            </div>

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="update-breadcrumb d-flex list-unstyled">
                    <li class="update-breadcrumb-item">
                        <a href="view_tiket.php">
                            <i class="fas fa-ticket-alt me-1"></i>Tiket Aktif
                        </a>
                    </li>
                    <li class="update-breadcrumb-item active">Update Status Tiket #<?= $tiket_id ?></li>
                </ol>
            </nav>

            <!-- Alert Info -->
            <div class="update-alert update-alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Informasi:</strong> Laporan perbaikan yang Anda kirim akan diteruskan ke supervisor untuk proses approval sebelum disimpan ke dalam sistem.
            </div>
            
            <div class="row g-4">
                <!-- Tiket Info Card -->
                <div class="col-lg-5">
                    <div class="update-card h-100">
                        <div class="update-card-header">
                            <h5 class="update-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informasi Tiket
                            </h5>
                        </div>
                        <div class="update-card-body">
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-hashtag"></i>ID Tiket:
                                </span>
                                <span class="update-tiket-info-value">
                                    <strong>#<?= $tiket_id ?></strong>
                                </span>
                            </div>
                            
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-exclamation-triangle"></i>Jenis Gangguan:
                                </span>
                                <span class="update-tiket-info-value">
                                    <?= htmlspecialchars($tiket['jenis_gangguan']) ?>
                                </span>
                            </div>
                            
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-map-marker-alt"></i>Lokasi:
                                </span>
                                <span class="update-tiket-info-value">
                                    <?= htmlspecialchars($tiket['alamat']) ?>
                                </span>
                            </div>
                            
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-calendar-plus"></i>Tanggal Dibuat:
                                </span>
                                <span class="update-tiket-info-value">
                                    <?= date('d M Y H:i', strtotime($tiket['created_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-flag"></i>Status Saat Ini:
                                </span>
                                <span class="update-tiket-info-value">
                                    <span class="update-status-badge <?= $tiket['status'] == 'open' ? 'update-status-open' : 'update-status-progress' ?>">
                                        <i class="fas <?= $tiket['status'] == 'open' ? 'fa-exclamation-circle' : 'fa-cogs' ?>"></i>
                                        <?= ucfirst($tiket['status']) ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="update-tiket-info-item">
                                <span class="update-tiket-info-label">
                                    <i class="fas fa-file-alt"></i>Deskripsi:
                                </span>
                                <span class="update-tiket-info-value">
                                    <?= htmlspecialchars($tiket['deskripsi']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Update Status -->
                <div class="col-lg-7">
                    <div class="update-card h-100">
                        <div class="update-card-header">
                            <h5 class="update-card-title">
                                <i class="fas fa-tasks"></i>
                                Update Status Tiket
                            </h5>
                        </div>
                        <div class="update-card-body">
                            <form method="POST" enctype="multipart/form-data" id="updateForm">
                                <div class="update-form-group">
                                    <label class="update-form-label" for="status">
                                        <i class="fas fa-flag me-2"></i>Status Baru
                                    </label>
                                    <select id="status" name="status" class="update-form-control" required>
                                        <option value="on progress">On Progress</option>
                                        <option value="selesai">Selesai</option>
                                    </select>
                                </div>

                                <div id="laporan-form" class="update-hidden-section">
                                    <div class="update-form-group">
                                        <label class="update-form-label" for="jenis_perbaikan">
                                            <i class="fas fa-tools me-2"></i>Jenis Perbaikan
                                        </label>
                                        <select id="jenis_perbaikan" name="jenis_perbaikan" class="update-form-control">
                                            <option value="temporary">Temporary</option>
                                            <option value="permanent">Permanent</option>
                                        </select>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Pilih "Temporary" jika masih memerlukan perbaikan lanjutan, "Permanent" jika sudah selesai sepenuhnya.
                                        </small>
                                    </div>

                                    <div class="update-form-group">
                                        <label class="update-form-label">
                                            <i class="fas fa-camera me-2"></i>Dokumentasi (Maksimal 5 file)
                                        </label>
                                        <div class="update-file-upload">
                                            <label for="dokumentasi" class="update-file-upload-label">
                                                <div class="update-file-upload-icon">
                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                </div>
                                                <div class="update-file-upload-text">
                                                    Pilih file atau seret ke sini
                                                </div>
                                                <div class="update-file-upload-info">
                                                    Format yang didukung: JPG, PNG, JPEG (Maksimal 5MB per file)
                                                </div>
                                            </label>
                                            <input id="dokumentasi" type="file" name="dokumentasi[]" multiple accept="image/*" class="update-file-upload-input">
                                            <div id="file-list" class="update-file-list"></div>
                                        </div>
                                    </div>

                                    <div class="update-form-group">
                                        <label class="update-form-label" for="catatan">
                                            <i class="fas fa-file-alt me-2"></i>Catatan Perbaikan
                                        </label>
                                        <textarea id="catatan" name="catatan" class="update-form-control update-form-textarea" 
                                                  placeholder="Tuliskan detail perbaikan yang telah dilakukan..." required></textarea>
                                    </div>
                                </div>

                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <a href="view_tiket.php" class="update-btn update-btn-secondary">
                                            <i class="fas fa-arrow-left"></i>Kembali
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="update-btn update-btn-primary">
                                            <i class="fas fa-save"></i>Simpan Perubahan
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
            // Tampilkan form laporan saat status 'selesai'
            const statusSelect = document.getElementById('status');
            const laporanForm = document.getElementById('laporan-form');
            
            statusSelect.addEventListener('change', function(e) {
                if (e.target.value === 'selesai') {
                    laporanForm.style.display = 'block';
                    laporanForm.classList.add('update-hidden-section');
                } else {
                    laporanForm.style.display = 'none';
                }
            });
            
            // Menampilkan nama file yang dipilih
            const fileInput = document.getElementById('dokumentasi');
            const fileList = document.getElementById('file-list');
            
            fileInput.addEventListener('change', function(e) {
                fileList.innerHTML = '';
                
                if (this.files.length > 0) {
                    for (let i = 0; i < this.files.length; i++) {
                        const file = this.files[i];
                        const fileItem = document.createElement('div');
                        fileItem.className = 'update-file-item';
                        fileItem.innerHTML = `
                            <div class="update-file-name">
                                <i class="fas fa-file-image text-secondary me-2"></i>
                                <span>${file.name}</span>
                            </div>
                            <span class="update-file-size">${(file.size / 1024).toFixed(1)} KB</span>
                        `;
                        fileList.appendChild(fileItem);
                    }
                }
            });

            // Form validation and loading state
            const updateForm = document.getElementById('updateForm');
            updateForm.addEventListener('submit', function(e) {
                const status = statusSelect.value;
                const catatan = document.getElementById('catatan').value.trim();
                
                if (status === 'selesai' && !catatan) {
                    e.preventDefault();
                    alert('Catatan perbaikan harus diisi untuk status selesai!');
                    return false;
                }

                // Add loading state
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="update-loading"></span> Menyimpan...';
                submitBtn.disabled = true;

                // Re-enable after 10 seconds in case of error
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 10000);
            });
        });

        // Touch device optimizations
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
        }
    </script>
</body>
</html>
