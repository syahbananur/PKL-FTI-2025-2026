<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// (B) PASTIKAN FORM DI-SUBMIT
if (isset($_POST['update_supplier'])) {

    // (C) AMBIL DATA DARI FORM
    $id_supplier = (int)$_POST['id_supplier'];
    $nama_supplier = htmlspecialchars($_POST['nama_supplier']);
    $kontak = htmlspecialchars($_POST['kontak']);

    // (D) VALIDASI
    if (empty($nama_supplier) || empty($id_supplier)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Nama supplier dan ID tidak boleh kosong!'];
        header('Location: data_supplier.php');
        exit();
    }

    // (E) BUAT QUERY UPDATE (Prepared Statements)
    $query = "UPDATE tabel_supplier SET nama_supplier = ?, kontak = ? WHERE id_supplier = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // (F) BIND PARAMETER (s = string, s = string, i = integer)
        mysqli_stmt_bind_param($stmt, "ssi", $nama_supplier, $kontak, $id_supplier);

        // (G) EKSEKUSI
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data supplier berhasil diperbarui!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal memperbarui supplier: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)];
    }

    // (H) TUTUP KONEKSI & REDIRECT
    mysqli_close($koneksi);
    header('Location: data_supplier.php'); // Kembali ke daftar supplier
    exit();

} else {
    header('Location: data_supplier.php');
    exit();
}
?>