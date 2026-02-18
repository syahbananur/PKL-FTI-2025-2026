<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; 

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses ditolak! Anda harus login sebagai admin.'];
    header('Location: ../index.php');
    exit();
}

// (B) Ambil alert jika ada
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']); 

// (C) AMBIL SEMUA DATA PENGGUNA
$query = "SELECT id_pengguna, nama_lengkap, email, role FROM tabel_pengguna ORDER BY nama_lengkap ASC";
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
    <title>Manajemen Pengguna - Stok Kopi</title>
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
            <h1>Manajemen Data Pengguna</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <a href="tambah_pengguna.php" class="btn btn-success" style="margin-bottom: 20px;">Tambah Pengguna Baru</a>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Email (Login)</th>
                        <th>Role</th>
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
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                        <td class="actions">
                            <a href="edit_pengguna.php?id=<?php echo $row['id_pengguna']; ?>" class="btn btn-warning">Edit</a>
                            <?php if ($row['id_pengguna'] != $_SESSION['id_pengguna']): ?>
                                <a href="hapus_pengguna.php?id=<?php echo $row['id_pengguna']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">Hapus</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                    } // Akhir while
                    ?>
                    <?php
                    if (mysqli_num_rows($result) === 0) {
                        echo '<tr><td colspan="5" style="text-align: center;">Belum ada data pengguna.</td></tr>';
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