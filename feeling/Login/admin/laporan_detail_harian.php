<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

// 1. TANGKAP INPUT TANGGAL (Default: 1 minggu terakhir biar gak berat loadingnya)
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-d', strtotime('-7 days'));
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// Fungsi Format Angka Pintar
function fp($n) {
    if ($n == 0) return '-';
    return (floor($n) == $n) ? number_format($n, 0, ',', '.') : rtrim(number_format($n, 2, ',', '.'), '0');
}

// 2. AMBIL SEMUA DATA BARANG
$q_barang = mysqli_query($koneksi, "SELECT * FROM tabel_barang b JOIN tabel_satuan_unit s ON b.id_satuan=s.id_satuan ORDER BY b.nama_barang ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Detail Stok Harian</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f4f4; padding: 20px; color: #333; }
        .container { background: white; padding: 20px; max-width: 1000px; margin: 0 auto; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        /* TOOLS & FORM */
        .tools { background: #eee; padding: 15px; margin-bottom: 20px; border-radius: 5px; display: flex; gap: 10px; align-items: center; justify-content: space-between; }
        form { display: flex; gap: 10px; align-items: center; }
        input[type="date"] { padding: 5px; border: 1px solid #ccc; }
        button { padding: 6px 15px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 3px; }
        .btn-print { padding: 6px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; }
        .btn-back { background: #6c757d; color: white; text-decoration: none; padding: 6px 15px; border-radius: 3px; }

        /* TABEL STYLE */
        .item-block { margin-bottom: 40px; border: 1px solid #ddd; padding: 10px; border-radius: 5px; page-break-inside: avoid; }
        .item-header { background: #333; color: white; padding: 8px 10px; font-weight: bold; display: flex; justify-content: space-between; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 5px; font-size: 13px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: center; }
        th { background: #f0f0f0; }
        
        .col-in { background: #e6fffa; color: #004d40; }
        .col-out { background: #fff5f5; color: #c53030; }
        .col-bal { background: #f0f7ff; font-weight: bold; }
        .text-right { text-align: right; }

        /* CETAK */
        @media print {
            body { background: white; padding: 0; }
            .container { box-shadow: none; max-width: 100%; }
            .tools, .btn-back { display: none; }
            .item-block { border: none; padding: 0; margin-bottom: 20px; border-bottom: 2px solid #000; }
            .item-header { background: #ddd; color: black; border: 1px solid #000; }
            table, th, td { border: 1px solid black; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="tools">
        <form method="GET">
            <label>Periode:</label>
            <input type="date" name="tgl_awal" value="<?= $tgl_awal; ?>" required>
            <span>s/d</span>
            <input type="date" name="tgl_akhir" value="<?= $tgl_akhir; ?>" required>
            <button type="submit">Tampilkan</button>
        </form>
        <div>
            <a href="dashboard.php" class="btn-back">Kembali</a>
            <button onclick="window.print()" class="btn-print">🖨️ Cetak</button>
        </div>
    </div>

    <h2 style="text-align:center; margin-bottom: 5px;">LAPORAN RINCIAN HARIAN STOK</h2>
    <p style="text-align:center; margin-top:0; color:#666;">Periode: <?= date('d F Y', strtotime($tgl_awal)); ?> s/d <?= date('d F Y', strtotime($tgl_akhir)); ?></p>

    <?php while($b = mysqli_fetch_assoc($q_barang)) { 
        $id_b = $b['id_barang'];
        
        // 1. HITUNG SALDO AWAL (Sebelum Tgl Awal)
        $q_lalu_in = mysqli_query($koneksi, "SELECT SUM(jumlah_masuk) as tot FROM tabel_barang_masuk WHERE id_barang='$id_b' AND tanggal_masuk < '$tgl_awal'");
        $q_lalu_out = mysqli_query($koneksi, "SELECT SUM(jumlah_keluar) as tot FROM tabel_barang_keluar WHERE id_barang='$id_b' AND tanggal_keluar < '$tgl_awal'");
        
        $lalu_in = mysqli_fetch_assoc($q_lalu_in)['tot'] ?? 0;
        $lalu_out = mysqli_fetch_assoc($q_lalu_out)['tot'] ?? 0;
        
        $saldo_berjalan = $b['stok_awal'] + $lalu_in - $lalu_out;
        $saldo_awal_tampilan = $saldo_berjalan; // Simpan untuk display
    ?>

    <div class="item-block">
        <div class="item-header">
            <span><?= htmlspecialchars($b['nama_barang']); ?> (Satuan: <?= $b['nama_satuan']; ?>)</span>
            <span>Stok Awal Periode: <?= fp($saldo_awal_tampilan); ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="20%">Tanggal</th>
                    <th width="20%" class="col-in">Masuk</th>
                    <th width="20%" class="col-out">Keluar</th>
                    <th width="20%" class="col-bal">Sisa Akhir</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background:#fafafa; color:#888; font-style:italic;">
                    <td class="text-right">Saldo Sebelumnya &raquo;</td>
                    <td>-</td>
                    <td>-</td>
                    <td class="text-right col-bal"><?= fp($saldo_awal_tampilan); ?></td>
                </tr>

                <?php
                // 2. LOOPING TANGGAL DARI AWAL SAMPAI AKHIR
                $period = new DatePeriod(
                    new DateTime($tgl_awal),
                    new DateInterval('P1D'),
                    (new DateTime($tgl_akhir))->modify('+1 day')
                );

                foreach ($period as $dt) {
                    $tgl_cek = $dt->format("Y-m-d");

                    // Ambil Data Transaksi Hari Itu
                    $q_in = mysqli_query($koneksi, "SELECT SUM(jumlah_masuk) as tot FROM tabel_barang_masuk WHERE id_barang='$id_b' AND tanggal_masuk='$tgl_cek'");
                    $q_out = mysqli_query($koneksi, "SELECT SUM(jumlah_keluar) as tot FROM tabel_barang_keluar WHERE id_barang='$id_b' AND tanggal_keluar='$tgl_cek'");

                    $in_hari_ini = mysqli_fetch_assoc($q_in)['tot'] ?? 0;
                    $out_hari_ini = mysqli_fetch_assoc($q_out)['tot'] ?? 0;

                    // Update Stok Berjalan
                    $saldo_berjalan = $saldo_berjalan + $in_hari_ini - $out_hari_ini;
                ?>
                <tr>
                    <td><?= date('d-m-Y', strtotime($tgl_cek)); ?></td>
                    
                    <td class="text-right col-in" style="<?= $in_hari_ini > 0 ? 'font-weight:bold;' : ''; ?>">
                        <?= fp($in_hari_ini); ?>
                    </td>
                    
                    <td class="text-right col-out" style="<?= $out_hari_ini > 0 ? 'font-weight:bold;' : ''; ?>">
                        <?= fp($out_hari_ini); ?>
                    </td>
                    
                    <td class="text-right col-bal"><?= fp($saldo_berjalan); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
    <?php } // End While Barang ?>

</div>

</body>
</html>