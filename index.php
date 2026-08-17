<?php
require_once 'koneksi.php';
require_once 'Session.php';

use Session\Session;

date_default_timezone_set('Asia/Jakarta');

if (Session::getUsername()) {

    if (Session::getRole() == 'admin') {
        header("Location: dashboard_admin/dashboard.php");
        exit;
    }

    if (Session::getRole() == 'guru') {
        header("Location: dashboard_guru/dashboard.php");
        exit;
    }
}

$error_message = '';
$success_message = '';

function logLogin($conn, $username, $status)
{
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO log_login(username,status,waktu)
         VALUES(?,?,NOW())"
    );

    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ss",
            $username,
            $status
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

function saveActivityLog($conn, $activity)
{
    $username = isset($_SESSION['username'])
        ? $_SESSION['username']
        : 'System';

    $role = isset($_SESSION['role'])
        ? $_SESSION['role']
        : 'Guest';

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO log_aktivitas
        (username,role,aktivitas,waktu)
        VALUES(?,?,?,NOW())"
    );

    if ($stmt) {

        mysqli_stmt_bind_param(
            $stmt,
            "sss",
            $username,
            $role,
            $activity
        );

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['login'])
) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $remember = isset($_POST['remember']);

    if (
        empty($username)
        || empty($password)
    ) {

        $error_message = "Username dan Password wajib diisi.";

    } else {

        $query = "
        SELECT
            akun.*,
            guru.nama_guru
        FROM akun
        LEFT JOIN guru
            ON akun.id_guru = guru.id_guru
        WHERE akun.username = ?
        LIMIT 1
        ";

        $stmt = mysqli_prepare(
            $conn,
            $query
        );

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "s",
                $username
            );

            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);

            $user = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);

            if (!$user) {

                logLogin(
                    $conn,
                    $username,
                    'GAGAL'
                );

                $error_message =
                    "Username tidak ditemukan.";

            } else {

                $idAkun = $user['id_akun'];
                $idGuru = $user['id_guru'];
                $role = $user['role'];

                $dbPassword = $user['password'];

                $failed =
                    (int)$user['failed_attempts'];

                $isLocked =
                    (int)$user['is_locked'];

                $mustChange =
                    (int)$user['must_change_password'];

                if ($isLocked == 1) {

                    $error_message =
                        "Akun terkunci. Hubungi administrator.";

                } elseif (
                    empty($dbPassword)
                ) {

                    $error_message =
                        "Password database tidak valid.";

                } elseif (
                    password_verify(
                        $password,
                        $dbPassword
                    )
                ) {

                    mysqli_query(
                        $conn,
                        "UPDATE akun
                        SET failed_attempts = 0,
                            last_login = NOW()
                        WHERE id_akun = $idAkun"
                    );

                    $wali = false;

                    if (!empty($idGuru)) {

                        $cekWali = mysqli_prepare(
                            $conn,
                            "SELECT id_kelas
                             FROM kelas
                             WHERE id_wali_guru = ?
                             LIMIT 1"
                        );

                        if ($cekWali) {

                            mysqli_stmt_bind_param(
                                $cekWali,
                                "s",
                                $idGuru
                            );

                            mysqli_stmt_execute(
                                $cekWali
                            );

                            mysqli_stmt_store_result(
                                $cekWali
                            );

                            if (
                                mysqli_stmt_num_rows(
                                    $cekWali
                                ) > 0
                            ) {
                                $wali = true;
                            }

                            mysqli_stmt_close(
                                $cekWali
                            );
                        }
                    }

                    Session::setSession(
                        $idAkun,
                        $idGuru,
                        $username,
                        $role,
                        $mustChange,
                        $wali
                    );

                    saveActivityLog(
                        $conn,
                        "Login ke sistem"
                    );

                    logLogin(
                        $conn,
                        $username,
                        'SUKSES'
                    );

                    if ($remember) {

                        setcookie(
                            'login_username',
                            $username,
                            time() + (86400 * 30),
                            "/"
                        );

                    } else {

                        setcookie(
                            'login_username',
                            '',
                            time() - 3600,
                            "/"
                        );
                    }

                    if (
                        $mustChange == 1
                    ) {

                        header(
                            "Location: dashboard_guru/ganti_password.php"
                        );

                        exit;
                    }

                    if (
                        strtolower($role)
                        == 'admin'
                    ) {

                        header(
                            "Location: dashboard_admin/dashboard.php"
                        );

                    } else {

                        header(
                            "Location: dashboard_guru/dashboard.php"
                        );
                    }

                    exit;

                } else {

                    $failed++;

                    if ($failed >= 3) {

                        $stmtLock =
                            mysqli_prepare(
                                $conn,
                                "UPDATE akun
                                 SET failed_attempts=?,
                                     is_locked=1,
                                     lock_time=NOW()
                                 WHERE username=?"
                            );

                        mysqli_stmt_bind_param(
                            $stmtLock,
                            "is",
                            $failed,
                            $username
                        );

                        mysqli_stmt_execute(
                            $stmtLock
                        );

                        mysqli_stmt_close(
                            $stmtLock
                        );

                        $error_message =
                            "Akun dikunci karena 3 kali gagal login.";

                    } else {

                        $stmtFail =
                            mysqli_prepare(
                                $conn,
                                "UPDATE akun
                                 SET failed_attempts=?
                                 WHERE username=?"
                            );

                        mysqli_stmt_bind_param(
                            $stmtFail,
                            "is",
                            $failed,
                            $username
                        );

                        mysqli_stmt_execute(
                            $stmtFail
                        );

                        mysqli_stmt_close(
                            $stmtFail
                        );

                        $error_message =
                            "Password salah ($failed/3)";
                    }

                    logLogin(
                        $conn,
                        $username,
                        'GAGAL'
                    );
                }
            }
        }
    }
}

$saved_username =
isset($_COOKIE['login_username'])
? $_COOKIE['login_username']
: '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SIAKAD SMPN 0</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

:root{
    --primary:#2563eb;
    --secondary:#1e40af;
    --dark:#0f172a;
}

*{
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;

    background:
    linear-gradient(
        rgba(15,23,42,.85),
        rgba(15,23,42,.85)
    ),
    url('image/bg-sekolah.jpg');

    background-size:cover;
    background-position:center;
    background-repeat:no-repeat;

    display:flex;
    align-items:center;
    justify-content:center;

    padding:20px;
}

.login-card{
    width:100%;
    max-width:1100px;
    border:none;
    border-radius:25px;
    overflow:hidden;
    box-shadow:0 20px 60px rgba(0,0,0,.3);
}

.left-panel{
    background:
    linear-gradient(
        135deg,
        #1e3a8a,
        #2563eb
    );

    color:white;
    padding:50px;
}

.right-panel{
    background:white;
    padding:50px;
}

.school-logo{
    width:90px;
}

.stat-box{
    background:rgba(255,255,255,.15);
    border-radius:15px;
    padding:15px;
    backdrop-filter:blur(10px);
}

.form-control{
    border-radius:12px;
    min-height:52px;
}

.input-group-text{
    border-radius:12px 0 0 12px;
}

.btn-login{
    background:linear-gradient(
        135deg,
        #2563eb,
        #1e40af
    );

    border:none;
    min-height:52px;
    border-radius:12px;
    font-weight:600;
}

.btn-login:hover{
    background:linear-gradient(
        135deg,
        #1e40af,
        #2563eb
    );
}

.password-toggle{
    cursor:pointer;
}

@media(max-width:991px){

    .left-panel,
    .right-panel{
        padding:30px;
    }

}

@media(max-width:768px){

    .left-panel{
        text-align:center;
    }

    .school-logo{
        width:70px;
    }

    .left-panel,
    .right-panel{
        padding:25px;
    }

}

</style>

</head>
<body>

<div class="card login-card">

<div class="row g-0">

<div class="col-lg-5 left-panel d-flex flex-column justify-content-center">

    <div class="text-center mb-4">

        <img
            src="image/departemen-pendidikan-nasional-seeklogo.png"
            class="school-logo mb-3">

        <h2 class="fw-bold">
            SIAKAD SMPN 0
        </h2>

        <p class="text-light opacity-75">
            Sistem Informasi Akademik Sekolah
        </p>

    </div>

    <?php

    $totalSiswa = 0;
    $totalGuru = 0;
    $totalKelas = 0;

    $q = mysqli_query(
        $conn,
        "SELECT COUNT(*) total FROM siswa"
    );

    if($q){
        $totalSiswa =
        mysqli_fetch_assoc($q)['total'];
    }

    $q = mysqli_query(
        $conn,
        "SELECT COUNT(*) total FROM guru"
    );

    if($q){
        $totalGuru =
        mysqli_fetch_assoc($q)['total'];
    }

    $q = mysqli_query(
        $conn,
        "SELECT COUNT(*) total FROM kelas"
    );

    if($q){
        $totalKelas =
        mysqli_fetch_assoc($q)['total'];
    }

    ?>

    <div class="row g-3">

        <div class="col-4">
            <div class="stat-box text-center">
                <h4><?= $totalSiswa ?></h4>
                <small>Siswa</small>
            </div>
        </div>

        <div class="col-4">
            <div class="stat-box text-center">
                <h4><?= $totalGuru ?></h4>
                <small>Guru</small>
            </div>
        </div>

        <div class="col-4">
            <div class="stat-box text-center">
                <h4><?= $totalKelas ?></h4>
                <small>Kelas</small>
            </div>
        </div>

    </div>

</div>

<div class="col-lg-7 right-panel d-flex flex-column justify-content-center">

    <h3 class="fw-bold mb-2">
        Selamat Datang 👋
    </h3>

    <p class="text-muted mb-4">
        Silakan login menggunakan akun Anda.
    </p>

    <?php if(!empty($error_message)): ?>

    <div class="alert alert-danger">
        <?= htmlspecialchars($error_message) ?>
    </div>

    <?php endif; ?>

    <form method="POST" id="loginForm">

        <div class="mb-3">

            <label class="form-label">
                Username
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-person"></i>
                </span>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    value="<?= htmlspecialchars($saved_username) ?>"
                    required>

            </div>

        </div>

        <div class="mb-3">

            <label class="form-label">
                Password
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required>

                <span
                    class="input-group-text password-toggle"
                    id="togglePassword">

                    <i class="bi bi-eye"></i>

                </span>

            </div>

        </div>

        <div class="d-flex justify-content-between mb-4">

            <div class="form-check">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="remember"
                    id="remember">

                <label
                    class="form-check-label"
                    for="remember">

                    Ingat Saya

                </label>

            </div>

        </div>

        <button
            type="submit"
            name="login"
            class="btn btn-primary btn-login w-100">

            <span id="btnText">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk ke Dashboard
            </span>

            <span
                id="loading"
                class="spinner-border spinner-border-sm d-none">
            </span>

        </button>

    </form>

    <div class="text-center mt-4 text-muted">

        © <?= date('Y') ?>
        SMP Negeri 0

    </div>

</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

const password =
document.getElementById('password');

const toggle =
document.getElementById('togglePassword');

toggle.addEventListener('click',()=>{

    if(password.type === 'password'){

        password.type='text';

        toggle.innerHTML=
        '<i class="bi bi-eye-slash"></i>';

    }else{

        password.type='password';

        toggle.innerHTML=
        '<i class="bi bi-eye"></i>';

    }

});

document
.getElementById('loginForm')
.addEventListener('submit',function(){

    document
    .getElementById('btnText')
    .classList.add('d-none');

    document
    .getElementById('loading')
    .classList.remove('d-none');

});

</script>

</body>
</html>