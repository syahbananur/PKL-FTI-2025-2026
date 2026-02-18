<?php
session_start();
require_once '../config/config.php';

// Hanya Admin yang boleh akses
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses ditolak!'];
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['update_bulk'])) {
    $id_user_baru = (int)$_POST['id_user_baru'];
    $selected_ids = $_POST['ids'] ?? []; // Array ID yang dicentang

    // Validasi
    if (empty($selected_ids)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Tidak ada transaksi yang dipilih!'];
        header('Location: riwayat_barang_masuk.php');
        exit();
    }
    if (empty($id_user_baru)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Pilih user pengganti terlebih dahulu!'];
        header('Location: riwayat_barang_masuk.php');
        exit();
    }

    // Proses Update Loop
    $query = "UPDATE tabel_barang_masuk SET id_pengguna_pencatat = ? WHERE id_masuk = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    $sukses = 0;
    if ($stmt) {
        foreach ($selected_ids as $id_transaksi) {
            mysqli_stmt_bind_param($stmt, "ii", $id_user_baru, $id_transaksi);
            if (mysqli_stmt_execute($stmt)) {
                $sukses++;
            }
        }
        mysqli_stmt_close($stmt);
        $_SESSION['alerts'][] = ['type' => 'success', 'message' => "Berhasil mengubah pencatat untuk $sukses transaksi."];
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query.'];
    }

    header('Location: riwayat_barang_masuk.php');
    exit();
}
?>