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

// Get ticket ID from URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    header('Location: view_tickets.php');
    exit();
}

// Get ticket details with related data - TANPA PENUGASAN
$ticket_query = $pdo->prepare("
    SELECT t.*, 
           l.alamat, l.latitude, l.longitude,
           u.username as created_by, u.nama_lengkap as admin_nama
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN users u ON t.id_admin = u.id_user
    WHERE t.id_tiket = ?
");
$ticket_query->execute([$ticket_id]);
$ticket = $ticket_query->fetch();

if (!$ticket) {
    header('Location: view_tickets.php?error=ticket_not_found');
    exit();
}

// Status badge configuration
$status_config = [
    'open' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Belum Ditugaskan'],
    'on progress' => ['class' => 'info', 'icon' => 'cog', 'text' => 'Dalam Proses'],
    'selesai' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Selesai']
];

$current_status = $status_config[$ticket['status']] ?? $status_config['open'];

// Get laporan if exists
$laporan_query = $pdo->prepare("
    SELECT * FROM laporan WHERE id_tiket = ?
");
$laporan_query->execute([$ticket_id]);
$laporan = $laporan_query->fetch();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Detail Tiket #<?= $ticket['id_tiket'] ?> - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Leaflet CSS untuk OpenStreetMap -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
          crossorigin=""/>
    
    <style>
        /* CSS Variables */
        :root {
            --detail-telkom-red: #E31E24;
            --detail-telkom-dark-red: #B71C1C;
            --detail-telkom-light-red: #FFEBEE;
            --detail-telkom-gray: #F5F5F5;
            --detail-telkom-dark-gray: #424242;
            --detail-telkom-white: #FFFFFF;
            --detail-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --detail-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --detail-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --detail-border-radius: 12px;
            --detail-border-radius-small: 8px;
            --detail-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .detail-main-content {
            padding: 110px 25px 25px;
            transition: var(--detail-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .detail-header-section {
            background: linear-gradient(135deg, var(--detail-telkom-red) 0%, var(--detail-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--detail-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--detail-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .detail-header-section::before {
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

        .detail-header-content {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .detail-header-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .detail-header-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .detail-back-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 0.75rem 1.5rem;
            border-radius: var(--detail-border-radius-small);
            text-decoration: none;
            font-weight: 500;
            transition: var(--detail-transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-2px);
        }

        /* Card Styles */
        .detail-card {
            background: var(--detail-telkom-white);
            border: none;
            border-radius: var(--detail-border-radius);
            box-shadow: var(--detail-shadow-light);
            transition: var(--detail-transition);
            overflow: hidden;
            border-left: 4px solid var(--detail-telkom-red);
            margin-bottom: 2rem;
        }

        .detail-card:hover {
            box-shadow: var(--detail-shadow-medium);
            transform: translateY(-2px);
        }

        .detail-card-header {
            background: linear-gradient(135deg, var(--detail-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--detail-telkom-red);
            padding: 1.5rem;
        }

        .detail-card-title {
            color: var(--detail-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .detail-card-body {
            padding: 2rem;
        }

        /* Status Badge */
        .detail-status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Info Grid */
        .detail-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .detail-info-item {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--detail-border-radius-small);
            border-left: 4px solid var(--detail-telkom-red);
        }

        .detail-info-label {
            font-weight: 600;
            color: var(--detail-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-info-value {
            font-size: 1.1rem;
            color: #212529;
            font-weight: 500;
        }

        /* Description Box */
        .detail-description-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-radius: var(--detail-border-radius);
            border: 2px solid #e9ecef;
            margin: 1.5rem 0;
        }

        .detail-description-text {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #495057;
            margin: 0;
        }

        /* Alert Messages */
        .detail-alert {
            padding: 1rem 1.5rem;
            border-radius: var(--detail-border-radius-small);
            margin-bottom: 1.5rem;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .detail-alert-success {
            background: #d1edff;
            border-color: #28a745;
            color: #155724;
        }

        .detail-alert-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }

        /* Map Container */
        .detail-map-container {
            height: 400px;
            border-radius: var(--detail-border-radius-small);
            overflow: hidden;
            border: 2px solid #e9ecef;
            background: #f8f9fa;
            position: relative;
        }

        .detail-map-placeholder {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            flex-direction: column;
        }

        /* Map Controls */
        .detail-map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            display: flex;
            gap: 5px;
        }

        .detail-map-btn {
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: var(--detail-transition);
        }

        .detail-map-btn:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
        }

        /* Custom Leaflet Marker */
        .custom-marker {
            background: var(--detail-telkom-red);
            border: 3px solid white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.4);
        }

        /* Map Loading */
        .map-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1001;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .map-loading .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--detail-telkom-red);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .detail-main-content {
                padding: 110px 20px 25px;
            }
        }

        @media (max-width: 992px) {
            .detail-main-content {
                padding: 110px 15px 25px;
            }
            
            .detail-header-title {
                font-size: 1.8rem;
            }
            
            .detail-info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .detail-main-content {
                padding: 110px 10px 25px;
            }
            
            .detail-header-section {
                padding: 1.5rem;
            }
            
            .detail-header-title {
                font-size: 1.5rem;
            }
            
            .detail-header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-card-body {
                padding: 1.5rem;
            }
            
            .detail-map-container {
                height: 300px;
            }
            
            .detail-map-controls {
                top: 5px;
                right: 5px;
            }
            
            .detail-map-btn {
                padding: 3px 8px;
                font-size: 11px;
            }
        }

        @media (max-width: 576px) {
            .detail-header-title {
                font-size: 1.3rem;
            }
            
            .detail-card-body {
                padding: 1rem;
            }
            
            .detail-info-item {
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
        
        <div class="detail-main-content">
            <!-- Header Section -->
            <div class="detail-header-section">
                <div class="detail-header-content">
                    <div>
                        <h1 class="detail-header-title">
                            <i class="fas fa-ticket-alt me-3"></i>
                            Detail Tiket #<?= $ticket['id_tiket'] ?>
                        </h1>
                        <p class="detail-header-subtitle">
                            Informasi lengkap tiket gangguan - PT Telkom Akses
                        </p>
                    </div>
                    <a href="view_tickets.php" class="detail-back-btn">
                        <i class="fas fa-arrow-left"></i>
                        Kembali ke Daftar
                    </a>
                </div>
            </div>

            <!-- Ticket Status Card -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h5 class="detail-card-title">
                        <i class="fas fa-info-circle"></i>
                        Status Tiket
                    </h5>
                </div>
                <div class="detail-card-body text-center">
                    <span class="detail-status-badge bg-<?= $current_status['class'] ?>">
                        <i class="fas fa-<?= $current_status['icon'] ?>"></i>
                        <?= $current_status['text'] ?>
                    </span>
                </div>
            </div>

            <!-- Ticket Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h5 class="detail-card-title">
                        <i class="fas fa-clipboard-list"></i>
                        Informasi Tiket
                    </h5>
                </div>
                <div class="detail-card-body">
                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-hashtag"></i>
                                ID Tiket
                            </div>
                            <div class="detail-info-value">#<?= $ticket['id_tiket'] ?></div>
                        </div>
                        
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-tools"></i>
                                Jenis Gangguan
                            </div>
                            <div class="detail-info-value"><?= htmlspecialchars($ticket['jenis_gangguan']) ?></div>
                        </div>
                        
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-user"></i>
                                Dibuat Oleh
                            </div>
                            <div class="detail-info-value"><?= htmlspecialchars($ticket['admin_nama']) ?> (<?= htmlspecialchars($ticket['created_by']) ?>)</div>
                        </div>
                        
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-calendar"></i>
                                Tanggal Dibuat
                            </div>
                            <div class="detail-info-value"><?= date('d F Y, H:i', strtotime($ticket['created_at'])) ?> WIB</div>
                        </div>
                        
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-info-circle"></i>
                                Status
                            </div>
                            <div class="detail-info-value"><?= ucfirst($ticket['status']) ?></div>
                        </div>
                    </div>
                    
                    <!-- Description -->
                    <div class="detail-description-box">
                        <div class="detail-info-label mb-3">
                            <i class="fas fa-align-left"></i>
                            Deskripsi Gangguan
                        </div>
                        <p class="detail-description-text"><?= nl2br(htmlspecialchars($ticket['deskripsi'])) ?></p>
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h5 class="detail-card-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Informasi Lokasi
                    </h5>
                </div>
                <div class="detail-card-body">
                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-location-arrow"></i>
                                Alamat
                            </div>
                            <div class="detail-info-value"><?= htmlspecialchars($ticket['alamat']) ?></div>
                        </div>
                        
                        <?php if ($ticket['latitude'] && $ticket['longitude']): ?>
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-crosshairs"></i>
                                Koordinat
                            </div>
                            <div class="detail-info-value" id="coordinates-display">
                                <span class="coord-text"><?= $ticket['latitude'] ?>, <?= $ticket['longitude'] ?></span>
                                <br>
                                <div class="mt-2">
                                    <a href="https://maps.google.com/maps?q=<?= $ticket['latitude'] ?>,<?= $ticket['longitude'] ?>" 
                                       target="_blank" class="text-primary me-3">
                                        <i class="fas fa-external-link-alt"></i> Google Maps
                                    </a>
                                    <a href="https://www.openstreetmap.org/?mlat=<?= $ticket['latitude'] ?>&mlon=<?= $ticket['longitude'] ?>&zoom=16" 
                                       target="_blank" class="text-success">
                                        <i class="fas fa-map"></i> OpenStreetMap
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Interactive Map -->
                    <div class="detail-map-container mt-3" id="mapContainer">
                        <?php if ($ticket['latitude'] && $ticket['longitude']): ?>
                            <div class="map-loading" id="mapLoading">
                                <div class="spinner"></div>
                                <small>Memuat peta...</small>
                            </div>
                            <div class="detail-map-controls">
                                <button class="detail-map-btn" onclick="centerMap()" title="Pusatkan Peta">
                                    <i class="fas fa-crosshairs"></i>
                                </button>
                                <button class="detail-map-btn" onclick="toggleMapType()" title="Ubah Jenis Peta">
                                    <i class="fas fa-layer-group"></i>
                                </button>
                                <button class="detail-map-btn" onclick="copyCoordinates()" title="Salin Koordinat">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <div id="map" style="height: 100%; width: 100%;"></div>
                        <?php else: ?>
                            <div class="detail-map-placeholder">
                                <i class="fas fa-map-marker-slash fa-3x mb-2"></i>
                                <p><strong>Koordinat lokasi tidak tersedia</strong></p>
                                <small class="text-muted">Peta tidak dapat ditampilkan karena data koordinat tidak lengkap</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Laporan (if exists) -->
            <?php if ($laporan): ?>
            <div class="detail-card">
                <div class="detail-card-header">
                    <h5 class="detail-card-title">
                        <i class="fas fa-file-alt"></i>
                        Laporan Penyelesaian
                    </h5>
                </div>
                <div class="detail-card-body">
                    <div class="detail-info-grid">
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-tools"></i>
                                Jenis Perbaikan
                            </div>
                            <div class="detail-info-value"><?= ucfirst($laporan['jenis_perbaikan']) ?></div>
                        </div>
                        
                        <div class="detail-info-item">
                            <div class="detail-info-label">
                                <i class="fas fa-calendar-check"></i>
                                Selesai Pada
                            </div>
                            <div class="detail-info-value"><?= date('d F Y, H:i', strtotime($laporan['selesai_pada'])) ?> WIB</div>
                        </div>
                    </div>
                    
                    <?php if ($laporan['catatan']): ?>
                    <div class="detail-description-box">
                        <div class="detail-info-label mb-3">
                            <i class="fas fa-sticky-note"></i>
                            Catatan Laporan
                        </div>
                        <p class="detail-description-text"><?= nl2br(htmlspecialchars($laporan['catatan'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($laporan['dokumentasi']): ?>
                    <div class="mt-3">
                        <div class="detail-info-label mb-2">
                            <i class="fas fa-camera"></i>
                            Dokumentasi
                        </div>
                        <img src="../../uploads/<?= htmlspecialchars($laporan['dokumentasi']) ?>" 
                             alt="Dokumentasi" class="img-fluid rounded" style="max-height: 400px;">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php include('../../includes/footer2.php'); ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JavaScript untuk OpenStreetMap -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
            crossorigin=""></script>

    <script>
        // Map variables
        let map;
        let marker;
        let currentMapType = 'osm';
        
        // Map tile layers
        const mapLayers = {
            osm: {
                url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                name: 'OpenStreetMap'
            },
            satellite: {
                url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                attribution: '© <a href="https://www.esri.com/">Esri</a>, DigitalGlobe, GeoEye, Earthstar Geographics, CNES/Airbus DS, USDA, USGS, AeroGRID, IGN, and the GIS User Community',
                name: 'Satellite'
            },
            terrain: {
                url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png',
                attribution: '© <a href="https://opentopomap.org/">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',
                name: 'Terrain'
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map if coordinates are available
            <?php if ($ticket['latitude'] && $ticket['longitude']): ?>
                initializeMap(<?= $ticket['latitude'] ?>, <?= $ticket['longitude'] ?>);
            <?php endif; ?>

            // Auto-hide success messages
            const successAlert = document.querySelector('.detail-alert-success');
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.opacity = '0';
                    setTimeout(() => {
                        successAlert.remove();
                    }, 300);
                }, 5000);
            }
        });

        // Initialize map function
        function initializeMap(lat, lng) {
            try {
                // Create map
                map = L.map('map', {
                    center: [lat, lng],
                    zoom: 16,
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    dragging: true
                });

                // Add initial tile layer
                const initialLayer = L.tileLayer(mapLayers.osm.url, {
                    attribution: mapLayers.osm.attribution,
                    maxZoom: 19
                }).addTo(map);

                // Create custom marker icon
                const customIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<i class="fas fa-map-marker-alt" style="color: white; font-size: 12px; margin-top: 2px;"></i>',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10],
                    popupAnchor: [0, -10]
                });

                // Add marker
                marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

                // Add popup to marker
                const popupContent = `
                    <div style="text-align: center; min-width: 200px;">
                        <h6 style="color: #E31E24; margin-bottom: 10px;">
                            <i class="fas fa-map-marker-alt"></i> Lokasi Tiket #<?= $ticket['id_tiket'] ?>
                        </h6>
                        <p style="margin-bottom: 8px; font-weight: 500;">
                            <?= htmlspecialchars($ticket['alamat']) ?>
                        </p>
                        <small style="color: #666;">
                            <i class="fas fa-crosshairs"></i> ${lat}, ${lng}
                        </small>
                        <hr style="margin: 10px 0;">
                        <div style="display: flex; gap: 5px; justify-content: center;">
                            <a href="https://maps.google.com/maps?q=${lat},${lng}" target="_blank" 
                               style="background: #4285f4; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                <i class="fab fa-google"></i> Google
                            </a>
                            <a href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=16" target="_blank"
                               style="background: #7ebc6f; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                <i class="fas fa-map"></i> OSM
                            </a>
                        </div>
                    </div>
                `;
                
                marker.bindPopup(popupContent, {
                    maxWidth: 250,
                    className: 'custom-popup'
                });

                // Add circle to show approximate area
                const circle = L.circle([lat, lng], {
                    color: '#E31E24',
                    fillColor: '#E31E24',
                    fillOpacity: 0.1,
                    radius: 100
                }).addTo(map);

                // Hide loading indicator
                document.getElementById('mapLoading').style.display = 'none';

                // Map event listeners
                map.on('load', function() {
                    console.log('Map loaded successfully');
                });

                map.on('error', function(e) {
                    console.error('Map error:', e);
                    showMapError();
                });

            } catch (error) {
                console.error('Error initializing map:', error);
                showMapError();
            }
        }

        // Center map function
        function centerMap() {
            if (map && marker) {
                map.setView(marker.getLatLng(), 16);
                marker.openPopup();
            }
        }

        // Toggle map type function
        function toggleMapType() {
            if (!map) return;

            // Cycle through map types
            const types = Object.keys(mapLayers);
            const currentIndex = types.indexOf(currentMapType);
            const nextIndex = (currentIndex + 1) % types.length;
            currentMapType = types[nextIndex];

            // Remove all layers
            map.eachLayer(function(layer) {
                if (layer instanceof L.TileLayer) {
                    map.removeLayer(layer);
                }
            });

            // Add new layer
            const newLayer = mapLayers[currentMapType];
            L.tileLayer(newLayer.url, {
                attribution: newLayer.attribution,
                maxZoom: 19
            }).addTo(map);

            // Show notification
            showMapNotification(`Peta diubah ke: ${newLayer.name}`);
        }

        // Copy coordinates function
        function copyCoordinates() {
            const coordText = document.querySelector('.coord-text').textContent;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(coordText).then(() => {
                    showMapNotification('Koordinat berhasil disalin!');
                }).catch(() => {
                    fallbackCopyTextToClipboard(coordText);
                });
            } else {
                fallbackCopyTextToClipboard(coordText);
            }
        }

        // Fallback copy function
        function fallbackCopyTextToClipboard(text) {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showMapNotification('Koordinat berhasil disalin!');
            } catch (err) {
                showMapNotification('Gagal menyalin koordinat');
            }
            
            document.body.removeChild(textArea);
        }

        // Show map notification
        function showMapNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #28a745;
                color: white;
                padding: 10px 15px;
                border-radius: 5px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                font-size: 14px;
                animation: slideInRight 0.3s ease;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 2000);
        }

        // Show map error
        function showMapError() {
            document.getElementById('mapLoading').style.display = 'none';
            const mapElement = document.getElementById('map');
            if (mapElement) {
                mapElement.innerHTML = `
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; flex-direction: column; color: #666;">
                        <i class="fas fa-exclamation-triangle fa-3x mb-2" style="color: #ffc107;"></i>
                        <p><strong>Gagal memuat peta</strong></p>
                        <small>Periksa koneksi internet Anda</small>
                        <button onclick="location.reload()" style="margin-top: 10px; padding: 5px 15px; background: #E31E24; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-refresh"></i> Muat Ulang
                        </button>
                    </div>
                `;
            }
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .custom-popup .leaflet-popup-content-wrapper {
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            }
            .custom-popup .leaflet-popup-tip {
                background: white;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
