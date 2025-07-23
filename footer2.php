<?php
/**
 * Footer template for Aplikasi Monitoring Insiden Gangguan Fiber Optic Mitratel
 */
?>

<footer class="simple-footer">
    <div class="footer-container">
        <p class="copyright">&copy; <?php echo date('Y'); ?> Aplikasi Monitoring Insiden Gangguan Fiber Optic Mitratel</p>
    </div>
</footer>

<style>
    .simple-footer {
        background-color: #f9f9f9;
        padding: 12px 0;
        text-align: center;
        font-family: inherit;
        border-top: 1px solid #e0e0e0;
        position: relative;
        bottom: 0;
        left: 0;
        right: 0;
        transition: all 0.3s ease;
        width: 100%;
        box-sizing: border-box;
        z-index: 100;
    }

    .footer-container {
        width: 100%;
        box-sizing: border-box;
        padding: 0 15px;
    }

    .copyright {
        color: #666666;
        font-size: 13px;
        margin: 0;
    }
    
    /* Sesuaikan dengan struktur sidebar pada dashboard */
    body:not(.sidebar-mini) .content-wrapper .simple-footer {
        padding-left: 80px; /* Lebar sidebar default */
    }
    
    /* Responsive adjustments sesuai dengan struktur aplikasi */
    @media (max-width: 768px) {
        .simple-footer {
            padding-left: 0 !important;
        }
    }
    
    /* Jika menggunakan main content wrapper */
    .content-wrapper {
        position: relative;
        min-height: 100vh;
    }
    
    .main-content {
        padding-bottom: 45px; /* Berikan ruang untuk footer */
    }
</style>
<script src="../../assets/js/notifications.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Deteksi kondisi sidebar
    function adjustFooter() {
        // Mendapatkan lebar sidebar saat ini
        const sidebar = document.querySelector('.sidebar') || 
                       document.querySelector('aside') || 
                       document.querySelector('.main-sidebar');
                       
        if (sidebar) {
            const sidebarWidth = sidebar.offsetWidth;
            const sidebarOffsetLeft = sidebar.getBoundingClientRect().left;
            
            // Jika sidebar hidden atau collapsed
            if (sidebarOffsetLeft < 0 || sidebarWidth < 70) {
                document.querySelector('.simple-footer').style.paddingLeft = '0px';
            } else {
                document.querySelector('.simple-footer').style.paddingLeft = sidebarWidth + 'px';
            }
        }
    }
    
    // Jalankan saat halaman dimuat
    adjustFooter();
    
    // Observer untuk memonitor perubahan pada body atau sidebar
    const observer = new MutationObserver(adjustFooter);
    const targetNode = document.body;
    observer.observe(targetNode, { attributes: true, subtree: true, attributeFilter: ['class', 'style'] });
    
    // Jalankan juga saat window di-resize
    window.addEventListener('resize', adjustFooter);
    // Mark notifications as read
    $('#notif-dropdown').click(function() {
        $.ajax({
            url: '../../includes/mark_notifications_read.php',
            type: 'POST',
            success: function() {
                $('.badge').fadeOut();
            }
        });
    });
});
</script>   