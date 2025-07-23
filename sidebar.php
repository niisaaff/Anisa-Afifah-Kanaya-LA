<?php
function showSidebar($userRole) {
    // Get current page filename
    $currentPage = basename($_SERVER['PHP_SELF']);
    ?>
    <div id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <div class="telkom-logo">
                <div class="logo-container">
                    <img src="../../img/logo.png" alt="Telkom Logo">
                </div>
                <div class="brand-text">
                    <h3>PT Telkom Akses</h3>
                    <span class="brand-subtitle">Monitoring Fiber Optic</span>
                </div>
            </div>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li class="menu-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <a href="<?php 
                        if($userRole == 'admin') {
                            echo '../admin/';
                        } elseif($userRole == 'supervisor') {
                            echo '../supervisor/';
                        } else {
                            echo '../teknisi/';
                        }
                    ?>index.php">
                        <div class="menu-icon">
                            <i class="fa fa-home"></i>
                        </div>
                        <span class="menu-text">Dashboard</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                
                <?php if($userRole == 'admin') { ?>
                <li class="menu-item <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                    <a href="../admin/users.php">
                        <div class="menu-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <span class="menu-text">Manage Users</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'create_tiket.php') ? 'active' : ''; ?>">
                    <a href="../admin/create_tiket.php">
                        <div class="menu-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <span class="menu-text">Ticket</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'assign_teknisi.php') ? 'active' : ''; ?>">
                    <a href="../admin/assign_teknisi.php">
                        <div class="menu-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <span class="menu-text">Assign</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'report.php') ? 'active' : ''; ?>">
                    <a href="../admin/report.php">
                        <div class="menu-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <span class="menu-text">Manage Report</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                 <!-- Divider -->
                <hr style="border-top: 1px solid #dc3545; margin-top: 0; margin-bottom: 0;">
                <!-- Heading -->
                <div class="sidebar-heading text-danger" style="font-size: 12px; padding-left: 1rem; padding-top: 0.5rem;">
                    Akun
                </div>
                <li class="menu-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                    <a href="../admin/profile.php">
                        <div class="menu-icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <span class="menu-text">Profile</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                
                <?php } elseif($userRole == 'supervisor') { ?>
                <li class="menu-item <?php echo ($currentPage == 'monitor_teknisi.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/monitor_teknisi.php">
                        <div class="menu-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <span class="menu-text">Monitor Teknisi</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'view_tickets.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/view_tickets.php">
                        <div class="menu-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <span class="menu-text">View Tickets</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'progress_report.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/progress_report.php">
                        <div class="menu-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="menu-text">Progress Report</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'approve_task.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/approve_task.php">
                        <div class="menu-icon">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <span class="menu-text">Approve Task</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'report.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/report.php">
                        <div class="menu-icon">
                            <i class="fas fa-wrench"></i>
                        </div>
                        <span class="menu-text">Manage Report</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <!-- Divider -->
                <hr style="border-top: 1px solid #dc3545; margin-top: 0; margin-bottom: 0;">
                <!-- Heading -->
                <div class="sidebar-heading text-danger" style="font-size: 12px; padding-left: 1rem; padding-top: 0.5rem;">
                    Akun
                </div>
                <li class="menu-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                    <a href="../supervisor/profile.php">
                        <div class="menu-icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <span class="menu-text">Profile</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                
                <?php } else { ?>
                <li class="menu-item <?php echo ($currentPage == 'view_tiket.php') ? 'active' : ''; ?>">
                    <a href="../teknisi/view_tiket.php">
                        <div class="menu-icon">
                            <i class="fa fa-tasks"></i>
                        </div>
                        <span class="menu-text">Tasks</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <li class="menu-item <?php echo ($currentPage == 'task_report.php') ? 'active' : ''; ?>">
                    <a href="../teknisi/task_report.php">
                        <div class="menu-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <span class="menu-text">Task Report</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <!-- Divider -->
                <hr style="border-top: 1px solid #dc3545; margin-top: 0; margin-bottom: 0;">
                <!-- Heading -->
                <div class="sidebar-heading text-danger" style="font-size: 12px; padding-left: 1rem; padding-top: 0.5rem;">
                    Akun
                </div>
                <li class="menu-item <?php echo ($currentPage == 'profile.php') ? 'active' : ''; ?>">
                    <a href="../teknisi/profile.php">
                        <div class="menu-icon">
                            <i class="fa fa-user"></i>
                        </div>
                        <span class="menu-text">Profile</span>
                        <div class="menu-indicator"></div>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </div>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <i class="fa fa-user"></i>
                    <div class="status-indicator"></div>
                </div>
                <div class="user-details">
                    <span class="user-name">User</span>
                    <span class="user-role"><?php echo ucfirst($userRole); ?></span>
                </div>
            </div>
            <div class="logout-section">
                <a href="../../logout.php" class="logout-btn">
                    <div class="menu-icon">
                        <i class="fas fa-sign-out-alt"></i>
                    </div>
                    <span class="menu-text">Sign Out</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Mobile Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap');
        
        * {
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #e74c3c;
            --primary-dark: #c0392b;
            --text-primary: #2c3e50;
            --text-secondary: #7f8c8d;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 20px rgba(0,0,0,0.12);
            --sidebar-width: 260px;
            --sidebar-collapsed: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Reset default styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            background: var(--bg-primary);
            transition: var(--transition);
            box-shadow: var(--shadow-lg);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
        }
        
        .sidebar.collapsed {
            width: var(--sidebar-collapsed);
        }
        
        .sidebar-header {
            padding: 18px 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            min-height: 75px;
        }
        
        .sidebar-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .telkom-logo {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 1;
            overflow: hidden;
            width: 100%;
        }
        
        .logo-container {
            width: 40px;
            height: 40px;
            background: rgb(255, 255, 255);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgb(255, 255, 255);
            transition: var(--transition);
            flex-shrink: 0;
        }
        
        .logo-container img {
            height: 24px;
            border-radius: 6px;
        }
        
        .brand-text {
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .brand-text h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            line-height: 1.2;
            letter-spacing: -0.02em;
            white-space: nowrap;
        }
        
        .brand-subtitle {
            font-size: 0.7rem;
            opacity: 0.8;
            font-weight: 400;
            margin-top: 1px;
            white-space: nowrap;
        }
        
        .sidebar.collapsed .brand-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        
        .sidebar-menu {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .sidebar-menu::-webkit-scrollbar {
            width: 4px;
        }
        
        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 2px;
        }
        
        .sidebar-menu::-webkit-scrollbar-thumb:hover {
            background: #cbd5e0;
        }
        
        .sidebar-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .menu-item {
            margin: 0 12px 6px;
            position: relative;
        }
        
        .menu-item a {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 12px;
            position: relative;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .menu-item a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.08), rgba(192, 57, 43, 0.08));
            opacity: 0;
            transition: var(--transition);
        }
        
        .menu-item:hover a::before {
            opacity: 1;
        }
        
        .menu-item.active a::before {
            opacity: 1;
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(192, 57, 43, 0.15));
        }
        
        .menu-icon {
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }
        
        .menu-icon i {
            font-size: 16px;
            color: var(--primary-color);
            transition: var(--transition);
        }
        
        .menu-text {
            font-weight: 500;
            font-size: 0.85rem;
            white-space: nowrap;
            position: relative;
            z-index: 1;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .menu-indicator {
            position: absolute;
            right: 16px;
            width: 4px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 50%;
            opacity: 0;
            transform: scale(0);
            transition: var(--transition);
        }
        
        .menu-item:hover a,
        .menu-item.active a {
            color: var(--text-primary);
            transform: translateX(3px);
        }
        
        .menu-item.active .menu-indicator {
            opacity: 1;
            transform: scale(1);
        }
        
        .sidebar.collapsed .menu-text,
        .sidebar.collapsed .menu-indicator {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        
        .sidebar.collapsed .menu-icon {
            margin-right: 0;
        }
        
        .sidebar.collapsed .menu-item a {
            justify-content: center;
            padding: 12px;
        }
        
        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border-color);
            background: var(--bg-secondary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            padding: 12px;
            background: var(--bg-primary);
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            color: white;
            font-size: 16px;
            position: relative;
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }
        
        .status-indicator {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 10px;
            height: 10px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            overflow: hidden;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-primary);
            line-height: 1.2;
            margin-bottom: 1px;
            white-space: nowrap;
        }
        
        .user-role {
            font-size: 0.7rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .sidebar.collapsed .user-details {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }
        
        .logout-section {
            margin-top: 6px;
        }
        
        .logout-btn {
            width: 100%;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            color: #ef4444;
            text-decoration: none;
            border-radius: 10px;
            transition: var(--transition);
            background: transparent;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.05);
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .logout-btn .menu-icon {
            color: #ef4444;
        }
        
        .sidebar.collapsed .logout-btn {
            justify-content: center;
            padding: 10px;
        }
        
        .content-wrapper {
            margin-left: var(--sidebar-width);
            transition: var(--transition);
            min-height: 100vh;
        }
        
        .content-wrapper.expanded {
            margin-left: var(--sidebar-collapsed);
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
            backdrop-filter: blur(3px);
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            :root {
                --sidebar-width: 240px;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
                left: -280px;
                z-index: 1001;
            }
            
            .sidebar.mobile-active {
                left: 0;
                box-shadow: var(--shadow-lg);
            }
            
            .content-wrapper {
                margin-left: 0 !important;
            }
            
            .content-wrapper.expanded {
                margin-left: 0 !important;
            }
            
            .sidebar.collapsed {
                width: 280px;
                left: -280px;
            }
            
            .sidebar.collapsed.mobile-active {
                left: 0;
            }
            
            /* Reset collapsed styles for mobile */
            .sidebar.collapsed .brand-text,
            .sidebar.collapsed .menu-text,
            .sidebar.collapsed .user-details {
                opacity: 1 !important;
                width: auto !important;
                overflow: visible !important;
            }
            
            .sidebar.collapsed .menu-item a {
                justify-content: flex-start !important;
                padding: 12px 16px !important;
            }
            
            .sidebar.collapsed .menu-icon {
                margin-right: 12px !important;
            }
            
            .sidebar.collapsed .logout-btn {
                justify-content: flex-start !important;
                padding: 10px 16px !important;
            }
        }
        
        @media (max-width: 480px) {
            .sidebar {
                width: 260px;
                left: -260px;
            }
            
            .sidebar.mobile-active {
                left: 0;
            }
        }
        
        /* Smooth animations */
        @media (prefers-reduced-motion: no-preference) {
            .menu-item:hover .menu-icon i {
                animation: bounce 0.5s ease-in-out;
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% {
                    transform: translateY(0);
                }
                40% {
                    transform: translateY(-3px);
                }
                60% {
                    transform: translateY(-1px);
                }
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const contentWrapper = document.querySelector('.content-wrapper') || document.body;
            const overlay = document.getElementById('sidebarOverlay');
            
            // Check if mobile
            function isMobile() {
                return window.innerWidth <= 768;
            }
            
            // Initialize sidebar state
            function initializeSidebar() {
                if (!isMobile()) {
                    // Show sidebar on desktop
                    sidebar.style.display = 'flex';
                    sidebar.classList.remove('mobile-active');
                    overlay.classList.remove('active');
                    
                    // Check saved state for collapsed
                    const sidebarState = localStorage.getItem('sidebarCollapsed');
                    if (sidebarState === 'true') {
                        sidebar.classList.add('collapsed');
                        if (contentWrapper && !contentWrapper.classList.contains('expanded')) {
                            contentWrapper.classList.add('expanded');
                        }
                    } else {
                        sidebar.classList.remove('collapsed');
                        if (contentWrapper) {
                            contentWrapper.classList.remove('expanded');
                        }
                    }
                } else {
                    // Mobile setup
                    sidebar.classList.remove('collapsed');
                    if (contentWrapper) {
                        contentWrapper.classList.remove('expanded');
                        contentWrapper.style.marginLeft = '0';
                    }
                }
            }
            
            // Close sidebar when clicking overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    if (isMobile()) {
                        sidebar.classList.remove('mobile-active');
                        overlay.classList.remove('active');
                    }
                });
            }
            
            // Prevent closing when clicking inside sidebar
            sidebar.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            
            // Handle window resize
            let resizeTimeout;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(function() {
                    initializeSidebar();
                }, 150);
            });
            
            // Close mobile menu when clicking menu items
            const menuLinks = sidebar.querySelectorAll('.menu-item a, .logout-btn');
            menuLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (isMobile() && sidebar.classList.contains('mobile-active')) {
                        setTimeout(() => {
                            sidebar.classList.remove('mobile-active');
                            overlay.classList.remove('active');
                        }, 150);
                    }
                });
            });
            
            // Keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && isMobile() && sidebar.classList.contains('mobile-active')) {
                    sidebar.classList.remove('mobile-active');
                    overlay.classList.remove('active');
                }
            });
            
            // Initialize on load
            initializeSidebar();
            
            // Listen for external collapse toggle (from topbar)
            document.addEventListener('sidebarToggle', function() {
                if (!isMobile()) {
                    sidebar.classList.toggle('collapsed');
                    
                    if (contentWrapper) {
                        if (sidebar.classList.contains('collapsed')) {
                            contentWrapper.classList.add('expanded');
                        } else {
                            contentWrapper.classList.remove('expanded');
                        }
                    }
                    
                    // Save state
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });
            
            // Listen for mobile toggle (from topbar)
            document.addEventListener('mobileSidebarToggle', function() {
                if (isMobile()) {
                    sidebar.classList.toggle('mobile-active');
                    overlay.classList.toggle('active');
                }
            });
        });
    </script>
    <?php
}
?>
