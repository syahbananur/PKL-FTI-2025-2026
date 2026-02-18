<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna'])) { header('Location: ../index.php'); exit(); }

$jenis = isset($_GET['jenis']) ? $_GET['jenis'] : 'masuk';
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');

$judul = ($jenis == 'masuk') ? "REKAP TOTAL BARANG MASUK" : "REKAP TOTAL BARANG KELUAR";
$tabel = ($jenis == 'masuk') ? "tabel_barang_masuk" : "tabel_barang_keluar";
$kolom = ($jenis == 'masuk') ? "jumlah_masuk" : "jumlah_keluar";
$tgl   = ($jenis == 'masuk') ? "tanggal_masuk" : "tanggal_keluar";

$query = "SELECT b.nama_barang, s.nama_satuan, SUM(t.$kolom) as total
          FROM $tabel t
          JOIN tabel_barang b ON t.id_barang = b.id_barang
          JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
          WHERE MONTH(t.$tgl)='$bulan' AND YEAR(t.$tgl)='$tahun'
          GROUP BY t.id_barang";

$result = mysqli_query($koneksi, $query);

$bulanIndo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 20px; }
        .kop-surat { width: 100%; height: auto; display: block; margin-bottom: 10px; padding-bottom: 5px; }

        h2 { text-align: center; margin: 5px 0; text-transform: uppercase; }
        h3 { text-align: right; font-weight: normal; margin-top: 10px; font-size: 12pt; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12pt; }
        th, td { border: 1px solid #000; padding: 8px 10px; }
        th { background: #f0f0f0; text-align: center; font-weight: bold; }
        
        td:nth-child(4) { text-align: right; font-weight: bold; }
        td:nth-child(1), td:nth-child(3) { text-align: center; }
        
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

    <h2><?php echo $judul; ?></h2>
    <p style="text-align:center;">Periode: <?php echo $bulanIndo[$bulan] . " " . $tahun; ?></p>

    <h3>Banjarbaru, <?php echo date('d F Y'); ?></h3>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">No</th> 
                <th>Nama Barang</th> 
                <th style="width:15%;">Satuan</th> 
                <th style="width:25%;">Total Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($row = mysqli_fetch_assoc($result)) { 
                $jml = $row['total'];
                
                // FORMAT ANGKA PINTAR
                if (floor($jml) == $jml) {
                    $tampil = number_format($jml, 0, ',', '.');
                } else {
                    $tampil = rtrim(number_format($jml, 2, ',', '.'), '0');
                }
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_satuan']); ?></td>
                <td><?php echo $tampil; ?></td>
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