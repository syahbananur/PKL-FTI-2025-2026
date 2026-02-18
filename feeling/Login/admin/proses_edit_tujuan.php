<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['update_tujuan'])) {
    $id_tujuan = (int)$_POST['id_tujuan'];
    $nama_tujuan = htmlspecialchars($_POST['nama_tujuan']);

    if (empty($nama_tujuan) || empty($id_tujuan)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Nama tujuan dan ID tidak boleh kosong!'];
        header('Location: data_tujuan.php');
        exit();
    }

    $query = "UPDATE tabel_tujuan SET nama_tujuan = ? WHERE id_tujuan = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "si", $nama_tujuan, $id_tujuan);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data tujuan berhasil diperbarui!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal memperbarui: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)];
    }

    mysqli_close($koneksi);
    header('Location: data_tujuan.php');
    exit();
}
?>