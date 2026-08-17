<?php
// Set timezone agar tanggal & hari selalu akurat (WIB)
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

// Mengambil Username & mencari ID Guru jika $id_guru_login belum ada dari parent file
$username = Session::getUsername();
if (!isset($id_guru_login) || empty($id_guru_login)) {
    $q_g = mysqli_query($conn, "SELECT g.id_guru FROM guru g JOIN akun a ON g.id_guru = a.id_guru WHERE a.username = '$username' LIMIT 1");
    $d_g = mysqli_fetch_assoc($q_g);
    $id_guru_login = $d_g['id_guru'] ?? '';
}

$id_japel_terpilih = isset($_GET['id_japel']) ? $_GET['id_japel'] : '';
$cari_siswa        = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// ==== SETUP TANGGAL & HARI ====
$hari_map = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu'
];

// Ambil tanggal dari parameter GET, jika tidak ada/kosong maka gunakan tanggal hari ini
$tanggal_aktif = isset($_GET['tanggal']) && !empty($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Deteksi nama hari berdasarkan tanggal yang dipilih
$namahari_en   = date('l', strtotime($tanggal_aktif));
$hari_otomatis = $hari_map[$namahari_en] ?? '';

// === SIMPAN / UPDATE ABSEN ===    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $post_id_japel = mysqli_real_escape_string($conn, $_POST['id_japel']);
    $post_tanggal  = mysqli_real_escape_string($conn, $_POST['Tanggal']);

    // Mengambil id_kelas by id_japel yang di-post
    $query_cek_kelas = mysqli_query($conn, "SELECT id_kelas FROM japel WHERE id_japel ='$post_id_japel' LIMIT 1");
    $data_cek_kelas  = mysqli_fetch_assoc($query_cek_kelas);
    $id_kelas_cek    = $data_cek_kelas['id_kelas'] ?? '';

    // Hitung total siswa aktif yang wajib diabsen di kelas tersebut
    $query_total_siswa = mysqli_query($conn, "SELECT COUNT(*) as total FROM siswa_kelas WHERE id_kelas ='$id_kelas_cek' AND status ='Aktif'");
    $data_total_siswa  = mysqli_fetch_assoc($query_total_siswa);
    $total_wajib_absen = $data_total_siswa['total'] ?? 0;

    // Menghitung berapa siswa yang status absennya sudah dipilih pada form
    $total_diinput = 0;
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $st) {
            if (!empty($st)) $total_diinput++;
        }
    }

    // Validasi kelengkapan absen
    if ($total_diinput < $total_wajib_absen) {
        header("Location: dashboard.php?page=absensi");
        exit;            
    } else {
        // Folder tujuan upload file surat
        $target_dir = "../uploads/surat_absensi/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Jalankan query insert / update
        foreach ($_POST['status'] as $id_siswa_kelas => $status_hadir) {
            $id_sk_esc   = mysqli_real_escape_string($conn, $id_siswa_kelas);
            $status_esc  = mysqli_real_escape_string($conn, $status_hadir);
            
            // Logika Catatan
            $catatan_raw = $_POST['catatan'][$id_siswa_kelas] ?? '';
            if ($status_hadir === 'Hadir' || empty($catatan_raw)) {
                $catatan_raw = '-';
            }
            $catatan_esc = mysqli_real_escape_string($conn, $catatan_raw);

            // Cek file surat lama di database
            $q_existing = mysqli_query($conn, "SELECT file_surat FROM absensi WHERE id_siswa_kelas = '$id_sk_esc' AND id_japel = '$post_id_japel' AND tanggal = '$post_tanggal' LIMIT 1");
            $d_existing = mysqli_fetch_assoc($q_existing);
            $file_surat_name = $d_existing['file_surat'] ?? null;

            // Proses Upload File jika status 'Sakit' & ada file baru yang diunggah
            if ($status_hadir === 'Sakit' && isset($_FILES['file_surat']['name'][$id_siswa_kelas]) && $_FILES['file_surat']['error'][$id_siswa_kelas] === UPLOAD_ERR_OK) {
                $file_tmp  = $_FILES['file_surat']['tmp_name'][$id_siswa_kelas];
                $orig_name = $_FILES['file_surat']['name'][$id_siswa_kelas];
                $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                
                $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
                if (in_array($ext, $allowed_ext)) {
                    $new_file_name = "surat_" . $id_sk_esc . "_" . time() . "_" . rand(100, 999) . "." . $ext;
                    if (move_uploaded_file($file_tmp, $target_dir . $new_file_name)) {
                        // Hapus file lama jika ada
                        if ($file_surat_name && file_exists($target_dir . $file_surat_name)) {
                            unlink($target_dir . $file_surat_name);
                        }
                        $file_surat_name = $new_file_name;
                    }
                }
            } elseif ($status_hadir !== 'Sakit') {
                // Jika status diubah dari Sakit ke yang lain, hapus file lama
                if ($file_surat_name && file_exists($target_dir . $file_surat_name)) {
                    unlink($target_dir . $file_surat_name);
                }
                $file_surat_name = null;
            }

            $file_surat_esc = $file_surat_name ? "'" . mysqli_real_escape_string($conn, $file_surat_name) . "'" : "NULL";

            mysqli_query($conn, "INSERT INTO absensi (id_japel, tanggal, status_hadir, keterangan, file_surat, id_siswa_kelas, created_by)
                                VALUES ('$post_id_japel', '$post_tanggal', '$status_esc', '$catatan_esc', $file_surat_esc, '$id_sk_esc', '$id_guru_login')
                                ON DUPLICATE KEY UPDATE status_hadir = '$status_esc', keterangan = '$catatan_esc', file_surat = $file_surat_esc")
                                or die("Error SQL simpan: " . mysqli_error($conn));
        }
        
        $_SESSION['flash_msg'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data absensi siswa berhasil disimpan.'
        ];

        header("Location: dashboard.php?page=absensi");
        exit;
    }
}

// === Mengambil ID Kelas Berdasarkan Japel DAN Hari yang Cocok ===
$id_kelas_terpilih = null;
if (!empty($id_japel_terpilih)) {
    $id_japel_esc = mysqli_real_escape_string($conn, $id_japel_terpilih);
    $query_kelas = mysqli_query($conn, "SELECT id_kelas FROM japel WHERE id_japel = '$id_japel_esc' AND hari = '$hari_otomatis' LIMIT 1")
                                 or die("Error SQL : ". mysqli_error($conn));
    $data_kelas = mysqli_fetch_assoc($query_kelas);
    $id_kelas_terpilih = $data_kelas['id_kelas'] ?? null;
    
    if (!$id_kelas_terpilih) {
        $id_japel_terpilih = '';
    }
}
?>

<style>
    /* STYLING STATUS PILL RADIO BUTTONS */
    .status-toggle {
        display: flex;
        gap: 6px;
        justify-content: center;
    }
    .status-check {
        display: none;
    }
    .status-pill {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background-color: #f8fafc;
        color: #64748b;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        user-select: none;
    }
    .status-pill:hover {
        background-color: #e2e8f0;
    }
    
    /* Active States */
    .status-check:checked + .pill-hadir {
        background-color: #dcfce7;
        color: #15803d;
        border-color: #86efac;
    }
    .status-check:checked + .pill-izin {
        background-color: #e0f2fe;
        color: #0369a1;
        border-color: #7dd3fc;
    }
    .status-check:checked + .pill-sakit {
        background-color: #fef3c7;
        color: #b45309;
        border-color: #fde047;
    }
    .status-check:checked + .pill-alfa {
        background-color: #fee2e2;
        color: #b91c1c;
        border-color: #fca5a5;
    }
    .status-check:disabled + .status-pill {
        opacity: 0.7;
        cursor: not-allowed;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>

<!-- HEADER (Judul Page) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Menu Absensi Siswa</h4>
        <p class="text-muted small mb-0">Input kehadiran siswa berdasarkan jadwal pelajaran hari <strong><?= $hari_otomatis ?></strong></p>
    </div>    
</div>

<!-- FILTER CARD -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">

        <form method="GET" action="dashboard.php" class="row g-3 align-items-end" id="form-filter">
            <input type="hidden" name="page" value="absensi">

            <!-- INPUT TANGGAL -->
            <div class="col-md-3">
                <label class="form-label small fw-bold">Pilih Tanggal Absen :</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-primary border-end-0">
                        <i class="bi bi-calendar-event"></i>
                    </span>
                    <input type="date" name="tanggal" id="input-tanggal" class="form-control border-start-0" 
                           value="<?= htmlspecialchars($tanggal_aktif) ?>" 
                           onchange="ubahTanggal()">
                </div>
            </div>

            <!-- DROPDOWN KELAS -->
            <div class="col-md-4">
                <label class="form-label small fw-bold">Pilih Kelas - Mapel (Hari <?= $hari_otomatis ?>) : </label>
                <select name="id_japel" id="select-japel" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $query_options = mysqli_query($conn, "SELECT jp.id_japel, k.nama_kelas, m.nama_mapel
                                                          FROM japel jp
                                                          JOIN kelas k ON jp.id_kelas = k.id_kelas
                                                          JOIN mapel m ON jp.id_mapel = m.id_mapel
                                                          WHERE jp.id_guru = '$id_guru_login' 
                                                          AND jp.hari = '$hari_otomatis'");

                    if ($query_options && mysqli_num_rows($query_options) > 0) {
                        while ($row = mysqli_fetch_assoc($query_options)) {
                            $selected = ($id_japel_terpilih == $row['id_japel']) ? 'selected' : '';
                            echo "<option value='{$row['id_japel']}' $selected>{$row['nama_kelas']} - {$row['nama_mapel']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Tidak ada jadwal untuk hari $hari_otomatis</option>";
                    }                            
                    ?>
                </select>
            </div>
            
            <!-- SEARCH -->
            <div class="col-md-3">
                <label class="form-label small fw-bold">Cari Siswa :</label>
                <input type="text" name="cari" class="form-control" placeholder="Ketik nama siswa..."
                       value="<?= htmlspecialchars($cari_siswa) ?>">
            </div>

            <!-- TOMBOL SUBMIT -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" title="Cari">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
            </div>

        </form>
    </div>
</div>

<!-- TABLE CONTENT -->
<?php if (!empty($id_japel_terpilih) && !empty($id_kelas_terpilih)): ?>

 <div class="card border-0 shadow-sm">
    <div class="card-body">
        
        <?php
            $id_japel_esc = mysqli_real_escape_string($conn, $id_japel_terpilih);
            $q_cek_absen = mysqli_query($conn, "SELECT COUNT(*) as total FROM absensi WHERE id_japel = '$id_japel_esc' AND tanggal = '$tanggal_aktif'");
            $d_cek_absen = mysqli_fetch_assoc($q_cek_absen);
            $ada_data_tersimpan = ($d_cek_absen['total'] ?? 0) > 0;
        ?>

        <!-- HEADER TABEL & TOMBOL EDIT -->
        <?php if ($ada_data_tersimpan): ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                    <i class="bi bi-check-circle-fill me-1"></i> Absensi Sudah Tersimpan
                </span>
                <button type="button" id="btn-toggle-edit" class="btn btn-outline-warning btn-sm fw-semibold" onclick="toggleEditMode()">
                    <i class="bi bi-pencil-square me-1"></i> Ubah / Edit Absensi
                </button>
            </div>
        <?php endif; ?>

        <!-- FORM ABSENSI -->
        <form method="POST" action="dashboard.php?page=absensi" id="form-absensi" enctype="multipart/form-data">

            <div class="table-responsive">
                
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="30%">Nama Siswa</th>
                            <th width="35%" class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <span>Kehadiran</span>
                                    <!-- CHECKBOX HADIR SEMUA -->
                                    <div class="form-check m-0 ms-2" title="Centang untuk membuat semua siswa Hadir">
                                        <input class="form-check-input cursor-pointer" type="checkbox" id="check-hadir-semua" onchange="toggleHadirSemua(this)" <?= $ada_data_tersimpan ? 'disabled' : '' ?>>
                                        <label class="form-check-label small fw-bold text-success cursor-pointer" for="check-hadir-semua">
                                            Hadir Semua
                                        </label>
                                    </div>
                                </div>
                            </th>
                            <th width="30%">Catatan / File Surat</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                    <?php
                        $cari_esc    = mysqli_real_escape_string($conn, $cari_siswa);
                        $filter_nama = !empty($cari_siswa) ? "AND s.nama_siswa LIKE '%$cari_esc%'" : "";

                        $query_siswa = mysqli_query($conn, "SELECT sk.id AS id_siswa_kelas, s.nama_siswa, a.status_hadir, a.keterangan, a.file_surat
                                                            FROM siswa_kelas sk
                                                            JOIN siswa s ON sk.id_siswa = s.id_siswa
                                                            LEFT JOIN absensi a
                                                            ON a.id_siswa_kelas = sk.id
                                                            AND a.id_japel = '$id_japel_esc'
                                                            AND a.tanggal = '$tanggal_aktif'                                                                
                                                            WHERE sk.id_kelas = '$id_kelas_terpilih'
                                                            AND sk.status = 'Aktif'
                                                            $filter_nama
                                                            ORDER BY s.nama_siswa ASC") 
                                                            or die("Error SQL : " . mysqli_error($conn));
                        $no = 1;
                        while ($siswa = mysqli_fetch_assoc($query_siswa)) {
                            $status_sekarang = $siswa['status_hadir'] ?? '';
                            $sudah_tersimpan = !empty($status_sekarang);
                            $id_sk           = $siswa['id_siswa_kelas'];
                            $catatan_val      = $siswa['keterangan'] ?? '';
                            $file_surat      = $siswa['file_surat'] ?? null;
                            ?>

                            <tr data-sk="<?= $id_sk ?>">
                                <td><?= $no++ ?></td>
                                <td class="fw-bold text-dark">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                    <?php if ($sudah_tersimpan): ?>
                                        <span class="badge bg-success-subtle text-success ms-1 badge-status-tersimpan" style="font-size:0.65rem">Tersimpan</span>
                                   <?php endif; ?>
                                </td>
                                <td>                                        
                                    <div class="status-toggle">
                                        <?php
                                        $icon_map = ['Hadir' => 'bi-check-circle', 'Izin' => 'bi-envelope', 'Sakit' => 'bi-thermometer-half', 'Alfa' => 'bi-x-circle'];
                                        foreach (['Hadir','Izin','Sakit','Alfa'] as $opsi):
                                            $input_id   = 'opsi_' . $id_sk . '_' . $opsi;
                                            $pill_class = 'pill-' . strtolower($opsi);
                                        ?>
                                            <input type="radio" class="status-check" id="<?= $input_id ?>"
                                                name="status[<?= $id_sk ?>]" value="<?= $opsi ?>"
                                                onchange="handleStatusChange(this, '<?= $id_sk ?>')"
                                                <?= $status_sekarang === $opsi ? 'checked' : '' ?>
                                                <?= $sudah_tersimpan ? 'disabled' : '' ?>>
                                            <label for="<?= $input_id ?>" class="status-pill <?= $pill_class ?>">
                                                <i class="bi <?= $icon_map[$opsi] ?>"></i><?= $opsi ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>                                        
                                </td>
                                <td>        
                                    <div class="input-group input-group-sm">
                                        <!-- INPUT CATATAN (Atribut data-db Simpan Kondisi Asli DB) -->
                                        <input type="text" class="form-control input-catatan"
                                            id="catatan_<?= $id_sk ?>"
                                            name="catatan[<?= $id_sk ?>]" 
                                            placeholder="-"
                                            value="<?= htmlspecialchars($catatan_val) ?>"
                                            data-db-catatan="<?= htmlspecialchars($catatan_val) ?>"
                                            data-saved="<?= $sudah_tersimpan ? 'true' : 'false' ?>"
                                            <?= $sudah_tersimpan ? 'readonly' : 'disabled' ?>>

                                        <!-- TOMBOL IKON UPLOAD SURAT -->
                                        <div id="wrapper_file_<?= $id_sk ?>" class="wrapper-file-surat" style="<?= ($status_sekarang === 'Sakit') ? 'display:flex;' : 'display:none;' ?>">
                                            <input type="file" class="d-none input-file-surat"
                                                   id="file_<?= $id_sk ?>"
                                                   name="file_surat[<?= $id_sk ?>]"
                                                   accept=".jpg,.jpeg,.png,.pdf"
                                                   onchange="updateFileName(this, '<?= $id_sk ?>')"
                                                   <?= $sudah_tersimpan ? 'disabled' : '' ?>>

                                            <label for="file_<?= $id_sk ?>" id="btn_file_label_<?= $id_sk ?>" class="btn btn-outline-secondary" title="Upload Surat Dokter/Keterangan">
                                                <i class="bi bi-paperclip"></i>
                                            </label>
                                        </div>

                                        <!-- LINK PREVIEW SURAT -->
                                        <?php if (!empty($file_surat)): ?>
                                            <a href="../uploads/surat_absensi/<?= htmlspecialchars($file_surat) ?>" target="_blank" class="btn btn-info text-white" title="Lihat Surat Dokter/Keterangan">
                                                <i class="bi bi-file-earmark-medical"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Teks Indikator Nama File Terpilih -->
                                    <div id="file_name_text_<?= $id_sk ?>" class="text-primary mt-1" style="font-size: 0.75rem; display: none;"></div>
                                </td>                                            
                            </tr>
                            <?php                    
                        }
                    ?>
                    </tbody>

                </table>

                <div class="text-end mt-3" id="wrapper-btn-simpan">
                    <button type="submit" id="btn-simpan" class="btn btn-success fw-semibold" <?= $ada_data_tersimpan ? 'style="display:none;"' : '' ?>>
                        <i class="bi bi-floppy-fill me-1"></i> Simpan Absensi
                    </button>        
                </div>

            </div>

            <input type="hidden" name="id_japel" value="<?= htmlspecialchars($id_japel_terpilih) ?>">
            <input type="hidden" name="Tanggal" value="<?= htmlspecialchars($tanggal_aktif) ?>">
        </form>
    </div>
 </div>
<?php endif; ?>

<!-- Load SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let isEditMode = false;
let lastClickedRadio = {}; // Tracker radio yang terakhir diklik
let dbOriginalState = {};  // Backup data asli dari Database saat halaman dimuat

// Otomatis bersihkan URL browser menjadi ?page=absensi
if (window.history.replaceState) {
    window.history.replaceState(null, null, "dashboard.php?page=absensi");
}

// Function saat tanggal diganti: Reset pilihan kelas dan submit form GET
function ubahTanggal() {
    const selectJapel = document.getElementById('select-japel');
    if (selectJapel) {
        selectJapel.value = ''; 
    }
    document.getElementById('form-filter').submit();
}

// Function untuk memperbarui indikator nama file saat file dipilih
function updateFileName(inputElem, idSk) {
    const textElem = document.getElementById('file_name_text_' + idSk);
    const labelBtn = document.getElementById('btn_file_label_' + idSk);

    if (inputElem.files && inputElem.files[0]) {
        const fileName = inputElem.files[0].name;
        if (textElem) {
            textElem.innerText = "📎 File dipilih: " + fileName;
            textElem.style.display = 'block';
        }
        if (labelBtn) {
            labelBtn.classList.remove('btn-outline-secondary');
            labelBtn.classList.add('btn-success', 'text-white');
        }
    } else {
        if (textElem) textElem.style.display = 'none';
        if (labelBtn) {
            labelBtn.classList.remove('btn-success', 'text-white');
            labelBtn.classList.add('btn-outline-secondary');
        }
    }
}

// Toggle Hadir Semua
function toggleHadirSemua(masterCheckbox) {
    const rows = document.querySelectorAll('tbody tr[data-sk]');
    const isChecked = masterCheckbox.checked;

    rows.forEach(row => {
        const idSk = row.getAttribute('data-sk');
        const radioHadir = document.getElementById('opsi_' + idSk + '_Hadir');
        
        if (radioHadir && !radioHadir.disabled) {
            if (isChecked) {
                radioHadir.checked = true;
                lastClickedRadio[idSk] = radioHadir;
                handleStatusChange(radioHadir, idSk, false);
            } else {
                radioHadir.checked = false;
                delete lastClickedRadio[idSk];
                resetStatusInput(idSk);
            }
        }
    });
}

// Synkronisasi Checkbox Master "Hadir Semua"
function syncMasterCheckbox() {
    const masterCheckbox = document.getElementById('check-hadir-semua');
    if (!masterCheckbox) return;

    const rows = document.querySelectorAll('tbody tr[data-sk]');
    let totalSiswa = rows.length;
    let countHadir = 0;

    rows.forEach(row => {
        const checkedRadio = row.querySelector('input[type="radio"]:checked');
        if (checkedRadio && checkedRadio.value === 'Hadir') {
            countHadir++;
        }
    });

    masterCheckbox.checked = (totalSiswa > 0 && countHadir === totalSiswa);
}

// Helper untuk reset input catatan dan file ke kondisi belum dipilih
function resetStatusInput(idSk) {
    const inputCatatan = document.getElementById('catatan_' + idSk);
    const wrapperFile  = document.getElementById('wrapper_file_' + idSk);
    const inputFile    = document.getElementById('file_' + idSk);
    const textElem     = document.getElementById('file_name_text_' + idSk);
    const labelBtn     = document.getElementById('btn_file_label_' + idSk);

    if (inputCatatan) {
        inputCatatan.value = '';
        inputCatatan.placeholder = '-';
        inputCatatan.readOnly = true;
        inputCatatan.disabled = true;
    }
    if (wrapperFile) wrapperFile.style.display = 'none';
    if (inputFile) inputFile.value = '';
    if (textElem) textElem.style.display = 'none';
    if (labelBtn) {
        labelBtn.classList.remove('btn-success', 'text-white');
        labelBtn.classList.add('btn-outline-secondary');
    }
}

// Toggle Mode Edit Absensi
function toggleEditMode() {
    isEditMode = !isEditMode;
    const btnToggle = document.getElementById('btn-toggle-edit');
    const btnSimpan = document.getElementById('btn-simpan');
    const masterCheckbox = document.getElementById('check-hadir-semua');

    const radioInputs = document.querySelectorAll('.status-check');
    const fileInputs  = document.querySelectorAll('.input-file-surat');
    const rows        = document.querySelectorAll('tbody tr[data-sk]');

    if (isEditMode) {
        // Mode EDIT Aktif
        btnToggle.className = 'btn btn-secondary btn-sm fw-semibold';
        btnToggle.innerHTML = '<i class="bi bi-x-circle me-1"></i> Batal Edit';
        btnSimpan.style.display = 'inline-block';
        if (masterCheckbox) masterCheckbox.disabled = false;

        radioInputs.forEach(input => input.disabled = false);
        fileInputs.forEach(input => input.disabled = false);

        rows.forEach(row => {
            const idSk = row.getAttribute('data-sk');
            const checkedRadio = row.querySelector('input[type="radio"]:checked');
            const inputCatatan = document.getElementById('catatan_' + idSk);

            if (inputCatatan) {
                inputCatatan.setAttribute('data-saved', 'false');
                if (checkedRadio) {
                    lastClickedRadio[idSk] = checkedRadio;
                    handleStatusChange(checkedRadio, idSk, false);
                }
            }
        });
    } else {
        // Mode EDIT Dibatalkan -> RESTORE KE DATA DB SEMULA
        btnToggle.className = 'btn btn-outline-warning btn-sm fw-semibold';
        btnToggle.innerHTML = '<i class="bi bi-pencil-square me-1"></i> Ubah / Edit Absensi';
        btnSimpan.style.display = 'none';
        if (masterCheckbox) masterCheckbox.disabled = true;

        rows.forEach(row => {
            const idSk = row.getAttribute('data-sk');
            const origStatus  = dbOriginalState[idSk] ? dbOriginalState[idSk].status : '';
            const origCatatan = dbOriginalState[idSk] ? dbOriginalState[idSk].catatan : '';

            const inputCatatan = document.getElementById('catatan_' + idSk);
            if (inputCatatan) {
                inputCatatan.setAttribute('data-saved', 'true');
                inputCatatan.value = origCatatan;
                inputCatatan.readOnly = true;
            }

            // Kembalikan checked radio button ke kondisi database semula
            const radios = row.querySelectorAll('input[type="radio"]');
            radios.forEach(r => {
                if (r.value === origStatus) {
                    r.checked = true;
                    lastClickedRadio[idSk] = r;
                } else {
                    r.checked = false;
                }
                r.disabled = true;
            });

            // Sesuaikan kembali elemen UI catatan/file
            if (origStatus === 'Sakit') {
                const wrapperFile = document.getElementById('wrapper_file_' + idSk);
                if (wrapperFile) wrapperFile.style.display = 'flex';
            } else {
                const wrapperFile = document.getElementById('wrapper_file_' + idSk);
                if (wrapperFile) wrapperFile.style.display = 'none';
            }
        });

        fileInputs.forEach(input => {
            input.value = '';
            input.disabled = true;
        });

        syncMasterCheckbox();
    }
}

// Handler Perubahan Pilihan Kehadiran (Radio Button)
function handleStatusChange(radioElem, idSk, triggerSync = true) {
    const inputCatatan  = document.getElementById('catatan_' + idSk);
    const wrapperFile   = document.getElementById('wrapper_file_' + idSk);
    const inputFile     = document.getElementById('file_' + idSk);
    const textElem      = document.getElementById('file_name_text_' + idSk);
    const labelBtn      = document.getElementById('btn_file_label_' + idSk);

    if (!inputCatatan) return;

    if (inputCatatan.getAttribute('data-saved') === 'true' && !isEditMode) return;

    const val = radioElem.value;

    if (val === 'Hadir') {
        inputCatatan.value = '-';
        inputCatatan.placeholder = '-';
        inputCatatan.readOnly = true;
        inputCatatan.disabled = false;
        
        if (wrapperFile) wrapperFile.style.display = 'none';
        if (inputFile) inputFile.value = '';
        if (textElem) textElem.style.display = 'none';
        if (labelBtn) {
            labelBtn.classList.remove('btn-success', 'text-white');
            labelBtn.classList.add('btn-outline-secondary');
        }

    } else if (val === 'Sakit') {
        if (inputCatatan.value === '-') {
            inputCatatan.value = '';
        }
        inputCatatan.placeholder = 'Ketik keterangan sakit...';
        inputCatatan.readOnly = false;
        inputCatatan.disabled = false;
        
        if (wrapperFile) wrapperFile.style.display = 'flex';

    } else if (val === 'Izin' || val === 'Alfa') {
        if (inputCatatan.value === '-') {
            inputCatatan.value = '';
        }
        inputCatatan.placeholder = 'Ketik alasan...';
        inputCatatan.readOnly = false;
        inputCatatan.disabled = false;
        
        if (wrapperFile) wrapperFile.style.display = 'none';
        if (inputFile) inputFile.value = '';
        if (textElem) textElem.style.display = 'none';
        if (labelBtn) {
            labelBtn.classList.remove('btn-success', 'text-white');
            labelBtn.classList.add('btn-outline-secondary');
        }
    }

    if (triggerSync) {
        syncMasterCheckbox();
    }
}

// Inisialisasi awal & Event Listener Click Uncheck
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr[data-sk]');
    
    rows.forEach(function(row) {
        const idSk = row.getAttribute('data-sk');
        const checkedRadio = row.querySelector('input[type="radio"]:checked');
        const inputCatatan = document.getElementById('catatan_' + idSk);

        // BACKUP DATA ASLI DARI DATABASE
        dbOriginalState[idSk] = {
            status: checkedRadio ? checkedRadio.value : '',
            catatan: inputCatatan ? inputCatatan.getAttribute('data-db-catatan') : ''
        };

        if (checkedRadio && inputCatatan) {
            lastClickedRadio[idSk] = checkedRadio;
            handleStatusChange(checkedRadio, idSk, false);
        }

        // Event listener klik pada radio button (Toggle 2x Uncheck)
        const radios = row.querySelectorAll('input[type="radio"]');
        radios.forEach(radio => {
            radio.addEventListener('click', function(e) {
                if (this.disabled) return;

                if (lastClickedRadio[idSk] === this) {
                    this.checked = false;
                    delete lastClickedRadio[idSk];
                    resetStatusInput(idSk);
                    syncMasterCheckbox();
                } else {
                    lastClickedRadio[idSk] = this;
                }
            });
        });
    });

    syncMasterCheckbox();
});

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

const formAbsensi = document.getElementById('form-absensi');
if (formAbsensi) {
    formAbsensi.addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('tbody tr');
        let adaYangBelumDiisi = false;
        let jumlahBelumDiisi = 0;

        rows.forEach(function(row) {
            const radios = row.querySelectorAll('input[type="radio"]');
            if (radios.length === 0) return; 

            const nameGroup = radios[0].name;
            const checkedRadio = row.querySelector(`input[name="${nameGroup}"]:checked`);

            if (!checkedRadio) {
                adaYangBelumDiisi = true;
                jumlahBelumDiisi++;
            }
        });

        if (adaYangBelumDiisi) {
            e.preventDefault(); 
            
            Toast.fire({
                icon: 'error',
                title: 'Gagal Menyimpan!',
                text: 'Ada ' + jumlahBelumDiisi + ' siswa yang belum dipilih status kehadirannya.'
            });
        }
    });
}
</script>

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