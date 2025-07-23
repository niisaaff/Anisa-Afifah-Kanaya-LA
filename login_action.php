<?php
require 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user = null;
    $role = null;

    // Cek di tabel admin
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        $user = $admin;
        $role = 'admin';
        $id_field = 'id_admin';
    }

    // Jika tidak ditemukan di admin, cek di tabel teknisi
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM teknisi WHERE username = ?");
        $stmt->execute([$username]);
        $teknisi = $stmt->fetch();
        
        if ($teknisi) {
            $user = $teknisi;
            $role = 'teknisi';
            $id_field = 'id_teknisi';
        }
    }

    // Jika tidak ditemukan di teknisi, cek di tabel supervisor
    if (!$user) {
        $stmt = $pdo->prepare("SELECT * FROM supervisor WHERE username = ?");
        $stmt->execute([$username]);
        $supervisor = $stmt->fetch();
        
        if ($supervisor) {
            $user = $supervisor;
            $role = 'supervisor';
            $id_field = 'id_supervisor';
        }
    }

    if ($user) {
        // Periksa password
        $storedPassword = $user['password'];

        // Verifikasi password hash
        if (password_verify($password, $storedPassword)) {
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role;

            if ($role === 'admin') {
                header('Location: dashboard/admin/index.php');
            } elseif ($role === 'teknisi') {
                header('Location: dashboard/teknisi/index.php');
            } elseif ($role === 'supervisor') {
                header('Location: dashboard/supervisor/index.php');
            }
            exit();
        }

        // Backward compatibility: cek password plaintext
        if ($password === $storedPassword) {
            // Upgrade password ke hash
            $newHashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password di tabel yang sesuai
            $updateStmt = $pdo->prepare("UPDATE $role SET password = ? WHERE $id_field = ?");
            $updateStmt->execute([$newHashed, $user[$id_field]]);

            // Set session
            $_SESSION['user_id'] = $user[$id_field];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $role;

            if ($role === 'admin') {
                header('Location: dashboard/admin/index.php');
            } elseif ($role === 'teknisi') {
                header('Location: dashboard/teknisi/index.php');
            } elseif ($role === 'supervisor') {
                header('Location: dashboard/supervisor/index.php');
            }
            exit();
        }
    }
}

// Jika gagal login
header('Location: login.php?error=1');
exit();
?>