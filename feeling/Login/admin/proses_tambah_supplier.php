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
if (isset($_POST['simpan_supplier'])) {

    // (C) AMBIL DATA DARI FORM
    $nama_supplier = htmlspecialchars($_POST['nama_supplier']);
    $kontak = htmlspecialchars($_POST['kontak']);

    // (D) VALIDASI
    if (empty($nama_supplier)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Nama supplier tidak boleh kosong!'];
        header('Location: tambah_supplier.php');
        exit();
    }

    // (E) BUAT QUERY INSERT (Prepared Statements)
    $query = "INSERT INTO tabel_supplier (nama_supplier, kontak) VALUES (?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // (F) BIND PARAMETER (s = string, s = string)
        mysqli_stmt_bind_param($stmt, "ss", $nama_supplier, $kontak);

        // (G) EKSEKUSI
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Supplier baru berhasil ditambahkan!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyimpan supplier: ' . mysqli_stmt_error($stmt)];
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
    // Jika diakses tanpa submit
    header('Location: data_supplier.php');
    exit();
}
?>