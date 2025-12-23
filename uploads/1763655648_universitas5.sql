-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 20, 2025 at 04:05 PM
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
-- Database: `universitas`
--

-- --------------------------------------------------------

--
-- Table structure for table `dosen`
--

CREATE TABLE `dosen` (
  `nik` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `id_prodi` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `status_aktif` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `tanggal_bergabung` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dosen`
--

INSERT INTO `dosen` (`nik`, `nama`, `jenis_kelamin`, `tanggal_lahir`, `id_prodi`, `email`, `no_hp`, `alamat`, `jabatan`, `status_aktif`, `tanggal_bergabung`) VALUES
(1001, 'Dr. Ahmad Saputra', 'L', '1978-04-10', 1, 'ahmad@univ.ac.id', '081234000001', 'Bandung', 'Lektor Kepala', 'Aktif', '2010-01-15'),
(1002, 'Dr. Siti Nurhaliza', 'P', '1982-11-02', 1, 'siti@univ.ac.id', '081234000002', 'Purwakarta', 'Lektor', 'Aktif', '2015-02-20'),
(1003, 'Ir. Budi Santoso', 'L', '1975-07-20', 2, 'budi@univ.ac.id', '081234000003', 'Cirebon', 'Dosen', 'Aktif', '2008-03-10'),
(1004, 'Dr. Maya Putri', 'P', '1988-01-30', 1, 'maya@univ.ac.id', '081234000004', 'Bandung', 'Asisten Ahli', 'Aktif', '2018-09-01'),
(1005, 'Prof. R. Hidayat', 'L', '1969-12-12', 1, 'hidayat@univ.ac.id', '081234000005', 'Jakarta', 'Profesor', 'Aktif', '2000-07-01');

-- --------------------------------------------------------

--
-- Table structure for table `fakultas`
--

CREATE TABLE `fakultas` (
  `id_fakultas` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `id_dosen_dekan` int(11) DEFAULT NULL,
  `id_dosen_wakil_dekan` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `tanggal_berdiri` date DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fakultas`
--

INSERT INTO `fakultas` (`id_fakultas`, `nama`, `id_dosen_dekan`, `id_dosen_wakil_dekan`, `email`, `no_telp`, `alamat`, `status`, `tanggal_berdiri`, `deskripsi`) VALUES
(1, 'Fakultas Teknik', NULL, NULL, 'ft@univ.ac.id', '022700000', 'Kampus Utama', 'Aktif', '2000-09-01', 'Fakultas Teknik contoh');

-- --------------------------------------------------------

--
-- Table structure for table `file_tugas`
--

CREATE TABLE `file_tugas` (
  `id` int(11) NOT NULL,
  `id_tugas` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `kode_matkul` varchar(10) DEFAULT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `kapasitas` int(11) DEFAULT 50,
  `hari` varchar(20) DEFAULT NULL,
  `ruangan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `kode_matkul`, `id_dosen`, `jam_mulai`, `jam_selesai`, `kapasitas`, `hari`, `ruangan`) VALUES
(101, 'MK101', 1001, '08:00:00', '10:00:00', 40, 'Senin', 'R101'),
(102, 'MK102', 1002, '10:00:00', '12:00:00', 40, 'Selasa', 'Lapangan'),
(103, 'MK103', 1003, '13:00:00', '15:00:00', 40, NULL, NULL),
(201, 'MK201', 1001, '08:00:00', '10:00:00', 40, 'Selasa', 'R201'),
(202, 'MK202', 1001, '10:00:00', '12:00:00', 40, 'Rabu', 'R202'),
(203, 'MK203', 1002, '13:00:00', '15:00:00', 40, NULL, NULL),
(301, 'MK301', 1001, '08:00:00', '10:00:00', 40, 'Kamis', 'R301'),
(302, 'MK302', 1002, '10:00:00', '12:00:00', 40, NULL, NULL),
(303, 'MK303', 1004, '13:00:00', '15:00:00', 40, NULL, NULL),
(304, 'MK304', 1001, '15:00:00', '17:00:00', 40, 'Jumat', 'R302'),
(305, 'MK305', 1001, '17:00:00', '19:00:00', 40, 'Sabtu', 'R303'),
(306, 'MK306', 1005, '19:00:00', '21:00:00', 40, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `krs`
--

CREATE TABLE `krs` (
  `id_krs` int(11) NOT NULL,
  `id_mahasiswa` int(11) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `kode_matkul` varchar(10) DEFAULT NULL,
  `tanggal_pengajuan` date DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `krs`
--

INSERT INTO `krs` (`id_krs`, `id_mahasiswa`, `id_kelas`, `kode_matkul`, `tanggal_pengajuan`, `status`) VALUES
(1001, 201, 101, 'MK101', '2023-09-01', 'Disetujui'),
(1002, 201, 102, 'MK102', '2023-09-01', 'Disetujui'),
(1003, 201, 103, 'MK103', '2023-09-01', 'Disetujui'),
(1004, 201, 201, 'MK201', '2024-02-01', 'Disetujui'),
(1005, 201, 202, 'MK202', '2024-02-01', 'Disetujui'),
(1006, 201, 203, 'MK203', '2024-02-01', 'Disetujui'),
(1007, 201, 301, 'MK301', '2025-01-10', 'Disetujui'),
(1008, 201, 302, 'MK302', '2025-01-10', 'Disetujui'),
(1009, 201, 303, 'MK303', '2025-01-10', 'Disetujui'),
(1010, 201, 304, 'MK304', '2025-01-10', 'Disetujui'),
(1011, 201, 305, 'MK305', '2025-01-10', 'Disetujui'),
(1012, 201, 306, 'MK306', '2025-01-10', 'Disetujui'),
(1013, 202, 101, 'MK101', '2025-11-20', 'Disetujui'),
(1014, 203, 101, 'MK101', '2025-11-20', 'Disetujui'),
(1015, 202, 201, 'MK201', '2025-11-20', 'Disetujui'),
(1016, 204, 201, 'MK201', '2025-11-20', 'Disetujui'),
(1017, 205, 202, 'MK202', '2025-11-20', 'Disetujui'),
(1018, 206, 202, 'MK202', '2025-11-20', 'Disetujui'),
(1019, 202, 301, 'MK301', '2025-11-20', 'Disetujui'),
(1020, 203, 304, 'MK304', '2025-11-20', 'Disetujui'),
(1021, 204, 305, 'MK305', '2025-11-20', 'Disetujui');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id_mahasiswa` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama_mahasiswa` varchar(100) NOT NULL,
  `id_prodi` int(11) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `angkatan` year(4) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `id_dosen_pembimbing` int(11) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `status_akademik` enum('aktif','cuti','lulus','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `nim`, `nama_mahasiswa`, `id_prodi`, `id_kelas`, `angkatan`, `email`, `no_hp`, `id_dosen_pembimbing`, `alamat`, `status_akademik`) VALUES
(201, '230001', 'Hanif Rachman', 1, 301, '2023', 'hanif@kampus.ac.id', '081234999777', 1001, 'Purwakarta', 'aktif'),
(202, '230002', 'Alya Putri', 1, 101, '2023', 'alya@kampus.ac.id', '081234999778', 1002, 'Bandung', 'aktif'),
(203, '230003', 'Rizky Pratama', 1, 101, '2023', 'rizky@kampus.ac.id', '081234999779', 1003, 'Cirebon', 'aktif'),
(204, '230004', 'Intan Sari', 1, 201, '2023', 'intan@kampus.ac.id', '081234999780', 1004, 'Bekasi', 'aktif'),
(205, '230005', 'Deni Saputra', 1, 202, '2023', 'deni@kampus.ac.id', '081234999781', 1001, 'Garut', 'aktif'),
(206, '230006', 'Mira Lestari', 1, 202, '2023', 'mira@kampus.ac.id', '081234999782', 1002, 'Tasikmalaya', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `kode_matkul` varchar(10) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `sks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`kode_matkul`, `nama`, `id_dosen`, `semester`, `sks`) VALUES
('MK101', 'Pengantar Pemrograman', 1001, 1, 3),
('MK102', 'Matematika Dasar', 1002, 1, 3),
('MK103', 'Elektronika Dasar', 1003, 1, 3),
('MK201', 'Algoritma & Pemrograman', 1001, 2, 3),
('MK202', 'Struktur Data', 1001, 2, 3),
('MK203', 'Basis Data', 1002, 2, 3),
('MK301', 'Pemrograman Web', 1001, 3, 3),
('MK302', 'Basis Data Lanjut', 1002, 3, 3),
('MK303', 'Sistem Operasi', 1004, 3, 3),
('MK304', 'Jaringan Komputer', 1001, 3, 3),
('MK305', 'Rekayasa Perangkat Lunak', 1001, 3, 3),
('MK306', 'Kecerdasan Buatan', 1005, 3, 3);

-- --------------------------------------------------------

--
-- Table structure for table `nilai_akhir`
--

CREATE TABLE `nilai_akhir` (
  `id_nilai_akhir` int(11) NOT NULL,
  `id_krs` int(11) DEFAULT NULL,
  `nilai_tugas` decimal(5,2) DEFAULT NULL,
  `nilai_uts` decimal(5,2) DEFAULT NULL,
  `nilai_uas` decimal(5,2) DEFAULT NULL,
  `nilai_akhir` decimal(5,2) DEFAULT NULL,
  `grade` char(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai_akhir`
--

INSERT INTO `nilai_akhir` (`id_nilai_akhir`, `id_krs`, `nilai_tugas`, `nilai_uts`, `nilai_uas`, `nilai_akhir`, `grade`) VALUES
(5001, 1001, 85.00, 80.00, 88.00, 84.33, 'A'),
(5002, 1002, 78.00, 75.00, 80.00, 77.67, 'B'),
(5003, 1003, 72.00, 70.00, 74.00, 72.00, 'B'),
(5004, 1004, 88.00, 85.00, 90.00, 87.67, 'A'),
(5005, 1005, 82.00, 80.00, 84.00, 82.00, 'B'),
(5006, 1006, 76.00, 70.00, 75.00, 73.67, 'B'),
(5007, 1007, 90.00, 88.00, 92.00, 90.00, 'A'),
(5008, 1008, 84.00, 80.00, 86.00, 83.33, 'B'),
(5009, 1009, 75.00, 70.00, 78.00, 74.33, 'C'),
(5010, 1010, 80.00, 78.00, 82.00, 80.00, 'B'),
(5011, 1011, 86.00, 84.00, 88.00, 86.00, 'A'),
(5012, 1012, 70.00, 68.00, 72.00, 70.00, 'C');

-- --------------------------------------------------------

--
-- Table structure for table `nilai_tugas`
--

CREATE TABLE `nilai_tugas` (
  `id_nilai_tugas` int(11) NOT NULL,
  `id_tugas` int(11) DEFAULT NULL,
  `id_mahasiswa` int(11) DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `tanggal_submit` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nilai_tugas`
--

INSERT INTO `nilai_tugas` (`id_nilai_tugas`, `id_tugas`, `id_mahasiswa`, `nilai`, `tanggal_submit`) VALUES
(801, 701, 201, 90.00, '2025-02-14'),
(802, 701, 202, 85.00, '2025-02-14'),
(803, 702, 201, 88.00, '2025-02-21'),
(804, 702, 202, 80.00, '2025-02-21');

-- --------------------------------------------------------

--
-- Table structure for table `pertemuan`
--

CREATE TABLE `pertemuan` (
  `id_pertemuan` int(11) NOT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `minggu_ke` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `topik` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pertemuan`
--

INSERT INTO `pertemuan` (`id_pertemuan`, `id_kelas`, `minggu_ke`, `tanggal`, `topik`) VALUES
(401, 301, 1, '2025-02-01', 'Pengenalan Pemrograman Web'),
(402, 301, 2, '2025-02-08', 'HTML & CSS'),
(403, 302, 1, '2025-02-02', 'Basis Data Lanjut - Normalisasi');

-- --------------------------------------------------------

--
-- Table structure for table `pmb`
--

CREATE TABLE `pmb` (
  `id_pmb` int(11) NOT NULL,
  `nama_peserta` varchar(100) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `jalur` enum('SNBP','SNBT','Mandiri') DEFAULT NULL,
  `skor` int(11) DEFAULT NULL,
  `status` enum('Diterima','Tidak Diterima') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pmb`
--

INSERT INTO `pmb` (`id_pmb`, `nama_peserta`, `prodi`, `jalur`, `skor`, `status`) VALUES
(9001, 'Peserta SNBP 1', 'Teknik Informatika', 'SNBP', 88, 'Diterima'),
(9002, 'Peserta SNBP 2', 'Teknik Informatika', 'SNBP', 90, 'Diterima'),
(9003, 'Peserta SNBP 3', 'Teknik Informatika', 'SNBP', 82, 'Diterima'),
(9004, 'Peserta SNBP 4', 'Teknik Informatika', 'SNBP', 84, 'Diterima'),
(9005, 'Peserta SNBP 5', 'Teknik Informatika', 'SNBP', 86, 'Diterima'),
(9006, 'Peserta SNBP 6', 'Teknik Elektro', 'SNBP', 80, 'Diterima'),
(9007, 'Peserta SNBP 7', 'Teknik Elektro', 'SNBP', 79, 'Diterima'),
(9008, 'Peserta SNBP 8', 'Teknik Elektro', 'SNBP', 81, 'Diterima'),
(9009, 'Peserta SNBP 9', 'Teknik Informatika', 'SNBP', 87, 'Diterima'),
(9010, 'Peserta SNBP 10', 'Teknik Informatika', 'SNBP', 83, 'Diterima'),
(9011, 'Peserta SNBT 1', 'Teknik Informatika', 'SNBT', 78, 'Diterima'),
(9012, 'Peserta SNBT 2', 'Teknik Informatika', 'SNBT', 75, 'Diterima'),
(9013, 'Peserta SNBT 3', 'Teknik Elektro', 'SNBT', 74, 'Diterima'),
(9014, 'Peserta SNBT 4', 'Teknik Elektro', 'SNBT', 77, 'Diterima'),
(9015, 'Peserta SNBT 5', 'Teknik Informatika', 'SNBT', 79, 'Diterima'),
(9016, 'Peserta SNBT 6', 'Teknik Informatika', 'SNBT', 73, 'Diterima'),
(9017, 'Peserta SNBT 7', 'Teknik Elektro', 'SNBT', 76, 'Diterima'),
(9018, 'Peserta SNBT 8', 'Teknik Elektro', 'SNBT', 72, 'Diterima'),
(9019, 'Peserta SNBT 9', 'Teknik Informatika', 'SNBT', 80, 'Diterima'),
(9020, 'Peserta SNBT 10', 'Teknik Informatika', 'SNBT', 81, 'Diterima'),
(9021, 'Peserta Mandiri 1', 'Teknik Informatika', 'Mandiri', 70, 'Diterima'),
(9022, 'Peserta Mandiri 2', 'Teknik Informatika', 'Mandiri', 68, 'Diterima'),
(9023, 'Peserta Mandiri 3', 'Teknik Elektro', 'Mandiri', 72, 'Diterima'),
(9024, 'Peserta Mandiri 4', 'Teknik Elektro', 'Mandiri', 74, 'Diterima'),
(9025, 'Peserta Mandiri 5', 'Teknik Informatika', 'Mandiri', 69, 'Diterima'),
(9026, 'Peserta Mandiri 6', 'Teknik Informatika', 'Mandiri', 71, 'Diterima'),
(9027, 'Peserta Mandiri 7', 'Teknik Elektro', 'Mandiri', 73, 'Diterima'),
(9028, 'Peserta Mandiri 8', 'Teknik Elektro', 'Mandiri', 75, 'Diterima'),
(9029, 'Peserta Mandiri 9', 'Teknik Informatika', 'Mandiri', 67, 'Diterima'),
(9030, 'Peserta Mandiri 10', 'Teknik Informatika', 'Mandiri', 76, 'Diterima');

-- --------------------------------------------------------

--
-- Table structure for table `prodi`
--

CREATE TABLE `prodi` (
  `id_prodi` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jenjang` enum('D3','S1','S2','S3') NOT NULL,
  `id_fakultas` int(11) DEFAULT NULL,
  `id_dosen_kaprodi` int(11) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('Aktif','Tidak Aktif') DEFAULT 'Aktif',
  `tanggal_berdiri` date DEFAULT NULL,
  `akreditasi` enum('A','B','C','Baik','Baik Sekali','Unggul') DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prodi`
--

INSERT INTO `prodi` (`id_prodi`, `nama`, `jenjang`, `id_fakultas`, `id_dosen_kaprodi`, `email`, `status`, `tanggal_berdiri`, `akreditasi`, `deskripsi`) VALUES
(1, 'Teknik Informatika', 'S1', 1, NULL, 'ti@univ.ac.id', 'Aktif', '2001-08-15', 'B', 'Program studi TI'),
(2, 'Teknik Elektro', 'S1', 1, NULL, 'te@univ.ac.id', 'Aktif', '2005-08-15', 'B', 'Program studi TE');

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id_tugas` int(11) NOT NULL,
  `id_pertemuan` int(11) DEFAULT NULL,
  `nama_tugas` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id_tugas`, `id_pertemuan`, `nama_tugas`, `deskripsi`, `tanggal_deadline`) VALUES
(701, 401, 'Tugas 1 - Landing Page', 'Buat landing page', '2025-02-15'),
(702, 402, 'Tugas 2 - Form', 'Buat form validasi', '2025-02-22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`nik`),
  ADD KEY `id_prodi` (`id_prodi`);

--
-- Indexes for table `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`id_fakultas`);

--
-- Indexes for table `file_tugas`
--
ALTER TABLE `file_tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_tugas` (`id_tugas`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD KEY `kode_matkul` (`kode_matkul`),
  ADD KEY `id_dosen` (`id_dosen`);

--
-- Indexes for table `krs`
--
ALTER TABLE `krs`
  ADD PRIMARY KEY (`id_krs`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `kode_matkul` (`kode_matkul`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `id_prodi` (`id_prodi`),
  ADD KEY `id_kelas` (`id_kelas`),
  ADD KEY `id_dosen_pembimbing` (`id_dosen_pembimbing`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`kode_matkul`),
  ADD KEY `id_dosen` (`id_dosen`);

--
-- Indexes for table `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  ADD PRIMARY KEY (`id_nilai_akhir`),
  ADD KEY `id_krs` (`id_krs`);

--
-- Indexes for table `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD PRIMARY KEY (`id_nilai_tugas`),
  ADD KEY `id_tugas` (`id_tugas`),
  ADD KEY `id_mahasiswa` (`id_mahasiswa`);

--
-- Indexes for table `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD PRIMARY KEY (`id_pertemuan`),
  ADD KEY `id_kelas` (`id_kelas`);

--
-- Indexes for table `pmb`
--
ALTER TABLE `pmb`
  ADD PRIMARY KEY (`id_pmb`);

--
-- Indexes for table `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id_prodi`),
  ADD KEY `id_fakultas` (`id_fakultas`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `id_pertemuan` (`id_pertemuan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fakultas`
--
ALTER TABLE `fakultas`
  MODIFY `id_fakultas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `file_tugas`
--
ALTER TABLE `file_tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=307;

--
-- AUTO_INCREMENT for table `krs`
--
ALTER TABLE `krs`
  MODIFY `id_krs` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1022;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=207;

--
-- AUTO_INCREMENT for table `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  MODIFY `id_nilai_akhir` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5013;

--
-- AUTO_INCREMENT for table `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  MODIFY `id_nilai_tugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=805;

--
-- AUTO_INCREMENT for table `pertemuan`
--
ALTER TABLE `pertemuan`
  MODIFY `id_pertemuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=404;

--
-- AUTO_INCREMENT for table `pmb`
--
ALTER TABLE `pmb`
  MODIFY `id_pmb` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9031;

--
-- AUTO_INCREMENT for table `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id_prodi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id_tugas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=703;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `dosen_ibfk_1` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`);

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`kode_matkul`) REFERENCES `mata_kuliah` (`kode_matkul`),
  ADD CONSTRAINT `kelas_ibfk_2` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`nik`);

--
-- Constraints for table `krs`
--
ALTER TABLE `krs`
  ADD CONSTRAINT `krs_ibfk_1` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`),
  ADD CONSTRAINT `krs_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `krs_ibfk_3` FOREIGN KEY (`kode_matkul`) REFERENCES `mata_kuliah` (`kode_matkul`);

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`),
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`),
  ADD CONSTRAINT `mahasiswa_ibfk_3` FOREIGN KEY (`id_dosen_pembimbing`) REFERENCES `dosen` (`nik`);

--
-- Constraints for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD CONSTRAINT `mata_kuliah_ibfk_1` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`nik`);

--
-- Constraints for table `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  ADD CONSTRAINT `nilai_akhir_ibfk_1` FOREIGN KEY (`id_krs`) REFERENCES `krs` (`id_krs`);

--
-- Constraints for table `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD CONSTRAINT `nilai_tugas_ibfk_1` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`),
  ADD CONSTRAINT `nilai_tugas_ibfk_2` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`);

--
-- Constraints for table `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD CONSTRAINT `pertemuan_ibfk_1` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`);

--
-- Constraints for table `prodi`
--
ALTER TABLE `prodi`
  ADD CONSTRAINT `prodi_ibfk_1` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`);

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`id_pertemuan`) REFERENCES `pertemuan` (`id_pertemuan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
