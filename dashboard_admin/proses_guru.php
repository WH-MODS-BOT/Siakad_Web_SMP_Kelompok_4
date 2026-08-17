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

// Menjaga agar pengalihan halaman tetap berada dalam kesatuan dashboard utama
$redirect_url = "dashboard.php?page=guru";

// Fungsi pembantu untuk membuat flash message pop-up
function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, 
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER METHOD GET (UNTUK PROSES SOFT DELETE VIA LINK)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'soft_delete') {
    $id_guru = mysqli_real_escape_string($conn, $_GET['id']);
    
    // PROTEKSI: Cek apakah guru ini menjabat sebagai wali kelas aktif
    $cek_wali = mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id_wali_guru = '$id_guru'");
    if (mysqli_num_rows($cek_wali) > 0) {
        $data_kelas = mysqli_fetch_assoc($cek_wali);
        setFlash('danger', 'Gagal Menonaktifkan', 'Peringatan! Guru ini masih memegang tanggung jawab sebagai wali kelas di kelas ' . htmlspecialchars($data_kelas['nama_kelas']) . '. Silakan ganti posisi wali kelas tersebut terlebih dahulu sebelum dinonaktifkan.');
        header("Location: " . $redirect_url);
        exit;
    }
    
    // Melakukan Soft Delete: Mengubah kolom 'deleted' menjadi 1
    $query = mysqli_query($conn, "UPDATE guru SET deleted = 1 WHERE id_guru = '$id_guru'");
    
    if ($query) {
        setFlash('warning', 'Pindah ke Cache', 'Data guru berhasil dipindahkan ke Cache Backup.');
    } else {
        setFlash('danger', 'Gagal', 'Terjadi kesalahan sistem saat menonaktifkan data guru.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST (INSERT, UPDATE, RESTORE, & HARD DELETE PERMANEN)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AKSI: INSERT (TAMBAH DATA GURU BARU) ---
    if ($action == 'insert') {
        $id_guru   = mysqli_real_escape_string($conn, $_POST['id_guru']);
        $nip       = trim(mysqli_real_escape_string($conn, $_POST['nip'])); 
        $nama_guru = trim(mysqli_real_escape_string($conn, $_POST['nama_guru']));
        $jk        = mysqli_real_escape_string($conn, $_POST['jk']);
        $notelp    = mysqli_real_escape_string($conn, $_POST['notelp']);
        $jurusan   = mysqli_real_escape_string($conn, $_POST['jurusan']);
        $status    = mysqli_real_escape_string($conn, $_POST['status']); 
        $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);

        if (empty($nip) || empty($status) || empty($nama_guru)) {
            setFlash('danger', 'Gagal', 'NIP, Nama Lengkap, dan Status Kepegawaian tidak boleh kosong.');
            header("Location: " . $redirect_url);
            exit;
        }

        // VALIDASI NIP: Harus berupa angka saja (tanpa huruf, spasi, atau simbol)
        if (!preg_match("/^[0-9]+$/", $nip)) {
            setFlash('danger', 'Format NIP Salah', 'NIP harus berupa angka saja! Tidak boleh mengandung huruf, spasi, atau simbol.');
            header("Location: " . $redirect_url);
            exit;
        }

        // 1. Cek duplikasi NIP sebelum melakukan INSERT
        $check_nip = mysqli_query($conn, "SELECT id_guru, deleted FROM guru WHERE nip = '$nip'");
        if (mysqli_num_rows($check_nip) > 0) {
            $existing_guru = mysqli_fetch_assoc($check_nip);
            if ($existing_guru['deleted'] == 1) {
                setFlash('info', 'Data Terarsip', 'NIP ini sudah terdaftar pada data guru nonaktif di Cache Backup. Silakan lakukan restore.');
            } else {
                setFlash('danger', 'NIP Duplikat', 'Gagal! NIP (' . $nip . ') sudah digunakan oleh guru aktif lain.');
            }
            header("Location: " . $redirect_url);
            exit;
        }

        // 2. Cek duplikasi NAMA GURU sebelum melakukan INSERT
        $check_nama = mysqli_query($conn, "SELECT id_guru, deleted FROM guru WHERE nama_guru = '$nama_guru'");
        if (mysqli_num_rows($check_nama) > 0) {
            $existing_nama = mysqli_fetch_assoc($check_nama);
            if ($existing_nama['deleted'] == 1) {
                setFlash('info', 'Nama Terarsip', 'Nama guru "' . $nama_guru . '" terdeteksi ada di Cache Backup. Silakan periksa arsip sampah.');
            } else {
                setFlash('danger', 'Nama Duplikat', 'Gagal! Guru dengan nama "' . $nama_guru . '" sudah terdaftar dalam sistem.');
            }
            header("Location: " . $redirect_url);
            exit;
        }

        $sql = "INSERT INTO guru (id_guru, nip, nama_guru, jk, alamat, notelp, status, jurusan, deleted) 
                VALUES ('$id_guru', '$nip', '$nama_guru', '$jk', '$alamat', '$notelp', '$status', '$jurusan', 0)";

        $query = mysqli_query($conn, $sql);
        if ($query) {
            setFlash('success', 'Berhasil Disimpan', 'Data guru baru berhasil ditambahkan.');
        } else {
            setFlash('danger', 'Gagal', 'Terjadi kesalahan internal saat menyimpan data.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: UPDATE (EDIT DATA GURU) ---
    if ($action == 'update') {
        $id_guru   = mysqli_real_escape_string($conn, $_POST['id_guru']);
        $nip       = trim(mysqli_real_escape_string($conn, $_POST['nip'])); 
        $nama_guru = trim(mysqli_real_escape_string($conn, $_POST['nama_guru']));
        $jk        = mysqli_real_escape_string($conn, $_POST['jk']);
        $notelp    = mysqli_real_escape_string($conn, $_POST['notelp']);
        $jurusan   = mysqli_real_escape_string($conn, $_POST['jurusan']);
        $status    = mysqli_real_escape_string($conn, $_POST['status']); 
        $alamat    = mysqli_real_escape_string($conn, $_POST['alamat']);

        if (empty($nip) || empty($status) || empty($nama_guru)) {
            setFlash('danger', 'Gagal', 'NIP, Nama Lengkap, dan Status Kepegawaian wajib diisi.');
            header("Location: " . $redirect_url);
            exit;
        }

        // VALIDASI NIP: Harus berupa angka saja (tanpa huruf, spasi, atau simbol)
        if (!preg_match("/^[0-9]+$/", $nip)) {
            setFlash('danger', 'Format NIP Salah', 'NIP harus berupa angka saja! Tidak boleh mengandung huruf, spasi, atau simbol.');
            header("Location: " . $redirect_url);
            exit;
        }

        // 1. Cek duplikasi NIP pada record milik guru lain saat UPDATE
        $check_nip_edit = mysqli_query($conn, "SELECT id_guru FROM guru WHERE nip = '$nip' AND id_guru != '$id_guru'");
        if (mysqli_num_rows($check_nip_edit) > 0) {
            setFlash('danger', 'Gagal Perbarui', 'NIP (' . $nip . ') sudah digunakan oleh guru lain. Perubahan ditolak.');
            header("Location: " . $redirect_url);
            exit;
        }

        // 2. Cek duplikasi NAMA GURU pada record milik guru lain saat UPDATE
        $check_nama_edit = mysqli_query($conn, "SELECT id_guru FROM guru WHERE nama_guru = '$nama_guru' AND id_guru != '$id_guru'");
        if (mysqli_num_rows($check_nama_edit) > 0) {
            setFlash('danger', 'Gagal Perbarui', 'Nama guru "' . $nama_guru . '" sudah terdaftar pada record data guru lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        $sql = "UPDATE guru SET nip = '$nip', nama_guru = '$nama_guru', jk = '$jk', notelp = '$notelp', 
                jurusan = '$jurusan', status = '$status', alamat = '$alamat' WHERE id_guru = '$id_guru'";

        $query = mysqli_query($conn, $sql);
        if ($query) {
            setFlash('success', 'Berhasil Diperbarui', 'Informasi biodata guru berhasil diubah.');
        } else {
            setFlash('danger', 'Gagal', 'Gagal memperbarui data guru.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: RESTORE ---
    if ($action == 'restore') {
        if (!empty($_POST['selected_guru'])) {
            $ids = array_map(function($val) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $val) . "'";
            }, $_POST['selected_guru']);
            $id_list = implode(',', $ids);
            
            $query = mysqli_query($conn, "UPDATE guru SET deleted = 0 WHERE id_guru IN ($id_list)");
            if ($query) {
                setFlash('success', 'Restored', 'Data guru terpilih berhasil diaktifkan kembali.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('warning', 'Peringatan', 'Tidak ada data guru terarsip yang dipilih.');
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: PERMANENT DELETE ---
    if ($action == 'permanent_delete') {
        if (!empty($_POST['selected_guru'])) {
            $ids = array_map(function($val) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $val) . "'";
            }, $_POST['selected_guru']);
            $id_list = implode(',', $ids);
            
            $query = mysqli_query($conn, "DELETE FROM guru WHERE id_guru IN ($id_list)");
            if ($query) {
                setFlash('success', 'Musnah Permanen', 'Data guru terpilih telah dihapus selamanya dari sistem.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('warning', 'Peringatan', 'Tidak ada data guru yang dipilih untuk dihapus permanen.');
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;