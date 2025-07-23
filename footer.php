<footer class="telkom-footer">
    <div class="container py-5">
        <div class="row g-4">
            <!-- Company Info & Support Section -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-section">
                    <!-- Logo/Brand -->
                    <div class="brand-section mb-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="brand-icon">
                                <i class="fas fa-network-wired"></i>
                            </div>
                            <div>
                                <h4 class="brand-name mb-0">Telkom Akses</h4>
                                <p class="brand-tagline mb-0">Connecting Indonesia</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Info -->
                    <div class="support-info">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="support-icon">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <h6 class="support-title mb-1">Support 24/7</h6>
                                <p class="support-desc mb-0">Layanan gangguan jaringan fiber optic</p>
                            </div>
                        </div>
                        
                        <button class="btn btn-telkom mt-3">
                            <i class="fas fa-phone-alt me-2"></i>Hubungi Kami
                        </button>
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-section">
                    <h5 class="section-title mb-4">Kontak Kami</h5>
                    <ul class="contact-list">
                        <li class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="contact-info">
                                <span>Jl. S. Parman Kav. 8, Jakarta Barat 11440</span>
                            </div>
                        </li>
                        <li class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="contact-info">
                                <span>+62 21 521 3711</span>
                            </div>
                        </li>
                        <li class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-info">
                                <span>support@telkomakses.co.id</span>
                            </div>
                        </li>
                        <li class="contact-item">
                            <div class="contact-icon">
                                <i class="fas fa-globe"></i>
                            </div>
                            <div class="contact-info">
                                <span>www.telkomakses.co.id</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Services & Features -->
            <div class="col-lg-4 col-md-12">
                <div class="footer-section">
                    <h5 class="section-title mb-4">Layanan Unggulan</h5>
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <span>Jaringan Andal</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-wifi"></i>
                            </div>
                            <span>Coverage Nasional</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <span>High Speed</span>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-tools"></i>
                            </div>
                            <span>Maintenance 24/7</span>
                        </div>
                    </div>

                    <!-- Social Media -->
                    <div class="social-section mt-4">
                        <h6 class="social-title mb-3">Ikuti Kami</h6>
                        <div class="social-links">
                            <a href="#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="#" class="social-link">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Copyright Section -->
        <div class="footer-bottom mt-5 pt-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="copyright mb-0">
                        © 2025 PT Telkom Akses. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6">
                    <div class="footer-links">
                        <a href="#" class="footer-link">Kebijakan Privasi</a>
                        <a href="#" class="footer-link">Syarat Layanan</a>
                        <a href="#" class="footer-link">FAQ</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Telkom Akses Footer Styles - Red-Maroon Gradient */
    .telkom-footer {
        background: linear-gradient(135deg, #800020 0%, #A0002A 20%, #722F37 40%, #8B0000 60%, #5D1A1D 80%, #4A0E0E 100%);
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .telkom-footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
        opacity: 0.4;
    }

    .telkom-footer .container {
        position: relative;
        z-index: 1;
    }

    /* Brand Section */
    .brand-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(45deg, #D2691E, #CD853F);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 4px 15px rgba(210, 105, 30, 0.4);
    }

    .brand-name {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.5rem;
        letter-spacing: -0.5px;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.4);
    }

    .brand-tagline {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        font-style: italic;
    }

    /* Support Section */
    .support-icon {
        width: 45px;
        height: 45px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #DEB887;
        font-size: 1.2rem;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(222, 184, 135, 0.3);
    }

    .support-title {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .support-desc {
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.9rem;
    }

    /* Button Styles */
    .btn-telkom {
        background: linear-gradient(45deg, #D2691E, #CD853F);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(210, 105, 30, 0.4);
    }

    .btn-telkom:hover {
        background: linear-gradient(45deg, #CD853F, #D2691E);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(210, 105, 30, 0.5);
        color: white;
    }

    /* Section Titles */
    .section-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 0.5rem;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.4);
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(45deg, #D2691E, #CD853F);
        border-radius: 2px;
    }

    /* Contact List */
    .contact-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .contact-item {
        display: flex;
        align-items: center;
        margin-bottom: 1rem;
        padding: 0.5rem 0;
    }

    .contact-icon {
        width: 35px;
        height: 35px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #DEB887;
        margin-right: 1rem;
        flex-shrink: 0;
        border: 1px solid rgba(222, 184, 135, 0.2);
    }

    .contact-info span {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.95rem;
    }

    /* Features Grid */
    .features-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.06);
        border-radius: 10px;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .feature-item:hover {
        background: rgba(255, 255, 255, 0.12);
        transform: translateY(-2px);
        border-color: rgba(222, 184, 135, 0.3);
    }

    .feature-icon {
        width: 30px;
        height: 30px;
        background: linear-gradient(45deg, #D2691E, #CD853F);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .feature-item span {
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* Social Media */
    .social-title {
        color: #ffffff;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .social-links {
        display: flex;
        gap: 0.75rem;
    }

    .social-link {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .social-link:hover {
        background: linear-gradient(45deg, #D2691E, #CD853F);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(210, 105, 30, 0.4);
    }

    /* Footer Bottom */
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.15);
    }

    .copyright {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
    }

    .footer-links {
        display: flex;
        gap: 1.5rem;
        justify-content: flex-end;
    }

    .footer-link {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }

    .footer-link:hover {
        color: #DEB887;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .footer-links {
            justify-content: flex-start;
            margin-top: 1rem;
        }
        
        .brand-name {
            font-size: 1.3rem;
        }
        
        .social-links {
            justify-content: flex-start;
        }
    }

    @media (max-width: 576px) {
        .telkom-footer .container {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .contact-item {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        
        .contact-icon {
            margin-right: 0;
            margin-bottom: 0.5rem;
        }
    }
</style>

<!-- Dependencies -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
