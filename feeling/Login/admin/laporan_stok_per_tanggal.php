<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) TENTUKAN TANGGAL PILIHAN
// Default ke hari ini jika belum dipilih
$tanggal_pilih = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// (C) HITUNG STOK PADA TANGGAL TERSEBUT
$laporan_stok = [];

// 1. Ambil semua barang
$query_barang = "SELECT id_barang, nama_barang, stok_awal, s.nama_satuan 
                 FROM tabel_barang b 
                 JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan 
                 ORDER BY b.nama_barang ASC";
$result_barang = mysqli_query($koneksi, $query_barang);

if ($result_barang) {
    while ($barang = mysqli_fetch_assoc($result_barang)) {
        $id_barang = $barang['id_barang'];
        $stok_awal_master = $barang['stok_awal'];

        // 2. Hitung Total Masuk SAMPAI DENGAN Tanggal Pilih (<=)
        $q_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total FROM tabel_barang_masuk 
                    WHERE id_barang = $id_barang AND tanggal_masuk <= '$tanggal_pilih'";
        $res_masuk = mysqli_query($koneksi, $q_masuk);
        $total_masuk = mysqli_fetch_assoc($res_masuk)['total'];

        // 3. Hitung Total Keluar SAMPAI DENGAN Tanggal Pilih (<=)
        $q_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total FROM tabel_barang_keluar 
                     WHERE id_barang = $id_barang AND tanggal_keluar <= '$tanggal_pilih'";
        $res_keluar = mysqli_query($koneksi, $q_keluar);
        $total_keluar = mysqli_fetch_assoc($res_keluar)['total'];

        // 4. Hitung Sisa Stok Historis
        $sisa_stok_historis = $stok_awal_master + $total_masuk - $total_keluar;

        $laporan_stok[] = [
            'nama_barang' => $barang['nama_barang'],
            'satuan' => $barang['nama_satuan'],
            'stok_awal' => $stok_awal_master,
            'total_masuk' => $total_masuk,
            'total_keluar' => $total_keluar,
            'sisa_stok' => $sisa_stok_historis
        ];
    }
}

$nama_user_login = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cek Stok per Tanggal</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .filter-form { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filter-form input { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        @media print {
            .admin-header, .filter-form, .btn-secondary, .print-btn, .admin-nav { display: none !important; }
            .dashboard-container { box-shadow: none; border: none; margin: 0; padding: 0; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="dashboard-container" style="max-width: 1200px;">
        <header class="admin-header">
            <h1>Cek Stok per Tanggal</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>
            
            <form action="laporan_stok_per_tanggal.php" method="GET" class="filter-form">
                <label>Lihat Stok Pada Tanggal:</label>
                <input type="date" name="tanggal" value="<?php echo $tanggal_pilih; ?>" required>
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>

            <div class="section-header">
                <h2>Posisi Stok per Tanggal: <?php echo date('d-m-Y', strtotime($tanggal_pilih)); ?></h2>
                <button onclick="window.print()" class="print-btn">🖨️ Cetak</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th style="text-align: right;">Total Masuk (s/d Tgl Ini)</th>
                        <th style="text-align: right;">Total Keluar (s/d Tgl Ini)</th>
                        <th style="text-align: right;">Sisa Stok (Pada Tgl Ini)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($laporan_stok)): ?>
                        <?php $no = 1; foreach ($laporan_stok as $item): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                            <td style="text-align: right; color: green;">+<?php echo number_format($item['total_masuk'], 2, ',', '.'); ?></td>
                            <td style="text-align: right; color: red;">-<?php echo number_format($item['total_keluar'], 2, ',', '.'); ?></td>
                            <td style="text-align: right; font-weight: bold; background-color: #f8f9fa;">
                                <?php echo number_format($item['sisa_stok'], 2, ',', '.'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center;">Data tidak tersedia.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </main>
    </div>

</body>
</html>
<?php mysqli_close($koneksi); ?>