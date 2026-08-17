<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Memanggil Session & Autentikasi Admin
require_once '../Session.php';

use Session\Session;

if(Session::getRole()!='admin'){
    header("Location: ../index.php");
    exit;
}

// 2. Memanggil Koneksi Database
require_once '../koneksi.php'; 

// =========================================================================
// LOGIKA GENERATE KODE GURU OTOMATIS (GR001, GR002, dst.)
// =========================================================================
$next_id_guru = "GR001"; 
$query_id = mysqli_query($conn, "SELECT id_guru FROM guru ORDER BY id_guru DESC LIMIT 1");
if ($query_id && mysqli_num_rows($query_id) > 0) {
    $data_id = mysqli_fetch_assoc($query_id);
    $last_id = $data_id['id_guru'];
    $urutan = (int) substr($last_id, 2);
    $urutan++;
    $next_id_guru = "GR" . sprintf("%03s", $urutan);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Guru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        .bg-gradient-dark { background: linear-gradient(45deg, #212529, #343a40); }

        /* Desain Kustom UI Toast Notifikasi Mengambang */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 1060; }
        .custom-toast { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 6px solid #0d6efd; overflow: hidden; display: none; animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards; }
        .custom-toast.success { border-left-color: #198754; }
        .custom-toast.danger { border-left-color: #dc3545; }
        .custom-toast.warning { border-left-color: #ffc107; }
        .custom-toast.info { border-left-color: #0dcaf0; }
        @keyframes slideIn { from { transform: translateX(120%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(120%); opacity: 0; } }
        
        /* Tambahan styling kursor untuk ikon badge */
        .cursor-pointer { cursor: pointer; }
    </style>
</head>
<body>

<div class="toast-container">
    <?php if (isset($_SESSION['flash_msg'])): 
        $flash = $_SESSION['flash_msg'];
        $icon = 'bi-info-circle-fill';
        if($flash['type'] == 'success') $icon = 'bi-check-circle-fill text-success';
        if($flash['type'] == 'danger') $icon = 'bi-exclamation-triangle-fill text-danger';
        if($flash['type'] == 'warning') $icon = 'bi-exclamation-circle-fill text-warning';
        unset($_SESSION['flash_msg']); 
    ?>
        <div class="custom-toast p-3 <?= $flash['type'] ?>" id="liveToast" style="display: block; width: 360px;">
            <div class="d-flex align-items-start">
                <i class="bi <?= $icon ?> fs-4 me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($flash['title']) ?></h6>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($flash['message']) ?></p>
                </div>
                <button type="button" class="btn-close ms-2" onclick="closeToast()"></button>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="container-fluid px-4 mt-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Data Tenaga Pendidik (Guru)</h3>
            <p class="text-muted small m-0">Kelola biodata guru, nomor induk pendidik, kompetensi jurusan, serta status keaktifan.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalCacheGuru">
                <i class="bi bi-archive-fill"></i> Cache Backup Data Guru
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalGuru" onclick="prepareTambahForm('<?= $next_id_guru ?>')">
                <i class="bi bi-plus-circle-fill"></i> Tambah Guru
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="dashboard.php" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="guru">
                
                <div class="col-md-10">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="search_input" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan NIP, Nama Guru, atau Jurusan..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                </div>
                <div class="col-md-2">
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
                            <th style="width: 70px;" class="ps-4">No.</th>
                            <th>Kode Guru</th>
                            <th>NIP</th>
                            <th>Nama Lengkap</th>
                            <th>Jenis Kelamin</th>
                            <th>Alamat</th>
                            <th>No. Telepon</th>
                            <th>Jurusan Kuliah</th>
                            <th>Status</th>
                            <th class="text-center" style="width: 130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                        
                        // Menambahkan LEFT JOIN untuk mengecek ketersediaan data guru di dalam tabel kelas sebagai wali kelas
                        $sql = "SELECT g.*, k.nama_kelas 
                                FROM guru g 
                                LEFT JOIN kelas k ON g.id_guru = k.id_wali_guru 
                                WHERE g.deleted = 0";
                                
                        if ($search != '') {
                            $sql .= " AND (g.nip LIKE '%$search%' OR g.nama_guru LIKE '%$search%' OR g.jurusan LIKE '%$search%')";
                        }
                        $sql .= " ORDER BY g.id_guru ASC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            $no = 1; 
                            while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-semibold"><?= $no++ ?></td>
                                    <td><span class="badge bg-light text-dark border fw-bold"><?= htmlspecialchars($row['id_guru']) ?></span></td>
                                    <td><?= htmlspecialchars($row['nip']) ?></td>
                                    
                                    <td class="fw-semibold text-dark">
                                        <?= htmlspecialchars($row['nama_guru']) ?>
                                        <?php if (!empty($row['nama_kelas'])): ?>
                                            <i class="bi bi-patch-check-fill text-success ms-1 fs-6 cursor-pointer" 
                                               data-bs-toggle="tooltip" 
                                               data-bs-placement="top" 
                                               title="Guru tersebut adalah wali kelas di kelas <?= htmlspecialchars($row['nama_kelas']) ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td><?= htmlspecialchars($row['jk']) ?></td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['alamat'] ?? '-') ?></small></td>
                                    <td><?= htmlspecialchars($row['notelp'] ?? '-') ?></td>
                                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars($row['jurusan'] ?? '-') ?></span></td>
                                    <td>
                                        <span class="badge <?= $row['status'] == 'PNS' ? 'bg-success' : 'bg-primary' ?>">
                                            <?= htmlspecialchars($row['status'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-warning text-dark me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalGuru"
                                                onclick='prepareEditForm(<?= json_encode($row) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="proses_guru.php?action=soft_delete&id=<?= $row['id_guru'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin memindahkan data pendidik ini ke Cache Backup?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="10" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2"></i> Belum ada data guru aktif.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalGuru" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalGuruTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalGuruTitle">Tambah Data Guru Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formGuru" method="POST" action="proses_guru.php">
                    <input type="hidden" name="action" id="form_action" value="insert">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-secondary">Kode Guru</label>
                            <input type="text" class="form-control bg-light fw-bold text-primary" name="id_guru" id="id_guru" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">NIP (Nomor Induk Pegawai)</label>
                            <input type="text" class="form-control" name="nip" id="nip" required 
                                   pattern="[0-9]+" inputmode="numeric" title="NIP wajib diisi dengan angka saja, tanpa huruf atau simbol!" 
                                   placeholder="Contoh: 19880312xxxxxxxxxx">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Lengkap Guru</label>
                            <input type="text" class="form-control" name="nama_guru" id="nama_guru" required placeholder="Nama lengkap beserta gelar">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Jenis Kelamin</label>
                            <select class="form-select" name="jk" id="jk" required>
                                <option value="">-- Pilih --</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No. Telepon / HP</label>
                            <input type="text" class="form-control" name="notelp" id="notelp" placeholder="08xxxxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Jurusan Masa Perkuliahan</label>
                            <input type="text" class="form-control" name="jurusan" id="jurusan" placeholder="Contoh: Pendidikan Matematika, Sastra Inggris">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status Kepegawaian</label>
                            <select class="form-select" name="status" id="status" required>
                                <option value="">-- Pilih Status --</option>
                                <option value="PNS">PNS</option>
                                <option value="Honorer">Honorer</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat Rumah Lengkap</label>
                            <textarea class="form-control" name="alamat" id="alamat" rows="2" placeholder="Nama jalan, nomor rumah, RT/RW..."></textarea>
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

<div class="modal fade" id="modalCacheGuru" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalCacheGuruTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="modalCacheGuruTitle"><i class="bi bi-archive"></i> Cache Backup Data Guru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.reload();"></button>
            </div>
            <form action="proses_guru.php" method="POST">
                <div class="modal-body p-4">
                    <p class="text-muted small">Berikut adalah daftar tenaga pendidik yang dinonaktifkan sementara. Centang item guru lalu pilih instruksi manajemen di bawah.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center"><input type="checkbox" id="checkAll" onclick="toggleSelectAll(this)"></th>
                                    <th>Kode</th>
                                    <th>NIP</th>
                                    <th>Nama Guru</th>
                                    <th>Jurusan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $query_cache = mysqli_query($conn, "SELECT * FROM guru WHERE deleted = 1 ORDER BY id_guru ASC");
                                if ($query_cache && mysqli_num_rows($query_cache) > 0) {
                                    while ($row_c = mysqli_fetch_assoc($query_cache)) { ?>
                                        <tr>
                                            <td class="text-center"><input type="checkbox" name="selected_guru[]" value="<?= $row_c['id_guru'] ?>" class="item-checkbox"></td>
                                            <td><span class="badge bg-secondary fw-bold"><?= htmlspecialchars($row_c['id_guru']) ?></span></td>
                                            <td><?= htmlspecialchars($row_c['nip'] ?? '-') ?></td>
                                            <td class="text-dark fw-semibold"><?= htmlspecialchars($row_c['nama_guru']) ?></td>
                                            <td><?= htmlspecialchars($row_c['jurusan'] ?? '-') ?></td>
                                        </tr>
                                    <?php }
                                } else {
                                    echo '<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada arsip data guru di dalam sampah penyimpanan sementara.</td></tr>';
                                } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="window.location.reload();">Cancel / Tutup</button>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="permanent_delete" class="btn btn-danger" onclick="return confirm('Peringatan! Data guru terpilih akan musnah selamanya dari database. Lanjutkan?')"><i class="bi bi-trash3-fill"></i> Delete Permanen</button>
                        <button type="submit" name="action" value="restore" class="btn btn-success"><i class="bi bi-arrow-counterclockwise"></i> Restore Terpilih</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const formGuru = document.getElementById('formGuru');
    const liveToast = document.getElementById('liveToast');
    const nipInput = document.getElementById('nip');
    let cachedAutoID = "";

    // Inisialisasi Fitur Tooltip Bootstrap
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    if (liveToast) {
        setTimeout(() => { closeToast(); }, 4000);
    }

    // Mencegah input non-angka diketik langsung ke kolom NIP di browser
    if (nipInput) {
        nipInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }

    function closeToast() {
        if (liveToast) {
            liveToast.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => { liveToast.style.display = 'none'; }, 300);
        }
    }

    function prepareTambahForm(nextId) {
        formGuru.reset();
        document.getElementById('modalGuruTitle').innerText = "Tambah Data Guru Baru";
        document.getElementById('form_action').value = "insert";
        cachedAutoID = nextId;
        document.getElementById('id_guru').value = nextId;
        document.getElementById('btnReset').style.display = "block";
    }

    function prepareEditForm(data) {
        formGuru.reset();
        document.getElementById('modalGuruTitle').innerText = "Ubah / Edit Informasi Guru";
        document.getElementById('form_action').value = "update";
        
        document.getElementById('id_guru').value = data.id_guru;
        document.getElementById('nip').value = data.nip;
        document.getElementById('nama_guru').value = data.nama_guru;
        document.getElementById('jk').value = data.jk;
        document.getElementById('notelp').value = data.notelp;
        document.getElementById('jurusan').value = data.jurusan;
        document.getElementById('status').value = data.status;
        document.getElementById('alamat').value = data.alamat;
        
        document.getElementById('btnReset').style.display = "none";
    }

    function resetFormKeepID() {
        formGuru.reset();
        document.getElementById('id_guru').value = cachedAutoID;
    }

    function toggleSelectAll(master) {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }
</script>

</body>
</html>