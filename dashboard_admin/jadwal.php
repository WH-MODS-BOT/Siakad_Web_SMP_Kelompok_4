<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Autentikasi Admin & Session
if (file_exists('../Session.php')) {
    require_once '../Session.php';
} else {
    require_once 'Session.php';
}

use Session\Session;

if (Session::getRole() != 'admin') {
    header("Location: ../index.php");
    exit;
}

// 2. Koneksi Database
if (file_exists('../koneksi.php')) {
    require_once '../koneksi.php';
} else {
    require_once 'koneksi.php';
}

// =========================================================================
// AMBIL DATA PILIHAN (DATA MASTER UNTUK SELECT LIST DI MODAL TAMBAH/EDIT)
// =========================================================================
$query_guru  = mysqli_query($conn, "SELECT id_guru, nama_guru FROM guru WHERE deleted = 0 ORDER BY nama_guru ASC");
$query_mapel = mysqli_query($conn, "SELECT id_mapel, nama_mapel FROM mapel ORDER BY nama_mapel ASC");
$query_kelas = mysqli_query($conn, "SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas ASC");

// Ambil semua kelas untuk keperluan filter/sortir di dalam pop-up
$list_semua_kelas = [];
if(mysqli_num_rows($query_kelas) > 0) {
    mysqli_data_seek($query_kelas, 0);
    while($k = mysqli_fetch_assoc($query_kelas)) {
        $list_semua_kelas[] = $k;
    }
}

$query_tahun = mysqli_query($conn, "SELECT ta.id_tahun_ajaran, t.tahun, ta.semester 
                                    FROM tahun_ajaran ta
                                    INNER JOIN tahun t ON ta.id_tahun = t.id_tahun 
                                    WHERE ta.deleted = 0 
                                    ORDER BY t.tahun DESC, ta.semester ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Jadwal Pelajaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { border: none; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05); border-radius: 0.75rem; }
        .table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-content { border: none; border-radius: 1rem; overflow: hidden; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        .bg-gradient-secondary { background: linear-gradient(45deg, #6c757d, #495057); }
        .bg-gradient-info { background: linear-gradient(45deg, #0dcaf0, #0aa2c0); }
        
        /* Toast Notifikasi Mengambang */
        .toast-container { position: fixed; top: 25px; right: 25px; z-index: 1060; }
    </style>
</head>
<body>

<div class="toast-container">
    <?php if (isset($_SESSION['flash_msg'])): 
        $flash = $_SESSION['flash_msg'];
        $bg_icon = $flash['type'] == 'danger' ? 'text-danger' : ($flash['type'] == 'warning' ? 'text-warning' : 'text-success');
    ?>
        <div id="liveToast" class="toast show shadow-lg border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000" style="border-radius: 12px; background: #ffffff; min-width: 320px;">
            <div class="toast-header border-0 bg-white pt-3 px-3 pb-2">
                <i class="bi <?= $flash['type'] == 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> <?= $bg_icon ?> fs-5 me-2"></i>
                <strong class="me-auto text-dark fs-6 fw-bold"><?= htmlspecialchars($flash['title']); ?></strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body text-secondary px-3 pb-3 pt-1" style="font-size: 0.9rem;">
                <?= htmlspecialchars($flash['message']); ?>
            </div>
        </div>
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>
</div>

<div class="container-fluid px-4 mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0">Jadwal Pelajaran</h3>
            <p class="text-muted small m-0">Gunakan icon mata untuk melihat detail rincian dan agenda mengajar setiap guru.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-dark d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTrashJapel">
                <i class="bi bi-archive-fill"></i> Data Terarsip
            </button>
            <button type="button" class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalJapel" onclick="prepareTambahForm()">
                <i class="bi bi-plus-circle-fill"></i> Tambah Jadwal
            </button>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 80px;" class="ps-4">No</th>
                            <th class="text-start ps-4">Nama Guru Pendidik</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_guru_list = "SELECT id_guru, nama_guru FROM guru WHERE deleted = 0 ORDER BY nama_guru ASC";
                        $res_guru_list = mysqli_query($conn, $sql_guru_list);
                        $no = 1;

                        if ($res_guru_list && mysqli_num_rows($res_guru_list) > 0) {
                            while ($gRow = mysqli_fetch_assoc($res_guru_list)) {
                                ?>
                                <tr>
                                    <td class="fw-bold text-secondary ps-4"><?= $no++ ?></td>
                                    <td class="text-start ps-4">
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($gRow['nama_guru']) ?></div>
                                        <small class="text-muted text-uppercase" style="font-size: 0.75rem;">ID Guru: <?= htmlspecialchars($gRow['id_guru']) ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary px-3" title="Lihat Jadwal Mengajar" onclick="bukaDetailJadwalGuru('<?= $gRow['id_guru'] ?>', '<?= htmlspecialchars($gRow['nama_guru']) ?>')">
                                            <i class="bi bi-eye-fill fs-5"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center py-4 text-muted"><i class="bi bi-people fs-2 d-block mb-2"></i> Belum ada data guru terdaftar dalam sistem.</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetailJadwalGuru" tabindex="-1" aria-labelledby="titleDetailGuru" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white py-3">
                <h5 class="modal-title fw-bold" id="titleDetailGuru"><i class="bi bi-calendar3 me-2"></i> Agenda Mengajar Guru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="p-3 bg-light border-bottom d-flex align-items-center gap-3">
                <label class="fw-bold text-secondary small text-nowrap m-0 text-uppercase">
                    <i class="bi bi-funnel-fill me-1"></i> Pilih Filter Kelas:
                </label>
                <select class="form-select form-select-sm" id="selectFilterKelas" onchange="filterJadwalByKelas(this.value)" style="max-width: 250px; border-radius: 20px; padding-left: 15px;">
                    <option value="all">-- Semua Kelas --</option>
                    <?php foreach ($list_semua_kelas as $kls): ?>
                        <option value="<?= $kls['id_kelas'] ?>">Kelas <?= htmlspecialchars($kls['nama_kelas']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Hari</th>
                                <th>Jam Operasional</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th>Kegiatan</th>
                                <th>Tahun Ajaran & Semester</th>
                                <th class="text-center" style="width: 120px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="kontenDetailJadwal">
                            </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTrashJapel" tabindex="-1" aria-labelledby="modalTrashJapelTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form method="POST" action="proses_jadwal.php">
                <div class="modal-header bg-gradient-secondary text-white py-3">
                    <h5 class="modal-title fw-bold" id="modalTrashJapelTitle"><i class="bi bi-archive-fill me-2"></i>Data Jadwal Terarsip</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="p-3 bg-light border-bottom d-flex gap-2">
                        <button type="submit" name="action" value="bulk_restore" class="btn btn-sm btn-success fw-semibold d-flex align-items-center gap-1">
                            <i class="bi bi-arrow-counterclockwise"></i> Restore Terpilih
                        </button>
                        <button type="submit" name="action" value="bulk_permanent_delete" class="btn btn-sm btn-danger fw-semibold d-flex align-items-center gap-1" onclick="return confirm('Hapus permanen data terpilh?')">
                            <i class="bi bi-x-circle-fill"></i> Hapus Permanen
                        </button>
                    </div>
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark text-nowrap">
                                <tr>
                                    <th class="text-center" style="width: 50px;"><input type="checkbox" class="form-check-input" onclick="toggleSelectAllTrash(this)"></th>
                                    <th>Hari</th>
                                    <th>Jam</th>
                                    <th>Kelas</th>
                                    <th>Guru Pengajar</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Tahun & Semester</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql_trash = "SELECT j.*, g.nama_guru, m.nama_mapel, k.nama_kelas, t.tahun
                                              FROM japel j
                                              LEFT JOIN guru g ON j.id_guru = g.id_guru
                                              LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
                                              LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
                                              LEFT JOIN tahun_ajaran ta ON (j.id_tahun = ta.id_tahun AND j.semester = ta.semester)
                                              LEFT JOIN tahun t ON ta.id_tahun = t.id_tahun
                                              WHERE j.deleted = 1 ORDER BY j.id_japel DESC";
                                $res_trash = mysqli_query($conn, $sql_trash);
                                if($res_trash && mysqli_num_rows($res_trash) > 0) {
                                    while($trash = mysqli_fetch_assoc($res_trash)) {
                                        $is_ist = (empty($trash['id_mapel']));
                                        ?>
                                        <tr>
                                            <td class="text-center"><input type="checkbox" name="selected_jadwal[]" value="<?= $trash['id_japel'] ?>" class="form-check-input item-trash-checkbox"></td>
                                            <td><span class="badge bg-secondary"><?= $trash['hari'] ?></span></td>
                                            <td><?= date('H:i', strtotime($trash['jam_mulai'])) ?> - <?= date('H:i', strtotime($trash['jam_selesai'])) ?></td>
                                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($trash['nama_kelas'] ?? '-') ?></span></td>
                                            <td><?= htmlspecialchars($trash['nama_guru'] ?? '-') ?></td>
                                            <td><?= $is_ist ? '-' : htmlspecialchars($trash['nama_mapel']) ?></td>
                                            <td class="small text-muted"><?= htmlspecialchars($trash['tahun'] ?? '-') ?> - <?= htmlspecialchars($trash['semester'] ?? '-') ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-info-circle-fill d-block mb-1 fs-4"></i> Tidak ada berkas jadwal dalam arsip cadangan.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalJapel" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalJapelTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="modalJapelTitle">Tambah Jadwal Pelajaran Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formJapel" method="POST" action="proses_jadwal.php">
                    <input type="hidden" name="action" id="form_action" value="insert">
                    <input type="hidden" name="id_japel" id="id_japel">

                    <div class="form-check form-switch p-3 bg-light rounded-3 mb-4 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="is_istirahat" name="is_istirahat" value="1" onchange="toggleIstirahat()">
                        <label class="form-check-input-label fw-bold text-dark" for="is_istirahat">
                            <i class="bi bi-cup-hot-fill text-warning me-1"></i> Set Sebagai Waktu Istirahat / Non-KBM
                        </label>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6" id="box_guru">
                            <label class="form-label fw-semibold">Guru Pengajar</label>
                            <select class="form-select" name="id_guru" id="id_guru" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php if(mysqli_num_rows($query_guru) > 0) {
                                    mysqli_data_seek($query_guru, 0);
                                    while($g = mysqli_fetch_assoc($query_guru)) {
                                        echo "<option value='".$g['id_guru']."'>".$g['nama_guru']."</option>";
                                    }
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-6" id="box_mapel">
                            <label class="form-label fw-semibold">Mata Pelajaran</label>
                            <select class="form-select" name="id_mapel" id="id_mapel" required>
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php if(mysqli_num_rows($query_mapel) > 0) {
                                    mysqli_data_seek($query_mapel, 0);
                                    while($m = mysqli_fetch_assoc($query_mapel)) {
                                        echo "<option value='".$m['id_mapel']."'>".$m['nama_mapel']."</option>";
                                    }
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Kelas Target</label>
                            <select class="form-select" name="id_kelas" id="id_kelas" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php if(mysqli_num_rows($query_kelas) > 0) {
                                    mysqli_data_seek($query_kelas, 0);
                                    while($k = mysqli_fetch_assoc($query_kelas)) {
                                        echo "<option value='".$k['id_kelas']."'>".$k['nama_kelas']."</option>";
                                    }
                                } ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Hari</label>
                            <select class="form-select" name="hari" id="hari" required>
                                <option value="">-- Pilih Hari --</option>
                                <option value="Senin">Senin</option>
                                <option value="Selasa">Selasa</option>
                                <option value="Rabu">Rabu</option>
                                <option value="Kamis">Kamis</option>
                                <option value="Jumat">Jumat</option>
                                <option value="Sabtu">Sabtu</option>
                                <option value="Minggu">Minggu</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Jam Mulai</label>
                            <input type="time" class="form-control" name="jam_mulai" id="jam_mulai" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Jam Selesai</label>
                            <input type="time" class="form-control" name="jam_selesai" id="jam_selesai" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tahun Ajaran & Semester</label>
                            <select class="form-select" name="id_tahun" id="id_tahun" required>
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php if(mysqli_num_rows($query_tahun) > 0) {
                                    mysqli_data_seek($query_tahun, 0);
                                    while($t = mysqli_fetch_assoc($query_tahun)) {
                                        echo "<option value='".$t['id_tahun_ajaran']."'>".$t['tahun']." - ".$t['semester']."</option>";
                                    }
                                } ?>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan Jadwal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$all_jadwal = [];
$sql_all = "SELECT j.*, g.nama_guru, m.nama_mapel, k.nama_kelas, t.tahun, ta.id_tahun_ajaran
            FROM japel j
            LEFT JOIN guru g ON j.id_guru = g.id_guru
            LEFT JOIN mapel m ON j.id_mapel = m.id_mapel
            LEFT JOIN kelas k ON j.id_kelas = k.id_kelas
            LEFT JOIN tahun_ajaran ta ON (j.id_tahun = ta.id_tahun AND j.semester = ta.semester)
            LEFT JOIN tahun t ON ta.id_tahun = t.id_tahun
            WHERE j.deleted = 0
            ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'), j.jam_mulai ASC";
$res_all = mysqli_query($conn, $sql_all);
while($r = mysqli_fetch_assoc($res_all)) {
    $all_jadwal[] = $r;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const dataJadwalMaster = <?= json_encode($all_jadwal) ?>;
    const modalDetailElement = new bootstrap.Modal(document.getElementById('modalDetailJadwalGuru'));
    const modalFormElement = new bootstrap.Modal(document.getElementById('modalJapel'));

    let currentActiveGuruId = "";

    function bukaDetailJadwalGuru(idGuru, namaGuru) {
        currentActiveGuruId = idGuru;
        document.getElementById('titleDetailGuru').innerHTML = `<i class="bi bi-calendar3 me-2"></i> Jadwal Mengajar - ${namaGuru}`;
        document.getElementById('selectFilterKelas').value = 'all';
        renderTabelJadwal(idGuru, 'all');
        modalDetailElement.show();
    }

    function renderTabelJadwal(idGuru, filterKelasId) {
        const container = document.getElementById('kontenDetailJadwal');
        container.innerHTML = "";

        let dataTerfilter = dataJadwalMaster.filter(item => item.id_guru == idGuru);

        if (filterKelasId !== 'all') {
            dataTerfilter = dataTerfilter.filter(item => item.id_kelas == filterKelasId);
        }

        if(dataTerfilter.length > 0) {
            dataTerfilter.forEach(row => {
                const isIstirahat = (!row.id_mapel || row.id_mapel === null || row.id_mapel === "");
                const kegiatanBadge = isIstirahat ? '<span class="badge bg-warning text-dark fw-bold"><i class="bi bi-cup-hot-fill me-1"></i> Istirahat</span>' : '<span class="text-muted small">KBM Efektif</span>';
                const namaMapel = isIstirahat ? '<span class="text-muted italic">-</span>' : row.nama_mapel;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="badge bg-light text-dark border fw-bold">${row.hari}</span></td>
                    <td class="fw-semibold"><i class="bi bi-clock text-muted me-1"></i>${row.jam_mulai.substring(0,5)} - ${row.jam_selesai.substring(0,5)}</td>
                    <td><span class="badge bg-info text-dark">Kelas ${row.nama_kelas}</span></td>
                    <td>${namaMapel}</td>
                    <td>${kegiatanBadge}</td>
                    <td><span class="badge bg-secondary fw-medium">${row.tahun} - ${row.semester}</span></td>
                    <td class="text-center text-nowrap">
                        <button class="btn btn-sm btn-warning text-dark me-1" onclick='pemicuEditDariDetail(${JSON.stringify(row)})'><i class="bi bi-pencil-square"></i></button>
                        <a href="proses_jadwal.php?action=soft_delete&id=${row.id_japel}" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin memindahkan jadwal ini ke Arsip?')"><i class="bi bi-trash-fill"></i></a>
                    </td>
                `;
                container.appendChild(tr);
            });
        } else {
            container.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-muted"><i class="bi bi-info-circle-fill fs-5 d-block mb-1"></i> Tidak ada data jadwal mengajar yang sesuai pada kelas ini.</td></tr>';
        }
    }

    function filterJadwalByKelas(idKelas) {
        renderTabelJadwal(currentActiveGuruId, idKelas);
    }

    function pemicuEditDariDetail(dataRow) {
        modalDetailElement.hide();
        setTimeout(() => {
            prepareEditForm(dataRow);
            modalFormElement.show();
        }, 400);
    }

    function toggleSelectAllTrash(master) {
        const checkboxes = document.querySelectorAll('.item-trash-checkbox');
        checkboxes.forEach(cb => cb.checked = master.checked);
    }

    const formJapel = document.getElementById('formJapel');
    const checkIstirahat = document.getElementById('is_istirahat');
    const selectGuru = document.getElementById('id_guru');
    const selectMapel = document.getElementById('id_mapel');

    function toggleIstirahat() {
        if(checkIstirahat.checked) {
            selectMapel.value = "";
            selectMapel.required = false;
            document.getElementById('box_mapel').style.opacity = '0.5';
            selectMapel.disabled = true;
            selectGuru.required = false; 
        } else {
            selectGuru.required = true;
            selectMapel.required = true;
            document.getElementById('box_mapel').style.opacity = '1';
            selectMapel.disabled = false;
        }
    }

    function prepareTambahForm() {
        formJapel.reset();
        document.getElementById('modalJapelTitle').innerText = "Tambah Jadwal Pelajaran Baru";
        document.getElementById('form_action').value = "insert";
        document.getElementById('id_japel').value = "";
        checkIstirahat.checked = false;
        toggleIstirahat();
    }

    function prepareEditForm(data) {
        formJapel.reset();
        document.getElementById('modalJapelTitle').innerText = "Ubah / Edit Jadwal Pelajaran";
        document.getElementById('form_action').value = "update";
        document.getElementById('id_japel').value = data.id_japel;
        document.getElementById('id_kelas').value = data.id_kelas;
        document.getElementById('hari').value = data.hari;
        document.getElementById('jam_mulai').value = data.jam_mulai.substring(0,5);
        document.getElementById('jam_selesai').value = data.jam_selesai.substring(0,5);
        document.getElementById('id_tahun').value = data.id_tahun_ajaran;

        selectGuru.value = data.id_guru ? data.id_guru : "";
        selectMapel.value = data.id_mapel ? data.id_mapel : "";

        if(!data.id_mapel) { 
            checkIstirahat.checked = true; 
        } else { 
            checkIstirahat.checked = false; 
        }
        toggleIstirahat();
    }
</script>
</body>
</html>