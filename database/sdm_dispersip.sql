-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 09, 2026 at 12:47 AM
-- Server version: 8.0.30
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sdm_dispersip`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `absensi_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `lng_masuk` decimal(11,8) DEFAULT NULL,
  `lat_pulang` decimal(10,8) DEFAULT NULL,
  `lng_pulang` decimal(11,8) DEFAULT NULL,
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `status_absensi` enum('Hadir','Terlambat','Tidak Hadir','Izin','Cuti') DEFAULT 'Hadir',
  `keterangan` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`absensi_id`, `pegawai_id`, `tanggal`, `jam_masuk`, `jam_pulang`, `lat_masuk`, `lng_masuk`, `lat_pulang`, `lng_pulang`, `foto_masuk`, `foto_pulang`, `status_absensi`, `keterangan`, `created_at`) VALUES
(2, 9, '2025-12-16', '08:00:00', '16:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Hadir', NULL, '2025-12-16 12:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `arsip_dokumen`
--

CREATE TABLE `arsip_dokumen` (
  `dok_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jenis_dokumen` varchar(100) DEFAULT NULL,
  `nama_dokumen` varchar(150) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `arsip_dokumen`
--

INSERT INTO `arsip_dokumen` (`dok_id`, `pegawai_id`, `jenis_dokumen`, `nama_dokumen`, `file_path`, `uploaded_by`, `created_at`) VALUES
(1, 2, 'S', 'SK SITI', 'uploads/pegawai_2/20251128_022528_Undangan_Rapat_Koordinasi_Lokasi_Titik_Baca.pdf', 2, '2025-11-28 10:25:28'),
(2, 9, 'SK', 'SK', 'uploads/pegawai_9/20260109_130039_Formulir_Permintaan_dan_Pemberian_Cuti.pdf', 5, '2026-01-09 21:00:39');

-- --------------------------------------------------------

--
-- Table structure for table `cuti`
--

CREATE TABLE `cuti` (
  `cuti_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jenis_cuti` enum('Tahunan','Sakit','Melahirkan','Penting','Besar') DEFAULT 'Tahunan',
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `lama_hari` int NOT NULL,
  `alasan` text,
  `disposisi` text,
  `alasan_ditolak` text,
  `status` enum('Menunggu','Disetujui','Ditolak') DEFAULT 'Menunggu',
  `verifikator_user_id` int DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cuti`
--

INSERT INTO `cuti` (`cuti_id`, `pegawai_id`, `jenis_cuti`, `tgl_mulai`, `tgl_selesai`, `lama_hari`, `alasan`, `disposisi`, `alasan_ditolak`, `status`, `verifikator_user_id`, `verified_at`, `created_at`, `updated_at`) VALUES
(6, 9, 'Tahunan', '2026-01-12', '2026-01-15', 4, 'Melakukan Cuti Tahunan', NULL, 'tes', 'Ditolak', 3, '2026-01-09 12:45:28', '2026-01-09 19:40:01', '2026-01-09 20:45:28'),
(7, 12, 'Tahunan', '2026-01-14', '2026-01-16', 3, 'Tahunan', '', NULL, 'Disetujui', 4, '2026-01-09 13:21:11', '2026-01-09 20:49:21', '2026-01-09 21:21:11'),
(8, 13, 'Tahunan', '2026-02-06', '2026-02-10', 3, 'Tahunan', 'Disetujui', NULL, 'Disetujui', 4, '2026-02-06 02:17:19', '2026-02-06 09:56:43', '2026-02-06 10:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `gaji`
--

CREATE TABLE `gaji` (
  `gaji_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `periode` char(7) NOT NULL,
  `gaji_pokok` decimal(12,2) NOT NULL,
  `tunjangan` decimal(12,2) DEFAULT '0.00',
  `potongan` decimal(12,2) DEFAULT '0.00',
  `total_gaji` decimal(12,2) GENERATED ALWAYS AS (((coalesce(`gaji_pokok`,0) + coalesce(`tunjangan`,0)) - coalesce(`potongan`,0))) STORED,
  `keterangan` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `gaji`
--

INSERT INTO `gaji` (`gaji_id`, `pegawai_id`, `periode`, `gaji_pokok`, `tunjangan`, `potongan`, `keterangan`, `created_at`) VALUES
(1, 9, '2025-11', '2500000.00', '200000.00', '0.00', NULL, '2025-11-28 22:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `hari_libur`
--

CREATE TABLE `hari_libur` (
  `libur_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(200) NOT NULL,
  `jenis` enum('Nasional','Cuti Bersama','Khusus') NOT NULL DEFAULT 'Nasional',
  `keterangan` text,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `hari_libur`
--

INSERT INTO `hari_libur` (`libur_id`, `tanggal`, `nama_libur`, `jenis`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2025-01-01', 'Tahun Baru Masehi 2025', 'Nasional', 'Tahun Baru Masehi', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(2, '2025-01-29', 'Tahun Baru Imlek 2576 Kongzili', 'Nasional', 'Tahun Baru Imlek', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(3, '2025-02-12', 'Isra Mikraj Nabi Muhammad SAW', 'Nasional', 'Isra Mikraj', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(4, '2025-03-29', 'Hari Suci Nyepi Tahun Baru Saka 1947', 'Nasional', 'Hari Raya Nyepi', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(5, '2025-03-31', 'Wafat Isa Al-Masih', 'Nasional', 'Wafat Yesus Kristus', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(6, '2025-03-31', 'Cuti Bersama Idul Fitri', 'Cuti Bersama', 'Cuti Bersama Idul Fitri', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(7, '2025-04-01', 'Hari Raya Idul Fitri 1446 H', 'Nasional', 'Hari Raya Idul Fitri', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(8, '2025-04-02', 'Hari Raya Idul Fitri 1446 H', 'Nasional', 'Hari Raya Idul Fitri', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(9, '2025-04-03', 'Cuti Bersama Idul Fitri', 'Cuti Bersama', 'Cuti Bersama Idul Fitri', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(10, '2025-04-04', 'Cuti Bersama Idul Fitri', 'Cuti Bersama', 'Cuti Bersama Idul Fitri', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(11, '2025-05-01', 'Hari Buruh Internasional', 'Nasional', 'Hari Buruh', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(12, '2025-05-12', 'Hari Raya Waisak 2569', 'Nasional', 'Hari Raya Waisak', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(13, '2025-05-29', 'Kenaikan Isa Al-Masih', 'Nasional', 'Kenaikan Yesus Kristus', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(14, '2025-06-01', 'Hari Lahir Pancasila', 'Nasional', 'Hari Lahir Pancasila', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(15, '2025-06-07', 'Hari Raya Idul Adha 1446 H', 'Nasional', 'Hari Raya Idul Adha', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(16, '2025-07-28', 'Tahun Baru Islam 1447 H', 'Nasional', 'Tahun Baru Hijriyah', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(17, '2025-08-17', 'Hari Kemerdekaan RI', 'Nasional', 'HUT Kemerdekaan RI ke-80', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(18, '2025-09-06', 'Maulid Nabi Muhammad SAW', 'Nasional', 'Maulid Nabi Muhammad SAW', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(19, '2025-12-25', 'Hari Raya Natal', 'Nasional', 'Hari Raya Natal', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09'),
(20, '2025-12-26', 'Cuti Bersama Natal', 'Cuti Bersama', 'Cuti Bersama Natal', 1, '2025-12-16 12:22:09', '2025-12-16 12:22:09');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kerja`
--

CREATE TABLE `jadwal_kerja` (
  `jadwal_id` int NOT NULL,
  `nama_jadwal` varchar(100) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_keluar` time NOT NULL,
  `toleransi_terlambat` int DEFAULT '15' COMMENT 'dalam menit',
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `jadwal_kerja`
--

INSERT INTO `jadwal_kerja` (`jadwal_id`, `nama_jadwal`, `jam_masuk`, `jam_keluar`, `toleransi_terlambat`, `is_default`, `created_at`) VALUES
(1, 'Jam Kerja Normal', '08:00:00', '16:00:00', 15, 1, '2025-12-15 13:07:21');

-- --------------------------------------------------------

--
-- Table structure for table `kenaikan_pangkat`
--

CREATE TABLE `kenaikan_pangkat` (
  `kp_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `pangkat_lama_id` int DEFAULT NULL,
  `pangkat_baru_id` int NOT NULL,
  `nomor_sk` varchar(100) DEFAULT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `tmt` date NOT NULL,
  `file_sk` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kenaikan_pangkat`
--

INSERT INTO `kenaikan_pangkat` (`kp_id`, `pegawai_id`, `pangkat_lama_id`, `pangkat_baru_id`, `nomor_sk`, `tanggal_sk`, `tmt`, `file_sk`, `created_at`) VALUES
(2, 3, 11, 15, '012/DISPERSIP-BJM/2026', '2026-12-12', '2029-03-01', 'uploads/kp/sk_20260204_064604_e30502bc.pdf', '2026-02-04 14:46:04');

-- --------------------------------------------------------

--
-- Table structure for table `kgb`
--

CREATE TABLE `kgb` (
  `kgb_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `nomor_sk` varchar(100) DEFAULT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `tmt_mulai` date NOT NULL,
  `gaji_lama` decimal(12,2) DEFAULT NULL,
  `gaji_baru` decimal(12,2) DEFAULT NULL,
  `jadwal_kgb_berikut` date DEFAULT NULL,
  `file_sk` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Disahkan') DEFAULT 'Draft',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kgb`
--

INSERT INTO `kgb` (`kgb_id`, `pegawai_id`, `nomor_sk`, `tanggal_sk`, `tmt_mulai`, `gaji_lama`, `gaji_baru`, `jadwal_kgb_berikut`, `file_sk`, `status`, `created_at`) VALUES
(1, 9, 'SK.208938294', '2024-11-28', '2025-11-28', '1200000.00', '2000000.00', '2025-11-28', 'uploads/kgb/sk_20251128_130456_1c2035c3.png', 'Disahkan', '2025-11-28 21:04:56'),
(2, 12, '123/BJM/2026', '2026-01-08', '2026-01-09', '1200000.00', '1500000.00', '2026-01-09', 'uploads/kgb/sk_20260204_123053_77d48357.pdf', 'Disahkan', '2026-01-09 21:11:29');

-- --------------------------------------------------------

--
-- Table structure for table `logbook`
--

CREATE TABLE `logbook` (
  `logbook_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `hasil` text,
  `status` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `komentar_verifikator` text,
  `verifikator_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `logbook`
--

INSERT INTO `logbook` (`logbook_id`, `pegawai_id`, `tanggal`, `kegiatan`, `hasil`, `status`, `komentar_verifikator`, `verifikator_id`, `created_at`, `updated_at`) VALUES
(1, 2, '2026-03-09', 'melakukan ujicoba website', 'berhasil menambahkan logbook', 'Disetujui', 'bagus`', 3, '2026-03-09 00:42:52', '2026-03-09 00:43:16');

-- --------------------------------------------------------

--
-- Table structure for table `master_jabatan`
--

CREATE TABLE `master_jabatan` (
  `jabatan_id` int NOT NULL,
  `nama_jabatan` varchar(150) NOT NULL,
  `eselon` varchar(20) DEFAULT NULL,
  `jenis_jabatan` enum('Struktural','Fungsional','Pelaksana') DEFAULT 'Pelaksana',
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_jabatan`
--

INSERT INTO `master_jabatan` (`jabatan_id`, `nama_jabatan`, `eselon`, `jenis_jabatan`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Kepala Dinas', 'II.b', 'Struktural', 'Kepala Dinas Perpustakaan dan Kearsipan', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(2, 'Sekretaris', 'III.a', 'Struktural', 'Sekretaris Dinas', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(3, 'Kepala Bidang Perpustakaan', 'III.b', 'Struktural', 'Kepala Bidang Perpustakaan', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(4, 'Kepala Bidang Kearsipan', 'III.b', 'Struktural', 'Kepala Bidang Kearsipan', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(5, 'Kepala Sub Bagian Umum', 'IV.a', 'Struktural', 'Kepala Sub Bagian Umum dan Kepegawaian', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(6, 'Pustakawan', NULL, 'Fungsional', 'Jabatan Fungsional Pustakawan', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(7, 'Arsiparis', NULL, 'Fungsional', 'Jabatan Fungsional Arsiparis', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(8, 'Staf Administrasi', NULL, 'Pelaksana', 'Staf Administrasi Umum', '2025-11-26 19:24:41', '2025-11-26 19:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `master_pangkat`
--

CREATE TABLE `master_pangkat` (
  `pangkat_id` int NOT NULL,
  `nama_pangkat` varchar(100) NOT NULL,
  `golongan` varchar(10) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `master_pangkat`
--

INSERT INTO `master_pangkat` (`pangkat_id`, `nama_pangkat`, `golongan`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Juru Muda', 'I/a', 'Golongan I', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(2, 'Juru Muda Tingkat I', 'I/b', 'Golongan I', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(3, 'Juru', 'I/c', 'Golongan I', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(4, 'Juru Tingkat I', 'I/d', 'Golongan I', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(5, 'Pengatur Muda', 'II/a', 'Golongan II', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(6, 'Pengatur Muda Tingkat I', 'II/b', 'Golongan II', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(7, 'Pengatur', 'II/c', 'Golongan II', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(8, 'Pengatur Tingkat I', 'II/d', 'Golongan II', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(9, 'Penata Muda', 'III/a', 'Golongan III', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(10, 'Penata Muda Tingkat I', 'III/b', 'Golongan III', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(11, 'Penata', 'III/c', 'Golongan III', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(12, 'Penata Tingkat I', 'III/d', 'Golongan III', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(13, 'Pembina', 'IV/a', 'Golongan IV', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(14, 'Pembina Tingkat I', 'IV/b', 'Golongan IV', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(15, 'Pembina Utama Muda', 'IV/c', 'Golongan IV', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(16, 'Pembina Utama Madya', 'IV/d', 'Golongan IV', '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(17, 'Pembina Utama', 'IV/e', 'Golongan IV', '2025-11-26 19:24:41', '2025-11-26 19:24:41');

-- --------------------------------------------------------

--
-- Table structure for table `pegawai`
--

CREATE TABLE `pegawai` (
  `pegawai_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `nik` varchar(20) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `jk` enum('L','P') NOT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `status_kepegawaian` enum('PNS','PPPK','Honorer','Kontrak') NOT NULL,
  `jabatan_id` int DEFAULT NULL,
  `pangkat_id` int DEFAULT NULL,
  `tgl_mulai_kerja` date DEFAULT NULL,
  `alamat` text,
  `status_aktif` enum('Aktif','Pensiun','Pindah','Meninggal','Nonaktif') DEFAULT 'Aktif',
  `tmt_pensiun` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pegawai`
--

INSERT INTO `pegawai` (`pegawai_id`, `user_id`, `nik`, `nip`, `nama_lengkap`, `jk`, `tgl_lahir`, `status_kepegawaian`, `jabatan_id`, `pangkat_id`, `tgl_mulai_kerja`, `alamat`, `status_aktif`, `tmt_pensiun`, `created_at`, `updated_at`) VALUES
(2, 2, '6371012345678902', '199505052018032001', 'Siti Nurhaliza', 'P', '1995-05-05', 'PNS', 6, 10, '2018-03-01', 'Banjarmasin', 'Aktif', NULL, '2025-11-26 19:24:41', '2025-11-26 19:24:41'),
(3, 3, '6371012345678903', '199203032016031002', 'Ahmad Yani', 'L', '1992-03-03', 'PNS', 5, 15, '2016-03-01', 'Banjarmasin', 'Aktif', NULL, '2025-11-26 19:24:41', '2026-02-04 14:46:04'),
(4, 4, '6371012345678901', '199001012015031001', 'Administrator Sistem', 'L', '1990-01-01', 'PNS', NULL, NULL, '2015-03-01', 'Banjarmasin', 'Aktif', NULL, '2025-11-26 19:27:16', '2025-11-26 19:27:16'),
(9, 5, '6304041810990002', NULL, 'Ahmad Fauzi,S.Kom', 'L', '2025-01-01', 'Honorer', 7, 13, '2025-12-31', 'bjm', 'Aktif', NULL, '2025-11-28 09:17:12', '2026-02-06 09:43:04'),
(12, 6, '6167837813713919', '831318938917892424', 'NUR AZIZAH', 'P', '1999-10-18', 'Honorer', 7, 1, '2010-01-01', 'BANJARMASIN', 'Aktif', '2045-12-12', '2026-01-09 19:46:59', '2026-01-09 21:04:03'),
(13, 8, '3489348902089429', '202034893489020894', 'ANA DR', 'P', '1997-12-12', 'PNS', 7, 14, '2008-02-04', 'BANJARMASIN', 'Pensiun', '2026-02-04', '2026-02-04 14:39:52', '2026-02-06 09:56:03'),
(14, NULL, '6304178938791289', '199823289392833333', 'Muhammad Haiqal Faiq Dzikri', 'L', '1998-01-01', 'PNS', 8, 7, '2008-01-01', 'Marabahan', 'Pensiun', '2026-02-11', '2026-02-11 11:53:22', '2026-02-11 11:53:58');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian_kinerja`
--

CREATE TABLE `penilaian_kinerja` (
  `penilaian_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `periode` char(7) NOT NULL,
  `nilai_kuantitas` decimal(5,2) DEFAULT '0.00',
  `nilai_kualitas` decimal(5,2) DEFAULT '0.00',
  `nilai_perilaku` decimal(5,2) DEFAULT '0.00',
  `komentar` text,
  `penilai_user_id` int DEFAULT NULL,
  `skor_akhir` decimal(5,2) GENERATED ALWAYS AS ((((coalesce(`nilai_kuantitas`,0) + coalesce(`nilai_kualitas`,0)) + coalesce(`nilai_perilaku`,0)) / 3)) STORED,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `penilaian_kinerja`
--

INSERT INTO `penilaian_kinerja` (`penilaian_id`, `pegawai_id`, `periode`, `nilai_kuantitas`, `nilai_kualitas`, `nilai_perilaku`, `komentar`, `penilai_user_id`, `created_at`) VALUES
(1, 9, '2025-11', '90.00', '80.00', '95.00', 'Tingkatkan', 4, '2025-11-28 21:18:05'),
(2, 12, '2026-01', '90.00', '90.00', '90.00', 'Bagus', 4, '2026-01-09 20:11:28'),
(3, 9, '2026-01', '90.00', '90.00', '90.00', NULL, 4, '2026-01-09 20:13:14');

-- --------------------------------------------------------

--
-- Table structure for table `pensiun`
--

CREATE TABLE `pensiun` (
  `pensiun_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jenis` enum('BUP','Dini','Lainnya') DEFAULT 'BUP',
  `nomor_sk` varchar(100) DEFAULT NULL,
  `tanggal_sk` date DEFAULT NULL,
  `tmt` date NOT NULL,
  `keterangan` text,
  `file_sk` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pensiun`
--

INSERT INTO `pensiun` (`pensiun_id`, `pegawai_id`, `jenis`, `nomor_sk`, `tanggal_sk`, `tmt`, `keterangan`, `file_sk`, `created_at`) VALUES
(2, 13, 'BUP', '001/DISPERSIP-BJM/2026', '2026-02-04', '2026-02-04', NULL, 'uploads/pensiun/sk_20260204_064051_37c3ea2a.pdf', '2026-02-04 14:40:37'),
(3, 14, 'Dini', '123/BJM/2026', '2026-02-11', '2026-02-11', 'wkwkwk', 'uploads/pensiun/sk_20260211_040239_e08faed0.png', '2026-02-11 12:02:39');

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_jabatan`
--

CREATE TABLE `riwayat_jabatan` (
  `riwayat_id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `jabatan_id` int NOT NULL,
  `tmt` date NOT NULL,
  `nomor_sk` varchar(100) DEFAULT NULL,
  `file_sk` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pegawai','verifikator') NOT NULL DEFAULT 'pegawai',
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(2, 'pegawai1', '$2y$10$uvTP7pOEZjRHtnOoeAt29.E2zAOXw17Ait9tgykoPCvwUJEcEYBKa', 'pegawai', 'Aktif', '2025-11-26 19:24:41', '2025-11-28 10:06:55'),
(3, 'verifikator1', '$2y$10$5dvUVyWWoqSA1fFZCHtA1ukxGetioJ6FEeW8Vi7IBRhUZCC9LjzkK', 'verifikator', 'Aktif', '2025-11-26 19:24:41', '2025-11-28 10:06:24'),
(4, 'admin', '$2y$10$TXgrm/l/D./p9EkzR54QM.lYgu6yr2uGnSCleICPlY4aZlqh7Ffxm', 'admin', 'Aktif', '2025-11-26 19:27:16', '2025-11-26 19:27:16'),
(5, 'coba', '$2y$10$CZkepLuQepyJ12RIKyhA8OvgWpTNTPgxJCwaAE8DC70kZV6bJaukO', 'pegawai', 'Aktif', '2025-11-28 22:31:24', '2025-11-28 22:31:40'),
(6, 'azizah', '$2y$12$znqajbUJjFrMvTYR5OvzGOckwBj9NFEuDESMrumqlFpPoV9kO7bc6', 'pegawai', 'Aktif', '2026-01-09 21:04:03', '2026-02-12 21:37:14'),
(8, 'ana', '$2y$12$zOVWvJThGOUemKgx93JbdeXqInG.8Cn8M2vZPIndIqiF9xBOmPm/e', 'pegawai', 'Aktif', '2026-02-06 09:56:03', '2026-02-06 09:56:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`absensi_id`),
  ADD UNIQUE KEY `uk_absensi` (`pegawai_id`,`tanggal`);

--
-- Indexes for table `arsip_dokumen`
--
ALTER TABLE `arsip_dokumen`
  ADD PRIMARY KEY (`dok_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `cuti`
--
ALTER TABLE `cuti`
  ADD PRIMARY KEY (`cuti_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `gaji`
--
ALTER TABLE `gaji`
  ADD PRIMARY KEY (`gaji_id`),
  ADD UNIQUE KEY `uk_gaji` (`pegawai_id`,`periode`);

--
-- Indexes for table `hari_libur`
--
ALTER TABLE `hari_libur`
  ADD PRIMARY KEY (`libur_id`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_jenis` (`jenis`);

--
-- Indexes for table `jadwal_kerja`
--
ALTER TABLE `jadwal_kerja`
  ADD PRIMARY KEY (`jadwal_id`);

--
-- Indexes for table `kenaikan_pangkat`
--
ALTER TABLE `kenaikan_pangkat`
  ADD PRIMARY KEY (`kp_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `pangkat_lama_id` (`pangkat_lama_id`),
  ADD KEY `pangkat_baru_id` (`pangkat_baru_id`);

--
-- Indexes for table `kgb`
--
ALTER TABLE `kgb`
  ADD PRIMARY KEY (`kgb_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `logbook`
--
ALTER TABLE `logbook`
  ADD PRIMARY KEY (`logbook_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `master_jabatan`
--
ALTER TABLE `master_jabatan`
  ADD PRIMARY KEY (`jabatan_id`);

--
-- Indexes for table `master_pangkat`
--
ALTER TABLE `master_pangkat`
  ADD PRIMARY KEY (`pangkat_id`);

--
-- Indexes for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`pegawai_id`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nip` (`nip`),
  ADD KEY `jabatan_id` (`jabatan_id`),
  ADD KEY `pangkat_id` (`pangkat_id`);

--
-- Indexes for table `penilaian_kinerja`
--
ALTER TABLE `penilaian_kinerja`
  ADD PRIMARY KEY (`penilaian_id`),
  ADD UNIQUE KEY `uk_kinerja` (`pegawai_id`,`periode`),
  ADD KEY `penilai_user_id` (`penilai_user_id`);

--
-- Indexes for table `pensiun`
--
ALTER TABLE `pensiun`
  ADD PRIMARY KEY (`pensiun_id`),
  ADD KEY `pegawai_id` (`pegawai_id`);

--
-- Indexes for table `riwayat_jabatan`
--
ALTER TABLE `riwayat_jabatan`
  ADD PRIMARY KEY (`riwayat_id`),
  ADD KEY `pegawai_id` (`pegawai_id`),
  ADD KEY `jabatan_id` (`jabatan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `absensi_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `arsip_dokumen`
--
ALTER TABLE `arsip_dokumen`
  MODIFY `dok_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cuti`
--
ALTER TABLE `cuti`
  MODIFY `cuti_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gaji`
--
ALTER TABLE `gaji`
  MODIFY `gaji_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hari_libur`
--
ALTER TABLE `hari_libur`
  MODIFY `libur_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `kenaikan_pangkat`
--
ALTER TABLE `kenaikan_pangkat`
  MODIFY `kp_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kgb`
--
ALTER TABLE `kgb`
  MODIFY `kgb_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `logbook`
--
ALTER TABLE `logbook`
  MODIFY `logbook_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `master_jabatan`
--
ALTER TABLE `master_jabatan`
  MODIFY `jabatan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `master_pangkat`
--
ALTER TABLE `master_pangkat`
  MODIFY `pangkat_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `pegawai_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `penilaian_kinerja`
--
ALTER TABLE `penilaian_kinerja`
  MODIFY `penilaian_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pensiun`
--
ALTER TABLE `pensiun`
  MODIFY `pensiun_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `riwayat_jabatan`
--
ALTER TABLE `riwayat_jabatan`
  MODIFY `riwayat_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `absensi_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`);

--
-- Constraints for table `arsip_dokumen`
--
ALTER TABLE `arsip_dokumen`
  ADD CONSTRAINT `arsip_dokumen_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`),
  ADD CONSTRAINT `arsip_dokumen_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `cuti`
--
ALTER TABLE `cuti`
  ADD CONSTRAINT `cuti_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE CASCADE;

--
-- Constraints for table `gaji`
--
ALTER TABLE `gaji`
  ADD CONSTRAINT `gaji_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`);

--
-- Constraints for table `kenaikan_pangkat`
--
ALTER TABLE `kenaikan_pangkat`
  ADD CONSTRAINT `kenaikan_pangkat_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`),
  ADD CONSTRAINT `kenaikan_pangkat_ibfk_2` FOREIGN KEY (`pangkat_lama_id`) REFERENCES `master_pangkat` (`pangkat_id`),
  ADD CONSTRAINT `kenaikan_pangkat_ibfk_3` FOREIGN KEY (`pangkat_baru_id`) REFERENCES `master_pangkat` (`pangkat_id`);

--
-- Constraints for table `kgb`
--
ALTER TABLE `kgb`
  ADD CONSTRAINT `kgb_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`);

--
-- Constraints for table `logbook`
--
ALTER TABLE `logbook`
  ADD CONSTRAINT `logbook_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE CASCADE;

--
-- Constraints for table `pegawai`
--
ALTER TABLE `pegawai`
  ADD CONSTRAINT `pegawai_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pegawai_ibfk_2` FOREIGN KEY (`jabatan_id`) REFERENCES `master_jabatan` (`jabatan_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `pegawai_ibfk_3` FOREIGN KEY (`pangkat_id`) REFERENCES `master_pangkat` (`pangkat_id`) ON DELETE SET NULL;

--
-- Constraints for table `penilaian_kinerja`
--
ALTER TABLE `penilaian_kinerja`
  ADD CONSTRAINT `penilaian_kinerja_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`),
  ADD CONSTRAINT `penilaian_kinerja_ibfk_2` FOREIGN KEY (`penilai_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `pensiun`
--
ALTER TABLE `pensiun`
  ADD CONSTRAINT `pensiun_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`);

--
-- Constraints for table `riwayat_jabatan`
--
ALTER TABLE `riwayat_jabatan`
  ADD CONSTRAINT `riwayat_jabatan_ibfk_1` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`pegawai_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `riwayat_jabatan_ibfk_2` FOREIGN KEY (`jabatan_id`) REFERENCES `master_jabatan` (`jabatan_id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
