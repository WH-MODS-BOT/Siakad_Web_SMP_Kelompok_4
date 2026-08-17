<?php
// Set timezone agar tanggal & waktu selalu akurat (WIB)
date_default_timezone_set('Asia/Jakarta');

require_once "../koneksi.php";
require_once "../Session.php";

use Session\Session;

// Proteksi halaman: Pastikan hanya role guru yang bisa mengakses
if (Session::getRole() != "guru") {
    header("Location: ../index.php");
    exit;
}

/** @var mysqli $conn */

// Mengambil Username
$username = Session::getUsername();

$pesan_sukses = '';
$pesan_error  = '';

// === PROSES UBAH PASSWORD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_baru'])) {
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi    = $_POST['konfirmasi_password'] ?? '';

    // Validasi form
    if (empty($password_baru) || empty($konfirmasi)) {
        $_SESSION['flash_msg'] = [
            'type'    => 'error',
            'title'   => 'Gagal Menyimpan!',
            'message' => 'Semua kolom password wajib diisi.'
        ];
    } elseif ($password_baru !== $konfirmasi) {
        $_SESSION['flash_msg'] = [
            'type'    => 'error',
            'title'   => 'Gagal Menyimpan!',
            'message' => 'Konfirmasi password tidak cocok.'
        ];
    } elseif (strlen($password_baru) < 6) {
        $_SESSION['flash_msg'] = [
            'type'    => 'error',
            'title'   => 'Gagal Menyimpan!',
            'message' => 'Password minimal harus 6 karakter.'
        ];
    } else {
        // Encrypt password menggunakan BCRYPT (sesuai format $2y$ pada tabel akun)
        $hashed_password = password_hash($password_baru, PASSWORD_BCRYPT);
        $pass_esc         = mysqli_real_escape_string($conn, $hashed_password);
        $username_esc     = mysqli_real_escape_string($conn, $username);

        // Update password & reset flag must_change_password
        $query_update = "UPDATE akun 
                         SET password = '$pass_esc', must_change_password = 0 
                         WHERE username = '$username_esc'";

        if (mysqli_query($conn, $query_update)) {
            $_SESSION['flash_msg'] = [
                'type'    => 'success',
                'title'   => 'Berhasil!',
                'message' => 'Password akun berhasil diperbarui.'
            ];

            header("Location: dashboard.php?page=password");
            exit;
        } else {
            $_SESSION['flash_msg'] = [
                'type'    => 'error',
                'title'   => 'Error SQL!',
                'message' => 'Gagal mengubah password: ' . mysqli_error($conn)
            ];
        }
    }

    header("Location: dashboard.php?page=password");
    exit;
}
?>

<!-- HEADER (Judul Page) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Pengaturan Password</h4>
        <p class="text-muted small mb-0">Ubah password akun Anda untuk menjaga keamanan data</p>
    </div>    
</div>

<!-- FORM CARD -->
<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">

                <form method="POST" action="dashboard.php?page=password" id="form-password">
                    
                    <!-- INPUT PASSWORD BARU -->
                    <div class="mb-3">
                        <label for="password_baru" class="form-label small fw-bold">Masukkan Password Baru :</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary border-end-0">
                                <i class="bi bi-key"></i>
                            </span>
                            <input type="password" name="password_baru" id="password_baru" class="form-control border-start-0 border-end-0" placeholder="Ketik password baru..." required minlength="6">
                            <button class="btn btn-outline-secondary btn-toggle-pass" type="button" data-target="password_baru" title="Lihat Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- INPUT KONFIRMASI PASSWORD -->
                    <div class="mb-4">
                        <label for="konfirmasi_password" class="form-label small fw-bold">Konfirmasi Password Baru :</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-primary border-end-0">
                                <i class="bi bi-shield-lock"></i>
                            </span>
                            <input type="password" name="konfirmasi_password" id="konfirmasi_password" class="form-control border-start-0 border-end-0" placeholder="Ulangi password baru..." required minlength="6">
                            <button class="btn btn-outline-secondary btn-toggle-pass" type="button" data-target="konfirmasi_password" title="Lihat Password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="text-end">
                        <button type="submit" id="btn-simpan" class="btn btn-primary fw-semibold">
                            <i class="bi bi-floppy-fill me-1"></i> Simpan Password
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<!-- Load SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Otomatis bersihkan URL browser menjadi ?page=password
if (window.history.replaceState) {
    window.history.replaceState(null, null, "dashboard.php?page=password");
}

// Toggle Show/Hide Password
document.querySelectorAll('.btn-toggle-pass').forEach(function(button) {
    button.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const input    = document.getElementById(targetId);
        const icon     = this.querySelector('i');
        
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
});

// Inisialisasi SweetAlert2 Toast Mixin
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

// Validasi Form sebelum Submit
const formPassword = document.getElementById('form-password');
if (formPassword) {
    formPassword.addEventListener('submit', function(e) {
        const passBaru   = document.getElementById('password_baru').value.trim();
        const konfirmasi = document.getElementById('konfirmasi_password').value.trim();

        if (passBaru === '' || konfirmasi === '') {
            e.preventDefault();
            Toast.fire({
                icon: 'error',
                title: 'Gagal Menyimpan!',
                text: 'Silakan isi semua kolom password.'
            });
            return;
        }

        if (passBaru !== konfirmasi) {
            e.preventDefault();
            Toast.fire({
                icon: 'error',
                title: 'Gagal Menyimpan!',
                text: 'Konfirmasi password tidak cocok dengan password baru.'
            });
            return;
        }

        if (passBaru.length < 6) {
            e.preventDefault();
            Toast.fire({
                icon: 'error',
                title: 'Gagal Menyimpan!',
                text: 'Password minimal harus 6 karakter.'
            });
            return;
        }
    });
}
</script>

<!-- Render Flash Message dari PHP Session menggunakan SweetAlert2 Toast -->
<?php if (isset($_SESSION['flash_msg'])): ?>
<script>
    Toast.fire({
        icon: '<?= $_SESSION['flash_msg']['type'] ?>',
        title: '<?= $_SESSION['flash_msg']['title'] ?>',
        text: '<?= $_SESSION['flash_msg']['message'] ?>'
    });
</script>
<?php 
    unset($_SESSION['flash_msg']);
endif; 
?>