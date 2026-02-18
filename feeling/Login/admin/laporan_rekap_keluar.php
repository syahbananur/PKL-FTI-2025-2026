<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php';

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) TENTUKAN BULAN & TAHUN (Menggunakan GET agar URL bisa dicopy/refresh)
$bulan_pilih = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// Array Nama Bulan
$nama_bulan = ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

// (C) SIAPKAN DATA LAPORAN (QUERY GROUP BY KHUSUS KELUAR)
$laporan_data = [];
$query_rekap = "SELECT
                    b.nama_barang,
                    s.nama_satuan,
                    SUM(bk.jumlah_keluar) AS total_bulanan
                FROM
                    tabel_barang_keluar AS bk
                JOIN
                    tabel_barang AS b ON bk.id_barang = b.id_barang
                JOIN
                    tabel_satuan_unit AS s ON b.id_satuan = s.id_satuan
                WHERE
                    MONTH(bk.tanggal_keluar) = ? AND YEAR(bk.tanggal_keluar) = ?
                GROUP BY
                    bk.id_barang, b.nama_barang, s.nama_satuan
                ORDER BY
                    total_bulanan DESC, b.nama_barang ASC";

if ($stmt = mysqli_prepare($koneksi, $query_rekap)) {
    mysqli_stmt_bind_param($stmt, "ii", $bulan_pilih, $tahun_pilih);
    mysqli_stmt_execute($stmt);
    $result_rekap = mysqli_stmt_get_result($stmt);
    if ($result_rekap) {
        while ($row = mysqli_fetch_assoc($result_rekap)) {
            $laporan_data[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Barang Keluar</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    
    <style>
        /* --- STYLE KONSISTEN --- */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            margin: 0; padding: 20px; color: #333;
        }

        .dashboard-container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            margin: 0 auto;
            max-width: 1000px;
            border-top: 5px solid #d9534f; /* Aksen Merah Kopi */
        }

        /* HEADER */
        .admin-header {
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;
        }
        h1 { margin: 0; font-size: 24px; color: #333; }

        /* TOMBOL */
        .btn {
            display: inline-block; padding: 8px 15px; text-decoration: none; border-radius: 4px;
            color: white; font-weight: bold; font-size: 14px; cursor: pointer; border: none;
            transition: 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-secondary { background: #6c757d; color: #fff; }
        .btn-danger { background: #d9534f; }
        .btn-primary { background: #007bff; }
        
        /* FILTER BOX */
        .filter-box {
            background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #ddd;
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        .filter-box select, .filter-box input {
            padding: 8px; border: 1px solid #ccc; border-radius: 4px;
        }

        /* TABEL */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f1f1f1; font-weight: bold; }
        tr:hover { background-color: #f9f9f9; }
        
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        
        <header class="admin-header">
            <h1>Rekap Laporan Barang Keluar</h1>
            <a href="../logout.php" class="btn btn-danger">Logout</a>
        </header>

        <main>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">&laquo; Kembali ke Dashboard</a>
            
            <form action="" method="GET" class="filter-box">
                <label for="bulan">Bulan:</label>
                <select name="bulan" id="bulan">
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == $bulan_pilih) ? 'selected' : ''; ?>>
                            <?php echo $nama_bulan[$i]; ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <label for="tahun">Tahun:</label>
                <input type="number" name="tahun" id="tahun" value="<?php echo $tahun_pilih; ?>" min="2020" max="<?php echo date('Y'); ?>" style="width: 80px;">
                
                <button type="submit" class="btn btn-primary">Tampilkan</button>
            </form>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; margin-top: 30px;">
                <h3 style="margin: 0;">Total Keluar: <?php echo $nama_bulan[$bulan_pilih] . ' ' . $tahun_pilih; ?></h3>
                
                <a href="cetak_rekap.php?jenis=keluar&bulan=<?= $bulan_pilih; ?>&tahun=<?= $tahun_pilih; ?>" target="_blank" class="btn btn-secondary">
                    🖨️ Cetak Rekap
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th class="text-right">Total Keluar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1; 
                    if (!empty($laporan_data)):
                        foreach ($laporan_data as $item): 
                            // Format Angka Pintar
                            $jml = $item['total_bulanan'];
                            $tampil = (floor($jml) == $jml) ? number_format($jml, 0, ',', '.') : rtrim(number_format($jml, 2, ',', '.'), '0');
                    ?>
                    <tr>
                        <td class="text-center"><?php echo $no++; ?></td>
                        <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                        <td><?php echo htmlspecialchars($item['nama_satuan']); ?></td>
                        <td class="text-right bold"><?php echo $tampil; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center" style="padding: 20px;">Tidak ada data barang keluar untuk periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>

</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>