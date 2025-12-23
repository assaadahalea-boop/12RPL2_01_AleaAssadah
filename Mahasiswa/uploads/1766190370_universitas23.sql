-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 20 Des 2025 pada 01.11
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id_absensi` int(11) NOT NULL,
  `id_pertemuan` int(11) NOT NULL,
  `nim_mahasiswa` varchar(20) NOT NULL,
  `status_absensi` enum('Hadir','Tidak Hadir','Izin','Sakit') DEFAULT 'Hadir',
  `waktu_absensi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `dosen`
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
-- Dumping data untuk tabel `dosen`
--

INSERT INTO `dosen` (`nik`, `nama`, `jenis_kelamin`, `tanggal_lahir`, `id_prodi`, `email`, `no_hp`, `alamat`, `jabatan`, `status_aktif`, `tanggal_bergabung`) VALUES
(1001, 'Dr. Hendra Pratama', NULL, NULL, 1, 'hendra@univ.ac.id', NULL, NULL, NULL, 'Aktif', NULL),
(1002, 'Dr. Siti Nurhaliza', NULL, NULL, 1, 'siti@univ.ac.id', NULL, NULL, NULL, 'Aktif', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `fakultas`
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
-- Dumping data untuk tabel `fakultas`
--

INSERT INTO `fakultas` (`id_fakultas`, `nama`, `id_dosen_dekan`, `id_dosen_wakil_dekan`, `email`, `no_telp`, `alamat`, `status`, `tanggal_berdiri`, `deskripsi`) VALUES
(1, 'Fakultas Teknik', NULL, NULL, NULL, NULL, NULL, 'Aktif', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `file_tugas`
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
-- Struktur dari tabel `jadwal_kelas`
--

CREATE TABLE `jadwal_kelas` (
  `id_jadwal` int(11) NOT NULL,
  `id_kelas` int(11) NOT NULL,
  `kode_matkul` varchar(10) NOT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `hari` varchar(20) DEFAULT NULL,
  `ruangan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jadwal_kelas`
--

INSERT INTO `jadwal_kelas` (`id_jadwal`, `id_kelas`, `kode_matkul`, `id_dosen`, `hari`, `ruangan`) VALUES
(1, 101, 'MK-H01', 1001, 'Senin', 'R101'),
(2, 101, 'MK-H02', 1001, 'Selasa', 'R101'),
(3, 102, 'MK-H01', 1001, 'Rabu', 'R102'),
(4, 102, 'MK-H02', 1001, 'Kamis', 'R102'),
(5, 201, 'MK-S01', 1002, 'Selasa', 'R201'),
(6, 201, 'MK-S02', 1002, 'Rabu', 'R201'),
(7, 202, 'MK-S01', 1002, 'Kamis', 'R202'),
(8, 202, 'MK-S02', 1002, 'Senin', 'R202');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(11) NOT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `jam_mulai` time DEFAULT NULL,
  `jam_selesai` time DEFAULT NULL,
  `kapasitas` int(11) DEFAULT 50,
  `hari` varchar(20) DEFAULT NULL,
  `ruangan` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `id_dosen`, `jam_mulai`, `jam_selesai`, `kapasitas`, `hari`, `ruangan`) VALUES
(101, 1001, NULL, NULL, 50, NULL, 'R101'),
(102, 1001, NULL, NULL, 50, NULL, 'R102'),
(201, 1002, NULL, NULL, 50, NULL, 'R201'),
(202, 1002, NULL, NULL, 50, NULL, 'R202');

-- --------------------------------------------------------

--
-- Struktur dari tabel `krs`
--

CREATE TABLE `krs` (
  `id_krs` int(11) NOT NULL,
  `id_mahasiswa` int(11) DEFAULT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `kode_matkul` varchar(10) DEFAULT NULL,
  `tanggal_pengajuan` date DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
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
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id_mahasiswa`, `nim`, `nama_mahasiswa`, `id_prodi`, `id_kelas`, `angkatan`, `email`, `no_hp`, `id_dosen_pembimbing`, `alamat`, `status_akademik`) VALUES
(201, '230001', 'Andi Wijaya', 1, 101, NULL, NULL, NULL, 1001, NULL, 'aktif'),
(202, '230002', 'Beni Kurniawan', 1, 101, NULL, NULL, NULL, 1001, NULL, 'aktif'),
(203, '230003', 'Citra Lestari', 1, 102, NULL, NULL, NULL, 1002, NULL, 'aktif'),
(204, '230004', 'Dina Ananta', 1, 102, NULL, NULL, NULL, 1001, NULL, 'aktif'),
(205, '230005', 'Eko Prasetyo', 1, 201, NULL, NULL, NULL, 1001, NULL, 'aktif'),
(206, '230006', 'Faris Jibran', 1, 201, NULL, NULL, NULL, 1002, NULL, 'aktif'),
(207, '230007', 'Gita Permata', 1, 202, NULL, NULL, NULL, 1002, NULL, 'aktif'),
(208, '230008', 'Hana Sofia', 1, 201, NULL, NULL, NULL, 1001, NULL, 'aktif'),
(209, '230009', 'Irfan Hakim', 1, 202, NULL, NULL, NULL, 1002, NULL, 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `kode_matkul` varchar(10) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `id_dosen` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `sks` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`kode_matkul`, `nama`, `id_dosen`, `semester`, `sks`) VALUES
('MK-H01', 'Matematika', 1001, 1, 3),
('MK-H02', 'Pemrograman Web', 1001, 3, 3),
('MK-S01', 'Fisika', 1002, 1, 3),
('MK-S02', 'Basis Data', 1002, 2, 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_akhir`
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `nilai_tugas`
--

CREATE TABLE `nilai_tugas` (
  `id_nilai_tugas` int(11) NOT NULL,
  `id_tugas` int(11) DEFAULT NULL,
  `id_mahasiswa` int(11) DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `tanggal_submit` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertemuan`
--

CREATE TABLE `pertemuan` (
  `id_pertemuan` int(11) NOT NULL,
  `id_kelas` int(11) DEFAULT NULL,
  `minggu_ke` int(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `topik` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pertemuan`
--

INSERT INTO `pertemuan` (`id_pertemuan`, `id_kelas`, `minggu_ke`, `tanggal`, `topik`) VALUES
(1, 101, 1, NULL, 'Pengantar Matematika'),
(2, 101, 2, NULL, 'Logika & Himpunan'),
(3, 101, 3, NULL, 'Aljabar Linear'),
(4, 101, 4, NULL, 'Kalkulus Dasar'),
(5, 101, 5, NULL, 'Latihan Soal UTS'),
(6, 102, 1, NULL, 'HTML Dasar'),
(7, 102, 2, NULL, 'CSS Dasar'),
(8, 102, 3, NULL, 'Javascript Introduction'),
(9, 102, 4, NULL, 'Bootstrap Framework'),
(10, 102, 5, NULL, 'Review Project Web Statis'),
(11, 201, 1, NULL, 'Besaran dan Satuan'),
(12, 201, 2, NULL, 'Mekanika & Gaya'),
(13, 201, 3, NULL, 'Termodinamika'),
(14, 201, 4, NULL, 'Optik & Cahaya'),
(15, 201, 5, NULL, 'Fisika Modern'),
(16, 202, 1, NULL, 'Pengantar Basis Data'),
(17, 202, 2, NULL, 'ERD (Entity Relationship Diagram)'),
(18, 202, 3, NULL, 'Normalisasi Database'),
(19, 202, 4, NULL, 'SQL Dasar (SELECT, INSERT)'),
(20, 202, 5, NULL, 'SQL Join & Relasi');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pmb`
--

CREATE TABLE `pmb` (
  `id_pmb` int(11) NOT NULL,
  `nama_peserta` varchar(100) DEFAULT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `jalur` enum('SNBP','SNBT','Mandiri') DEFAULT NULL,
  `skor` int(11) DEFAULT NULL,
  `status` enum('Diterima','Tidak Diterima') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `prodi`
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
-- Dumping data untuk tabel `prodi`
--

INSERT INTO `prodi` (`id_prodi`, `nama`, `jenjang`, `id_fakultas`, `id_dosen_kaprodi`, `email`, `status`, `tanggal_berdiri`, `akreditasi`, `deskripsi`) VALUES
(1, 'Teknik Informatika', 'S1', 1, NULL, NULL, 'Aktif', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas`
--

CREATE TABLE `tugas` (
  `id_tugas` int(11) NOT NULL,
  `id_pertemuan` int(11) DEFAULT NULL,
  `nama_tugas` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `tanggal_deadline` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tugas_pengumpulan`
--

CREATE TABLE `tugas_pengumpulan` (
  `id_pengumpulan` int(11) NOT NULL,
  `id_tugas` int(11) DEFAULT NULL,
  `id_mahasiswa` int(11) DEFAULT NULL,
  `file_upload` varchar(255) DEFAULT NULL,
  `tanggal_submit` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD KEY `fk_absensi_pertemuan` (`id_pertemuan`);

--
-- Indeks untuk tabel `dosen`
--
ALTER TABLE `dosen`
  ADD PRIMARY KEY (`nik`),
  ADD KEY `fk_dosen_prodi` (`id_prodi`);

--
-- Indeks untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  ADD PRIMARY KEY (`id_fakultas`);

--
-- Indeks untuk tabel `file_tugas`
--
ALTER TABLE `file_tugas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `fk_jadwal_kelas` (`id_kelas`),
  ADD KEY `fk_jadwal_matkul` (`kode_matkul`),
  ADD KEY `fk_jadwal_dosen` (`id_dosen`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`);

--
-- Indeks untuk tabel `krs`
--
ALTER TABLE `krs`
  ADD PRIMARY KEY (`id_krs`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id_mahasiswa`),
  ADD KEY `fk_mhs_prodi` (`id_prodi`),
  ADD KEY `fk_mhs_pembimbing` (`id_dosen_pembimbing`);

--
-- Indeks untuk tabel `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`kode_matkul`);

--
-- Indeks untuk tabel `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  ADD PRIMARY KEY (`id_nilai_akhir`);

--
-- Indeks untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD PRIMARY KEY (`id_nilai_tugas`),
  ADD KEY `fk_nilai_tugas_id` (`id_tugas`),
  ADD KEY `fk_nilai_tugas_mhs` (`id_mahasiswa`);

--
-- Indeks untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD PRIMARY KEY (`id_pertemuan`),
  ADD KEY `fk_pertemuan_kelas` (`id_kelas`);

--
-- Indeks untuk tabel `pmb`
--
ALTER TABLE `pmb`
  ADD PRIMARY KEY (`id_pmb`);

--
-- Indeks untuk tabel `prodi`
--
ALTER TABLE `prodi`
  ADD PRIMARY KEY (`id_prodi`),
  ADD KEY `fk_prodi_fakultas` (`id_fakultas`);

--
-- Indeks untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id_tugas`),
  ADD KEY `fk_tugas_pertemuan` (`id_pertemuan`);

--
-- Indeks untuk tabel `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  ADD PRIMARY KEY (`id_pengumpulan`),
  ADD KEY `fk_submit_tugas` (`id_tugas`),
  ADD KEY `fk_submit_mahasiswa` (`id_mahasiswa`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id_absensi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `fakultas`
--
ALTER TABLE `fakultas`
  MODIFY `id_fakultas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `file_tugas`
--
ALTER TABLE `file_tugas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  MODIFY `id_jadwal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=203;

--
-- AUTO_INCREMENT untuk tabel `krs`
--
ALTER TABLE `krs`
  MODIFY `id_krs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id_mahasiswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=210;

--
-- AUTO_INCREMENT untuk tabel `nilai_akhir`
--
ALTER TABLE `nilai_akhir`
  MODIFY `id_nilai_akhir` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  MODIFY `id_nilai_tugas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  MODIFY `id_pertemuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `pmb`
--
ALTER TABLE `pmb`
  MODIFY `id_pmb` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `prodi`
--
ALTER TABLE `prodi`
  MODIFY `id_prodi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id_tugas` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  MODIFY `id_pengumpulan` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `fk_absensi_pertemuan` FOREIGN KEY (`id_pertemuan`) REFERENCES `pertemuan` (`id_pertemuan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `dosen`
--
ALTER TABLE `dosen`
  ADD CONSTRAINT `fk_dosen_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `jadwal_kelas`
--
ALTER TABLE `jadwal_kelas`
  ADD CONSTRAINT `fk_jadwal_dosen` FOREIGN KEY (`id_dosen`) REFERENCES `dosen` (`nik`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_jadwal_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_jadwal_matkul` FOREIGN KEY (`kode_matkul`) REFERENCES `mata_kuliah` (`kode_matkul`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `fk_mhs_pembimbing` FOREIGN KEY (`id_dosen_pembimbing`) REFERENCES `dosen` (`nik`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mhs_prodi` FOREIGN KEY (`id_prodi`) REFERENCES `prodi` (`id_prodi`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `nilai_tugas`
--
ALTER TABLE `nilai_tugas`
  ADD CONSTRAINT `fk_nilai_tugas_id` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_nilai_tugas_mhs` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD CONSTRAINT `fk_pertemuan_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `prodi`
--
ALTER TABLE `prodi`
  ADD CONSTRAINT `fk_prodi_fakultas` FOREIGN KEY (`id_fakultas`) REFERENCES `fakultas` (`id_fakultas`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `fk_tugas_pertemuan` FOREIGN KEY (`id_pertemuan`) REFERENCES `pertemuan` (`id_pertemuan`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tugas_pengumpulan`
--
ALTER TABLE `tugas_pengumpulan`
  ADD CONSTRAINT `fk_submit_mahasiswa` FOREIGN KEY (`id_mahasiswa`) REFERENCES `mahasiswa` (`id_mahasiswa`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submit_tugas` FOREIGN KEY (`id_tugas`) REFERENCES `tugas` (`id_tugas`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
