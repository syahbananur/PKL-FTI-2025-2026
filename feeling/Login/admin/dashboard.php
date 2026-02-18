<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; 

// Ambil alert jika ada & hapus dari session
$alerts = $_SESSION['alerts'] ?? []; 
unset($_SESSION['alerts']); 

// (B) Cek login
if (!isset($_SESSION['id_pengguna'])) {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Anda harus login terlebih dahulu!'];
    header('Location: ../index.php'); 
    exit();
}

// (C) Ambil data user
$nama_user_login = $_SESSION['name'] ?? 'Pengguna';
$role_user_login = $_SESSION['role'] ?? 'user';

// === HITUNG SISA STOK ===
$barang_kritis = []; // Tetap hitung ini untuk warning

// === TAMBAHAN: DATA TRANSAKSI HARI INI (UNTUK DASHBOARD) ===
$hari_ini = date('Y-m-d');

// 1. Ambil Barang Masuk Hari Ini
$masuk_hari_ini = [];
$q_masuk_now = "SELECT b.nama_barang, s.nama_satuan, bm.jumlah_masuk, p.nama_lengkap 
                FROM tabel_barang_masuk bm 
                JOIN tabel_barang b ON bm.id_barang = b.id_barang 
                JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
                JOIN tabel_pengguna p ON bm.id_pengguna_pencatat = p.id_pengguna
                WHERE bm.tanggal_masuk = '$hari_ini' 
                ORDER BY bm.id_masuk DESC";
$res_masuk_now = mysqli_query($koneksi, $q_masuk_now);
while ($row = mysqli_fetch_assoc($res_masuk_now)) { $masuk_hari_ini[] = $row; }

// 2. Ambil Barang Keluar Hari Ini
$keluar_hari_ini = [];
$q_keluar_now = "SELECT b.nama_barang, s.nama_satuan, bk.jumlah_keluar, t.nama_tujuan 
                 FROM tabel_barang_keluar bk 
                 JOIN tabel_barang b ON bk.id_barang = b.id_barang 
                 JOIN tabel_satuan_unit s ON b.id_satuan = s.id_satuan
                 JOIN tabel_tujuan t ON bk.id_tujuan = t.id_tujuan
                 WHERE bk.tanggal_keluar = '$hari_ini' 
                 ORDER BY bk.id_keluar DESC";
$res_keluar_now = mysqli_query($koneksi, $q_keluar_now);
while ($row = mysqli_fetch_assoc($res_keluar_now)) { $keluar_hari_ini[] = $row; }
// === AKHIR TAMBAHAN QUERY ===

// 1. Ambil semua barang aktif
$query_barang_all = "SELECT 
                        b.id_barang, b.nama_barang, 
                        b.kategori_barang, 
                        s.nama_satuan, b.stok_awal, b.stok_minimum 
                    FROM tabel_barang AS b 
                    JOIN tabel_satuan_unit AS s ON b.id_satuan = s.id_satuan 
                    WHERE b.status_barang = 'Aktif'
                    ORDER BY b.kategori_barang ASC, b.nama_barang ASC";
$result_barang_all = mysqli_query($koneksi, $query_barang_all);

if (!$result_barang_all) {
     echo "Error query barang: " . mysqli_error($koneksi);
}
// === AKHIR HITUNG STOK ===
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Stok Kopi</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.8"> <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    </head>
<body>

    <?php if (!empty($alerts)) :?>
    <div class="alert-box">
        <?php foreach ($alerts as $alert) : ?>
        <div class="alert <?= htmlspecialchars($alert['type']); ?>">
            <i class='bx <?= $alert['type'] === 'success' ? 'bxs-check-circle' : 'bxs-x-circle'; ?>'></i>
            <span><?= htmlspecialchars($alert['message']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <header class="admin-header">
            <a href="#" class="logo"><img src="../assets/fel.png" alt="aa"></a>
            <h1>Halo, <?php echo htmlspecialchars($nama_user_login); ?>!</h1>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </header>
        <main>
            
            <?php if ($result_barang_all): // Cek dulu query barang berhasil ?>
                <?php
                    // Hitung sisa stok HANYA untuk cek kritis (perlu loop cepat)
                    $barang_kritis_cek = [];
                    mysqli_data_seek($result_barang_all, 0); // Balikkan pointer data ke awal
                    while ($barang_cek = mysqli_fetch_assoc($result_barang_all)) {
                        $id_cek = $barang_cek['id_barang'];
                        $total_masuk_cek = 0; $total_keluar_cek = 0;
                        
                        $q_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total_masuk FROM tabel_barang_masuk WHERE id_barang = ?";
                        if($s_masuk = mysqli_prepare($koneksi, $q_masuk)){
                            mysqli_stmt_bind_param($s_masuk, "i", $id_cek); mysqli_stmt_execute($s_masuk);
                            $res_m = mysqli_stmt_get_result($s_masuk); if($res_m) $total_masuk_cek = mysqli_fetch_assoc($res_m)['total_masuk']; mysqli_stmt_close($s_masuk);
                        }
                        $q_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total_keluar FROM tabel_barang_keluar WHERE id_barang = ?";
                         if($s_keluar = mysqli_prepare($koneksi, $q_keluar)){
                            mysqli_stmt_bind_param($s_keluar, "i", $id_cek); mysqli_stmt_execute($s_keluar);
                            $res_k = mysqli_stmt_get_result($s_keluar); if($res_k) $total_keluar_cek = mysqli_fetch_assoc($res_k)['total_keluar']; mysqli_stmt_close($s_keluar);
                         }
                        $sisa_stok_cek = $barang_cek['stok_awal'] + $total_masuk_cek - $total_keluar_cek;

                        if ($sisa_stok_cek <= $barang_cek['stok_minimum'] && $barang_cek['stok_minimum'] > 0) {
                            $barang_kritis[] = true; // Cukup tandai ada
                        }
                    }
                    mysqli_data_seek($result_barang_all, 0); // Kembalikan lagi pointer data ke awal
                ?>
            <?php endif; ?>
            
            <?php if (!empty($barang_kritis)): ?>
                <div class="alert alert-error" style="background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                    <i class='bx bxs-x-circle' style="font-size: 1.5em;"></i>
                    <span style="font-size: 1.1em;"><strong>Perhatian!</strong> Ada <strong><?php echo count($barang_kritis); ?></strong> barang yang stoknya kritis.</span>
                </div>
            <?php endif; ?>

            <p>Anda login sebagai: <strong><?php echo htmlspecialchars($role_user_login); ?></strong>.</p>
            <hr>
            <nav class="admin-nav">
                <h2>Menu Navigasi</h2>
                <ul>
                    <!-- <li><a href="dashboard.php">Dashboard</a></li> -->
                    <li><a href="data_barang.php">Manajemen Barang</a></li>
                    <li><a href="barang_masuk.php">Barang Masuk</a></li>
                    <li><a href="barang_keluar.php">Barang Keluar</a></li>
                    <li><a href="riwayat_barang_masuk.php">Riwayat Masuk</a></li>
                    <li><a href="riwayat_barang_keluar.php">Riwayat Keluar</a></li>
                    <!-- <li><a href="laporan_bulanan.php" style="background-color: #198754;">Laporan Bulanan</a></li> -->
                    <li><a href="laporan_rekap_masuk.php" style="background-color: #198754;">Rekap Masuk</a></li>
                    <li><a href="laporan_rekap_keluar.php" style="background-color: #198754;">Rekap Keluar</a></li>
                    <!-- <li><a href="laporan_harian.php" style="background-color: #198754;">Kartu Stok Harian</a></li> -->
                    <!-- <li><a href="laporan_stok_per_tanggal.php" style="background-color: #198754;">Cek Stok per Tanggal</a></li> -->
                    <?php 
                    if ($role_user_login == 'admin') {
                        echo '<li><a href="data_pengguna.php">Manajemen User</a></li>';
                        echo '<li><a href="data_supplier.php">Manajemen Supplier</a></li>';
                        echo '<li><a href="data_tujuan.php">Manajemen Tujuan</a></li>';
                    }
                    ?>
                </ul>
            </nav>
            <hr>

            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 40px;">
                
                <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #d4edda; border-radius: 8px; overflow: hidden;">
                    <div style="background: #d4edda; color: #155724; padding: 10px 15px; font-weight: bold; border-bottom: 1px solid #c3e6cb;">
                        📥 Masuk Hari Ini (<?php echo date('d-m-Y'); ?>)
                    </div>
                    <table style="margin-top: 0; border: none;">
                        <?php if (!empty($masuk_hari_ini)): ?>
                            <?php foreach ($masuk_hari_ini as $m): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px 15px; border: none;"><?php echo htmlspecialchars($m['nama_barang']); ?></td>
                                    <td style="padding: 8px 15px; border: none; text-align: right; font-weight: bold; color: #155724;">
                                        +<?php echo number_format($m['jumlah_masuk'], 2, ',', '.'); ?> <?php echo $m['nama_satuan']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td style="padding: 15px; text-align: center; color: #777; border: none;">Belum ada barang masuk.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

                <div style="flex: 1; min-width: 300px; background: #fff; border: 1px solid #f8d7da; border-radius: 8px; overflow: hidden;">
                    <div style="background: #f8d7da; color: #721c24; padding: 10px 15px; font-weight: bold; border-bottom: 1px solid #f5c6cb;">
                        📤 Keluar Hari Ini (<?php echo date('d-m-Y'); ?>)
                    </div>
                    <table style="margin-top: 0; border: none;">
                        <?php if (!empty($keluar_hari_ini)): ?>
                            <?php foreach ($keluar_hari_ini as $k): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 8px 15px; border: none;">
                                        <?php echo htmlspecialchars($k['nama_barang']); ?>
                                        <br><small style="color: #777;">Ke: <?php echo htmlspecialchars($k['nama_tujuan']); ?></small>
                                    </td>
                                    <td style="padding: 8px 15px; border: none; text-align: right; font-weight: bold; color: #721c24;">
                                        -<?php echo number_format($k['jumlah_keluar'], 2, ',', '.'); ?> <?php echo $k['nama_satuan']; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td style="padding: 15px; text-align: center; color: #777; border: none;">Belum ada barang keluar.</td></tr>
                        <?php endif; ?>
                    </table>
                </div>

            </div>
            <!-- ```

### Hasilnya
Di atas tabel "Laporan Sisa Stok" yang besar, sekarang akan ada **dua kotak ringkasan** berdampingan (Hijau & Merah) yang menunjukkan transaksi *khusus hari ini*.

Ini sangat membantu user untuk melihat "Apa yang baru saja terjadi?" tanpa harus menggulir laporan besar.

**Catatan:** Saya tidak menampilkan "Sisa Stok Final Hari Ini" di kotak kecil ini karena angka itu **SUDAH ADA** di tabel besar "Laporan Sisa Stok Saat Ini" tepat di bawahnya (karena laporan stok itu *real-time*). Jadi tidak perlu duplikasi data.
 -->
            <section class="laporan-stok">
                <div class="section-header">
                    <h2>Laporan Sisa Stok Saat Ini</h2>
                    <a href="cetak_sisa_stok.php" target="_blank" class="print-btn">🖨️ Cetak</a>
                </div>
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
                        <?php 
                        if ($result_barang_all && mysqli_num_rows($result_barang_all) > 0):
                            
                            $nomor_stok = 1;
                            $kategori_sekarang = null; 

                            mysqli_data_seek($result_barang_all, 0); // Pastikan pointer kembali ke awal
                            while ($barang = mysqli_fetch_assoc($result_barang_all)) { 
                                // Hitung sisa stok
                                $id_barang_current = $barang['id_barang'];
                                $total_masuk = 0; $total_keluar = 0;
                                
                                $query_total_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total_masuk FROM tabel_barang_masuk WHERE id_barang = ?";
                                if($stmt_masuk = mysqli_prepare($koneksi, $query_total_masuk)){
                                    mysqli_stmt_bind_param($stmt_masuk, "i", $id_barang_current); mysqli_stmt_execute($stmt_masuk);
                                    $res_m = mysqli_stmt_get_result($stmt_masuk); if($res_m) $total_masuk = mysqli_fetch_assoc($res_m)['total_masuk']; mysqli_stmt_close($stmt_masuk);
                                }
                                $query_total_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total_keluar FROM tabel_barang_keluar WHERE id_barang = ?";
                                 if($stmt_keluar = mysqli_prepare($koneksi, $query_total_keluar)){
                                    mysqli_stmt_bind_param($stmt_keluar, "i", $id_barang_current); mysqli_stmt_execute($stmt_keluar);
                                    $res_k = mysqli_stmt_get_result($stmt_keluar); if($res_k) $total_keluar = mysqli_fetch_assoc($res_k)['total_keluar']; mysqli_stmt_close($stmt_keluar);
                                 }
                                $sisa_stok = $barang['stok_awal'] + $total_masuk - $total_keluar;

                                // **(PERBAIKAN: Gunakan style inline untuk sub-bab)**
                                if ($barang['kategori_barang'] !== $kategori_sekarang) {
                                    $style_tr = "background-color: #6c757d; color: white; font-weight: 600; font-size: 1.1em;";
                                    $style_td = "padding: 10px 15px; text-align: center;"; // Teks di-set center

                                    echo '<tr style="' . $style_tr . '">';
                                    echo '    <td colspan="5" style="' . $style_td . '">' . htmlspecialchars($barang['kategori_barang']) . '</td>'; // Colspan 5
                                    echo '</tr>';
                                    $kategori_sekarang = $barang['kategori_barang']; // Update pelacak
                                }
                                // **(AKHIR PERBAIKAN)**

                                // Cek kritis
                                $class_kritis = ($sisa_stok <= $barang['stok_minimum'] && $barang['stok_minimum'] > 0) ? 'stok-kritis' : '';
                                
                                // Cetak baris data barang
                                echo '<tr class="' . $class_kritis . '">';
                                echo '<td>' . $nomor_stok++ . '</td>';
                                echo '<td>' . htmlspecialchars($barang['nama_barang']) . '</td>';
                                echo '<td>' . htmlspecialchars($barang['nama_satuan']) . '</td>';
                                echo '<td><strong>' . number_format($sisa_stok, 2, ',', '.') . '</strong></td>';
                                echo '<td>' . number_format($barang['stok_minimum'], 2, ',', '.') . '</td>';
                                echo '</tr>';
                            } // Akhir while
                        
                        else: 
                            echo '<tr><td colspan="5" style="text-align: center;">Belum ada data barang aktif.</td></tr>';
                        endif; 
                        ?>
                    </tbody>
                </table>
            </section>
            
            </main>
    </div>

    <script>
        const alertBox = document.querySelector('.alert-box');
        if (alertBox) {
            setTimeout(() => { alertBox.classList.add('show'); }, 50);
            setTimeout(() => {
                alertBox.classList.remove('show');
                setTimeout(() => { if(alertBox.parentNode) { alertBox.parentNode.removeChild(alertBox); } }, 500);
            }, 5500);
        }
    </script>
</body>
</html>
<?php if(isset($koneksi)) mysqli_close($koneksi); ?>