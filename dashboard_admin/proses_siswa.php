<?php
if (file_exists('../Session.php')) {
    require_once '../Session.php';
} else {
    require_once 'Session.php';
}

use Session\Session;

// Proteksi Hak Akses Admin
if (Session::getRole() != 'admin') {
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

$redirect_url = "dashboard.php?page=siswa";

// Fungsi pembantu untuk membuat flash message pop-up Toast
function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, 
        'title' => $title,
        'message' => $message
    ];
}

// Fungsi pembantu konversi penamaan bulan Indonesia untuk otomatisasi Lulus
function nama_bulan($bulan_angka) {
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $bulan[$bulan_angka] ?? '';
}

// =========================================================================
// HANDLER METHOD GET (AKSI DELETE DATA SISWA)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_del = mysqli_real_escape_string($conn, $_GET['id']);
    
    $delete = mysqli_query($conn, "DELETE FROM siswa WHERE id_siswa='$id_del'");
    if ($delete) {
        setFlash('success', 'Siswa Dihapus', 'Data induk siswa berhasil dihapus secara permanen dari sistem.');
    } else {
        setFlash('danger', 'Gagal Menghapus', 'Terjadi kesalahan sistem saat mencoba menghapus data siswa.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER METHOD POST (AKSI INSERT & UPDATE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    $id_siswa       = mysqli_real_escape_string($conn, $_POST['id_siswa']);
    $nis            = trim(mysqli_real_escape_string($conn, $_POST['nis']));
    $nisn           = trim(mysqli_real_escape_string($conn, $_POST['nisn']));
    $nama_siswa     = trim(mysqli_real_escape_string($conn, $_POST['nama_siswa']));
    $jk             = mysqli_real_escape_string($conn, $_POST['jk']);
    $tempat_lahir   = mysqli_real_escape_string($conn, $_POST['tempat_lahir']);
    $tanggal_lahir  = !empty($_POST['tanggal_lahir']) ? "'" . mysqli_real_escape_string($conn, $_POST['tanggal_lahir']) . "'" : "NULL";
    $agama          = mysqli_real_escape_string($conn, $_POST['agama']);
    $nik            = mysqli_real_escape_string($conn, $_POST['nik']);
    $no_telpon      = mysqli_real_escape_string($conn, $_POST['no_telpon']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $status         = mysqli_real_escape_string($conn, $_POST['status']);
    $id_kelas       = (int)$_POST['id_kelas'];
    $id_tahun       = (int)$_POST['id_tahun'];

    // Logika Real-time Tanggal Keluar & Keterangan
    $tanggal_keluar = "NULL";
    $keterangan     = "NULL";

    // Ambil semester dari tahun ajaran yang sedang aktif untuk id_tahun tertentu
    function getSemesterAktif($conn, $id_tahun) {
    $id_tahun_esc = (int)$id_tahun;
    $query = mysqli_query($conn, "SELECT semester FROM tahun_ajaran 
                                   WHERE id_tahun = $id_tahun_esc 
                                         AND status = 'Aktif' AND deleted = 0
                                   LIMIT 1");
    $row = mysqli_fetch_assoc($query);
    return $row['semester'] ?? null;
    }

    if ($status == 'Keluar' || $status == 'Dropout' || $status == 'Nonaktif') {
        $tanggal_keluar = "'" . date('Y-m-d') . "'";
        $keterangan     = !empty($_POST['keterangan']) ? "'" . mysqli_real_escape_string($conn, $_POST['keterangan']) . "'" : "NULL";
    } elseif ($status == 'Lulus') {
        $hari_ini       = date('j ') . nama_bulan(date('n')) . date(' Y');
        $keterangan     = "'siswa sudah lulus pada tanggal " . $hari_ini . " secara real time'";
    }

    // --- PROSES TAMBAH DATA (INSERT) ---
    if ($action == 'insert') {
        $check_nis = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE nis = '$nis'");
        if (mysqli_num_rows($check_nis) > 0) {
            setFlash('danger', 'NIS Sudah Terpakai', 'Nomor Induk Siswa (NIS) tersebut sudah terdaftar di sistem.');
            header("Location: " . $redirect_url);
            exit;
        }

        $check_nisn = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE nisn = '$nisn'");
        if (mysqli_num_rows($check_nisn) > 0) {
            setFlash('danger', 'NISN Sudah Terpakai', 'Nomor Induk Siswa Nasional (NISN) tersebut sudah digunakan.');
            header("Location: " . $redirect_url);
            exit;
        }

        mysqli_begin_transaction($conn);
        try {
            $sql_siswa = "INSERT INTO siswa (id_siswa, nis, nama_siswa, jk, alamat, tempat_lahir, tanggal_lahir, nisn, agama, nik, no_telpon, status, tanggal_keluar, keterangan, id_tahun) 
                          VALUES ('$id_siswa', '$nis', '$nama_siswa', '$jk', '$alamat', '$tempat_lahir', $tanggal_lahir, '$nisn', '$agama', '$nik', '$no_telpon', '$status', $tanggal_keluar, $keterangan, $id_tahun)";
            mysqli_query($conn, $sql_siswa);

            $semester_aktif = getSemesterAktif($conn, $id_tahun);
            $semester_sql   = $semester_aktif !== null ? "'$semester_aktif'" : "NULL";

            $sql_sk = "INSERT INTO siswa_kelas (id_siswa, id_kelas, id_tahun, semester, status) 
                        VALUES ('$id_siswa', $id_kelas, $id_tahun, $semester_sql, 'Aktif')";
            mysqli_query($conn, $sql_sk);

            mysqli_commit($conn);
            setFlash('success', 'Siswa Ditambahkan', 'Data profil siswa baru sukses disimpan.');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setFlash('danger', 'Gagal Sistem', 'Terjadi kesalahan database saat menyimpan data.');
        }
        header("Location: " . $redirect_url);
        exit;
    } 
    
    // --- PROSES PERBARUI DATA (UPDATE) ---
    elseif ($action == 'update') {
        $check_nis = mysqli_query($conn, "SELECT id_siswa FROM siswa WHERE nis = '$nis' AND id_siswa != '$id_siswa'");
        if (mysqli_num_rows($check_nis) > 0) {
            setFlash('danger', 'NIS Konflik', 'Perubahan ditolak! NIS tersebut sudah digunakan oleh siswa lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        mysqli_begin_transaction($conn);
        try {
            // 1. Ambil data kelas lama siswa saat ini sebelum di-update untuk verifikasi riwayat perpindahan
            $get_old_kelas = mysqli_query($conn, "SELECT id_kelas FROM siswa_kelas WHERE id_siswa = '$id_siswa' LIMIT 1");
            $kelas_lama_id = null;
            if (mysqli_num_rows($get_old_kelas) > 0) {
                $res_old = mysqli_fetch_assoc($get_old_kelas);
                $kelas_lama_id = (int)$res_old['id_kelas'];
            }

            // Tentukan kelas final yang dipilih user
            $pindah_kelas_post = !empty($_POST['pindah_kelas']) ? (int)$_POST['pindah_kelas'] : $id_kelas;

            // Logika Deteksi: Jika kelas baru berbeda dengan kelas lama, catat ke riwayat_pindah_kelas
            if ($kelas_lama_id !== null && $pindah_kelas_post !== $kelas_lama_id) {
                $ket_pindah = mysqli_real_escape_string($conn, "Perubahan kelas saat pembaharuan data profil siswa.");
                $sql_riwayat = "INSERT INTO riwayat_pindah_kelas (id_siswa, kelas_lama, kelas_baru, keterangan, tanggal) 
                                VALUES ('$id_siswa', $kelas_lama_id, $pindah_kelas_post, '$ket_pindah', NOW())";
                mysqli_query($conn, $sql_riwayat);
            }

            // 2. Update data induk siswa
            $sql_siswa = "UPDATE siswa SET nis='$nis', nama_siswa='$nama_siswa', jk='$jk', alamat='$alamat', tempat_lahir='$tempat_lahir', tanggal_lahir=$tanggal_lahir, nisn='$nisn', agama='$agama', nik='$nik', no_telpon='$no_telpon', status='$status', tanggal_keluar=$tanggal_keluar, keterangan=$keterangan, id_tahun=$id_tahun WHERE id_siswa='$id_siswa'";
            mysqli_query($conn, $sql_siswa);

            // 3. Sinkronisasi data kelas di tabel siswa_kelas
            $check_sk = mysqli_query($conn, "SELECT id FROM siswa_kelas WHERE id_siswa='$id_siswa'");

            $semester_aktif = getSemesterAktif($conn, $id_tahun);
            $semester_sql   = $semester_aktif !== null ? "'$semester_aktif'" : "NULL";

            if (mysqli_num_rows($check_sk) > 0) {
                $sql_sk = "UPDATE siswa_kelas SET id_kelas=$pindah_kelas_post, id_tahun=$id_tahun, semester=$semester_sql, status='Aktif' WHERE id_siswa='$id_siswa'";
            } else {
                $sql_sk = "INSERT INTO siswa_kelas (id_siswa, id_kelas, id_tahun, semester, status) VALUES ('$id_siswa', $pindah_kelas_post, $id_tahun, $semester_sql, 'Aktif')";
            }
            mysqli_query($conn, $sql_sk);
            
            mysqli_commit($conn);
            setFlash('success', 'Perubahan Disimpan', 'Informasi biodata siswa dan riwayat mutasi kelas berhasil diperbarui.');
        } catch (Exception $e) {
            mysqli_rollback($conn);
            setFlash('danger', 'Gagal Memperbarui', 'Gagal memperbarui data akibat kesalahan relasi database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;