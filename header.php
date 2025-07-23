<?php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitratel Monitoring - PT Telkom Akses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --telkom-red: #e42313;
            --telkom-gray: #4a4a4a;
            --telkom-light-gray: #f8f9fa;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .navbar-telkom {
            background-color: #ffffff;
            padding: 0.9rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .logo-container {
            transition: all 0.3s ease;
            background: transparent;
            padding: 5px 0;
            display: flex;
            align-items: center;
        }

        .logo-container:hover {
            transform: translateY(-1px);
        }

        .nav-item {
            margin: 0 5px;
        }

        .nav-link-telkom {
            color: var(--telkom-gray) !important;
            transition: all 0.3s ease;
            padding: 12px 24px !important;
            font-weight: 500;
            font-size: 1rem;
            position: relative;
        }

        .nav-link-telkom:hover {
            color: var(--telkom-red) !important;
        }

        .nav-link-telkom.active {
            color: var(--telkom-red) !important;
            font-weight: 600;
        }

        .nav-link-telkom,
        .dropdown-item-telkom {
            text-decoration: none !important;
        }
        
        .nav-link-telkom:hover,
        .nav-link-telkom:focus,
        .dropdown-item-telkom:hover,
        .dropdown-item-telkom:focus {
            text-decoration: none !important;
        }
        
        .navbar-brand:hover {
            text-decoration: none !important;
        }

        .dropdown-menu-telkom {
            border: none;
            border-radius: 15px;
            backdrop-filter: blur(12px);
            background: rgba(255,255,255,0.98);
            box-shadow: 0 10px 35px rgba(0,0,0,0.1);
            margin-top: 10px !important;
            overflow: hidden;
            border-top: 3px solid var(--primary-orange);
        }

        .dropdown-item-telkom {
            border-radius: 8px;
            transition: all 0.25s ease;
            margin: 5px;
            padding: 10px 15px;
        }

        .dropdown-item-telkom:hover {
            background: linear-gradient(135deg, rgba(255, 140, 0, 0.12) 0%, rgba(237, 27, 36, 0.12) 100%);
            color: var(--primary-red) !important;
            transform: translateX(5px);
        }
        
        /* Dropdown caret styling */
        .nav-link-telkom .dropdown-toggle::after {
            margin-left: 0.4rem;
            vertical-align: middle;
            color: #aaa;
        }
        
        /* Brand name styling */
        .brand-name {
            font-weight: 600;
            letter-spacing: 0.3px;
            color: var(--telkom-gray);
            font-size: 1rem;
            display: none;
        }
        
        /* Elegant hover for dropdown items */
        .dropdown-item-telkom i {
            transition: transform 0.3s ease;
        }
        
        .dropdown-item-telkom:hover i {
            transform: translateX(3px);
        }
        
        /* Navbar toggler animation */
        .navbar-toggler {
            border: 2px solid rgba(237, 27, 36, 0.3) !important;
            border-radius: 8px;
            padding: 8px;
            transition: all 0.3s ease;
        }
        
        .navbar-toggler:hover {
            background: rgba(237, 27, 36, 0.1);
            transform: rotate(5deg);
        }
        
        /* Enhanced Login Button */
        .login-btn {
            background-color: var(--telkom-red);
            color: white !important;
            padding: 8px 28px !important;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            border-radius: 4px;
        }
        
        .login-btn:hover {
            background-color: #c91e10;
            color: white !important;
        }
        
        .login-btn i {
            font-size: 1.1rem;
            margin-right: 5px;
        }
        
        /* Search icon styling */
        .search-icon {
            color: var(--telkom-gray);
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .search-icon:hover {
            color: var(--telkom-red);
        }
        
        /* Language selector styling */
        .language-selector {
            display: flex;
            align-items: center;
            font-weight: 500;
            color: var(--telkom-gray);
        }
        
        /* Custom navbar container */
        .navbar-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 0 15px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-telkom">
    <div class="container">
        <!-- Brand Section -->
        <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
            <div class="logo-container">
                <img src="img/logo.png" 
                     alt="Telkom Indonesia Logo" 
                     height="45"
                     class="d-inline-block align-text-top">
            </div>
        </a>

        <!-- Toggle Button -->
        <button class="navbar-toggler border-0" 
                type="button" 
                data-bs-toggle="collapse" 
                data-bs-target="#mainNavbar">
            <i class="bi bi-list text-danger fs-4"></i>
        </button>

        <!-- Navigation Content -->
        <div class="collapse navbar-collapse" id="mainNavbar">
    <!-- Main Menu - Left Side -->
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
            <a class="nav-link-telkom text-decoration-none" href="index.php">
                <i class="fas fa-house me-2"></i> Beranda
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link-telkom text-decoration-none" href="tentang.php">
                <i class="fas fa-circle-info me-2"></i> Tentang
            </a>
        </li>
    </ul>

            <!-- Right Menu -->
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link-telkom dropdown-toggle d-flex align-items-center text-decoration-none" 
                           href="#" 
                           role="button" 
                           data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="text-nowrap"><?= htmlspecialchars($_SESSION['username']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-telkom dropdown-menu-end">
                            <li>
                                <a class="dropdown-item dropdown-item-telkom d-flex align-items-center text-decoration-none" 
                                   href="dashboard/<?= $_SESSION['user_role'] ?>/">
                                    <i class="bi bi-speedometer2 me-2"></i>
                                    Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item dropdown-item-telkom d-flex align-items-center text-decoration-none" 
                                   href="pengaturan.php">
                                    <i class="bi bi-gear me-2"></i>
                                    Pengaturan
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item dropdown-item-telkom d-flex align-items-center text-danger text-decoration-none" 
                                   href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="login-btn d-flex align-items-center text-decoration-none" href="login.php">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span class="d-none d-lg-inline ms-1">Login</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>