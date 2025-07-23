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

// Get user data for navbar
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin User';
$userRole = 'admin';

// Check if report ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['warning'] = "⚠️ ID Laporan tidak valid";
    header('Location: report.php');
    exit();
}

$report_id = $_GET['id'];

// Fetch report details with related data
$stmt = $pdo->prepare("
    SELECT 
        l.*,
        t.jenis_gangguan,
        t.deskripsi AS tiket_deskripsi,
        t.status AS tiket_status,
        t.created_at AS tiket_created_at,
        lok.alamat,
        lok.latitude,
        lok.longitude,
        u.username AS teknisi_name,
        a.username AS admin_name,
        p.created_at AS penugasan_created_at
    FROM 
        laporan l
    JOIN 
        tiket t ON l.id_tiket = t.id_tiket
    JOIN 
        lokasi lok ON t.id_lokasi = lok.id_lokasi
    LEFT JOIN 
        penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN 
        users u ON p.id_teknisi = u.id_user
    LEFT JOIN
        users a ON t.id_admin = a.id_user
    WHERE 
        l.id_laporan = ?
");
$stmt->execute([$report_id]);
$report = $stmt->fetch();

// Check if report exists
if (!$report) {
    $_SESSION['warning'] = "⚠️ Laporan tidak ditemukan";
    header('Location: report.php');
    exit();
}

// Calculate duration of repair
$ticket_created = new DateTime($report['tiket_created_at']);
$repair_completed = new DateTime($report['selesai_pada']);
$duration = $ticket_created->diff($repair_completed);

// Format duration
$duration_text = '';
if ($duration->d > 0) {
    $duration_text .= $duration->d . ' hari ';
}
if ($duration->h > 0) {
    $duration_text .= $duration->h . ' jam ';
}
if ($duration->i > 0) {
    $duration_text .= $duration->i . ' menit';
}
if ($duration_text == '') {
    $duration_text = 'Kurang dari 1 menit';
}

// Format response time if available
$response_time = '';
if (isset($report['penugasan_created_at'])) {
    $ticket_created = new DateTime($report['tiket_created_at']);
    $assignment_created = new DateTime($report['penugasan_created_at']);
    $response = $ticket_created->diff($assignment_created);
    
    if ($response->d > 0) {
        $response_time .= $response->d . ' hari ';
    }
    if ($response->h > 0) {
        $response_time .= $response->h . ' jam ';
    }
    if ($response->i > 0) {
        $response_time .= $response->i . ' menit';
    }
    if ($response_time == '') {
        $response_time = 'Kurang dari 1 menit';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Laporan - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #c0392b;
            --text-color: #333;
            --light-bg: #f5f7fa;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', sans-serif;
        }
        
        body {
            background: var(--light-bg);
            color: var(--text-color);
        }
        
        .content-wrapper {
            transition: all 0.3s;
        }
        
        .main-content {
            padding: 90px 25px 25px;
            transition: all 0.3s;
            min-height: calc(100vh - 45px);
        }
        
        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: none;
            margin-bottom: 25px;
            transition: transform 0.3s;
        }
        
        .card-bordered {
            border-left: 4px solid var(--secondary-color);
        }
        
        .card-body {
            padding: 25px;
        }
        
        .card-title {
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--secondary-color);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #900C3F, #e74c3c);
            color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--box-shadow);
        }
        
        .welcome-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .welcome-subtitle {
            font-size: 1rem;
            opacity: 0.8;
            margin-bottom: 0;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 400;
            color: var(--text-color);
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .badge-open {
            background-color: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .badge-progress {
            background-color: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .badge-selesai {
            background-color: #2ecc71;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .badge-temporary {
            background-color: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .badge-permanent {
            background-color: #2ecc71;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .documentation-img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .map-container {
            height: 300px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 2px;
            background: #e5e5e5;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-left: 40px;
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: 12px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
        }
        
        .timeline-dot.created {
            background-color: #3498db;
        }
        
        .timeline-dot.assigned {
            background-color: #f39c12;
        }
        
        .timeline-dot.completed {
            background-color: #2ecc71;
        }
        
        .timeline-content {
            background: #ffffff;
            padding: 15px;
            border-radius: var(--border-radius);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .timeline-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--primary-color);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .stat-container {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-right: 15px;
            background-color: var(--secondary-color);
        }
        
        .stat-text {
            flex: 1;
        }
        
        .stat-title {
            font-weight: 600;
            margin-bottom: 0;
            color: var(--primary-color);
        }
        
        .stat-value {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
    </style>
</head>
<body>

<?php include('../../includes/sidebar.php'); ?> 
<?php showSidebar($userRole); ?>

<div class="content-wrapper">
    <?php include('../../includes/topbar.php'); ?>
    <?php showTopbar($userRole, $username); ?>
    
    <div class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h4 class="welcome-title">Detail Laporan</h4>
            <p class="welcome-subtitle">Lihat informasi lengkap laporan perbaikan</p>
        </div>
        
        <h3 class="dashboard-title">Laporan #<?= $report_id ?></h3>
        
        <!-- Action buttons -->
        <div class="mb-4">
            <a href="report.php" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <a href="edit_report.php?id=<?= $report_id ?>" class="btn btn-primary">
                <i class="fas fa-edit mr-2"></i>Edit Laporan
            </a>
        </div>
        
        <div class="row">
            <!-- Report Information -->
            <div class="col-lg-8">
                <div class="card card-bordered">
                    <div class="card-body">
                        <h5 class="card-title">Informasi Laporan</h5>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <p class="info-label">ID Laporan:</p>
                                <p class="info-value">#<?= $report_id ?></p>
                                
                                <p class="info-label">Tiket ID:</p>
                                <p class="info-value">#<?= $report['id_tiket'] ?></p>
                                
                                <p class="info-label">Jenis Gangguan:</p>
                                <p class="info-value"><?= htmlspecialchars($report['jenis_gangguan']) ?></p>
                                
                                <p class="info-label">Status Tiket:</p>
                                <p class="info-value">
                                    <?php if ($report['tiket_status'] === 'open'): ?>
                                        <span class="badge badge-open">Terbuka</span>
                                    <?php elseif ($report['tiket_status'] === 'on progress'): ?>
                                        <span class="badge badge-progress">Dalam Proses</span>
                                    <?php else: ?>
                                        <span class="badge badge-selesai">Selesai</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            
                            <div class="col-md-6">
                                <p class="info-label">Jenis Perbaikan:</p>
                                <p class="info-value">
                                    <?php if ($report['jenis_perbaikan'] === 'temporary'): ?>
                                        <span class="badge badge-temporary">Sementara</span>
                                    <?php else: ?>
                                        <span class="badge badge-permanent">Permanen</span>
                                    <?php endif; ?>
                                </p>
                                
                                <p class="info-label">Teknisi:</p>
                                <p class="info-value"><?= htmlspecialchars($report['teknisi_name']) ?></p>
                                
                                <p class="info-label">Admin:</p>
                                <p class="info-value"><?= htmlspecialchars($report['admin_name']) ?></p>
                                
                                <p class="info-label">Selesai Pada:</p>
                                <p class="info-value"><?= date('d M Y H:i', strtotime($report['selesai_pada'])) ?></p>
                            </div>
                        </div>
                        
                        <p class="info-label">Deskripsi Gangguan:</p>
                        <p class="info-value"><?= nl2br(htmlspecialchars($report['tiket_deskripsi'])) ?></p>
                        
                        <p class="info-label">Catatan Perbaikan:</p>
                        <p class="info-value"><?= nl2br(htmlspecialchars($report['catatan'])) ?></p>
                    </div>
                </div>
                
                <!-- Documentation and Location -->
                <div class="row">
                    <!-- Documentation -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Dokumentasi</h5>
                                
                                <?php if ($report['dokumentasi']): ?>
                                    <img src="../../uploads/<?= $report['dokumentasi'] ?>" alt="Dokumentasi Perbaikan" class="documentation-img img-fluid">
                                    <a href="../../uploads/<?= $report['dokumentasi'] ?>" target="_blank" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-expand mr-2"></i>Lihat Gambar Penuh
                                    </a>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>Tidak ada dokumentasi tersedia
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Lokasi</h5>
                                
                                <div id="map" class="map-container"></div>
                                
                                <p class="info-label">Alamat:</p>
                                <p class="info-value"><?= htmlspecialchars($report['alamat']) ?></p>
                                
                                <p class="info-label">Koordinat:</p>
                                <p class="info-value">
                                    <?= $report['latitude'] ?>, <?= $report['longitude'] ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats and Timeline -->
            <div class="col-lg-4">
                <!-- Performance Stats -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Statistik Penanganan</h5>
                        
                        <div class="stat-container">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-text">
                                <p class="stat-title">Waktu Respons</p>
                                <p class="stat-value">
                                    <?= $response_time ? $response_time : 'Data tidak tersedia' ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="stat-container">
                            <div class="stat-icon">
                                <i class="fas fa-hourglass-end"></i>
                            </div>
                            <div class="stat-text">
                                <p class="stat-title">Durasi Penanganan</p>
                                <p class="stat-value"><?= $duration_text ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-container">
                            <div class="stat-icon">
                                <i class="fas fa-wrench"></i>
                            </div>
                            <div class="stat-text">
                                <p class="stat-title">Tipe Perbaikan</p>
                                <p class="stat-value">
                                    <?= $report['jenis_perbaikan'] === 'temporary' ? 'Sementara' : 'Permanen' ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="stat-container">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-text">
                                <p class="stat-title">Status Akhir</p>
                                <p class="stat-value">
                                    <?= $report['tiket_status'] === 'selesai' ? 'Selesai' : 'Belum Selesai' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Timeline -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Timeline Penanganan</h5>
                        
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-dot created"></div>
                                <div class="timeline-content">
                                    <h5 class="timeline-title">Tiket Dibuat</h5>
                                    <p class="timeline-date"><?= date('d M Y H:i', strtotime($report['tiket_created_at'])) ?></p>
                                    <p class="mb-0">Tiket gangguan dibuat oleh <?= htmlspecialchars($report['admin_name']) ?></p>
                                </div>
                            </div>
                            
                            <?php if (isset($report['penugasan_created_at'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot assigned"></div>
                                <div class="timeline-content">
                                    <h5 class="timeline-title">Penugasan Teknisi</h5>
                                    <p class="timeline-date"><?= date('d M Y H:i', strtotime($report['penugasan_created_at'])) ?></p>
                                    <p class="mb-0">Tiket ditugaskan kepada <?= htmlspecialchars($report['teknisi_name']) ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="timeline-item">
                                <div class="timeline-dot completed"></div>
                                <div class="timeline-content">
                                    <h5 class="timeline-title">Perbaikan Selesai</h5>
                                    <p class="timeline-date"><?= date('d M Y H:i', strtotime($report['selesai_pada'])) ?></p>
                                    <p class="mb-0">
                                        Perbaikan 
                                        <?= $report['jenis_perbaikan'] === 'temporary' ? 'sementara' : 'permanen' ?> 
                                        telah selesai dilakukan
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('../../includes/footer2.php'); ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

<script>
$(document).ready(function() {
    // Initialize map
    var map = L.map('map').setView([<?= $report['latitude'] ?>, <?= $report['longitude'] ?>], 15);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Add marker
    L.marker([<?= $report['latitude'] ?>, <?= $report['longitude'] ?>])
        .addTo(map)
        .bindPopup("<b>Lokasi Perbaikan</b><br><?= htmlspecialchars($report['alamat']) ?>");
});
</script>

</body>
</html>
