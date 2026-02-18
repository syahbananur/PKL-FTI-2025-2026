<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; 

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses ditolak!'];
    header('Location: ../index.php');
    exit();
}

// (B) AMBIL ID & DATA PENGGUNA
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID Pengguna tidak ditemukan."; exit();
}
$id_pengguna_edit = (int)$_GET['id'];
$query = "SELECT nama_lengkap, email, role FROM tabel_pengguna WHERE id_pengguna = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_pengguna_edit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "Data pengguna tidak ditemukan."; exit();
}
$pengguna = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Ambil alert
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pengguna - Stok Kopi</title>
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
            <h1>Edit Pengguna: <?php echo htmlspecialchars($pengguna['nama_lengkap']); ?></h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_edit_pengguna.php" method="POST">
                
                <input type="hidden" name="id_pengguna" value="<?php echo $id_pengguna_edit; ?>">
                
                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap:</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($pengguna['nama_lengkap']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email (Login):</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($pengguna['email']); ?>" required>
                </div>
                
                 <div class="form-group">
                    <label for="role">Role:</label>
                    <select id="role" name="role" required>
                        <option value="user" <?php echo ($pengguna['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?php echo ($pengguna['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <hr style="border-top: 1px solid #eee; margin: 20px 0;">
                
                <div class="form-group">
                    <label for="password">Password Baru (Opsional):</label>
                    <input type="password" id="password" name="password">
                    <small>Kosongkan jika tidak ingin mengubah password.</small>
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_pengguna" class="btn btn-primary">Update Pengguna</button>
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
<?php mysqli_close($koneksi); ?>