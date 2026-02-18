<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// (B) PASTIKAN ADA ID DI URL
if (isset($_GET['id'])) {

    $id_supplier_hapus = (int)$_GET['id'];

    // (C) CEK FOREIGN KEY (PENTING!)
    // Cek apakah supplier ini pernah dipakai di tabel_barang_masuk
    $query_cek = "SELECT COUNT(*) AS total FROM tabel_barang_masuk WHERE id_supplier = ?";
    $stmt_cek = mysqli_prepare($koneksi, $query_cek);
    mysqli_stmt_bind_param($stmt_cek, "i", $id_supplier_hapus);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);
    $total_pakai = mysqli_fetch_assoc($result_cek)['total'];
    mysqli_stmt_close($stmt_cek);

    if ($total_pakai > 0) {
        // JIKA SUDAH DIPAKAI, JANGAN HAPUS!
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Gagal menghapus! Supplier ini sudah memiliki riwayat transaksi di Barang Masuk.'
        ];
        mysqli_close($koneksi);
        header('Location: data_supplier.php');
        exit();
    }

    // (D) JIKA AMAN, LANJUT HAPUS (HARD DELETE)
    $query_delete = "DELETE FROM tabel_supplier WHERE id_supplier = ?";
    $stmt_delete = mysqli_prepare($koneksi, $query_delete);

    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $id_supplier_hapus);

        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Supplier berhasil dihapus.'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menghapus supplier: ' . mysqli_stmt_error($stmt_delete)];
        }
        mysqli_stmt_close($stmt_delete);
    } else {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query hapus.'];
    }

    // (E) TUTUP KONEKSI & REDIRECT
    mysqli_close($koneksi);
    header('Location: data_supplier.php');
    exit();

} else {
    header('Location: data_supplier.php');
    exit();
}
?>