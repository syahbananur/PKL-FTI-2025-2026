<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

// 1. TANGKAP TANGGAL (Default: Hari Ini)
$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// 2. QUERY HITUNG STOK SAMPAI TANGGAL TERSEBUT
// Logika: Stok = Stok Awal Master + (Masuk s/d Tgl) - (Keluar s/d Tgl)
$query = "SELECT b.nama_barang, s.nama_satuan, b.stok_awal,
          (SELECT COALESCE(SUM(jumlah_masuk),0) FROM tabel_barang_masuk WHERE id_barang=b.id_barang AND tanggal_masuk <= '$tanggal') as tot_masuk,
          (SELECT COALESCE(SUM(jumlah_keluar),0) FROM tabel_barang_keluar WHERE id_barang=b.id_barang AND tanggal_keluar <= '$tanggal') as tot_keluar
          FROM tabel_barang b
          JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
          ORDER BY b.nama_barang ASC";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Stok Harian</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f4f4f4; padding: 20px; color: #333; }
        .dashboard-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); max-width: 900px; margin: 0 auto; border-top: 5px solid #d9534f; }
        
        .header-tools { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .filter-form { display: flex; gap: 10px; align-items: center; }
        input[type="date"] { padding: 6px; border: 1px solid #ccc; border-radius: 4px; }
        
        /* TOMBOL */
        .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; color: white; font-size: 14px; border: none; cursor: pointer; display: inline-block; }
        .btn-primary { background: #007bff; }
        .btn-secondary { background: #6c757d; }
        .btn-danger { background: #d9534f; }

        /* --- CSS TABEL COMPACT (AGAR TIDAK TINGGI) --- */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; /* Font lebih kecil */ }
        th { background: #f1f1f1; font-weight: bold; padding: 8px 10px; border: 1px solid #ddd; }
        td { 
            padding: 5px 10px; /* Padding kecil agar baris rapat */
            border: 1px solid #ddd; 
            height: 30px; /* Paksa tinggi baris minimal */
        }
        tr:hover { background: #f9f9f9; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h2 style="margin:0;">Laporan Posisi Stok</h2>
            <a href="dashboard.php" class="btn btn-secondary">&laquo; Kembali</a>
        </div>

        <div class="header-tools">
            <form method="GET" class="filter-form">
                <label>Pilih Tanggal:</label>
                <input type="date" name="tanggal" value="<?= $tanggal; ?>" required>
                <button type="submit" class="btn btn-primary">Lihat</button>
            </form>

            <a href="cetak_stok_harian.php?tanggal=<?= $tanggal; ?>" target="_blank" class="btn btn-secondary">
                🖨️ Cetak Laporan
            </a>
        </div>

        <p>Posisi stok per tanggal: <strong><?= date('d F Y', strtotime($tanggal)); ?></strong></p>

        <table>
            <thead>
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th>Nama Barang</th>
                    <th width="15%" class="text-center">Satuan</th>
                    <th width="20%" class="text-right">Sisa Stok</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no=1; 
                while($row = mysqli_fetch_assoc($result)) {
                    // Rumus: Stok = Awal + Masuk - Keluar
                    $stok_akhir = $row['stok_awal'] + $row['tot_masuk'] - $row['tot_keluar'];
                    
                    // Format Angka Pintar
                    $tampil = (floor($stok_akhir) == $stok_akhir) ? number_format($stok_akhir, 0, ',', '.') : rtrim(number_format($stok_akhir, 2, ',', '.'), '0');
                ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['nama_satuan']); ?></td>
                    <td class="text-right bold"><?= $tampil; ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

</body>
</html>