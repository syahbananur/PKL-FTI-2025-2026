<?php
// (A) PENJAGA SESI & KONEKSI
session_start();
require_once '../config/config.php'; // Panggil koneksi

// Cek login
if (!isset($_SESSION['id_pengguna'])) {
    header('Location: ../index.php');
    exit();
}

// (B) PASTIKAN FORM DI-SUBMIT
// Kita cek apakah tombol 'simpan_masuk' ditekan
if (isset($_POST['simpan_masuk'])) {
    
    // (C) AMBIL DATA DARI FORMULIR ($_POST)
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $id_barang = (int)$_POST['id_barang'];
    $id_supplier = (int)$_POST['id_supplier'];
    $jumlah_masuk = (int)$_POST['jumlah_masuk'];
    $id_pengguna_pencatat = (int)$_POST['id_pengguna_pencatat']; // Ambil dari input hidden

    // (D) VALIDASI SEDERHANA
    if (empty($tanggal_masuk) || empty($id_barang) || empty($id_supplier) || $jumlah_masuk <= 0 || empty($id_pengguna_pencatat)) {
        echo "Semua data wajib diisi dan jumlah harus lebih dari 0!";
        // Idealnya: redirect kembali ke form barang_masuk.php dengan pesan error
        exit(); 
    }

    // (E) BUAT QUERY INSERT (Gunakan Prepared Statements)
    $query = "INSERT INTO tabel_barang_masuk 
              (tanggal_masuk, id_barang, id_supplier, jumlah_masuk, id_pengguna_pencatat) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // (F) BIND PARAMETER
        // "siiii" artinya: String (tanggal), Integer, Integer, Integer, Integer
        mysqli_stmt_bind_param($stmt, "siiii", 
            $tanggal_masuk, 
            $id_barang, 
            $id_supplier, 
            $jumlah_masuk,
            $id_pengguna_pencatat
        );

        // (G) EKSEKUSI QUERY
        if (mysqli_stmt_execute($stmt)) {
            // Jika BERHASIL disimpan
            
            // TITIP PESAN SUKSES KE SESSION (Untuk ditampilkan di halaman berikutnya)
            $_SESSION['alerts'][] = [
                'type' => 'success',
                'message' => 'Data barang masuk berhasil disimpan!'
            ];

            // (H) TUTUP STATEMENT & KONEKSI SEBELUM REDIRECT
            mysqli_stmt_close($stmt);
            mysqli_close($koneksi);

            // Arahkan ke halaman riwayat barang masuk (yang akan kita buat) atau kembali ke form
            header('Location: riwayat_barang_masuk.php'); // Kita akan buat file ini
            exit();

        } else {
            // Jika GAGAL disimpan
             $_SESSION['alerts'][] = [
                'type' => 'error',
                'message' => 'Gagal menyimpan data barang masuk: ' . mysqli_stmt_error($stmt)
            ];
             // (H) TUTUP STATEMENT & KONEKSI SEBELUM REDIRECT
            mysqli_stmt_close($stmt);
            mysqli_close($koneksi);
             header('Location: barang_masuk.php'); // Kembali ke form jika gagal
             exit();
        }

    } else {
        // Jika query prepare gagal
         $_SESSION['alerts'][] = [
            'type' => 'error',
            'message' => 'Gagal menyiapkan query: ' . mysqli_error($koneksi)
        ];
        mysqli_close($koneksi); // Tutup koneksi
        header('Location: barang_masuk.php'); // Kembali ke form jika gagal
        exit();
    }

} else {
    // Jika halaman ini diakses langsung tanpa submit form
    echo "Akses tidak sah.";
    // Arahkan ke halaman lain
    header('Location: dashboard.php');
    exit();
}
?>