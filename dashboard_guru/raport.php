<?php
// Set timezone agar tanggal selalu akurat (WIB)
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

// Mengambil Username & mencari ID Guru
$username = Session::getUsername();
if (!isset($id_guru_login) || empty($id_guru_login)) {
    $q_g = mysqli_query($conn, "SELECT g.id_guru FROM guru g JOIN akun a ON g.id_guru = a.id_guru WHERE a.username = '$username' LIMIT 1");
    $d_g = mysqli_fetch_assoc($q_g);
    $id_guru_login = $d_g['id_guru'] ?? '';
}

$id_kelas_terpilih = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
$cari_siswa        = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// === SIMPAN / UPDATE DATA RAPOR ===    
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['catatan_raport'])) {
    $post_id_kelas = mysqli_real_escape_string($conn, $_POST['id_kelas']);

    foreach ($_POST['catatan_raport'] as $id_siswa_kelas => $catatan) {
        $id_sk_esc       = mysqli_real_escape_string($conn, $id_siswa_kelas);
        $catatan_esc    = mysqli_real_escape_string($conn, $catatan);
        $sikap_sp_esc   = mysqli_real_escape_string($conn, $_POST['sikap_spiritual'][$id_siswa_kelas] ?? 'Baik');
        $sikap_so_esc   = mysqli_real_escape_string($conn, $_POST['sikap_sosial'][$id_siswa_kelas] ?? 'Baik');
        $keputusan_esc  = mysqli_real_escape_string($conn, $_POST['keputusan'][$id_siswa_kelas] ?? 'Naik');

        // Hitung Total Nilai Siswa
        $q_tot = mysqli_query($conn, "SELECT SUM(nilai_akhir) as total FROM nilai WHERE id_siswa_kelas = '$id_sk_esc'");
        $d_tot = mysqli_fetch_assoc($q_tot);
        $total_nilai = $d_tot['total'] ?? 0;

        mysqli_query($conn, "INSERT INTO raport (id_siswa_kelas, total_nilai, catatan, sikap_spiritual, sikap_sosial, keputusan)
                            VALUES ('$id_sk_esc', '$total_nilai', '$catatan_esc', '$sikap_sp_esc', '$sikap_so_esc', '$keputusan_esc')
                            ON DUPLICATE KEY UPDATE 
                                total_nilai = '$total_nilai',
                                catatan = '$catatan_esc', 
                                sikap_spiritual = '$sikap_sp_esc', 
                                sikap_sosial = '$sikap_so_esc', 
                                keputusan = '$keputusan_esc'")
                            or die("Error SQL simpan raport: " . mysqli_error($conn));
    }
    
    $_SESSION['flash_msg'] = [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => 'Data raport siswa berhasil disimpan.'
    ];

    header("Location: dashboard.php?page=raport&id_kelas=" . $post_id_kelas);
    exit;
}
?>

<style>
    .cursor-pointer { cursor: pointer; }
    @media print {
        .no-print { display: none !important; }
        .card { border: none !important; shadow: none !important; }
    }
</style>

<!-- HEADER (Judul Page) -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="fw-bold mb-1">Menu Raport Siswa</h4>
        <p class="text-muted small mb-0">Kelola dan cetak raport hasil belajar siswa per kelas</p>
    </div>    
</div>

<!-- FILTER CARD -->
<div class="card mb-4 border-0 shadow-sm no-print">
    <div class="card-body">
        <form method="GET" action="dashboard.php" class="row g-3 align-items-end" id="form-filter">
            <input type="hidden" name="page" value="raport">

            <!-- DROPDOWN KELAS -->
            <div class="col-md-5">
                <label class="form-label small fw-bold">Pilih Kelas Binaan / Ampu :</label>
                <select name="id_kelas" id="select-kelas" class="form-select" required onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $query_options = mysqli_query($conn, "SELECT DISTINCT k.id_kelas, k.nama_kelas
                                                          FROM kelas k
                                                          WHERE k.id_wali_guru = '$id_guru_login'");

                    if ($query_options && mysqli_num_rows($query_options) > 0) {
                        while ($row = mysqli_fetch_assoc($query_options)) {
                            $selected = ($id_kelas_terpilih == $row['id_kelas']) ? 'selected' : '';
                            echo "<option value='{$row['id_kelas']}' $selected>{$row['nama_kelas']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Anda tidak terdaftar sebagai wali kelas</option>";
                    }                            
                    ?>
                </select>
            </div>
            
            <!-- SEARCH -->
            <div class="col-md-5">
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
<?php if (!empty($id_kelas_terpilih)): ?>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        
        <form method="POST" action="dashboard.php?page=raport" id="form-raport">
            <input type="hidden" name="id_kelas" value="<?= htmlspecialchars($id_kelas_terpilih) ?>">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">No</th>
                            <th width="20%">Nama Siswa</th>
                            <th width="12%" class="text-center">Kehadiran (S/I/A)</th>
                            <th width="10%" class="text-center">Sikap Spiritual</th>
                            <th width="10%" class="text-center">Sikap Sosial</th>
                            <th width="23%">Catatan Wali Kelas</th>
                            <th width="10%" class="text-center">Keputusan</th>
                            <th width="10%" class="text-center no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $cari_esc    = mysqli_real_escape_string($conn, $cari_siswa);
                        $id_kls_esc  = mysqli_real_escape_string($conn, $id_kelas_terpilih);
                        $filter_nama = !empty($cari_siswa) ? "AND s.nama_siswa LIKE '%$cari_esc%'" : "";

                        $query_siswa = mysqli_query($conn, "SELECT 
                                                                sk.id AS id_siswa_kelas, 
                                                                s.nama_siswa, s.nis,
                                                                rp.catatan, rp.sikap_spiritual, rp.sikap_sosial, rp.keputusan,
                                                                SUM(CASE WHEN a.status_hadir = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                                                                SUM(CASE WHEN a.status_hadir = 'Izin' THEN 1 ELSE 0 END) AS izin,
                                                                SUM(CASE WHEN a.status_hadir = 'Alfa' THEN 1 ELSE 0 END) AS alfa
                                                            FROM siswa_kelas sk
                                                            JOIN siswa s ON sk.id_siswa = s.id_siswa
                                                            LEFT JOIN absensi a ON a.id_siswa_kelas = sk.id
                                                            LEFT JOIN raport rp ON rp.id_siswa_kelas = sk.id
                                                            WHERE sk.id_kelas = '$id_kls_esc' AND sk.status = 'Aktif' $filter_nama
                                                            GROUP BY sk.id
                                                            ORDER BY s.nama_siswa ASC") 
                                                            or die("Error SQL : " . mysqli_error($conn));
                        $no = 1;
                        if (mysqli_num_rows($query_siswa) > 0):
                            while ($siswa = mysqli_fetch_assoc($query_siswa)) {
                                $id_sk = $siswa['id_siswa_kelas'];
                    ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="fw-bold text-dark">
                                    <?= htmlspecialchars($siswa['nama_siswa']) ?><br>
                                    <small class="text-muted fw-normal">NIS: <?= htmlspecialchars($siswa['nis']) ?></small>
                                </td>
                                <td class="text-center small">
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?= $siswa['sakit'] ?> S</span>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle"><?= $siswa['izin'] ?> I</span>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><?= $siswa['alfa'] ?> A</span>
                                </td>
                                <td>
                                    <select name="sikap_spiritual[<?= $id_sk ?>]" class="form-select form-select-sm">
                                        <option value="Sangat Baik" <?= ($siswa['sikap_spiritual'] ?? '') == 'Sangat Baik' ? 'selected' : '' ?>>Sangat Baik</option>
                                        <option value="Baik" <?= ($siswa['sikap_spiritual'] ?? 'Baik') == 'Baik' ? 'selected' : '' ?>>Baik</option>
                                        <option value="Cukup" <?= ($siswa['sikap_spiritual'] ?? '') == 'Cukup' ? 'selected' : '' ?>>Cukup</option>
                                        <option value="Kurang" <?= ($siswa['sikap_spiritual'] ?? '') == 'Kurang' ? 'selected' : '' ?>>Kurang</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="sikap_sosial[<?= $id_sk ?>]" class="form-select form-select-sm">
                                        <option value="Sangat Baik" <?= ($siswa['sikap_sosial'] ?? '') == 'Sangat Baik' ? 'selected' : '' ?>>Sangat Baik</option>
                                        <option value="Baik" <?= ($siswa['sikap_sosial'] ?? 'Baik') == 'Baik' ? 'selected' : '' ?>>Baik</option>
                                        <option value="Cukup" <?= ($siswa['sikap_sosial'] ?? '') == 'Cukup' ? 'selected' : '' ?>>Cukup</option>
                                        <option value="Kurang" <?= ($siswa['sikap_sosial'] ?? '') == 'Kurang' ? 'selected' : '' ?>>Kurang</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="catatan_raport[<?= $id_sk ?>]" 
                                           value="<?= htmlspecialchars($siswa['catatan'] ?? 'Tingkatkan terus prestasi belajarmu.') ?>" 
                                           placeholder="Tulis catatan...">
                                </td>
                                <td>
                                    <select name="keputusan[<?= $id_sk ?>]" class="form-select form-select-sm">
                                        <option value="Naik" <?= ($siswa['keputusan'] ?? 'Naik') == 'Naik' ? 'selected' : '' ?>>Naik</option>
                                        <option value="Tinggal" <?= ($siswa['keputusan'] ?? '') == 'Tinggal' ? 'selected' : '' ?>>Tinggal</option>
                                    </select>
                                </td>
                                <td class="text-center no-print">
                                    <a href="cetak_raport.php?id_siswa_kelas=<?= $id_sk ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Cetak Raport">
                                        <i class="bi bi-printer"></i> Cetak
                                    </a>
                                </td>
                            </tr>
                    <?php 
                            }
                        else:
                    ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Tidak ada data siswa ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-end mt-3 no-print">
                <button type="submit" class="btn btn-success fw-semibold">
                    <i class="bi bi-floppy-fill me-1"></i> Simpan Raport
                </button>        
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- SweetAlert2 Toast Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, "dashboard.php?page=raport<?= !empty($id_kelas_terpilih) ? '&id_kelas=' . $id_kelas_terpilih : '' ?>");
    }

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true
    });
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