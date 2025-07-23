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

/**
 * Modifikasi query untuk mengambil tiket:
 * - Status 'open' (belum ditugaskan)
 * - Status 'selesai' dengan laporan 'temporary' (bisa ditugaskan ulang)
 */
$tiket_query = $pdo->query("
    SELECT DISTINCT t.id_tiket, t.jenis_gangguan, t.deskripsi, t.created_at, t.status,
           l.alamat, a.username as created_by,
           lap.jenis_perbaikan,
           tek.nama_lengkap as current_teknisi
    FROM tiket t
    JOIN lokasi l ON t.id_lokasi = l.id_lokasi
    JOIN admin a ON t.id_admin = a.id_admin
    LEFT JOIN laporan lap ON t.id_tiket = lap.id_tiket
    LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
    LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
    WHERE t.status = 'open' 
       OR (t.status = 'selesai' AND lap.jenis_perbaikan = 'temporary')
    ORDER BY t.created_at DESC
");
$tiket_list = $tiket_query->fetchAll();

$show_assign_form = false;
$selected_tiket = null;

if (isset($_GET['tiket_id'])) {
    $tiket_id = (int)$_GET['tiket_id'];
    $stmt = $pdo->prepare("
        SELECT t.id_tiket, t.jenis_gangguan, t.deskripsi, t.created_at, t.status, 
               l.alamat, lap.jenis_perbaikan,
               tek.nama_lengkap as current_teknisi
        FROM tiket t
        JOIN lokasi l ON t.id_lokasi = l.id_lokasi
        LEFT JOIN laporan lap ON t.id_tiket = lap.id_tiket
        LEFT JOIN penugasan p ON t.id_tiket = p.id_tiket
        LEFT JOIN teknisi tek ON p.id_teknisi = tek.id_teknisi
        WHERE t.id_tiket = ? 
        AND (t.status = 'open' OR (t.status = 'selesai' AND lap.jenis_perbaikan = 'temporary'))
    ");
    $stmt->execute([$tiket_id]);
    $selected_tiket = $stmt->fetch();

    if ($selected_tiket) {
        $show_assign_form = true;
    }
}

// Proses assign teknisi ke tiket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_submit'])) {
    $teknisi_id = (int)$_POST['teknisi_id'];
    $tiket_id = (int)$_POST['tiket_id'];

    try {
        $pdo->beginTransaction();
        
        // Cek status tiket dan jenis perbaikan
        $check_tiket = $pdo->prepare("
            SELECT t.*, lap.jenis_perbaikan 
            FROM tiket t 
            LEFT JOIN laporan lap ON t.id_tiket = lap.id_tiket 
            WHERE t.id_tiket = ?
        ");
        $check_tiket->execute([$tiket_id]);
        $tiket_info = $check_tiket->fetch();
        
        $is_temporary_reassign = false;
        
        // Jika tiket sudah selesai dengan perbaikan temporary
        if ($tiket_info['status'] == 'selesai' && $tiket_info['jenis_perbaikan'] == 'temporary') {
            $is_temporary_reassign = true;
            
            // Hapus laporan temporary yang ada
            $delete_laporan = $pdo->prepare("DELETE FROM laporan WHERE id_tiket = ?");
            $delete_laporan->execute([$tiket_id]);
            
            // Update status tiket kembali ke 'on progress'
            $update_status = $pdo->prepare("UPDATE tiket SET status = 'on progress' WHERE id_tiket = ?");
            $update_status->execute([$tiket_id]);
        } else {
            // Update status tiket ke 'on progress' untuk tiket open
            $update_status = $pdo->prepare("UPDATE tiket SET status = 'on progress' WHERE id_tiket = ?");
            $update_status->execute([$tiket_id]);
        }

        // Cek apakah sudah pernah ada penugasan untuk tiket tsb
        $check = $pdo->prepare("SELECT id_penugasan FROM penugasan WHERE id_tiket = ?");
        $check->execute([$tiket_id]);

        if ($check->fetch()) {
            // Update penugasan yang ada dengan teknisi baru
            $stmt = $pdo->prepare("UPDATE penugasan SET id_teknisi = ?, created_at = CURRENT_TIMESTAMP WHERE id_tiket = ?");
            $stmt->execute([$teknisi_id, $tiket_id]);
        } else {
            // Insert penugasan baru
            $stmt = $pdo->prepare("INSERT INTO penugasan (id_tiket, id_teknisi) VALUES (?, ?)");
            $stmt->execute([$tiket_id, $teknisi_id]);
        }
        
        // Ambil detail tiket untuk notifikasi
        $stmt_detail = $pdo->prepare("
            SELECT t.jenis_gangguan, t.deskripsi, l.alamat, t.created_at
            FROM tiket t
            JOIN lokasi l ON t.id_lokasi = l.id_lokasi
            WHERE t.id_tiket = ?
        ");
        $stmt_detail->execute([$tiket_id]);
        $tiket_detail = $stmt_detail->fetch();

        // Ambil nama teknisi untuk pesan sukses
        $stmt_teknisi = $pdo->prepare("SELECT nama_lengkap FROM teknisi WHERE id_teknisi = ?");
        $stmt_teknisi->execute([$teknisi_id]);
        $teknisi_info = $stmt_teknisi->fetch();

        // Siapkan pesan notifikasi berdasarkan jenis penugasan
        if ($is_temporary_reassign) {
            $judul = "Penugasan Ulang Tiket Temporary";
            $prioritas = ($tiket_detail['jenis_gangguan'] === 'Critical') ? 'TINGGI' : 'NORMAL';
            $status_prioritas = ($tiket_detail['jenis_gangguan'] === 'Critical') ? 'SEGERA DITANGANI' : 'PERLU DITANGANI';
            
            $pesan = "Penugasan Ulang Tiket Temporary\n" .
                     "Tiket #$tiket_id\n" .
                     "Jenis Gangguan: " . $tiket_detail['jenis_gangguan'] . "\n" .
                     "Lokasi: " . substr($tiket_detail['alamat'], 0, 50) . "\n" .
                     "Deskripsi: " . substr($tiket_detail['deskripsi'], 0, 100) . "\n" .
                     "Tanggal: " . date('d M Y H:i') . "\n" .
                     "Status: $status_prioritas\n" .
                     "Prioritas: $prioritas\n" .
                     "Catatan: Perbaikan sebelumnya bersifat temporary, diperlukan perbaikan lanjutan";
        } else {
            $judul = "Penugasan Tiket Baru";
            $prioritas = ($tiket_detail['jenis_gangguan'] === 'Critical') ? 'TINGGI' : 'NORMAL';
            $status_prioritas = ($tiket_detail['jenis_gangguan'] === 'Critical') ? 'SEGERA DITANGANI' : 'PERLU DITANGANI';
            
            $pesan = "Tiket #$tiket_id\n" .
                     "Jenis Gangguan: " . $tiket_detail['jenis_gangguan'] . "\n" .
                     "Lokasi: " . $tiket_detail['alamat'] . "\n" .
                     "Deskripsi: " . mb_strimwidth($tiket_detail['deskripsi'], 0, 100, "...") . "\n" .
                     "Tanggal: " . date('d M Y H:i', strtotime($tiket_detail['created_at'])) . "\n" .
                     "Status: $status_prioritas\n" .
                     "Prioritas: $prioritas";
        }

        // Simpan notifikasi ke database
        $stmt_notif = $pdo->prepare("
            INSERT INTO notifikasi (id_teknisi, judul, pesan)
            VALUES (?, ?, ?)
        ");
        $stmt_notif->execute([$teknisi_id, $judul, $pesan]);

        $pdo->commit();

        // Set pesan sukses berdasarkan jenis penugasan
        if ($is_temporary_reassign) {
            $_SESSION['success'] = "✅ Tiket temporary berhasil ditugaskan ulang kepada " . $teknisi_info['nama_lengkap'] . "!";
        } else {
            $_SESSION['success'] = "✅ Teknisi " . $teknisi_info['nama_lengkap'] . " berhasil ditugaskan!";
        }
        
        header("Location: assign_teknisi.php");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "❌ Gagal melakukan penugasan: " . $e->getMessage();
    }
}

// Ambil daftar teknisi dari tabel teknisi
$teknisi = $pdo->query("SELECT * FROM teknisi ORDER BY username ASC")->fetchAll();
$teknisi_available = count($teknisi) > 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Penugasan Teknisi - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables */
        :root {
            --assign-telkom-red: #E31E24;
            --assign-telkom-dark-red: #B71C1C;
            --assign-telkom-light-red: #FFEBEE;
            --assign-telkom-gray: #F5F5F5;
            --assign-telkom-dark-gray: #424242;
            --assign-telkom-white: #FFFFFF;
            --assign-shadow-light: 0 2px 10px rgba(227, 30, 36, 0.1);
            --assign-shadow-medium: 0 4px 20px rgba(227, 30, 36, 0.15);
            --assign-shadow-heavy: 0 8px 30px rgba(227, 30, 36, 0.2);
            --assign-border-radius: 12px;
            --assign-border-radius-small: 8px;
            --assign-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        .assign-main-content {
            padding: 110px 25px 25px;
            transition: var(--assign-transition);
            min-height: calc(100vh - 45px);
        }

        /* Header Section */
        .assign-header-section {
            background: linear-gradient(135deg, var(--assign-telkom-red) 0%, var(--assign-telkom-dark-red) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--assign-border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--assign-shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .assign-header-section::before {
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

        .assign-header-section::after {
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

        .assign-header-content {
            position: relative;
            z-index: 2;
        }

        .assign-header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .assign-header-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .assign-telkom-logo {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 1.5rem;
            opacity: 0.3;
        }

        /* Card Styles */
        .assign-card {
            background: var(--assign-telkom-white);
            border: none;
            border-radius: var(--assign-border-radius);
            box-shadow: var(--assign-shadow-light);
            transition: var(--assign-transition);
            overflow: hidden;
            border-left: 4px solid var(--assign-telkom-red);
            margin-bottom: 2rem;
        }

        .assign-card:hover {
            box-shadow: var(--assign-shadow-medium);
            transform: translateY(-2px);
        }

        .assign-card-header {
            background: linear-gradient(135deg, var(--assign-telkom-light-red) 0%, #fafafa 100%);
            border-bottom: 2px solid var(--assign-telkom-red);
            padding: 1.5rem;
            border-radius: var(--assign-border-radius) var(--assign-border-radius) 0 0 !important;
        }

        .assign-card-title {
            color: var(--assign-telkom-red);
            font-weight: 600;
            font-size: 1.25rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .assign-card-body {
            padding: 2rem;
        }

        /* Form Styles */
        .assign-form-group {
            margin-bottom: 1.5rem;
        }

        .assign-form-label {
            font-weight: 500;
            color: var(--assign-telkom-dark-gray);
            margin-bottom: 0.5rem;
            display: block;
        }

        .assign-form-control {
            border: 2px solid #e9ecef;
            border-radius: var(--assign-border-radius-small);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: var(--assign-transition);
            background: #fafafa;
            width: 100%;
        }

        .assign-form-control:focus {
            border-color: var(--assign-telkom-red);
            box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);
            background: white;
            outline: none;
        }

        /* Button Styles */
        .assign-btn {
            border-radius: var(--assign-border-radius-small);
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--assign-transition);
            border: none;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            font-size: 0.875rem;
            min-width: 120px;
            justify-content: center;
        }

        .assign-btn-primary {
            background: linear-gradient(135deg, var(--assign-telkom-red) 0%, var(--assign-telkom-dark-red) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.3);
        }

        .assign-btn-primary:hover {
            background: linear-gradient(135deg, var(--assign-telkom-dark-red) 0%, #8B0000 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .assign-btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        }

        .assign-btn-info:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.4);
            color: white;
        }

        .assign-btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            min-width: 100px;
            font-weight: 600;
        }

        /* Action Button Container */
        .assign-action-container {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        /* Improved Action Buttons */
        .assign-btn-action {
            position: relative;
            overflow: hidden;
            border-radius: 8px;
            padding: 0.6rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            min-width: 110px;
            justify-content: center;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
        }

        .assign-btn-action::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.6s;
        }

        .assign-btn-action:hover::before {
            left: 100%;
        }

        .assign-btn-tugaskan {
            background: linear-gradient(135deg, #E31E24 0%, #B71C1C 100%);
            color: white;
            border-left: 4px solid #8B0000;
        }

        .assign-btn-tugaskan:hover {
            background: linear-gradient(135deg, #B71C1C 0%, #8B0000 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(227, 30, 36, 0.4);
            color: white;
        }

        .assign-btn-reassign {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
            border-left: 4px solid #d39e00;
        }

        .assign-btn-reassign:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
            color: #212529;
        }

        .assign-btn-detail {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            border-left: 4px solid #117a8b;
        }

        .assign-btn-detail:hover {
            background: linear-gradient(135deg, #138496 0%, #117a8b 100%);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
            color: white;
        }

        /* Icon Animation */
        .assign-btn-action i {
            transition: transform 0.3s ease;
        }

        .assign-btn-action:hover i {
            transform: scale(1.1) rotate(5deg);
        }

        /* Alert Styles */
        .assign-alert {
            border: none;
            border-radius: var(--assign-border-radius-small);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .assign-alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .assign-alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .assign-alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Ticket Detail Box */
        .assign-tiket-detail-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--assign-border-radius-small);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--assign-telkom-red);
            box-shadow: var(--assign-shadow-light);
        }

        .assign-tiket-detail-heading {
            font-weight: 600;
            color: var(--assign-telkom-red);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .assign-tiket-property {
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: white;
            border-radius: var(--assign-border-radius-small);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .assign-tiket-property-label {
            font-weight: 600;
            color: var(--assign-telkom-dark-gray);
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .assign-tiket-property-value {
            color: #333;
            font-size: 0.95rem;
        }

        /* Table Styles */
        .assign-table {
            margin-bottom: 0;
            background: white;
            border-radius: var(--assign-border-radius-small);
            overflow: hidden;
            box-shadow: var(--assign-shadow-light);
        }

        .assign-table thead th {
            background: linear-gradient(135deg, var(--assign-telkom-red) 0%, var(--assign-telkom-dark-red) 100%);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            font-size: 0.9rem;
        }

        .assign-table tbody tr {
            transition: var(--assign-transition);
        }

        .assign-table tbody tr:hover {
            background: var(--assign-telkom-light-red);
        }

        .assign-table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        /* Badge Styles */
        .assign-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .assign-badge-pending {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #856404;
        }

        .assign-badge-temporary {
            background: linear-gradient(135deg, #fd7e14 0%, #e55100 100%);
            color: #fff;
        }

        /* Search Wrapper */
        .assign-search-wrapper {
            margin-bottom: 1.5rem;
        }

        .assign-search-wrapper .assign-form-control {
            border-radius: var(--assign-border-radius-small) 0 0 var(--assign-border-radius-small);
        }

        .assign-search-wrapper .assign-btn {
            border-radius: 0 var(--assign-border-radius-small) var(--assign-border-radius-small) 0;
            width: auto;
        }

        /* No Tickets Message */
        .assign-no-tickets-message {
            padding: 3rem 2rem;
            text-align: center;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--assign-border-radius);
            margin-top: 1.5rem;
            box-shadow: var(--assign-shadow-light);
        }

        .assign-no-tickets-icon {
            font-size: 4rem;
            color: #d1d1d1;
            margin-bottom: 1rem;
        }

        .assign-no-tickets-title {
            color: var(--assign-telkom-dark-gray);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .assign-no-tickets-subtitle {
            color: #6c757d;
            font-size: 0.95rem;
        }

        /* Temporary Assignment Alert */
        .assign-temporary-alert {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
            border: 1px solid #ffc107;
            border-radius: var(--assign-border-radius-small);
            padding: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #856404;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .assign-main-content {
                padding: 110px 20px 25px;
            }
            
            .assign-header-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 992px) {
            .assign-main-content {
                padding: 110px 15px 25px;
            }
            
            .assign-header-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .assign-header-title {
                font-size: 1.8rem;
            }
            
            .assign-header-subtitle {
                font-size: 1rem;
            }
            
            .assign-card-body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .assign-main-content {
                padding: 110px 10px 25px;
            }
            
            .assign-header-section {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .assign-header-title {
                font-size: 1.5rem;
            }
            
            .assign-card-body {
                padding: 1rem;
            }

            .assign-table {
                font-size: 0.85rem;
            }

            .assign-btn-action {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
                min-width: 90px;
            }

            .assign-action-container {
                flex-direction: column;
                gap: 0.3rem;
            }
        }

        @media (max-width: 576px) {
            .assign-header-title {
                font-size: 1.3rem;
            }
            
            .assign-card-body {
                padding: 0.75rem;
            }
            
            .assign-btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .assign-btn-action {
                padding: 0.5rem 0.8rem;
                font-size: 0.7rem;
                min-width: 85px;
                gap: 0.4rem;
            }
        }

        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .assign-btn:hover,
            .assign-card:hover,
            .assign-table tbody tr:hover,
            .assign-btn-action:hover {
                transform: none;
            }
        }

        /* Print Styles */
        @media print {
            .assign-header-section,
            .assign-btn,
            .assign-btn-action {
                display: none !important;
            }
            
            .assign-main-content {
                padding: 0;
            }
            
            .assign-card {
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
        
        <div class="assign-main-content">
            <!-- Header Section -->
            <div class="assign-header-section">
                <div class="assign-telkom-logo">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="assign-header-content">
                    <h1 class="assign-header-title">
                        <i class="fas fa-user-plus me-3"></i>
                        Penugasan Teknisi
                    </h1>
                    <p class="assign-header-subtitle">
                        Kelola tiket gangguan dan tugaskan teknisi - PT Telkom Akses
                    </p>
                </div>
            </div>
            
            <!-- Messages display -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="assign-alert assign-alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['warning'])): ?>
                <div class="assign-alert assign-alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= $_SESSION['warning']; unset($_SESSION['warning']); ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="assign-alert assign-alert-danger">
                    <i class="fas fa-times-circle me-2"></i>
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="row g-4">
                <!-- Form Assign Teknisi -->
                <?php if($show_assign_form): ?>
                <div class="col-lg-4">
                    <div class="assign-card h-100">
                        <div class="assign-card-header">
                            <h5 class="assign-card-title">
                                <i class="fas fa-user-plus"></i>
                                <?= $selected_tiket['jenis_perbaikan'] == 'temporary' ? 'Penugasan Ulang Tiket' : 'Penugasan Teknisi' ?>
                            </h5>
                        </div>
                        <div class="assign-card-body">
                            <?php if($selected_tiket['jenis_perbaikan'] == 'temporary'): ?>
                                <div class="assign-temporary-alert">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Penugasan Ulang:</strong> Tiket ini memiliki perbaikan temporary dan akan ditugaskan ulang untuk perbaikan permanen.
                                </div>
                            <?php endif; ?>
                            
                            <div class="assign-tiket-detail-box">
                                <h6 class="assign-tiket-detail-heading">
                                    <i class="fas fa-ticket-alt me-2"></i>
                                    Detail Tiket #<?= $selected_tiket['id_tiket'] ?>
                                </h6>
                                
                                <div class="assign-tiket-property">
                                    <div class="assign-tiket-property-label">
                                        <i class="fas fa-tools me-1"></i>
                                        Jenis Gangguan:
                                    </div>
                                    <div class="assign-tiket-property-value">
                                        <?= htmlspecialchars($selected_tiket['jenis_gangguan']) ?>
                                    </div>
                                </div>
                                
                                <div class="assign-tiket-property">
                                    <div class="assign-tiket-property-label">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        Lokasi:
                                    </div>
                                    <div class="assign-tiket-property-value">
                                        <?= htmlspecialchars($selected_tiket['alamat']) ?>
                                    </div>
                                </div>
                                
                                <div class="assign-tiket-property">
                                    <div class="assign-tiket-property-label">
                                        <i class="fas fa-align-left me-1"></i>
                                        Deskripsi:
                                    </div>
                                    <div class="assign-tiket-property-value">
                                        <?= htmlspecialchars($selected_tiket['deskripsi']) ?>
                                    </div>
                                </div>
                                
                                <div class="assign-tiket-property">
                                    <div class="assign-tiket-property-label">
                                        <i class="fas fa-calendar me-1"></i>
                                        Tanggal Dibuat:
                                    </div>
                                    <div class="assign-tiket-property-value">
                                        <?= date('d M Y H:i', strtotime($selected_tiket['created_at'])) ?>
                                    </div>
                                </div>
                                
                                <?php if($selected_tiket['current_teknisi']): ?>
                                <div class="assign-tiket-property">
                                    <div class="assign-tiket-property-label">
                                        <i class="fas fa-user-cog me-1"></i>
                                        Teknisi Sebelumnya:
                                    </div>
                                    <div class="assign-tiket-property-value">
                                        <?= htmlspecialchars($selected_tiket['current_teknisi']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if($teknisi_available): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="tiket_id" value="<?= $selected_tiket['id_tiket'] ?>">
                                
                                <div class="assign-form-group">
                                    <label for="teknisi_id" class="assign-form-label">
                                        <i class="fas fa-user-hard-hat me-2"></i>Pilih Teknisi
                                    </label>
                                    <select class="assign-form-control" id="teknisi_id" name="teknisi_id" required>
                                        <option value="">-- Pilih Teknisi --</option>
                                        <?php foreach ($teknisi as $t): ?>
                                            <option value="<?= $t['id_teknisi'] ?>">
                                                <?= htmlspecialchars($t['nama_lengkap']) ?> (<?= htmlspecialchars($t['username']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" name="assign_submit" class="assign-btn assign-btn-primary">
                                    <i class="fas fa-tasks"></i>
                                    <?= $selected_tiket['jenis_perbaikan'] == 'temporary' ? 'Tugaskan Ulang' : 'Tugaskan Teknisi' ?>
                                </button>
                            </form>
                            <?php else: ?>
                                <div class="assign-alert assign-alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Tidak ada teknisi yang tersedia. Harap tambahkan teknisi terlebih dahulu.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- List Tiket -->
                <div class="col-lg-<?= $show_assign_form ? '8' : '12' ?>">
                    <div class="assign-card h-100">
                        <div class="assign-card-header">
                            <h5 class="assign-card-title">
                                <i class="fas fa-ticket-alt"></i>
                                Daftar Tiket untuk Penugasan
                            </h5>
                        </div>
                        <div class="assign-card-body">
                            <!-- Search and filter -->
                            <div class="assign-search-wrapper d-flex">
                                <input type="text" id="searchTiket" class="assign-form-control" placeholder="Cari tiket berdasarkan ID, jenis gangguan, atau lokasi...">
                                <button class="assign-btn assign-btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            
                            <?php if(count($tiket_list) > 0): ?>
                                <div class="table-responsive">
                                    <table class="assign-table table" id="tiketTable">
                                        <thead>
                                            <tr>
                                                <th><i class="fas fa-hashtag me-1"></i>ID</th>
                                                <th><i class="fas fa-tools me-1"></i>Jenis Gangguan</th>
                                                <th><i class="fas fa-map-marker-alt me-1"></i>Lokasi</th>
                                                <th><i class="fas fa-user me-1"></i>Dibuat Oleh</th>
                                                <th><i class="fas fa-calendar me-1"></i>Tanggal</th>
                                                <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                                <th><i class="fas fa-cogs me-1"></i>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($tiket_list as $tiket): ?>
                                                <tr>
                                                    <td><strong>#<?= $tiket['id_tiket'] ?></strong></td>
                                                    <td><?= htmlspecialchars($tiket['jenis_gangguan']) ?></td>
                                                    <td><?= mb_strimwidth(htmlspecialchars($tiket['alamat']), 0, 40, "...") ?></td>
                                                    <td><?= htmlspecialchars($tiket['created_by']) ?></td>
                                                    <td><?= date('d/m/Y', strtotime($tiket['created_at'])) ?></td>
                                                    <td>
                                                        <?php if($tiket['status'] == 'open'): ?>
                                                            <span class="assign-badge assign-badge-pending">
                                                                <i class="fas fa-clock"></i>
                                                                Menunggu
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="assign-badge assign-badge-temporary">
                                                                <i class="fas fa-redo"></i>
                                                                Temporary
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="assign-action-container">
                                                            <?php if($tiket['jenis_perbaikan'] == 'temporary'): ?>
                                                                <a href="assign_teknisi.php?tiket_id=<?= $tiket['id_tiket'] ?>" 
                                                                    class="assign-btn-action assign-btn-reassign">
                                                                    <i class="fas fa-redo"></i>
                                                                    <span>Tugaskan Ulang</span>
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="assign_teknisi.php?tiket_id=<?= $tiket['id_tiket'] ?>" 
                                                                    class="assign-btn-action assign-btn-tugaskan">
                                                                    <i class="fas fa-user-plus"></i>
                                                                    <span>Tugaskan</span>
                                                                </a>
                                                            <?php endif; ?>
                                                            <a href="detail_tiket.php?id=<?= $tiket['id_tiket'] ?>" 
                                                                class="assign-btn-action assign-btn-detail">
                                                                <i class="fas fa-eye"></i>
                                                                <span>Detail</span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <!-- No Tickets Message -->
                                <div class="assign-no-tickets-message">
                                    <div class="assign-no-tickets-icon">
                                        <i class="fas fa-ticket-alt"></i>
                                    </div>
                                    <h5 class="assign-no-tickets-title">Tidak ada tiket untuk penugasan</h5>
                                    <p class="assign-no-tickets-subtitle">
                                        Semua tiket sudah ditugaskan dan memiliki perbaikan permanen, atau belum ada tiket yang dibuat.
                                    </p>
                                </div>
                            <?php endif; ?>
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
            // Fungsi pencarian tiket
            const searchInput = document.getElementById('searchTiket');
            const tiketTable = document.getElementById('tiketTable');
            
            if (searchInput && tiketTable) {
                searchInput.addEventListener('keyup', function() {
                    const value = this.value.toLowerCase();
                    const rows = tiketTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(function(row) {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(value) > -1 ? '' : 'none';
                    });
                });
            }

            // Form validation
            const assignForm = document.querySelector('form[method="POST"]');
            if (assignForm) {
                assignForm.addEventListener('submit', function(e) {
                    const teknisiSelect = document.getElementById('teknisi_id');
                    if (teknisiSelect && !teknisiSelect.value) {
                        e.preventDefault();
                        alert('Silakan pilih teknisi terlebih dahulu!');
                        teknisiSelect.focus();
                    }
                });
            }

            // Auto-hide alerts
            const alerts = document.querySelectorAll('.assign-alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(function() {
                        alert.remove();
                    }, 300);
                }, 5000);
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl+F to focus search
                if (e.ctrlKey && e.key === 'f' && searchInput) {
                    e.preventDefault();
                    searchInput.focus();
                }
            });

            // Enhanced button interactions
            const actionButtons = document.querySelectorAll('.assign-btn-action');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px) scale(1.02)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>
