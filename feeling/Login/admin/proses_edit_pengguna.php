<?php
session_start();
require_once '../config/config.php';
if (!isset($_SESSION['id_pengguna']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

if (isset($_POST['update_pengguna'])) {
    $id_pengguna = (int)$_POST['id_pengguna'];
    $nama_lengkap = htmlspecialchars($_POST['nama_lengkap']);
    $email = htmlspecialchars($_POST['email']);
    $role = htmlspecialchars($_POST['role']);
    $password_baru = $_POST['password']; // Ambil password baru (mungkin kosong)

    // Validasi
    if (empty($nama_lengkap) || empty($email) || empty($role) || empty($id_pengguna)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Data wajib tidak boleh kosong!'];
        header('Location: data_pengguna.php');
        exit();
    }
    
    // Cek email duplikat (tapi abaikan email milik user ini sendiri)
    $stmt_cek = $koneksi->prepare("SELECT email FROM tabel_pengguna WHERE email = ? AND id_pengguna != ?");
    $stmt_cek->bind_param("si", $email, $id_pengguna);
    $stmt_cek->execute();
    $stmt_cek->store_result();
    if ($stmt_cek->num_rows > 0) {
         $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Email sudah dipakai oleh pengguna lain.'];
         $stmt_cek->close();
         mysqli_close($koneksi);
         header('Location: data_pengguna.php');
         exit();
    }
    $stmt_cek->close();

    // PERSIAPKAN QUERY UPDATE
    if (empty($password_baru)) {
        // JIKA PASSWORD KOSONG: Jangan update password
        $query = "UPDATE tabel_pengguna SET nama_lengkap = ?, email = ?, role = ? WHERE id_pengguna = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "sssi", $nama_lengkap, $email, $role, $id_pengguna);
    } else {
        // JIKA PASSWORD DIISI: Update password juga
        $hashed_password_baru = password_hash($password_baru, PASSWORD_DEFAULT);
        $query = "UPDATE tabel_pengguna SET nama_lengkap = ?, email = ?, role = ?, password = ? WHERE id_pengguna = ?";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssssi", $nama_lengkap, $email, $role, $hashed_password_baru, $id_pengguna);
    }

    // EKSEKUSI
    if ($stmt) {
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data pengguna berhasil diperbarui!'];
        } else {
            $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal memperbarui: ' . mysqli_stmt_error($stmt)];
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