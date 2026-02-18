<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login
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

// (C) AMBIL DATA TUJUAN (UNTUK DROPDOWN)
$query_tujuan = "SELECT id_tujuan, nama_tujuan FROM tabel_tujuan ORDER BY nama_tujuan ASC";
$result_tujuan = mysqli_query($koneksi, $query_tujuan);
if (!$result_tujuan) {
    die("Query tujuan gagal: " . mysqli_error($koneksi));
}

// (D) Ambil data user dari session
$nama_user_login = $_SESSION['name'];
$id_user_login = $_SESSION['id_pengguna'];

// (E) Ambil alert jika ada (untuk pesan error validasi stok)
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Barang Keluar - Stok Kopi</title>
    
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.0"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    </head>
<body>

    <?php if (!empty($alerts)): ?>
    <div class="alert-box">
        <?php foreach ($alerts as $alert): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?>" role="alert">
                <i class='bx bx-<?php echo ($alert['type'] == 'success') ? 'check-circle' : 'x-circle'; ?>'></i>
                <span><?php echo htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-container" style="max-width: 600px;"> 
        
        <header class="admin-header">
            <h1>Input Barang Keluar</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <form action="proses_barang_keluar.php" method="POST">

                <div class="form-group">
                    <label for="tanggal_keluar">Tanggal Keluar:</label>
                    <input type="date" id="tanggal_keluar" name="tanggal_keluar" value="<?php echo date('Y-m-d'); ?>" required>
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
                    <label for="id_tujuan">Tujuan Pengambilan:</label>
                    <select id="id_tujuan" name="id_tujuan" required>
                        <option value="">-- Pilih Tujuan --</option>
                         <?php
                        while ($tujuan = mysqli_fetch_assoc($result_tujuan)) {
                            echo '<option value="' . $tujuan['id_tujuan'] . '">' . htmlspecialchars($tujuan['nama_tujuan']) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="jumlah_keluar">Jumlah Keluar:</label>
                    <input type="number" id="jumlah_keluar" name="jumlah_keluar" min="0.01" step="0.01" required>
                </div>

                <div class="form-group">
                    <label for="keterangan_darurat">Keterangan Tambahan (Opsional):</label>
                    <textarea id="keterangan_darurat" name="keterangan_darurat" rows="3"></textarea>
                </div>

                <input type="hidden" name="id_pengguna_pencatat" value="<?php echo $id_user_login; ?>">

                <div class="btn-container">
                    <button type="submit" name="simpan_keluar" class="btn btn-primary">Simpan Barang Keluar</button>
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