<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// QUERY SAMA PERSIS
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
    <title>Cetak Stok Harian</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 20px; }
        .kop-surat { width: 100%; height: auto; display: block; margin-bottom: 5px; }

        h2 { text-align: center; margin: 5px 0; text-transform: uppercase; font-size: 14pt; }
        h3 { text-align: right; font-weight: normal; margin-top: 5px; font-size: 11pt; }

        /* --- SETTING TABEL RAPAT (COMPACT) --- */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; /* Font kecil */ }
        th, td { border: 1px solid #000; padding: 4px 6px; /* Padding kecil */ }
        th { background: #f0f0f0; text-align: center; font-weight: bold; }
        
        td:nth-child(4) { text-align: right; font-weight: bold; }
        td:nth-child(1), td:nth-child(3) { text-align: center; }

        .tanda-tangan { float: right; text-align: center; width: 200px; margin-top: 20px; font-size: 11pt; }

        @media print {
            @page { margin: 0; size: auto; }
            body { margin: 15px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print" style="margin-bottom: 20px;">🖨️ Cetak</button>

    <img src="../assets/kop.png" alt="Kop Surat" class="kop-surat">

    <h2>LAPORAN POSISI STOK</h2>
    <p style="text-align:center; margin:0;">Per Tanggal: <?php echo date('d F Y', strtotime($tanggal)); ?></p>

    <h3>Banjarbaru, <?php echo date('d F Y'); ?></h3>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>Nama Barang</th>
                <th width="15%">Satuan</th>
                <th width="20%">Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; 
            while($row = mysqli_fetch_assoc($result)) {
                $stok_akhir = $row['stok_awal'] + $row['tot_masuk'] - $row['tot_keluar'];
                $tampil = (floor($stok_akhir) == $stok_akhir) ? number_format($stok_akhir, 0, ',', '.') : rtrim(number_format($stok_akhir, 2, ',', '.'), '0');
            ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= htmlspecialchars($row['nama_barang']); ?></td>
                <td><?= htmlspecialchars($row['nama_satuan']); ?></td>
                <td><?= $tampil; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="tanda-tangan">
        <p>Banjarbaru, <?php echo date('d-m-Y'); ?></p>
        <p>Dilaporkan Oleh,</p>
        <br><br><br>
        <p><strong>( <?php echo $_SESSION['name']; ?> )</strong><br>Admin Gudang</p>
    </div>

</body>
</html>