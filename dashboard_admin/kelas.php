<?php
// 1. Memanggil Session & Autentikasi Admin
require_once '../Session.php';

use Session\Session;

if(Session::getRole()!='admin'){
    header("Location: ../index.php");
    exit;
}

// 2. Memanggil Koneksi Database
require_once '../koneksi.php'; 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// PERBAIKAN UTAMA: MEMBACA FLASH MESSAGE DARI SESSION (BUKAN URL GET)
// =========================================================================
$toast_msg = null;
if (isset($_SESSION['flash_msg'])) {
    $toast_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']); // Langsung dihapus agar tidak muncul lagi saat page di-refresh
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Kelas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        .bg-gradient-dark { background: linear-gradient(45deg, #212529, #343a40); }

        /* Style Kustom Toast Notification Mengambang */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 1060; }
        .custom-toast { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 6px solid #0d6efd; overflow: hidden; }
        .custom-toast.success { border-left-color: #198754; }
        .custom-toast.danger { border-left-color: #dc3545; }
        .custom-toast.warning { border-left-color: #ffc107; }
        .custom-toast.info { border-left-color: #0dcaf0; }
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

<div class="container px-4 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Data Manajemen Kelas</h3>
            <p class="text-muted small m-0">Kelola ruang kelas, kapasitas siswa, serta penugasan Guru Wali Kelas.</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalKelas" onclick="prepareTambahForm()">
                <i class="bi bi-plus-circle-fill"></i> Tambah Kelas
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="kelas">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="search_input" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan Kode Kelas atau Nama Kelas..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary w-100 fw-semibold">Cari Data</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th style="width: 80px;" class="ps-4">No.</th>
                            <th>Kode Kelas</th>
                            <th>Nama Kelas</th>
                            <th>Kapasitas</th>
                            <th>Wali Kelas (Guru)</th>
                            <th class="text-center" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                        
                        // Query JOIN untuk mendapatkan nama_guru dari tabel guru berdasarkan id_wali_guru
                        $sql = "SELECT kelas.*, guru.nama_guru FROM kelas 
                                LEFT JOIN guru ON kelas.id_wali_guru = guru.id_guru";
                        
                        if ($search != '') {
                            $sql .= " WHERE kelas.kode_kelas LIKE '%$search%' OR kelas.nama_kelas LIKE '%$search%' OR guru.nama_guru LIKE '%$search%'";
                        }
                        $sql .= " ORDER BY kelas.kode_kelas ASC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($row['kode_kelas']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                    <td><span class="badge bg-info text-dark fw-bold"><?= $row['kapasitas'] ?> Siswa</span></td>
                                    <td>
                                        <?php if (!empty($row['id_wali_guru'])): ?>
                                            <i class="bi bi-person-badge text-primary me-1"></i><?= htmlspecialchars($row['nama_guru']) ?>
                                        <?php else: ?>
                                            <span class="text-muted italic small"><i class="bi bi-dash-circle me-1"></i> Belum Ditunjuk</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-warning text-dark me-1" data-bs-toggle="modal" data-bs-target="#modalKelas" onclick='prepareEditForm(<?= json_encode($row) ?>)'><i class="bi bi-pencil-square"></i></button>
                                        <a href="proses_kelas.php?action=delete&id=<?= $row['id_kelas'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data kelas <?= htmlspecialchars($row['nama_kelas']) ?> secara permanen?')"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2"></i> Belum ada data kelas ditemukan.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalKelas" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalKelasTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalKelasTitle">Tambah Kelas Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formKelas" method="POST" action="proses_kelas.php">
                    <input type="hidden" name="action" id="form_action" value="insert">
                    <input type="hidden" name="id_kelas" id="id_kelas">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kode Kelas</label>
                            <input type="text" class="form-control" name="kode_kelas" id="kode_kelas" required placeholder="Contoh: VII-A">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Kelas</label>
                            <input type="text" class="form-control" name="nama_kelas" id="nama_kelas" required placeholder="Contoh: VII A">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kapasitas Maksimal (Siswa)</label>
                            <input type="number" class="form-control" name="kapasitas" id="kapasitas" required min="1" max="100" value="30">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Wali Kelas (Guru Pendidik)</label>
                            <select class="form-select" name="id_wali_guru" id="id_wali_guru">
                                <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                                <?php
                                // Ambil semua guru yang aktif (deleted = 0)
                                $query_guru = mysqli_query($conn, "SELECT id_guru, nama_guru FROM guru WHERE deleted = 0 ORDER BY nama_guru ASC");
                                
                                // Ambil daftar id_guru yang sudah menjadi wali kelas di tabel kelas
                                $query_terpakai = mysqli_query($conn, "SELECT id_wali_guru FROM kelas WHERE id_wali_guru IS NOT NULL");
                                $guru_terpakai = [];
                                while ($row_t = mysqli_fetch_assoc($query_terpakai)) {
                                    $guru_terpakai[] = $row_t['id_wali_guru'];
                                }

                                while ($guru = mysqli_fetch_assoc($query_guru)) {
                                    // Berikan atribut data-terpakai="true" jika guru sudah mengajar di kelas lain
                                    $is_terpakai = in_array($guru['id_guru'], $guru_terpakai) ? 'true' : 'false';
                                    
                                    echo "<option value='".htmlspecialchars($guru['id_guru'])."' data-terpakai='{$is_terpakai}'>".htmlspecialchars($guru['nama_guru'])."</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-warning px-4" id="btnReset" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const formKelas = document.getElementById('formKelas');

    // Menjalankan pop-up toast otomatis jika session flash terdeteksi
    document.addEventListener("DOMContentLoaded", function() {
        const toastEl = document.getElementById('statusToast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        }
    });

    function prepareTambahForm() {
    formKelas.reset();
    document.getElementById('modalKelasTitle').innerText = "Tambah Ruang Kelas Baru";
    document.getElementById('form_action').value = "insert";
    document.getElementById('btnReset').style.display = "block";

    // SEMBUNYIKAN guru yang sudah menjadi wali kelas di tempat lain
    const options = document.querySelectorAll('#id_wali_guru option');
    options.forEach(opt => {
        if (opt.getAttribute('data-terpakai') === 'true') {
            opt.style.display = 'none';
        } else {
            opt.style.display = 'block';
        }
    });
}

    function prepareEditForm(data) {
    formKelas.reset();
    document.getElementById('modalKelasTitle').innerText = "Ubah Informasi Kelas";
    document.getElementById('form_action').value = "update";
    document.getElementById('id_kelas').value = data.id_kelas;
    document.getElementById('kode_kelas').value = data.kode_kelas;
    document.getElementById('nama_kelas').value = data.nama_kelas;
    document.getElementById('kapasitas').value = data.kapasitas;
    
    // TAMPILKAN/SEMBUNYIKAN guru berdasarkan status kelas saat ini
    const options = document.querySelectorAll('#id_wali_guru option');
    options.forEach(opt => {
        // Jika id_guru sama dengan wali kelas di data ini, tetap tampilkan meskipun 'data-terpakai' bernilai true
        if (opt.value === data.id_wali_guru) {
            opt.style.display = 'block';
        } else if (opt.getAttribute('data-terpakai') === 'true') {
            opt.style.display = 'none'; // Sembunyikan milik kelas lain
        } else {
            opt.style.display = 'block'; // Tampilkan yang kosong
        }
    });

    document.getElementById('id_wali_guru').value = data.id_wali_guru ? data.id_wali_guru : "";
    document.getElementById('btnReset').style.display = "none";
}

    function resetForm() {
        formKelas.reset();
    }
</script>
</body>
</html>