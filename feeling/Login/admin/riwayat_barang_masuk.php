<?php
session_start();
require_once '../config/config.php'; 

$alerts = $_SESSION['alerts'] ?? [];
unset($_SESSION['alerts']);

if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php'); exit();
}

// (B) AMBIL DATA FILTER (Jika ada)
$tanggal_akhir_default = date('Y-m-d');
$tanggal_mulai_default = date('Y-m-d', strtotime('-6 days', strtotime($tanggal_akhir_default)));
$tanggal_mulai = $_GET['tanggal_mulai'] ?? $tanggal_mulai_default;
$tanggal_akhir = $_GET['tanggal_akhir'] ?? $tanggal_akhir_default;

// (C) AMBIL DAFTAR USER (Untuk Dropdown Bulk Edit)
$query_users = "SELECT id_pengguna, nama_lengkap FROM tabel_pengguna ORDER BY nama_lengkap ASC";
$result_users = mysqli_query($koneksi, $query_users);

// (D) AMBIL DATA RIWAYAT
$query = "SELECT
            bm.id_masuk, bm.tanggal_masuk, b.nama_barang, s.nama_supplier,
            bm.jumlah_masuk, p.nama_lengkap AS nama_pencatat
          FROM tabel_barang_masuk AS bm
          JOIN tabel_barang AS b ON bm.id_barang = b.id_barang
          JOIN tabel_supplier AS s ON bm.id_supplier = s.id_supplier
          JOIN tabel_pengguna AS p ON bm.id_pengguna_pencatat = p.id_pengguna
          WHERE bm.tanggal_masuk BETWEEN ? AND ?
          ORDER BY bm.tanggal_masuk DESC, bm.id_masuk DESC";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_akhir);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Barang Masuk</title>
    <link rel="stylesheet" href="../assets/admin_style.css?v=1.5"> 
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .filter-form { margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; border: 1px solid #eee;}
        .filter-form input { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>

    <?php if (!empty($alerts)): ?>
    <div class="alert-box">
        <?php foreach ($alerts as $alert): ?>
            <div class="alert <?= htmlspecialchars($alert['type']); ?>">
                <span><?= htmlspecialchars($alert['message']); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dashboard-container" style="max-width: 1400px;">
        <header class="admin-header">
            <h1>Riwayat Barang Masuk</h1>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </header>

        <main>
            <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom: 20px;">Kembali</a>
            <a href="barang_masuk.php" class="btn btn-primary" style="margin-bottom: 20px;">Input Baru</a>
            
            <form action="riwayat_barang_masuk.php" method="GET" class="filter-form">
                <label>Dari:</label> <input type="date" name="tanggal_mulai" value="<?php echo $tanggal_mulai; ?>">
                <label>Sampai:</label> <input type="date" name="tanggal_akhir" value="<?php echo $tanggal_akhir; ?>">
                <button type="submit" class="btn btn-primary" style="padding: 8px 15px;">Filter</button>
                <a href="cetak_riwayat.php?jenis=masuk&tgl_awal=<?= $tanggal_mulai; ?>&tgl_akhir=<?= $tanggal_akhir; ?>" target="_blank" class="btn btn-secondary" style="padding: 8px 15px;">🖨️ Cetak</a>
            </form>

            <form action="proses_bulk_update_masuk.php" method="POST" onsubmit="return confirm('Yakin ubah pencatat untuk data terpilih?');">
            
                <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="bulk-action-box">
                    <i class='bx bx-edit-alt'></i>
                    <strong>Edit Massal:</strong> Ubah "Dicatat Oleh" menjadi:
                    <select name="id_user_baru" required>
                        <option value="">-- Pilih User Baru --</option>
                        <?php 
                        if ($result_users) {
                            mysqli_data_seek($result_users, 0);
                            while ($u = mysqli_fetch_assoc($result_users)) {
                                echo "<option value='{$u['id_pengguna']}'>{$u['nama_lengkap']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <button type="submit" name="update_bulk" class="bulk-btn">Update Terpilih</button>
                </div>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <input type="checkbox" id="selectAll">
                                <?php endif; ?>
                            </th>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Barang</th>
                            <th>Supplier</th>
                            <th>Jumlah</th>
                            <th>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                <input type="checkbox" name="ids[]" value="<?php echo $row['id_masuk']; ?>" class="select-item">
                                <?php endif; ?>
                            </td>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['tanggal_masuk'])); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_barang']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama_supplier']); ?></td>
                            <td><?php echo number_format($row['jumlah_masuk'], 2, ',', '.'); ?></td>
                            <td style="font-weight: bold; color: #555;"><?php echo htmlspecialchars($row['nama_pencatat']); ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            
            </form>
        </main>
    </div>

    <script>
        const alertBox = document.querySelector('.alert-box');
        if (alertBox) {
            setTimeout(() => { alertBox.classList.add('show'); }, 50);
            setTimeout(() => {
                alertBox.classList.remove('show');
                setTimeout(() => { if(alertBox.parentNode) alertBox.parentNode.removeChild(alertBox); }, 500);
            }, 5500);
        }

        // SCRIPT SELECT ALL
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.select-item');
                checkboxes.forEach(cb => cb.checked = this.checked);
            });
        }
    </script>
</body>
</html>
<?php if(isset($stmt)) mysqli_stmt_close($stmt); mysqli_close($koneksi); ?>