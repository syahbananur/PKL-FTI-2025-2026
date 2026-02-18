<?php
// (A) PENJAGA SESI (SESSION GUARD)
// Ini adalah "Satpam" di depan pintu setiap halaman admin
session_start();
require_once '..\config\config.php'; // (1) Panggil koneksi

// (B) Cek apakah user sudah login
// Jika session 'id_pengguna' TIDAK ADA, usir dia kembali ke login.php
if (!isset($_SESSION['id_pengguna'])) {
    
    // (Opsional) Beri pesan error
    $_SESSION['alerts'][] = [
        'type' => 'error',
        'message' => 'Anda harus login terlebih dahulu!'
    ];
    header('Location: ../index.php'); // (2) ../ artinya "keluar 1 folder"
    exit();
}

// (C) Ambil data user dari session (untuk ditampilkan)
$id_user_login = $_SESSION['id_pengguna'];
$nama_user_login = $_SESSION['name']; // 'name' dari tutorial Anda
$role_user_login = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Stok Kopi</title>
    
    <link rel="stylesheet" href="/assets/style.css"> 
    
    <style>
        body {
            /* Kita pakai background yang lebih netral untuk admin */
            background-color: #f4f4f4; 
        }
        .dashboard-container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .admin-header h1 {
            color: #333;
            font-size: 24px;
        }
        /* Tombol Logout */
        .