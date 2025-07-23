<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('supervisor');

// Get supervisor data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Supervisor';
$userRole = 'supervisor';
$id_supervisor = $_SESSION['user_id'];

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $telegram_chat_id = htmlspecialchars($_POST['telegram_chat_id']);
    $foto = null;

    // Handle file upload
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $filename = $_FILES['foto']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $new_filename = "user_" . $id_supervisor . "_" . uniqid() . "." . $ext;
            $upload_path = "../../uploads/users/" . $new_filename;

            if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                // Delete old photo if exists and not default
                $stmt = $pdo->prepare("SELECT foto FROM supervisor WHERE id_supervisor = ?");
                $stmt->execute([$id_supervisor]);
                $old_photo = $stmt->fetchColumn();

                if ($old_photo && $old_photo != 'default-avatar.jpg' && file_exists("../../uploads/users/" . $old_photo)) {
                    unlink("../../uploads/users/" . $old_photo);
                }
                $foto = $new_filename;
            } else {
                $_SESSION['error'] = "Gagal mengupload foto!";
            }
        } else {
            $_SESSION['error'] = "Format file tidak didukung! Gunakan JPG, JPEG, atau PNG.";
        }
    }

    // Update profile
    if ($foto) {
        $stmt = $pdo->prepare("UPDATE supervisor SET nama_lengkap = ?, telegram_chat_id = ?, foto = ? WHERE id_supervisor = ?");
        $stmt->execute([$nama_lengkap, $telegram_chat_id, $foto, $id_supervisor]);
    } else {
        $stmt = $pdo->prepare("UPDATE supervisor SET nama_lengkap = ?, telegram_chat_id = ? WHERE id_supervisor = ?");
        $stmt->execute([$nama_lengkap, $telegram_chat_id, $id_supervisor]);
    }

    $_SESSION['success'] = "✅ Profile berhasil diperbarui!";
    header('Location: profile.php');
    exit();
}

// Get supervisor data
$stmt = $pdo->prepare("SELECT * FROM supervisor WHERE id_supervisor = ?");
$stmt->execute([$id_supervisor]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan!";
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profile - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --profile-telkom-red: #E31E24;
            --profile-telkom-dark-red: #B71C1C;
            --profile-telkom-light-red: #FFEBEE;
            --profile-telkom-gray: #F5F5F5;
            --profile-telkom-dark-gray: #424242;
            --profile-telkom-white: #FFFFFF;
            --profile-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --profile-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --profile-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --profile-border-radius: 12px;
            --profile-border-radius-small: 8px;
            --profile-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .profile-main-content {
            padding: 110px 25px 25px;
            transition: var(--profile-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .profile-header-section {
            background: linear-gradient(135deg, var(--profile-telkom-red) 0%, var(--profile-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--profile-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--profile-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .profile-header-section::before {
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

        .profile-header-section::after {
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

        .profile-header-content {
            position: relative;
            z-index: 2;
        }

        .profile-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .profile-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .profile-card {
            background: var(--profile-telkom-white);
            border: none;
            border-radius: var(--profile-border-radius);
            box-shadow: var(--profile-shadow-light);
            transition: var(--profile-transition);
            overflow: hidden;
            border-left: 4px solid var(--profile-telkom-red);
            margin-bottom: 2rem;
        }

        .profile-card:hover {
            box-shadow: var(--profile-shadow-medium);
            transform: translateY(-2px);
        }

        .profile-card-header {
            background: linear-gradient(135deg, var(--profile-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--profile-telkom-red);
            padding: 1.5rem;
            border-radius: var(--profile-border-radius) var(--profile-border-radius) 0 0 !important;
        }

        .profile-card-title {
            color: var(--profile-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .profile-card-body {
            padding: 2rem;
        }

        /* Profile Photo Section */
        .profile-photo-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--profile-telkom-red);
            box-shadow: var(--profile-shadow-medium);
        }

        .profile-photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--profile-transition);
            cursor: pointer;
        }

        .profile-photo-overlay:hover {
            opacity: 1;
        }

        .profile-photo-overlay i {
            color: white;
            font-size: 2rem;
        }

        /* Form Styles */
        .profile-form-group {
            margin-bottom: 1.5rem;
        }

        .profile-form-label {
            font-weight: 500;
            color: var(--profile-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .profile-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--profile-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--profile-transition);
            background: #fafafa;
            width: 100%;
        }

        .profile-form-control:focus {
            border-color: var(--profile-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .profile-form-control:disabled {
            background: #f8f9fa;
            color: #6c757d;
        }

        /* Button Styles */
        .profile-btn {
            border-radius: var(--profile-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--profile-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .profile-btn-primary {
            background: linear-gradient(135deg, var(--profile-telkom-red) 0%, var(--profile-telkom-dark-red) 100%);
            color: white;
            width: 100%;
            justify-content: center;
        }

        .profile-btn-primary:hover {
            background: linear-gradient(135deg, var(--profile-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--profile-shadow-medium);
            color: white;
        }

        .profile-btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            width: 100%;
            justify-content: center;
        }

        .profile-btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
            color: white;
        }

        /* Info Display */
        .profile-info-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: var(--profile-border-radius-small);
            margin-bottom: 1rem;
            border-left: 4px solid var(--profile-telkom-red);
        }

        .profile-info-icon {
            width: 40px;
            height: 40px;
            background: var(--profile-telkom-red);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }

        .profile-info-content {
            flex: 1;
        }

        .profile-info-label {
            font-weight: 600;
            color: var(--profile-telkom-dark-gray);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .profile-info-value {
            color: #333;
            font-size: 1rem;
        }

        /* File Upload Styles */
        .profile-file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }

        .profile-file-input {
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

        /* Alert Styles */
        .profile-alert {
            border: none;
            border-radius: var(--profile-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .profile-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .profile-alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Loading Animation */
        .profile-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: profile-spin 1s ease-in-out infinite;
        }

        @keyframes profile-spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .profile-main-content {
                padding: 110px 20px 25px;
            }
            
            .profile-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .profile-main-content {
                padding: 110px 15px 25px;
            }
            
            .profile-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .profile-header-title {
                font-size: 1.8rem;
            }
            
            .profile-header-subtitle {
                font-size: 1rem;
            }
            
            .profile-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .profile-main-content {
                padding: 110px 10px 25px;
            }
            
            .profile-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .profile-header-title {
                font-size: 1.5rem;
            }
            
            .profile-card-body {
                padding: 1rem;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }

            .profile-info-item {
                flex-direction: column;
                text-align: center;
            }

            .profile-info-icon {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 576px) {
            .profile-header-title {
                font-size: 1.3rem;
            }
            
            .profile-card-body {
                padding: 0.75rem;
            }
            
            .profile-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .profile-photo {
                width: 100px;
                height: 100px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .profile-btn:hover,
            .profile-card:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .profile-header-section,
            .profile-btn {
                display: none !important;
            }
            
            .profile-main-content {
                padding: 0;
            }
            
            .profile-card {
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

        <div class="profile-main-content">
            <!-- Header Section -->
            <div class="profile-header-section">
                <div class="profile-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="profile-header-content">
                    <h1 class="profile-header-title">
                        <i class="fas fa-user-circle me-3"></i>
                        Profile Supervisor
                    </h1>
                    <p class="profile-header-subtitle">
                        Kelola informasi profile dan pengaturan akun - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <div class="row g-4">
                <!-- Profile Info Card -->
                <div class="col-lg-4">
                    <div class="profile-card h-100">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informasi Profile
                            </h5>
                        </div>
                        <div class="profile-card-body">
                            <div class="profile-photo-section">
                                <div class="profile-photo-container">
                                    <img src="../../uploads/users/<?= htmlspecialchars($user['foto']) ?>" 
                                         alt="Profile Photo" 
                                         class="profile-photo"
                                         id="currentPhoto"
                                         onerror="this.src='../../uploads/users/default-avatar.jpg'">
                                </div>
                                <h5><?= htmlspecialchars($user['username']) ?></h5>
                                <p class="text-muted">Supervisor</p>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-hashtag"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">ID Supervisor</div>
                                    <div class="profile-info-value">#<?= $user['id_supervisor'] ?></div>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">Bergabung Sejak</div>
                                    <div class="profile-info-value"><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="profile-info-item">
                                <div class="profile-info-icon">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="profile-info-content">
                                    <div class="profile-info-label">Role</div>
                                    <div class="profile-info-value">Supervisor</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Edit Profile Form -->
                <div class="col-lg-8">
                    <div class="profile-card h-100">
                        <div class="profile-card-header">
                            <h5 class="profile-card-title">
                                <i class="fas fa-edit"></i>
                                Edit Profile
                            </h5>
                        </div>
                        <div class="profile-card-body">
                            <form id="profileForm" enctype="multipart/form-data" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="profile-form-group">
                                            <label for="username" class="profile-form-label">
                                                <i class="fas fa-user me-2"></i>Username
                                            </label>
                                            <input type="text" class="profile-form-control" id="username" 
                                                   value="<?= htmlspecialchars($user['username']) ?>" disabled>
                                            <small class="text-muted">Username tidak dapat diubah</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="profile-form-group">
                                            <label for="role" class="profile-form-label">
                                                <i class="fas fa-shield-alt me-2"></i>Role
                                            </label>
                                            <input type="text" class="profile-form-control" id="role" 
                                                   value="Supervisor" disabled>
                                            <small class="text-muted">Role tidak dapat diubah</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="profile-form-group">
                                    <label for="nama_lengkap" class="profile-form-label">
                                        <i class="fas fa-id-card me-2"></i>Nama Lengkap *
                                    </label>
                                    <input type="text" class="profile-form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="<?= htmlspecialchars($user['nama_lengkap']) ?>" 
                                           placeholder="Masukkan nama lengkap Anda" required>
                                    <div class="validation-feedback"></div>
                                </div>
                                <div class="profile-form-group">
                                    <label for="telegram_chat_id" class="profile-form-label">
                                        <i class="fab fa-telegram me-2"></i>Telegram Chat ID
                                    </label>
                                    <input type="text" class="profile-form-control" id="telegram_chat_id" name="telegram_chat_id" 
                                           value="<?= htmlspecialchars($user['telegram_chat_id']) ?>" 
                                           placeholder="Masukkan Telegram Chat ID">
                                    <small class="text-muted">Chat ID digunakan untuk notifikasi Telegram</small>
                                    <div class="validation-feedback"></div>
                                </div>
                                <div class="profile-form-group">
                                    <label for="foto" class="profile-form-label">
                                        <i class="fas fa-camera me-2"></i>Foto Profile
                                    </label>
                                    <div class="profile-file-upload">
                                        <input type="file" class="profile-file-input" id="foto" name="foto" accept="image/*">
                                        <div class="profile-form-control" style="cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                            <i class="fas fa-upload"></i>
                                            <span id="file-name">Pilih file foto baru</span>
                                        </div>
                                    </div>
                                    <small class="text-muted">Format yang didukung: JPG, JPEG, PNG (Max: 2MB)</small>
                                    <div class="validation-feedback"></div>
                                </div>
                                <div class="row g-3 mt-3">
                                    <div class="col-md-6">
                                        <a href="index.php" class="profile-btn profile-btn-secondary">
                                            <i class="fas fa-arrow-left"></i>Kembali ke Dashboard
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <button type="submit" class="profile-btn profile-btn-primary" id="submitBtn">
                                            <i class="fas fa-save"></i>
                                            <span class="btn-text">Simpan Perubahan</span>
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
            const profileForm = document.getElementById('profileForm');
            const submitBtn = document.getElementById('submitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const fileInput = document.getElementById('foto');
            const fileName = document.getElementById('file-name');

            fileInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (file.size > 2 * 1024 * 1024) {
                        showNotification('error', 'Ukuran file terlalu besar! Maksimal 2MB.');
                        this.value = '';
                        fileName.textContent = 'Pilih file foto baru';
                        return;
                    }
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (!allowedTypes.includes(file.type)) {
                        showNotification('error', 'Format file tidak didukung! Gunakan JPG, JPEG, atau PNG.');
                        this.value = '';
                        fileName.textContent = 'Pilih file foto baru';
                        return;
                    }
                    fileName.textContent = file.name;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('currentPhoto').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    fileName.textContent = 'Pilih file foto baru';
                }
            });

            document.getElementById('nama_lengkap').addEventListener('input', function() {
                validateField(this, this.value.trim().length >= 3, 'Nama lengkap minimal 3 karakter');
            });

            document.getElementById('telegram_chat_id').addEventListener('input', function() {
                const value = this.value.trim();
                if (value === '') {
                    clearValidation(this);
                } else {
                    validateField(this, /^\d+$/.test(value), 'Chat ID harus berupa angka');
                }
            });

            // NON-AJAX
            // AJAX code di-nonaktifkan, karena form method POST-submit langsung ke PHP.

            function validateForm() {
                let isValid = true;
                const namaLengkap = document.getElementById('nama_lengkap').value.trim();
                if (namaLengkap.length < 3) {
                    validateField(document.getElementById('nama_lengkap'), false, 'Nama lengkap minimal 3 karakter');
                    isValid = false;
                }
                const telegramChatId = document.getElementById('telegram_chat_id').value.trim();
                if (telegramChatId && !/^\d+$/.test(telegramChatId)) {
                    validateField(document.getElementById('telegram_chat_id'), false, 'Chat ID harus berupa angka');
                    isValid = false;
                }
                return isValid;
            }

            function validateField(field, isValid, message) {
                const feedback = field.parentNode.querySelector('.validation-feedback');
                field.classList.remove('is-valid', 'is-invalid');
                if (isValid) {
                    field.classList.add('is-valid');
                    feedback.textContent = '';
                } else {
                    field.classList.add('is-invalid');
                    feedback.textContent = message;
                }
            }

            function clearValidation(field) {
                field.classList.remove('is-valid', 'is-invalid');
                const feedback = field.parentNode.querySelector('.validation-feedback');
                feedback.textContent = '';
            }

            function showNotification(type, message) {
                const existingNotifications = document.querySelectorAll('.notification');
                existingNotifications.forEach(notification => notification.remove());
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
                    ${message}
                `;
                document.body.appendChild(notification);
                setTimeout(() => notification.classList.add('show'), 100);
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }, 5000);
            }

            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    profileForm.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>
