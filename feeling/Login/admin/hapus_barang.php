<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) PASTIKAN ADA ID BARANG YANG DIKIRIM (via GET)
if (isset($_GET['id'])) {

    // (C) AMBIL ID BARANG DAN SANITASI
    $id_barang_hapus = (int)$_GET['id']; // Pastikan ID adalah angka

    // (D) PERIKSA APAKAH BARANG TERKAIT DENGAN TRANSAKSI
    $query_cek_masuk = "SELECT COUNT(*) AS total FROM tabel_barang_masuk WHERE id_barang = ?";
    $stmt_cek_masuk = mysqli_prepare($koneksi, $query_cek_masuk);
    mysqli_stmt_bind_param($stmt_cek_masuk, "i", $id_barang_hapus);
    mysqli_stmt_execute($stmt_cek_masuk);
    $result_cek_masuk = mysqli_stmt_get_result($stmt_cek_masuk);
    $row_cek_masuk = mysqli_fetch_assoc($result_cek_masuk);
    mysqli_stmt_close($stmt_cek_masuk);

    $query_cek_keluar = "SELECT COUNT(*) AS total FROM tabel_barang_keluar WHERE id_barang = ?";
    $stmt_cek_keluar = mysqli_prepare($koneksi, $query_cek_keluar);
    mysqli_stmt_bind_param($stmt_cek_keluar, "i", $id_barang_hapus);
    mysqli_stmt_execute($stmt_cek_keluar);
    $result_cek_keluar = mysqli_stmt_get_result($stmt_cek_keluar);
    $row_cek_keluar = mysqli_fetch_assoc($result_cek_keluar);
    mysqli_stmt_close($stmt_cek_keluar);

    if ($row_cek_masuk['total'] > 0 || $row_cek_keluar['total'] > 0) {
        // ---- SOLUSI SOFT DELETE ----
        $query_soft_delete = "UPDATE tabel_barang SET status_barang = 'Non-Aktif' WHERE id_barang = ?";
        $stmt_soft_delete = mysqli_prepare($koneksi, $query_soft_delete);
        mysqli_stmt_bind_param($stmt_soft_delete, "i", $id_barang_hapus);
        if (mysqli_stmt_execute($stmt_soft_delete)) {
            // echo "Barang dinonaktifkan..."; // Pesan opsional
        } else {
            // echo "Gagal menonaktifkan..."; // Pesan opsional
        }
        mysqli_stmt_close($stmt_soft_delete);

        // PERBAIKAN: Tutup koneksi sebelum redirect
        mysqli_close($koneksi);
        header('Location: data_barang.php');
        exit();
        // ---- AKHIR SOLUSI SOFT DELETE ----

    } else {
        // ---- PROSES HARD DELETE ----
        $query_delete = "DELETE FROM tabel_barang WHERE id_barang = ?";
        $stmt_delete = mysqli_prepare($koneksi, $query_delete);

        if ($stmt_delete) {
            mysqli_stmt_bind_param($stmt_delete, "i", $id_barang_hapus);
            if (mysqli_stmt_execute($stmt_delete)) {
                // echo "Data barang berhasil dihapus!"; // Pesan opsional
            } else {
                // echo "Gagal menghapus..."; // Pesan opsional
            }
            mysqli_stmt_close($stmt_delete);
        } else {
            // echo "Gagal menyiapkan query hapus..."; // Pesan opsional
        }

        // PERBAIKAN: Tutup koneksi sebelum redirect
        mysqli_close($koneksi);
        header('Location: data_barang.php');
        exit();
         // ---- AKHIR PROSES HARD DELETE ----
    }

} else {
    // Jika ID tidak ada
    // echo "ID Barang tidak ditemukan."; // Pesan opsional

    // PERBAIKAN: Tutup koneksi sebelum redirect
    mysqli_close($koneksi);
    header('Location: data_barang.php');
    exit();
}

// Baris mysqli_close($koneksi); yang tadinya di sini sudah dihapus.
?>