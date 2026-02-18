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

// (B) AMBIL DATA SATUAN (UNTUK DROPDOWN)
$query_satuan = "SELECT id_satuan, nama_satuan FROM tabel_satuan_unit ORDER BY nama_satuan ASC";
$result_satuan = mysqli_query($koneksi, $query_satuan);
if (!$result_satuan) { die("Query satuan gagal: " . mysqli_error($koneksi)); }

// Ambil alert
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang Baru - Stok Kopi</title>
    <link rel="stylesheet" href="../assets/admin_style.css"> 
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
            <h1>Tambah Barang Baru</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_tambah_barang.php" method="POST">
                
                <div class="form-group">
                    <label for="nama_barang">Nama Barang:</label>
                    <input type="text" id="nama_barang" name="nama_barang" required>
                </div>

                <div class="form-group">
                    <label for="kategori_barang">Kategori Barang:</label>
                    <select id="kategori_barang" name="kategori_barang" required>
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Produksi">Produksi (Bahan Baku Olahan)</option>
                        <option value="Non Produksi">Non Produksi (Langsung Pakai)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="id_satuan">Satuan:</label>
                    <select id="id_satuan" name="id_satuan" required>
                        <option value="">-- Pilih Satuan --</option>
                        <?php 
                        while ($satuan = mysqli_fetch_assoc($result_satuan)) {
                            echo '<option value="' . $satuan['id_satuan'] . '">' . htmlspecialchars($satuan['nama_satuan']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stok_awal">Stok Awal:</label>
                    <input type="number" id="stok_awal" name="stok_awal" value="0" min="0" step="0.01" required> 
                </div>

                <div class="form-group">
                    <label for="stok_minimum">Stok Minimum:</label>
                    <input type="number" id="stok_minimum" name="stok_minimum" value="0" min="0" step="0.01" required>
                </div>

                <div class="btn-container">
                    <button type="submit" name="simpan_barang" class="btn btn-primary">Simpan Barang</button>
                    <a href="data_barang.php" class="btn btn-secondary">Batal</a>
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