<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; 

// Cek login & role admin
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

// (B) PASTIKAN FORM DI-SUBMIT
if (isset($_POST['simpan_barang'])) {
    
    // (C) AMBIL DATA (TERMASUK KATEGORI)
    $nama_barang = htmlspecialchars($_POST['nama_barang']);
    $kategori_barang = htmlspecialchars($_POST['kategori_barang']); // <-- DATA BARU
    $id_satuan = (int)$_POST['id_satuan'];
    $stok_awal = (float)$_POST['stok_awal'];
    $stok_minimum = (float)$_POST['stok_minimum'];
    $status_barang = 'Aktif'; 

    // (D) VALIDASI (TERMASUK KATEGORI)
    if (empty($nama_barang) || empty($id_satuan) || empty($kategori_barang)) { // <-- Tambah cek kategori
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Nama, Kategori, dan Satuan tidak boleh kosong!'];
        header('Location: tambah_barang.php');
        exit(); 
    }
    // Validasi nilai kategori
    if ($kategori_barang !== 'Produksi' && $kategori_barang !== 'Non Produksi') {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Kategori barang tidak valid!'];
        header('Location: tambah_barang.php');
        exit(); 
    }

    // (E) BUAT QUERY INSERT (DENGAN KATEGORI)
    $query = "INSERT INTO tabel_barang (nama_barang, kategori_barang, id_satuan, stok_awal, stok_minimum, status_barang) VALUES (?, ?, ?, ?, ?, ?)"; // <-- 1 kolom baru
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // (F) BIND PARAMETER (ssidds)
        mysqli_stmt_bind_param($stmt, "ssidds", // <-- Tambah 's'
            $nama_barang, 
            $kategori_barang, // <-- Variabel baru
            $id_satuan, 
            $stok_awal, 
            $stok_minimum, 
            $status_barang
        );

        // (G) EKSEKUSI QUERY
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data barang berhasil disimpan!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyimpan data: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)];
    }

} else {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses tidak sah.'];
}

// (H) TUTUP KONEKSI & REDIRECT
mysqli_close($koneksi);
header('Location: data_barang.php'); // Redirect ke halaman daftar
exit();
?>