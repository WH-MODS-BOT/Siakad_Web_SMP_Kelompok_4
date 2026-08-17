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

$redirect_url = "dashboard.php?page=akun";

function setFlash($type, $title, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, 
        'title' => $title,
        'message' => $message
    ];
}

// =========================================================================
// HANDLER GET (MENGHAPUS DATA / DELETE)
// =========================================================================
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    $id_akun_target = (int) $_GET['id'];
    $current_logged_username = Session::getUsername();

    // Mencegah admin menghapus akun miliknya sendiri yang sedang digunakan
    $check_self = mysqli_query($conn, "SELECT username FROM akun WHERE id_akun = $id_akun_target");
    $data_self = mysqli_fetch_assoc($check_self);
    
    if ($data_self && $data_self['username'] === $current_logged_username) {
        setFlash('danger', 'Aksi Ditolak', 'Peringatan! Anda tidak dapat menghapus akun Anda sendiri yang sedang digunakan saat ini.');
        header("Location: " . $redirect_url);
        exit;
    }

    $query = mysqli_query($conn, "DELETE FROM akun WHERE id_akun = $id_akun_target");
    if ($query) {
        setFlash('success', 'Akun Dihapus', 'Data autentikasi akun telah dimusnahkan secara permanen.');
    } else {
        setFlash('danger', 'Gagal', 'Terjadi kesalahan sistem saat memproses perintah penghapusan.');
    }
    header("Location: " . $redirect_url);
    exit;
}

// =========================================================================
// HANDLER POST (TAMBAH & PERBARUI DATA)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // --- AKSI: INSERT (TAMBAH DATA BARU) ---
    if ($action == 'insert') {
        $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
        $password = trim($_POST['password']);
        $role     = trim(mysqli_real_escape_string($conn, $_POST['role']));
        $id_guru  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";

        if ($role == 'guru' && $id_guru == 'NULL') {
            setFlash('danger', 'Validasi Gagal', 'Jika role adalah Guru, maka wajib memilih pemilik data guru pada list dropdown.');
            header("Location: " . $redirect_url);
            exit;
        }

        // Cek Duplikasi Username
        $check_user = mysqli_query($conn, "SELECT id_akun FROM akun WHERE username = '$username'");
        if (mysqli_num_rows($check_user) > 0) {
            setFlash('danger', 'Username Tersedia', 'Gagal menyimpan! Username tersebut sudah digunakan oleh akun lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        // Cek Duplikasi Guru Tertaut
        if ($id_guru !== 'NULL') {
            $check_guru = mysqli_query($conn, "SELECT id_akun FROM akun WHERE id_guru = $id_guru");
            if (mysqli_num_rows($check_guru) > 0) {
                setFlash('danger', 'Guru Telah Tertaut', 'Gagal menyimpan! Data guru tersebut sudah memiliki akun lain.');
                header("Location: " . $redirect_url);
                exit;
            }
        }

        // Enkripsi Password (Bcrypt Hash Convert)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO akun (username, password, role, id_guru, created_at) 
                VALUES ('$username', '$hashed_password', '$role', $id_guru, current_timestamp())";
                
        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Akun Berhasil Dibuat', 'Kredensial pengguna baru sukses ditambahkan ke dalam basis data.');
        } else {
            setFlash('danger', 'Gagal Query', 'Kesalahan internal saat menyimpan data baru ke database.');
        }
        header("Location: " . $redirect_url);
        exit;
    }

    // --- AKSI: UPDATE (PERBARUI DATA) ---
    if ($action == 'update') {
        $id_akun  = (int) $_POST['id_akun'];
        $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
        $password = trim($_POST['password']); // Bisa kosong jika user tidak mengubah sandi
        $role     = trim(mysqli_real_escape_string($conn, $_POST['role']));
        $id_guru  = !empty($_POST['id_guru']) ? "'" . mysqli_real_escape_string($conn, $_POST['id_guru']) . "'" : "NULL";

        if ($role == 'guru' && $id_guru == 'NULL') {
            setFlash('danger', 'Validasi Gagal', 'Tipe akun Guru wajib ditautkan dengan biodata salah satu guru yang tersedia.');
            header("Location: " . $redirect_url);
            exit;
        }
        if ($role == 'admin') {
            $id_guru = 'NULL'; // Bersihkan sisa jika admin berubah pikiran
        }

        // Cek Duplikasi Username milik record akun lain
        $check_user = mysqli_query($conn, "SELECT id_akun FROM akun WHERE username = '$username' AND id_akun != $id_akun");
        if (mysqli_num_rows($check_user) > 0) {
            setFlash('danger', 'Username Terpakai', 'Pembaruan ditolak! Username tersebut telah dimiliki oleh pengguna lain.');
            header("Location: " . $redirect_url);
            exit;
        }

        // Cek Duplikasi Guru milik record akun lain
        if ($id_guru !== 'NULL') {
            $check_guru = mysqli_query($conn, "SELECT id_akun FROM akun WHERE id_guru = $id_guru AND id_akun != $id_akun");
            if (mysqli_num_rows($check_guru) > 0) {
                setFlash('danger', 'Guru Telah Tertaut', 'Pembaruan ditolak! Pendidik terpilih sudah memiliki akun autentikasi.');
                header("Location: " . $redirect_url);
                exit;
            }
        }

        // Penyusunan Query Update Dinamis berdasarkan Password
        if (!empty($password)) {
            // Jika ada isian password baru, enkripsi dan perbarui
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE akun SET username = '$username', password = '$hashed_password', role = '$role', id_guru = $id_guru WHERE id_akun = $id_akun";
        } else {
            // Jika password dikosongkan, perbarui field sisanya dan abaikan kolom password
            $sql = "UPDATE akun SET username = '$username', role = '$role', id_guru = $id_guru WHERE id_akun = $id_akun";
        }

        if (mysqli_query($conn, $sql)) {
            setFlash('success', 'Akun Diperbarui', 'Data hak akses dan autentikasi pengguna berhasil disimpan.');
        } else {
            setFlash('danger', 'Gagal Query', 'Kesalahan internal pada pemrosesan SQL update.');
        }
        header("Location: " . $redirect_url);
        exit;
    }
}

header("Location: " . $redirect_url);
exit;