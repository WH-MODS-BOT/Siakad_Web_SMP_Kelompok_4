<?php
// Pastikan session sudah aktif melalui Session.php atau session_start()
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

// Menjaga agar pengalihan halaman tetap berada dalam kesatuan dashboard utama (Bersih tanpa parameter status di URL)
$redirect_url = "dashboard.php?page=mapel";

// Fungsi pembantu untuk membuat flash message pop-up berbasis session
function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER METHOD GET (UNTUK PROSES SOFT DELETE VIA URL)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'soft_delete') {
    $id_mapel = (int) $_GET['id'];
    
    // Melakukan Soft Delete: Mengubah status 'deleted' menjadi 1
    $query = mysqli_query($conn, "UPDATE mapel SET deleted = 1 WHERE id_mapel = $id_mapel");
    
    if ($query) {
        setFlash('warning', 'Arsip Penyimpanan', 'Mata pelajaran berhasil dipindahkan ke Cache Backup sementara.');
    } else {
        setFlash('danger', 'Gagal Eksekusi', 'Terjadi kesalahan sistem saat memproses perintah hapus.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST (UNTUK FORM INSERT, UPDATE, RESTORE, & HARD DELETE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AKSI: INSERT (TAMBAH DATA BARU) ---
    if ($action == 'insert') {
        $kode_mapel = mysqli_real_escape_string($conn, $_POST['kode_mapel']);
        $nama_mapel = trim(mysqli_real_escape_string($conn, $_POST['nama_mapel']));
        $kkm        = (int) $_POST['kkm'];

        // Validasi Duplikasi Kode Mapel sebelum Insert
        $check_duplicate = mysqli_query($conn, "SELECT id_mapel FROM mapel WHERE kode_mapel = '$kode_mapel'");
        if (mysqli_num_rows($check_duplicate) > 0) {
            setFlash('danger', 'Duplikasi Ditolak', 'Gagal! Kode Mata Pelajaran tersebut sudah dipakai data lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        $sql = "INSERT INTO mapel (kode_mapel, nama_mapel, kkm, deleted) VALUES ('$kode_mapel', '$nama_mapel', $kkm, 0)";
        $query = mysqli_query($conn, $sql);

        if ($query) {
            setFlash('success', 'Mata Pelajaran Ditambahkan', 'Data kurikulum baru berhasil disimpan dengan sukses.');
        } else {
            setFlash('danger', 'Gagal Eksekusi', 'Terjadi kesalahan sistem saat memproses perintah query database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: UPDATE (PERBARUI DATA) ---
    if ($action == 'update') {
        $id_mapel   = (int) $_POST['id_mapel'];
        $kode_mapel = mysqli_real_escape_string($conn, $_POST['kode_mapel']);
        $nama_mapel = trim(mysqli_real_escape_string($conn, $_POST['nama_mapel']));
        $kkm        = (int) $_POST['kkm'];

        $sql = "UPDATE mapel SET kode_mapel = '$kode_mapel', nama_mapel = '$nama_mapel', kkm = $kkm WHERE id_mapel = $id_mapel";
        $query = mysqli_query($conn, $sql);

        if ($query) {
            setFlash('success', 'Pembaruan Berhasil', 'Perubahan informasi nama mapel & nilai KKM berhasil disimpan.');
        } else {
            setFlash('danger', 'Gagal Eksekusi', 'Terjadi kesalahan sistem saat memproses perintah query database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: RESTORE (MEMULIHKAN DARI SAMPAH VIA CHECKBOX) ---
    if ($action == 'restore') {
        if (!empty($_POST['selected_mapel'])) {
            $ids = array_map('intval', $_POST['selected_mapel']);
            $id_list = implode(',', $ids);
            
            $query = mysqli_query($conn, "UPDATE mapel SET deleted = 0 WHERE id_mapel IN ($id_list)");
            if ($query) {
                setFlash('success', 'Berhasil Restore', 'Mata pelajaran terpilih berhasil dikembalikan ke tabel aktif.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('info', 'Tidak Ada Pilihan', 'Harap pilih/centang mata pelajaran terlebih dahulu untuk dikelola.');
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: PERMANENT DELETE (MENGHAPUS BERSIH VIA CHECKBOX) ---
    if ($action == 'permanent_delete') {
        if (!empty($_POST['selected_mapel'])) {
            $ids = array_map('intval', $_POST['selected_mapel']);
            $id_list = implode(',', $ids);
            
            $query = mysqli_query($conn, "DELETE FROM mapel WHERE id_mapel IN ($id_list)");
            if ($query) {
                setFlash('danger', 'Pembersihan Permanen', 'Mata pelajaran terpilih dimusnahkan selamanya dari basis data.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('info', 'Tidak Ada Pilihan', 'Harap pilih/centang mata pelajaran terlebih dahulu untuk dikelola.');
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;