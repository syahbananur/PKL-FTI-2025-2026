<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['simpan_pengguna'])) {
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password']; // Ambil password mentah
    $role = htmlspecialchars($_POST['role']);

    // Validasi
    if (empty($nama_lengkap) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Semua data wajib diisi!'];
        header('Location: tambah_pengguna.php');
        exit();
    }
    
    // Cek email duplikat
    $stmt_cek = $koneksi->prepare("SELECT email FROM tabel_pengguna WHERE email = ?");
    $stmt_cek->bind_param("s", $email);
    $stmt_cek->execute();
    $stmt_cek->store_result();

    if ($stmt_cek->num_rows > 0) {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Email sudah terdaftar. Gunakan email lain.'];
         $stmt_cek->close();
         mysqli_close($koneksi);
         header('Location: tambah_pengguna.php');
         exit();
    }
    $stmt_cek->close();

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Query Insert
    $query = "INSERT INTO tabel_pengguna (nama_lengkap, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $nama_lengkap, $email, $hashed_password, $role);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Pengguna baru berhasil ditambahkan!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyimpan: ' . mysqli_stmt_error($stmt)];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)];
    }
    
    mysqli_close($koneksi);
    header('Location: data_pengguna.php');
    exit();
} else {
    header('Location: data_pengguna.php');
    exit();
}
?>