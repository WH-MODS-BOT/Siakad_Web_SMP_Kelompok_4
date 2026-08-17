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

// 3. Ambil semua daftar tahun unik dari tabel `tahun`
$list_tahun = [];
$query_t = mysqli_query($conn, "SELECT * FROM tahun ORDER BY tahun DESC");
if ($query_t && mysqli_num_rows($query_t) > 0) {
    while ($row_t = mysqli_fetch_assoc($query_t)) {
        $list_tahun[] = $row_t;
    }
}

// 4. Membaca Flash Message Session Notifikasi Pop-up (Bersih dari URL)
$toast_msg = null;
if (isset($_SESSION['flash_msg'])) {
    $toast_msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tahun Ajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        .bg-gradient-dark { background: linear-gradient(45deg, #212529, #343a40); }

        /* Style Mengambang Toast Notification */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 1060; }
        .custom-toast { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 6px solid #0d6efd; overflow: hidden; }
        .custom-toast.success { border-left-color: #198754; }
        .custom-toast.danger { border-left-color: #dc3545; }
        .custom-toast.warning { border-left-color: #ffc107; }
        .custom-toast.info { border-left-color: #0dcaf0; }
        .table-success-light { background-color: #e8f5e9 !important; }

        .table-success-light { 
        background-color: #e8f5e9 !important; 
        }
        .table-success-light td { 
            background-color: #e8f5e9 !important; 
        }
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
            <h3 class="fw-bold text-dark m-0">Data Tahun Ajaran & Semester</h3>
            <p class="text-muted small m-0">Sistem mengunci otomatis agar hanya ada 1 tahun ajaran yang AKTIF beroperasi.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCache">
                <i class="bi bi-archive-fill"></i> Cache Backup
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTA" onclick="prepareTambahForm()">
                <i class="bi bi-plus-circle-fill"></i> Tambah Periode
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="tahun">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="search_input" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan Tahun Ajaran atau Semester..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
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
                            <th style="width: 80px;" class="ps-4">No</th>
                            <th>Tahun Ajaran</th>
                            <th>Semester</th>
                            <th>Status Administrasi</th>
                            <th class="text-center" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                        
                        // PERBAIKAN: Menggunakan LOWER(ta.status) AS status_clean agar data sinkron dengan pengecekan PHP
                        $sql = "SELECT ta.id_tahun_ajaran, ta.id_tahun, ta.semester, ta.deleted, 
                                    LOWER(TRIM(ta.status)) AS status, t.tahun 
                                FROM tahun_ajaran ta 
                                JOIN tahun t ON ta.id_tahun = t.id_tahun 
                                WHERE ta.deleted = 0";
                                
                        if ($search != '') {
                            $sql .= " AND (t.tahun LIKE '%$search%' OR ta.semester LIKE '%$search%')";
                        }
                        
                        // Urutkan agar status yang AKTIF selalu berada di posisi paling atas tabel
                        $sql .= " ORDER BY ta.status ASC, t.tahun DESC, ta.semester ASC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) { 
                                // Sekarang pengecekan di bawah ini dijamin 100% akurat dan valid
                                $is_aktif = ($row['status'] === 'aktif');
                                ?>
                                <tr class="<?= $is_aktif ? 'table-success-light' : '' ?>">
                                    <td class="ps-4 text-muted"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border fw-bold fs-6 px-3 py-2"><?= htmlspecialchars($row['tahun']) ?></span></td>
                                    <td class="fw-semibold text-dark text-capitalize"><?= htmlspecialchars($row['semester']) ?></td>
                                    <td>
                                        <?php if ($is_aktif): ?>
                                            <span class="badge bg-success px-3 py-2"><i class="bi bi-check-circle-fill me-1"></i> Aktif Berjalan</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-2">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-warning text-dark me-1" data-bs-toggle="modal" data-bs-target="#modalTA" onclick='prepareEditForm(<?= json_encode($row) ?>)'><i class="bi bi-pencil-square"></i></button>
                                        
                                        <?php if ($is_aktif): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" disabled title="Tahun ajaran aktif tidak boleh dihapus"><i class="bi bi-trash-fill"></i></button>
                                        <?php else: ?>
                                            <a href="proses_tahun_ajaran.php?action=soft_delete&id=<?= $row['id_tahun_ajaran'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin memindahkan periode ini ke Cache Backup?')"><i class="bi bi-trash-fill"></i></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2"></i> Belum ada data aktif tersedia.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTA" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTATitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalTATitle">Tambah Tahun Ajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formTA" method="POST" action="proses_tahun_ajaran.php">
                    <input type="hidden" name="action" id="form_action" value="insert">
                    <input type="hidden" name="id_tahun_ajaran" id="id_tahun_ajaran">
                    <input type="hidden" name="is_tahun_baru" id="is_tahun_baru" value="0">
                    
                    <input type="hidden" name="id_tahun_hidden" id="id_tahun_hidden">

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tahun Ajaran</label>
                            
                            <div id="containerSelectTahun">
                                <div class="input-group">
                                    <select class="form-select fw-bold text-primary" name="id_tahun" id="select_tahun_ajaran" required>
                                        <option value="">-- Pilih Tahun --</option>
                                        <?php foreach ($list_tahun as $th) {
                                            echo "<option value='{$th['id_tahun']}'>".htmlspecialchars($th['tahun'])."</option>";
                                        } ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="btnTahunBaru" onclick="pemicuTahunBaru()"><i class="bi bi-plus-lg"></i> Tahun Baru</button>
                                </div>
                            </div>

                            <div id="containerInputTahun" class="d-none">
                                <div class="input-group">
                                    <input type="text" class="form-control fw-bold text-primary" name="tahun_baru" id="input_tahun_baru" placeholder="Contoh: 2026/2027">
                                    <button type="button" class="btn btn-outline-danger" onclick="batalTahunBaru()"><i class="bi bi-x-lg"></i> Batal</button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Semester</label>
                            <select class="form-select text-capitalize" name="semester" id="semester" required>
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Status Aktivitas</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="nonaktif">Nonaktif</option>
                                <option value="aktif">Aktif</option>
                            </select>
                            <div class="form-text text-muted small mt-1"><i class="bi bi-info-circle"></i> Jika diset <strong>Aktif</strong>, secara otomatis tahun/semester lainnya akan menjadi Nonaktif.</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-warning px-4" id="btnReset" onclick="resetForm()">Reset</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCache" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalCacheTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalCacheTitle"><i class="bi bi-archive"></i> Cache Backup Periode</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>
            </div>
            <form action="proses_tahun_ajaran.php" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small">Daftar arsip tahun ajaran yang dihapus sementara.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center"><input type="checkbox" id="checkAll" onclick="toggleSelectAll(this)"></th>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th>Status Eks-Administrasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_cache = mysqli_query($conn, "SELECT ta.*, t.tahun FROM tahun_ajaran ta JOIN tahun t ON ta.id_tahun = t.id_tahun WHERE ta.deleted = 1 ORDER BY ta.id_tahun_ajaran DESC");
                                if ($query_cache && mysqli_num_rows($query_cache) > 0) {
                                    while ($row_c = mysqli_fetch_assoc($query_cache)) { ?>
                                        <tr>
                                            <td class="text-center"><input type="checkbox" name="selected_ta[]" value="<?= $row_c['id_tahun_ajaran'] ?>" class="item-checkbox"></td>
                                            <td><span class="badge bg-secondary fw-bold"><?= htmlspecialchars($row_c['tahun']) ?></span></td>
                                            <td class="text-dark fw-semibold text-capitalize"><?= htmlspecialchars($row_c['semester']) ?></td>
                                            <td><span class="badge bg-light text-muted"><?= htmlspecialchars($row_c['status']) ?></span></td>
                                        </tr>
                                    <?php }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data arsip penyimpanan sementara.</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Tutup</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="permanent_delete" class="btn btn-danger" onclick="return confirm('Peringatan! Data terpilih akan dihapus permanen dari database. Lanjutkan?')"><i class="bi bi-trash3-fill"></i> Hapus Permanen</button>
                        <button type="submit" name="action" value="restore" class="btn btn-success"><i class="bi bi-arrow-counterclockwise"></i> Pulihkan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const formTA = document.getElementById('formTA');
    const containerSelect = document.getElementById('containerSelectTahun');
    const containerInput = document.getElementById('containerInputTahun');
    const selectTahun = document.getElementById('select_tahun_ajaran');
    const inputTahunBaru = document.getElementById('input_tahun_baru');
    const isTahunBaruFlag = document.getElementById('is_tahun_baru');
    const idTahunHidden = document.getElementById('id_tahun_hidden');
    const btnTahunBaru = document.getElementById('btnTahunBaru');

    document.addEventListener("DOMContentLoaded", function() {
        const toastEl = document.getElementById('statusToast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        }
    });

    function pemicuTahunBaru() {
        isTahunBaruFlag.value = "1";
        selectTahun.required = false;
        inputTahunBaru.required = true;
        containerSelect.classList.add('d-none');
        containerInput.classList.remove('d-none');
        inputTahunBaru.focus();
    }

    function batalTahunBaru() {
        isTahunBaruFlag.value = "0";
        selectTahun.required = true;
        inputTahunBaru.required = false;
        inputTahunBaru.value = "";
        containerInput.classList.add('d-none');
        containerSelect.classList.remove('d-none');
    }

    function prepareTambahForm() {
        formTA.reset();
        batalTahunBaru();
        
        document.getElementById('modalTATitle').innerText = "Tambah Tahun Ajaran Baru";
        document.getElementById('form_action').value = "insert";
        
        // Aktifkan kembali input jika sebelumnya dari mode edit
        selectTahun.disabled = false;
        document.getElementById('semester').disabled = false;
        btnTahunBaru.classList.remove('d-none');
        
        // Hapus hidden input cadangan semester jika ada
        const hiddenSem = document.getElementById('hidden_semester');
        if (hiddenSem) hiddenSem.remove();
        
        document.getElementById('status').value = "nonaktif";
        document.getElementById('semester').value = "ganjil";
        document.getElementById('btnReset').style.display = "block";
    }

    function prepareEditForm(data) {
        formTA.reset();
        batalTahunBaru();
        
        document.getElementById('modalTATitle').innerText = "Ubah / Edit Tahun Ajaran";
        document.getElementById('form_action').value = "update";
        document.getElementById('id_tahun_ajaran').value = data.id_tahun_ajaran;
        
        // Sinkronisasi ID Tahun ke select option dan isi hidden input id_tahun
        selectTahun.value = data.id_tahun; 
        idTahunHidden.value = data.id_tahun;
        selectTahun.disabled = true; 
        btnTahunBaru.classList.add('d-none');
        
        // SINKRONISASI SEMESTER & KUNCI (DISABLED) agar tidak bisa diedit
        const semesterSelect = document.getElementById('semester');
        semesterSelect.value = data.semester.toLowerCase();
        semesterSelect.disabled = true; // Kunci pilihan semester saat edit
        
        // Tambahkan input hidden cadangan untuk semester agar datanya tetap terkirim ke PHP
        let hiddenSem = document.getElementById('hidden_semester');
        if (!hiddenSem) {
            hiddenSem = document.createElement('input');
            hiddenSem.type = 'hidden';
            hiddenSem.name = 'semester';
            hiddenSem.id = 'hidden_semester';
            formTA.appendChild(hiddenSem);
        }
        hiddenSem.value = data.semester.toLowerCase();
        
        document.getElementById('status').value = data.status.toLowerCase();
        document.getElementById('btnReset').style.display = "none";
    }

    function resetForm() {
        formTA.reset();
        batalTahunBaru();
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }
</script>
</body>
</html>