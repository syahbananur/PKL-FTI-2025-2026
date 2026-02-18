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
if (isset($_POST['simpan_keluar'])) {

    // (C) AMBIL DATA DARI FORMULIR (DENGAN PERUBAHAN)
    $tanggal_keluar = $_POST['tanggal_keluar'];
    $id_barang = (int)$_POST['id_barang'];
    $id_tujuan = (int)$_POST['id_tujuan']; // <-- KOLOM BARU
    $jumlah_keluar = (float)$_POST['jumlah_keluar']; // Ubah ke float untuk desimal
    $keterangan_darurat = htmlspecialchars($_POST['keterangan_darurat']); // <-- KOLOM BARU
    $id_pengguna_pencatat = (int)$_POST['id_pengguna_pencatat'];

    // (D) VALIDASI INPUT DASAR
    if (empty($tanggal_keluar) || empty($id_barang) || empty($id_tujuan) || $jumlah_keluar <= 0 || empty($id_pengguna_pencatat)) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Data wajib (Tanggal, Barang, Tujuan, Jumlah) harus diisi!'];
        header('Location: barang_keluar.php');
        exit();
    }
    
    // (E) VALIDASI STOK (Logika ini tetap sama dan sangat penting)
    // 1. Hitung total masuk
    $query_masuk = "SELECT COALESCE(SUM(jumlah_masuk), 0) AS total_masuk FROM tabel_barang_masuk WHERE id_barang = ?";
    $stmt_masuk = mysqli_prepare($koneksi, $query_masuk);
    mysqli_stmt_bind_param($stmt_masuk, "i", $id_barang);
    mysqli_stmt_execute($stmt_masuk);
    $total_masuk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_masuk))['total_masuk'];
    mysqli_stmt_close($stmt_masuk);

    // 2. Hitung total keluar sebelumnya
    $query_keluar = "SELECT COALESCE(SUM(jumlah_keluar), 0) AS total_keluar FROM tabel_barang_keluar WHERE id_barang = ?";
    $stmt_keluar = mysqli_prepare($koneksi, $query_keluar);
    mysqli_stmt_bind_param($stmt_keluar, "i", $id_barang);
    mysqli_stmt_execute($stmt_keluar);
    $total_keluar_sebelumnya = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_keluar))['total_keluar'];
    mysqli_stmt_close($stmt_keluar);

    // 3. Hitung stok awal
    $query_awal = "SELECT stok_awal FROM tabel_barang WHERE id_barang = ?";
    $stmt_awal = mysqli_prepare($koneksi, $query_awal);
    mysqli_stmt_bind_param($stmt_awal, "i", $id_barang);
    mysqli_stmt_execute($stmt_awal);
    $stok_awal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_awal))['stok_awal'] ?? 0;
    mysqli_stmt_close($stmt_awal);

    // 4. Hitung Sisa Stok Saat Ini
    $sisa_stok = $stok_awal + $total_masuk - $total_keluar_sebelumnya;

    // 5. Bandingkan
    if ($jumlah_keluar > $sisa_stok) {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => "Stok tidak cukup! Sisa stok saat ini hanya {$sisa_stok}."];
        mysqli_close($koneksi);
        header('Location: barang_keluar.php');
        exit();
    }
    // --- AKHIR VALIDASI STOK ---

    // (F) JIKA STOK CUKUP, LANJUT SIMPAN (QUERY BARU)
    $query_insert = "INSERT INTO tabel_barang_keluar
                     (tanggal_keluar, id_barang, id_tujuan, jumlah_keluar, keterangan_darurat, id_pengguna_pencatat)
                     VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt_insert = mysqli_prepare($koneksi, $query_insert);

    if ($stmt_insert) {
        // "siidssi" -> s(string), i(int), i(int), d(double/decimal), s(string), i(int)
        mysqli_stmt_bind_param($stmt_insert, "siidsi",
            $tanggal_keluar,
            $id_barang,
            $id_tujuan,
            $jumlah_keluar,
            $keterangan_darurat,
            $id_pengguna_pencatat
        );

        // Eksekusi Query
        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['alerts'][] = ['type' => 'success', 'message' => 'Data barang keluar berhasil disimpan!'];
            mysqli_stmt_close($stmt_insert);
            mysqli_close($koneksi);
            header('Location: riwayat_barang_keluar.php'); // Arahkan ke halaman riwayat
            exit();
        } else {
             $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyimpan data: ' . mysqli_stmt_error($stmt_insert)];
        }
    } else {
        $_SESSION['alerts'][] = ['type' => 'error', 'message' => 'Gagal menyiapkan query insert: ' . mysqli_error($koneksi)];
    }
    
    // Jika gagal di suatu tempat, tutup koneksi dan kembali ke form
    if(isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
    mysqli_close($koneksi);
    header('Location: barang_keluar.php');
    exit();

} else {
    // Jika diakses tanpa submit form
    header('Location: dashboard.php');
    exit();
}
?>