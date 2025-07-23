<?php 
// Memuat konfigurasi
require_once 'config/config.php';

// Header sudah termasuk memulai session
include 'includes/header.php';
?>

<style>
    :root {
        --primary-red-light: #FF3333;
        --primary-red: #E01A1A;
        --primary-red-dark: #B60000;
        --primary-red-darker: #8B0000;
        --hover-red: #8B0000;
        --text-dark: #2D3748;
        --text-light: #718096;
        --bg-light: #FEF2F2;
        --bg-gradient: linear-gradient(135deg, var(--primary-red-light) 0%, var(--primary-red-dark) 100%);
        --shadow-sm: 0 4px 6px rgba(224, 26, 26, 0.1);
        --shadow-md: 0 6px 15px rgba(224, 26, 26, 0.15);
        --shadow-lg: 0 10px 25px rgba(224, 26, 26, 0.2);
        --border-radius-sm: 8px;
        --border-radius-md: 12px;
        --border-radius-lg: 20px;
    }

    body {
        font-family: 'Poppins', sans-serif;
        color: var(--text-dark);
        line-height: 1.7;
    }

    .section-divider {
        height: 2px;
        background: var(--bg-gradient);
        width: 80px;
        margin: 0 auto 2rem;
        opacity: 0.8;
    }

    /* Hero Section Styles */
    .hero-section {
        position: relative;
        overflow: hidden;
        min-height: 60vh;
        display: flex;
        align-items: center;
        padding: 6rem 0;
        margin-bottom: 3rem;
    }

    .hero-bg {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('img/foto4.jpg') center/cover no-repeat;
        z-index: -2;
        transform: scale(1.05);
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 51, 51, 0.85) 0%, rgba(139, 0, 0, 0.9) 100%);
        z-index: -1;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        color: white;
    }

    .hero-heading {
        font-size: 3.5rem;
        font-weight: 700;
        margin-bottom: 1rem;
        letter-spacing: 1px;
        text-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .hero-heading span {
        color: #FFD7D7;
        position: relative;
        display: inline-block;
    }

    .hero-heading span::after {
        content: '';
        position: absolute;
        bottom: -5px;
        left: 0;
        width: 100%;
        height: 3px;
        background: #FFD7D7;
        transform: scaleX(0);
        transform-origin: right;
        transition: transform 0.6s cubic-bezier(0.19, 1, 0.22, 1);
    }

    .hero-section:hover .hero-heading span::after {
        transform: scaleX(1);
        transform-origin: left;
    }

    .hero-subheading {
        font-size: 1.8rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
        opacity: 0.9;
    }

    .hero-text {
        font-size: 1.2rem;
        max-width: 700px;
        opacity: 0.85;
    }

    /* Section Styles */
    .section-title {
        font-weight: 700;
        margin-bottom: 2rem;
        position: relative;
    }

    .section-subtitle {
        color: var(--primary-red);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
    }

    .text-gradient {
        background: var(--bg-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;
    }

    .main-wrapper {
        background-color: white;
        overflow: hidden;
    }

    /* About Section Styles */
    .about-section {
        padding: 6rem 0;
    }

    .about-image-container {
        position: relative;
        border-radius: var(--border-radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-lg);
        transform: perspective(1000px) rotateY(5deg);
        transition: all 0.5s ease;
    }

    .about-image-container:hover {
        transform: perspective(1000px) rotateY(0);
    }

    .about-image {
        position: relative;
        overflow: hidden;
        border-radius: var(--border-radius-lg);
    }

    .about-image img {
        transition: transform 0.6s cubic-bezier(0.19, 1, 0.22, 1);
    }

    .about-image-container:hover .about-image img {
        transform: scale(1.05);
    }

    .about-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .about-text {
        color: var(--text-light);
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }

    .feature-item {
        display: flex;
        margin-bottom: 2rem;
        align-items: flex-start;
    }

    .feature-icon-container {
        width: 50px;
        height: 50px;
        min-width: 50px;
        border-radius: 50%;
        background: var(--bg-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1.5rem;
        box-shadow: var(--shadow-sm);
    }

    .feature-icon-small {
        color: white;
        font-size: 1.2rem;
    }

    .feature-content h5 {
        font-weight: 600;
        margin-bottom: 0.5rem;
        font-size: 1.2rem;
    }

    .feature-content p {
        color: var(--text-light);
        margin: 0;
    }

    /* Services Section Styles */
    .services-section {
        padding: 6rem 0;
        background-color: var(--bg-light);
    }

    .service-card {
        height: 100%;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        transition: all 0.3s ease;
        background: white;
    }

    .service-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .service-card-header {
        background: var(--bg-gradient);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .service-card-header::before {
        content: '';
        position: absolute;
        width: 200%;
        height: 200%;
        background: rgba(255,255,255,0.1);
        top: -50%;
        left: -50%;
        transform: rotate(35deg);
        transition: all 0.5s ease;
    }

    .service-card:hover .service-card-header::before {
        transform: rotate(35deg) translateX(10%);
    }

    .service-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .service-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

    .service-body {
        padding: 2rem;
    }

    .service-text {
        color: var(--text-light);
        margin-bottom: 1.5rem;
    }

    .service-body ul {
        padding-left: 1.5rem;
        color: var(--text-light);
    }

    .service-body ul li {
        margin-bottom: 0.5rem;
    }

    /* Team Section Styles */
    .card {
        border-radius: var(--border-radius-md);
        overflow: hidden;
        transition: all 0.3s ease;
        border: none;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
    }

    .card-img-top {
        height: 250px;
        object-fit: cover;
        transition: all 0.5s ease;
    }

    .card:hover .card-img-top {
        transform: scale(1.05);
    }

    .card-title {
        margin-bottom: 0.25rem;
    }

    /* FAQ Section Styles */
    .accordion-item {
        border-radius: var(--border-radius-sm) !important;
        overflow: hidden;
        border: none !important;
        margin-bottom: 1rem;
    }

    .accordion-button {
        padding: 1.25rem;
        background-color: white;
        color: var(--text-dark);
        font-size: 1.1rem;
    }

    .accordion-button:not(.collapsed) {
        background: var(--bg-gradient);
        color: white;
    }

    .accordion-button:focus {
        box-shadow: none;
    }

    .accordion-body {
        padding: 1.5rem;
        background-color: white;
    }

    /* CTA Section Styles */
    .btn-primary {
        background: var(--bg-gradient);
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(224, 26, 26, 0.3);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(224, 26, 26, 0.4);
        background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-red-darker) 100%);
    }

    .btn-outline-primary {
        background: transparent;
        border: 2px solid var(--primary-red);
        color: var(--primary-red);
        padding: 0.75rem 1.5rem;
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
        background: var(--bg-gradient);
        border-color: transparent;
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(224, 26, 26, 0.3);
    }

    /* Animation Classes */
    .animated {
        animation-duration: 1s;
        animation-fill-mode: both;
    }

    .fadeInUp {
        animation-name: fadeInUp;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translate3d(0, 40px, 0);
        }
        to {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }
    }

    .animate-on-scroll {
        opacity: 0;
        transition: all 0.8s cubic-bezier(0.19, 1, 0.22, 1);
    }

    .animate-on-scroll.animated {
        opacity: 1;
    }
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="container hero-content">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <div class="animated fadeInUp">
                    <h1 class="hero-heading">TENTANG <span>SISTEM</span></h1>
                    <div class="section-divider"></div>
                    <h2 class="hero-subheading">Fiber Optic Monitoring System</h2>
                    <p class="hero-text mx-auto">Solusi teknologi terdepan untuk monitoring jaringan fiber optic yang terintegrasi dan handal.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="main-wrapper">
    <!-- Company Profile Section -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0 animate-on-scroll" data-animation="fadeInUp">
                    <div class="about-image-container">
                        <div class="about-image">
                            <img src="img/foto.jpeg" alt="Telkom Akses Team" class="img-fluid">
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate-on-scroll" data-animation="fadeInUp">
                    <div class="about-content">
                        <p class="section-subtitle">Profil Perusahaan</p>
                        <h2 class="about-title">PT <span class="text-gradient">Telkom Akses</span> & Mitratel</h2>
                        <div class="section-divider" style="margin: 1.5rem 0;"></div>
                        <p class="about-text">PT Telkom Akses merupakan anak perusahaan PT Telkom Indonesia, Tbk yang bergerak di bidang konstruksi dan pengelolaan infrastruktur jaringan. Bersama dengan PT Mitratel, kami berkomitmen untuk menyediakan layanan jaringan fiber optic terbaik di Indonesia.</p>
                        
                        <div class="feature-item">
                            <div class="feature-icon-container">
                                <i class="fas fa-history feature-icon-small"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Sejarah Kami</h5>
                                <p>Didirikan pada tahun 2012, PT Telkom Akses telah menjadi pionir dalam pengembangan jaringan fiber optic di Indonesia.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon-container">
                                <i class="fas fa-rocket feature-icon-small"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Visi</h5>
                                <p>Menjadi perusahaan penyedia jaringan broadband dan jasa konstruksi jaringan telekomunikasi terbaik di Asia Tenggara.</p>
                            </div>
                        </div>
                        
                        <div class="feature-item">
                            <div class="feature-icon-container">
                                <i class="fas fa-bullseye feature-icon-small"></i>
                            </div>
                            <div class="feature-content">
                                <h5>Misi</h5>
                                <p>Membangun dan menyediakan layanan infrastruktur jaringan broadband berkualitas tinggi untuk mendukung transformasi digital Indonesia.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Details Section -->
    <section class="services-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center animate-on-scroll" data-animation="fadeInUp">
                    <p class="section-subtitle">Sistem Monitoring FO</p>
                    <h2 class="display-4 fw-bold mb-3">Detail <span class="text-gradient">Sistem</span></h2>
                    <div class="section-divider"></div>
                    <p class="lead text-secondary col-lg-8 mx-auto">Teknologi terpadu untuk monitoring jaringan fiber optic secara komprehensif</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4 animate-on-scroll" data-animation="fadeInUp">
                    <div class="service-card">
                        <div class="service-card-header">
                            <i class="fas fa-network-wired service-icon"></i>
                            <h3 class="service-title">Arsitektur Sistem</h3>
                        </div>
                        <div class="service-body">
                            <p class="service-text">Sistem monitoring fiber optic kami dibangun dengan arsitektur multi-tier yang menjamin keandalan, skalabilitas, dan keamanan tingkat tinggi. Didukung oleh teknologi cloud computing untuk performa optimal.</p>
                            <ul class="text-start">
                                <li>Arsitektur terdistribusi untuk keandalan maksimal</li>
                                <li>Integrasi dengan sistem OTDR dan sensor real-time</li>
                                <li>Database time-series untuk analisis performa</li>
                                <li>API Gateway untuk integrasi dengan sistem eksternal</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="200">
                    <div class="service-card">
                        <div class="service-card-header">
                            <i class="fas fa-laptop-code service-icon"></i>
                            <h3 class="service-title">Teknologi</h3>
                        </div>
                        <div class="service-body">
                            <p class="service-text">Sistem kami menggunakan teknologi terdepan untuk memberikan performa dan keandalan terbaik. Dengan algoritma AI dan machine learning untuk prediksi gangguan secara proaktif.</p>
                            <ul class="text-start">
                                <li>Pemantauan OTDR real-time untuk deteksi cepat</li>
                                <li>Algoritma machine learning untuk analisis prediktif</li>
                                <li>Dashboard interaktif dengan visualisasi data tingkat tinggi</li>
                                <li>Sistem notifikasi multi-kanal (SMS, Email, Mobile App)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6 mb-4 animate-on-scroll" data-animation="fadeInUp">
                    <div class="service-card">
                        <div class="service-card-header">
                            <i class="fas fa-shield-alt service-icon"></i>
                            <h3 class="service-title">Keamanan</h3>
                        </div>
                        <div class="service-body">
                            <p class="service-text">Keamanan data dan jaringan adalah prioritas utama kami. Sistem monitoring fiber optic kami dilengkapi dengan lapisan keamanan berlapis untuk melindungi aset informasi kritikal.</p>
                            <ul class="text-start">
                                <li>Enkripsi end-to-end untuk transmisi data</li>
                                <li>Autentikasi multi-faktor untuk akses sistem</li>
                                <li>Audit logging komprehensif untuk semua aktivitas</li>
                                <li>Pemindaian kerentanan otomatis secara berkala</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4 animate-on-scroll" data-animation="fadeInUp" data-delay="200">
                    <div class="service-card">
                        <div class="service-card-header">
                            <i class="fas fa-chart-line service-icon"></i>
                            <h3 class="service-title">Analisis Performa</h3>
                        </div>
                        <div class="service-body">
                            <p class="service-text">Sistem analisis performa canggih untuk memantau kondisi jaringan fiber optic. Identifikasi tren dan pola untuk optimalisasi jaringan yang berkelanjutan.</p>
                            <ul class="text-start">
                                <li>Dashboard analitik real-time dengan metrik kunci</li>
                                <li>Pelaporan historis dengan visualisasi tren</li>
                                <li>Analisis prediktif untuk antisipasi masalah</li>
                                <li>Peringatan otomatis berdasarkan threshold dinamis</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="services-section">
        <div class="container">
            <div class="row mb-5">
                <div class="col-12 text-center animate-on-scroll" data-animation="fadeInUp">
                    <p class="section-subtitle">FAQ</p>
                    <h2 class="display-5 fw-bold mb-3">Pertanyaan <span class="text-gradient">Umum</span></h2>
                    <div class="section-divider"></div>
                    <p class="lead text-secondary col-lg-8 mx-auto">Jawaban atas pertanyaan yang sering ditanyakan tentang Sistem Monitoring FO</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-lg-8 mx-auto animate-on-scroll" data-animation="fadeInUp">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item shadow-sm">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Apa itu Sistem Monitoring Fiber Optic?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Sistem Monitoring Fiber Optic adalah platform terintegrasi yang memantau kondisi jaringan fiber optic secara real-time. Sistem ini menggunakan teknologi OTDR (Optical Time Domain Reflectometer) dan berbagai sensor untuk mendeteksi gangguan, anomali, dan potensi masalah pada jaringan fiber optic sebelum menyebabkan gangguan layanan.</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item shadow-sm">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Bagaimana sistem ini meningkatkan efisiensi operasional?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Sistem kami meningkatkan efisiensi operasional melalui:</p>
                                    <ul>
                                        <li>Deteksi dini masalah sebelum menyebabkan gangguan layanan</li>
                                        <li>Pengurangan waktu pemecahan masalah dengan lokalisasi gangguan yang akurat</li>
                                        <li>Optimalisasi jadwal pemeliharaan preventif berdasarkan data historis</li>
                                        <li>Penghematan biaya dengan mengurangi kunjungan lapangan yang tidak perlu</li>
                                        <li>Peningkatan kualitas layanan dengan meminimalkan downtime</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
    
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Bagaimana sistem notifikasi bekerja?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Sistem notifikasi kami bekerja melalui mekanisme multi-kanal dengan prioritas yang dapat dikonfigurasi:</p>
                                    <ul>
                                        <li>Peringatan real-time melalui dashboard web dan mobile</li>
                                        <li>Notifikasi SMS untuk peringatan mendesak</li><li>Notifikasi SMS untuk peringatan mendesak</li>
                                        <li>Notifikasi email untuk laporan harian dan mingguan</li>
                                        <li>Integrasi dengan sistem tiket untuk penanganan masalah otomatis</li>
                                        <li>Eskalasi otomatis ke tingkat manajemen yang lebih tinggi untuk masalah kritis</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                           <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Apa keunggulan sistem ini dibandingkan solusi lain?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Keunggulan utama sistem kami meliputi:</p>
                                    <ul>
                                        <li>Teknologi OTDR terkini dengan kemampuan deteksi gangguan yang lebih sensitif</li>
                                        <li>Algoritma AI yang terus belajar dan beradaptasi dengan pola gangguan jaringan</li>
                                        <li>Antarmuka pengguna yang intuitif dan mudah digunakan</li>
                                        <li>Skalabilitas untuk memantau jaringan fiber optic dari skala kecil hingga nasional</li>
                                        <li>Pelaporan kustomisasi yang komprehensif untuk berbagai tingkat manajemen</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item border-0 mb-3 shadow-sm">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Apakah sistem ini dapat diintegrasikan dengan sistem lain?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>Ya, sistem kami dirancang dengan pendekatan API-first yang memungkinkan integrasi mudah dengan sistem lain seperti:</p>
                                    <ul>
                                        <li>Sistem manajemen jaringan (NMS) yang sudah ada</li>
                                        <li>Sistem tiket dan helpdesk untuk penanganan masalah</li>
                                        <li>Sistem manajemen aset untuk pelacakan infrastruktur</li>
                                        <li>Platform visualisasi dan analitik data untuk analisis lanjutan</li>
                                        <li>Sistem notifikasi perusahaan untuk pemberitahuan</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="about-section text-center">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto animate-on-scroll" data-animation="fadeInUp">
                    <p class="section-subtitle">Hubungi Kami</p>
                    <h2 class="display-5 fw-bold mb-4">Siap untuk <span class="text-gradient">Meningkatkan</span> Performa Jaringan Fiber Optic Anda?</h2>
                    <p class="lead text-secondary mb-5">Hubungi tim kami untuk demo dan konsultasi tentang bagaimana sistem monitoring kami dapat membantu perusahaan Anda.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="contact.php" class="btn btn-primary px-4 py-2">Hubungi Kami</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Animation Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animate elements when they enter the viewport
        const animateElements = document.querySelectorAll('.animate-on-scroll');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        animateElements.forEach(element => {
            observer.observe(element);
        });
    });
</script>

<?php include 'includes/footer.php'; ?>