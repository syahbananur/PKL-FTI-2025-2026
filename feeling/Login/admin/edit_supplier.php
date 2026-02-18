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

// (B) AMBIL ID & DATA SUPPLIER
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID Supplier tidak ditemukan."; exit();
}
$id_supplier_edit = (int)$_GET['id'];
$query = "SELECT * FROM tabel_supplier WHERE id_supplier = ?";
$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "i", $id_supplier_edit);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    echo "Data supplier tidak ditemukan."; exit();
}
$supplier = mysqli_fetch_assoc($result);
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
    <title>Edit Supplier - Stok Kopi</title>
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
            <h1>Edit Supplier: <?php echo htmlspecialchars($supplier['nama_supplier']); ?></h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_edit_supplier.php" method="POST">
                
                <input type="hidden" name="id_supplier" value="<?php echo $supplier['id_supplier']; ?>">

                <div class="form-group">
                    <label for="nama_supplier">Nama Supplier:</label>
                    <input type="text" id="nama_supplier" name="nama_supplier" value="<?php echo htmlspecialchars($supplier['nama_supplier']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="kontak">Kontak:</label>
                    <input type="text" id="kontak" name="kontak" value="<?php echo htmlspecialchars($supplier['kontak']); ?>">
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_supplier" class="btn btn-primary">Update Supplier</button>
                    <a href="data_supplier.php" class="btn btn-secondary">Batal</a>
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