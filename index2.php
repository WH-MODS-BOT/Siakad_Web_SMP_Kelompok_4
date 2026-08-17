<?php
// Mengambil halaman aktif dari URL, default ke 'dashboard'
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD - SMP NEGERI 0</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            color: #333;
        }
        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #0b1a30;
            color: #fff;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        .sidebar-brand {
            padding: 24px;
            font-size: 1.25rem;
            font-weight: 700;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .sidebar-menu {
            list-style: none;
            padding: 15px 0;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 24px;
            color: #a3b1cc;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        .sidebar-menu li a i {
            margin-right: 15px;
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }
        .sidebar-menu li a:hover, 
        .sidebar-menu li.active a {
            color: #fff;
            background-color: #1e3a8a;
            border-left: 4px solid #3b82f6;
        }
        /* Main Content Styling */
        .main-content {
            margin-left: 260px;
            padding: 24px;
            min-height: 100vh;
        }
        /* Top Navbar */
        .top-navbar {
            background: #fff;
            padding: 15px 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.03);
            margin-bottom: 24px;
        }
        /* Custom UI Cards */
        .stat-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            height: 100%;
        }
        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
        }
        .bg-custom-blue { background-color: #eff6ff; color: #3b82f6; }
        .bg-custom-green { background-color: #f0fdf4; color: #22c55e; }
        .bg-custom-purple { background-color: #faf5ff; color: #a855f7; }
        .bg-custom-orange { background-color: #fff7ed; color: #f97316; }
        
        .table-card {
            background: #fff;
            border-radius: 16px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.02);
            padding: 24px;
        }
        .badge-selesai { background-color: #e6f4ea; color: #137333; }
        .badge-berlangsung { background-color: #e8f0fe; color: #1a73e8; }
        .badge-datang { background-color: #f1f3f4; color: #3c4043; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand d-flex align-items-center">
            <i class="fa-solid fa-graduation-cap me-2 text-primary"></i>
            <div>
                <div class="fw-bold lh-1" style="font-size: 1.1rem;">SIAKAD</div>
                <small class="text-muted" style="font-size: 0.75rem;">SMP NEGERI 0</small>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li class="<?= $page == 'dashboard' ? 'active' : '' ?>">
                <a href="?page=dashboard"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            </li>
            <li class="<?= $page == 'absensi' ? 'active' : '' ?>">
                <a href="?page=absensi"><i class="fa-solid fa-user-check"></i> Absensi Siswa</a>
            </li>
            <li class="<?= $page == 'nilai' ? 'active' : '' ?>">
                <a href="?page=nilai"><i class="fa-solid fa-award"></i> Nilai Siswa</a>
            </li>
            <li class="<?= $page == 'raport' ? 'active' : '' ?>">
                <a href="?page=raport"><i class="fa-solid fa-file-invoice"></i> Raport</a>
            </li>
            <li class="<?= $page == 'password' ? 'active' : '' ?>">
                <a href="?page=password"><i class="fa-solid fa-lock"></i> Ganti Password</a>
            </li>
            <li>
                <a href="?page=logout" class="text-danger"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
            </li>
        </ul>
        <div class="p-3 m-3 rounded bg-primary bg-opacity-10 text-center" style="font-size: 0.85rem;">
            <span class="text-muted d-block">Tahun Ajaran</span>
            <strong class="text-white">2024/2025</strong>
            <span class="badge bg-primary d-block mt-1">Semester Ganjil</span>
        </div>
    </div>

    <div class="main-content">
        
        <div class="top-navbar d-flex justify-content-between align-items-center">
            <div class="search-bar w-50 position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" class="form-control ps-5 border-0 bg-light" placeholder="Cari siswa, kelas, mata pelajaran...">
            </div>
            <div class="user-profile d-flex align-items-center">
                <div class="position-relative me-4">
                    <i class="fa-regular fa-bell fs-5 text-muted"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">3</span>
                </div>
                <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=100&q=80" alt="Guru" class="rounded-circle me-2" width="40" height="40">
                <div>
                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">Budi Santoso, S.Pd</h6>
                    <small class="text-muted" style="font-size: 0.8rem;">Guru</small>
                </div>
            </div>
        </div>

        <?php if ($page == 'dashboard'): ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1">Selamat datang kembali, Budi Santoso, S.Pd 👋</h4>
                    <p class="text-muted small mb-0">Berikut ringkasan informasi aktivitas akademik Anda hari ini.</p>
                </div>
                <div class="bg-primary text-white p-3 rounded-3 d-flex align-items-center">
                    <i class="fa-regular fa-calendar-days me-3 fs-4"></i>
                    <div>
                        <div class="small fw-light">Selasa, 27 Mei 2025</div>
                        <div class="fw-bold">08:45 WIB</div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card d-flex align-items-start">
                        <div class="icon-box bg-custom-blue me-3"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <span class="text-muted small d-block mb-1">Absensi Hari Ini</span>
                            <h3 class="fw-bold mb-1">24 / 28</h3>
                            <div class="progress" style="height: 4px; width: 120px;">
                                <div class="progress-bar bg-primary" style="width: 85.7%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Siswa hadir 85.7%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card d-flex align-items-start">
                        <div class="icon-box bg-custom-green me-3"><i class="fa-solid fa-book-open"></i></div>
                        <div>
                            <span class="text-muted small d-block mb-1">Rata-rata Nilai</span>
                            <h3 class="fw-bold mb-1">87.4</h3>
                            <div class="progress" style="height: 4px; width: 120px;">
                                <div class="progress-bar bg-success" style="width: 87.4%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Dari 5 mata pelajaran</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card d-flex align-items-start">
                        <div class="icon-box bg-custom-purple me-3"><i class="fa-regular fa-clock"></i></div>
                        <div>
                            <span class="text-muted small d-block mb-1">Jadwal Hari Ini</span>
                            <h3 class="fw-bold mb-1">5</h3>
                            <div class="progress" style="height: 4px; width: 120px;">
                                <div class="progress-bar bg-purple" style="width: 100%; background-color:#a855f7;"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Total Jam Mengajar</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card d-flex align-items-start">
                        <div class="icon-box bg-custom-orange me-3"><i class="fa-solid fa-house-user"></i></div>
                        <div>
                            <span class="text-muted small d-block mb-1">Wali Kelas</span>
                            <h3 class="fw-bold mb-1">IX-A</h3>
                            <div class="progress" style="height: 4px; width: 120px;">
                                <div class="progress-bar bg-warning" style="width: 100%"></div>
                            </div>
                            <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;">24 Siswa</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-7">
                    <div class="table-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Jadwal Mengajar Hari Ini</h5>
                            <a href="#" class="text-decoration-none small fw-semibold">Lihat Semua</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
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
                                        <td>Matematika</td>
                                        <td>IX-A</td>
                                        <td>R. 01</td>
                                        <td><span class="badge badge-selesai px-2 py-1">Selesai</span></td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>07:45 - 08:30</td>
                                        <td>Matematika</td>
                                        <td>IX-A</td>
                                        <td>R. 01</td>
                                        <td><span class="badge badge-selesai px-2 py-1">Selesai</span></td>
                                    </tr>
                                    <tr>
                                        <td>3</td>
                                        <td>08:45 - 09:30</td>
                                        <td>Matematika</td>
                                        <td>VIII-B</td>
                                        <td>R. 02</td>
                                        <td><span class="badge badge-berlangsung px-2 py-1">Berlangsung</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="table-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0">Pengumuman Terbaru</h5>
                            <a href="#" class="text-decoration-none small fw-semibold">Lihat Semua</a>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex align-items-start p-2 rounded hover-bg-light">
                                <div class="icon-box bg-custom-blue me-3"><i class="fa-solid fa-bullhorn"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">Ujian Akhir Semester Ganjil</h6>
                                    <p class="text-muted small mb-0">Ujian akan dilaksanakan pada tanggal 10 - 15 Juni 2025.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-start p-2 rounded hover-bg-light">
                                <div class="icon-box bg-custom-green me-3"><i class="fa-solid fa-graduation-cap"></i></div>
                                <div>
                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.9rem;">Pembagian Raport</h6>
                                    <p class="text-muted small mb-0">Raport semester ganjil akan dibagikan pada tanggal 20 Juni 2025.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($page == 'absensi'): ?>
            <div class="table-card">
                <h4 class="fw-bold mb-3">Manajemen Absensi Siswa</h4>
                <p class="text-muted">Fitur pengisian absensi harian kelas IX-A.</p>
                <hr>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>NIS</th>
                                <th>Nama Siswa</th>
                                <th>Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>102931</td>
                                <td>Ahmad Fauzi</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <input type="radio" class="btn-check" name="abs[1]" id="h1" checked>
                                        <label class="btn btn-outline-success" for="h1">Hadir</label>
                                        <input type="radio" class="btn-check" name="abs[1]" id="i1">
                                        <label class="btn btn-outline-warning" for="i1">Izin</label>
                                        <input type="radio" class="btn-check" name="abs[1]" id="a1">
                                        <label class="btn btn-outline-danger" for="a1">Alpha</label>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'nilai'): ?>
            <div class="table-card">
                <h4 class="fw-bold mb-3">Input Nilai Siswa</h4>
                <p class="text-muted">Kelola nilai tugas, UTS, dan UAS siswa.</p>
                <hr>
                <form class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Pilih Mata Pelajaran</label>
                        <select class="form-select"><option>Matematika - IX-A</option></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Nilai</label>
                        <select class="form-select"><option>Ujian Tengah Semester (UTS)</option></select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </form>
            </div>

        <?php elseif ($page == 'raport'): ?>
            <div class="table-card">
                <h4 class="fw-bold mb-3">Cetak & Preview Raport</h4>
                <p class="text-muted">Halaman finalisasi nilai capaian belajar siswa.</p>
                <hr>
                <div class="alert alert-info">Pilih kelas terlebih dahulu untuk memproses lembar capaian raport siswa.</div>
            </div>

        <?php elseif ($page == 'password'): ?>
            <div class="table-card" style="max-width: 500px;">
                <h4 class="fw-bold mb-3">Ganti Password</h4>
                <p class="text-muted">Perbarui kata sandi akun Anda secara berkala demi keamanan.</p>
                <hr>
                <form>
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>

        <?php elseif ($page == 'logout'): ?>
            <div class="table-card text-center py-5">
                <i class="fa-solid fa-circle-check text-success display-4 mb-3"></i>
                <h4>Anda telah berhasil logout</h4>
                <p class="text-muted">Terima kasih telah menggunakan sistem informasi akademik.</p>
                <a href="?page=dashboard" class="btn btn-primary mt-2">Login Kembali</a>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>