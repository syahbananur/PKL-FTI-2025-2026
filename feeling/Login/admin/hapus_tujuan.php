<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id'])) {
    $id_tujuan_hapus = (int)$_GET['id'];

    // Cek Foreign Key di tabel_barang_keluar
    $query_cek = "SELECT COUNT(*) AS total FROM tabel_barang_keluar WHERE id_tujuan = ?";
    $stmt_cek = mysqli_prepare($koneksi, $query_cek);
    mysqli_stmt_bind_param($stmt_cek, "i", $id_tujuan_hapus);
    mysqli_stmt_execute($stmt_cek);
    $total_pakai = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek))['total'];
    mysqli_stmt_close($stmt_cek);

    if ($total_pakai > 0) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menghapus! Tujuan ini sudah memiliki riwayat transaksi di Barang Keluar.'];
        mysqli_close($koneksi);
        header('Location: data_tujuan.php');
        exit();
    }

    // Jika aman, lanjut Hapus
    $query_delete = "DELETE FROM tabel_tujuan WHERE id_tujuan = ?";
    $stmt_delete = mysqli_prepare($koneksi, $query_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id_tujuan_hapus);
        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Tujuan berhasil dihapus.'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menghapus: ' . mysqli_stmt_error($stmt_delete)];
        }
        mysqli_stmt_close($stmt_delete);
    } else {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query hapus.'];
    }

    mysqli_close($koneksi);
    header('Location: data_tujuan.php');
    exit();
}
?>