<?php
require_once "../koneksi.php";
require_once "../Session.php";

use Session\Session;

if (Session::getRole() != "admin") {
    header("Location: ../index.php");
    exit;
}

$username = Session::getUsername();

/** @var mysqli $conn */

// Filter State Parameter
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'Siswa';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$tgl_awal = isset($_GET['tgl_awal']) ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';

$where_clause = "";
$params = [];
$types = "";

// 1. QUERY LOGIKA (SISWA vs GURU)
if ($mode === 'Siswa') {
    $sql = "SELECT 
                s.nis, 
                s.nama_siswa AS nama, 
                k.nama_kelas AS info_tambahan,
                SUM(CASE WHEN a.status_hadir = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status_hadir = 'Izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status_hadir = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status_hadir = 'Alfa' THEN 1 ELSE 0 END) AS alpha
            FROM absensi a
            JOIN siswa_kelas sk ON a.id_siswa_kelas = sk.id
            JOIN siswa s ON sk.id_siswa = s.id_siswa
            JOIN kelas k ON sk.id_kelas = k.id_kelas
            WHERE 1=1 ";

    if (!empty($keyword)) {
        $sql .= "AND (s.nis LIKE ? OR s.nama_siswa LIKE ?) ";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= "ss";
    }

    if (!empty($tgl_awal) && !empty($tgl_akhir)) {
        $sql .= "AND DATE(a.tanggal) BETWEEN ? AND ? ";
        $params[] = $tgl_awal;
        $params[] = $tgl_akhir;
        $types .= "ss";
    }

    $sql .= "GROUP BY sk.id_siswa ORDER BY s.nama_siswa ASC";

} else {
    // Mode Guru
    $sql = "SELECT 
                g.nip AS nis, 
                g.nama_guru AS nama, 
                '-' AS info_tambahan,
                SUM(CASE WHEN a.status IN ('Hadir','Terlambat') THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN a.status = 'Alfa' THEN 1 ELSE 0 END) AS alpha
            FROM absensi_guru a
            JOIN guru g ON a.id_guru = g.id_guru
            WHERE a.deleted = 0 ";

    if (!empty($keyword)) {
        $sql .= "AND (g.nip LIKE ? OR g.nama_guru LIKE ?) ";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
        $types .= "ss";
    }

    if (!empty($tgl_awal) && !empty($tgl_akhir)) {
        $sql .= "AND DATE(a.tanggal) BETWEEN ? AND ? ";
        $params[] = $tgl_awal;
        $params[] = $tgl_akhir;
        $types .= "ss";
    }

    $sql .= "GROUP BY g.id_guru ORDER BY g.nama_guru ASC";
}

$stmt = mysqli_prepare($conn, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_data = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 8px; border: 1px solid #e0e0e0; }
        .table-custom th { background-color: #e6e6e6; text-align: center; font-weight: 600; }
        .table-custom td { text-align: center; vertical-align: middle; }
        .view-link { color: #0066ff; text-decoration: underline; cursor: pointer; }
    </style>
</head>
<body>

<div class="container-fluid p-4">
    <!-- PANEL FILTER & TITLE -->
    <div class="card p-3 mb-3 bg-white">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-3">
                <h5 class="fw-bold mb-0">Rekap Data Absensi</h5>
            </div>
            <div class="col-md-2">
                <select name="mode" class="form-select" onchange="this.form.submit()">
                    <option value="Siswa" <?= $mode == 'Siswa' ? 'selected' : '' ?>>Siswa</option>
                    <option value="Guru" <?= $mode == 'Guru' ? 'selected' : '' ?>>Guru</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="tgl_awal" class="form-content form-control" value="<?= htmlspecialchars($tgl_awal) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-2">
                <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <input type="text" name="keyword" class="form-control" placeholder="Cari <?= $mode == 'Siswa' ? 'NIS/Nama Siswa' : 'NIP/Nama Guru' ?>" value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </form>
    </div>

    <!-- PANEL TABLE DATA -->
    <div class="card p-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="fw-semibold">Data Riwayat Absensi</span>
            <div>
                <span class="badge bg-info text-dark me-2">Total : <?= $total_data ?></span>
                <button type="button" class="btn btn-sm btn-outline-danger me-1" onclick="window.print()">Cetak PDF</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped table-bordered table-custom">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th><?= $mode == 'Siswa' ? 'NIS' : 'NIP' ?></th>
                        <th><?= $mode == 'Siswa' ? 'Nama Siswa' : 'Nama Guru' ?></th>
                        <?php if ($mode == 'Siswa'): ?>
                            <th>Kelas</th>
                        <?php endif; ?>
                        <th>Hadir</th>
                        <th>Izin</th>
                        <th>Sakit</th>
                        <th>Alpha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($total_data > 0): 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['nis'] ?? '-') ?></td>
                            <td class="text-start"><?= htmlspecialchars($row['nama']) ?></td>
                            <?php if ($mode == 'Siswa'): ?>
                                <td><?= htmlspecialchars($row['info_tambahan']) ?></td>
                            <?php endif; ?>
                            <td><?= $row['hadir'] ?></td>
                            <td>
                                <?= $row['izin'] ?> 
                                <?php if ($row['izin'] > 0): ?>
                                    <span class="view-link ms-1" onclick="bukaSurat('<?= $mode ?>', '<?= htmlspecialchars($row['nis']) ?>', 'Izin')">View</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $row['sakit'] ?> 
                                <?php if ($row['sakit'] > 0): ?>
                                    <span class="view-link ms-1" onclick="bukaSurat('<?= $mode ?>', '<?= htmlspecialchars($row['nis']) ?>', 'Sakit')">View</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['alpha'] ?></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="<?= $mode == 'Siswa' ? 8 : 7 ?>" class="text-center text-muted">Tidak ada data absensi ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function bukaSurat(mode, id, status) {
    // Fungsi ini menggantikan dialog_daftar_surat pada Swing Java
    alert("Membuka dokumen " + status + " untuk " + mode + " (" + id + ")");
}
</script>

</body>
</html>