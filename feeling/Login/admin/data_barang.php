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

// Ambil alert
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);

// (B) AMBIL DATA BARANG (DENGAN KATEGORI)
$query = "SELECT 
            b.id_barang, b.nama_barang, b.kategori_barang, 
            s.nama_satuan, b.stok_awal, b.stok_minimum, b.status_barang 
          FROM tabel_barang AS b 
          JOIN tabel_satuan_unit AS s ON b.id_satuan = s.id_satuan 
          ORDER BY b.kategori_barang ASC, b.nama_barang ASC"; 

$result = mysqli_query($koneksi, $query);
if (!$result) { die("Query gagal: " . mysqli_error($koneksi)); }
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Barang</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.6"> 
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

    <div class="dashboard-container">
        
        <header class="admin-header">
            <h1>Manajemen Data Barang</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <a href="tambah_barang.php" class="btn btn-success" style="margin-bottom: 20px;">Tambah Barang Baru</a>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Stok Awal</th>
                        <th>Stok Minimum</th>
                        <th>Status</th>
                        <th class="actions">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($result && mysqli_num_rows($result) > 0):
                        $nomor = 1;
                        $kategori_sekarang = null; 

                        while ($row = mysqli_fetch_assoc($result)) { // Variabelnya $row
                            
                            // **(PERBAIKAN: Gunakan $row dan style inline)**
                            if ($row['kategori_barang'] !== $kategori_sekarang) {
                                $style_tr = "background-color: #6c757d; color: white; font-weight: 600; font-size: 1.1em;";
                                $style_td = "padding: 10px 15px; text-align: center;"; // Teks di-set center

                                echo '<tr style="' . $style_tr . '">';
                                echo '    <td colspan="7" style="' . $style_td . '">' . htmlspecialchars($row['kategori_barang']) . '</td>'; // Colspan 7
                                echo '</tr>';
                                $kategori_sekarang = $row['kategori_barang']; // Update pelacak
                            }
                            // **(AKHIR PERBAIKAN)**

                            // Logika class untuk status non-aktif
                            $class_css = ($row['status_barang'] == 'Non-Aktif') ? 'stok-non-aktif' : '';
                        ?>
                        <tr class="<?php echo $class_css; ?>">
                            <td><?php echo $nomor++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_satuan']); ?></td>
                            <td><?php echo number_format($row['stok_awal'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format($row['stok_minimum'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($row['status_barang']); ?></td>
                            <td class="actions">
                                <a href="edit_barang.php?id=<?php echo $row['id_barang']; ?>" class="btn btn-warning">Edit</a>
                                <a href="hapus_barang.php?id=<?php echo $row['id_barang']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin menghapus barang ini?');">Hapus</a>
                            </td>
                        </tr>
                        <?php 
                        } // Akhir while
                    else: 
                        echo '<tr><td colspan="7" style="text-align: center;">Belum ada data barang.</td></tr>';
                    endif; 
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
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>