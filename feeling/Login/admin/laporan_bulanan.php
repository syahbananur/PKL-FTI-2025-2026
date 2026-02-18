<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) Ambil alert jika ada
$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);

// (C) TENTUKAN BULAN & TAHUN
$bulan_pilih = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// (D) SIAPKAN DATA UNTUK LAPORAN
$laporan_data = [];
$nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

if ($bulan_pilih >= 1 && $bulan_pilih <= 12 && $tahun_pilih > 2000) {

    $tanggal_awal_bulan = "$tahun_pilih-$bulan_pilih-01";
    $tanggal_akhir_bulan = date("Y-m-t", strtotime($tanggal_awal_bulan)); 

    $query_barang = "SELECT id_barang, nama_barang, stok_awal, s.nama_satuan
                     FROM tabel_barang b JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
                     WHERE b.status_barang = 'Aktif' ORDER BY b.nama_barang ASC";
    $result_barang = mysqli_query($koneksi, $query_barang);

    if ($result_barang) {
        while ($barang = mysqli_fetch_assoc($result_barang)) {
            $id_barang_current = $barang['id_barang'];
            $stok_awal_master = $barang['stok_awal'];

            // Hitung Total Masuk SEBELUM Awal Bulan
            $query_masuk_lalu = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total FROM tabel_barang_masuk WHERE id_barang = ? AND tanggal_masuk < ?";
            $stmt_masuk_lalu = mysqli_prepare($koneksi, $query_masuk_lalu);
            mysqli_stmt_bind_param($stmt_masuk_lalu, "is", $id_barang_current, $tanggal_awal_bulan);
            mysqli_stmt_execute($stmt_masuk_lalu);
            $total_masuk_lalu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_masuk_lalu))['total'];
            mysqli_stmt_close($stmt_masuk_lalu);

            // Hitung Total Keluar SEBELUM Awal Bulan
            $query_keluar_lalu = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total FROM tabel_barang_keluar WHERE id_barang = ? AND tanggal_keluar < ?";
            $stmt_keluar_lalu = mysqli_prepare($koneksi, $query_keluar_lalu);
            mysqli_stmt_bind_param($stmt_keluar_lalu, "is", $id_barang_current, $tanggal_awal_bulan);
            mysqli_stmt_execute($stmt_keluar_lalu);
            $total_keluar_lalu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_keluar_lalu))['total'];
            mysqli_stmt_close($stmt_keluar_lalu);

            $stok_awal_bulan = $stok_awal_master + $total_masuk_lalu - $total_keluar_lalu;

            // Hitung Total Masuk BULAN INI
            $query_masuk_bulan = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total FROM tabel_barang_masuk WHERE id_barang = ? AND tanggal_masuk BETWEEN ? AND ?";
            $stmt_masuk_bulan = mysqli_prepare($koneksi, $query_masuk_bulan);
            mysqli_stmt_bind_param($stmt_masuk_bulan, "iss", $id_barang_current, $tanggal_awal_bulan, $tanggal_akhir_bulan);
            mysqli_stmt_execute($stmt_masuk_bulan);
            $total_masuk_bulan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_masuk_bulan))['total'];
            mysqli_stmt_close($stmt_masuk_bulan);

            // Hitung Total Keluar BULAN INI
            $query_keluar_bulan = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total FROM tabel_barang_keluar WHERE id_barang = ? AND tanggal_keluar BETWEEN ? AND ?";
            $stmt_keluar_bulan = mysqli_prepare($koneksi, $query_keluar_bulan);
            mysqli_stmt_bind_param($stmt_keluar_bulan, "iss", $id_barang_current, $tanggal_awal_bulan, $tanggal_akhir_bulan);
            mysqli_stmt_execute($stmt_keluar_bulan);
            $total_keluar_bulan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_keluar_bulan))['total'];
            mysqli_stmt_close($stmt_keluar_bulan);

            $stok_akhir_bulan = $stok_awal_bulan + $total_masuk_bulan - $total_keluar_bulan;

            // Simpan ke array laporan
            $laporan_data[] = [
                'nama_barang' => $barang['nama_barang'],
                'satuan' => $barang['nama_satuan'],
                'stok_awal_bulan' => $stok_awal_bulan,
                'total_masuk_bulan' => $total_masuk_bulan,
                'total_keluar_bulan' => $total_keluar_bulan,
                'stok_akhir_bulan' => $stok_akhir_bulan
            ];
        }
    } else {
        echo "Error: " . mysqli_error($koneksi);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Bulanan - Stok Kopi</title>
    
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.0"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        .filter-form {
            margin-bottom: 20px;
            padding: 20px;
            background: #f8f9fa; /* Latar abu-abu muda */
            border-radius: 8px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap; /* Agar responsif */
        }
        .filter-form label { font-weight: 500; margin-right: 5px; }
        /* Style input & select agar konsisten dgn form-group */
        .filter-form select, .filter-form input[type=number] {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1em;
        }
        /* Style tombol Tampilkan (btn-primary) */
        .filter-form button {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            border: none;
            cursor: pointer;
            background-color: #007bff;
            font-size: 1em;
        }
        .filter-form button:hover {
            background-color: #0056b3;
        }
    </style>
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

    <div class="dashboard-container" style="max-width: 1400px;">
        
        <header class="admin-header">
            <h1>Laporan Rekapitulasi Stok Bulanan</h1>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </header>

        <main>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>
            
            <form action="laporan_bulanan.php" method="GET" class="filter-form">
                <label for="bulan">Bulan:</label>
                <select name="bulan" id="bulan">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == $bulan_pilih) ? 'selected' : ''; ?>>
                            <?php echo $nama_bulan[$i]; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label for="tahun">Tahun:</label>
                <input type="number" name="tahun" id="tahun" value="<?php echo $tahun_pilih; ?>" min="2020" max="<?php echo date('Y'); ?>">

                <button type="submit">Tampilkan Laporan</button>
            </form>

            <?php if (!empty($laporan_data)): ?>
                <div class="section-header" style="margin-top: 20px;">
                    <h2>Periode: <?php echo $nama_bulan[$bulan_pilih] . ' ' . $tahun_pilih; ?></h2>
                    <a href="cetak_laporan_bulanan.php?bulan=<?php echo $bulan_pilih; ?>&tahun=<?php echo $tahun_pilih; ?>" target="_blank" class="print-btn">🖨️ Versi Cetak</a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Stok Awal Bulan</th>
                            <th>Masuk Bulan Ini</th>
                            <th>Keluar Bulan Ini</th>
                            <th>Stok Akhir Bulan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($laporan_data as $item): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                            <td><?php echo number_format($item['stok_awal_bulan'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format($item['total_masuk_bulan'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format($item['total_keluar_bulan'], 2, ',', '.'); ?></td>
                            <td><strong><?php echo number_format($item['stok_akhir_bulan'], 2, ',', '.'); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

            <?php elseif(isset($_GET['bulan'])): // Tampilkan pesan jika sudah filter tapi data kosong ?>
                <p class="pesan-aman" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                    Tidak ada data transaksi untuk periode yang dipilih.
                </p>
            <?php endif; ?>

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