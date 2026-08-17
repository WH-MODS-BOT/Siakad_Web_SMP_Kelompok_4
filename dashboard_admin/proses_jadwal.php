<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Memanggil Session & Autentikasi Admin
if (file_exists('../Session.php')) {
    require_once '../Session.php';
} else {
    require_once 'Session.php';
}

use Session\Session;

if (Session::getRole() != 'admin') {
    header("Location: ../index.php");
    exit;
}

// 2. Memanggil Koneksi Database
if (file_exists('../koneksi.php')) {
    require_once '../koneksi.php';
} else {
    require_once 'koneksi.php';
}

$redirect_url = "dashboard.php?page=jadwal";

function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, 
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER METHOD GET (SOFT DELETE)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'soft_delete') {
    $id_target = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    $query = mysqli_query($conn, "UPDATE japel SET deleted = 1 WHERE id_japel = $id_target");
    if ($query) {
        setFlash('success', 'Diarsipkan', 'Data jadwal pelajaran berhasil dipindahkan ke cache arsip.');
    } else {
        setFlash('danger', 'Gagal', 'Terjadi kesalahan sistem sewaktu mengarsipkan data.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';

    // BULK RESTORE
    if ($action == 'bulk_restore') {
        if (!empty($_POST['selected_jadwal'])) {
            $ids = array_map(function($val) { return (int)$val; }, $_POST['selected_jadwal']);
            $id_list = implode(',', $ids);
            $query = mysqli_query($conn, "UPDATE japel SET deleted = 0 WHERE id_japel IN ($id_list)");
            if ($query) {
                setFlash('success', 'Restored', 'Data jadwal terpilih sukses diaktifkan kembali.');
            } else {
                setFlash('danger', 'Gagal', 'Sistem gagal memulihkan data.');
            }
        } else {
            setFlash('warning', 'Peringatan', 'Tidak ada data jadwal yang dipilih.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // BULK PERMANENT DELETE
    if ($action == 'bulk_permanent_delete') {
        if (!empty($_POST['selected_jadwal'])) {
            $ids = array_map(function($val) { return (int)$val; }, $_POST['selected_jadwal']);
            $id_list = implode(',', $ids);
            $query = mysqli_query($conn, "DELETE FROM japel WHERE id_japel IN ($id_list)");
            if ($query) {
                setFlash('success', 'Musnah Permanen', 'Data jadwal terpilih telah dihapus selamanya.');
            } else {
                setFlash('danger', 'Gagal', 'Gagal melakukan penghapusan data secara permanen.');
            }
        } else {
            setFlash('warning', 'Peringatan', 'Tidak ada objek jadwal yang dipilih.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: INSERT JADWAL BARU
    // -------------------------------------------------------------------------
    if ($action == 'insert') {
        $hari         = mysqli_real_escape_string($conn, $_POST['hari']);
        $jam_mulai    = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
        $jam_selesai  = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
        $id_kelas     = (int)$_POST['id_kelas'];
        $id_tahun_aj  = (int)$_POST['id_tahun']; 
        
        $is_istirahat = isset($_POST['is_istirahat']) && $_POST['is_istirahat'] == '1';

        $query_ta = mysqli_query($conn, "SELECT id_tahun, semester FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun_aj");
        if ($query_ta && mysqli_num_rows($query_ta) > 0) {
            $data_ta = mysqli_fetch_assoc($query_ta);
            $id_tahun = (int)$data_ta['id_tahun'];
            $semester = mysqli_real_escape_string($conn, $data_ta['semester']);
        } else {
            setFlash('danger', 'Gagal', 'Referensi Data Tahun Ajaran tidak valid.');
            header("Location: " . $redirect_url);
            exit;
        }

        if ($is_istirahat) {
            // Jika istirahat, Mapel mutlak NULL. Guru boleh diisi (jika dipilih) atau NULL (jika dikosongkan)
            $id_mapel_sql = "NULL";
            $id_guru_sql  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";
        } else {
            // Jika KBM Efektif, Guru dan Mapel wajib ada
            $id_guru_sql  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";
            $id_mapel_sql = !empty($_POST['id_mapel']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_mapel']) . "'" : "NULL";

            if ($id_guru_sql == "NULL" || $id_mapel_sql == "NULL") {
                setFlash('warning', 'Input Tidak Lengkap', 'Guru dan Mata Pelajaran wajib diisi jika bukan waktu istirahat.');
                header("Location: " . $redirect_url);
                exit;
            }
        }

        $sql_insert = "INSERT INTO japel (hari, jam_mulai, jam_selesai, id_guru, id_mapel, id_kelas, id_tahun, semester, deleted) 
                       VALUES ('$hari', '$jam_mulai', '$jam_selesai', $id_guru_sql, $id_mapel_sql, $id_kelas, $id_tahun, '$semester', 0)";
        
        if (mysqli_query($conn, $sql_insert)) {
            setFlash('success', 'Berhasil Disimpan', 'Jadwal pelajaran baru sukses ditambahkan ke sistem.');
        } else {
            setFlash('danger', 'Gagal Query', 'Kesalahan internal database sewaktu menyimpan jadwal.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // -------------------------------------------------------------------------
    // ACTION: UPDATE JADWAL
    // -------------------------------------------------------------------------
    if ($action == 'update') {
        $id_japel     = (int)$_POST['id_japel'];
        $hari         = mysqli_real_escape_string($conn, $_POST['hari']);
        $jam_mulai    = mysqli_real_escape_string($conn, $_POST['jam_mulai']);
        $jam_selesai  = mysqli_real_escape_string($conn, $_POST['jam_selesai']);
        $id_kelas     = (int)$_POST['id_kelas'];
        $id_tahun_aj  = (int)$_POST['id_tahun']; 
        
        $is_istirahat = isset($_POST['is_istirahat']) && $_POST['is_istirahat'] == '1';

        $query_ta = mysqli_query($conn, "SELECT id_tahun, semester FROM tahun_ajaran WHERE id_tahun_ajaran = $id_tahun_aj");
        if ($query_ta && mysqli_num_rows($query_ta) > 0) {
            $data_ta = mysqli_fetch_assoc($query_ta);
            $id_tahun = (int)$data_ta['id_tahun'];
            $semester = mysqli_real_escape_string($conn, $data_ta['semester']);
        } else {
            setFlash('danger', 'Gagal', 'Referensi Data Tahun Ajaran tidak valid.');
            header("Location: " . $redirect_url);
            exit;
        }

        if ($is_istirahat) {
            $id_mapel_sql = "NULL";
            $id_guru_sql  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";
        } else {
            $id_guru_sql  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";
            $id_mapel_sql = !empty($_POST['id_mapel']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_mapel']) . "'" : "NULL";

            if ($id_guru_sql == "NULL" || $id_mapel_sql == "NULL") {
                setFlash('warning', 'Input Tidak Lengkap', 'Guru dan Mata Pelajaran wajib ditentukan untuk KBM Efektif.');
                header("Location: " . $redirect_url);
                exit;
            }
        }

        $sql_update = "UPDATE japel SET 
                        hari = '$hari', 
                        jam_mulai = '$jam_mulai', 
                        jam_selesai = '$jam_selesai', 
                        id_guru = $id_guru_sql, 
                        id_mapel = $id_mapel_sql, 
                        id_kelas = $id_kelas, 
                        id_tahun = $id_tahun, 
                        semester = '$semester' 
                       WHERE id_japel = $id_japel";
        
        if (mysqli_query($conn, $sql_update)) {
            setFlash('success', 'Perubahan Disimpan', 'Informasi jadwal pelajaran berhasil diperbarui.');
        } else {
            setFlash('danger', 'Gagal Update', 'Sistem gagal memperbarui data rekor jadwal.');
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;