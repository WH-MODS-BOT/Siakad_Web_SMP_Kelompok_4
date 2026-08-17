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

$redirect_url = "dashboard.php?page=tahun";

function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, 
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER METHOD GET (SOFT DELETE VIA LINK AKSIS)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'soft_delete') {
    $id_ta = (int) $_GET['id'];
    
    $check_status = mysqli_query($conn, "SELECT status FROM tahun_ajaran WHERE id_tahun_ajaran = $id_ta");
    $data_status = mysqli_fetch_assoc($check_status);
    
    if ($data_status && $data_status['status'] == 'aktif') {
        setFlash('danger', 'Hapus Ditolak', 'Gagal! Tahun ajaran yang sedang AKTIF tidak boleh dihapus.');
        header("Location: " . $redirect_url);
        exit;
    }
    
    $query = mysqli_query($conn, "UPDATE tahun_ajaran SET deleted = 1 WHERE id_tahun_ajaran = $id_ta");
    if ($query) {
        setFlash('warning', 'Pindah ke Cache', 'Data tahun ajaran berhasil dipindahkan ke Cache Backup.');
    } else {
        setFlash('danger', 'Gagal', 'Terjadi kesalahan sistem database.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST (INSERT & UPDATE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AKSI: INSERT (TAMBAH DATA BARU) ---
    // --- AKSI: INSERT (TAMBAH DATA BARU) ---
    if ($action == 'insert') {
        // Ambil data status langsung dari POST dan bersihkan karakternya ke huruf kecil
        $semester = strtolower(trim(mysqli_real_escape_string($conn, $_POST['semester'])));
        $status   = strtolower(trim(mysqli_real_escape_string($conn, $_POST['status'])));
        $id_tahun = 0;

        if (isset($_POST['is_tahun_baru']) && $_POST['is_tahun_baru'] == '1') {
            $tahun_input = trim(mysqli_real_escape_string($conn, $_POST['tahun_baru']));
            
            $check_t = mysqli_query($conn, "SELECT id_tahun FROM tahun WHERE tahun = '$tahun_input'");
            if (mysqli_num_rows($check_t) > 0) {
                $row_t = mysqli_fetch_assoc($check_t);
                $id_tahun = $row_t['id_tahun'];
            } else {
                $ins_t = mysqli_query($conn, "INSERT INTO tahun (tahun) VALUES ('$tahun_input')");
                if ($ins_t) {
                    $id_tahun = mysqli_insert_id($conn);
                }
            }
        } else {
            $id_tahun = (int) $_POST['id_tahun'];
        }

        if ($id_tahun == 0) {
            setFlash('danger', 'Gagal', 'Referensi identitas data tahun tidak valid.');
            header("Location: " . $redirect_url);
            exit;
        }

        // Cek duplikasi kombinasi
        $check_duplicate = mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE id_tahun = $id_tahun AND semester = '$semester' AND deleted = 0");
        if (mysqli_num_rows($check_duplicate) > 0) {
            setFlash('danger', 'Duplikasi Data', 'Gagal! Tahun ajaran beserta semester tersebut sudah terdaftar.');
            header("Location: " . $redirect_url);
            exit;
        }

        // JIKA USER MEMILIH AKTIF: Matikan semua status aktif yang lain di database terlebih dahulu
        if ($status == 'aktif') {
            mysqli_query($conn, "UPDATE tahun_ajaran SET status = 'nonaktif'");
        }

        // Simpan ke database sesuai variabel $status dari form
        $sql = "INSERT INTO tahun_ajaran (id_tahun, semester, status, deleted) VALUES ($id_tahun, '$semester', '$status', 0)";
        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Berhasil Disimpan', 'Data tahun ajaran baru sukses ditambahkan sebagai ' . strtoupper($status));
        } else {
            setFlash('danger', 'Gagal Query', 'Gagal mengeksekusi penyimpanan data baru.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: UPDATE (EDIT DATA) ---
    // --- AKSI: UPDATE (EDIT DATA) ---
    if ($action == 'update') {
        $id_ta    = (int) $_POST['id_tahun_ajaran'];
        $semester = strtolower(trim(mysqli_real_escape_string($conn, $_POST['semester'])));
        $status   = strtolower(trim(mysqli_real_escape_string($conn, $_POST['status'])));
        
        // Ambil dari input hidden karena select utamanya di-disabled saat edit
        $id_tahun = (int) ($_POST['id_tahun_hidden'] ?? 0);

        if ($id_tahun == 0) {
            setFlash('danger', 'Gagal', 'Identitas data tahun tidak terbaca dengan benar.');
            header("Location: " . $redirect_url);
            exit;
        }

        // =========================================================================
        // VALIDASI POP-UP DUPLIKASI SEBELUM UPDATE (PENCEGAH FATAL ERROR)
        // =========================================================================
        $check_duplicate = mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran 
                            WHERE id_tahun = $id_tahun 
                            AND LOWER(semester) = '$semester' 
                            AND id_tahun_ajaran != $id_ta 
                            AND deleted = 0");
                            
        if (mysqli_num_rows($check_duplicate) > 0) {
            setFlash('danger', 'Duplikasi Terdeteksi', 'Gagal menyimpan! Kombinasi Tahun Ajaran dan Semester tersebut sudah digunakan oleh data lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        // JIKA DIUBAH MENJADI AKTIF: Nonaktifkan semua yang lain dulu
        if ($status == 'aktif') {
            mysqli_query($conn, "UPDATE tahun_ajaran SET status = 'nonaktif'");
        } else {
            // Proteksi agar sistem tidak kehilangan satu-satunya tahun ajaran yang aktif
            $check_active = mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE status = 'aktif'");
            if (mysqli_num_rows($check_active) <= 1) {
                $check_self = mysqli_query($conn, "SELECT status FROM tahun_ajaran WHERE id_tahun_ajaran = $id_ta AND status = 'aktif'");
                if (mysqli_num_rows($check_self) > 0) {
                    setFlash('warning', 'Perubahan Ditolak', 'Sistem harus memiliki minimal 1 tahun ajaran yang AKTIF. Silakan aktifkan periode tahun lain terlebih dahulu.');
                    header("Location: " . $redirect_url);
                    exit;
                }
            }
        }

        // Eksekusi Update dengan aman
        $sql = "UPDATE tahun_ajaran SET id_tahun = $id_tahun, semester = '$semester', status = '$status' WHERE id_tahun_ajaran = $id_ta";
        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Berhasil Diperbarui', 'Pembaruan data sukses disimpan sebagai ' . strtoupper($status));
        } else {
            setFlash('danger', 'Gagal Query', 'Gagal mengeksekusi pembaruan data ke database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: RESTORE ---
    if ($action == 'restore') {
        if (!empty($_POST['selected_ta'])) {
            $ids = array_map('intval', $_POST['selected_ta']);
            $id_list = implode(',', $ids);
            
            $query = mysqli_query($conn, "UPDATE tahun_ajaran SET deleted = 0, status = 'nonaktif' WHERE id_tahun_ajaran IN ($id_list)");
            if ($query) {
                setFlash('success', 'Berhasil Restore', 'Data dikembalikan ke tabel aktif dengan status default Nonaktif.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('warning', 'Peringatan', 'Tidak ada data arsip yang dipilih.');
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: PERMANENT DELETE ---
    if ($action == 'permanent_delete') {
        if (!empty($_POST['selected_ta'])) {
            $ids = array_map('intval', $_POST['selected_ta']);
            $id_list = implode(',', $ids);
            
            $check_active_cache = mysqli_query($conn, "SELECT id_tahun_ajaran FROM tahun_ajaran WHERE id_tahun_ajaran IN ($id_list) AND status = 'aktif'");
            if (mysqli_num_rows($check_active_cache) > 0) {
                setFlash('danger', 'Hapus Massal Ditolak', 'Terdapat tahun ajaran berstatus AKTIF di dalam pilihan yang tidak boleh dihapus.');
                header("Location: " . $redirect_url);
                exit;
            }

            $query_delete_ta = mysqli_query($conn, "DELETE FROM tahun_ajaran WHERE id_tahun_ajaran IN ($id_list)");
            if ($query_delete_ta) {
                setFlash('success', 'Musnah Permanen', 'Data arsip terpilih berhasil dihapus selamanya.');
                header("Location: " . $redirect_url);
                exit;
            }
        }
        setFlash('warning', 'Peringatan', 'Tidak ada data arsip yang dipilih.');
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;