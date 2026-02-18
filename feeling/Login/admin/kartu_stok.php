<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

// 1. INISIALISASI VARIABEL
$id_barang = isset($_GET['id_barang']) ? $_GET['id_barang'] : '';
$tgl_awal  = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01'); // Default awal bulan
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d'); // Default hari ini

// Fungsi Format Angka Pintar (Pcs/Kg)
function fp($n) {
    if ($n == 0) return '-'; // Jika 0 ganti jadi strip
    return (floor($n) == $n) ? number_format($n, 0, ',', '.') : rtrim(number_format($n, 2, ',', '.'), '0');
}

// 2. AMBIL DAFTAR BARANG (Untuk Dropdown)
$q_barang = mysqli_query($koneksi, "SELECT * FROM tabel_barang ORDER BY nama_barang ASC");

// 3. LOGIKA UTAMA (JIKA BARANG DIPILIH)
$data_tabel = [];
$stok_saat_ini = 0;
$info_barang = "";
$satuan = "";

if ($id_barang) {
    // A. Ambil Info Barang & Satuan
    $q_info = mysqli_query($koneksi, "SELECT b.nama_barang, b.stok_awal, s.nama_satuan 
                                      FROM tabel_barang b 
                                      JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan 
                                      WHERE b.id_barang = '$id_barang'");
    $d_info = mysqli_fetch_assoc($q_info);
    $info_barang = $d_info['nama_barang'];
    $satuan = $d_info['nama_satuan'];
    $stok_master = $d_info['stok_awal'];

    // B. Hitung Stok "Masa Lalu" (Sebelum Tanggal Awal yang dipilih)
    // Rumus: Stok Master + (Masuk Dulu) - (Keluar Dulu)
    $q_lalu_masuk = mysqli_query($koneksi, "SELECT SUM(jumlah_masuk) as tot FROM tabel_barang_masuk WHERE id_barang='$id_barang' AND tanggal_masuk < '$tgl_awal'");
    $q_lalu_keluar = mysqli_query($koneksi, "SELECT SUM(jumlah_keluar) as tot FROM tabel_barang_keluar WHERE id_barang='$id_barang' AND tanggal_keluar < '$tgl_awal'");
    
    $lalu_masuk = mysqli_fetch_assoc($q_lalu_masuk)['tot'] ?? 0;
    $lalu_keluar = mysqli_fetch_assoc($q_lalu_keluar)['tot'] ?? 0;

    $saldo_awal_periode = $stok_master + $lalu_masuk - $lalu_keluar;
    $stok_berjalan = $saldo_awal_periode; // Ini yang akan naik turun di tabel

    // C. Loop Tanggal dari Awal s/d Akhir
    $period = new DatePeriod(
        new DateTime($tgl_awal),
        new DateInterval('P1D'),
        (new DateTime($tgl_akhir))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $tgl_cek = $dt->format("Y-m-d");

        // Cek Transaksi di Tanggal Tersebut
        $q_in = mysqli_query($koneksi, "SELECT SUM(jumlah_masuk) as tot FROM tabel_barang_masuk WHERE id_barang='$id_barang' AND tanggal_masuk = '$tgl_cek'");
        $q_out = mysqli_query($koneksi, "SELECT SUM(jumlah_keluar) as tot FROM tabel_barang_keluar WHERE id_barang='$id_barang' AND tanggal_keluar = '$tgl_cek'");
        
        $masuk = mysqli_fetch_assoc($q_in)['tot'] ?? 0;
        $keluar = mysqli_fetch_assoc($q_out)['tot'] ?? 0;

        // Update Stok Berjalan
        $stok_berjalan = $stok_berjalan + $masuk - $keluar;

        // Masukkan ke Array Data
        $data_tabel[] = [
            'tanggal' => $tgl_cek,
            'masuk' => $masuk,
            'keluar' => $keluar,
            'sisa' => $stok_berjalan
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu Stok Barang</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; padding: 20px; }
        .card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; border-top: 5px solid #28a745; }
        
        .form-row { display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; gap: 5px; flex: 1; }
        label { font-weight: bold; font-size: 14px; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        
        .btn { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: bold; }
        .btn:hover { background: #218838; }
        .btn-back { background: #6c757d; margin-right: 10px; }

        /* TABEL STYLE */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: center; }
        th { background: #f8f9fa; font-weight: bold; }
        
        /* WARNA KOLOM */
        .col-masuk { background-color: #e6fffa; color: #006644; } /* Hijau Muda */
        .col-keluar { background-color: #fff5f5; color: #c53030; } /* Merah Muda */
        .col-sisa { font-weight: bold; background-color: #f0f4ff; } /* Biru Muda */
        
        .info-box { background: #e2e6ea; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0;">Kartu Stok (Tracking Item)</h2>
            <a href="dashboard.php" class="btn btn-back">Kembali</a>
        </div>

        <form method="GET" class="form-row">
            <div class="form-group" style="flex: 2;">
                <label>Pilih Barang:</label>
                <select name="id_barang" required>
                    <option value="">-- Pilih Salah Satu --</option>
                    <?php while($b = mysqli_fetch_assoc($q_barang)) { ?>
                        <option value="<?= $b['id_barang']; ?>" <?= ($id_barang == $b['id_barang']) ? 'selected' : ''; ?>>
                            <?= $b['nama_barang']; ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>Dari Tanggal:</label>
                <input type="date" name="tgl_awal" value="<?= $tgl_awal; ?>" required>
            </div>
            <div class="form-group">
                <label>Sampai Tanggal:</label>
                <input type="date" name="tgl_akhir" value="<?= $tgl_akhir; ?>" required>
            </div>
            <button type="submit" class="btn">Cek Kartu Stok</button>
        </form>

        <hr>

        <?php if ($id_barang): ?>
            <div class="info-box">
                <h3 style="margin:0;"><?= strtoupper($info_barang); ?></h3>
                <p style="margin:5px 0 0 0;">Satuan: <?= $satuan; ?> | Saldo Awal (sebelum tgl <?= date('d-m-Y', strtotime($tgl_awal)); ?>): <strong><?= fp($saldo_awal_periode); ?></strong></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Tanggal</th>
                        <th class="col-masuk">Masuk</th>
                        <th class="col-keluar">Keluar</th>
                        <th class="col-sisa">Sisa Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="background: #fafafa; font-style: italic; color: #666;">
                        <td colspan="4" style="text-align: right;">Saldo Awal Periode Ini &raquo;</td>
                        <td class="col-sisa"><?= fp($saldo_awal_periode); ?></td>
                    </tr>

                    <?php 
                    $no = 1;
                    foreach ($data_tabel as $row) { 
                    ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                        
                        <td class="col-masuk"><?= fp($row['masuk']); ?></td>
                        <td class="col-keluar"><?= fp($row['keluar']); ?></td>
                        
                        <td class="col-sisa"><?= fp($row['sisa']); ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align:center; padding: 40px; color: #999;">Silakan pilih barang dan rentang tanggal untuk melihat kartu stok.</p>
        <?php endif; ?>

    </div>

</body>
</html>