// Cek apakah browser mendukung notifikasi
function checkNotificationSupport() {
    if (!("Notification" in window)) {
        console.log("Browser ini tidak mendukung notifikasi desktop");
        return false;
    }
    return true;
}

// Minta izin notifikasi
function requestNotificationPermission() {
    if (!checkNotificationSupport()) return;
    
    Notification.requestPermission().then(function(permission) {
        if (permission === "granted") {
            console.log("Izin notifikasi diberikan");
        }
    });
}

// Tampilkan notifikasi
function showNotification(title, options = {}) {
    if (!checkNotificationSupport()) return;
    
    if (Notification.permission === "granted") {
        const notification = new Notification(title, options);
        
        // Tambahkan event listener untuk klik
        if (options.url) {
            notification.onclick = function() {
                window.open(options.url);
            };
        }
        
        // Tutup notifikasi setelah beberapa detik
        setTimeout(() => {
            notification.close();
        }, 5000);
    } else if (Notification.permission !== "denied") {
        Notification.requestPermission().then(function(permission) {
            if (permission === "granted") {
                showNotification(title, options);
            }
        });
    }
}

// Periksa notifikasi baru dari server
function checkNewNotifications() {
    fetch('../../includes/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.hasNew) {
                data.notifications.forEach(notif => {
                    showNotification(notif.title, {
                        body: notif.message,
                        icon: '../../assets/img/notification-icon.png',
                        url: notif.url
                    });
                });
            }
        })
        .catch(error => console.error('Error checking notifications:', error));
}

// Minta izin saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    requestNotificationPermission();
    
    // Periksa notifikasi setiap 30 detik
    setInterval(checkNewNotifications, 30000);
});
