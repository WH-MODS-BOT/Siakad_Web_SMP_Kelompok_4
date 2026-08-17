<?php
require_once 'Session.php';
require_once 'koneksi.php';

use Session\Session;

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil data user sebelum session dihapus
$username = Session::getUsername();
$role     = Session::getRole();

// Simpan log aktivitas logout
if (!empty($username)) {

    $display_role = ucfirst(strtolower($role));
    $aktivitas    = "Logout dari sistem";

    $sql = "INSERT INTO log_aktivitas
            (username, role, aktivitas, waktu)
            VALUES (?, ?, ?, NOW())";

    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $username,
            $display_role,
            $aktivitas
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Hapus cookie remember me jika ada
if (isset($_COOKIE['login_username'])) {
    setcookie(
        'login_username',
        '',
        time() - 3600,
        '/'
    );
}

// Redirect ke halaman login
header("Location: index.php");
exit;
?>