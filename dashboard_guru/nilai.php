<?php
/** @var mysqli $conn */
/** @var string $id_guru_login */
/** @var string $username */

$id_japel_terpilih = isset($_GET['id_japel']) ? $_GET['id_japel'] : '';
$cari_siswa        = isset($_GET['cari']) ? trim($_GET['cari']) : '';

//PROSES SIMPAN NILAI / SAVE
$pesan_sukses = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tugas'])) {
    $post_id_japel  = mysqli_real_escape_string($conn, $_POST['id_japel']);
    $post_id_tahun  = mysqli_real_escape_string($conn, $_POST['id_tahun']);
    $post_semester  = mysqli_real_escape_string($conn, $_POST['semester']);
    $post_kkm       = (float) $_POST['kkm'];

    $jumlah_diproses = 0;
    foreach ($_POST['tugas'] as $id_siswa_kelas => $nilai_tugas) {
        $val_tugas = $_POST['tugas'][$id_siswa_kelas] ?? '';
        $val_uts   = $_POST['uts'][$id_siswa_kelas] ?? '';
        $val_uas   = $_POST['uas'][$id_siswa_kelas] ?? '';

        // skip siswa jika nilainya masih belum diinput atau kosong
        if ($val_tugas === '' || $val_uts === '' || $val_uas === '') {
            continue;
        
        }
        $jumlah_diproses++;
        
            

        $id_sk_esc = mysqli_real_escape_string($conn, $id_siswa_kelas);

        $tugas = max(0, min(100, (float) $nilai_tugas));
        $uts   = max(0, min(100, (float) ($_POST['uts'][$id_siswa_kelas] ?? 0)));
        $uas   = max(0, min(100, (float) ($_POST['uas'][$id_siswa_kelas] ?? 0)));

        $nilai_akhir = ($tugas * 0.2) + ($uts * 0.3) + ($uas * 0.5);
        $keterangan  = $nilai_akhir >= $post_kkm ? 'Tuntas' : 'Belum Tuntas';

        mysqli_query($conn, "INSERT INTO nilai 
                              (id_japel, id_siswa_kelas, id_tahun, semester, tugas, uts, uas, nilai_akhir, keterangan, created_by)
                              VALUES ('$post_id_japel', '$id_sk_esc', '$post_id_tahun', '$post_semester', 
                                      $tugas, $uts, $uas, $nilai_akhir, '$keterangan', '$id_guru_login')
                              ON DUPLICATE KEY UPDATE 
                                    tugas = $tugas, uts = $uts, uas = $uas, 
                                    nilai_akhir = $nilai_akhir, keterangan = '$keterangan'")
                              or die("Error SQL simpan: " . mysqli_error($conn));
    }

    $pesan_sukses      = "Nilai berhasil disimpan!";
    $id_japel_terpilih = $post_id_japel;

    // Set Session manual agar terbaca oleh Javascript Toast di baris paling bawah
    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => $pesan_sukses
    ];

    header("Location: dashboard.php?page=nilai&id_japel=" . $post_id_japel);
    exit;
}


//SEMESTER & TAHUN AJARAN AKTIF
$query_tahun_aktif = mysqli_query($conn, "SELECT ta.id_tahun, ta.semester, t.tahun 
                                          FROM tahun_ajaran ta
                                          JOIN tahun t ON ta.id_tahun = t.id_tahun
                                          WHERE ta.status = 'Aktif' AND ta.deleted = 0 LIMIT 1")
                                          or die("Error SQL : " . mysqli_error($conn));

$data_tahun_aktif    = mysqli_fetch_assoc($query_tahun_aktif);
$id_tahun_aktif      = $data_tahun_aktif['id_tahun'] ?? null;
$semester_aktif      = $data_tahun_aktif['semester'] ?? null;
$tahun_ajaran_aktif  = $data_tahun_aktif['tahun'] ?? null;

//PULL DATA DARI JAPEL TERPILIH (id_kelas,id_mapel,kkm)
$id_kelas_terpilih = null;
$id_mapel_terpilih = null;
$kkm_terpilih       = null;

if (!empty($id_japel_terpilih)) {
    $id_japel_esc = mysqli_real_escape_string($conn, $id_japel_terpilih);
    $query_japel  = mysqli_query($conn, "SELECT jp.id_kelas, jp.id_mapel, m.kkm
                                          FROM japel jp
                                          JOIN mapel m ON jp.id_mapel = m.id_mapel
                                          WHERE jp.id_japel = '$id_japel_esc' LIMIT 1")
                                          or die("Error SQL : " . mysqli_error($conn));
    $data_japel = mysqli_fetch_assoc($query_japel);
    $id_kelas_terpilih = $data_japel['id_kelas'] ?? null;
    $id_mapel_terpilih = $data_japel['id_mapel'] ?? null;
    $kkm_terpilih       = $data_japel['kkm'] ?? 75; // fallback 75 kalau entah kenapa null
}

?>

<!-- HEADER (Judul Page) -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Menu Nilai Siswa</h4>
        <p class="text-muted small mb-0"> Input semua nilai siswa berdasarkan mata pelajaran</p>
    </div>    
</div>

<!-- FILTER CARD -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">

        <div class="d-flex align-items-center gap-3 mb-2 flex-wrap">            
            <!-- DISPLAY TAHUN AJARAN AKTIF -->
            <div>
                <label class="form-label small fw-bold mb-1 me-2">Semester Aktif :</label>
                <span class="badge bg-light text-dark border px-3 py-2">
                    <i class="bi bi-calendar3 me-1"></i>
                    <?= htmlspecialchars($semester_aktif ?? '-') ?> — TA <?= htmlspecialchars($tahun_ajaran_aktif ?? '-') ?>
                </span>
            </div>
        </div>                    

        <form method="GET" action="dashboard.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="nilai">

            <!-- DROPDOWN KELAS -->
            <div class="col-md-5">
                <label class="form-label small fw-bold">Pilih Kelas - Mapel : </label>
                <select name="id_japel" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    

                    //QUERY SQL utk mengambil jadwal pelajaran khusus guru yang sedang login
                    $query_options = mysqli_query($conn, "SELECT jp.id_japel, k.nama_kelas, m.nama_mapel
                                                          FROM japel jp
                                                          JOIN kelas k ON jp.id_kelas = k.id_kelas
                                                          JOIN mapel m ON jp.id_mapel = m.id_mapel
                                                          WHERE jp.id_guru = '$id_guru_login' AND jp.deleted = 0")
                                                          or die ("Error SQL : ". mysqli_error($conn));
                    
                    while ($row = mysqli_fetch_assoc($query_options)){
                        $selected = ($id_japel_terpilih == $row['id_japel']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['id_japel']) . "' $selected>"
                           . htmlspecialchars($row['nama_kelas']) . " - " . htmlspecialchars($row['nama_mapel'])
                           . "</option>";
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

            <!-- TOMBOL SUBMIT (fallback + trigger buat search) -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary fw-semibold w-100">
                    <i class="bi bi-search me-2"></i>Cari
                </button>
            </div>

        </form>
    </div>
</div>


<!-- TABLE CARD -->
<?php if (!empty($id_japel_terpilih) && !empty($id_kelas_terpilih)): ?>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="dashboard.php?page=nilai" id="form-nilai">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="30%">Nama Siswa</th>
                                <th width="12%" class="text-center">Tugas</th>
                                <th width="12%" class="text-center">UTS</th>
                                <th width="12%" class="text-center">UAS</th>
                                <th width="20%" class="text-center">Nilai Akhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $id_japel_esc = mysqli_real_escape_string($conn, $id_japel_terpilih);
                            $cari_esc     = mysqli_real_escape_string($conn, $cari_siswa);
                            $filter_nama  = !empty($cari_siswa) ? "AND s.nama_siswa LIKE '%$cari_esc%'" : "";

                            $query_siswa = mysqli_query($conn, "SELECT sk.id AS id_siswa_kelas, s.nama_siswa,
                                                                        n.tugas, n.uts, n.uas, n.nilai_akhir, n.keterangan
                                                                 FROM siswa_kelas sk
                                                                 JOIN siswa s ON sk.id_siswa = s.id_siswa
                                                                 LEFT JOIN nilai n 
                                                                        ON n.id_siswa_kelas = sk.id
                                                                       AND n.id_japel = '$id_japel_esc'
                                                                       AND n.semester = '$semester_aktif'
                                                                       AND n.id_tahun = '$id_tahun_aktif'
                                                                 WHERE sk.id_kelas = '$id_kelas_terpilih'
                                                                       AND sk.status = 'Aktif'
                                                                       $filter_nama
                                                                 ORDER BY s.nama_siswa ASC")
                                                                 or die("Error SQL : " . mysqli_error($conn));

                            $no = 1;
                            while ($siswa = mysqli_fetch_assoc($query_siswa)) {
                                $sudah_tersimpan = !is_null($siswa['nilai_akhir']);
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-bold text-dark">
                                        <?= htmlspecialchars($siswa['nama_siswa']) ?>
                                        <?php if ($sudah_tersimpan): ?>
                                            <span class="badge bg-success-subtle text-success ms-1" style="font-size:0.65rem">Tersimpan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm nilai-input"
                                               data-id="<?= $siswa['id_siswa_kelas'] ?>" data-jenis="tugas"
                                               name="tugas[<?= $siswa['id_siswa_kelas'] ?>]"
                                               value="<?= $siswa['tugas'] !== null ? htmlspecialchars($siswa['tugas']) : '' ?>"
                                               <?= $sudah_tersimpan ? 'readonly' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm nilai-input"
                                               data-id="<?= $siswa['id_siswa_kelas'] ?>" data-jenis="uts"
                                               name="uts[<?= $siswa['id_siswa_kelas'] ?>]"
                                               value="<?= $siswa['uts'] !== null ? htmlspecialchars($siswa['uts']) : '' ?>"
                                               <?= $sudah_tersimpan ? 'readonly' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm nilai-input"
                                               data-id="<?= $siswa['id_siswa_kelas'] ?>" data-jenis="uas"
                                               name="uas[<?= $siswa['id_siswa_kelas'] ?>]"
                                               value="<?= $siswa['uas'] !== null ? htmlspecialchars($siswa['uas']) : '' ?>"
                                               <?= $sudah_tersimpan ? 'readonly' : '' ?>>
                                    </td>
                                    <td class="text-center">
                                        <span id="hasil_<?= $siswa['id_siswa_kelas'] ?>" class="fw-bold">
                                            <?= $siswa['nilai_akhir'] !== null ? number_format($siswa['nilai_akhir'], 2) : '0.00' ?>
                                        </span>
                                        <?php if ($sudah_tersimpan): ?>
                                            <br>
                                            <span class="badge <?= $siswa['keterangan'] === 'Tuntas' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>" style="font-size:0.65rem">
                                                <?= htmlspecialchars($siswa['keterangan']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>

                    <div class="text-end mt-3">
                         <button type="button" id="btn-simpan-nilai" class="btn btn-success fw-semibold">
                            <i class="bi bi-floppy-fill me-2"></i>Simpan Nilai
                        </button>
                    </div>
                </div>

                <!-- HIDDEN INPUT: konteks yang wajib ikut terkirim saat submit -->
                <input type="hidden" name="id_japel" value="<?= htmlspecialchars($id_japel_terpilih) ?>">
                <input type="hidden" name="id_tahun" value="<?= htmlspecialchars($id_tahun_aktif) ?>">
                <input type="hidden" name="semester" value="<?= htmlspecialchars($semester_aktif) ?>">
                <input type="hidden" name="kkm" value="<?= htmlspecialchars($kkm_terpilih) ?>">
            </form>
        </div>
    </div>

<?php endif; ?>

<!-- TOAST CONTAINER -->
<div id="toast-container" class="toast-container"></div>

<!-- CONFIRM MODAL -->
<div id="confirm-modal" class="modal-overlay" style="display:none">
    <div class="modal-box">
        <div class="modal-icon" id="modal-icon">
            <i class="bi bi-question-circle"></i>
        </div>
        <h5 class="modal-title" id="modal-title">Judul Konfirmasi</h5>
        <p class="modal-text" id="modal-text">Isi pesan konfirmasi.</p>
        <div class="modal-actions">
            <button type="button" class="btn-modal btn-modal-cancel" id="modal-cancel">Batal</button>
            <button type="button" class="btn-modal btn-modal-confirm" id="modal-confirm">Ya, Simpan</button>
        </div>
    </div>
</div>

<!-- TAHAP 6 — LIVE CALCULATION (JavaScript) -->

<script>
document.querySelectorAll('.nilai-input').forEach(function (input) {
    input.addEventListener('input', function () {
        const id    = this.dataset.id;
        const tugas = parseFloat(document.querySelector(`[data-id="${id}"][data-jenis="tugas"]`).value) || 0;
        const uts   = parseFloat(document.querySelector(`[data-id="${id}"][data-jenis="uts"]`).value) || 0;
        const uas   = parseFloat(document.querySelector(`[data-id="${id}"][data-jenis="uas"]`).value) || 0;

        const nilaiAkhir = (tugas * 0.2) + (uts * 0.3) + (uas * 0.5);
        document.getElementById(`hasil_${id}`).textContent = nilaiAkhir.toFixed(2);
    });
});

// ===== FUNGSI TOAST (pengganti Swal Toast.fire) =====
function showToast(icon, title, text, duration = 4000) {
    const container = document.getElementById('toast-container');
    const iconMap = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', info: 'bi-info-circle-fill' };

    const toast = document.createElement('div');
    toast.className = `toast-item ${icon}`;
    toast.innerHTML = `
        <i class="bi ${iconMap[icon] || iconMap.info} toast-icon"></i>
        <div class="toast-body">
            <div class="toast-title">${title}</div>
            <div class="toast-text">${text}</div>
        </div>
        <button class="toast-close"><i class="bi bi-x"></i></button>
        <div class="toast-progress" style="animation-duration:${duration}ms"></div>
    `;
    container.appendChild(toast);

    const remove = () => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    };
    toast.querySelector('.toast-close').addEventListener('click', remove);
    setTimeout(remove, duration);
}

// ===== FUNGSI CONFIRM MODAL (pengganti Swal.fire confirm) =====
function showConfirm({ title, html, icon = 'question' }) {
    return new Promise((resolve) => {
        const overlay      = document.getElementById('confirm-modal');
        const iconEl       = document.getElementById('modal-icon');
        const titleEl       = document.getElementById('modal-title');
        const textEl        = document.getElementById('modal-text');
        const btnConfirm   = document.getElementById('modal-confirm');
        const btnCancel     = document.getElementById('modal-cancel');

        titleEl.textContent = title;
        textEl.innerHTML     = html;
        iconEl.className      = icon === 'warning' ? 'modal-icon warning' : 'modal-icon';
        iconEl.innerHTML       = `<i class="bi bi-${icon === 'warning' ? 'exclamation-triangle' : 'question-circle'}"></i>`;

        overlay.style.display = 'flex';

        const cleanup = (result) => {
            overlay.style.display = 'none';
            btnConfirm.removeEventListener('click', onConfirm);
            btnCancel.removeEventListener('click', onCancel);
            resolve(result);
        };
        const onConfirm = () => cleanup(true);
        const onCancel  = () => cleanup(false);

        btnConfirm.addEventListener('click', onConfirm);
        btnCancel.addEventListener('click', onCancel);
    });
}

// ===== VALIDASI FORM SIMPAN NILAI (VERSI REVISI) =====
document.addEventListener("DOMContentLoaded", function() {
    const btnSimpan = document.getElementById('btn-simpan-nilai');
    const formNilai = document.getElementById('form-nilai');

    if (btnSimpan && formNilai) {
        btnSimpan.addEventListener('click', async function () {
            
            const inputs = document.querySelectorAll('.nilai-input:not([readonly])');
            const idSiswaChecked = new Set();
            let jumlahLengkap = 0;
            let jumlahKosong  = 0;

            inputs.forEach(function (input) {
                const id = input.dataset.id;
                // Cegah perhitungan ganda untuk siswa yang sama
                if (idSiswaChecked.has(id)) return;
                idSiswaChecked.add(id);

                const tugas = document.querySelector(`[data-id="${id}"][data-jenis="tugas"]`);
                const uts   = document.querySelector(`[data-id="${id}"][data-jenis="uts"]`);
                const uas   = document.querySelector(`[data-id="${id}"][data-jenis="uas"]`);

                // Pastikan elemen ditemukan dan tidak kosong
                const lengkap = (tugas && uts && uas) && 
                                (tugas.value !== '' && uts.value !== '' && uas.value !== '');
                
                lengkap ? jumlahLengkap++ : jumlahKosong++;
            });

            // Jika sama sekali belum ada yang diisi
            if (jumlahLengkap === 0) {
                showToast('error', 'Belum ada nilai diisi', 'Isi nilai minimal 1 siswa sebelum menyimpan.');
                return;
            }

            // Siapkan pesan konfirmasi
            let pesanHtml = `Nilai <b>${jumlahLengkap} siswa</b> akan disimpan dan terkunci (tidak bisa diedit lagi).`;
            if (jumlahKosong > 0) {
                pesanHtml += `<br><span style="color:#f59e0b">${jumlahKosong} siswa belum lengkap diisi</span> dan bisa dilanjutkan nanti.`;
            }

            // Panggil modal konfirmasi
            const confirmed = await showConfirm({
                title: 'Simpan nilai sekarang?',
                html: pesanHtml,
                icon: jumlahKosong > 0 ? 'warning' : 'question'
            });

            // JIKA USER KLIK "YA, SIMPAN" -> Submit form ke PHP
            if (confirmed) {
                // Nonaktifkan tombol agar tidak diklik 2x
                btnSimpan.disabled = true;
                btnSimpan.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Menyimpan...';
                
                // Submit form secara manual via Javascript
                formNilai.submit();
            }
        });
    }
});



// ===== RENDER FLASH MESSAGE DARI PHP SESSION =====
<?php if (isset($_SESSION['flash_msg'])): ?>
showToast(
    '<?= $_SESSION['flash_msg']['type'] === 'error' ? 'error' : $_SESSION['flash_msg']['type'] ?>',
    '<?= addslashes($_SESSION['flash_msg']['title']) ?>',
    '<?= addslashes($_SESSION['flash_msg']['message']) ?>'
);
<?php unset($_SESSION['flash_msg']); endif; ?>
</script>