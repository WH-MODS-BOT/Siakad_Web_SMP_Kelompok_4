<?php
// Set timezone WIB
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

$id_siswa_kelas = isset($_GET['id_siswa_kelas']) ? mysqli_real_escape_string($conn, $_GET['id_siswa_kelas']) : '';

if (empty($id_siswa_kelas)) {
    echo "ID Siswa Kelas tidak ditemukan.";
    exit;
}

// 1. QUERY IDENTITAS SISWA & WALI KELAS & TAHUN AJARAN
$q_identitas = mysqli_query($conn, "SELECT 
                                    s.nama_siswa, s.nis, s.nisn,
                                    k.nama_kelas, k.id_kelas,
                                    g.nama_guru AS wali_kelas, g.nip AS nip_wali,
                                    t.tahun, ta.semester,
                                    rp.catatan, rp.sikap_spiritual, rp.sikap_sosial, rp.keputusan
                                FROM siswa_kelas sk
                                JOIN siswa s ON sk.id_siswa = s.id_siswa
                                JOIN kelas k ON sk.id_kelas = k.id_kelas
                                LEFT JOIN guru g ON k.id_wali_guru = g.id_guru
                                LEFT JOIN raport rp ON rp.id_siswa_kelas = sk.id
                                LEFT JOIN tahun_ajaran ta ON ta.status = 'Aktif' AND ta.deleted = 0
                                LEFT JOIN tahun t ON ta.id_tahun = t.id_tahun
                                WHERE sk.id = '$id_siswa_kelas' LIMIT 1") 
                                or die("Error SQL Identitas: " . mysqli_error($conn));

$d_identitas = mysqli_fetch_assoc($q_identitas);

if (!$d_identitas) {
    echo "Data siswa tidak ditemukan.";
    exit;
}

// 2. QUERY REKAP ABSENSI
$q_absensi = mysqli_query($conn, "SELECT 
                                    SUM(CASE WHEN status_hadir = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                                    SUM(CASE WHEN status_hadir = 'Izin' THEN 1 ELSE 0 END) AS izin,
                                    SUM(CASE WHEN status_hadir = 'Alfa' THEN 1 ELSE 0 END) AS alfa
                                  FROM absensi 
                                  WHERE id_siswa_kelas = '$id_siswa_kelas'")
                                  or die("Error SQL Absensi: " . mysqli_error($conn));
$d_absensi = mysqli_fetch_assoc($q_absensi);

// 3. QUERY MATA PELAJARAN DAN NILAI DARI TABEL `nilai` (Mengacu pada referensi nilai.php)
$id_kelas = $d_identitas['id_kelas'];
$semester_aktif = $d_identitas['semester'];

$q_nilai = mysqli_query($conn, "SELECT 
                                    m.nama_mapel, m.kkm,
                                    n.tugas, n.uts, n.uas, n.nilai_akhir, n.keterangan
                                FROM japel jp
                                JOIN mapel m ON jp.id_mapel = m.id_mapel
                                LEFT JOIN nilai n ON n.id_japel = jp.id_japel 
                                                  AND n.id_siswa_kelas = '$id_siswa_kelas'
                                WHERE jp.id_kelas = '$id_kelas' AND jp.deleted = 0
                                ORDER BY m.nama_mapel ASC") 
                                or die("Error SQL Nilai: " . mysqli_error($conn));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport - <?= htmlspecialchars($d_identitas['nama_siswa']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            background-color: #fff;
            color: #000;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
            padding: 5px 8px;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        @media print {
            .no-print { display: none !important; }
            @page {
                size: A4;
                margin: 15mm;
            }
        }
    </style>
</head>
<body class="p-4">

    <!-- TOMBOL ACTION (NO PRINT) -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center">
        <button onclick="window.history.back()" class="btn btn-secondary">
            &larr; Kembali
        </button>
        <button onclick="window.print()" class="btn btn-primary">
            Cetak / Download PDF
        </button>
    </div>

    <!-- HEADER RAPORT -->
    <div class="header-title">
        <h4 class="fw-bold mb-1">LAPORAN HASIL BELAJAR SISWA (RAPORT)</h4>
        <h6 class="fw-bold">TAHUN AJARAN <?= htmlspecialchars($d_identitas['tahun'] ?? '-') ?></h6>
    </div>

    <!-- IDENTITAS SISWA -->
    <table class="table table-borderless mb-3" style="width: 100%; font-size: 11pt;">
        <tr>
            <td width="15%"><strong>Nama Siswa</strong></td>
            <td width="2%">:</td>
            <td width="40%"><?= htmlspecialchars($d_identitas['nama_siswa']) ?></td>
            <td width="15%"><strong>Kelas</strong></td>
            <td width="2%">:</td>
            <td width="26%"><?= htmlspecialchars($d_identitas['nama_kelas']) ?></td>
        </tr>
        <tr>
            <td><strong>NIS / NISN</strong></td>
            <td>:</td>
            <td><?= htmlspecialchars($d_identitas['nis'] ?? '-') ?> / <?= htmlspecialchars($d_identitas['nisn'] ?? '-') ?></td>
            <td><strong>Semester</strong></td>
            <td>:</td>
            <td><?= htmlspecialchars($d_identitas['semester'] ?? '-') ?></td>
        </tr>
    </table>

    <!-- A. SIKAP -->
    <strong class="d-block mb-1">A. SIKAP</strong>
    <table class="table table-bordered mb-4">
        <thead>
            <tr class="text-center bg-light">
                <th width="50%">Sikap Spiritual</th>
                <th width="50%">Sikap Sosial</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-center">
                <td><?= htmlspecialchars($d_identitas['sikap_spiritual'] ?? 'Baik') ?></td>
                <td><?= htmlspecialchars($d_identitas['sikap_sosial'] ?? 'Baik') ?></td>
            </tr>
        </tbody>
    </table>

    <!-- B. NILAI AKADEMIK (DIAMBIL DARI TABEL NILAI) -->
    <strong class="d-block mb-1">B. NILAI AKADEMIK</strong>
    <table class="table table-bordered mb-4 align-middle">
        <thead>
            <tr class="text-center bg-light">
                <th width="5%">No</th>
                <th width="35%">Mata Pelajaran</th>
                <th width="10%">KKM</th>
                <th width="10%">Tugas</th>
                <th width="10%">UTS</th>
                <th width="10%">UAS</th>
                <th width="10%">Nilai Akhir</th>
                <th width="10%">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($q_nilai) > 0):
                while ($row = mysqli_fetch_assoc($q_nilai)): 
            ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama_mapel']) ?></td>
                    <td class="text-center"><?= $row['kkm'] ?? 75 ?></td>
                    <td class="text-center"><?= $row['tugas'] !== null ? number_format($row['tugas'], 2) : '-' ?></td>
                    <td class="text-center"><?= $row['uts'] !== null ? number_format($row['uts'], 2) : '-' ?></td>
                    <td class="text-center"><?= $row['uas'] !== null ? number_format($row['uas'], 2) : '-' ?></td>
                    <td class="text-center fw-bold">
                        <?= $row['nilai_akhir'] !== null ? number_format($row['nilai_akhir'], 2) : '-' ?>
                    </td>
                    <td class="text-center">
                        <?= htmlspecialchars($row['keterangan'] ?? '-') ?>
                    </td>
                </tr>
            <?php 
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="8" class="text-center">Belum ada data mata pelajaran / nilai.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- C. KETIDAKHADIRAN & CATATAN WALI KELAS -->
    <div class="row">
        <div class="col-6">
            <strong class="d-block mb-1">C. KETIDAKHADIRAN</strong>
            <table class="table table-bordered">
                <tr>
                    <td width="60%">Sakit</td>
                    <td width="40%" class="text-center"><?= $d_absensi['sakit'] ?? 0 ?> hari</td>
                </tr>
                <tr>
                    <td>Izin</td>
                    <td class="text-center"><?= $d_absensi['izin'] ?? 0 ?> hari</td>
                </tr>
                <tr>
                    <td>Tanpa Keterangan (Alfa)</td>
                    <td class="text-center"><?= $d_absensi['alfa'] ?? 0 ?> hari</td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <strong class="d-block mb-1">D. KEPUTUSAN</strong>
            <table class="table table-bordered">
                <tr>
                    <td class="py-3">
                        <strong>Keputusan:</strong><br>
                        Berdasarkan hasil yang dicapai, siswa dinyatakan: 
                        <strong class="text-uppercase"><?= htmlspecialchars($d_identitas['keputusan'] ?? 'Naik') ?></strong>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- E. CATATAN WALI KELAS -->
    <strong class="d-block mb-1">E. CATATAN WALI KELAS</strong>
    <table class="table table-bordered mb-5">
        <tr>
            <td class="py-2">
                <em>"<?= htmlspecialchars($d_identitas['catatan'] ?? 'Tingkatkan terus prestasi belajarmu.') ?>"</em>
            </td>
        </tr>
    </table>

    <!-- TANDA TANGAN -->
    <table class="table table-borderless text-center" style="width: 100%; margin-top: 40px;">
        <tr>
            <td width="33%">
                Mengetahui,<br>Orang Tua / Wali
                <br><br><br><br>
                ( ..................................... )
            </td>
            <td width="34%"></td>
            <td width="33%">
                Jakarta, <?= date('d F Y') ?><br>Wali Kelas
                <br><br><br><br>
                <strong><u><?= htmlspecialchars($d_identitas['wali_kelas'] ?? '.....................') ?></u></strong><br>
                NIP. <?= htmlspecialchars($d_identitas['nip_wali'] ?? '-') ?>
            </td>
        </tr>
    </table>

</body>
</html>