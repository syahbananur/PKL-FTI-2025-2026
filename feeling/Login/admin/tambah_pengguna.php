<?php
session_start();
require_once '../config/config.php';
// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses ditolak!'];
    header('Location: ../index.php');
    exit();
}

// Ambil alert
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna Baru</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.0"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>

    <?php if (!empty($alerts)) :?>
    <div class="alert-box"> 
        <?php foreach ($alerts as $alert) : ?>
        <div class="alert <?= htmlspecialchars($alert['type']); ?>"> 
            <i class='bx <?= $alert['type'] === 'success' ? 'bxs-check-circle' : 'bxs-x-circle'; ?>'></i> 
            <span><?= htmlspecialchars($alert['message']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-container" style="max-width: 600px;">
        
        <header class="admin-header">
            <h1>Tambah Pengguna Baru</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_tambah_pengguna.php" method="POST">
                
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap:</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email (Untuk Login):</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="password">Password Baru:</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="btn-container">
                    <button type="submit" name="simpan_pengguna" class="btn btn-primary">Simpan Pengguna</button>
                    <a href="data_pengguna.php" class="btn btn-secondary">Batal</a>
                </div>

            </form>
        </main>
    </div>

    <script>
        const alertBox = document.querySelector('.alert-box');
        if (alertBox) {
            setTimeout(() => { alertBox.classList.add('show'); }, 50); 
            setTimeout(() => {
                alertBox.classList.remove('show'); 
                setTimeout(() => { if(alertBox.parentNode) { alertBox.parentNode.removeChild(alertBox); } }, 500); 
            }, 5500); 
        }
    </script>
</body>
</html>