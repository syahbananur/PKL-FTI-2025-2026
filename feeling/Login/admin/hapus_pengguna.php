<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_GET['id'])) {
    $id_pengguna_hapus = (int)$_GET['id'];

    // VALIDASI PENTING: Jangan biarkan admin hapus diri sendiri!
    if ($id_pengguna_hapus == $_SESSION['id_pengguna']) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Anda tidak bisa menghapus akun Anda sendiri!'];
        mysqli_close($koneksi);
        header('Location: data_pengguna.php');
        exit();
    }

    // CEK FOREIGN KEY: Cek apakah user ini pernah mencatat transaksi
    $query_cek_masuk = "SELECT COUNT(*) AS total FROM tabel_barang_masuk WHERE id_pengguna_pencatat = ?";
    $stmt_cek_masuk = mysqli_prepare($koneksi, $query_cek_masuk);
    mysqli_stmt_bind_param($stmt_cek_masuk, "i", $id_pengguna_hapus);
    mysqli_stmt_execute($stmt_cek_masuk);
    $total_masuk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_masuk))['total'];
    mysqli_stmt_close($stmt_cek_masuk);

    $query_cek_keluar = "SELECT COUNT(*) AS total FROM tabel_barang_keluar WHERE id_pengguna_pencatat = ?";
    $stmt_cek_keluar = mysqli_prepare($koneksi, $query_cek_keluar);
    mysqli_stmt_bind_param($stmt_cek_keluar, "i", $id_pengguna_hapus);
    mysqli_stmt_execute($stmt_cek_keluar);
    $total_keluar = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_keluar))['total'];
    mysqli_stmt_close($stmt_cek_keluar);

    if ($total_masuk > 0 || $total_keluar > 0) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menghapus! Pengguna ini sudah memiliki riwayat transaksi.'];
        mysqli_close($koneksi);
        header('Location: data_pengguna.php');
        exit();
    }

    // JIKA AMAN, LANJUT HAPUS (HARD DELETE)
    $query_delete = "DELETE FROM tabel_pengguna WHERE id_pengguna = ?";
    $stmt_delete = mysqli_prepare($koneksi, $query_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id_pengguna_hapus);
        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menghapus: ' . mysqli_stmt_error($stmt_delete)];
        }
        mysqli_stmt_close($stmt_delete);
    } else {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query hapus.'];
    }
    
    mysqli_close($koneksi);
    header('Location: data_pengguna.php');
    exit();
} else {
    header('Location: data_pengguna.php');
    exit();
}
?>