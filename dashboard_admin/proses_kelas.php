<?php
if (file_exists('../Session.php')) {
    require_once '../Session.php';
} else {
    require_once 'Session.php';
}

use Session\Session;

if(Session::getRole()!='admin'){
    header("Location: ../index.php");
    exit;
}

if (file_exists('../koneksi.php')) {
    require_once '../koneksi.php';
} else {
    require_once 'koneksi.php';
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect dialihkan kembali ke modul halaman kelas pada dashboard utama
$redirect_url = "dashboard.php?page=kelas";

// Fungsi pembantu pembentuk flash message
function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER METHOD GET (UNTUK PROSES DELETE)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id_kelas = (int) $_GET['id'];
    
    $query = mysqli_query($conn, "DELETE FROM kelas WHERE id_kelas = $id_kelas");
    if ($query) {
        setFlash('danger', 'Data Terhapus', 'Data manajemen ruang kelas telah berhasil dimusnahkan selamanya.');
    } else {
        setFlash('danger', 'Gagal Eksekusi', 'Gagal menghapus data. Kemungkinan data terikat relasi aktif dengan tabel lain.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST (UNTUK FORM INSERT & UPDATE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AKSI: INSERT DATA ---
    if ($action == 'insert') {
        $kode_kelas   = trim(mysqli_real_escape_string($conn, $_POST['kode_kelas']));
        $nama_kelas   = trim(mysqli_real_escape_string($conn, $_POST['nama_kelas']));
        $kapasitas    = (int) $_POST['kapasitas'];
        $id_wali_guru = !empty($_POST['id_wali_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_wali_guru']) . "'" : "NULL";

        // Cek duplikasi kode kelas atau nama kelas
        $check = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE kode_kelas = '$kode_kelas' OR nama_kelas = '$nama_kelas'");
        if (mysqli_num_rows($check) > 0) {
            setFlash('warning', 'Duplikasi Terdeteksi', 'Gagal menyimpan! Kode atau nama kelas tersebut sudah digunakan.');
            header("Location: " . $redirect_url);
            exit;
        }

        $sql = "INSERT INTO kelas (kode_kelas, nama_kelas, kapasitas, id_wali_guru) VALUES ('$kode_kelas', '$nama_kelas', $kapasitas, $id_wali_guru)";
        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Kelas Berhasil Dibuat', 'Ruang kelas kurikulum baru telah sukses didaftarkan.');
        } else {
            setFlash('danger', 'Gagal Simpan', 'Terjadi gangguan Query internal pada sistem database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: UPDATE DATA ---
    if ($action == 'update') {
        $id_kelas     = (int) $_POST['id_kelas'];
        $kode_kelas   = trim(mysqli_real_escape_string($conn, $_POST['kode_kelas']));
        $nama_kelas   = trim(mysqli_real_escape_string($conn, $_POST['nama_kelas']));
        $kapasitas    = (int) $_POST['kapasitas'];
        $id_wali_guru = !empty($_POST['id_wali_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_wali_guru']) . "'" : "NULL";

        // Cek duplikasi kode kelas/nama kelas milik baris lain
        $check = mysqli_query($conn, "SELECT id_kelas FROM kelas WHERE (kode_kelas = '$kode_kelas' OR nama_kelas = '$nama_kelas') AND id_kelas != $id_kelas");
        if (mysqli_num_rows($check) > 0) {
            setFlash('warning', 'Pembaruan Ditolak', 'Gagal mengubah! Kode atau nama kelas alternatif sudah dipakai kelas lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        $sql = "UPDATE kelas SET kode_kelas = '$kode_kelas', nama_kelas = '$nama_kelas', kapasitas = $kapasitas, id_wali_guru = $id_wali_guru WHERE id_kelas = $id_kelas";
        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Pembaruan Sukses', 'Informasi manajemen kelas berhasil diperbarui dengan aman.');
        } else {
            setFlash('danger', 'Gagal Diperbarui', 'Gagal memproses eksekusi query pembaruan data.');
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;