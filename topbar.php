<?php
function showTopbar($userRole, $username) {
    global $pdo;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Tentukan tabel dan field ID berdasarkan role
    $table = '';
    $id_field = '';

    if ($role === 'admin') {
        $table = 'admin';
        $id_field = 'id_admin';
    } elseif ($role === 'teknisi') {
        $table = 'teknisi';
        $id_field = 'id_teknisi';
    } elseif ($role === 'supervisor') {
        $table = 'supervisor';
        $id_field = 'id_supervisor';
    }

    // Ambil data user dari tabel yang sesuai
    $stmt = $pdo->prepare("SELECT foto, nama_lengkap FROM $table WHERE $id_field = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $foto_path = $user && $user['foto'] ? "../../uploads/users/" . htmlspecialchars($user['foto']) : "../../uploads/users/default-avatar.jpg";
    $nama_lengkap = $user && $user['nama_lengkap'] ? htmlspecialchars($user['nama_lengkap']) : htmlspecialchars($username);

    // ========== NOTIFIKASI KHUSUS TEKNISI ========== 
    $notif_list = [];
    $unread_count = 0;
    if ($role === "teknisi") {
        try {
            $notif_q = $pdo->prepare("SELECT * FROM notifikasi WHERE id_teknisi = ? ORDER BY created_at DESC LIMIT 10");
            $notif_q->execute([$user_id]);
            $notif_list = $notif_q->fetchAll(PDO::FETCH_ASSOC);
            foreach($notif_list as $n) {
                if ($n['status_baca'] == 'unread') $unread_count++;
            }
        } catch (PDOException $e) {
            // Jika tabel notifikasi belum ada, set array kosong
            $notif_list = [];
            $unread_count = 0;
        }
    }
?>
    <div class="topbar">
        <div class="topbar-left">
            <!-- Desktop Sidebar Toggle -->
            <button id="desktopSidebarToggle" class="desktop-toggle-btn">
                <i class="fa fa-bars"></i>
                <span class="ripple"></span>
            </button>
            
            <!-- Mobile Sidebar Toggle -->
            <button id="mobileSidebarToggle" class="mobile-toggle-btn">
                <i class="fa fa-bars"></i>
                <span class="ripple"></span>
            </button>
            
            <div class="page-title">
                <h2><?php echo $nama_lengkap; ?></h2>
                <span class="breadcrumb">PT Telkom Akses / <?php echo ucfirst($userRole); ?></span>
            </div>
        </div>
        
        <div class="topbar-right">
            <!-- =================== NOTIFIKASI ICON DI TOPBAR =================== -->
            <?php if($role === 'teknisi'): ?>
            <div class="notification-wrapper">
                <button class="notification-btn" id="notificationToggle" type="button">
                    <i class="fa fa-bell"></i>
                    <?php if($unread_count > 0): ?>
                        <span class="notification-badge"><?= $unread_count ?></span>
                    <?php endif; ?>
                </button>
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h6>Notifikasi</h6>
                        <?php if($unread_count > 0): ?>
                            <span class="unread-count"><?= $unread_count ?> baru</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-content">
                        <?php if(empty($notif_list)): ?>
                            <div class="notification-empty">
                                <i class="fa fa-bell-slash"></i>
                                <p>Tidak ada notifikasi</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($notif_list as $notif): ?>
                                <div class="notification-item <?= $notif['status_baca'] == 'unread' ? 'unread' : '' ?>">
                                    <div class="notification-icon">
                                        <i class="fa fa-tools"></i>
                                    </div>
                                    <div class="notification-details">
                                        <h6 class="notification-title"><?= htmlspecialchars($notif['judul']) ?></h6>
                                        <p class="notification-message"><?= htmlspecialchars($notif['pesan']) ?></p>
                                        <span class="notification-time"><?= date('d M Y, H:i', strtotime($notif['created_at'])) ?></span>
                                    </div>
                                    <?php if($notif['status_baca'] == 'unread'): ?>
                                        <div class="notification-dot"></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!empty($notif_list)): ?>
                        <div class="notification-footer">
                            <a href="notifikasi.php" class="btn-view-all">Lihat Semua</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- User Profile Dropdown -->
            <div class="user-profile" id="userProfile">
                <div class="user-info-text">
                    <span class="user-name"><?php echo $nama_lengkap; ?></span>
                    <span class="user-role"><?php echo ucfirst($userRole); ?></span>
                </div>
                <div class="user-avatar-topbar">
                    <img src="<?php echo $foto_path; ?>" alt="User Avatar" 
                         onerror="this.src='../../uploads/users/default.svg';">
                    <div class="avatar-fallback">
                        <i class="fa fa-user"></i>
                    </div>
                    <div class="status-indicator online"></div>
                </div>
                <div class="dropdown-arrow">
                    <i class="fa fa-chevron-down"></i>
                </div>
                
                <!-- Enhanced Dropdown Menu -->
                <div class="dropdown-menu" id="dropdownMenu">
                    <!-- User Info Section -->
                    <div class="dropdown-user-card">
                        <div class="dropdown-avatar-large">
                            <img src="<?php echo $foto_path; ?>" alt="User Avatar" 
                                 onerror="this.src='../../uploads/users/default.svg';">
                            <div class="dropdown-avatar-fallback">
                                <i class="fa fa-user"></i>
                            </div>
                            <div class="status-dot online"></div>
                        </div>
                        <div class="dropdown-user-details">
                            <h3 class="dropdown-username"><?php echo $nama_lengkap; ?></h3>
                            <p class="dropdown-userrole"><?php echo ucfirst($userRole); ?></p>
                            <span class="user-status">Online</span>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="dropdown-section">
                        <h5 class="section-title">Quick Actions</h5>
                        <div class="quick-actions">
                            <a href="#" class="quick-action-item">
                                <i class="fa fa-plus"></i>
                                <span>New Ticket</span>
                            </a>
                            <a href="#" class="quick-action-item">
                                <i class="fa fa-chart-bar"></i>
                                <span>Reports</span>
                            </a>
                            <a href="#" class="quick-action-item">
                                <i class="fa fa-cog"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Menu Actions -->
                    <div class="dropdown-section">
                        <div class="dropdown-actions">
                            <a href="profile.php" class="dropdown-action-item">
                                <div class="action-icon">
                                    <i class="fa fa-user"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">My Profile</span>
                                    <span class="action-subtitle">View and edit profile</span>
                                </div>
                                <i class="fa fa-chevron-right action-arrow"></i>
                            </a>
                            <a href="#" class="dropdown-action-item">
                                <div class="action-icon">
                                    <i class="fa fa-bell"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Notifications</span>
                                    <span class="action-subtitle">Manage preferences</span>
                                </div>
                                <i class="fa fa-chevron-right action-arrow"></i>
                            </a>
                            <a href="#" class="dropdown-action-item">
                                <div class="action-icon">
                                    <i class="fa fa-shield-alt"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Security</span>
                                    <span class="action-subtitle">Password & 2FA</span>
                                </div>
                                <i class="fa fa-chevron-right action-arrow"></i>
                            </a>
                            <a href="#" class="dropdown-action-item">
                                <div class="action-icon">
                                    <i class="fa fa-question-circle"></i>
                                </div>
                                <div class="action-content">
                                    <span class="action-title">Help & Support</span>
                                    <span class="action-subtitle">Get assistance</span>
                                </div>
                                <i class="fa fa-chevron-right action-arrow"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Logout Section -->
                    <div class="dropdown-section logout-section">
                        <a href="../../logout.php" class="dropdown-action-item logout-item">
                            <div class="action-icon">
                                <i class="fa fa-sign-out-alt"></i>
                            </div>
                            <div class="action-content">
                                <span class="action-title">Sign Out</span>
                                <span class="action-subtitle">Logout from account</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #c0392b;
            --primary-light: #f39c12;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --text-muted: #95a5a6;
            --text-white: #ffffff;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --bg-dark: #2c3e50;
            --border-color: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 25px rgba(0,0,0,0.15);
            --shadow-xl: 0 20px 40px rgba(0,0,0,0.1);
            --shadow-2xl: 0 25px 50px rgba(0,0,0,0.25);
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --topbar-height: 80px;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            --gradient-secondary: linear-gradient(135deg, var(--secondary-color), #2980b9);
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .topbar {
            height: var(--topbar-height);
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px 0 24px;
            box-shadow: var(--shadow-md);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 998;
            transition: var(--transition);
            border-bottom: 1px solid var(--border-color);
            font-family: 'Inter', sans-serif;
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .content-wrapper.expanded .topbar {
            left: var(--sidebar-collapsed);
        }
        
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 24px;
            flex: 1;
        }
        
        .desktop-toggle-btn,
        .mobile-toggle-btn {
            background: var(--gradient-primary);
            border: none;
            color: white;
            width: 48px;
            height: 48px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
        }
        
        .desktop-toggle-btn::before,
        .mobile-toggle-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.2);
            opacity: 0;
            transition: var(--transition);
        }
        
        .desktop-toggle-btn:hover::before,
        .mobile-toggle-btn:hover::before {
            opacity: 1;
        }
        
        .desktop-toggle-btn:hover,
        .mobile-toggle-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .desktop-toggle-btn i,
        .mobile-toggle-btn i {
            font-size: 18px;
            transition: var(--transition);
            z-index: 2;
            position: relative;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .mobile-toggle-btn {
            display: none;
        }
        
        .page-title {
            display: flex;
            flex-direction: column;
            gap: 0px;
        }
        
        .page-title h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.025em;
            line-height: 1.2;
        }
        
        .breadcrumb {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        /* =================== NOTIFICATION STYLES =================== */
        .notification-wrapper {
            position: relative;
        }

        .notification-btn {
            background: var(--bg-secondary);
            border: 2px solid var(--border-light);
            color: var(--text-secondary);
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .notification-btn:hover {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        .notification-btn i {
            font-size: 18px;
        }

        .notification-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: var(--primary-color);
            color: white;
            font-size: 10px;
            font-weight: 700;
            min-width: 18px;
            height: 18px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
            box-shadow: var(--shadow-sm);
        }

        .notification-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 380px;
            background: white;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-12px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 9999;
            overflow: hidden;
            max-height: 500px;
            display: flex;
            flex-direction: column;
        }

        .notification-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .notification-header {
            padding: 16px 20px;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .notification-header h6 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .unread-count {
            background: var(--primary-color);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .notification-content {
            flex: 1;
            overflow-y: auto;
            max-height: 350px;
        }

        .notification-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-muted);
        }

        .notification-empty i {
            font-size: 2rem;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .notification-empty p {
            margin: 0;
            font-size: 0.9rem;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            transition: var(--transition);
            position: relative;
        }

        .notification-item:hover {
            background: var(--bg-secondary);
        }

        .notification-item.unread {
            background: rgba(231, 76, 60, 0.05);
            border-left: 3px solid var(--primary-color);
        }

        .notification-icon {
            width: 36px;
            height: 36px;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            flex-shrink: 0;
        }

        .notification-item.unread .notification-icon {
            background: var(--primary-color);
            color: white;
        }

        .notification-details {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            margin: 0 0 4px 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.3;
        }

        .notification-message {
            margin: 0 0 8px 0;
            font-size: 0.8rem;
            color: var(--text-secondary);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .notification-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .notification-dot {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 4px;
        }

        .notification-footer {
            padding: 12px 20px;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-light);
            text-align: center;
        }

        .btn-view-all {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-view-all:hover {
            color: var(--primary-dark);
            text-decoration: none;
        }
        
        /* Enhanced User Profile */
        .user-profile {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px 8px 8px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-xl);
            cursor: pointer;
            transition: var(--transition);
            min-width: 200px;
        }
        
        .user-profile:hover {
            background: var(--bg-tertiary);
            border-color: var(--border-color);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .user-profile.active {
            background: var(--bg-tertiary);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
        }
        
        .user-info-text {
            display: flex;
            flex-direction: column;
            text-align: right;
            flex: 1;
            min-width: 0;
        }
        
        .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-avatar-topbar {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            position: relative;
            border: 3px solid var(--border-light);
            flex-shrink: 0;
            background: var(--bg-tertiary);
        }
        
        .user-avatar-topbar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .avatar-fallback {
            display: none;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .status-indicator {
            position: absolute;
            bottom: -1px;
            right: -1px;
            width: 14px;
            height: 14px;
            border: 3px solid white;
            border-radius: 50%;
        }
        
        .status-indicator.online {
            background: var(--success-color);
        }
        
        .status-indicator.away {
            background: var(--warning-color);
        }
        
        .status-indicator.offline {
            background: var(--text-muted);
        }
        
        .dropdown-arrow {
            color: var(--text-muted);
            font-size: 14px;
            transition: var(--transition);
            flex-shrink: 0;
        }
        
        .user-profile.active .dropdown-arrow {
            transform: rotate(180deg);
            color: var(--primary-color);
        }
        
        /* Enhanced Dropdown Menu */
        .dropdown-menu {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 320px;
            background: white;
            border: 1px solid var(--border-light);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-2xl);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-12px) scale(0.95);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 9999;
            overflow: hidden;
            pointer-events: none;
        }
        
        .user-profile.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }
        
        /* Enhanced User Card Section */
        .dropdown-user-card {
            padding: 24px;
            background: var(--gradient-bg);
            color: white;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }
        
        .dropdown-user-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: translate(30%, -30%);
        }
        
        .dropdown-avatar-large {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-xl);
            overflow: hidden;
            position: relative;
            border: 3px solid rgba(255, 255, 255, 0.3);
            flex-shrink: 0;
            background: var(--bg-tertiary);
            z-index: 2;
        }
        
        .dropdown-avatar-large img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .dropdown-avatar-fallback {
            display: none;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            position: absolute;
            top: 0;
            left: 0;
        }
        
        .status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            border: 3px solid white;
            border-radius: 50%;
        }
        
        .status-dot.online {
            background: var(--success-color);
        }
        
        .dropdown-user-details {
            flex: 1;
            min-width: 0;
            z-index: 2;
        }
        
        .dropdown-username {
            margin: 0 0 4px 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .dropdown-userrole {
            margin: 0 0 8px 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            text-transform: capitalize;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .user-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .user-status::before {
            content: '';
            width: 8px;
            height: 8px;
            background: var(--success-color);
            border-radius: 50%;
        }
        
        /* Dropdown Sections */
        .dropdown-section {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-light);
        }
        
        .dropdown-section:last-child {
            border-bottom: none;
        }
        
        .section-title {
            margin: 0 0 16px 0;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .quick-action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 12px;
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            text-decoration: none;
            color: var(--text-secondary);
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .quick-action-item:hover {
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .quick-action-item i {
            font-size: 20px;
        }
        
        .quick-action-item span {
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }
        
        /* Enhanced Actions Section */
        .dropdown-actions {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .dropdown-action-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 20px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-lg);
            transition: var(--transition);
            position: relative;
            border: 2px solid transparent;
        }
        
        .dropdown-action-item:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-decoration: none;
            transform: translateX(4px);
            border-color: var(--border-light);
        }
        
        .action-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: var(--bg-tertiary);
            border-radius: var(--radius-md);
        }
        
        .action-icon i {
            font-size: 14px;
        }
        
        .action-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .action-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .action-subtitle {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .action-arrow {
            font-size: 12px;
            color: var(--text-muted);
            transition: var(--transition);
        }
        
        .dropdown-action-item:hover .action-arrow {
            color: var(--primary-color);
            transform: translateX(4px);
        }
        
        /* Logout Section */
        .logout-section {
            background: rgba(231, 76, 60, 0.05);
            border-top: 2px solid var(--border-light);
        }
        
        .logout-item {
            color: var(--danger-color) !important;
        }
        
        .logout-item:hover {
            background: rgba(231, 76, 60, 0.1) !important;
            color: var(--danger-color) !important;
        }
        
        .logout-item .action-icon {
            background: rgba(231, 76, 60, 0.1);
            color: var(--danger-color);
        }
        
        .logout-item .action-title {
            color: var(--danger-color);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .topbar {
                left: 0;
                padding: 0 16px;
                height: 70px;
            }
            
            .desktop-toggle-btn {
                display: none;
            }
            
            .mobile-toggle-btn {
                display: flex;
            }
            
            .page-title h2 {
                font-size: 1.5rem;
            }
            
            .breadcrumb {
                display: none;
            }
            
            .user-info-text {
                display: none;
            }
            
            .dropdown-arrow {
                display: none;
            }
            
            .user-profile {
                padding: 8px;
                background: transparent;
                border: none;
                min-width: auto;
                border-radius: var(--radius-lg);
            }
            
            .user-profile:hover {
                background: var(--bg-secondary);
                border: 2px solid var(--border-light);
            }
            
            .user-avatar-topbar {
                width: 40px;
                height: 40px;
            }
            
            .dropdown-menu {
                right: -8px;
                width: 300px;
            }

            .notification-dropdown {
                right: -8px;
                width: 320px;
            }
        }
        
        @media (max-width: 480px) {
            .topbar {
                padding: 0 12px;
            }
            
            .topbar-left {
                gap: 16px;
            }
            
            .page-title h2 {
                font-size: 1.25rem;
            }
            
            .dropdown-menu {
                right: -12px;
                width: 280px;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }

            .notification-dropdown {
                right: -12px;
                width: 300px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const contentWrapper = document.querySelector('.content-wrapper') || document.body;
            const desktopToggle = document.getElementById('desktopSidebarToggle');
            const mobileToggle = document.getElementById('mobileSidebarToggle');
            const overlay = document.getElementById('sidebarOverlay');
            const userProfile = document.getElementById('userProfile');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            // Notification functionality
            const notificationToggle = document.getElementById('notificationToggle');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            if (notificationToggle && notificationDropdown) {
                notificationToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    notificationDropdown.classList.toggle('show');
                    
                    // Close user profile dropdown if open
                    if (userProfile) {
                        userProfile.classList.remove('active');
                    }
                });
                
                // Mark notifications as read when opened
                notificationToggle.addEventListener('click', function() {
                    <?php if($role === 'teknisi'): ?>
                    fetch('../../ajax/mark_notif_read.php').catch(err => console.log('Marking read failed:', err));
                    <?php endif; ?>
                });
            }
            
            // Ripple effect function
            function createRipple(event, element) {
                const ripple = element.querySelector('.ripple');
                if (!ripple) return;
                
                const rect = element.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple-animation 0.6s linear';
            }
            
            // Check if mobile
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Desktop sidebar toggle
            if (desktopToggle && sidebar) {
                desktopToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    createRipple(e, this);
                    
                    if (!isMobile()) {
                        sidebar.classList.toggle('collapsed');
                        
                        if (contentWrapper) {
                            if (sidebar.classList.contains('collapsed')) {
                                contentWrapper.classList.add('expanded');
                            } else {
                                contentWrapper.classList.remove('expanded');
                            }
                        }
                        
                        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                    }
                });
            }
            
            // Mobile sidebar toggle
            if (mobileToggle && sidebar) {
                mobileToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    createRipple(e, this);
                    
                    if (isMobile()) {
                        sidebar.classList.toggle('mobile-active');
                        if (overlay) {
                            overlay.classList.toggle('active');
                        }
                    }
                });
            }
            
            // User profile dropdown functionality
            if (userProfile) {
                userProfile.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.classList.toggle('active');
                    
                    // Close notification dropdown if open
                    if (notificationDropdown) {
                        notificationDropdown.classList.remove('show');
                    }
                });
                
                document.addEventListener('click', function(e) {
                    if (!userProfile.contains(e.target)) {
                        userProfile.classList.remove('active');
                    }
                });
                
                if (dropdownMenu) {
                    dropdownMenu.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                    
                    const dropdownItems = dropdownMenu.querySelectorAll('.dropdown-action-item');
                    dropdownItems.forEach(item => {
                        item.addEventListener('click', function(e) {
                            setTimeout(() => {
                                userProfile.classList.remove('active');
                            }, 100);
                        });
                    });
                }
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationToggle.contains(e.target)) {
                    notificationDropdown.classList.remove('show');
                }
                
                if (userProfile && !userProfile.contains(e.target)) {
                    userProfile.classList.remove('active');
                }
            });
            
            // Initialize sidebar state
            function initializeSidebarState() {
                if (!isMobile() && sidebar) {
                    const savedState = localStorage.getItem('sidebarCollapsed');
                    if (savedState === 'true') {
                        sidebar.classList.add('collapsed');
                        if (contentWrapper) {
                            contentWrapper.classList.add('expanded');
                        }
                    }
                }
            }
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    if (!isMobile() && sidebar) {
                        sidebar.classList.remove('mobile-active');
                        if (overlay) {
                            overlay.classList.remove('active');
                        }
                        initializeSidebarState();
                    }
                    
                    if (userProfile) {
                        userProfile.classList.remove('active');
                    }
                    
                    if (notificationDropdown) {
                        notificationDropdown.classList.remove('show');
                    }
                }, 250);
            });
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    if (!isMobile() && desktopToggle) {
                        desktopToggle.click();
                    } else if (isMobile() && mobileToggle) {
                        mobileToggle.click();
                    }
                }
                
                if (e.key === 'Escape') {
                    if (userProfile && userProfile.classList.contains('active')) {
                        userProfile.classList.remove('active');
                    }
                    if (notificationDropdown && notificationDropdown.classList.contains('show')) {
                        notificationDropdown.classList.remove('show');
                    }
                    if (isMobile() && sidebar && sidebar.classList.contains('mobile-active')) {
                        sidebar.classList.remove('mobile-active');
                        if (overlay) {
                            overlay.classList.remove('active');
                        }
                    }
                }
            });
            
            // Initialize
            initializeSidebarState();
            
            // Update profile photo function
            window.updateTopbarPhoto = function(newPhotoPath) {
                const topbarImages = document.querySelectorAll('.user-avatar-topbar img, .dropdown-avatar-large img');
                topbarImages.forEach(img => {
                    img.src = newPhotoPath;
                });
            };
        });
    </script>
<?php
}
?>
