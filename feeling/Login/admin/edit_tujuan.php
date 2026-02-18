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

// (B) AMBIL ID & DATA TUJUAN
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID Tujuan tidak ditemukan."; exit();
}
$id_tujuan_edit = (int)$_GET['id'];
$query = "SELECT * FROM tabel_tujuan WHERE id_tujuan = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_tujuan_edit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "Data tujuan tidak ditemukan."; exit();
}
$tujuan = mysqli_fetch_assoc($result);
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
    <title>Edit Tujuan - Stok Kopi</title>
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
            <h1>Edit Tujuan: <?php echo htmlspecialchars($tujuan['nama_tujuan']); ?></h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_edit_tujuan.php" method="POST">
                
                <input type="hidden" name="id_tujuan" value="<?php echo $tujuan['id_tujuan']; ?>">

                <div class="form-group">
                    <label for="nama_tujuan">Nama Tujuan:</label>
                    <input type="text" id="nama_tujuan" name="nama_tujuan" value="<?php echo htmlspecialchars($tujuan['nama_tujuan']); ?>" required>
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_tujuan" class="btn btn-primary">Update Tujuan</button>
                    <a href="data_tujuan.php" class="btn btn-secondary">Batal</a>
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