<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('admin');

// --- PASTIKAN INISIALISASI PDO $pdo ADA (dari config.php, atau tambahkan jika belum) ---

if (isset($_GET['logout'])) {
    session_start();
    session_destroy();
    header('Location: ../../index.php');
    exit();
}

// Cek user login dan ambil id_admin (bukan user_id dari users) sesuai dengan struktur mitratel_monitoring.sql
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin User';
$userRole = 'admin';
// Ambil id_admin dari session yang benar
$idAdmin = $_SESSION['user_id'] ?? 1; // Sesuaikan dengan login id_admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi lokasi
    $alamat = htmlspecialchars($_POST['alamat']);
    $lat = (float)$_POST['latitude'];
    $lng = (float)$_POST['longitude'];

    // Cek lokasi duplikat berdasarkan latitude & longitude
    $stmt = $pdo->prepare("SELECT * FROM lokasi WHERE latitude = ? AND longitude = ?");
    $stmt->execute([$lat, $lng]);
    $lokasi = $stmt->fetch();

    if (!$lokasi) {
        $stmt = $pdo->prepare("INSERT INTO lokasi (alamat, latitude, longitude) VALUES (?, ?, ?)");
        $stmt->execute([$alamat, $lat, $lng]);
        $lokasi_id = $pdo->lastInsertId();
    } else {
        $lokasi_id = $lokasi['id_lokasi'];
        // Hitung total gangguan pada lokasi ini
        $stmt = $pdo->prepare("SELECT COUNT(id_tiket) FROM tiket WHERE id_lokasi = ?");
        $stmt->execute([$lokasi_id]);
        $total_gangguan = $stmt->fetchColumn();
        $_SESSION['warning'] = "⚠️ Lokasi ini telah mengalami $total_gangguan gangguan sebelumnya!";
    }

    // Buat tiket baru ke tabel tiket (mitratel_monitoring.sql: id_admin, id_lokasi, jenis_gangguan, deskripsi)
    $stmt = $pdo->prepare("INSERT INTO tiket (id_admin, id_lokasi, jenis_gangguan, deskripsi) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $idAdmin,
        $lokasi_id,
        htmlspecialchars($_POST['jenis_gangguan']),
        htmlspecialchars($_POST['deskripsi'])
    ]);
    
    $_SESSION['success'] = "✅ Tiket berhasil dibuat!";
    header("Location: assign_teknisi.php?tiket_id=" . $pdo->lastInsertId());
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Buat Tiket Baru - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --create-telkom-red: #E31E24;
            --create-telkom-dark-red: #B71C1C;
            --create-telkom-light-red: #FFEBEE;
            --create-telkom-gray: #F5F5F5;
            --create-telkom-dark-gray: #424242;
            --create-telkom-white: #FFFFFF;
            --create-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --create-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --create-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --create-border-radius: 12px;
            --create-border-radius-small: 8px;
            --create-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .create-main-content {
            padding: 110px 25px 25px;
            transition: var(--create-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .create-header-section {
            background: linear-gradient(135deg, var(--create-telkom-red) 0%, var(--create-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--create-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--create-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .create-header-section::before {
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

        .create-header-section::after {
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

        .create-header-content {
            position: relative;
            z-index: 2;
        }

        .create-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .create-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .create-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .create-card {
            background: var(--create-telkom-white);
            border: none;
            border-radius: var(--create-border-radius);
            box-shadow: var(--create-shadow-light);
            transition: var(--create-transition);
            overflow: hidden;
            border-left: 4px solid var(--create-telkom-red);
            margin-bottom: 2rem;
        }

        .create-card:hover {
            box-shadow: var(--create-shadow-medium);
            transform: translateY(-2px);
        }

        .create-card-header {
            background: linear-gradient(135deg, var(--create-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--create-telkom-red);
            padding: 1.5rem;
            border-radius: var(--create-border-radius) var(--create-border-radius) 0 0 !important;
        }

        .create-card-title {
            color: var(--create-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .create-card-body {
            padding: 2rem;
        }

        /* Form Styles */
        .create-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--create-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--create-transition);
            background: #fafafa;
            width: 100%;
        }

        .create-form-control:focus {
            border-color: var(--create-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        .create-form-label {
            font-weight: 500;
            color: var(--create-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .create-form-group {
            margin-bottom: 1.5rem;
        }

        /* Button Styles */
        .create-btn {
            border-radius: var(--create-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--create-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .create-btn-primary {
            background: linear-gradient(135deg, var(--create-telkom-red) 0%, var(--create-telkom-dark-red) 100%);
            color: white;
            width: 100%;
            justify-content: center;
        }

        .create-btn-primary:hover {
            background: linear-gradient(135deg, var(--create-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-1px);
            box-shadow: var(--create-shadow-medium);
            color: white;
        }

        /* Map Styles */
        .create-map-container {
            height: 520px;
            width: 100%;
            border-radius: var(--create-border-radius-small);
            overflow: hidden;
            position: relative;
            background: #f0f0f0;
            border: 2px solid #ddd;
        }

        #map {
            height: 100% !important;
            width: 100% !important;
            z-index: 1;
        }

        /* Leaflet Fix */
        .leaflet-container {
            height: 100% !important;
            width: 100% !important;
            background: #ddd;
        }

        /* Alert Styles */
        .create-alert {
            border: none;
            border-radius: var(--create-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .create-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .create-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        /* Autocomplete Styles */
        .create-autocomplete-container {
            position: relative;
        }

        .create-autocomplete-items {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            border-radius: 0 0 var(--create-border-radius-small) var(--create-border-radius-small);
            box-shadow: var(--create-shadow-light);
        }

        .create-autocomplete-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: var(--create-transition);
            border-bottom: 1px solid #f1f1f1;
        }

        .create-autocomplete-item:hover {
            background: var(--create-telkom-light-red);
        }

        .create-autocomplete-item:last-child {
            border-bottom: none;
        }

        /* Map Info */
        .create-map-info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border: 1px solid #2196f3;
            border-radius: var(--create-border-radius-small);
            padding: 1rem;
            margin-top: 1rem;
        }

        .create-map-info small {
            color: #1976d2;
            font-weight: 500;
        }

        /* Loading Animation */
        .create-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: create-spin 1s ease-in-out infinite;
        }

        @keyframes create-spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .create-main-content {
                padding: 110px 20px 25px;
            }
            
            .create-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .create-main-content {
                padding: 110px 15px 25px;
            }
            
            .create-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .create-header-title {
                font-size: 1.8rem;
            }
            
            .create-header-subtitle {
                font-size: 1rem;
            }
            
            .create-card-body {
                padding: 1.5rem;
            }

            .create-map-container {
                height: 400px;
            }
        }

        @media (max-width: 768px) {
            .create-main-content {
                padding: 110px 10px 25px;
            }
            
            .create-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .create-header-title {
                font-size: 1.5rem;
            }
            
            .create-card-body {
                padding: 1rem;
            }

            .create-map-container {
                height: 350px;
            }

            .col-md-5, .col-md-7 {
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .create-header-title {
                font-size: 1.3rem;
            }
            
            .create-card-body {
                padding: 0.75rem;
            }
            
            .create-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .create-map-container {
                height: 300px;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .create-btn:hover {
                transform: none;
            }
            
            .create-card:hover {
                transform: none;
            }
            
            .create-autocomplete-item {
                padding: 1rem;
            }
        }

        /* Print Styles */
        @media print {
            .create-header-section,
            .create-btn {
                display: none !important;
            }
            
            .create-main-content {
                padding: 0;
            }
            
            .create-card {
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
        
        <div class="create-main-content">
            <!-- Header Section -->
            <div class="create-header-section">
                <div class="create-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="create-header-content">
                    <h1 class="create-header-title"><i class="fas fa-ticket-alt me-3"></i> Buat Tiket Baru</h1>
                    <p class="create-header-subtitle">Buat tiket gangguan dan tentukan lokasi pada peta - PT Telkom Akses</p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="create-alert create-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['success'])): ?>
                <div class="create-alert create-alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Form Card -->
                <div class="col-lg-5 col-md-6">
                    <div class="create-card h-100">
                        <div class="create-card-header">
                            <h5 class="create-card-title"><i class="fas fa-edit"></i> Informasi Gangguan</h5>
                        </div>
                        <div class="create-card-body">
                            <form method="POST" id="createTicketForm">
                                <div class="create-form-group">
                                    <label for="alamat" class="create-form-label">
                                        <i class="fas fa-map-marker-alt me-2"></i>Lokasi Gangguan
                                    </label>
                                    <div class="create-autocomplete-container">
                                        <input type="text" class="create-form-control" id="alamat" name="alamat" 
                                               placeholder="Ketik alamat untuk pencarian atau klik pada peta..." required>
                                        <div id="autocomplete-results" class="create-autocomplete-items"></div>
                                    </div>
                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                </div>
                                <div class="create-form-group">
                                    <label for="jenis_gangguan" class="create-form-label">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Jenis Gangguan
                                    </label>
                                    <input type="text" class="create-form-control" id="jenis_gangguan" name="jenis_gangguan" 
                                           placeholder="Contoh: Kabel Putus, Perangkat Mati" required>
                                </div>
                                <div class="create-form-group">
                                    <label for="deskripsi" class="create-form-label">
                                        <i class="fas fa-file-alt me-2"></i>Deskripsi Lengkap
                                    </label>
                                    <textarea class="create-form-control" id="deskripsi" name="deskripsi" rows="6" 
                                              placeholder="Jelaskan detail gangguan dan informasi tambahan yang diperlukan..." required></textarea>
                                </div>
                                <button type="submit" class="create-btn create-btn-primary">
                                    <i class="fas fa-ticket-alt"></i> Buat Tiket
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Map Card -->
                <div class="col-lg-7 col-md-6">
                    <div class="create-card h-100">
                        <div class="create-card-header">
                            <h5 class="create-card-title"><i class="fas fa-map"></i> Pilih Lokasi Pada Peta</h5>
                        </div>
                        <div class="create-card-body">
                            <div class="create-map-container">
                                <div id="map"></div>
                            </div>
                            <div class="create-map-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Klik pada peta untuk memilih lokasi dan mendapatkan alamat secara otomatis. 
                                    Anda juga dapat mencari alamat menggunakan kotak pencarian.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include('../../includes/footer2.php'); ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        let map = null;
        let marker = null;
        let searchTimeout = null;

        // Koordinat Palembang, Sumatera Selatan
        const PALEMBANG_LAT = -2.9761;
        const PALEMBANG_LNG = 104.7754;
        const DEFAULT_ZOOM = 13;

        document.addEventListener('DOMContentLoaded', function() {
            if (document.readyState === 'complete') {
                initializeMap();
            } else {
                window.addEventListener('load', function() {
                    setTimeout(initializeMap, 1000);
                });
            }
        });

        function initializeMap() {
            try {
                const mapContainer = document.getElementById('map');
                if (!mapContainer) {
                    console.error('Map container not found');
                    setTimeout(initializeMap, 2000);
                    return;
                }

                if (map) {
                    map.remove();
                    map = null;
                }

                // Inisialisasi map
                map = L.map('map', {
                    center: [PALEMBANG_LAT, PALEMBANG_LNG],
                    zoom: DEFAULT_ZOOM,
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    touchZoom: true
                });

                // Tambahkan tile layer
                const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                    timeout: 10000
                });

                tileLayer.on('tileerror', function(error) {
                    console.error('Tile loading error:', error);
                });

                tileLayer.addTo(map);

                // Force refresh ukuran map
                setTimeout(function() {
                    if (map) {
                        map.invalidateSize();
                    }
                }, 500);

                // Handle click pada peta dengan geocoding
                map.on('click', function(e) {
                    const lat = e.latlng.lat;
                    const lng = e.latlng.lng;
                    
                    // Update marker dan koordinat
                    updateMap(lat, lng, map.getZoom());
                    
                    // Lakukan reverse geocoding untuk mendapatkan alamat
                    reverseGeocode(lat, lng);
                });

                console.log('Map initialized successfully with geocoding');

            } catch (error) {
                console.error('Error initializing map:', error);
                setTimeout(initializeMap, 3000);
            }
        }

        // Fungsi untuk update marker di peta
        function updateMap(lat, lng, zoom = 16) {
            if (!map) return;

            // Hapus marker lama jika ada
            if (marker) {
                map.removeLayer(marker);
            }

            // Tambahkan marker baru
            marker = L.marker([lat, lng], {
                draggable: true
            }).addTo(map);

            // Set view ke lokasi baru
            map.setView([lat, lng], zoom);

            // Update hidden inputs
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;

            // Handle drag marker
            marker.on('dragend', function(e) {
                const newLat = e.target.getLatLng().lat;
                const newLng = e.target.getLatLng().lng;
                
                document.getElementById('latitude').value = newLat;
                document.getElementById('longitude').value = newLng;
                
                // Update alamat saat marker di-drag
                reverseGeocode(newLat, newLng);
            });
        }

        // Fungsi reverse geocoding yang diperbaiki
        function reverseGeocode(lat, lng) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            // Tampilkan loading di input alamat
            const alamatInput = document.getElementById('alamat');
            const originalValue = alamatInput.value;
            alamatInput.value = 'Mencari alamat...';
            alamatInput.disabled = true;

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=id`, {
                signal: controller.signal,
                headers: {
                    'User-Agent': 'TelkomAkses/1.0'
                }
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                alamatInput.disabled = false;
                
                if (data && data.display_name) {
                    // Format alamat yang lebih bersih
                    let formattedAddress = data.display_name;
                    
                    // Jika ada address components, buat alamat yang lebih terstruktur
                    if (data.address) {
                        const addr = data.address;
                        let parts = [];
                        
                        // Tambahkan komponen alamat sesuai prioritas
                        if (addr.house_number && addr.road) {
                            parts.push(`${addr.road} ${addr.house_number}`);
                        } else if (addr.road) {
                            parts.push(addr.road);
                        }
                        
                        if (addr.suburb || addr.village || addr.neighbourhood) {
                            parts.push(addr.suburb || addr.village || addr.neighbourhood);
                        }
                        
                        if (addr.city || addr.town || addr.municipality) {
                            parts.push(addr.city || addr.town || addr.municipality);
                        }
                        
                        if (addr.state) {
                            parts.push(addr.state);
                        }
                        
                        if (parts.length > 0) {
                            formattedAddress = parts.join(', ');
                        }
                    }
                    
                    alamatInput.value = formattedAddress;
                    
                    // Tambahkan popup di marker
                    if (marker) {
                        marker.bindPopup(`
                            <div style="max-width: 200px;">
                                <strong>Lokasi Terpilih</strong><br>
                                <small>${formattedAddress}</small><br>
                                <small style="color: #666;">
                                    Lat: ${lat.toFixed(6)}<br>
                                    Lng: ${lng.toFixed(6)}
                                </small>
                            </div>
                        `).openPopup();
                    }
                } else {
                    // Jika tidak ada hasil geocoding, gunakan koordinat
                    alamatInput.value = `Lokasi: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                }
            })
            .catch(error => {
                alamatInput.disabled = false;
                console.error('Error in reverse geocoding:', error);
                
                if (error.name !== 'AbortError') {
                    // Set alamat dengan koordinat jika geocoding gagal
                    alamatInput.value = `Lokasi: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                } else {
                    alamatInput.value = originalValue;
                }
            });
        }

        // Autocomplete search yang diperbaiki
        const alamatInput = document.getElementById('alamat');
        const resultsDiv = document.getElementById('autocomplete-results');

        alamatInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 3) {
                resultsDiv.innerHTML = '';
                return;
            }

            resultsDiv.innerHTML = '<div class="create-autocomplete-item">Mencari...</div>';

            searchTimeout = setTimeout(() => {
                searchLocation(query);
            }, 800);
        });

        // Sembunyikan hasil autocomplete saat klik di luar
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.create-autocomplete-container')) {
                resultsDiv.innerHTML = '';
            }
        });

        function searchLocation(query) {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000);

            const searchQuery = `${query}, Palembang, Sumatera Selatan, Indonesia`;
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchQuery)}&limit=5&countrycodes=id&bounded=1&viewbox=104.6,-2.7,104.9,-3.2&accept-language=id`, {
                signal: controller.signal,
                headers: {
                    'User-Agent': 'TelkomAkses/1.0'
                }
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                resultsDiv.innerHTML = '';
                
                if (data.length === 0) {
                    return searchLocationFallback(query);
                }
                
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Search error:', error);
                resultsDiv.innerHTML = '<div class="create-autocomplete-item" style="color: #dc3545;">Error pencarian. Coba lagi.</div>';
            });
        }

        function searchLocationFallback(query) {
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&countrycodes=id&accept-language=id`)
            .then(response => response.json())
            .then(data => {
                if (data.length === 0) {
                    resultsDiv.innerHTML = '<div class="create-autocomplete-item">Tidak ada hasil ditemukan</div>';
                    return;
                }
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Fallback search error:', error);
                resultsDiv.innerHTML = '<div class="create-autocomplete-item" style="color: #dc3545;">Pencarian gagal</div>';
            });
        }

        function displaySearchResults(data) {
            resultsDiv.innerHTML = '';
            
            data.forEach(item => {
                const div = document.createElement('div');
                div.className = 'create-autocomplete-item';
                div.innerHTML = `
                    <strong>${item.display_name.split(',')[0]}</strong><br>
                    <small style="color: #666;">${item.display_name}</small>
                `;
                div.onclick = () => {
                    alamatInput.value = item.display_name;
                    document.getElementById('latitude').value = item.lat;
                    document.getElementById('longitude').value = item.lon;
                    resultsDiv.innerHTML = '';
                    updateMap(parseFloat(item.lat), parseFloat(item.lon), 16);
                };
                resultsDiv.appendChild(div);
            });
        }

        // Form validation yang diperbaiki
        document.getElementById('createTicketForm').addEventListener('submit', function(e) {
            const alamat = document.getElementById('alamat').value.trim();
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            const jenisGangguan = document.getElementById('jenis_gangguan').value.trim();
            const deskripsi = document.getElementById('deskripsi').value.trim();

            if (!jenisGangguan || !deskripsi) {
                e.preventDefault();
                alert('Jenis Gangguan dan Deskripsi harus diisi!');
                return false;
            }

            if (!alamat) {
                e.preventDefault();
                alert('Alamat harus diisi! Silakan klik pada peta atau cari alamat.');
                return false;
            }

            // Jika koordinat kosong tapi alamat ada, coba set koordinat default Palembang
            if (!latitude || !longitude) {
                document.getElementById('latitude').value = PALEMBANG_LAT;
                document.getElementById('longitude').value = PALEMBANG_LNG;
                console.log('Using default Palembang coordinates');
            }

            // Add loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="create-loading"></span> Membuat Tiket...';
            submitBtn.disabled = true;

            // Re-enable jika form gagal submit
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }, 15000);
        });

        // Error handling untuk peta
        window.addEventListener('error', function(e) {
            console.error('Global error:', e.error);
            if (e.error && e.error.message && e.error.message.includes('Leaflet')) {
                console.log('Retrying map initialization...');
                setTimeout(initializeMap, 2000);
            }
        });

        // Cleanup saat page unload
        window.addEventListener('beforeunload', function() {
            if (map) {
                map.remove();
            }
        });
    </script>
</body>
</html>
