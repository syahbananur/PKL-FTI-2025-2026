<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) HITUNG ULANG STOK KRITIS (Sama seperti di dashboard)
$barang_kritis = [];
$query_barang_all = "SELECT
                        b.id_barang, b.nama_barang, s.nama_satuan,
                        b.stok_awal, b.stok_minimum
                    FROM tabel_barang AS b
                    JOIN tabel_satuan_unit AS s ON b.id_satuan = s.id_satuan
                    WHERE b.status_barang = 'Aktif'"; // Ambil semua barang aktif dulu
$result_barang_all = mysqli_query($koneksi, $query_barang_all);

if ($result_barang_all) {
    while ($barang = mysqli_fetch_assoc($result_barang_all)) {
        $id_barang_current = $barang['id_barang'];
        $total_masuk = 0; $total_keluar = 0;

        // Hitung total masuk
        $query_total_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total_masuk FROM tabel_barang_masuk WHERE id_barang = ?";
        if($stmt_masuk = mysqli_prepare($koneksi, $query_total_masuk)){ /* ... (bind, execute, fetch, close) ... */
            mysqli_stmt_bind_param($stmt_masuk, "i", $id_barang_current); mysqli_stmt_execute($stmt_masuk);
            $res_m = mysqli_stmt_get_result($stmt_masuk); if($res_m) $total_masuk = mysqli_fetch_assoc($res_m)['total_masuk']; mysqli_stmt_close($stmt_masuk);
        }

        // Hitung total keluar
        $query_total_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total_keluar FROM tabel_barang_keluar WHERE id_barang = ?";
         if($stmt_keluar = mysqli_prepare($koneksi, $query_total_keluar)){ /* ... (bind, execute, fetch, close) ... */
            mysqli_stmt_bind_param($stmt_keluar, "i", $id_barang_current); mysqli_stmt_execute($stmt_keluar);
            $res_k = mysqli_stmt_get_result($stmt_keluar); if($res_k) $total_keluar = mysqli_fetch_assoc($res_k)['total_keluar']; mysqli_stmt_close($stmt_keluar);
         }

        $sisa_stok = $barang['stok_awal'] + $total_masuk - $total_keluar;

        // Cek kritis
        if ($sisa_stok <= $barang['stok_minimum'] && $barang['stok_minimum'] > 0) {
            $barang_kritis[] = [
                'nama_barang' => $barang['nama_barang'],
                'satuan' => $barang['nama_satuan'],
                'sisa_stok' => $sisa_stok,
                'stok_minimum' => $barang['stok_minimum']
            ];
        }
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
    <title>Cetak Laporan Stok Kritis</title>
    <style>
        /* CSS Dasar Layar & Print (Mirip cetak_sisa_stok) */
        body { font-family: 'Arial', sans-serif; margin: 20px; font-size: 11pt; color: #333; }
        .header-cetak { text-align: center; margin-bottom: 25px; border-bottom: 1px solid #999; padding-bottom: 10px; }
        .header-cetak h1 { font-size: 16pt; margin-bottom: 5px; color: #000; }
        .header-cetak p { font-size: 10pt; margin-top: 2px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 10pt; }
        th, td { border: 1px solid #666; padding: 8px 10px; text-align: left; vertical-align: top; }
        th { background-color: #e9ecef; font-weight: bold; color: #000; }
        td:nth-child(1), /* No */
        td:nth-child(4), /* Sisa Stok */
        td:nth-child(5) { /* Stok Min */
            text-align: right; width: 10%;
        }
        td:nth-child(4) { font-weight: bold; color: #dc3545; } /* Sisa stok kritis diberi warna */

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
            td:nth-child(4) { color: #000; } /* Warna stok kritis normal saat print */
        }
    </style>
</head>
<body>

    <div class="header-cetak">
        <h1>Laporan Stok Kritis</h1>
        <p>Dicetak pada: <?php echo date('d-m-Y H:i:s'); ?></p>
    </div>

    <?php if (!empty($barang_kritis)): ?>
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
                <?php $nomor_kritis = 1; ?>
                <?php foreach ($barang_kritis as $item_kritis): ?>
                    <tr>
                        <td><?php echo $nomor_kritis++; ?></td>
                        <td><?php echo htmlspecialchars($item_kritis['nama_barang']); ?></td>
                        <td><?php echo htmlspecialchars($item_kritis['satuan']); ?></td>
                        <td><strong><?php echo number_format($item_kritis['sisa_stok'], 2, ',', '.'); ?></strong></td>
                        <td><?php echo number_format($item_kritis['stok_minimum'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
         <p style="font-size: 9pt; text-align: center;">* Stok Kritis: Barang dengan Sisa Stok <= Stok Minimum (dan Stok Minimum > 0)</p>
    <?php else: ?>
        <p style="text-align: center; color: green; font-weight: bold;">✅ Tidak ada barang yang stoknya kritis saat ini.</p>
    <?php endif; ?>

    <button onclick="window.print()" class="print-button">🖨️ Print / Simpan PDF</button>

</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>