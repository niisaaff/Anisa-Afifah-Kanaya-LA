<?php
require '../config/config.php';
session_start();

if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'teknisi') {
    try {
        $stmt = $pdo->prepare("UPDATE notifikasi SET status_baca = 'read' WHERE id_teknisi = ? AND status_baca = 'unread'");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
}
?>
