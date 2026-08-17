<?php
require_once "../koneksi.php";
require_once "../Session.php";

use Session\Session;

// Proteksi halaman: Pastikan hanya role guru yang bisa mengakses
if (Session::getRole() != "guru") {
    header("Location: ../index.php");
    exit;
}

$username = Session::getUsername();

// 1. Ambil data guru yang sedang login dengan melakukan JOIN ke tabel akun berdasarkan username session
$query_guru = mysqli_query($conn, "SELECT g.id_guru, g.nama_guru FROM guru g JOIN akun a ON g.id_guru = a.id_guru WHERE a.username = '$username' LIMIT 1");
$data_guru = mysqli_fetch_assoc($query_guru);
$id_guru_login = isset($data_guru['id_guru']) ? $data_guru['id_guru'] : '';
$nama_guru_login = isset($data_guru['nama_guru']) ? $data_guru['nama_guru'] : $username;

// 2. Cek apakah guru tersebut merupakan wali kelas di tabel kelas
$is_wali_kelas = false;
$nama_kelas_terwalian = '';
if (!empty($id_guru_login)) {
    $query_wali = mysqli_query($conn, "SELECT nama_kelas FROM kelas WHERE id_wali_guru = '$id_guru_login' LIMIT 1");
    if (mysqli_num_rows($query_wali) > 0) {
        $is_wali_kelas = true;
        $res_wali = mysqli_fetch_assoc($query_wali);
        $nama_kelas_terwalian = $res_wali['nama_kelas'];
    }
}

// 3. Mengambil data tahun ajaran yang aktif secara otomatis (sama seperti dashboard admin)
$query_ta_aktif = mysqli_query($conn, "SELECT ta.*, t.tahun FROM tahun_ajaran ta JOIN tahun t ON ta.id_tahun = t.id_tahun WHERE LOWER(TRIM(ta.status)) = 'aktif' AND ta.deleted = 0 LIMIT 1");
$ta_aktif = mysqli_fetch_assoc($query_ta_aktif);

// Fallback jika tidak ada tahun ajaran aktif di database
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
    <title>SIAKAD - Dashboard Guru</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <link rel="stylesheet" href="partials/style.css">
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
            <a href="?page=absensi" class="sidebar-link <?= $page == 'absensi' ? 'active' : '' ?>">
                <i class="bi bi-calendar-check-fill"></i> Absensi Siswa
            </a>
            <a href="?page=nilai" class="sidebar-link <?= $page == 'nilai' ? 'active' : '' ?>">
                <i class="bi bi-award-fill"></i> Nilai Siswa
            </a>
            
            <?php if ($is_wali_kelas): ?>
            <a href="?page=raport" class="sidebar-link <?= $page == 'raport' ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text-fill"></i> Raport
            </a>
            <?php endif; ?>

            <a href="?page=password" class="sidebar-link <?= $page == 'password' ? 'active' : '' ?>">
                <i class="bi bi-lock-fill"></i> Ganti Password
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
                <button type="button" class="btn btn-light border d-inline-flex align-items-center justify-content-center" style="width:42px;height:42px;border-radius:10px;" onclick="toggleSidebar()" aria-label="Toggle Sidebar">
                    <i class="bi bi-list fs-4 lh-1 text-dark"></i>
                </button>
            </div>

            <div class="d-flex align-items-center gap-4">
                <div class="position-relative" style="cursor: pointer;">
                    <i class="bi bi-bell-fill fs-5 text-secondary"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 3px 6px;">
                        3
                    </span>
                </div>
                
                <div class="d-flex align-items-center gap-2">
                    <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=256" class="user-profile-img" alt="Guru Face">
                    <div class="d-none d-sm-block">
                        <div class="fw-bold mb-0" style="font-size: 0.9rem; line-height: 1.2;"><?= htmlspecialchars($nama_guru_login) ?></div>
                        <small class="text-muted" style="font-size: 0.75rem;">Guru Pengajar</small>
                    </div>
                </div>
            </div>
        </div>

        <?php if($page == 'dashboard'): ?>

            <div class="row align-items-center mb-4">
                <div class="col-md-8 col-sm-10 mb-3 mb-md-0">
                    <h4 class="fw-bold mb-1">Selamat Datang Kembali, <?= htmlspecialchars($nama_guru_login) ?> 👋</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Berikut ringkasan informasi aktivitas akademik Anda hari ini.</p>
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
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Absensi Hari Ini</span>
                                    <h3 class="fw-bold mb-0 mt-1">24 / 28</h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: 85.7%"></div>
                            </div>
                            <span class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Siswa hadir 85.7%</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card green p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #ecfdf5; color: #10b981;">
                                    <i class="bi bi-book-half"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Rata-rata Nilai</span>
                                    <h3 class="fw-bold mb-0 mt-1">87.4</h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 87.4%"></div>
                            </div>
                            <span class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Dari 5 mata pelajaran</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card purple p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #f5f3ff; color: #8b5cf6;">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Jadwal Hari Ini</span>
                                    <h3 class="fw-bold mb-0 mt-1">5</h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: 100%; background-color: var(--accent-purple);"></div>
                            </div>
                            <span class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Total jam mengajar</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6">
                    <div class="card rekap-card orange p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="icon-box" style="background-color: #fffbeb; color: #f59e0b;">
                                    <i class="bi bi-building-fill"></i>
                                </div>
                                <div>
                                    <span class="text-muted fw-semibold" style="font-size: 0.85rem;">Wali Kelas</span>
                                    <h3 class="fw-bold mb-0 mt-1"><?= $is_wali_kelas ? htmlspecialchars($nama_kelas_terwalian) : '-' ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: 100%"></div>
                            </div>
                            <span class="text-muted mt-1 d-block" style="font-size: 0.75rem;">
                                <?= $is_wali_kelas ? 'Anda memegang perwalian aktif.' : 'Bukan Wali Kelas aktif.' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-calendar3 text-primary"></i>
                                <span>Jadwal Mengajar Hari Ini</span>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" style="font-size: 0.75rem;">Lihat Semua</a>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table align-middle table-hover">
                                    <thead class="table-light text-muted small">
                                        <tr>
                                            <th>Jam Ke</th>
                                            <th>Waktu</th>
                                            <th>Mata Pelajaran</th>
                                            <th>Kelas</th>
                                            <th>Ruangan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 0.9rem;">
                                        <tr>
                                            <td>1</td>
                                            <td>07:00 - 07:45</td>
                                            <td class="fw-semibold">Matematika</td>
                                            <td>IX-A</td>
                                            <td>R. 01</td>
                                            <td><span class="badge-selesai">Selesai</span></td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>07:45 - 08:30</td>
                                            <td class="fw-semibold">Matematika</td>
                                            <td>IX-A</td>
                                            <td>R. 01</td>
                                            <td><span class="badge-selesai">Selesai</span></td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>08:45 - 09:30</td>
                                            <td class="fw-semibold">Matematika</td>
                                            <td>VIII-B</td>
                                            <td>R. 02</td>
                                            <td><span class="badge-berlangsung">Berlangsung</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="dashboard-card-header">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-megaphone-fill text-primary"></i>
                                <span>Pengumuman Terbaru</span>
                            </div>
                            <a href="#" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" style="font-size: 0.75rem;">Lihat Semua</a>
                        </div>
                        <div class="card-body p-4">
                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-primary">
                                    <i class="bi bi-info-circle-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Ujian Akhir Semester Ganjil</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">Ujian akan dilaksanakan pada tanggal 10 - 15 Juni 2025.</div>
                                </div>
                            </div>

                            <div class="timeline-item">
                                <div class="timeline-icon-box bg-success">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold" style="font-size:0.85rem;">Pembagian Raport</div>
                                    <div class="text-muted" style="font-size:0.8rem; margin-top:2px;">Raport semester ganjil akan dibagikan pada tanggal 20 Juni 2025.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <?php
            $routes = [
                'absensi'  => __DIR__ . '/absensi.php',
                'nilai'    => __DIR__ . '/nilai.php',
                'password' => __DIR__ . '/password.php',
            ];

            // Tambahkan rute raport secara kondisional jika terdeteksi wali kelas
            if ($is_wali_kelas) {
                $routes['raport'] = __DIR__ . '/raport.php';
            }

            if (isset($routes[$page]) && file_exists($routes[$page])) {
                define('IN_DASHBOARD', true);
                include $routes[$page];
            } else {
                ?>
                <div class="card dashboard-card">
                    <div class="dashboard-card-header">
                        <span class="fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Akses Ditolak</span>
                    </div>
                    <div class="card-body p-5 text-center">
                        <div class="mx-auto bg-light rounded-circle d-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="bi bi-lock-fill text-danger fs-2"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">Halaman Tidak Ditemukan atau Akses Dibatasi</h4>
                        <p class="text-muted mx-auto" style="max-width: 500px;">Anda tidak memiliki otoritas wali kelas untuk membuka modul pengerjaan raport ini.</p>
                        <a href="?page=dashboard" class="btn btn-primary btn-sm rounded-pill px-4 mt-3" style="background-color: #2563eb; border:none;">Kembali ke Dashboard</a>
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
    </script>
</body>

</html>