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

// (B) AMBIL ID & DATA BARANG
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "ID Barang tidak ditemukan."; exit();
}
$id_barang_edit = (int)$_GET['id'];
$query_barang = "SELECT * FROM tabel_barang WHERE id_barang = ?";
$stmt_barang = mysqli_prepare($koneksi, $query_barang);
mysqli_stmt_bind_param($stmt_barang, "i", $id_barang_edit);
mysqli_stmt_execute($stmt_barang);
$result_barang = mysqli_stmt_get_result($stmt_barang);
if (mysqli_num_rows($result_barang) === 0) {
    echo "Data barang tidak ditemukan."; exit();
}
$barang = mysqli_fetch_assoc($result_barang);
mysqli_stmt_close($stmt_barang);

// (C) AMBIL DATA SATUAN
$query_satuan = "SELECT id_satuan, nama_satuan FROM tabel_satuan_unit ORDER BY nama_satuan ASC";
$result_satuan = mysqli_query($koneksi, $query_satuan);
if (!$result_satuan) { die("Query satuan gagal: " . mysqli_error($koneksi)); }

// (D) AMBIL ALERT (jika ada error validasi dari proses_edit)
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang - Stok Kopi</title>
    
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

    <div class="dashboard-container" style="max-width: 600px;"> <header class="admin-header">
            <h1>Edit Barang: <?php echo htmlspecialchars($barang['nama_barang']); ?></h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_edit_barang.php" method="POST">
                
                <input type="hidden" name="id_barang" value="<?php echo $barang['id_barang']; ?>">

                <div class="form-group">
                    <label for="nama_barang">Nama Barang:</label>
                    <input type="text" id="nama_barang" name="nama_barang" value="<?php echo htmlspecialchars($barang['nama_barang']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="kategori_barang">Kategori Barang:</label>
                    <select id="kategori_barang" name="kategori_barang" required>
                        <option value="Non Produksi" <?php echo ($barang['kategori_barang'] == 'Non Produksi') ? 'selected' : ''; ?>>
                            Non Produksi (Langsung Pakai)
                        </option>
                        <option value="Produksi" <?php echo ($barang['kategori_barang'] == 'Produksi') ? 'selected' : ''; ?>>
                            Produksi (Bahan Baku Olahan)
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_satuan">Satuan:</label>
                    <select id="id_satuan" name="id_satuan" required>
                        <option value="">-- Pilih Satuan --</option>
                        <?php 
                        while ($satuan = mysqli_fetch_assoc($result_satuan)) {
                            $selected = ($satuan['id_satuan'] == $barang['id_satuan']) ? 'selected' : '';
                            echo '<option value="' . $satuan['id_satuan'] . '" ' . $selected . '>' . htmlspecialchars($satuan['nama_satuan']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="stok_awal">Stok Awal:</label>
                    <input type="number" id="stok_awal" name="stok_awal" value="<?php echo $barang['stok_awal']; ?>" min="0" step="0.01" required> 
                </div>

                <div class="form-group">
                    <label for="stok_minimum">Stok Minimum:</label>
                    <input type="number" id="stok_minimum" name="stok_minimum" value="<?php echo $barang['stok_minimum']; ?>" min="0" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="status_barang">Status Barang:</label>
                    <select id="status_barang" name="status_barang" required>
                        <option value="Aktif" <?php echo ($barang['status_barang'] == 'Aktif') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="Non-Aktif" <?php echo ($barang['status_barang'] == 'Non-Aktif') ? 'selected' : ''; ?>>Non-Aktif</option>
                    </select>
                </div>

                <div class="btn-container">
                    <button type="submit" name="update_barang" class="btn btn-primary">Update Barang</button>
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