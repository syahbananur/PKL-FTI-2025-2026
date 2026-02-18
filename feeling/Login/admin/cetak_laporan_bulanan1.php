<?php
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (A) AMBIL PARAMETER BULAN & TAHUN
$bulan_pilih = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Array Nama Bulan
$nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// (B) LOGIKA PERHITUNGAN DATA (SAMA PERSIS DENGAN WEB VIEW)
$laporan_data = [];

if ($bulan_pilih >= 1 && $bulan_pilih <= 12 && $tahun_pilih > 2000) {

    $tanggal_awal_bulan = "$tahun_pilih-$bulan_pilih-01";
    $tanggal_akhir_bulan = date("Y-m-t", strtotime($tanggal_awal_bulan)); 

    // 1. Ambil Data Master Barang
    $query_barang = "SELECT id_barang, nama_barang, stok_awal, s.nama_satuan
                     FROM tabel_barang b JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
                     WHERE b.status_barang = 'Aktif' ORDER BY b.nama_barang ASC";
    $result_barang = mysqli_query($koneksi, $query_barang);

    if ($result_barang) {
        while ($barang = mysqli_fetch_assoc($result_barang)) {
            $id = $barang['id_barang'];
            $stok_master = $barang['stok_awal'];

            // 2. Hitung Total Masuk LALU (Sebelum tanggal 1 bulan ini)
            // Saya sederhanakan query-nya agar lebih pendek tapi logikanya tetap sama
            $q_masuk_lalu = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_masuk), 0) FROM tabel_barang_masuk WHERE id_barang='$id' AND tanggal_masuk < '$tanggal_awal_bulan'");
            $total_masuk_lalu = mysqli_fetch_row($q_masuk_lalu)[0];

            // 3. Hitung Total Keluar LALU (Sebelum tanggal 1 bulan ini)
            $q_keluar_lalu = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_keluar), 0) FROM tabel_barang_keluar WHERE id_barang='$id' AND tanggal_keluar < '$tanggal_awal_bulan'");
            $total_keluar_lalu = mysqli_fetch_row($q_keluar_lalu)[0];

            // >> HITUNG STOK AWAL BULAN
            $stok_awal_bulan = $stok_master + $total_masuk_lalu - $total_keluar_lalu;

            // 4. Hitung Total Masuk BULAN INI
            $q_masuk_ini = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_masuk), 0) FROM tabel_barang_masuk WHERE id_barang='$id' AND tanggal_masuk BETWEEN '$tanggal_awal_bulan' AND '$tanggal_akhir_bulan'");
            $total_masuk_bulan = mysqli_fetch_row($q_masuk_ini)[0];

            // 5. Hitung Total Keluar BULAN INI
            $q_keluar_ini = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah_keluar), 0) FROM tabel_barang_keluar WHERE id_barang='$id' AND tanggal_keluar BETWEEN '$tanggal_awal_bulan' AND '$tanggal_akhir_bulan'");
            $total_keluar_bulan = mysqli_fetch_row($q_keluar_ini)[0];

            // >> HITUNG STOK AKHIR BULAN
            $stok_akhir_bulan = $stok_awal_bulan + $total_masuk_bulan - $total_keluar_bulan;

            // Simpan ke array
            $laporan_data[] = [
                'nama_barang' => $barang['nama_barang'],
                'satuan' => $barang['nama_satuan'],
                'awal' => $stok_awal_bulan,
                'masuk' => $total_masuk_bulan,
                'keluar' => $total_keluar_bulan,
                'akhir' => $stok_akhir_bulan
            ];
        }
    }
}

// Fungsi Format Angka (Hapus desimal jika bulat)
function fp($n) {
    return (floor($n) == $n) ? number_format($n, 0, ',', '.') : number_format($n, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Laporan Bulanan</title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; color: #000; margin: 20px; }
        .kop-surat { width: 100%; height: auto; display: block; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
        
        h2 { text-align: center; margin: 5px 0; text-transform: uppercase; font-size: 16pt; }
        p.periode { text-align: center; font-size: 12pt; margin-top: 5px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11pt; }
        th, td { border: 1px solid #000; padding: 6px 8px; }
        th { background: #e0e0e0; text-align: center; font-weight: bold; vertical-align: middle; }
        
        .angka { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* Tanda Tangan */
        .container-ttd { width: 100%; margin-top: 40px; overflow: hidden; }
        .tanda-tangan { float: right; text-align: center; width: 250px; }

        .btn-print { 
            background: #007bff; color: white; border: none; padding: 10px 20px; 
            cursor: pointer; font-size: 14px; border-radius: 5px; margin-bottom: 20px; 
        }

        @media print {
            @page { margin: 1cm; size: A4; }
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            table { width: 100%; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print btn-print">🖨️ Cetak Laporan</button>

    <img src="../assets/kop.png" alt="Kop Surat" class="kop-surat">

    <h2>Laporan Rekapitulasi Stok Bulanan</h2>
    <p class="periode">Periode: <?php echo $nama_bulan[$bulan_pilih] . " " . $tahun_pilih; ?></p>

    <table>
        <thead>
            <tr>
                <th style="width:5%;">No</th> 
                <th>Nama Barang</th> 
                <th style="width:10%;">Satuan</th>
                <th style="width:12%;">Stok Awal</th> 
                <th style="width:12%;">Masuk (+)</th> 
                <th style="width:12%;">Keluar (-)</th> 
                <th style="width:12%;">Stok Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (!empty($laporan_data)) {
                $no = 1;
                foreach ($laporan_data as $row) { 
            ?>
            <tr>
                <td class="center"><?php echo $no++; ?></td>
                <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                <td class="center"><?php echo htmlspecialchars($row['satuan']); ?></td>
                <td class="angka"><?php echo fp($row['awal']); ?></td>
                <td class="angka"><?php echo fp($row['masuk']); ?></td>
                <td class="angka"><?php echo fp($row['keluar']); ?></td>
                <td class="angka bold"><?php echo fp($row['akhir']); ?></td>
            </tr>
            <?php 
                } 
            } else {
                echo "<tr><td colspan='7' class='center'>Tidak ada data barang.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="container-ttd">
        <div class="tanda-tangan">
            <p>Banjarbaru, <?php echo date('d') . ' ' . $nama_bulan[(int)date('m')] . ' ' . date('Y'); ?></p>
            <p>Mengetahui,</p>
            <br><br><br><br>
            <p><strong>( <?php echo isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin Gudang'; ?> )</strong></p>
            <p>Penanggung Jawab Gudang</p>
        </div>
    </div>

</body>
</html>