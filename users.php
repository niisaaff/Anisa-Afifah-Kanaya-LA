<?php
require '../../config/config.php';
require '../../includes/auth_check.php';
check_role('admin');

// Inisialisasi koneksi PDO yang benar
try {
    $pdo = new PDO("mysql:host=localhost;dbname=mitratel_monitoring;charset=utf8mb4", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$username = $_SESSION['username'] ?? 'Admin';
$userRole = 'admin';

// ==== FUNGSI UNTUK MANAJEMEN USER (ADMIN, SUPERVISOR, TEKNISI) SESUAI DENGAN STRUKTUR TABEL DALAM mitratel_monitoring.sql ====
// Semua user dibagi ke tabel masing-masing: admin, supervisor, teknisi dan dengan id serta atribut masing-masing.
// Jadi, untuk keperluan tampilan dan operasi CRUD, gabungan data dari ketiga tabel.

function getAllUsers($pdo) {
    try {
        // Ambil data dari semua tabel user struktur baru
        $query = "
            SELECT id_admin AS id_user, username, nama_lengkap, 'admin' AS role, telegram_chat_id, created_at, foto 
            FROM admin
            UNION ALL
            SELECT id_supervisor AS id_user, username, nama_lengkap, 'supervisor' AS role, telegram_chat_id, created_at, foto
            FROM supervisor
            UNION ALL
            SELECT id_teknisi AS id_user, username, nama_lengkap, 'teknisi' AS role, telegram_chat_id, created_at, foto
            FROM teknisi
            ORDER BY role ASC, id_user ASC
        ";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error getting users: " . $e->getMessage());
        return [];
    }
}

function addUser($pdo, $username, $nama_lengkap, $password, $role, $telegram_chat_id) {
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        if ($role === 'admin') {
            $stmt = $pdo->prepare("INSERT INTO admin (username, nama_lengkap, password, telegram_chat_id, foto) VALUES (?, ?, ?, ?, 'default-avatar.jpg')");
            return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id]);
        } elseif ($role === 'supervisor') {
            $stmt = $pdo->prepare("INSERT INTO supervisor (username, nama_lengkap, password, telegram_chat_id, foto) VALUES (?, ?, ?, ?, 'default-avatar.jpg')");
            return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id]);
        } elseif ($role === 'teknisi') {
            $stmt = $pdo->prepare("INSERT INTO teknisi (username, nama_lengkap, password, telegram_chat_id, foto) VALUES (?, ?, ?, ?, 'default-avatar.jpg')");
            return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id]);
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error adding user: " . $e->getMessage());
        return false;
    }
}

function updateUser($pdo, $id, $username, $nama_lengkap, $password, $role, $telegram_chat_id) {
    try {
        if ($role === 'admin') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admin SET username = ?, nama_lengkap = ?, password = ?, telegram_chat_id = ? WHERE id_admin = ?");
                return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE admin SET username = ?, nama_lengkap = ?, telegram_chat_id = ? WHERE id_admin = ?");
                return $stmt->execute([$username, $nama_lengkap, $telegram_chat_id, $id]);
            }
        } elseif ($role === 'supervisor') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE supervisor SET username = ?, nama_lengkap = ?, password = ?, telegram_chat_id = ? WHERE id_supervisor = ?");
                return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE supervisor SET username = ?, nama_lengkap = ?, telegram_chat_id = ? WHERE id_supervisor = ?");
                return $stmt->execute([$username, $nama_lengkap, $telegram_chat_id, $id]);
            }
        } elseif ($role === 'teknisi') {
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE teknisi SET username = ?, nama_lengkap = ?, password = ?, telegram_chat_id = ? WHERE id_teknisi = ?");
                return $stmt->execute([$username, $nama_lengkap, $hashedPassword, $telegram_chat_id, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE teknisi SET username = ?, nama_lengkap = ?, telegram_chat_id = ? WHERE id_teknisi = ?");
                return $stmt->execute([$username, $nama_lengkap, $telegram_chat_id, $id]);
            }
        }
        return false;
    } catch (PDOException $e) {
        error_log("Error updating user: " . $e->getMessage());
        return false;
    }
}

function deleteUser($pdo, $id) {
    try {
        // Cek role dan id untuk menentukan dari tabel mana user akan dihapus
        // Cari user dari semua tabel
        $role = null;
        $roleRow = null;
        $stmt = $pdo->prepare("SELECT 'admin' as role, id_admin as iduser FROM admin WHERE id_admin = ?
            UNION ALL SELECT 'supervisor', id_supervisor FROM supervisor WHERE id_supervisor = ?
            UNION ALL SELECT 'teknisi', id_teknisi FROM teknisi WHERE id_teknisi = ?");
        $stmt->execute([$id, $id, $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['success' => false, 'message' => 'User tidak ditemukan!'];
        }
        $role = $row['role'];

        // Untuk teknisi: tidak bisa dihapus jika punya penugasan open/on progress
        if ($role === 'teknisi') {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM penugasan p JOIN tiket t ON p.id_tiket = t.id_tiket WHERE p.id_teknisi = ? AND t.status IN ('open', 'on progress')");
            $checkStmt->execute([$id]);
            if ($checkStmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'User tidak dapat dihapus karena masih memiliki penugasan aktif!'];
            }
        }

        // Admin: tidak bisa dihapus jika admin terakhir
        if ($role === 'admin') {
            $adminCheck = $pdo->query("SELECT COUNT(*) FROM admin");
            $adminCount = $adminCheck->fetchColumn();
            if ($adminCount <= 1) {
                return ['success' => false, 'message' => 'Tidak dapat menghapus admin terakhir!'];
            }
        }

        // Hapus sesuai tabel asalnya
        if ($role === 'admin') {
            $delStmt = $pdo->prepare("DELETE FROM admin WHERE id_admin = ?");
        } elseif ($role === 'supervisor') {
            $delStmt = $pdo->prepare("DELETE FROM supervisor WHERE id_supervisor = ?");
        } elseif ($role === 'teknisi') {
            $delStmt = $pdo->prepare("DELETE FROM teknisi WHERE id_teknisi = ?");
        } else {
            return ['success' => false, 'message' => 'Role user tidak valid!'];
        }
        $result = $delStmt->execute([$id]);
        return ['success' => $result && $delStmt->rowCount() > 0, 'message' => $result ? 'User berhasil dihapus!' : 'Gagal menghapus user!'];
    } catch (PDOException $e) {
        error_log("Error deleting user: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error database: ' . $e->getMessage()];
    }
}

// Penanganan form
$messages = ['add' => '', 'update' => '', 'delete' => ''];

// Handle ADD
if (isset($_POST['add_user'])) {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'nama_lengkap' => trim($_POST['nama_lengkap'] ?? ''),
        'password' => trim($_POST['password'] ?? ''),
        'role' => trim($_POST['role'] ?? ''),
        'telegram_chat_id' => trim($_POST['telegram_chat_id'] ?? '')
    ];
    if (empty($data['username']) || empty($data['nama_lengkap']) || empty($data['password']) || empty($data['role'])) {
        $messages['add'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Username, nama lengkap, password, dan role harus diisi!</div>';
    } elseif (strlen($data['password']) < 6) {
        $messages['add'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Password minimal 6 karakter!</div>';
    } elseif (!in_array($data['role'], ['admin', 'supervisor', 'teknisi'])) {
        $messages['add'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Role tidak valid!</div>';
    } else {
        // Cek username exist di semua tabel user dengan UNION
        $checkStmt = $pdo->prepare("
            SELECT username FROM admin WHERE username = ?
            UNION ALL SELECT username FROM supervisor WHERE username = ?
            UNION ALL SELECT username FROM teknisi WHERE username = ?");
        $checkStmt->execute([$data['username'], $data['username'], $data['username']]);
        if ($checkStmt->fetch()) {
            $messages['add'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Username sudah digunakan!</div>';
        } else {
            if (addUser($pdo, $data['username'], $data['nama_lengkap'], $data['password'], $data['role'], $data['telegram_chat_id'])) {
                $messages['add'] = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> User berhasil ditambahkan!</div>';
                $_POST = [];
            } else {
                $messages['add'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal menambah user!</div>';
            }
        }
    }
}

// Handle UPDATE
if (isset($_POST['update_user'])) {
    $data = [
        'id' => intval($_POST['edit_user_id'] ?? 0),
        'username' => trim($_POST['edit_username'] ?? ''),
        'nama_lengkap' => trim($_POST['edit_nama_lengkap'] ?? ''),
        'password' => trim($_POST['edit_password'] ?? ''),
        'role' => trim($_POST['edit_role'] ?? ''),
        'telegram_chat_id' => trim($_POST['edit_telegram_chat_id'] ?? '')
    ];
    if (empty($data['username']) || empty($data['nama_lengkap']) || empty($data['role'])) {
        $messages['update'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Username, nama lengkap, dan role harus diisi!</div>';
    } elseif (!empty($data['password']) && strlen($data['password']) < 6) {
        $messages['update'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Password minimal 6 karakter!</div>';
    } elseif (!in_array($data['role'], ['admin', 'supervisor', 'teknisi'])) {
        $messages['update'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Role tidak valid!</div>';
    } else {
        // Cek username exist di semua user lain (selain id sekarang)
        $checkStmt = $pdo->prepare("
            SELECT username FROM admin WHERE username = ? AND id_admin <> ?
            UNION ALL SELECT username FROM supervisor WHERE username = ? AND id_supervisor <> ?
            UNION ALL SELECT username FROM teknisi WHERE username = ? AND id_teknisi <> ?
        ");
        $checkStmt->execute([
            $data['username'], $data['id'],
            $data['username'], $data['id'],
            $data['username'], $data['id']
        ]);
        if ($checkStmt->fetch()) {
            $messages['update'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Username sudah digunakan!</div>';
        } else {
            if (updateUser($pdo, $data['id'], $data['username'], $data['nama_lengkap'], $data['password'], $data['role'], $data['telegram_chat_id'])) {
                $messages['update'] = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> User berhasil diperbarui!</div>';
            } else {
                $messages['update'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memperbarui user!</div>';
            }
        }
    }
}

// Handle DELETE
if (isset($_POST['delete_user'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    if ($userId > 0) {
        $result = deleteUser($pdo, $userId);
        $alertType = $result['success'] ? 'success' : 'danger';
        $icon = $result['success'] ? 'check-circle' : 'exclamation-triangle';
        $messages['delete'] = "<div class='alert alert-{$alertType}'><i class='fas fa-{$icon}'></i> {$result['message']}</div>";
    } else {
        $messages['delete'] = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ID user tidak valid!</div>';
    }
}

$users = getAllUsers($pdo);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Users - PT Telkom Akses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --telkom-red:#E31E24; --telkom-dark-red: #B71C1C; --telkom-light-red: #FFEBEE; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); color: #212529; }
        .main-content { padding: 110px 25px 25px; min-height: calc(100vh - 45px); }
        .header-section { background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-dark-red) 100%); color: white; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(227, 30, 36, 0.15);}
        .card { border: none; border-radius: 12px; box-shadow: 0 2px 10px rgba(227, 30, 36, 0.1); border-left: 4px solid var(--telkom-red);}
        .card-header { background: linear-gradient(135deg, var(--telkom-light-red) 0%, #fafafa 100%); border-bottom: 2px solid var(--telkom-red); border-radius: 12px 12px 0 0 !important;}
        .btn-primary { background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-dark-red) 100%); border: none;}
        .btn-primary:hover { background: linear-gradient(135deg, var(--telkom-dark-red) 0%, #8B0000 100%); transform: translateY(-1px);}
        .table thead th { background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-dark-red) 100%); color: white; border: none;}
        .table tbody tr:hover { background-color: var(--telkom-light-red);}
        .alert { border-radius: 8px; border: none; font-weight: 500;}
        .alert-success { background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); color: #155724; border-left: 4px solid #28a745;}
        .alert-danger { background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); color: #721c24; border-left: 4px solid #dc3545;}
        .form-control:focus { border-color: var(--telkom-red); box-shadow: 0 0 0 0.2rem rgba(227, 30, 36, 0.25);}
        .badge-admin { background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); color: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.8rem;}
        .badge-supervisor { background: linear-gradient(135deg, #fd7e14 0%, #e8590c 100%); color: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.8rem;}
        .badge-teknisi { background: linear-gradient(135deg, var(--telkom-red) 0%, var(--telkom-dark-red) 100%); color: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.8rem;}
        .role-admin { background-color: #dc354520; }
        .role-supervisor { background-color: #fd7e1420; }
        .role-teknisi { background-color: #E31E2420; }
        @media (max-width: 768px) {
            .main-content { padding: 110px 10px 25px;}
            .header-section { padding: 1rem;}
            .table-responsive { font-size: 0.85rem;}
        }
    </style>
</head>
<body>
    <?php 
    if (file_exists('../../includes/sidebar.php')) {
        include('../../includes/sidebar.php'); 
        if (function_exists('showSidebar')) showSidebar($userRole);
    }
    ?>
    <div class="content-wrapper">
        <?php 
        if (file_exists('../../includes/topbar.php')) {
            include('../../includes/topbar.php');
            if (function_exists('showTopbar')) showTopbar($userRole, $username);
        }
        ?>
        <div class="main-content">
            <!-- Header -->
            <div class="header-section">
                <h1><i class="fas fa-users-cog me-3"></i>Kelola Users</h1>
                <p>Manajemen Admin, Supervisor & Teknisi PT Telkom Akses - Sistem Monitoring Infrastruktur</p>
            </div>
            <?php echo implode('', $messages); ?>
            <div class="row g-4">
                <!-- Form Tambah -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="text-danger mb-0"><i class="fas fa-user-plus"></i> Tambah User</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="addForm">
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user me-2"></i>Username *</label>
                                    <input type="text" class="form-control" name="username" required 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-id-card me-2"></i>Nama Lengkap *</label>
                                    <input type="text" class="form-control" name="nama_lengkap" required
                                           value="<?php echo htmlspecialchars($_POST['nama_lengkap'] ?? ''); ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-user-tag me-2"></i>Role *</label>
                                    <select class="form-control" name="role" required>
                                        <option value="">Pilih Role</option>
                                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="supervisor" <?php echo (($_POST['role'] ?? '') === 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                        <option value="teknisi" <?php echo (($_POST['role'] ?? '') === 'teknisi') ? 'selected' : ''; ?>>Teknisi</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fas fa-lock me-2"></i>Password *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="password" required minlength="6" id="password">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><i class="fab fa-telegram me-2"></i>Telegram Chat ID</label>
                                    <input type="text" class="form-control" name="telegram_chat_id"
                                           value="<?php echo htmlspecialchars($_POST['telegram_chat_id'] ?? ''); ?>">
                                    <small class="text-muted">Kirim pesan ke @get_id_bot untuk mendapatkan Chat ID</small>
                                </div>
                                <button type="submit" name="add_user" class="btn btn-primary w-100">
                                    <i class="fas fa-plus-circle me-2"></i>Tambah User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- Daftar Users -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="text-danger mb-0"><i class="fas fa-list"></i> Daftar Users (<?php echo count($users); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <!-- Search -->
                            <div class="mb-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Cari user...">
                            </div>
                            <?php if (empty($users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                                    <h5>Belum Ada User</h5>
                                    <p class="text-muted">Tambahkan user baru menggunakan form di sebelah kiri.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover" id="userTable">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Username</th>
                                                <th>Nama Lengkap</th>
                                                <th>Role</th>
                                                <th>Telegram ID</th>
                                                <th>Dibuat</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr class="role-<?php echo $user['role']; ?>">
                                                <td><span class="badge-<?php echo $user['role']; ?>">#<?php echo $user['id_user']; ?></span></td>
                                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($user['nama_lengkap'] ?: '-'); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $user['telegram_chat_id'] ? '<code>' . htmlspecialchars($user['telegram_chat_id']) . '</code>' : '-'; ?></td>
                                                <td><small><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></small></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary me-1" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(<?php echo $user['id_user']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger"><i class="fas fa-user-edit me-2"></i>Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editForm">
                    <div class="modal-body">
                        <input type="hidden" name="edit_user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Username *</label>
                            <input type="text" class="form-control" name="edit_username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" class="form-control" name="edit_nama_lengkap" id="edit_nama_lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role *</label>
                            <select class="form-control" name="edit_role" id="edit_role" required>
                                <option value="">Pilih Role</option>
                                <option value="admin">Admin</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="teknisi">Teknisi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="edit_password" id="edit_password" minlength="6">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telegram Chat ID</label>
                            <input type="text" class="form-control" name="edit_telegram_chat_id" id="edit_telegram_chat_id">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_user" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Delete -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="deleteForm">
                    <div class="modal-body text-center">
                        <i class="fas fa-user-times text-danger fa-3x mb-3"></i>
                        <h5>Apakah Anda yakin?</h5>
                        <p>User <strong id="deleteUsername"></strong> akan dihapus permanen.</p>
                        <input type="hidden" name="user_id" id="delete_user_id">
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="delete_user" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#userTable tbody tr');
            rows.forEach(row => {
                const username = row.cells[1].textContent.toLowerCase();
                const fullName = row.cells[2].textContent.toLowerCase();
                const role = row.cells[3].textContent.toLowerCase();
                row.style.display = (username.includes(searchTerm) || fullName.includes(searchTerm) || role.includes(searchTerm)) ? '' : 'none';
            });
        });
        // Edit user function
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id_user;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_nama_lengkap').value = user.nama_lengkap || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = '';
            document.getElementById('edit_telegram_chat_id').value = user.telegram_chat_id || '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
        // Delete user function
        function deleteUser(userId, username) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('deleteUsername').textContent = username;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
        // Form validation
        document.getElementById('addForm').addEventListener('submit', function(e) {
            const username = this.username.value.trim();
            const nama_lengkap = this.nama_lengkap.value.trim();
            const password = this.password.value.trim();
            const role = this.role.value.trim();
            if (!username || !nama_lengkap || !password || !role) {
                e.preventDefault();
                alert('Username, nama lengkap, password, dan role harus diisi!');
                return false;
            }
            if (password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const username = this.edit_username.value.trim();
            const nama_lengkap = this.edit_nama_lengkap.value.trim();
            const password = this.edit_password.value.trim();
            const role = this.edit_role.value.trim();
            if (!username || !nama_lengkap || !role) {
                e.preventDefault();
                alert('Username, nama lengkap, dan role harus diisi!');
                return false;
            }
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password minimal 6 karakter!');
                return false;
            }
        });
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
        // Prevent double submission
        let isSubmitting = false;
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    return false;
                }
                isSubmitting = true;
                setTimeout(() => isSubmitting = false, 3000);
            });
        });
    </script>
</body>
</html>
