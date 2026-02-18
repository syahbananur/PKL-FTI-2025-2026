<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login (Halaman ini bisa diakses user, jadi tidak perlu cek role 'admin')
if (!isset($_SESSION['id_pengguna'])) {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Anda harus login!'];
    header('Location: ../index.php');
    exit();
}

// (B) AMBIL DATA BARANG AKTIF (UNTUK DROPDOWN)
$query_barang = "SELECT id_barang, nama_barang FROM tabel_barang WHERE status_barang = 'Aktif' ORDER BY nama_barang ASC";
$result_barang = mysqli_query($koneksi, $query_barang);
if (!$result_barang) {
    die("Query barang gagal: " . mysqli_error($koneksi));
}

// (C) AMBIL DATA SUPPLIER (UNTUK DROPDOWN)
$query_supplier = "SELECT id_supplier, nama_supplier FROM tabel_supplier ORDER BY nama_supplier ASC";
$result_supplier = mysqli_query($koneksi, $query_supplier);
if (!$result_supplier) {
    die("Query supplier gagal: " . mysqli_error($koneksi));
}

// (D) Ambil data user dari session
$nama_user_login = $_SESSION['name'];
$id_user_login = $_SESSION['id_pengguna']; 

// (E) Ambil alert jika ada (untuk pesan error validasi)
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang Masuk - Stok Kopi</title>
    
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
            <h1>Input Barang Masuk</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_barang_masuk.php" method="POST">
                
                <div class="form-group">
                    <label for="tanggal_masuk">Tanggal Masuk:</label>
                    <input type="date" id="tanggal_masuk" name="tanggal_masuk" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="id_barang">Pilih Barang:</label>
                    <select id="id_barang" name="id_barang" required>
                        <option value="">-- Pilih Barang --</option>
                        <?php 
                        while ($barang = mysqli_fetch_assoc($result_barang)) {
                            echo '<option value="' . $barang['id_barang'] . '">' . htmlspecialchars($barang['nama_barang']) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="id_supplier">Pilih Supplier:</label>
                    <select id="id_supplier" name="id_supplier" required>
                        <option value="">-- Pilih Supplier --</option>
                         <?php 
                        while ($supplier = mysqli_fetch_assoc($result_supplier)) {
                            echo '<option value="' . $supplier['id_supplier'] . '">' . htmlspecialchars($supplier['nama_supplier']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_masuk">Jumlah Masuk:</label>
                    <input type="number" id="jumlah_masuk" name="jumlah_masuk" min="0.01" step="0.01" required> 
                </div>

                <input type="hidden" name="id_pengguna_pencatat" value="<?php echo $id_user_login; ?>">

                <div class="btn-container">
                    <button type="submit" name="simpan_masuk" class="btn btn-primary">Simpan Barang Masuk</button>
                    <a href="dashboard.php" class="btn btn-secondary">Kembali</a>
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

<?php
mysqli_close($koneksi); // Tutup koneksi
?>