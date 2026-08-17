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
// LOGIKA GENERATE KODE MAPEL OTOMATIS (MP001, MP002, dst.)
// =========================================================================
$next_id_mapel = "MP001"; 
$query_id = mysqli_query($conn, "SELECT kode_mapel FROM mapel ORDER BY id_mapel DESC LIMIT 1");
if ($query_id && mysqli_num_rows($query_id) > 0) {
    $data_id = mysqli_fetch_assoc($query_id);
    $last_kode = $data_id['kode_mapel'];
    $urutan = (int) substr($last_kode, 2);
    $urutan++;
    $next_id_mapel = "MP" . sprintf("%03s", $urutan);
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
    <title>Manajemen Mata Pelajaran</title>
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
            <h3 class="fw-bold text-dark m-0">Data Mata Pelajaran</h3>
            <p class="text-muted small m-0">Kelola informasi kurikulum, kode mata pelajaran, beserta nilai acuan KKM.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCache">
                <i class="bi bi-archive-fill"></i> Cache Backup Mapel
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMapel" onclick="prepareTambahForm('<?= $next_id_mapel ?>')">
                <i class="bi bi-plus-circle-fill"></i> Tambah Mapel
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="mapel">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="search_input" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan Kode Mapel atau Nama Mata Pelajaran..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
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
                            <th>Kode Mapel</th>
                            <th>Nama Mata Pelajaran</th>
                            <th>KKM</th>
                            <th class="text-center" style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                        $sql = "SELECT * FROM mapel WHERE deleted = 0";
                        if ($search != '') {
                            $sql .= " AND (kode_mapel LIKE '%$search%' OR nama_mapel LIKE '%$search%')";
                        }
                        $sql .= " ORDER BY kode_mapel ASC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td class="ps-4 text-muted"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($row['kode_mapel']) ?></span></td>
                                    <td class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                    <td><span class="badge bg-info text-dark fw-bold"><?= $row['kkm'] ?></span></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-warning text-dark me-1" data-bs-toggle="modal" data-bs-target="#modalMapel" onclick='prepareEditForm(<?= json_encode($row) ?>)'><i class="bi bi-pencil-square"></i></button>
                                        <a href="proses_mapel.php?action=soft_delete&id=<?= $row['id_mapel'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin memindahkan mata pelajaran ini ke Cache Backup?')"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php }
                        } else {
                            echo '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2"></i> Belum ada data mata pelajaran aktif.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalMapel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalMapelTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalMapelTitle">Tambah Mata Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formMapel" method="POST" action="proses_mapel.php">
                    <input type="hidden" name="action" id="form_action" value="insert">
                    <input type="hidden" name="id_mapel" id="id_mapel">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Kode Mata Pelajaran</label>
                            <input type="text" class="form-control bg-light fw-bold text-primary" name="kode_mapel" id="kode_mapel" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nama Mata Pelajaran</label>
                            <input type="text" class="form-control" name="nama_mapel" id="nama_mapel" required placeholder="Contoh: Matematika, Bahasa Indonesia">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nilai KKM</label>
                            <input type="number" class="form-control" name="kkm" id="kkm" required min="0" max="100" value="75">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-warning px-4" id="btnReset" onclick="resetFormKeepID()">Reset</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan Data</button>
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
                <h5 class="modal-title fw-bold" id="modalCacheTitle"><i class="bi bi-archive"></i> Cache Backup Mata Pelajaran</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>
            </div>
            <form action="proses_mapel.php" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small">Berikut adalah daftar mata pelajaran yang dihapus sementara. Centang item yang ingin dikelola lalu pilih aksi di bawah.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center"><input type="checkbox" id="checkAll" onclick="toggleSelectAll(this)"></th>
                                    <th>Kode</th>
                                    <th>Nama Mata Pelajaran</th>
                                    <th>KKM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_cache = mysqli_query($conn, "SELECT * FROM mapel WHERE deleted = 1 ORDER BY id_mapel DESC");
                                if ($query_cache && mysqli_num_rows($query_cache) > 0) {
                                    while ($row_c = mysqli_fetch_assoc($query_cache)) { ?>
                                        <tr>
                                            <td class="text-center"><input type="checkbox" name="selected_mapel[]" value="<?= $row_c['id_mapel'] ?>" class="item-checkbox"></td>
                                            <td><span class="badge bg-secondary fw-bold"><?= htmlspecialchars($row_c['kode_mapel']) ?></span></td>
                                            <td class="text-dark fw-semibold"><?= htmlspecialchars($row_c['nama_mapel']) ?></td>
                                            <td><?= $row_c['kkm'] ?></td>
                                        </tr>
                                    <?php }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">Tidak ada data di dalam sampah penyimpanan sementara.</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Cancel / Tutup</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="permanent_delete" class="btn btn-danger" onclick="return confirm('Peringatan! Data yang terpilih akan terhapus secara permanen dari basis data database. Lanjutkan?')"><i class="bi bi-trash3-fill"></i> Delete Permanen</button>
                        <button type="submit" name="action" value="restore" class="btn btn-success"><i class="bi bi-arrow-counterclockwise"></i> Restore Terpilih</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const formMapel = document.getElementById('formMapel');
    let cachedAutoID = "";

    // Memicu inisialisasi Pop-up Toast Otomatis jika terdapat data Session Flash Message
    document.addEventListener("DOMContentLoaded", function() {
        const toastEl = document.getElementById('statusToast');
        if (toastEl) {
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
        }
    });

    function prepareTambahForm(nextId) {
        formMapel.reset();
        document.getElementById('modalMapelTitle').innerText = "Tambah Mata Pelajaran Baru";
        document.getElementById('form_action').value = "insert";
        cachedAutoID = nextId;
        document.getElementById('kode_mapel').value = nextId;
        document.getElementById('btnReset').style.display = "block";
    }

    function prepareEditForm(data) {
        formMapel.reset();
        document.getElementById('modalMapelTitle').innerText = "Ubah / Edit Mata Pelajaran";
        document.getElementById('form_action').value = "update";
        document.getElementById('id_mapel').value = data.id_mapel;
        document.getElementById('kode_mapel').value = data.kode_mapel;
        document.getElementById('nama_mapel').value = data.nama_mapel;
        document.getElementById('kkm').value = data.kkm;
        document.getElementById('btnReset').style.display = "none";
    }

    function resetFormKeepID() {
        formMapel.reset();
        document.getElementById('kode_mapel').value = cachedAutoID;
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }
</script>
</body>
</html>