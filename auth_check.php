<?php
// Mulai session (WAJIB sebelum akses $_SESSION)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit();
}

// Role checking function
function check_role($required_role) {
    if ($_SESSION['role'] !== $required_role) {
        header('Location: ../../login.php');
        exit();
    }
}
?>
