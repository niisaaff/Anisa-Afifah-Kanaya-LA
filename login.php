    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - PT Telkom Akses</title>
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css">
        <!-- Google Fonts - Roboto & Montserrat -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Montserrat:wght@400;500;600;700&display=swap">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: 'Roboto', sans-serif;
            }
            
            body {
                background: #f5f5f7;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            
            .container {
                display: flex;
                width: 100%;
                max-width: 1100px;
                min-height: 600px;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
                opacity: 0;
                transform: translateY(20px);
                transition: all 0.5s ease;
                background-color: white;
            }
            
            .container.loaded {
                opacity: 1;
                transform: translateY(0);
            }
            
            /* Left side - Company info */
            .company-info {
                flex: 0 0 45%;
                background: linear-gradient(150deg, #E31E24 0%, #B71C1C 85%);
                color: white;
                padding: 50px 40px;
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                overflow: hidden;
            }
            
            .company-info::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5z' fill='rgba(255,255,255,0.05)' fill-rule='evenodd'/%3E%3C/svg%3E");
                opacity: 0.1;
            }
            
            .company-logo {
                text-align: center;
                margin-bottom: 25px;
                position: relative;
                z-index: 1;
            }
            
            .company-logo img {
                max-width: 220px;
                background-color: white;
                padding: 15px;
                border-radius: 12px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                transition: transform 0.3s ease;
            }
            
            .company-logo img:hover {
                transform: translateY(-5px);
            }
            
            .company-tagline {
                text-align: center;
                margin-bottom: 40px;
                position: relative;
                z-index: 1;
            }
            
            .company-tagline h1 {
                font-family: 'Montserrat', sans-serif;
                font-weight: 700;
                font-size: 28px;
                margin-bottom: 12px;
                text-transform: uppercase;
                letter-spacing: 1px;
                text-shadow: 0px 1px 3px rgba(0, 0, 0, 0.2);
            }
            
            .company-tagline p {
                font-size: 16px;
                opacity: 0.95;
                letter-spacing: 0.5px;
                font-weight: 300;
            }
            
            .company-features {
                margin-top: auto;
                position: relative;
                z-index: 1;
            }
            
            .feature-item {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding: 8px;
                border-radius: 10px;
                transition: all 0.3s ease;
            }
            
            .feature-item:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateX(5px);
            }
            
            .feature-icon {
                background: rgba(255, 255, 255, 0.2);
                width: 48px;
                height: 48px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 18px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
                font-size: 20px;
            }
            
            .feature-text h3 {
                font-family: 'Montserrat', sans-serif;
                font-size: 16px;
                font-weight: 600;
                margin-bottom: 4px;
            }
            
            .feature-text p {
                font-size: 14px;
                opacity: 0.95;
                line-height: 1.4;
                font-weight: 300;
            }
            
            /* Right side - Login form */
            .login-form {
                flex: 0 0 55%;
                background: white;
                padding: 60px 50px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            
            .login-header {
                text-align: center;
                margin-bottom: 40px;
            }
            
            .login-header h2 {
                font-family: 'Montserrat', sans-serif;
                font-size: 30px;
                font-weight: 700;
                color: #333;
                margin-bottom: 12px;
                background: linear-gradient(135deg, #E31E24, #B71C1C);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }
            
            .login-header p {
                color: #666;
                font-size: 16px;
                font-weight: 300;
            }
            
            .alert {
                padding: 16px;
                border-radius: 8px;
                margin-bottom: 30px;
                display: flex;
                align-items: center;
                background-color: #FFEBEE;
                color: #C62828;
                border-left: 4px solid #C62828;
                box-shadow: 0 4px 10px rgba(198, 40, 40, 0.1);
            }
            
            .alert i {
                margin-right: 12px;
                font-size: 20px;
            }
            
            .form-group {
                margin-bottom: 25px;
            }
            
            .form-label {
                display: block;
                margin-bottom: 8px;
                font-weight: 500;
                color: #444;
                font-size: 15px;
            }
            
            .input-group {
                position: relative;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                overflow: hidden;
                transition: all 0.3s ease;
            }
            
            .input-group:focus-within {
                border-color: #E31E24;
                box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.1);
            }
            
            .input-group.input-focus {
                border-color: #E31E24;
                box-shadow: 0 0 0 3px rgba(227, 30, 36, 0.1);
            }
            
            .input-group-icon {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: #E31E24;
                font-size: 18px;
            }
            
            .form-control {
                width: 100%;
                padding: 14px 14px 14px 45px;
                border: none;
                outline: none;
                font-size: 15px;
                background: transparent;
                color: #333;
            }
            
            .form-control::placeholder {
                color: #bbb;
                font-weight: 300;
            }
            
            .password-toggle-btn {
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                color: #777;
                cursor: pointer;
                font-size: 16px;
                padding: 5px;
            }
            
            .password-toggle-btn:hover {
                color: #E31E24;
            }
            
            .form-check {
                display: flex;
                align-items: center;
                margin-bottom: 30px;
            }
            
            .form-check-input {
                margin-right: 12px;
                width: 18px;
                height: 18px;
                accent-color: #E31E24;
                cursor: pointer;
            }
            
            .form-check-label {
                font-size: 14px;
                color: #555;
                cursor: pointer;
            }
            
            .form-action {
                margin-top: 15px;
            }
            
            .btn-login {
                background: linear-gradient(135deg, #E31E24 0%, #B71C1C 100%);
                color: white;
                font-family: 'Montserrat', sans-serif;
                font-weight: 600;
                padding: 14px 20px;
                border-radius: 8px;
                width: 100%;
                font-size: 16px;
                border: none;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 1px;
                box-shadow: 0 4px 12px rgba(227, 30, 36, 0.3);
            }
            
            .btn-login:hover {
                background: linear-gradient(135deg, #D32F2F 0%, #B71C1C 100%);
                transform: translateY(-2px);
                box-shadow: 0 6px 15px rgba(227, 30, 36, 0.4);
            }
            
            .btn-login:active {
                transform: translateY(0);
                box-shadow: 0 3px 8px rgba(227, 30, 36, 0.3);
            }
            
            .btn-login i {
                margin-left: 10px;
                font-size: 18px;
            }
            
            .login-footer {
                text-align: center;
                margin-top: 40px;
                color: #777;
                font-size: 13px;
                font-weight: 300;
            }
            
            .back-to-home {
                margin-top: 16px;
            }
            
            .btn-back {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #666;
                font-size: 14px;
                text-decoration: none;
                padding: 8px 18px;
                border-radius: 6px;
                transition: all 0.3s ease;
                border: 1px solid #e0e0e0;
                background-color: #fafafa;
            }
            
            .btn-back:hover {
                background-color: #FFEBEE;
                color: #E31E24;
                border-color: #FFCDD2;
            }
            
            .btn-back i {
                margin-right: 8px;
                font-size: 16px;
            }
            
            /* Responsive styles */
            @media (max-width: 992px) {
                .container {
                    flex-direction: column;
                    max-width: 600px;
                }
                
                .company-info, .login-form {
                    flex: 0 0 100%;
                    padding: 40px 30px;
                }
                
                .company-info {
                    padding-bottom: 50px;
                }
                
                .company-features {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: space-between;
                }
                
                .feature-item {
                    width: 48%;
                    margin-bottom: 15px;
                }
            }
            
            @media (max-width: 768px) {
                .company-features .feature-item {
                    width: 100%;
                }
            }
            
            @media (max-width: 576px) {
                .container {
                    box-shadow: none;
                    border-radius: 12px;
                }
                
                .company-info, .login-form {
                    padding: 30px 20px;
                }
                
                .login-header h2 {
                    font-size: 26px;
                }
                
                .form-control {
                    padding: 12px 12px 12px 42px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Left side - Company info -->
            <div class="company-info">
                <div>
                    <div class="company-logo">
                        <img src="img/logo.png" alt="Telkom Akses Logo">
                    </div>
                    
                    <div class="company-tagline">
                        <h1>PT Telkom Akses</h1>
                        <p>Sistem Monitoring Fiber Optic</p>
                    </div>
                </div>
                
                <div class="company-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Keamanan Terjamin</h3>
                            <p>Sistem monitoring dengan keamanan tingkat tinggi</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Analisis Real-time</h3>
                            <p>Pantau kinerja jaringan secara real-time</p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-gear"></i>
                        </div>
                        <div class="feature-text">
                            <h3>Kemudahan Akses</h3>
                            <p>Akses sistem dari mana saja dan kapan saja</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right side - Login form -->
            <div class="login-form">
                <div class="login-header">
                    <h2>Selamat Datang</h2>
                    <p>Masuk ke akun Anda untuk melanjutkan</p>
                </div>
                
                <!-- Show error message if exists -->
                <div class="alert" id="error-alert" style="display: none;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Username atau password salah!</span>
                </div>
                
                <form action="login_action.php" method="post">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-icon">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-icon">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                            <button type="button" id="togglePassword" class="password-toggle-btn">
                                <i class="bi bi-eye-slash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>
                    
                    <div class="form-action">
                        <button type="submit" class="btn-login">
                            <span>Login</span>
                            <i class="bi bi-arrow-right-circle-fill"></i>
                        </button>
                    </div>
                </form>
                
                <div class="login-footer">
                    <p>Â© 2025 PT Telkom Akses. All Rights Reserved.</p>
                    <div class="back-to-home">
                        <a href="index.php" class="btn-back">
                            <i class="bi bi-house-fill"></i>
                            <span>Kembali ke Beranda</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Check if error parameter exists in URL
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.has('error')) {
                    document.getElementById('error-alert').style.display = 'flex';
                }
                
                // Toggle password visibility
                const togglePassword = document.getElementById('togglePassword');
                const passwordInput = document.getElementById('password');
                
                if (togglePassword) {
                    togglePassword.addEventListener('click', function() {
                        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                        passwordInput.setAttribute('type', type);
                        
                        // Toggle eye icon
                        const eyeIcon = togglePassword.querySelector('i');
                        if (type === 'password') {
                            eyeIcon.classList.remove('bi-eye');
                            eyeIcon.classList.add('bi-eye-slash');
                        } else {
                            eyeIcon.classList.remove('bi-eye-slash');
                            eyeIcon.classList.add('bi-eye');
                        }
                    });
                }
                
                // Add animations
                const container = document.querySelector('.container');
                setTimeout(() => {
                    container.classList.add('loaded');
                }, 100);
                
                // Add focus effects
                const formInputs = document.querySelectorAll('.form-control');
                formInputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        this.parentElement.classList.add('input-focus');
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement.classList.remove('input-focus');
                    });
                });
            });
        </script>
    </body>
    </html>