<?php
require_once "../koneksi.php";
require_once "../Session.php";

use Session\Session;

if (Session::getRole() != "admin") {
    header("Location: ../index.php");
    exit;
}

$username = Session::getUsername();

$jmlSiswa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM siswa"))['total'];
$jmlGuru  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM guru"))['total'];
$jmlKelas = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM kelas"))['total'];
$jmlMapel = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM mapel"))['total'];
$jmlAkun  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM akun"))['total'];
$jmlTahun = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM tahun_ajaran"))['total'];
// Mengambil data tahun ajaran yang aktif secara otomatis
$query_ta_aktif = mysqli_query($conn, "SELECT ta.*, t.tahun FROM tahun_ajaran ta JOIN tahun t ON ta.id_tahun = t.id_tahun WHERE LOWER(TRIM(ta.status)) = 'aktif' AND ta.deleted = 0 LIMIT 1");
$ta_aktif = mysqli_fetch_assoc($query_ta_aktif);

// Berikan fallback (cadangan) jika seandainya tidak ada yang aktif di database
$tahun_aktif_display = isset($ta_aktif['tahun']) ? $ta_aktif['tahun'] : 'Belum Diatur';
$semester_aktif_display = isset($ta_aktif['semester']) ? ucfirst($ta_aktif['semester']) : '';

// Mengambil parameter halaman aktif (default ke 'dashboard')
$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIAKAD - Dashboard Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        @media (min-width: 992px) {
    .sidebar.collapsed {
        transform: translateX(-260px);
    }

    .main-content.expanded {
        margin-left: 0;
    }
}
        :root {
            --sidebar-bg: #0b192c;
            --sidebar-active: #1e3a8a;
            --main-bg: #f8fafc;
            --card-border-radius: 12px;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-purple: #8b5cf6;
            --accent-orange: #f59e0b;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
        }

        /* SIDEBAR STYLING */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background-color: var(--sidebar-bg);
            color: #cbd5e1;
            z-index: 1040;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-brand-icon {
            background-color: #2563eb;
            color: white;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 1.25rem;
        }

        .sidebar-menu {
            padding: 15px 0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .sidebar-link i {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.03);
            color: #ffffff;
        }

        .sidebar-link.active {
            background-color: #2563eb;
            color: #ffffff;
            border-left-color: #60a5fa;
            border-radius: 0 8px 8px 0;
            margin-right: 15px;
        }

        .sidebar-badge-info {
            background-color: #1d4ed8;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 15px;
            font-size: 0.85rem;
            color: white;
        }

        /* MAIN CONTENT STYLING */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        /* TOPBAR STYLING */
        .topbar {
            background: transparent;
            margin-bottom: 30px;
        }

        .search-bar-container {
            position: relative;
            width: 300px;
        }

        .search-bar-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .search-input {
            padding-left: 40px;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 30px;
            height: 42px;
            font-size: 0.9rem;
        }

        .search-input:focus {
            box-shadow: none;
            border-color: var(--accent-blue);
        }

        .user-profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* CARD REKAP STYLING */
        .rekap-card {
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.04);
            background-color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .rekap-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 35px;
            opacity: 0.15;
            border-radius: 0 0 var(--card-border-radius) var(--card-border-radius);
        }

        .rekap-card.blue::after { background: linear-gradient(to top, var(--accent-blue), transparent); }
        .rekap-card.green::after { background: linear-gradient(to top, var(--accent-green), transparent); }
        .rekap-card.purple::after { background: linear-gradient(to top, var(--accent-purple), transparent); }
        .rekap-card.orange::after { background: linear-gradient(to top, var(--accent-orange), transparent); }

        .icon-box {
            width: 54px;
            height: 54px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* DASHBOARD SECTION LAYOUT */
        .dashboard-card {
            background: #ffffff;
            border: none;
            border-radius: var(--card-border-radius);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 1px 2px rgba(0,0,0,0.04);
            margin-bottom: 24px;
        }

        .dashboard-card-header {
            padding: 20px 24px;
            background: transparent;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 600;
        }

        /* TIMELINE STYLING FOR DATA TERBARU */
        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-icon-box {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
            flex-shrink: 0;
        }

        /* AKSES CEPAT BUTTONS */
        .quick-access-btn {
            background-color: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 20px 10px;
            text-align: center;
            text-decoration: none;
            color: #334155;
            font-weight: 500;
            font-size: 0.85rem;
            display: block;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        }

        .quick-access-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            color: #2563eb;
        }

        .quick-access-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            font-size: 1.25rem;
        }

        /* RESPONSIVE CONFIGURATIONS */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-260px);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <div>
                <h6 class="mb-0 text-white fw-bold" style="letter-spacing: 0.5px;">SIAKAD</h6>
                <small class="text-muted" style="font-size: 0.75rem;">SMP NEGERI 1</small>
            </div>
        </div>

        <div class="sidebar-menu">
            <a href="?page=dashboard" class="sidebar-link <?= $page == 'dashboard' ? 'active' : '' ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>
            <a href="?page=siswa" class="sidebar-link <?= $page == 'siswa' ? 'active' : '' ?>">
                <i class="bi bi-people-fill"></i> Data Siswa
            </a>
            <a href="?page=guru" class="sidebar-link <?= $page == 'guru' ? 'active' : '' ?>">
                <i class="bi bi-person-workspace"></i> Data Guru
            </a>
            <a href="?page=kelas" class="sidebar-link <?= $page == 'kelas' ? 'active' : '' ?>">
                <i class="bi bi-building-fill"></i> Data Kelas
            </a>
            <a href="?page=tahun" class="sidebar-link <?= $page == 'tahun' ? 'active' : '' ?>">
                <i class="bi bi-calendar3"></i> Tahun Ajaran
            </a>
            <a href="?page=mapel" class="sidebar-link <?= $page == 'mapel' ? 'active' : '' ?>">
                <i class="bi bi-book-half"></i> Mata Pelajaran
            </a>
            <a href="?page=jadwal" class="sidebar-link <?= $page == 'jadwal' ? 'active' : '' ?>">
                <i class="bi bi-alarm-fill"></i> Jadwal Pelajaran
            </a>
            <a href="?page=absensi" class="sidebar-link <?= $page == 'absensi' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill"></i> Absensi
            </a>
            <a href="?page=akun" class="sidebar-link <?= $page == 'akun' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Kelola Akun
            </a>
            
            <hr class="mx-3 opacity-10">
            
            <a href="../logout.php" class="sidebar-link text-danger">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>

        <div class="sidebar-badge-info d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-20 p-2 rounded">
                <i class="bi bi-shield-check text-white fs-5"></i>
            </div>
            <div>
                <div style="font-size: 0.75rem; opacity: 0.8;">Tahun Ajaran Aktif</div>
                <div class="fw-bold" style="font-size: 0.85rem;">
                    <?= htmlspecialchars($tahun_aktif_display) ?> <?= htmlspecialchars($semester_aktif_display) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="main-content">

        <div class="topbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button type="button"
        class="btn btn-light border d-inline-flex align-items-center justify-content-center"
        style="width:42px;height:42px;border-radius:10px;"
        onclick="toggleSidebar()"
        aria-label="Toggle Sidebar">
    <i class="bi bi-list fs-4 lh-1 text-dark"></i>
</button>
            </div>

            <div class="d-flex align-items-center gap-4">
                <div class="position-relative style-pointer" style="cursor: pointer;">
                    <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 3px 6px;">
                        3
                    </span>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=256" class="user-profile-img" alt="Admin Face">
                    <div class="d-none d-sm-block">
                        <div class="fw-bold mb-0" style="font-size: 0.9rem; line-height: 1.2;"><?= htmlspecialchars($username) ?></div>
                        <small class="text-muted" style="font-size: 0.75rem;">Super Admin</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if($page == 'dashboard'): ?>

            <div class="row align-items-center mb-4">
                <div class="col-md-8 col-sm-10 mb-3 mb-md-0">
                    <h4 class="fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($username) ?> 👋</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Berikut adalah ringkasan informasi akademik di sekolah.</p>
                </div>
                <div class="col-md-4 col-sm-2 text-md-end">
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-primary d-flex align-items-center gap-2 px-3 py-2 text-start ms-auto" type="button" style="background-color: #2563eb; border: none; border-radius: 10px;">
                            <i class="bi bi-calendar-range-fill"></i>
                            <div>
                                <div style="font-size: 0.65rem; opacity: 0.8; line-height:1;">Tahun Ajaran</div>
                                <span class="fw-bold" style="font-size: 0.85rem;">
                                    <?= htmlspecialchars($tahun_aktif_display) ?> <?= htmlspecialchars($semester_aktif_display) ?>
                                </span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card blue p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #eff6ff; color: #2563eb;">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Total Siswa</span>
                                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($jmlSiswa, 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" style="font-size: 0.8rem;">
                            <span class="text-primary fw-bold"><i class="bi bi-graph-up-arrow"></i> +32</span> <span class="text-muted">dari bulan lalu</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card green p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #ecfdf5; color: #10b981;">
                                    <i class="bi bi-person-fill-add"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Total Guru</span>
                                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($jmlGuru, 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" style="font-size: 0.8rem;">
                            <span class="text-success fw-bold"><i class="bi bi-graph-up-arrow"></i> +5</span> <span class="text-muted">dari bulan lalu</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card purple p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #f5f3ff; color: #8b5cf6;">
                                    <i class="bi bi-building-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Total Kelas</span>
                                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($jmlKelas, 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" style="font-size: 0.8rem;">
                            <span class="text-purple fw-bold" style="color: #8b5cf6;"><i class="bi bi-graph-up-arrow"></i> +2</span> <span class="text-muted">dari bulan lalu</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card orange p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #fffbeb; color: #f59e0b;">
                                    <i class="bi bi-book-half"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Mata Pelajaran</span>
                                    <h3 class="fw-bold mb-0 mt-1"><?= number_format($jmlMapel, 0, ',', '.') ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" style="font-size: 0.8rem;">
                            <span class="text-warning fw-bold"><i class="bi bi-graph-up-arrow"></i> +1</span> <span class="text-muted">dari bulan lalu</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-bar-chart-line-fill text-primary"></i>
                                <span>Grafik Siswa</span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div style="position: relative; height:280px; width:100%">
                                <canvas id="grafikSiswaChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <span>Data Terbaru</span>
                            <a href="?page=siswa" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" style="font-size: 0.75rem;">Lihat Semua</a>
                        </div>
                        <div class="card-body p-4">
                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-primary">
                                    <i class="bi bi-person-fill-add"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Siswa baru ditambahkan</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">10 siswa baru telah didaftarkan</div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem; white-space:nowrap;">2 jam lalu</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-success">
                                    <i class="bi bi-person-vcard-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Guru baru ditambahkan</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">1 guru baru telah ditambahkan</div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem; white-space:nowrap;">5 jam lalu</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-purple" style="background-color: var(--accent-purple) !important;">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Jadwal pelajaran diperbarui</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">Jadwal kelas 9 telah diperbarui</div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem; white-space:nowrap;">1 hari lalu</div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-warning">
                                    <i class="bi bi-check2-square"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Absensi hari ini</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">Total 1.180 siswa hadir hari ini</div>
                                </div>
                                <div class="text-muted" style="font-size:0.7rem; white-space:nowrap;">1 hari lalu</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-card">
                <div class="dashboard-card-header">
                    <span class="fw-bold">Akses Cepat</span>
                </div>
                <div class="card-body p-4">
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-7 g-3">
                        <div class="col">
                            <a href="?page=siswa" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #eff6ff; color: #2563eb;"><i class="bi bi-person-fill-add"></i></div>
                                Tambah Siswa
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=guru" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #ecfdf5; color: #10b981;"><i class="bi bi-person-fill-up"></i></div>
                                Tambah Guru
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=kelas" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #f5f3ff; color: #8b5cf6;"><i class="bi bi-building-fill-add"></i></div>
                                Tambah Kelas
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=mapel" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #fffbeb; color: #f59e0b;"><i class="bi bi-book-half"></i></div>
                                Tambah Mapel
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=jadwal" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #fdf2f8; color: #ec4899;"><i class="bi bi-calendar-plus-fill"></i></div>
                                Buat Jadwal
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=absensi" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #e0f2fe; color: #0284c7;"><i class="bi bi-journal-check"></i></div>
                                Input Absensi
                            </a>
                        </div>
                        <div class="col">
                            <a href="?page=laporan" class="quick-access-btn">
                                <div class="quick-access-icon" style="background-color: #f3e8ff; color: #a855f7;"><i class="bi bi-file-earmark-text-fill"></i></div>
                                Lihat Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- <div class="card dashboard-card">
                <div class="dashboard-card-header">
                    <span class="fw-bold"><i class="bi bi-folder2-open text-primary me-2"></i> Modul Panel</span>
                </div>
                <div class="card-body p-5 text-center">
                    <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                        <i class="bi bi-gear-fill text-secondary fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-2"><?= strtoupper(htmlspecialchars($page)) ?></h4>
                    <p class="text-muted mx-auto" style="max-width: 500px;">Halaman modul ini sedang dalam proses integrasi data dan pengembangan sistem informasi.</p>
                    <div class="alert alert-info d-inline-block border-0 px-4 py-2 rounded-pill mt-2" style="font-size: 0.9rem;">
                        <i class="bi bi-info-circle me-2"></i> Parameter URL aktif: <strong>?page=<?= htmlspecialchars($page) ?></strong>
                    </div>
                </div>
            </div> -->

    <?php
    // Whitelist routing: page => file
    $routes = [
        'siswa'      => __DIR__ . '/siswa.php',
        'guru'       => __DIR__ . '/guru.php',
        'kelas'      => __DIR__ . '/kelas.php',
        'tahun'      => __DIR__ . '/tahun_ajaran.php',
        'mapel'      => __DIR__ . '/mapel.php',
        'jadwal'     => __DIR__ . '/jadwal.php',
        'absensi'    => __DIR__ . '/absensi.php',
        'akun'       => __DIR__ . '/akun.php',
    ];

    if (isset($routes[$page]) && file_exists($routes[$page])) {
        // flag opsional biar file modul tahu dia sedang di-include dari dashboard
        define('IN_DASHBOARD', true);
        include $routes[$page];
    } else {
        // fallback kalau page tidak ada
        ?>
        <div class="card dashboard-card">
            <div class="dashboard-card-header">
                <span class="fw-bold"><i class="bi bi-folder2-open text-primary me-2"></i> Modul Panel</span>
            </div>
            <div class="card-body p-5 text-center">
                <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                    <i class="bi bi-gear-fill text-secondary fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2"><?= strtoupper(htmlspecialchars($page)) ?></h4>
                <p class="text-muted mx-auto" style="max-width: 500px;">Halaman modul ini sedang dalam proses integrasi data dan pengembangan sistem informasi.</p>
                <div class="alert alert-info d-inline-block border-0 px-4 py-2 rounded-pill mt-2" style="font-size: 0.9rem;">
                    <i class="bi bi-info-circle me-2"></i> Parameter URL aktif: <strong>?page=<?= htmlspecialchars($page) ?></strong>
                </div>
            </div>
        </div>
        <?php
    }
    ?>

<?php endif; ?>

        <div class="d-flex justify-content-between align-items-center text-muted px-2 mt-4" style="font-size: 0.8rem;">
            <div>© 2024 SIAKAD SMP NEGERI 1. All rights reserved.</div>
            <div>Version 1.0.0</div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');

        if (!sidebar || !mainContent) return;

        if (window.innerWidth >= 992) {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        } else {
            sidebar.classList.toggle('show');
        }
    }

    window.addEventListener('resize', function () {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.querySelector('.main-content');

        if (!sidebar || !mainContent) return;

        if (window.innerWidth >= 992) {
            sidebar.classList.remove('show');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
        }
    });

        // Jalankan ChartJS hanya jika elemen canvas ada pada halaman (saat page=dashboard)
        const canvasGrup = document.getElementById('grafikSiswaChart');
        if (canvasGrup) {
            const ctx = canvasGrup.getContext('2d');
            
            const blueGradient = ctx.createLinearGradient(0, 0, 0, 300);
            blueGradient.addColorStop(0, 'rgba(37, 99, 235, 0.23)');
            blueGradient.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

            const pinkGradient = ctx.createLinearGradient(0, 0, 0, 300);
            pinkGradient.addColorStop(0, 'rgba(236, 72, 153, 0.23)');
            pinkGradient.addColorStop(1, 'rgba(236, 72, 153, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [
                        {
                            label: 'Laki-laki',
                            data: [270, 280, 410, 430, 490, 560],
                            borderColor: '#2563eb',
                            backgroundColor: blueGradient,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#2563eb',
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Perempuan',
                            data: [150, 180, 230, 265, 290, 310],
                            borderColor: '#ec4899',
                            backgroundColor: pinkGradient,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#ec4899',
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                font: { size: 12, weight: '500' }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 600,
                            ticks: { stepSize: 100, color: '#94a3b8' },
                            grid: { color: '#f1f5f9' }
                        },
                        x: {
                            ticks: { color: '#94a3b8' },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>