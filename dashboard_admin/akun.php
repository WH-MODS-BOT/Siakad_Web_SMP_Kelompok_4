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

if(Session::getRole()!='admin'){
    header("Location: ../index.php");
    exit;
}

// 2. Memanggil Koneksi Database
if (file_exists('../koneksi.php')) {
    require_once '../koneksi.php';
} else {
    require_once 'koneksi.php';
}

// 3. Menangani Flash Message Session Notifikasi
$toast_msg = null;
if (isset($_SESSION['flash_msg'])) {
    $toast_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// 4. Ambil Daftar Guru Aktif untuk Dropdown
$query_guru = mysqli_query($conn, "SELECT id_guru, nama_guru FROM guru WHERE deleted = 0 ORDER BY nama_guru ASC");
$list_guru = [];
while ($row = mysqli_fetch_assoc($query_guru)) {
    $list_guru[] = $row;
}

// Ambil id_guru yang sudah punya akun untuk filter di JS
$query_terpakai = mysqli_query($conn, "SELECT id_guru FROM akun WHERE id_guru IS NOT NULL");
$guru_terpakai = [];
while ($row_t = mysqli_fetch_assoc($query_terpakai)) {
    $guru_terpakai[] = $row_t['id_guru'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelola Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        
        /* Toast Notification */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 1060; }
        .custom-toast { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 6px solid #0d6efd; overflow: hidden; }
        .custom-toast.success { border-left-color: #198754; }
        .custom-toast.danger { border-left-color: #dc3545; }
        .custom-toast.warning { border-left-color: #ffc107; }
        .custom-toast.info { border-left-color: #0dcaf0; }
        
        /* Hover tabel agar terlihat interaktif saat diklik */
        .table-hover tbody tr { cursor: pointer; transition: background-color 0.2s; }
        .table-hover tbody tr:hover { background-color: #f1f5f9; }
        .row-active { background-color: #e0f2fe !important; border-left: 4px solid #0284c7; }
    </style>
</head>
<body>

<div class="toast-container">
    <?php if ($toast_msg): 
        $icon = 'bi-info-circle-fill';
        if($toast_msg['type'] == 'success') $icon = 'bi-check-circle-fill text-success';
        if($toast_msg['type'] == 'danger') $icon = 'bi-exclamation-triangle-fill text-danger';
        if($toast_msg['type'] == 'warning') $icon = 'bi-exclamation-circle-fill text-warning';
    ?>
    <div id="statusToast" class="toast custom-toast p-3 <?= $toast_msg['type'] ?>" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-start">
            <i class="bi <?= $icon ?> fs-4 me-3 mt-1"></i>
            <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($toast_msg['title']) ?></h6>
                <p class="text-muted small mb-0"><?= htmlspecialchars($toast_msg['message']) ?></p>
            </div>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="container px-4 mt-5 mb-5">
    
    <div class="mb-4">
        <h3 class="fw-bold text-dark m-0">Data Autentikasi Pengguna</h3>
        <p class="text-muted small m-0">Kelola username, hak akses (role), dan integrasi akun guru.</p>
    </div>

    <div class="card shadow-sm mb-4 border-top border-primary border-4" id="formCard">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold m-0" id="formTitle"><i class="bi bi-person-plus-fill text-primary me-2"></i> Tambah Akun Baru</h5>
        </div>
        <div class="card-body p-4">
            <form id="formAkun" method="POST" action="proses_akun.php">
                <input type="hidden" name="action" id="form_action" value="insert">
                <input type="hidden" name="id_akun" id="id_akun">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" id="username" required placeholder="Masukkan username unik">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger" id="req_pass">*</span></label>
                        <input type="password" class="form-control" name="password" id="password" required placeholder="Ketik kata sandi">
                        <div class="form-text text-muted small d-none" id="help_pass">Kosongkan jika tidak ingin mengubah password lama.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Hak Akses (Role) <span class="text-danger">*</span></label>
                        <select class="form-select text-capitalize" name="role" id="role" required onchange="toggleGuruField()">
                            <option value="">-- Pilih Akses --</option>
                            <option value="admin">Admin</option>
                            <option value="guru">Guru</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-none" id="container_guru">
                        <label class="form-label fw-semibold">Integrasi Data Guru <span class="text-danger">*</span></label>
                        <select class="form-select" name="id_guru" id="id_guru">
                            <option value="">-- Pilih Guru Pemilik Akun --</option>
                            <?php foreach ($list_guru as $guru) {
                                $terpakai = in_array($guru['id_guru'], $guru_terpakai) ? 'true' : 'false';
                                echo "<option value='".htmlspecialchars($guru['id_guru'])."' data-terpakai='{$terpakai}'>".htmlspecialchars($guru['nama_guru'])."</option>";
                            } ?>
                        </select>
                        <div class="form-text text-muted small">Guru yang telah memiliki akun akan disembunyikan.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary px-4" id="btnCancel" onclick="batalEdit()" style="display: none;">Batal Edit</button>
                    <button type="reset" class="btn btn-outline-warning px-4" id="btnReset" onclick="resetFormToTambah()">Reset</button>
                    
                    <button type="submit" class="btn btn-primary px-4" id="btnSimpan"><i class="bi bi-floppy-fill me-1"></i> Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold m-0"><i class="bi bi-table text-secondary me-2"></i> Daftar Akun Sistem</h6>
            <span class="badge bg-primary rounded-pill">Klik baris untuk mengedit data</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tabelAkun">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th style="width: 70px;" class="ps-4">No</th>
                            <th>Username</th>
                            <th>Hak Akses (Role)</th>
                            <th>Nama Guru Tertaut</th>
                            <th class="text-center" style="width: 100px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT a.id_akun, a.username, a.role, a.id_guru, g.nama_guru 
                                FROM akun a 
                                LEFT JOIN guru g ON a.id_guru = g.id_guru 
                                ORDER BY a.role ASC, a.username ASC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) { 
                                $jsonData = htmlspecialchars(json_encode($row));
                                ?>
                                <tr onclick="prepareEditForm(<?= $jsonData ?>, this)" id="row_<?= $row['id_akun'] ?>">
                                    <td class="ps-4 text-muted fw-semibold"><?= $no++ ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($row['username']) ?></td>
                                    <td>
                                        <span class="badge <?= $row['role'] == 'admin' ? 'bg-danger' : 'bg-primary' ?> text-uppercase">
                                            <i class="bi <?= $row['role'] == 'admin' ? 'bi-shield-lock-fill' : 'bi-person-workspace' ?> me-1"></i> <?= htmlspecialchars($row['role']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted">
                                        <?= !empty($row['nama_guru']) ? htmlspecialchars($row['nama_guru']) : '<i class="bi bi-dash"></i> Tidak Terikat' ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="proses_akun.php?action=delete&id=<?= $row['id_akun'] ?>" class="btn btn-sm btn-outline-danger border-0" 
                                           onclick="event.stopPropagation(); return confirm('Peringatan! Menghapus akun ini akan memutuskan akses pengguna secara permanen. Lanjutkan?')" 
                                           title="Hapus Akun">
                                            <i class="bi bi-trash3-fill fs-6"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-database-x fs-2 d-block mb-2"></i> Belum ada data akun.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const formAkun = document.getElementById('formAkun');
    const containerGuru = document.getElementById('container_guru');
    const roleSelect = document.getElementById('role');
    const guruSelect = document.getElementById('id_guru');
    const passInput = document.getElementById('password');
    const reqPass = document.getElementById('req_pass');
    const helpPass = document.getElementById('help_pass');

    // Memicu Toast jika ada session flash
    document.addEventListener("DOMContentLoaded", function() {
        const toastEl = document.getElementById('statusToast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        }
    });

    // Menampilkan dropdown guru hanya jika role == 'guru'
    function toggleGuruField(editGuruId = null) {
        if (roleSelect.value === 'guru') {
            containerGuru.classList.remove('d-none');
            guruSelect.required = true;
            
            // Logika menyembunyikan guru yang sudah punya akun
            const options = document.querySelectorAll('#id_guru option');
            options.forEach(opt => {
                if (opt.value === "") {
                    opt.style.display = 'block';
                } else if (opt.value === editGuruId) {
                    // Tampilkan guru milik akun ini sendiri saat di mode edit
                    opt.style.display = 'block';
                } else if (opt.getAttribute('data-terpakai') === 'true') {
                    // Sembunyikan guru yang sudah memiliki akun lain
                    opt.style.display = 'none';
                } else {
                    opt.style.display = 'block';
                }
            });
        } else {
            containerGuru.classList.add('d-none');
            guruSelect.required = false;
            guruSelect.value = '';
        }
    }

    // Fungsi memuat data ke Form Edit
    function prepareEditForm(data, rowElement) {
        // Hapus highlight dari semua baris
        document.querySelectorAll('#tabelAkun tbody tr').forEach(tr => tr.classList.remove('row-active'));
        // Highlight baris yang sedang diedit
        rowElement.classList.add('row-active');

        // Ganti UI menjadi Mode Edit
        document.getElementById('formTitle').innerHTML = "<i class='bi bi-pencil-square text-warning me-2'></i> Ubah Data Akun";
        document.getElementById('form_action').value = "update";
        
        document.getElementById('btnReset').style.display = "none";
        document.getElementById('btnCancel').style.display = "block";

        // Isi Field Form
        document.getElementById('id_akun').value = data.id_akun;
        document.getElementById('username').value = data.username;
        roleSelect.value = data.role;
        
        // Atur status field password (tidak wajib diisi jika edit)
        passInput.required = false;
        reqPass.classList.add('d-none');
        helpPass.classList.remove('d-none');
        passInput.value = ""; // Kosongkan placeholder password aslinya

        // Sinkronisasi Role & Field Guru
        toggleGuruField(data.id_guru);
        if (data.role === 'guru') {
            guruSelect.value = data.id_guru ? data.id_guru : "";
        }

        // Scroll otomatis ke form
        document.getElementById('formCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Fungsi membatalkan Edit & mengembalikan ke Mode Tambah Baru
    function batalEdit() {
        document.querySelectorAll('#tabelAkun tbody tr').forEach(tr => tr.classList.remove('row-active'));
        resetFormToTambah();
    }

    // Fungsi mereset total Form ke kondisi awal (Insert)
    function resetFormToTambah() {
        formAkun.reset();
        document.getElementById('formTitle').innerHTML = "<i class='bi bi-person-plus-fill text-primary me-2'></i> Tambah Akun Baru";
        document.getElementById('form_action').value = "insert";
        document.getElementById('id_akun').value = "";
        
        document.getElementById('btnReset').style.display = "block";
        document.getElementById('btnCancel').style.display = "none";

        passInput.required = true;
        reqPass.classList.remove('d-none');
        helpPass.classList.add('d-none');

        toggleGuruField(); // Sembunyikan field guru
    }
</script>
</body>
</html>