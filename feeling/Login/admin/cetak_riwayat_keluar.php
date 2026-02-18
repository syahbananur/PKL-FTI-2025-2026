<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) AMBIL DATA RIWAYAT BARANG KELUAR
// PERBAIKAN QUERY: Gunakan tabel_tujuan
$query = "SELECT
            bk.id_keluar,
            bk.tanggal_keluar,
            b.nama_barang,
            t.nama_tujuan, /* <-- AMBIL NAMA TUJUAN DARI TABEL BARU */
            bk.jumlah_keluar,
            bk.keterangan_darurat, /* <-- AMBIL KETERANGAN */
            p.nama_lengkap AS nama_pencatat
          FROM
            tabel_barang_keluar AS bk
          JOIN
            tabel_barang AS b ON bk.id_barang = b.id_barang
          JOIN
            tabel_tujuan AS t ON bk.id_tujuan = t.id_tujuan /* <-- JOIN KE TABEL TUJUAN */
          JOIN
            tabel_pengguna AS p ON bk.id_pengguna_pencatat = p.id_pengguna
          ORDER BY
            bk.tanggal_keluar DESC, bk.id_keluar DESC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    die("Query gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Riwayat Barang Keluar</title>
    <style>
        /* CSS Dasar Layar & Print */
        body { font-family: 'Arial', sans-serif; margin: 20px; font-size: 11pt; color: #333;}
        .header-cetak { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #999; padding-bottom: 10px; }
        .header-cetak h1 { font-size: 16pt; margin-bottom: 5px; color: #000; }
        .header-cetak p { font-size: 10pt; margin-top: 2px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10pt; }
        th, td { border: 1px solid #666; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background-color: #e9ecef; font-weight: bold; color: #000; }
         td:nth-child(1), /* No */
        td:nth-child(5) { /* Jumlah */
            text-align: center;
            width: 5%;
        }
         td:nth-child(2) { width: 15%; } /* Tanggal */
         td:nth-child(7) { width: 15%; } /* Pencatat */
         td:nth-child(4) { width: 20%; } /* Tujuan */


        .print-button { display: block; width: 180px; margin: 30px auto; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 5px; text-align: center; cursor: pointer; font-size: 11pt; font-weight: bold; }

        /* CSS KHUSUS PRINT */
        @media print {
            body { margin: 1cm; font-size: 9pt; color: #000; }
            .header-cetak { margin-bottom: 15px; border-bottom: 1px solid #000; }
            .header-cetak h1 { font-size: 14pt; }
            .header-cetak p { font-size: 8pt; }
            .print-button { display: none; }
            table { margin-bottom: 10px; font-size: 8pt; }
            th, td { padding: 5px 8px; border: 1px solid #333; }
            th { background-color: #f0f0f0; }
        }
    </style>
</head>
<body>

    <div class="header-cetak">
        <h1>Riwayat Barang Keluar (Distribusi)</h1>
        <p>Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

    <?php if (mysqli_num_rows($result) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Keluar</th>
                    <th>Nama Barang</th>
                    <th>Tujuan</th> <th>Jumlah</th>
                    <th>Keterangan</th> <th>Dicatat Oleh</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $nomor = 1;
                while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <tr>
                    <td><?php echo $nomor++; ?></td>
                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_keluar'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_tujuan']); // Tampilkan nama tujuan ?></td>
                    <td><?php echo number_format($row['jumlah_keluar'], 2, ',', '.'); ?></td>
                    <td><?php echo htmlspecialchars($row['keterangan_darurat']); // Tampilkan keterangan ?></td>
                    <td><?php echo htmlspecialchars($row['nama_pencatat']); ?></td>
                </tr>
                <?php
                } // Akhir while loop
                ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align: center;">Belum ada riwayat barang keluar.</p>
    <?php endif; ?>

    <button onclick="window.print()" class="print-button">🖨️ Print / Simpan PDF</button>

</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>