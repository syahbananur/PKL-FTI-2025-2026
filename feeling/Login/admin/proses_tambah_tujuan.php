<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['simpan_tujuan'])) {
    $nama_tujuan = htmlspecialchars($_POST['nama_tujuan']);

    if (empty($nama_tujuan)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Nama tujuan tidak boleh kosong!'];
        header('Location: tambah_tujuan.php');
        exit();
    }

    $query = "INSERT INTO tabel_tujuan (nama_tujuan) VALUES (?)";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $nama_tujuan);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Tujuan baru berhasil ditambahkan!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyimpan: ' . mysqli_stmt_error($stmt)];
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