<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// (B) Cek login
if (!isset($_SESSION['id_pengguna'])) {
    // Redirect ke login jika mencoba akses langsung
    header('Location: ../index.php');
    exit();
}

// (C) HITUNG SISA STOK (Sama seperti di dashboard)
$stok_barang = [];
$query_barang_all = "SELECT
                        b.id_barang, b.nama_barang, s.nama_satuan,
                        b.stok_awal, b.stok_minimum
                    FROM tabel_barang AS b
                    JOIN tabel_satuan_unit AS s ON b.id_satuan = s.id_satuan
                    WHERE b.status_barang = 'Aktif'
                    ORDER BY b.nama_barang ASC";
$result_barang_all = mysqli_query($koneksi, $query_barang_all);

if ($result_barang_all) {
    while ($barang = mysqli_fetch_assoc($result_barang_all)) {
        $id_barang_current = $barang['id_barang'];
        $total_masuk = 0; $total_keluar = 0;

        // Hitung total masuk
        $query_total_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total_masuk FROM tabel_barang_masuk WHERE id_barang = ?";
        if($stmt_masuk = mysqli_prepare($koneksi, $query_total_masuk)){ /* ... (bind, execute, fetch, close) ... */ 
            mysqli_stmt_bind_param($stmt_masuk, "i", $id_barang_current);
            mysqli_stmt_execute($stmt_masuk);
            $result_total_masuk = mysqli_stmt_get_result($stmt_masuk);
            if($result_total_masuk) $total_masuk = mysqli_fetch_assoc($result_total_masuk)['total_masuk'];
            mysqli_stmt_close($stmt_masuk);
        }

        // Hitung total keluar
        $query_total_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total_keluar FROM tabel_barang_keluar WHERE id_barang = ?";
         if($stmt_keluar = mysqli_prepare($koneksi, $query_total_keluar)){ /* ... (bind, execute, fetch, close) ... */ 
            mysqli_stmt_bind_param($stmt_keluar, "i", $id_barang_current);
            mysqli_stmt_execute($stmt_keluar);
            $result_total_keluar = mysqli_stmt_get_result($stmt_keluar);
             if($result_total_keluar) $total_keluar = mysqli_fetch_assoc($result_total_keluar)['total_keluar'];
            mysqli_stmt_close($stmt_keluar);
         }

        $sisa_stok = $barang['stok_awal'] + $total_masuk - $total_keluar;

        $stok_barang[] = [
            'nama_barang' => $barang['nama_barang'],
            'satuan' => $barang['nama_satuan'],
            'sisa_stok' => $sisa_stok,
            'stok_minimum' => $barang['stok_minimum']
        ];
    }
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Sisa Stok</title>
    <style>
        /* == CSS DASAR UNTUK LAYAR == */
        body { font-family: sans-serif; margin: 20px; }
        h1 { text-align: center; border-bottom: 1px solid #ccc; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .print-button { display: block; width: 150px; margin: 20px auto; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 5px; text-align: center; cursor: pointer; }

        /* == (D) CSS KHUSUS UNTUK PRINT == */
        @media print {
            body { margin: 0; font-size: 10pt; } /* Hapus margin, perkecil font */
            h1 { font-size: 16pt; margin-bottom: 15px; }
            table { margin-bottom: 10px; }
            th, td { padding: 5px; }
            .print-button { display: none; } /* Sembunyikan tombol print saat mencetak */
            a { text-decoration: none; color: black; } /* Hapus link jika ada */
            /* Anda bisa tambahkan style lain di sini */
        }
    </style>
</head>
<body>

    <h1>Laporan Sisa Stok</h1>
    <p>Tanggal Cetak: <?php echo date('d-m-Y H:i:s'); ?></p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Sisa Stok</th>
                <th>Stok Minimum</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($stok_barang)): ?>
                <?php $nomor_stok = 1; ?>
                <?php foreach ($stok_barang as $item): ?>
                    <tr>
                        <td><?php echo $nomor_stok++; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                        <td><?php echo htmlspecialchars($item['satuan']); ?></td>
                        <td><strong><?php echo $item['sisa_stok']; ?></strong></td>
                        <td><?php echo $item['stok_minimum']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">Belum ada data barang aktif.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <button onclick="window.print()" class="print-button">🖨️ Print Halaman Ini</button>

</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>