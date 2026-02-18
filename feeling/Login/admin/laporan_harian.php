<?php
// (A) PENJAGA SESI & KONEKSI (TETAP SAMA)
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php'); exit();
}

// (B) DATA UNTUK DROPDOWN
$query_barang_list = "SELECT id_barang, nama_barang FROM tabel_barang ORDER BY nama_barang ASC";
$result_barang_list = mysqli_query($koneksi, $query_barang_list);

// (C) LOGIKA FILTER (TETAP SAMA)
$id_barang_pilih = isset($_GET['id_barang']) ? (int)$_GET['id_barang'] : 0;
$tanggal_pilih = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

$data_transaksi = [];
$stok_awal_hari = 0; $total_masuk_hari = 0; $total_keluar_hari = 0; $stok_akhir_hari = 0;
$info_barang = null; $mode_laporan = "";

if ($id_barang_pilih > 0) {
    $mode_laporan = "single";
    // ... (Logika hitung stok single item - SAMA SEPERTI SEBELUMNYA) ...
    $q_info = "SELECT b.nama_barang, s.nama_satuan, b.stok_awal FROM tabel_barang b JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan WHERE b.id_barang = $id_barang_pilih";
    $info_barang = mysqli_fetch_assoc(mysqli_query($koneksi, $q_info));

    $q_m_lalu = "SELECT COALESCE(SUM(jumlah_masuk), 0) as total FROM tabel_barang_masuk WHERE id_barang = $id_barang_pilih AND tanggal_masuk < '$tanggal_pilih'";
    $masuk_lalu = mysqli_fetch_assoc(mysqli_query($koneksi, $q_m_lalu))['total'];
    
    $q_k_lalu = "SELECT COALESCE(SUM(jumlah_keluar), 0) as total FROM tabel_barang_keluar WHERE id_barang = $id_barang_pilih AND tanggal_keluar < '$tanggal_pilih'";
    $keluar_lalu = mysqli_fetch_assoc(mysqli_query($koneksi, $q_k_lalu))['total'];

    $stok_awal_hari = $info_barang['stok_awal'] + $masuk_lalu - $keluar_lalu;

    $query_union = "SELECT 'Masuk' as jenis, jumlah_masuk as jumlah, 'Supplier' as keterangan, NULL as tujuan, created_at as waktu FROM tabel_barang_masuk WHERE id_barang = $id_barang_pilih AND tanggal_masuk = '$tanggal_pilih' UNION ALL SELECT 'Keluar' as jenis, jumlah_keluar as jumlah, keterangan_darurat as keterangan, t.nama_tujuan as tujuan, created_at as waktu FROM tabel_barang_keluar bk LEFT JOIN tabel_tujuan t ON bk.id_tujuan = t.id_tujuan WHERE id_barang = $id_barang_pilih AND tanggal_keluar = '$tanggal_pilih' ORDER BY waktu ASC, jenis ASC";
    $result_transaksi = mysqli_query($koneksi, $query_union);

    $stok_berjalan = $stok_awal_hari;
    if ($result_transaksi) {
        while ($row = mysqli_fetch_assoc($result_transaksi)) {
            if ($row['jenis'] == 'Masuk') {
                $stok_berjalan += $row['jumlah']; $total_masuk_hari += $row['jumlah']; $ket = "Dari Supplier";
            } else {
                $stok_berjalan -= $row['jumlah']; $total_keluar_hari += $row['jumlah']; $ket = "Ke: " . $row['tujuan'];
                if(!empty($row['keterangan'])) $ket .= " (" . $row['keterangan'] . ")";
            }
            $row['sisa_stok'] = $stok_berjalan; $row['detail_ket'] = $ket;
            $data_transaksi[] = $row;
        }
    }
    $stok_akhir_hari = $stok_berjalan;

} elseif (isset($_GET['id_barang']) && $id_barang_pilih == 0) {
    $mode_laporan = "all";
    // ... (Logika hitung all items - SAMA SEPERTI SEBELUMNYA) ...
    $query_all = "SELECT 'Masuk' as jenis, bm.jumlah_masuk as jumlah, b.nama_barang, s.nama_satuan, 'Supplier' as tujuan, NULL as keterangan, bm.created_at as waktu FROM tabel_barang_masuk bm JOIN tabel_barang b ON bm.id_barang = b.id_barang JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan WHERE bm.tanggal_masuk = '$tanggal_pilih' UNION ALL SELECT 'Keluar' as jenis, bk.jumlah_keluar as jumlah, b.nama_barang, s.nama_satuan, t.nama_tujuan as tujuan, bk.keterangan_darurat as keterangan, bk.created_at as waktu FROM tabel_barang_keluar bk JOIN tabel_barang b ON bk.id_barang = b.id_barang JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan LEFT JOIN tabel_tujuan t ON bk.id_tujuan = t.id_tujuan WHERE bk.tanggal_keluar = '$tanggal_pilih' ORDER BY waktu ASC";
    $result_all = mysqli_query($koneksi, $query_all);
    if($result_all) { while($row = mysqli_fetch_assoc($result_all)) { $data_transaksi[] = $row; } }
}

$nama_user_login = $_SESSION['name'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Harian - Stok Kopi</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.8">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* === TAMPILAN KHUSUS FILTER BAR === */
        .filter-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
        }
        .filter-form {
            display: flex;
            gap: 20px;
            align-items: flex-end; /* Agar tombol sejajar dengan input */
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            flex: 1; /* Agar input melebar rata */
            min-width: 200px;
        }
        .filter-group label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        .filter-group select, 
        .filter-group input {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            background-color: #fafafa;
        }
        .filter-btn {
            padding: 12px 25px;
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            height: 46px; /* Samakan tinggi dengan input */
            transition: 0.3s;
        }
        .filter-btn:hover { background-color: #0b5ed7; }

        /* === TAMPILAN SUMMARY CARD === */
        .summary-box { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .card-sum { flex: 1; min-width: 180px; padding: 20px; background: #fff; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #eee; position: relative; overflow: hidden; }
        .card-sum h3 { margin: 0 0 10px 0; font-size: 0.9em; color: #777; text-transform: uppercase; letter-spacing: 1px; }
        .card-sum p { margin: 0; font-size: 1.8em; font-weight: bold; color: #333; }
        
        /* Warna Card Border Top */
        .card-sum.awal { border-top: 4px solid #6c757d; }
        .card-sum.masuk { border-top: 4px solid #28a745; }
        .card-sum.keluar { border-top: 4px solid #dc3545; }
        .card-sum.akhir { border-top: 4px solid #007bff; background-color: #f8fbff; }

        /* CSS Print */
        @media print {
            .admin-header, .filter-container, .btn-secondary, .print-btn, .admin-nav { display: none !important; }
            .dashboard-container { box-shadow: none; border: none; margin: 0; padding: 0; width: 100%; max-width: 100%; }
            body { background-color: white; }
            .card-sum { border: 1px solid #000 !important; box-shadow: none; }
        }
    </style>
</head>
<body>

    <div class="dashboard-container" style="max-width: 1200px;">
        
        <header class="admin-header">
            <h1>Laporan Harian</h1>
            <a href="../logout.php" class="logout-btn">Logout</a> 
        </header>

        <main>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali ke Dashboard</a>
            
            <div class="filter-container">
                <form action="laporan_harian.php" method="GET" class="filter-form">
                    <div class="filter-group">
                        <label><i class='bx bx-calendar'></i> Pilih Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo $tanggal_pilih; ?>" required>
                    </div>

                    <div class="filter-group">
                        <label><i class='bx bx-package'></i> Pilih Barang</label>
                        <select name="id_barang">
                            <option value="0">-- Tampilkan Semua Aktivitas --</option>
                            <?php 
                            mysqli_data_seek($result_barang_list, 0);
                            while ($b = mysqli_fetch_assoc($result_barang_list)): ?>
                                <option value="<?php echo $b['id_barang']; ?>" <?php echo ($b['id_barang'] == $id_barang_pilih) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($b['nama_barang']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="filter-btn"><i class='bx bx-filter-alt'></i> Tampilkan Data</button>
                </form>
            </div>
            <?php if ($mode_laporan == 'single' && $info_barang): ?>
                
                <div class="section-header">
                    <h2>Detail: <?php echo htmlspecialchars($info_barang['nama_barang']); ?> <small style="color:#777; font-size:0.8em;">(<?php echo date('d-m-Y', strtotime($tanggal_pilih)); ?>)</small></h2>
                    <button onclick="window.print()" class="print-btn">🖨️ Cetak</button>
                </div>

                <div class="summary-box">
                    <div class="card-sum awal">
                        <h3>Stok Awal</h3>
                        <p><?php echo number_format($stok_awal_hari, 2, ',', '.'); ?> <small><?php echo $info_barang['nama_satuan']; ?></small></p>
                    </div>
                    <div class="card-sum masuk">
                        <h3>Total Masuk</h3>
                        <p class="text-success">+<?php echo number_format($total_masuk_hari, 2, ',', '.'); ?></p>
                    </div>
                    <div class="card-sum keluar">
                        <h3>Total Keluar</h3>
                        <p class="text-danger">-<?php echo number_format($total_keluar_hari, 2, ',', '.'); ?></p>
                    </div>
                    <div class="card-sum akhir">
                        <h3>Stok Akhir</h3>
                        <p><?php echo number_format($stok_akhir_hari, 2, ',', '.'); ?> <small><?php echo $info_barang['nama_satuan']; ?></small></p>
                    </div>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>No</th> <th>Jam</th> <th>Jenis</th> <th>Keterangan / Tujuan</th> <th style="text-align: right;">Jumlah</th> <th style="text-align: right;">Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="background-color: #f9f9f9; font-weight: bold; color: #555;">
                            <td>-</td> <td>-</td> <td>SALDO AWAL</td> <td>-</td> <td style="text-align: right;">-</td> <td style="text-align: right;"><?php echo number_format($stok_awal_hari, 2, ',', '.'); ?></td>
                        </tr>
                        <?php if (!empty($data_transaksi)): ?>
                            <?php $no = 1; foreach ($data_transaksi as $row): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo date('H:i', strtotime($row['waktu'])); ?></td>
                                <td>
                                    <?php if ($row['jenis'] == 'Masuk'): ?>
                                        <span style="color: #198754; font-weight: bold; background: #d1e7dd; padding: 3px 8px; border-radius: 4px;">Masuk</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545; font-weight: bold; background: #f8d7da; padding: 3px 8px; border-radius: 4px;">Keluar</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['detail_ket']); ?></td>
                                <td style="text-align: right;">
                                    <?php echo number_format($row['jumlah'], 2, ',', '.'); ?>
                                </td>
                                <td style="text-align: right; font-weight: bold;">
                                    <?php echo number_format($row['sisa_stok'], 2, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">Tidak ada transaksi pada tanggal ini.</td></tr>
                        <?php endif; ?>
                        <tr style="background-color: #e9ecef; font-weight: bold;">
                            <td colspan="5" style="text-align: right;">SALDO AKHIR</td>
                            <td style="text-align: right;"><?php echo number_format($stok_akhir_hari, 2, ',', '.'); ?></td>
                        </tr>
                    </tbody>
                </table>

            <?php elseif ($mode_laporan == 'all'): ?>
                <div class="section-header">
                    <h2>Log Aktivitas Gudang <small style="color:#777; font-size:0.8em;">(<?php echo date('d-m-Y', strtotime($tanggal_pilih)); ?>)</small></h2>
                    <button onclick="window.print()" class="print-btn">🖨️ Cetak</button>
                </div>
                
                <table>
                    <thead><tr><th>Jam</th><th>Nama Barang</th><th>Jenis</th><th>Tujuan / Sumber</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        <?php if (!empty($data_transaksi)): foreach ($data_transaksi as $row): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($row['waktu'])); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                                <td><?php if ($row['jenis'] == 'Masuk'): ?><span style="color: green; font-weight: bold;">⬇️ Masuk</span><?php else: ?><span style="color: red; font-weight: bold;">⬆️ Keluar</span><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($row['tujuan']); ?></td>
                                <td><strong><?php echo number_format($row['jumlah'], 2, ',', '.'); ?></strong> <?php echo htmlspecialchars($row['nama_satuan']); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" style="text-align: center; padding: 30px;">Tidak ada aktivitas gudang hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

            <?php else: ?>
                <div style="text-align: center; padding: 50px; color: #777; border: 2px dashed #ccc; border-radius: 8px; margin-top: 20px;">
                    <i class='bx bx-search-alt' style="font-size: 4em; color: #ccc;"></i>
                    <p style="font-size: 1.2em; margin-top: 10px;">Silakan pilih <strong>Tanggal</strong> dan <strong>Jenis Laporan</strong> di atas.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>