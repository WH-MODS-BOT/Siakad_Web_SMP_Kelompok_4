-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2026 at 06:29 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `siakad_web`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id_absensi` int(11) NOT NULL,
  `id_japel` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `status_hadir` enum('Hadir','Izin','Sakit','Alfa') DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `id_siswa_kelas` int(11) NOT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `file_surat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id_absensi`, `id_japel`, `tanggal`, `status_hadir`, `keterangan`, `id_siswa_kelas`, `created_by`, `created_at`, `file_surat`) VALUES
(33, 39, '2026-07-17', 'Hadir', '', 40, 'GR002', '2026-07-17 14:31:22', NULL),
(34, 39, '2026-07-17', 'Sakit', 'Demam, ada surat', 39, 'GR002', '2026-07-17 15:15:54', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `absensi_guru`
--

CREATE TABLE `absensi_guru` (
  `id_absen` int(11) NOT NULL,
  `id_guru` varchar(10) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `status` enum('Hadir','Terlambat','Izin','Sakit','Alfa') DEFAULT 'Hadir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `file_surat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aktivitas_mengajar`
--

CREATE TABLE `aktivitas_mengajar` (
  `id_absen_guru` int(11) NOT NULL,
  `id_guru` varchar(10) NOT NULL,
  `id_japel` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_absen` time NOT NULL,
  `status` enum('Hadir','Terlambat','Izin','Sakit') DEFAULT 'Hadir',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0,
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

CREATE TABLE `akun` (
  `id_akun` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `id_guru` varchar(10) DEFAULT NULL,
  `failed_attempts` int(11) DEFAULT 0,
  `is_locked` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `must_change_password` tinyint(1) DEFAULT 0,
  `lock_time` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`id_akun`, `username`, `password`, `role`, `id_guru`, `failed_attempts`, `is_locked`, `last_login`, `must_change_password`, `lock_time`, `created_at`) VALUES
(6, 'admin', '$2a$10$eReKQ0sw8NwRWGUfH5.dv.SJgTEPllcqpSm3Nog92eT8p2wNCqXxO', 'admin', NULL, 0, 0, '2026-07-18 12:09:06', 0, NULL, '2026-05-02 12:36:28'),
(16, 'wahid1', '$2y$10$ZCZF/bCdw1Q.t0ffrApPBuzCZtR65SS8MezErjz7GnYZQY25cbWy6', 'guru', 'GR001', 0, 0, '2026-07-03 22:28:47', 0, NULL, '2026-07-03 22:27:55'),
(17, 'rafid1', '$2y$10$eHc2bykEgIb3vcptIV/nPOMsvqkmy0i9LmAWh2WVm7AQflkI5.f6y', 'guru', 'GR002', 0, 0, '2026-07-18 14:55:56', 0, NULL, '2026-07-03 22:28:17');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id_log` int(11) NOT NULL,
  `tabel` varchar(50) DEFAULT NULL,
  `aksi` varchar(20) DEFAULT NULL,
  `id_data` int(11) DEFAULT NULL,
  `data_lama` text DEFAULT NULL,
  `data_baru` text DEFAULT NULL,
  `user` varchar(100) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `id_guru` varchar(10) NOT NULL,
  `nip` varchar(20) DEFAULT NULL,
  `nama_guru` varchar(100) NOT NULL,
  `jk` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `notelp` varchar(15) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`id_guru`, `nip`, `nama_guru`, `jk`, `alamat`, `notelp`, `status`, `jurusan`, `created_at`, `updated_at`, `deleted`) VALUES
('GR001', '5636364637373', 'wahid s.kom', 'Laki-laki', 'depok', '08143265423', 'PNS', 'TI', '2026-06-29 16:17:21', '2026-07-01 08:09:54', 0),
('GR002', '5463563636', 'rafid s.kom', 'Laki-laki', 'depok', '0834237554364', 'PNS', 'TI', '2026-06-28 12:42:01', '2026-06-29 14:40:47', 0),
('GR003', '19870102345678', 'yogi S.Pd', 'Laki-laki', 'Jakarta', '081234567890', 'PNS', 'Akuntansi', '2026-06-29 14:38:03', '2026-06-29 14:39:24', 0),
('GR004', '19880203456789', 'ridwan M.T', 'Laki-laki', 'Bogor', '081345678901', 'PNS', 'TI', '2026-06-29 14:38:03', '2026-06-29 14:39:40', 0),
('GR005', '19890304567890', 'Amelia S.Pd', 'Perempuan', 'Bekasi', '081456789012', 'Honorer', 'Bahasa Inggris', '2026-06-29 14:38:03', '2026-06-29 16:02:28', 0),
('GR006', '19900405678901', 'bayu M.Kom', 'Laki-laki', 'Depok', '081567890123', 'PNS', 'TI', '2026-06-29 14:38:03', '2026-06-29 14:40:10', 0),
('GR007', '19910506789012', 'diffa S.E', 'Laki-laki', 'Tangerang', '081678901234', 'Honorer', 'Akuntansi', '2026-06-29 14:38:03', '2026-06-29 14:40:25', 0),
('GR008', '19920607890123', 'Eko Prasetyo S.T', 'Laki-laki', 'Jakarta', '081789012345', 'PNS', 'Multimedia', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR009', '19930708901234', 'Sari Wijaya S.Pd', 'Perempuan', 'Depok', '081890123456', 'Honorer', 'Bahasa Indonesia', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR010', '19940809012345', 'Rian Hidayat S.Kom', 'Laki-laki', 'Bogor', '081901234567', 'PNS', 'TI', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR011', '19950910123456', 'Mega Utami M.Pd', 'Perempuan', 'Bekasi', '082112345678', 'PNS', 'Matematika', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR012', '19961011234567', 'Hendra Wijaya S.E', 'Laki-laki', 'Tangerang', '082223456789', 'Honorer', 'Pemasaran', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR013', '19971112345678', 'Fitriani S.Pd', 'Perempuan', 'Jakarta', '082334567890', 'Honorer', 'Bahasa Inggris', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR014', '19981213456789', 'Andi Perkasa M.T', 'Laki-laki', 'Depok', '082445678901', 'PNS', 'Otomotif', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR015', '19990114567890', 'Novianti S.Kom', 'Perempuan', 'Bogor', '082556789012', 'Honorer', 'TI', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR016', '20000215678901', 'Fajar Nugroho S.Pd', 'Laki-laki', 'Bekasi', '082667890123', 'PNS', 'Olahraga', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR017', '20010316789012', 'Dian Islamiati S.Si', 'Perempuan', 'Tangerang', '082778901234', 'PNS', 'Kimia', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR018', '20020417890123', 'Aditya Putra S.T', 'Laki-laki', 'Jakarta', '082889012345', 'Honorer', 'Multimedia', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR019', '20030518901234', 'Sri Wahyuni S.E', 'Perempuan', 'Depok', '082990123456', 'PNS', 'Akuntansi', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR020', '20040619012345', 'Denny Caknan M.Pd', 'Laki-laki', 'Bogor', '083112345678', 'Honorer', 'Seni Budaya', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR021', '20050720123456', 'Yulianti S.Pd', 'Perempuan', 'Bekasi', '083223456789', 'PNS', 'Sejarah', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR022', '20060821234567', 'Bambang Tri M.Kom', 'Laki-laki', 'Tangerang', '083334567890', 'PNS', 'TI', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR023', '20070922345678', 'Lia Natalia S.Pd', 'Perempuan', 'Jakarta', '083445678901', 'Honorer', 'Bahasa Indonesia', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR024', '20081023456789', 'Taufik Hidayat S.T', 'Laki-laki', 'Depok', '083556789012', 'PNS', 'Otomotif', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR025', '20091124567890', 'Citra Kirana S.E', 'Perempuan', 'Bogor', '083667890123', 'Honorer', 'Pemasaran', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0),
('GR026', '20101225678901', 'Rizky Febian M.Pd', 'Laki-laki', 'Bekasi', '083778901234', 'PNS', 'Fisika', '2026-06-29 14:38:03', '2026-06-29 14:38:03', 0);

-- --------------------------------------------------------

--
-- Table structure for table `japel`
--

CREATE TABLE `japel` (
  `id_japel` int(11) NOT NULL,
  `id_guru` varchar(10) DEFAULT NULL,
  `id_mapel` int(11) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `hari` varchar(20) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `id_tahun` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `japel`
--

INSERT INTO `japel` (`id_japel`, `id_guru`, `id_mapel`, `id_kelas`, `hari`, `jam_mulai`, `jam_selesai`, `id_tahun`, `semester`, `deleted`) VALUES
(37, 'GR018', 27, 28, 'Senin', '22:02:00', '22:03:00', 4, 'Ganjil', 0),
(39, 'GR002', 26, 29, 'Senin', '07:00:00', '08:00:00', 4, 'Ganjil', 0),
(40, 'GR002', 25, 31, 'Sabtu', '08:00:00', '09:00:00', 4, 'Ganjil', 0);

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `kode_kelas` varchar(20) NOT NULL,
  `nama_kelas` varchar(50) NOT NULL,
  `kapasitas` int(11) NOT NULL,
  `id_wali_guru` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `kode_kelas`, `nama_kelas`, `kapasitas`, `id_wali_guru`) VALUES
(28, '7-1', '7.1', 2, 'GR018'),
(29, '7-2', '7.2', 2, 'GR005'),
(30, '7-3', '7.3', 2, 'GR014'),
(31, '7-4', '7.4', 2, 'GR022'),
(32, '7-5', '7.5', 2, 'GR007'),
(33, '7-6', '7.6', 2, 'GR025'),
(34, '7-7', '7.7', 2, 'GR020'),
(35, '7-8', '7.8', 2, 'GR017'),
(36, '8-1', '8.1', 2, 'GR006'),
(37, '8-2', '8.2', 2, 'GR008'),
(38, '8-3', '8.3', 2, 'GR016'),
(39, '8-4', '8.4', 2, 'GR013'),
(40, '8-5', '8.5', 2, 'GR012'),
(41, '8-6', '8.6', 2, 'GR023'),
(42, '8-7', '8.7', 2, 'GR011'),
(43, '8-8', '8.8', 2, 'GR015'),
(44, '9-1', '9.1', 2, 'GR003'),
(45, '9-2', '9.2', 2, 'GR010'),
(46, '9-3', '9.3', 2, 'GR002'),
(47, '9-4', '9.4', 2, 'GR026'),
(48, '9-5', '9.5', 2, 'GR019'),
(49, '9-6', '9.6', 2, 'GR009'),
(50, '9-7', '9.7', 2, 'GR024'),
(51, '9-8', '9.8', 2, 'GR004');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `username`, `role`, `aktivitas`, `waktu`) VALUES
(462, 'admin', 'admin', 'Login ke sistem', '2026-06-27 05:40:06'),
(463, 'admin', 'Admin', 'Logout dari sistem', '2026-06-27 06:18:58'),
(464, 'admin', 'admin', 'Login ke sistem', '2026-06-27 06:19:07'),
(465, 'admin', 'Admin', 'Logout dari sistem', '2026-06-27 06:22:36'),
(466, 'admin', 'admin', 'Login ke sistem', '2026-06-27 06:22:50'),
(467, 'admin', 'Admin', 'Logout dari sistem', '2026-06-27 06:27:01'),
(468, 'admin', 'admin', 'Login ke sistem', '2026-06-27 06:27:09'),
(469, 'admin', 'admin', 'Login ke sistem', '2026-06-27 08:28:37'),
(470, 'admin', 'admin', 'Login ke sistem', '2026-06-28 06:18:08'),
(471, 'admin', 'Admin', 'Logout dari sistem', '2026-06-28 06:20:51'),
(472, 'admin', 'admin', 'Login ke sistem', '2026-06-28 06:21:48'),
(473, 'admin', 'Admin', 'Logout dari sistem', '2026-06-28 06:34:18'),
(474, 'admin', 'admin', 'Login ke sistem', '2026-06-28 06:34:28'),
(475, 'admin', 'admin', 'Login ke sistem', '2026-06-29 10:04:15'),
(476, 'admin', 'Admin', 'Logout dari sistem', '2026-06-29 13:20:45'),
(477, 'admin', 'admin', 'Login ke sistem', '2026-06-29 13:21:04'),
(478, 'admin', 'admin', 'Login ke sistem', '2026-06-30 09:19:42'),
(479, 'admin', 'Admin', 'Logout dari sistem', '2026-06-30 09:35:13'),
(480, 'admin', 'admin', 'Login ke sistem', '2026-06-30 09:35:22'),
(481, 'admin', 'Admin', 'Logout dari sistem', '2026-06-30 15:26:50'),
(482, 'rafid1', 'admin', 'Login ke sistem', '2026-06-30 15:27:03'),
(483, 'rafid1', 'Admin', 'Logout dari sistem', '2026-06-30 15:27:13'),
(484, 'admin', 'admin', 'Login ke sistem', '2026-06-30 15:27:23'),
(485, 'admin', 'admin', 'Login ke sistem', '2026-07-01 07:20:11'),
(486, 'admin', 'Admin', 'Logout dari sistem', '2026-07-01 08:45:57'),
(487, 'admin', 'admin', 'Login ke sistem', '2026-07-01 08:46:52'),
(488, 'admin', 'admin', 'Login ke sistem', '2026-07-02 07:51:58'),
(489, 'admin', 'admin', 'Login ke sistem', '2026-07-02 12:00:17'),
(490, 'admin', 'admin', 'Login ke sistem', '2026-07-03 02:35:52'),
(491, 'admin', 'Admin', 'Logout dari sistem', '2026-07-03 15:10:26'),
(492, 'rafid1', 'guru', 'Login ke sistem', '2026-07-03 15:10:35'),
(493, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:10:48'),
(494, 'rafid1', 'guru', 'Login ke sistem', '2026-07-03 15:10:58'),
(495, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:19:53'),
(496, 'admin', 'admin', 'Login ke sistem', '2026-07-03 15:20:01'),
(497, 'admin', 'Admin', 'Logout dari sistem', '2026-07-03 15:20:31'),
(498, 'wahid1', 'guru', 'Login ke sistem', '2026-07-03 15:20:46'),
(499, 'wahid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:24:23'),
(500, 'wahid1', 'guru', 'Login ke sistem', '2026-07-03 15:24:34'),
(501, 'wahid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:26:53'),
(502, 'admin', 'admin', 'Login ke sistem', '2026-07-03 15:27:01'),
(503, 'admin', 'Admin', 'Logout dari sistem', '2026-07-03 15:28:20'),
(504, 'rafid1', 'guru', 'Login ke sistem', '2026-07-03 15:28:30'),
(505, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:28:37'),
(506, 'wahid1', 'guru', 'Login ke sistem', '2026-07-03 15:28:47'),
(507, 'wahid1', 'Guru', 'Logout dari sistem', '2026-07-03 15:34:35'),
(508, 'admin', 'admin', 'Login ke sistem', '2026-07-09 15:34:57'),
(509, 'admin', 'Admin', 'Logout dari sistem', '2026-07-09 15:35:32'),
(510, 'rafid1', 'guru', 'Login ke sistem', '2026-07-09 15:35:50'),
(511, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-10 18:25:54'),
(512, 'rafid1', 'guru', 'Login ke sistem', '2026-07-10 18:37:35'),
(513, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-12 04:24:32'),
(514, 'admin', 'admin', 'Login ke sistem', '2026-07-12 04:24:36'),
(515, 'admin', 'Admin', 'Logout dari sistem', '2026-07-12 04:26:12'),
(516, 'rafid1', 'guru', 'Login ke sistem', '2026-07-12 04:26:25'),
(517, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-17 14:17:00'),
(518, 'admin', 'admin', 'Login ke sistem', '2026-07-17 14:17:06'),
(519, 'admin', 'Admin', 'Logout dari sistem', '2026-07-17 14:30:58'),
(520, 'rafid1', 'guru', 'Login ke sistem', '2026-07-17 14:31:11'),
(521, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-17 15:05:32'),
(522, 'admin', 'admin', 'Login ke sistem', '2026-07-17 15:05:41'),
(523, 'admin', 'Admin', 'Logout dari sistem', '2026-07-17 15:06:02'),
(524, 'rafid1', 'guru', 'Login ke sistem', '2026-07-17 15:06:18'),
(525, 'rafid1', 'Guru', 'Logout dari sistem', '2026-07-17 17:01:04'),
(526, 'admin', 'admin', 'Login ke sistem', '2026-07-17 17:01:09'),
(527, 'admin', 'Admin', 'Logout dari sistem', '2026-07-17 17:40:25'),
(528, 'rafid1', 'guru', 'Login ke sistem', '2026-07-17 17:40:31'),
(529, 'admin', 'admin', 'Login ke sistem', '2026-07-18 05:08:16'),
(530, 'admin', 'Admin', 'Logout dari sistem', '2026-07-18 05:08:54'),
(531, 'admin', 'admin', 'Login ke sistem', '2026-07-18 05:09:06'),
(532, 'admin', 'Admin', 'Logout dari sistem', '2026-07-18 07:55:47'),
(533, 'rafid1', 'guru', 'Login ke sistem', '2026-07-18 07:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `log_login`
--

CREATE TABLE `log_login` (
  `id_log` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_login`
--

INSERT INTO `log_login` (`id_log`, `username`, `status`, `waktu`) VALUES
(766, 'admin', 'SUKSES', '2026-06-27 05:40:06'),
(767, 'admin', 'SUKSES', '2026-06-27 06:19:07'),
(768, 'admin', 'SUKSES', '2026-06-27 06:22:50'),
(769, 'admin', 'SUKSES', '2026-06-27 06:27:09'),
(770, 'admin', 'SUKSES', '2026-06-27 08:28:37'),
(771, 'admin', 'SUKSES', '2026-06-28 06:18:08'),
(772, 'admin', 'SUKSES', '2026-06-28 06:21:48'),
(773, 'admin', 'SUKSES', '2026-06-28 06:34:28'),
(774, 'admin', 'SUKSES', '2026-06-29 10:04:15'),
(775, 'admin', 'SUKSES', '2026-06-29 13:21:04'),
(776, 'admin', 'SUKSES', '2026-06-30 09:19:42'),
(777, 'admin', 'SUKSES', '2026-06-30 09:35:22'),
(778, 'rafid1', 'SUKSES', '2026-06-30 15:27:03'),
(779, 'admin', 'SUKSES', '2026-06-30 15:27:23'),
(780, 'admin', 'SUKSES', '2026-07-01 07:20:11'),
(781, 'admin', 'SUKSES', '2026-07-01 08:46:52'),
(782, 'admin', 'SUKSES', '2026-07-02 07:51:58'),
(783, 'admin', 'SUKSES', '2026-07-02 12:00:17'),
(784, 'admin', 'SUKSES', '2026-07-03 02:35:52'),
(785, 'rafid1', 'SUKSES', '2026-07-03 15:10:35'),
(786, 'rafid1', 'SUKSES', '2026-07-03 15:10:58'),
(787, 'admin', 'SUKSES', '2026-07-03 15:20:01'),
(788, 'wahid1', 'SUKSES', '2026-07-03 15:20:46'),
(789, 'wahid1', 'SUKSES', '2026-07-03 15:24:34'),
(790, 'admin', 'SUKSES', '2026-07-03 15:27:01'),
(791, 'rafid1', 'SUKSES', '2026-07-03 15:28:30'),
(792, 'wahid1', 'SUKSES', '2026-07-03 15:28:47'),
(793, 'admin', 'GAGAL', '2026-07-09 15:34:43'),
(794, 'admin', 'SUKSES', '2026-07-09 15:34:57'),
(795, 'rafid1', 'SUKSES', '2026-07-09 15:35:50'),
(796, 'rafid1', 'SUKSES', '2026-07-10 18:37:35'),
(797, 'admin', 'SUKSES', '2026-07-12 04:24:36'),
(798, 'rafid1', 'SUKSES', '2026-07-12 04:26:25'),
(799, 'admin', 'SUKSES', '2026-07-17 14:17:06'),
(800, 'rafid1', 'SUKSES', '2026-07-17 14:31:11'),
(801, 'admin', 'SUKSES', '2026-07-17 15:05:41'),
(802, 'rafid1', 'SUKSES', '2026-07-17 15:06:18'),
(803, 'admin', 'SUKSES', '2026-07-17 17:01:09'),
(804, 'rafid1', 'SUKSES', '2026-07-17 17:40:31'),
(805, 'admin', 'SUKSES', '2026-07-18 05:08:16'),
(806, 'admin', 'GAGAL', '2026-07-18 05:09:02'),
(807, 'admin', 'SUKSES', '2026-07-18 05:09:06'),
(808, 'rafid1', 'SUKSES', '2026-07-18 07:55:56');

-- --------------------------------------------------------

--
-- Table structure for table `mapel`
--

CREATE TABLE `mapel` (
  `id_mapel` int(11) NOT NULL,
  `kode_mapel` varchar(20) NOT NULL,
  `nama_mapel` varchar(100) NOT NULL,
  `kkm` int(11) DEFAULT 75,
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mapel`
--

INSERT INTO `mapel` (`id_mapel`, `kode_mapel`, `nama_mapel`, `kkm`, `deleted`) VALUES
(23, 'MP001', 'IPA', 75, 0),
(24, 'MP002', 'IPS', 75, 0),
(25, 'MP003', 'Bahasa Inggris', 75, 0),
(26, 'MP004', 'Matematika', 75, 0),
(27, 'MP005', 'Bahasa Indonesia', 75, 0),
(28, 'MP006', 'Seni Musik', 75, 0),
(29, 'MP007', 'PJOK', 75, 0),
(30, 'MP008', 'PABP', 75, 0),
(31, 'MP009', 'Informatika', 75, 0),
(32, 'MP010', 'Bahasa Sunda', 75, 0),
(33, 'MP011', 'Pendidikan Pancasila', 75, 0);

-- --------------------------------------------------------

--
-- Table structure for table `nilai`
--

CREATE TABLE `nilai` (
  `id_nilai` int(11) NOT NULL,
  `id_japel` int(11) NOT NULL,
  `id_siswa_kelas` int(11) NOT NULL,
  `id_tahun` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `tugas` decimal(5,2) DEFAULT 0.00,
  `uts` decimal(5,2) DEFAULT 0.00,
  `uas` decimal(5,2) DEFAULT 0.00,
  `nilai_akhir` decimal(5,2) DEFAULT 0.00,
  `predikat` varchar(2) DEFAULT NULL,
  `keterangan` varchar(20) DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai`
--

INSERT INTO `nilai` (`id_nilai`, `id_japel`, `id_siswa_kelas`, `id_tahun`, `semester`, `tugas`, `uts`, `uas`, `nilai_akhir`, `predikat`, `keterangan`, `created_by`, `created_at`, `updated_at`) VALUES
(52, 40, 61, 4, 'Ganjil', 76.00, 82.00, 89.00, 84.30, NULL, 'Tuntas', 'GR002', '2026-07-18 14:47:36', '2026-07-18 14:47:36'),
(54, 40, 62, 4, 'Ganjil', 60.00, 75.00, 75.00, 72.00, NULL, 'Belum Tuntas', 'GR002', '2026-07-18 14:48:38', '2026-07-18 14:48:38'),
(57, 40, 63, 4, 'Ganjil', 80.00, 87.00, 90.00, 87.10, NULL, 'Tuntas', 'GR002', '2026-07-18 14:49:17', '2026-07-18 14:49:17'),
(64, 40, 64, 4, 'Ganjil', 90.00, 90.00, 90.00, 90.00, NULL, 'Tuntas', 'GR002', '2026-07-18 14:49:58', '2026-07-18 14:49:58'),
(85, 40, 65, 4, 'Ganjil', 80.00, 90.00, 100.00, 93.00, NULL, 'Tuntas', 'GR002', '2026-07-18 15:11:21', '2026-07-18 15:11:21'),
(91, 40, 66, 4, 'Ganjil', 100.00, 70.00, 70.00, 76.00, NULL, 'Tuntas', 'GR002', '2026-07-18 15:13:33', '2026-07-18 15:13:33'),
(98, 40, 67, 4, 'Ganjil', 80.00, 80.00, 80.00, 80.00, NULL, 'Tuntas', 'GR002', '2026-07-18 15:41:01', '2026-07-18 15:41:01'),
(106, 40, 68, 4, 'Ganjil', 70.00, 70.00, 75.00, 72.50, NULL, 'Belum Tuntas', 'GR002', '2026-07-18 15:44:39', '2026-07-18 15:44:39'),
(115, 40, 69, 4, 'Ganjil', 80.00, 79.00, 70.00, 74.70, NULL, 'Belum Tuntas', 'GR002', '2026-07-18 15:46:45', '2026-07-18 15:46:45'),
(125, 40, 70, 4, 'Ganjil', 70.00, 98.00, 71.00, 78.90, NULL, 'Tuntas', 'GR002', '2026-07-18 15:56:44', '2026-07-18 15:56:44'),
(136, 40, 71, 4, 'Ganjil', 70.00, 87.00, 71.00, 75.60, NULL, 'Tuntas', 'GR002', '2026-07-18 15:57:04', '2026-07-18 15:57:04'),
(148, 40, 72, 4, 'Ganjil', 70.00, 70.00, 90.00, 80.00, NULL, 'Tuntas', 'GR002', '2026-07-18 15:58:35', '2026-07-18 15:58:35'),
(161, 40, 73, 4, 'Ganjil', 80.00, 80.00, 100.00, 90.00, NULL, 'Tuntas', 'GR002', '2026-07-18 15:59:38', '2026-07-18 15:59:38'),
(175, 40, 74, 4, 'Ganjil', 100.00, 90.00, 90.00, 92.00, NULL, 'Tuntas', 'GR002', '2026-07-18 16:00:11', '2026-07-18 16:00:11'),
(190, 40, 75, 4, 'Ganjil', 90.00, 80.00, 80.00, 82.00, NULL, 'Tuntas', 'GR002', '2026-07-18 16:06:33', '2026-07-18 16:06:33');

-- --------------------------------------------------------

--
-- Table structure for table `raport`
--

CREATE TABLE `raport` (
  `id_raport` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `total_nilai` decimal(6,2) DEFAULT 0.00,
  `ranking_kelas` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `id_tahun` int(11) DEFAULT NULL,
  `id_siswa_kelas` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status_final` enum('Draft','Final') DEFAULT 'Draft',
  `keputusan` enum('Naik','Tidak Naik','Lulus') DEFAULT 'Naik',
  `sikap_spiritual` enum('Sangat Baik','Baik','Cukup','Kurang') DEFAULT NULL,
  `sikap_sosial` enum('Sangat Baik','Baik','Cukup','Kurang') DEFAULT NULL,
  `sakit` int(11) DEFAULT 0,
  `izin` int(11) DEFAULT 0,
  `alfa` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pindah_kelas`
--

CREATE TABLE `riwayat_pindah_kelas` (
  `id_riwayat` int(11) NOT NULL,
  `id_siswa` varchar(10) NOT NULL,
  `kelas_lama` int(11) NOT NULL,
  `kelas_baru` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `riwayat_pindah_kelas`
--

INSERT INTO `riwayat_pindah_kelas` (`id_riwayat`, `id_siswa`, `kelas_lama`, `kelas_baru`, `keterangan`, `tanggal`) VALUES
(8, 'SW001', 31, 28, 'Perubahan kelas saat pembaharuan data profil siswa.', '2026-07-02 19:01:02'),
(9, 'SW001', 28, 29, 'Perubahan kelas saat pembaharuan data profil siswa.', '2026-07-17 22:06:00');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id_siswa` varchar(10) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nama_siswa` varchar(100) NOT NULL,
  `jk` enum('Laki-laki','Perempuan') DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `nik` varchar(30) DEFAULT NULL,
  `no_telpon` varchar(20) DEFAULT NULL,
  `status` enum('Aktif','Nonaktif','Keluar','Dropout','Lulus') DEFAULT 'Aktif',
  `tanggal_keluar` date DEFAULT NULL,
  `keterangan` varchar(100) DEFAULT NULL,
  `id_tahun` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id_siswa`, `nis`, `nama_siswa`, `jk`, `alamat`, `tempat_lahir`, `tanggal_lahir`, `nisn`, `agama`, `nik`, `no_telpon`, `status`, `tanggal_keluar`, `keterangan`, `id_tahun`) VALUES
('SW001', '78687755', 'wahid hidayatullah', 'Laki-laki', 'depok limo', 'depok', '2026-07-01', '876587585587669', 'Islam', '986876585765', '08214537485395', 'Aktif', NULL, NULL, 4),
('SW002', '78687759', 'Ridwan Nugraha', 'Laki-laki', 'Jakarta', 'Jakarta', '2009-03-28', '877412314', 'Islam', '3275090123942', '0854713214', 'Aktif', NULL, NULL, 4),
('SW003', '2425003', 'Adi Nugroho', '', 'Jl. Merdeka No. 1', 'Jakarta', '2009-01-15', '0090000003', 'Islam', '3275010000000003', '081200000003', 'Aktif', NULL, NULL, 2),
('SW004', '2425004', 'Bambang Tri', '', 'Jl. Mawar No. 2', 'Bandung', '2009-02-20', '0090000004', 'Islam', '3275010000000004', '081200000004', 'Aktif', NULL, NULL, 2),
('SW005', '2425005', 'Citra Kirana', '', 'Jl. Melati No. 3', 'Surabaya', '2009-03-12', '0090000005', 'Islam', '3275010000000005', '081200000005', 'Aktif', NULL, NULL, 2),
('SW006', '2425006', 'Dedi Cahyadi', '', 'Jl. Kenanga No. 4', 'Semarang', '2009-04-05', '0090000006', 'Islam', '3275010000000006', '081200000006', 'Aktif', NULL, NULL, 2),
('SW007', '2425007', 'Eka Putri', '', 'Jl. Dahlia No. 5', 'Yogyakarta', '2009-05-18', '0090000007', 'Islam', '3275010000000007', '081200000007', 'Aktif', NULL, NULL, 2),
('SW008', '2425008', 'Fajar Ramadhan', '', 'Jl. Anggrek No. 6', 'Medan', '2009-06-25', '0090000008', 'Islam', '3275010000000008', '081200000008', 'Aktif', NULL, NULL, 2),
('SW009', '2425009', 'Gita Gutawa', '', 'Jl. Flamboyan No. 7', 'Makassar', '2009-07-09', '0090000009', 'Islam', '3275010000000009', '081200000009', 'Aktif', NULL, NULL, 2),
('SW010', '2425010', 'Heri Setiawan', '', 'Jl. Cempaka No. 8', 'Palembang', '2009-08-14', '0090000010', 'Islam', '3275010000000010', '081200000010', 'Aktif', NULL, NULL, 2),
('SW011', '2425011', 'Indah Permata', '', 'Jl. Teratai No. 9', 'Denpasar', '2009-09-30', '0090000011', 'Islam', '3275010000000011', '081200000011', 'Aktif', NULL, NULL, 2),
('SW012', '2425012', 'Joko Susilo', '', 'Jl. Kamboja No. 10', 'Malang', '2009-10-22', '0090000012', 'Islam', '3275010000000012', '081200000012', 'Aktif', NULL, NULL, 2),
('SW013', '2425013', 'Kartika Sari', '', 'Jl. Sakura No. 11', 'Solo', '2009-11-05', '0090000013', 'Islam', '3275010000000013', '081200000013', 'Aktif', NULL, NULL, 2),
('SW014', '2425014', 'Lilik Hendra', '', 'Jl. Tulip No. 12', 'Bogor', '2009-12-19', '0090000014', 'Islam', '3275010000000014', '081200000014', 'Aktif', NULL, NULL, 2),
('SW015', '2425015', 'Maya Ahmad', '', 'Jl. Aster No. 13', 'Bekasi', '2009-01-28', '0090000015', 'Islam', '3275010000000015', '081200000015', 'Aktif', NULL, NULL, 2),
('SW016', '2425016', 'Novan Saputra', '', 'Jl. Soka No. 14', 'Tangerang', '2009-03-04', '0090000016', 'Islam', '3275010000000016', '081200000016', 'Aktif', NULL, NULL, 2),
('SW017', '2425017', 'Olivia Wijaya', '', 'Jl. Lili No. 15', 'Depok', '2009-05-11', '0090000017', 'Kristen', '3275010000000017', '081200000017', 'Aktif', NULL, NULL, 2),
('SW018', '2425018', 'Panji Pradana', '', 'Jl. Bougenville No. 16', 'Cirebon', '2009-07-17', '0090000018', 'Islam', '3275010000000018', '081200000018', 'Aktif', NULL, NULL, 2),
('SW019', '2425019', 'Qori Antika', '', 'Jl. Lavender No. 17', 'Tasikmalaya', '2009-08-23', '0090000019', 'Islam', '3275010000000019', '081200000019', 'Aktif', NULL, NULL, 2),
('SW020', '2425020', 'Rian Hidayat', '', 'Jl. Ashoka No. 18', 'Garut', '2009-09-09', '0090000020', 'Islam', '3275010000000020', '081200000020', 'Aktif', NULL, NULL, 2),
('SW021', '2425021', 'Siti Aminah', '', 'Jl. Jasmine No. 19', 'Sukabumi', '2009-10-14', '0090000021', 'Islam', '3275010000000021', '081200000021', 'Aktif', NULL, NULL, 2),
('SW022', '2425022', 'Taufik Hidayat', '', 'Jl. Lotus No. 20', 'Karawang', '2009-12-01', '0090000022', 'Islam', '3275010000000022', '081200000022', 'Aktif', NULL, NULL, 2);

-- --------------------------------------------------------

--
-- Table structure for table `siswa_kelas`
--

CREATE TABLE `siswa_kelas` (
  `id` int(11) NOT NULL,
  `id_siswa` varchar(10) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `id_tahun` int(11) DEFAULT NULL,
  `semester` enum('Ganjil','Genap') DEFAULT NULL,
  `status` enum('Aktif','Naik','Lulus','Keluar') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa_kelas`
--

INSERT INTO `siswa_kelas` (`id`, `id_siswa`, `id_kelas`, `id_tahun`, `semester`, `status`, `created_at`) VALUES
(39, 'SW001', 29, 4, 'Ganjil', 'Aktif', '2026-06-30 17:05:19'),
(40, 'SW002', 29, 4, 'Ganjil', 'Aktif', '2026-07-17 14:29:14'),
(61, 'SW003', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(62, 'SW004', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(63, 'SW005', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(64, 'SW006', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(65, 'SW007', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(66, 'SW008', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(67, 'SW009', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(68, 'SW010', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(69, 'SW011', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(70, 'SW012', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(71, 'SW013', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(72, 'SW014', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(73, 'SW015', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(74, 'SW016', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(75, 'SW017', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(76, 'SW018', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(77, 'SW019', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(78, 'SW020', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(79, 'SW021', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21'),
(80, 'SW022', 31, 4, 'Ganjil', 'Aktif', '2026-07-18 07:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `tahun`
--

CREATE TABLE `tahun` (
  `id_tahun` int(11) NOT NULL,
  `tahun` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tahun`
--

INSERT INTO `tahun` (`id_tahun`, `tahun`) VALUES
(2, '2021/2022'),
(3, '2022/2023'),
(4, '2023/2024');

-- --------------------------------------------------------

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id_tahun_ajaran` int(11) NOT NULL,
  `id_tahun` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Nonaktif',
  `deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tahun_ajaran`
--

INSERT INTO `tahun_ajaran` (`id_tahun_ajaran`, `id_tahun`, `semester`, `status`, `deleted`) VALUES
(7, 2, 'Ganjil', 'Nonaktif', 0),
(8, 2, 'Genap', 'Nonaktif', 0),
(9, 3, 'Ganjil', 'Nonaktif', 0),
(10, 3, 'Genap', 'Nonaktif', 0),
(11, 4, 'Ganjil', 'Aktif', 0),
(12, 4, 'Genap', 'Nonaktif', 0);

-- --------------------------------------------------------

--
-- Table structure for table `unlock_absensi`
--

CREATE TABLE `unlock_absensi` (
  `id_unlock` int(11) NOT NULL,
  `id_japel` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD UNIQUE KEY `unik_absensi` (`id_siswa_kelas`,`tanggal`,`id_japel`),
  ADD KEY `fk_absensi_japel` (`id_japel`),
  ADD KEY `fk_absensi_created_01` (`created_by`),
  ADD KEY `idx_absensi_siswa_kelas` (`id_siswa_kelas`);

--
-- Indexes for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD PRIMARY KEY (`id_absen`),
  ADD UNIQUE KEY `unik_absen_harian` (`id_guru`,`tanggal`),
  ADD UNIQUE KEY `unik_guru_tanggal` (`id_guru`,`tanggal`),
  ADD KEY `idx_tanggal_guru` (`tanggal`);

--
-- Indexes for table `aktivitas_mengajar`
--
ALTER TABLE `aktivitas_mengajar`
  ADD PRIMARY KEY (`id_absen_guru`),
  ADD UNIQUE KEY `unik_absen_guru` (`id_guru`,`id_japel`,`tanggal`),
  ADD KEY `idx_guru` (`id_guru`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `fk_absen_japel` (`id_japel`);

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`),
  ADD UNIQUE KEY `unique_username` (`username`),
  ADD UNIQUE KEY `unique_id_guru` (`id_guru`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indexes for table `japel`
--
ALTER TABLE `japel`
  ADD PRIMARY KEY (`id_japel`),
  ADD UNIQUE KEY `unique_jadwal_pro` (`id_kelas`,`id_tahun`,`semester`,`hari`,`jam_mulai`),
  ADD UNIQUE KEY `unique_guru` (`id_guru`,`id_tahun`,`semester`,`hari`,`jam_mulai`),
  ADD KEY `fk_japel_mapel` (`id_mapel`),
  ADD KEY `idx_japel_kelas` (`id_kelas`),
  ADD KEY `idx_japel_tahun_semester` (`id_tahun`,`semester`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `kode_kelas` (`kode_kelas`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `fk_wali_guru` (`id_wali_guru`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `log_login`
--
ALTER TABLE `log_login`
  ADD PRIMARY KEY (`id_log`);

--
-- Indexes for table `mapel`
--
ALTER TABLE `mapel`
  ADD PRIMARY KEY (`id_mapel`),
  ADD UNIQUE KEY `kode_mapel` (`kode_mapel`),
  ADD UNIQUE KEY `nama_mapel` (`nama_mapel`),
  ADD KEY `idx_mapel_deleted_nama` (`deleted`,`nama_mapel`);

--
-- Indexes for table `nilai`
--
ALTER TABLE `nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD UNIQUE KEY `unik_nilai` (`id_japel`,`id_siswa_kelas`,`semester`,`id_tahun`),
  ADD KEY `fk_nilai_siswa` (`id_siswa_kelas`),
  ADD KEY `fk_nilai_tahun` (`id_tahun`),
  ADD KEY `idx_nilai_japel` (`id_japel`),
  ADD KEY `fk_nilai_guru` (`created_by`);

--
-- Indexes for table `raport`
--
ALTER TABLE `raport`
  ADD PRIMARY KEY (`id_raport`),
  ADD UNIQUE KEY `unik_raport` (`id_siswa_kelas`,`id_tahun`,`semester`),
  ADD KEY `idx_raport_siswa` (`id_siswa_kelas`),
  ADD KEY `fk_raport_tahun` (`id_tahun`);

--
-- Indexes for table `riwayat_pindah_kelas`
--
ALTER TABLE `riwayat_pindah_kelas`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `kelas_lama` (`kelas_lama`),
  ADD KEY `kelas_baru` (`kelas_baru`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `fk_siswa_tahun` (`id_tahun`);

--
-- Indexes for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unik_siswa_kelas` (`id_siswa`,`id_kelas`,`id_tahun`),
  ADD UNIQUE KEY `unik_siswa_tahun` (`id_siswa`,`id_tahun`),
  ADD KEY `idx_siswa` (`id_siswa`),
  ADD KEY `idx_kelas` (`id_kelas`),
  ADD KEY `idx_tahun` (`id_tahun`);

--
-- Indexes for table `tahun`
--
ALTER TABLE `tahun`
  ADD PRIMARY KEY (`id_tahun`),
  ADD UNIQUE KEY `tahun` (`tahun`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id_tahun_ajaran`),
  ADD UNIQUE KEY `unique_tahun_semester` (`id_tahun`,`semester`),
  ADD KEY `idx_status_tahun` (`status`);

--
-- Indexes for table `unlock_absensi`
--
ALTER TABLE `unlock_absensi`
  ADD PRIMARY KEY (`id_unlock`),
  ADD UNIQUE KEY `unik_unlock` (`id_japel`,`tanggal`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id_absensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  MODIFY `id_absen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `aktivitas_mengajar`
--
ALTER TABLE `aktivitas_mengajar`
  MODIFY `id_absen_guru` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `japel`
--
ALTER TABLE `japel`
  MODIFY `id_japel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=534;

--
-- AUTO_INCREMENT for table `log_login`
--
ALTER TABLE `log_login`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=809;

--
-- AUTO_INCREMENT for table `mapel`
--
ALTER TABLE `mapel`
  MODIFY `id_mapel` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `nilai`
--
ALTER TABLE `nilai`
  MODIFY `id_nilai` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `raport`
--
ALTER TABLE `raport`
  MODIFY `id_raport` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `riwayat_pindah_kelas`
--
ALTER TABLE `riwayat_pindah_kelas`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `tahun`
--
ALTER TABLE `tahun`
  MODIFY `id_tahun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id_tahun_ajaran` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `unlock_absensi`
--
ALTER TABLE `unlock_absensi`
  MODIFY `id_unlock` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `fk_absensi_created_01` FOREIGN KEY (`created_by`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absensi_japel` FOREIGN KEY (`id_japel`) REFERENCES `japel` (`id_japel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sk_abs_20260509_2210` FOREIGN KEY (`id_siswa_kelas`) REFERENCES `siswa_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `absensi_guru`
--
ALTER TABLE `absensi_guru`
  ADD CONSTRAINT `fk_absensi_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `aktivitas_mengajar`
--
ALTER TABLE `aktivitas_mengajar`
  ADD CONSTRAINT `fk_absen_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absen_japel` FOREIGN KEY (`id_japel`) REFERENCES `japel` (`id_japel`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `akun`
--
ALTER TABLE `akun`
  ADD CONSTRAINT `fk_akun_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `japel`
--
ALTER TABLE `japel`
  ADD CONSTRAINT `fk_japel_guru` FOREIGN KEY (`id_guru`) REFERENCES `guru` (`id_guru`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_japel_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_japel_mapel` FOREIGN KEY (`id_mapel`) REFERENCES `mapel` (`id_mapel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_japel_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id_tahun`);

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_wali_guru` FOREIGN KEY (`id_wali_guru`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `nilai`
--
ALTER TABLE `nilai`
  ADD CONSTRAINT `fk_nilai_guru` FOREIGN KEY (`created_by`) REFERENCES `guru` (`id_guru`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nilai_japel_final_fix` FOREIGN KEY (`id_japel`) REFERENCES `japel` (`id_japel`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_nilai_siswa` FOREIGN KEY (`id_siswa_kelas`) REFERENCES `siswa_kelas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_nilai_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id_tahun`);

--
-- Constraints for table `raport`
--
ALTER TABLE `raport`
  ADD CONSTRAINT `fk_raport_siswa_kelas` FOREIGN KEY (`id_siswa_kelas`) REFERENCES `siswa_kelas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_raport_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id_tahun`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `riwayat_pindah_kelas`
--
ALTER TABLE `riwayat_pindah_kelas`
  ADD CONSTRAINT `riwayat_pindah_kelas_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `riwayat_pindah_kelas_ibfk_2` FOREIGN KEY (`kelas_lama`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `riwayat_pindah_kelas_ibfk_3` FOREIGN KEY (`kelas_baru`) REFERENCES `kelas` (`id_kelas`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `fk_siswa_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun` (`id_tahun`) ON UPDATE CASCADE;

--
-- Constraints for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  ADD CONSTRAINT `fk_kelas_sk` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_siswa_sk` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_tahun_sk` FOREIGN KEY (`id_tahun`) REFERENCES `tahun_ajaran` (`id_tahun`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD CONSTRAINT `fk_tahunajaran_tahun` FOREIGN KEY (`id_tahun`) REFERENCES `tahun` (`id_tahun`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
