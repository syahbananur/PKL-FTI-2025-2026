<?php
session_start();
require_once '../config/config.php';

// Cek Login
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

// ==========================================================
// (A) LOGIKA PENENTU JENIS LAPORAN (MASUK / KELUAR)
// ==========================================================

// 1. Tangkap "Sinyal" dari URL (?jenis=...)
// Jika tidak ada sinyal, otomatis dianggap 'masuk' (Default)
$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'masuk'; 

// 2. Tangkap Filter Tanggal
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : date('Y-m-01');
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-d');

// 3. Percabangan Logika (IF / ELSE)
if ($jenis == 'masuk') {
    // === JIKA JENISNYA MASUK ===
    $judul_laporan = "RIWAYAT BARANG MASUK";
    $query = "SELECT m.*, b.nama_barang, b.id_satuan, s.nama_supplier, u.nama_lengkap, sat.nama_satuan
              FROM tabel_barang_masuk m
              JOIN tabel_barang b ON m.id_barang = b.id_barang
              JOIN tabel_satuan_unit sat ON b.id_satuan = sat.id_satuan
              LEFT JOIN tabel_supplier s ON m.id_supplier = s.id_supplier
              LEFT JOIN tabel_pengguna u ON m.id_pengguna_pencatat = u.id_pengguna
              WHERE m.tanggal_masuk BETWEEN '$tgl_awal' AND '$tgl_akhir'
              ORDER BY m.tanggal_masuk DESC";
    $kolom_sumber = "Supplier"; // Judul kolom khusus masuk
} else {
    // === JIKA JENISNYA KELUAR (ATAU LAINNYA) ===
    $judul_laporan = "RIWAYAT BARANG KELUAR";
    $query = "SELECT k.*, b.nama_barang, b.id_satuan, t.nama_tujuan, u.nama_lengkap, sat.nama_satuan
              FROM tabel_barang_keluar k
              JOIN tabel_barang b ON k.id_barang = b.id_barang
              JOIN tabel_satuan_unit sat ON b.id_satuan = sat.id_satuan
              LEFT JOIN tabel_tujuan t ON k.id_tujuan = t.id_tujuan
              LEFT JOIN tabel_pengguna u ON k.id_pengguna_pencatat = u.id_pengguna
              WHERE k.tanggal_keluar BETWEEN '$tgl_awal' AND '$tgl_akhir'
              ORDER BY k.tanggal_keluar DESC";
    $kolom_sumber = "Tujuan"; // Judul kolom khusus keluar
}

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Riwayat</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 20px; }
        
        /* KOP SURAT */
        .kop-surat { width: 100%; height: auto; display: block; margin-bottom: 10px; padding-bottom: 5px; }

        h2 { text-align: center; margin: 5px 0; text-transform: uppercase; }
        h3 { text-align: right; font-weight: normal; margin-top: 10px; font-size: 12pt; }
        
        /* TABEL */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 11pt; }
        th, td { border: 1px solid #000; padding: 6px 8px; }
        th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        
        /* Layout Kolom */
        td:nth-child(5) { text-align: right; font-weight: bold; } /* Kolom Jumlah */
        td:nth-child(1), td:nth-child(2), td:nth-child(6) { text-align: center; }

        .tanda-tangan { float: right; text-align: center; width: 200px; margin-top: 30px; }

        @media print {
            @page { margin: 0; size: auto; }
            body { margin: 0; padding: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print" style="margin-bottom: 20px; padding: 10px; cursor: pointer;">🖨️ Cetak</button>

    <img src="../assets/kop.png" alt="Kop Surat" class="kop-surat">

    <h2><?php echo $judul_laporan; ?></h2>
    <p style="text-align:center;">Periode: <?php echo date('d-m-Y', strtotime($tgl_awal)); ?> s/d <?php echo date('d-m-Y', strtotime($tgl_akhir)); ?></p>
    
    <h3>Banjarbaru, <?php echo date('d F Y'); ?></h3>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th> 
                <th style="width: 12%;">Tanggal</th> 
                <th>Nama Barang</th> 
                <th><?php echo $kolom_sumber; ?></th> 
                <th style="width: 10%;">Jumlah</th> 
                <th style="width: 8%;">Satuan</th> 
                <th style="width: 15%;">Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no=1; 
            while($row = mysqli_fetch_assoc($result)) { 
                // Logika Ambil Data (Beda nama kolom antara tabel masuk & keluar)
                $tgl = ($jenis == 'masuk') ? $row['tanggal_masuk'] : $row['tanggal_keluar'];
                $jml = ($jenis == 'masuk') ? $row['jumlah_masuk'] : $row['jumlah_keluar'];
                $sumber = ($jenis == 'masuk') ? $row['nama_supplier'] : $row['nama_tujuan'];

                // Format Angka Pintar
                if (floor($jml) == $jml) {
                    $tampil = number_format($jml, 0, ',', '.');
                } else {
                    $tampil = rtrim(number_format($jml, 2, ',', '.'), '0');
                }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo date('d-m-Y', strtotime($tgl)); ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td><?php echo htmlspecialchars($sumber); ?></td>
                <td><?php echo $tampil; ?></td>
                <td><?php echo htmlspecialchars($row['nama_satuan']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="tanda-tangan">
        <p>Banjarbaru, <?php echo date('d-m-Y'); ?></p>
        <br>
        <p>Dilaporkan Oleh,</p>
        <br><br><br>
        <p><strong>( <?php echo $_SESSION['name']; ?> )</strong><br>Admin Gudang</p>
    </div>

</body>
</html>