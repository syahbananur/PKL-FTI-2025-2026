<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses ditolak! Anda harus login sebagai admin.'];
    header('Location: ../index.php');
    exit();
}

// (B) Ambil alert jika ada
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']); 

// (C) AMBIL SEMUA DATA SUPPLIER
$query = "SELECT * FROM tabel_supplier ORDER BY nama_supplier ASC";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Supplier - Stok Kopi</title>
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

    <div class="dashboard-container" style="max-width: 1000px;">
        
        <header class="admin-header">
            <h1>Manajemen Data Supplier</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <a href="tambah_supplier.php" class="btn btn-success" style="margin-bottom: 20px;">Tambah Supplier Baru</a>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Supplier</th>
                        <th>Kontak</th>
                        <th class="actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $nomor = 1;
                    while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
                        <td><?php echo $nomor++; ?></td>
                        <td><?php echo htmlspecialchars($row['nama_supplier']); ?></td>
                        <td><?php echo htmlspecialchars($row['kontak']); ?></td>
                        <td class="actions">
                            <a href="edit_supplier.php?id=<?php echo $row['id_supplier']; ?>" class="btn btn-warning">Edit</a>
                            <a href="hapus_supplier.php?id=<?php echo $row['id_supplier']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus supplier ini?');">Hapus</a>
                        </td>
                    </tr>
                    <?php
                    } // Akhir while
                    ?>
                    <?php
                    if (mysqli_num_rows($result) === 0) {
                        echo '<tr><td colspan="4" style="text-align: center;">Belum ada data supplier.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
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