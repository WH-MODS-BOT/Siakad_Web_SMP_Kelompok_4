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

// =========================================================================
// LOGIKA GENERATE KODE SISWA OTOMATIS
// =========================================================================
$next_id_siswa = "SW001"; 
$query_id = mysqli_query($conn, "SELECT MAX(id_siswa) as max_id FROM siswa");
if ($query_id) {
    $data_id = mysqli_fetch_assoc($query_id);
    if ($data_id['max_id']) {
        $urutan = (int) substr($data_id['max_id'], 2);
        $urutan++;
        $next_id_siswa = "SW" . sprintf("%03s", $urutan);
    }
}

$query_kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$query_tahun = mysqli_query($conn, "SELECT id_tahun, tahun FROM tahun ORDER BY tahun DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Data Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
    </style>
</head>
<body>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 20px;">
    <?php if (isset($_SESSION['flash_msg'])): 
        $flash = $_SESSION['flash_msg'];
        $bg_icon = 'text-success';
        if ($flash['type'] == 'danger') $bg_icon = 'text-danger';
        if ($flash['type'] == 'warning') $bg_icon = 'text-warning';
        if ($flash['type'] == 'info') $bg_icon = 'text-info';
    ?>
        <div id="liveToast" class="toast show shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000" style="border-radius: 12px; background: #ffffff; min-width: 320px;">
            <div class="toast-header border-0 bg-white pt-3 px-3 pb-2" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <i class="bi <?= $flash['type'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> <?= $bg_icon ?> fs-5 me-2"></i>
                <strong class="me-auto text-dark fs-6 fw-bold"><?= htmlspecialchars($flash['title']); ?></strong>
                <small class="text-muted">Baru saja</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body text-secondary px-3 pb-3 pt-1" style="font-size: 0.9rem; line-height: 1.4;">
                <?= htmlspecialchars($flash['message']); ?>
            </div>
            <div class="progress" style="height: 3px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px; background-color: #f1f5f9;">
                <div class="progress-bar <?= $flash['type'] == 'success' ? 'bg-success' : ($flash['type'] == 'danger' ? 'bg-danger' : 'bg-warning'); ?>" role="progressbar" style="width: 100%; transition: width 4s linear;" id="toastProgress"></div>
            </div>
        </div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>
</div>

<div class="container-fluid px-4 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h3 class="fw-bold text-dark m-0">Data Induk Siswa</h3>
            <p class="text-muted small m-0">Kelola informasi data siswa, kelas, dan tahun ajaran secara terpusat.</p>
        </div>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalSiswa" onclick="prepareTambahForm('<?= $next_id_siswa ?>')">
            <i class="bi bi-plus-circle-fill"></i> Tambah Siswa
        </button>
    </div>

    <div class="card mb-4 mt-3">
        <div class="card-body py-3">
            <form method="GET" action="" class="row g-3 align-items-center">
                <input type="hidden" name="page" value="siswa">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" id="search_input" class="form-control border-start-0 ps-0" placeholder="Cari berdasarkan NIS, NISN, atau Nama..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100 fw-semibold">Cari</button>
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
                            <th>Kode Siswa</th>
                            <th>NIS</th>
                            <th>NISN</th>
                            <th>Nama</th>
                            <th>JK</th>
                            <th>Tempat Lahir</th>
                            <th>Agama</th>
                            <th>NIK</th>
                            <th>Telepon</th>
                            <th>Kelas</th>
                            <th>Alamat</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                            <th>Tgl Keluar</th>
                            <th>Tahun</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                        
                        $sql = "SELECT s.*, sk.id_kelas, k.nama_kelas, t.tahun, sk.id_tahun AS id_th_ajaran
                                FROM siswa s
                                LEFT JOIN siswa_kelas sk ON s.id_siswa = sk.id_siswa
                                LEFT JOIN kelas k ON sk.id_kelas = k.id_kelas
                                LEFT JOIN tahun t ON sk.id_tahun = t.id_tahun";
                        
                        if ($search != '') {
                            $sql .= " WHERE s.nis LIKE '%$search%' OR s.nisn LIKE '%$search%' OR s.nama_siswa LIKE '%$search%'";
                        }
                        
                        $sql .= " ORDER BY s.id_siswa DESC";
                        $result = mysqli_query($conn, $sql);
                        
                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border fw-mono"><?= $row['id_siswa'] ?></span></td>
                                    <td><?= $row['nis'] ?></td>
                                    <td><?= $row['nisn'] ?></td>
                                    <td class="fw-semibold text-dark"><?= $row['nama_siswa'] ?></td>
                                    <td><?= $row['jk'] ?></td>
                                    <td><?= $row['tempat_lahir'] ?></td>
                                    <td><?= $row['agama'] ?></td>
                                    <td><?= $row['nik'] ?></td>
                                    <td><?= $row['no_telpon'] ?></td>
                                    <td><span class="badge bg-info text-dark"><?= $row['nama_kelas'] ?? '-' ?></span></td>
                                    <td><small class="text-muted"><?= $row['alamat'] ?></small></td>
                                    <td>
                                        <?php 
                                        $status = $row['status'];
                                        $badge_class = 'bg-success';
                                        if($status == 'Nonaktif') $badge_class = 'bg-secondary';
                                        if($status == 'Keluar') $badge_class = 'bg-danger';
                                        if($status == 'Dropout') $badge_class = 'bg-dark';
                                        if($status == 'Lulus') $badge_class = 'bg-primary';
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $status ?></span>
                                    </td>
                                    <td><small class="text-muted"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></small></td>
                                    <td><small class="fw-semibold text-danger"><?= ($row['tanggal_keluar'] && $row['tanggal_keluar'] != '0000-00-00') ? date('d-m-Y', strtotime($row['tanggal_keluar'])) : '-' ?></small></td>
                                    <td><?= $row['tahun'] ?? '-' ?></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-warning text-dark me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalSiswa"
                                                onclick='prepareEditForm(<?= json_encode([
                                                    "id_siswa" => $row["id_siswa"], "nis" => $row["nis"], "nisn" => $row["nisn"],
                                                    "nama" => $row["nama_siswa"], "jk" => $row["jk"], "tempat_lahir" => $row["tempat_lahir"],
                                                    "tanggal_lahir" => $row["tanggal_lahir"], "agama" => $row["agama"], "nik" => $row["nik"],
                                                    "telepon" => $row["no_telpon"], "id_kelas" => $row["id_kelas"], "id_tahun" => $row["id_th_ajaran"],
                                                    "alamat" => $row["alamat"], "status" => $row["status"], "keterangan" => $row["keterangan"] ?? ""
                                                ]) ?>)'>
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <a href="proses_siswa.php?action=delete&id=<?= $row['id_siswa'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="16" class="text-center py-4 text-muted"><i class="bi bi-folder-x fs-2 d-block mb-2"></i> Belum ada data siswa.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalSiswa" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalSiswaTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalSiswaTitle">Tambah Data Siswa Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formSiswa" method="POST" action="proses_siswa.php">
                    <input type="hidden" name="action" id="form_action" value="insert">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold text-secondary">Kode Siswa</label>
                            <input type="text" class="form-control bg-light fw-bold text-primary" name="id_siswa" id="id_siswa" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NIS</label>
                            <input type="text" class="form-control" name="nis" id="nis" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NISN</label>
                            <input type="text" class="form-control" name="nisn" id="nisn" required>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nama Lengkap</label>
                            <input type="text" class="form-control" name="nama_siswa" id="nama_siswa" required>
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
                            <label class="form-label fw-semibold">Tempat Lahir</label>
                            <input type="text" class="form-control" name="tempat_lahir" id="tempat_lahir">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tanggal Lahir</label>
                            <input type="date" class="form-control" name="tanggal_lahir" id="tanggal_lahir">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Agama</label>
                            <select class="form-select" name="agama" id="agama">
                                <option value="">-- Pilih --</option>
                                <option value="Islam">Islam</option>
                                <option value="Kristen Katolik">Kristen Katolik</option>
                                <option value="Kristen Protestan">Kristen Protestan</option>
                                <option value="Hindu">Hindu</option>
                                <option value="Buddha">Buddha</option>
                                <option value="Konghucu">Konghucu</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">NIK</label>
                            <input type="text" class="form-control" name="nik" id="nik">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">No. Telepon</label>
                            <input type="text" class="form-control" name="no_telpon" id="no_telpon">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas Utama</label>
                            <select class="form-select" name="id_kelas" id="id_kelas" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php 
                                if (mysqli_num_rows($query_kelas) > 0) {
                                    mysqli_data_seek($query_kelas, 0);
                                    while($k = mysqli_fetch_assoc($query_kelas)): 
                                ?>
                                    <option value="<?= $k['id_kelas'] ?>"><?= $k['nama_kelas'] ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="wrapper_pindah_kelas" style="display:none;">
                            <label class="form-label text-warning fw-semibold"><i class="bi bi-arrow-left-right"></i> Ubah / Pindah Kelas</label>
                            <select class="form-select border-warning" name="pindah_kelas" id="pindah_kelas">
                                <option value="">-- Tetap di Kelas Saat Ini --</option>
                                <?php
                                if (mysqli_num_rows($query_kelas) > 0) {
                                    mysqli_data_seek($query_kelas, 0); 
                                    while ($row_kelas = mysqli_fetch_assoc($query_kelas)) {
                                        echo "<option value='" . $row_kelas['id_kelas'] . "'>" . $row_kelas['nama_kelas'] . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun</label>
                            <select class="form-select" name="id_tahun" id="id_tahun" required>
                                <option value="">-- Pilih Tahun --</option>
                                <?php 
                                if (mysqli_num_rows($query_tahun) > 0) {
                                    mysqli_data_seek($query_tahun, 0);
                                    while($t = mysqli_fetch_assoc($query_tahun)): 
                                ?>
                                    <option value="<?= $t['id_tahun'] ?>"><?= $t['tahun'] ?></option>
                                <?php endwhile; } ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Alamat</label>
                            <textarea class="form-control" name="alamat" id="alamat" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" id="status" onchange="handleStatusChange()" required>
                                <option value="Aktif" selected>Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                                <option value="Keluar">Keluar</option>
                                <option value="Dropout">Dropout</option>
                                <option value="Lulus">Lulus</option>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <input type="text" class="form-control" name="keterangan" id="keterangan">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-outline-warning px-4" id="btnReset" onclick="resetFormKeepID()">Reset</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const formSiswa = document.getElementById('formSiswa');
    const statusSelect = document.getElementById('status');
    const keteranganInput = document.getElementById('keterangan');
    const wrapperPindahKelas = document.getElementById('wrapper_pindah_kelas');
    let cachedAutoID = ""; 

    function getTanggalFormatIndonesia() {
        const bulanNama = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        const d = new Date();
        return `${d.getDate()} ${bulanNama[d.getMonth()]} ${d.getFullYear()}`;
    }

    function handleStatusChange() {
        const statusAktif = statusSelect.value;
        if (statusAktif === 'Aktif') {
            keteranganInput.value = "";
            keteranganInput.readOnly = true;
            keteranganInput.placeholder = "Siswa aktif (tidak perlu keterangan)";
        } else if (statusAktif === 'Lulus') {
            const hariIni = getTanggalFormatIndonesia();
            keteranganInput.value = `siswa sudah lulus pada tanggal ${hariIni} secara real time`;
            keteranganInput.readOnly = true;
        } else if (statusAktif === 'Keluar') {
            keteranganInput.value = ""; 
            keteranganInput.readOnly = false;
            keteranganInput.placeholder = "Tuliskan alasan keluar...";
        } else {
            keteranganInput.value = ""; 
            keteranganInput.readOnly = false;
            keteranganInput.placeholder = "Tuliskan alasan / keterangan di sini...";
        }
    }

    function prepareTambahForm(nextId) {
        formSiswa.reset();
        document.getElementById('modalSiswaTitle').innerText = "Tambah Data Siswa Baru";
        document.getElementById('form_action').value = "insert";
        cachedAutoID = nextId;
        document.getElementById('id_siswa').value = nextId;
        wrapperPindahKelas.style.display = "none"; 
        document.getElementById('btnReset').style.display = "block"; 
        handleStatusChange();
    }

    function prepareEditForm(data) {
        formSiswa.reset();
        document.getElementById('modalSiswaTitle').innerText = "Ubah / Edit Data Siswa";
        document.getElementById('form_action').value = "update";
        
        document.getElementById('id_siswa').value = data.id_siswa;
        document.getElementById('nis').value = data.nis;
        document.getElementById('nisn').value = data.nisn;
        document.getElementById('nama_siswa').value = data.nama;
        document.getElementById('jk').value = data.jk;
        document.getElementById('tempat_lahir').value = data.tempat_lahir;
        document.getElementById('tanggal_lahir').value = data.tanggal_lahir;
        document.getElementById('agama').value = data.agama;
        document.getElementById('nik').value = data.nik;
        document.getElementById('no_telpon').value = data.telepon;
        document.getElementById('id_kelas').value = data.id_kelas;
        document.getElementById('id_tahun').value = data.id_tahun;
        document.getElementById('alamat').value = data.alamat;
        document.getElementById('status').value = data.status;
        
        wrapperPindahKelas.style.display = "block"; 
        document.getElementById('btnReset').style.display = "none"; 
        
        handleStatusChange();
        
        if(data.status !== 'Aktif' && data.status !== 'Lulus') {
            document.getElementById('keterangan').value = data.keterangan;
        }
    }

    function resetFormKeepID() {
        formSiswa.reset();
        document.getElementById('id_siswa').value = cachedAutoID;
        handleStatusChange();
    }

    const liveToast = document.getElementById('liveToast');
    const toastProgress = document.getElementById('toastProgress');
    
    if (liveToast) {
        setTimeout(() => {
            if(toastProgress) toastProgress.style.width = '0%';
        }, 100);

        const toast = new bootstrap.Toast(liveToast, { delay: 4000 });
        toast.show();
    }
</script>
</body>
</html>