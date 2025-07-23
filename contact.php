<?php 
require_once 'config/config.php';
include 'includes/header.php';
?>

<style>
/* Corporate Contact Page - Simplified & Clean */
:root {
    --primary-red: #e60000;
    --primary-dark: #cc0000;
    --secondary-gray: #696969;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --shadow: 0 8px 25px rgba(230, 0, 0, 0.1);
    --radius: 12px;
    --transition: all 0.3s ease;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    color: #333;
}

/* Contact Section */
.contact-section {
    padding: 4rem 0;
    background: linear-gradient(135deg, var(--light-gray) 0%, #e9ecef 100%);
    position: relative;
}

.contact-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 50"><defs><pattern id="grid" width="5" height="5" patternUnits="userSpaceOnUse"><path d="M 5 0 L 0 0 0 5" fill="none" stroke="rgba(230,0,0,0.02)" stroke-width="0.5"/></pattern></defs><rect width="50" height="50" fill="url(%23grid)"/></svg>');
    opacity: 0.6;
}

.container {
    position: relative;
    z-index: 1;
}

/* Header */
.contact-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-badge {
    display: inline-block;
    background: var(--primary-red);
    color: white;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 1rem;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.contact-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: #333;
}

.text-red {
    color: var(--primary-red);
}

.contact-subtitle {
    font-size: 1.1rem;
    color: var(--secondary-gray);
    max-width: 600px;
    margin: 0 auto;
}

/* Form Card */
.form-card {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
}

.form-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(230, 0, 0, 0.15);
}

.form-header {
    background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1.5rem;
    text-align: center;
}

.form-header h3 {
    margin: 0;
    font-size: 1.3rem;
    font-weight: 600;
}

.form-body {
    padding: 2rem;
}

/* Form Elements */
.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #333;
    font-size: 0.9rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    background: var(--white);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 3px rgba(230, 0, 0, 0.1);
}

.form-control:hover {
    border-color: var(--primary-red);
}

/* Custom Select Dropdown with Icon */
.select-wrapper {
    position: relative;
    display: block;
}

.select-wrapper select {
    width: 100%;
    padding: 0.75rem 2.5rem 0.75rem 0.75rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: var(--transition);
    background: var(--white);
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

.select-wrapper select:focus {
    outline: none;
    border-color: var(--primary-red);
    box-shadow: 0 0 0 3px rgba(230, 0, 0, 0.1);
}

.select-wrapper select:hover {
    border-color: var(--primary-red);
}

.select-wrapper::after {
    content: '\f107';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    right: 0.75rem;
    transform: translateY(-50%);
    color: var(--primary-red);
    font-size: 1rem;
    pointer-events: none;
    transition: var(--transition);
}

.select-wrapper:hover::after {
    color: var(--primary-dark);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.btn-submit {
    width: 100%;
    background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-dark) 100%);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: var(--transition);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(230, 0, 0, 0.3);
}

/* Info Cards */
.info-cards {
    display: grid;
    gap: 1.5rem;
}

.info-card {
    background: var(--white);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 1.5rem;
    transition: var(--transition);
    border-left: 4px solid var(--primary-red);
}

.info-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(230, 0, 0, 0.12);
}

.info-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-dark) 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    margin-bottom: 1rem;
}

.info-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #333;
}

.info-text {
    color: var(--secondary-gray);
    font-size: 0.95rem;
    line-height: 1.5;
    margin: 0;
}

.info-text strong {
    color: var(--primary-red);
    font-weight: 600;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, var(--primary-red) 0%, var(--primary-dark) 100%);
    padding: 3rem 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.cta-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><defs><pattern id="dots" width="15" height="15" patternUnits="userSpaceOnUse"><circle cx="7.5" cy="7.5" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="60" height="60" fill="url(%23dots)"/></svg>');
}

.cta-content {
    position: relative;
    z-index: 1;
}

.cta-title {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.cta-text {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.btn-cta {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-light {
    background: white;
    color: var(--primary-red);
    border: 2px solid white;
}

.btn-light:hover {
    background: transparent;
    color: white;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.8);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
    color: white;
    transform: translateY(-2px);
}

/* Responsive */
@media (max-width: 768px) {
    .contact-section {
        padding: 3rem 0;
    }
    
    .contact-title {
        font-size: 2rem;
    }
    
    .form-body {
        padding: 1.5rem;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-cta {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
}

@media (max-width: 576px) {
    .contact-title {
        font-size: 1.8rem;
    }
    
    .cta-title {
        font-size: 1.6rem;
    }
    
    .form-header {
        padding: 1.25rem;
    }
}
</style>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <!-- Header -->
        <div class="contact-header">
            <div class="section-badge">Hubungi Kami</div>
            <h1 class="contact-title">Kontak <span class="text-red">Telkom Akses</span></h1>
            <p class="contact-subtitle">Solusi infrastruktur jaringan broadband terpercaya untuk kebutuhan bisnis Anda</p>
        </div>

        <div class="row g-4">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="form-card">
                    <div class="form-header">
                        <h3><i class="fas fa-paper-plane me-2"></i>Kirim Pesan</h3>
                    </div>
                    <div class="form-body">
                        <form id="contactForm" method="POST" action="process_contact.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Nama Lengkap *</label>
                                        <input type="text" class="form-control" name="name" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" name="email" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Telepon *</label>
                                        <input type="tel" class="form-control" name="phone" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Perusahaan</label>
                                        <input type="text" class="form-control" name="company">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Layanan *</label>
                                <div class="select-wrapper">
                                    <select class="form-control" name="service" required>
                                        <option value="">Pilih Layanan</option>
                                        <option value="survey">Survey & Drawing</option>
                                        <option value="pembangunan">Pembangunan Jaringan</option>
                                        <option value="pasang_baru">Layanan Pasang Baru</option>
                                        <option value="operasi">Operasi & Pemeliharaan</option>
                                        <option value="konsultasi">Konsultasi Teknis</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Detail Kebutuhan *</label>
                                <textarea class="form-control" name="message" required></textarea>
                            </div>
                            
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Kirim Pesan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-5">
                <div class="info-cards">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4 class="info-title">Kantor Pusat</h4>
                        <p class="info-text">
                            Gedung Telkom Landmark Tower<br>
                            Jl. Gatot Subroto Kav. 52<br>
                            Jakarta Selatan 12710
                        </p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h4 class="info-title">Telepon</h4>
                        <p class="info-text">
                            Customer Service: <strong>(021) 521-3711</strong><br>
                            Technical Support: <strong>147</strong><br>
                            Fax: (021) 521-3700
                        </p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4 class="info-title">Email</h4>
                        <p class="info-text">
                            info@telkomakses.co.id<br>
                            support@telkomakses.co.id<br>
                            sales@telkomakses.co.id
                        </p>
                    </div>
                    
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4 class="info-title">Jam Operasional</h4>
                        <p class="info-text">
                            Senin - Jumat: <strong>08.00 - 17.00 WIB</strong><br>
                            Sabtu: 08.00 - 12.00 WIB<br>
                            <strong>Support 24/7</strong> untuk gangguan
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2 class="cta-title">Siap Meningkatkan Infrastruktur Jaringan?</h2>
            <p class="cta-text">Bergabunglah dengan ribuan pelanggan yang mempercayakan kebutuhan jaringan kepada Telkom Akses</p>
            <div class="cta-buttons">
                <a href="tel:+62215213711" class="btn-cta btn-light">
                    <i class="fas fa-phone"></i>Hubungi Sekarang
                </a>
                <a href="tel:147" class="btn-cta btn-outline">
                    <i class="fas fa-headset"></i>Support 147
                </a>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Simple validation
    const requiredFields = this.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#dc3545';
            isValid = false;
        } else {
            field.style.borderColor = '#28a745';
        }
    });
    
    if (isValid) {
        const btn = this.querySelector('.btn-submit');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengirim...';
        btn.disabled = true;
        
        setTimeout(() => {
            alert('Pesan berhasil dikirim! Tim kami akan segera menghubungi Anda.');
            this.reset();
            btn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Kirim Pesan';
            btn.disabled = false;
            
            requiredFields.forEach(field => {
                field.style.borderColor = '#e9ecef';
            });
        }, 2000);
    }
});

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>
