<?php
// (A) Variabel Koneksi Database
$server   = "localhost";    // Nama server database Anda
$user     = "root";         // Username default XAMPP
$pass     = "";             // Password default XAMPP (kosong)
$db       = "db_stok"; // Nama database yang Anda buat

// (B) Membuat Koneksi
$koneksi = mysqli_connect($server, $user, $pass, $db);

// (C) Mengecek Koneksi
if (!$koneksi) {
  // Jika koneksi gagal, tampilkan pesan error dan hentikan program
  die("Koneksi ke database gagal: " . mysqli_connect_error());
}

// (D) Opsional: Hapus baris ini jika sudah berhasil
// echo "Koneksi ke database berhasil!"; 
?>