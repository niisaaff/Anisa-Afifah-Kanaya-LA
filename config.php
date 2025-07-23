<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mitratel_monitoring');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    // Create database connection using PDO
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
            PDO::ATTR_EMULATE_PREPARES => false // Use native prepared statements
        ]
    );
} catch (PDOException $e) {
    // Better error handling
    echo "Database connection failed: " . htmlspecialchars($e->getMessage());
    exit;
}
// Telegram configuration
define('TELEGRAM_BOT_TOKEN', 'TOKEN_BOT_ANDA');
define('TELEGRAM_API_URL', 'https://api.telegram.org/bot'.TELEGRAM_BOT_TOKEN.'/');
// Fungsi dasar
function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}
?>
