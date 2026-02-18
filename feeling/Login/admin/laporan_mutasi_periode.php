<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

// 1. TANGKAP TANGGAL (Default: Tanggal 1 bulan ini s/d Hari ini)
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// 2. FUNGSI FORMAT ANGKA PINTAR (Hilangkan .00 jika bulat)
function fp($n) {
    if ($n == 0) return '-';
    return (floor($n) == $n) ? number_format($n, 0, ',', '.') : rtrim(number_format($n, 2, ',', '.'), '0');
}

// 3. QUERY KOMPLEKS (SUBQUERY)
// Kita butuh 4 data untuk setiap barang:
// a. History Masuk (Sebelum tgl awal)
// b. History Keluar (Sebelum tgl awal)
// c. Periode Masuk (Antara tgl awal - akhir)
// d. Periode Keluar (Antara tgl awal - akhir)

$query = "SELECT 
            b.id_barang, 
            b.nama_barang, 
            b.stok_awal as stok_master,
            s.nama_satuan,
            
            -- Hitung Transaksi MASA LALU (Sebelum Start Date)
            (SELECT COALESCE(SUM(jumlah_masuk), 0) FROM tabel_barang_masuk WHERE id_barang = b.id_barang AND tanggal_masuk < '$tgl_awal') as lalu_masuk,
            (SELECT COALESCE(SUM(jumlah_keluar), 0) FROM tabel_barang_keluar WHERE id_barang = b.id_barang AND tanggal_keluar < '$tgl_awal') as lalu_keluar,

            -- Hitung Transaksi PERIODE INI (Antara Start - End)
            (SELECT COALESCE(SUM(jumlah_masuk), 0) FROM tabel_barang_masuk WHERE id_barang = b.id_barang AND tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir') as kini_masuk,
            (SELECT COALESCE(SUM(jumlah_keluar), 0) FROM tabel_barang_keluar WHERE id_barang = b.id_barang AND tanggal_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir') as kini_keluar

          FROM tabel_barang b
          JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
          ORDER BY b.nama_barang ASC";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Mutasi Stok</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f4f4; color: #333; padding: 20px; }
        .container { background: white; max-width: 1000px; margin: 0 auto; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        /* Header Tools */
        .tools { background: #eee; padding: 15px; border-radius: 5px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        form { display: flex; gap: 10px; align-items: center; }
        input[type="date"] { padding: 5px; border: 1px solid #ccc; }
        button { padding: 6px 12px; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 3px; }
        .btn-print { background: #28a745; text-decoration: none; color: white; padding: 6px 12px; border-radius: 3px; display: inline-block; }
        .btn-back { background: #6c757d; }

        /* Tabel Laporan */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { border: 1px solid #000; padding: 6px 8px; }
        th { background: #f0f0f0; text-align: center; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* Warna Kolom Agar Mudah Dibaca */
        .bg-awal { background: #fffbe6; } /* Kuning Tipis */
        .bg-masuk { background: #e6fffa; } /* Hijau Tipis */
        .bg-keluar { background: #fff5f5; } /* Merah Tipis */
        .bg-akhir { background: #e6f7ff; font-weight: bold; } /* Biru Tipis */

        /* CSS KHUSUS CETAK (PRINT) */
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; max-width: 100%; padding: 0; margin: 0; }
            .tools, .btn-back { display: none !important; } /* Sembunyikan Form saat print */
            
            /* Kop Surat Sederhana untuk Print */
            .print-header { display: block !important; text-align: center; margin-bottom: 20px; border-bottom: 2px solid black; padding-bottom: 10px; }
            h2, h4 { margin: 5px 0; }
        }
        .print-header { display: none; } /* Sembunyikan Kop di Layar */
    </style>
</head>
<body>

<div class="container">

    <div class="print-header">
        <h2>KOPI GEROBAKAN</h2>
        <h4>LAPORAN MUTASI STOK BARANG</h4>
        <p>Periode: <?= date('d F Y', strtotime($tgl_awal)); ?> s/d <?= date('d F Y', strtotime($tgl_akhir)); ?></p>
    </div>

    <div class="tools">
        <form method="GET">
            <label>Periode:</label>
            <input type="date" name="tgl_awal" value="<?= $tgl_awal; ?>" required>
            <span>s/d</span>
            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir; ?>" required>
            <button type="submit">Tampilkan</button>
            <a href="dashboard.php" class="btn-print btn-back" style="margin-left:5px;">Kembali</a>
        </form>
        
        <button onclick="window.print()" class="btn-print">🖨️ Cetak Laporan</button>
    </div>

    <div style="margin-bottom: 15px;">
        <h3>Laporan Mutasi Stok</h3>
        <span style="color:#666;">Menampilkan pergerakan stok dari tanggal <strong><?= date('d-m-Y', strtotime($tgl_awal)); ?></strong> sampai <strong><?= date('d-m-Y', strtotime($tgl_akhir)); ?></strong>.</span>
    </div>

    <table>
        <thead>
            <tr>
                <th rowspan="2" width="5%">No</th>
                <th rowspan="2">Nama Barang</th>
                <th rowspan="2" width="10%">Satuan</th>
                
                <th class="bg-awal" width="15%">Stok Awal<br><small>(Per <?= date('d-m-Y', strtotime($tgl_awal)); ?>)</small></th>
                <th class="bg-masuk" width="12%">Masuk</th>
                <th class="bg-keluar" width="12%">Keluar</th>
                <th class="bg-akhir" width="15%">Stok Akhir<br><small>(Per <?= date('d-m-Y', strtotime($tgl_akhir)); ?>)</small></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    
                    // RUMUS MATEMATIKA STOK
                    // 1. Stok Awal Periode = Master + (Masuk Dulu) - (Keluar Dulu)
                    $stok_awal_periode = $row['stok_master'] + $row['lalu_masuk'] - $row['lalu_keluar'];

                    // 2. Transaksi Selama Periode (Yang ada di database range tanggal)
                    $masuk_periode = $row['kini_masuk'];
                    $keluar_periode = $row['kini_keluar'];

                    // 3. Stok Akhir = Stok Awal Periode + Masuk Periode - Keluar Periode
                    $stok_akhir_periode = $stok_awal_periode + $masuk_periode - $keluar_periode;
            ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                <td class="text-center"><?= $row['nama_satuan']; ?></td>
                
                <td class="text-right bg-awal"><?= fp($stok_awal_periode); ?></td>
                
                <td class="text-right bg-masuk" style="<?= $masuk_periode > 0 ? 'color:green; font-weight:bold;' : ''; ?>">
                    <?= ($masuk_periode > 0) ? '+ '.fp($masuk_periode) : '-'; ?>
                </td>
                
                <td class="text-right bg-keluar" style="<?= $keluar_periode > 0 ? 'color:red; font-weight:bold;' : ''; ?>">
                    <?= ($keluar_periode > 0) ? '- '.fp($keluar_periode) : '-'; ?>
                </td>
                
                <td class="text-right bg-akhir"><?= fp($stok_akhir_periode); ?></td>
            </tr>
            <?php 
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>Belum ada data barang.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="print-header" style="text-align:right; margin-top:30px; border:none;">
        <p>Banjarbaru, <?= date('d F Y'); ?></p>
        <br><br><br>
        <p>( Admin Gudang )</p>
    </div>

</div>

</body>
</html>