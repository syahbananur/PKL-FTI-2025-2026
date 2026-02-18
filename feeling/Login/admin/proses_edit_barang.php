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
if (isset($_POST['update_barang'])) {
    
    // (C) AMBIL DATA (TERMASUK KATEGORI)
    $id_barang = (int)$_POST['id_barang']; 
    $nama_barang = htmlspecialchars($_POST['nama_barang']);
    $kategori_barang = htmlspecialchars($_POST['kategori_barang']); // <-- DATA BARU
    $id_satuan = (int)$_POST['id_satuan']; 
    $stok_awal = (float)$_POST['stok_awal']; 
    $stok_minimum = (float)$_POST['stok_minimum']; 
    $status_barang = htmlspecialchars($_POST['status_barang']);

    // (D) VALIDASI (TERMASUK KATEGORI)
    if (empty($nama_barang) || empty($id_satuan) || empty($id_barang) || empty($kategori_barang)) { // <-- Tambah cek
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Data wajib tidak boleh kosong!'];
        header('Location: data_barang.php');
        exit(); 
    }
    if ($kategori_barang !== 'Produksi' && $kategori_barang !== 'Non Produksi') {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Kategori barang tidak valid!'];
        header('Location: data_barang.php');
        exit(); 
    }
    if ($status_barang !== 'Aktif' && $status_barang !== 'Non-Aktif') {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Status barang tidak valid!'];
         header('Location: data_barang.php');
         exit();
    }

    // (E) BUAT QUERY UPDATE (DENGAN KATEGORI)
    $query = "UPDATE tabel_barang 
              SET 
                  nama_barang = ?, 
                  kategori_barang = ?, /* <-- KOLOM BARU */
                  id_satuan = ?, 
                  stok_awal = ?, 
                  stok_minimum = ?, 
                  status_barang = ? 
              WHERE 
                  id_barang = ?";
    
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // (F) BIND PARAMETER (ssiddsi)
        mysqli_stmt_bind_param($stmt, "ssiddsi", // <-- Tambah 's'
            $nama_barang, 
            $kategori_barang, // <-- Variabel baru
            $id_satuan, 
            $stok_awal, 
            $stok_minimum, 
            $status_barang,
            $id_barang
        );

        // (G) EKSEKUSI QUERY
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data barang berhasil diperbarui!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal memperbarui data: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query update: ' . mysqli_error($koneksi)];
    }

} else {
    $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Akses tidak sah.'];
}

// (I) TUTUP KONEKSI & REDIRECT
mysqli_close($koneksi);
header('Location: data_barang.php');
exit();
?>