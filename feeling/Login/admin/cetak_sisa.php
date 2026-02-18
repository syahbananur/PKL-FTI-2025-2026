<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) AMBIL DATA STOK
$query = "SELECT 
            b.nama_barang, 
            s.nama_satuan, 
            b.stok_awal, 
            (SELECT COALESCE(SUM(jumlah_masuk), 0) FROM tabel_barang_masuk WHERE id_barang = b.id_barang) AS total_masuk,
            (SELECT COALESCE(SUM(jumlah_keluar), 0) FROM tabel_barang_keluar WHERE id_barang = b.id_barang) AS total_keluar
          FROM tabel_barang b
          JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
          ORDER BY b.nama_barang ASC";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Sisa Stok</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 20px; }
        
        /* KOP SURAT */
        .kop-surat {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
            margin-bottom: 10px;
            /* border-bottom: 3px solid #000;  */
            padding-bottom: 5px;
        }
        h1 { text-align: center; padding-bottom: 10px; margin-bottom: 20px; }
        h2, p { text-align: center; margin: 5px 0; }
        h2 { text-transform: uppercase; }
        h3{ text-align: right; }
        
        /* TABEL SIMPEL */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12pt; }
        th, td { border: 1px solid #000; padding: 8px 10px; }
        th { background-color: #f0f0f0; text-align: center; font-weight: bold; }
        
        /* Rata Kanan untuk Angka Sisa */
        td:nth-child(4) { text-align: right; font-weight: bold; }
        /* Rata Tengah untuk No dan Satuan */
        td:nth-child(1), td:nth-child(3) { text-align: center; }

        .tanda-tangan {
            float: right;
            text-align: center;
            width: 200px;
            margin-top: 50px;
        }

        @media print {
            .no-print { display: none; }
            h1 { font-size: 16pt; margin-bottom: 15px; }

            body { margin: 0; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print" style="margin-bottom: 20px; padding: 10px; cursor: pointer;">🖨️ Cetak Laporan</button>

    <img src="../assets/kop.png" alt="Kop Surat" class="kop-surat">

    <!-- <h2>LAPORAN SISA STOK GUDANG</h2> -->
    <h1>LAPORAN SISA STOK</h1>
    <h3>Banjarbaru, <?php echo date('d F Y'); ?></h3>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th>Nama Barang</th>
                <th style="width: 15%;">Satuan</th>
                <th style="width: 20%;">Sisa Stok</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($row = mysqli_fetch_assoc($result)) {
                // 1. Hitung Sisa Murni
                $sisa = $row['stok_awal'] + $row['total_masuk'] - $row['total_keluar'];
                
                // 2. LOGIKA FORMAT ANGKA PINTAR
                // Cek apakah angka bulat? (Misal: 10.00)
                if (floor($sisa) == $sisa) {
                    // Jika bulat, gunakan 0 desimal (10)
                    $tampilan_stok = number_format($sisa, 0, ',', '.');
                } else {
                    // Jika desimal, gunakan 2 desimal dulu (10,50)
                    $tampilan_stok = number_format($sisa, 2, ',', '.');
                    // Hapus nol di belakang koma agar rapi (10,50 jadi 10,5)
                    $tampilan_stok = rtrim($tampilan_stok, '0');
                }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_satuan']); ?></td>
                
                <td><?php echo $tampilan_stok; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>

    <div class="tanda-tangan">
        <p>Banjarbaru, <?php echo date('d-m-Y'); ?></p>
        <br>
        <p>Dilaporkan Oleh,</p>        <br><br><br>
        <p><strong>( <?php echo $_SESSION['name']; ?> )</strong><br>Admin Gudang</p>
    </div>

</body>
</html>
<?php mysqli_close($koneksi); ?>