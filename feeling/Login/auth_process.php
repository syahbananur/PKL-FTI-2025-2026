<?php
// (1) Mulai session dan panggil file koneksi
session_start();
// BENAR: 'config/config.php' (karena file ini "tetangga" folder config)
require_once 'config/config.php'; 

/*
 * ========================================
 * PROSES REGISTRASI
 * ========================================
 */
if (isset($_POST['register_btn'])) {
    $nama_lengkap = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $default_role = 'user'; 

    $stmt_check = $koneksi->prepare("SELECT email FROM tabel_pengguna WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Email ini sudah terdaftar!'
        ];
        $_SESSION['active_form'] = 'register';
    } else {
        $stmt_insert = $koneksi->prepare("INSERT INTO tabel_pengguna (nama_lengkap, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("ssss", $nama_lengkap, $email, $hashed_password, $default_role);
        
        if ($stmt_insert->execute()) {
            $_SESSION['alerts'][] = [
                'type' => 'success',
                'message' => 'Registrasi berhasil! Silakan login.'
            ];
            $_SESSION['active_form'] = 'login';
        } else {
            $_SESSION['alerts'][] = [
                'type' => 'error',
                'message' => 'Registrasi gagal, silakan coba lagi.'
            ];
            $_SESSION['active_form'] = 'register';
        }
        $stmt_insert->close(); // Tutup statement insert
    }
    $stmt_check->close(); // Tutup statement check
    $koneksi->close(); // Tutup koneksi
    header('location: index.php'); // Kembalikan ke index.php
    exit();
}


/*
 * ========================================
 * PROSES LOGIN
 * ========================================
 */
if (isset($_POST['login_btn'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $koneksi->prepare("SELECT id_pengguna, nama_lengkap, email, password, role FROM tabel_pengguna WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // (D) Password COCOK! Buat Session Kunci
            $_SESSION['id_pengguna'] = $user['id_pengguna'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['nama_lengkap']; 
            $_SESSION['alerts'][] = [
            'type' => 'success',
            'message' => 'Login Berhasil!'];

            // PERBAIKAN: Tutup koneksi SEBELUM redirect
            $stmt->close();
            $koneksi->close();
            
            // BENAR: 'admin/dashboard.php' (pakai forward slash /)
            header('location: admin/dashboard.php'); 
            exit();

        } else {
            // Password Salah
            $_SESSION['alerts'][] = [
                'type' => 'error',
                'message' => 'Email atau Password salah!'
            ];
            $_SESSION['active_form'] = 'login';
            
            // PERBAIKAN: Tutup koneksi SEBELUM redirect
            $stmt->close();
            $koneksi->close();
            header('location: index.php');
            exit();
        }
    } else {
        // Email tidak ditemukan
        $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Email tidak terdaftar!'
        ];
        $_SESSION['active_form'] = 'login';
        
        // PERBAIKAN: Tutup koneksi SEBELUM redirect
        $stmt->close();
        $koneksi->close();
        header('location: index.php');
        exit();
    }
}

// Jika tidak ada POST 'login_btn' atau 'register_btn', 
// script akan sampai di sini dan menutup koneksi.
$koneksi->close();
?>