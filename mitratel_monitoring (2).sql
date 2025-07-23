-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 06:34 AM
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
-- Database: `mitratel_monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `nama_lengkap` varchar(40) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `telegram_chat_id` varchar(20) DEFAULT NULL,
  `foto` varchar(50) DEFAULT 'default-avatar.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `nama_lengkap`, `password`, `created_at`, `telegram_chat_id`, `foto`) VALUES
(1, 'admin', 'Helpdesk Technical Assistance', '$2y$10$Ha9k6S/UHsjdlH7uKLYmtedY49f44RSDaHY9PlK4u/p.2h7nt8IYe', '2025-04-25 17:02:41', '1767252837', 'user_1_687cbdac3eb24.png');

-- --------------------------------------------------------

--
-- Table structure for table `laporan`
--

CREATE TABLE `laporan` (
  `id_laporan` int(11) NOT NULL,
  `id_tiket` int(11) NOT NULL,
  `id_teknisi` int(11) DEFAULT NULL,
  `jenis_perbaikan` enum('temporary','permanent') NOT NULL,
  `dokumentasi` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `selesai_pada` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan`
--

INSERT INTO `laporan` (`id_laporan`, `id_tiket`, `id_teknisi`, `jenis_perbaikan`, `dokumentasi`, `catatan`, `selesai_pada`) VALUES
(12, 21, 5, 'temporary', '6859a6a9dd605_938a7a4b59b0032cfc402aac3dcb0a1f.jpg', 'Gangguan ini tercatat sebagai Repetitif, yang menunjukkan bahwa masalah serupa sudah terjadi sebelumnya. Penyebab gangguan tidak dijelaskan lebih lanjut (UNSPEK), tetapi perbaikan dilakukan menggunakan prosedur standar dan dinyatakan sebagai Repair Closure. Status gangguan ini akhirnya CLOSED setelah perbaikan selesai dan layanan kembali berfungsi normal.', '2025-06-23 19:15:06'),
(13, 29, 3, 'temporary', '6859a6264272c_201bbeb7de2a8a63c439b8c1132a9227.jpg', 'Gangguan disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang menyebabkan kerusakan pada jaringan. Setelah kabel yang terputus diperbaiki atau diganti, jaringan kembali normal. Gangguan ini dinyatakan CLOSED setelah perbaikan selesai, dan layanan pulih sesuai dengan SLA yang ditetapkan.', '2025-06-23 19:15:37'),
(14, 26, 3, 'temporary', '6859a5fc22a29_b569f5f9a3b863eb8e98931d8e871a3f.jpg', 'Gangguan disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang mengakibatkan gangguan pada jaringan. Setelah kabel yang rusak diganti atau diperbaiki, layanan dikembalikan ke kondisi normal. Gangguan ini dinyatakan CLOSED setelah perbaikan dilakukan, dan pemulihan jaringan berhasil diselesaikan dengan cepat sesuai dengan SLA yang telah ditetapkan.', '2025-06-24 01:59:44'),
(15, 30, 2, 'temporary', '6859a11fece65_Screenshot 2025-06-24 014640.png', 'Gangguan ini disebabkan oleh pekerjaan pihak ketiga yang terlibat dalam pekerjaan PU (Pekerjaan Umum), yang mempengaruhi kabel atau jaringan di area tersebut. Meskipun ini disebabkan oleh faktor eksternal, perbaikan dilakukan dengan mengganti atau memperbaiki kabel yang terpengaruh akibat pekerjaan tersebut. Setelah dilakukan perbaikan sesuai prosedur, gangguan ini dinyatakan CLOSED dan layanan dikembalikan ke kondisi normal setelah perbaikan.', '2025-07-20 10:29:44'),
(16, 36, 2, 'permanent', '687cbfaf4503f_photo_6219899474482939210_y.jpg', 'Sudah Diperbaiki', '2025-07-20 10:30:00'),
(17, 38, 2, 'temporary', '687dc32e5835e_photo_6235435006847730599_y.jpg', 'Sudah Diperbaiki', '2025-07-21 04:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `laporan_pending`
--

CREATE TABLE `laporan_pending` (
  `id_laporan_pending` int(11) NOT NULL,
  `id_tiket` int(11) NOT NULL,
  `id_teknisi` int(11) NOT NULL,
  `jenis_perbaikan` enum('temporary','permanent') NOT NULL,
  `dokumentasi` text DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `catatan_supervisor` text DEFAULT NULL,
  `id_supervisor` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `laporan_pending`
--

INSERT INTO `laporan_pending` (`id_laporan_pending`, `id_tiket`, `id_teknisi`, `jenis_perbaikan`, `dokumentasi`, `catatan`, `status_approval`, `catatan_supervisor`, `id_supervisor`, `created_at`, `updated_at`) VALUES
(8, 19, 2, 'temporary', '68599f289bd59_Screenshot 2025-06-24 013139.png', 'Gangguan ini disebabkan oleh masalah dengan Plug n Plug core ODC, yang diatasi dengan perbaikan cepat dan standar tanpa memerlukan inspeksi mendalam (status UNSPEK menunjukkan tidak ada analisis lebih lanjut yang dilakukan). Waktu perbaikan yang tercatat berada dalam batas SLA yang telah ditentukan, dan gangguan ini telah diselesaikan dengan efektif, mengembalikan jaringan ke kondisi normal setelah perbaikan.', 'pending', NULL, NULL, '2025-06-23 18:38:32', '2025-06-23 18:38:32'),
(9, 23, 2, 'temporary', '68599fce43589_Screenshot 2025-06-24 014014.png', 'Gangguan disebabkan oleh kabel FOC yang terputus akibat terkena benda tajam (FOC Tersayat Benda Tajam). Perbaikan dilakukan dengan mengganti bagian kabel yang rusak dan mengembalikan koneksi ke kondisi normal. Meskipun gangguan ini terjadi karena faktor eksternal (benda tajam), perbaikan dilakukan dengan cepat dan dalam waktu yang sesuai dengan SLA yang telah ditetapkan. Status gangguan ini akhirnya CLOSED setelah proses perbaikan selesai.', 'pending', NULL, NULL, '2025-06-23 18:41:18', '2025-06-23 18:41:18'),
(10, 27, 2, 'temporary', '6859a0a077c8d_Screenshot 2025-06-24 014314.png', 'Gangguan disebabkan oleh masalah pada [ODC] Ganti Patchcore, yang mungkin terkait dengan penggantian atau perbaikan bagian dari sistem distribusi optik (ODC). Proses perbaikan dilakukan tanpa analisis mendalam, mengacu pada status UNSPEK. Perbaikan dilakukan sesuai dengan prosedur standar, dan gangguan ini diselesaikan dalam waktu yang sesuai dengan SLA yang ditetapkan. Status gangguan ini akhirnya CLOSED setelah perbaikan dilakukan.', 'pending', NULL, NULL, '2025-06-23 18:44:48', '2025-06-23 18:44:48'),
(11, 30, 2, 'temporary', '6859a11fece65_Screenshot 2025-06-24 014640.png', 'Gangguan ini disebabkan oleh pekerjaan pihak ketiga yang terlibat dalam pekerjaan PU (Pekerjaan Umum), yang mempengaruhi kabel atau jaringan di area tersebut. Meskipun ini disebabkan oleh faktor eksternal, perbaikan dilakukan dengan mengganti atau memperbaiki kabel yang terpengaruh akibat pekerjaan tersebut. Setelah dilakukan perbaikan sesuai prosedur, gangguan ini dinyatakan CLOSED dan layanan dikembalikan ke kondisi normal setelah perbaikan.', 'approved', 'Laporan dan kondisi aktual di lapangan sama. Laporan diterima', 6, '2025-06-23 18:46:55', '2025-07-20 10:29:44'),
(12, 32, 2, 'temporary', '6859a18fe3cc4_Screenshot 2025-06-24 014835.png', 'Gangguan disebabkan oleh kabel FOC yang terputus akibat terkena benda tajam (FOC Tersayat Benda Tajam). Setelah kabel yang rusak diperbaiki atau diganti, layanan kembali pulih. Perbaikan dilakukan dengan cepat sesuai prosedur standar, dan gangguan ini akhirnya dinyatakan CLOSED setelah layanan kembali berfungsi normal.', 'pending', NULL, NULL, '2025-06-23 18:48:47', '2025-06-23 18:48:47'),
(13, 33, 2, 'temporary', '6859a2716d7ba_screenshot.jpg', 'Gangguan disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang mengakibatkan gangguan pada layanan jaringan. Perbaikan dilakukan dengan mengganti atau memperbaiki bagian kabel yang rusak. Setelah perbaikan selesai, gangguan ini dinyatakan CLOSED dan layanan kembali berfungsi normal setelah pemulihan.', 'pending', NULL, NULL, '2025-06-23 18:52:33', '2025-06-23 18:52:33'),
(14, 20, 3, 'temporary', '6859a5740c049_8f42024c7fc3b17eb10c431e0074c878.jpg', 'Gangguan ini tercatat sebagai Repetitif, yang menunjukkan bahwa masalah serupa telah terjadi sebelumnya. Penyebab gangguan tidak dijelaskan secara spesifik dalam laporan (UNSPEK), tetapi perbaikan dilakukan menggunakan prosedur standar dan mengarah pada Repair Closure. Status gangguan ini akhirnya dinyatakan CLOSED setelah perbaikan selesai dan jaringan kembali normal.', 'pending', NULL, NULL, '2025-06-23 19:05:24', '2025-06-23 19:05:24'),
(16, 29, 3, 'temporary', '6859a6264272c_201bbeb7de2a8a63c439b8c1132a9227.jpg', 'Gangguan disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang menyebabkan kerusakan pada jaringan. Setelah kabel yang terputus diperbaiki atau diganti, jaringan kembali normal. Gangguan ini dinyatakan CLOSED setelah perbaikan selesai, dan layanan pulih sesuai dengan SLA yang ditetapkan.', 'approved', 'Setelah melakukan evaluasi, saya setuju bahwa gangguan yang disebabkan oleh kabel FOC yang tertabrak kendaraan telah diperbaiki sesuai dengan prosedur yang berlaku. Kabel yang terputus telah diganti dan jaringan kembali berfungsi normal. Perbaikan dilakukan dalam waktu yang sesuai dengan SLA yang ditetapkan, dan gangguan ini dinyatakan CLOSED setelah perbaikan selesai. Semua tindakan telah sesuai dengan standar operasional.', 6, '2025-06-23 19:08:22', '2025-06-23 19:15:37'),
(17, 21, 5, 'temporary', '6859a6a9dd605_938a7a4b59b0032cfc402aac3dcb0a1f.jpg', 'Gangguan ini tercatat sebagai Repetitif, yang menunjukkan bahwa masalah serupa sudah terjadi sebelumnya. Penyebab gangguan tidak dijelaskan lebih lanjut (UNSPEK), tetapi perbaikan dilakukan menggunakan prosedur standar dan dinyatakan sebagai Repair Closure. Status gangguan ini akhirnya CLOSED setelah perbaikan selesai dan layanan kembali berfungsi normal.', 'approved', 'Setelah melakukan evaluasi terhadap gangguan yang tercatat sebagai Repetitif, saya menyetujui bahwa perbaikan telah dilakukan sesuai prosedur standar. Meskipun penyebab gangguan tidak dijelaskan lebih lanjut (UNSPEK), perbaikan berhasil mengatasi masalah dan jaringan telah kembali berfungsi normal. Perbaikan dilakukan dengan baik dan sesuai dengan SLA yang ditentukan. Status gangguan ini kini dinyatakan CLOSED dan masalah sudah teratasi', 6, '2025-06-23 19:10:33', '2025-06-23 19:15:06'),
(19, 36, 2, 'permanent', '687cbfaf4503f_photo_6219899474482939210_y.jpg', 'Sudah Diperbaiki', 'approved', 'Laporan diterima', 6, '2025-07-20 10:06:39', '2025-07-20 10:30:00'),
(20, 38, 2, 'temporary', '687dc32e5835e_photo_6235435006847730599_y.jpg', 'Sudah Diperbaiki', 'approved', 'Laporan diterima', 6, '2025-07-21 04:33:50', '2025-07-21 04:34:10');

-- --------------------------------------------------------

--
-- Table structure for table `lokasi`
--

CREATE TABLE `lokasi` (
  `id_lokasi` int(11) NOT NULL,
  `alamat` text NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lokasi`
--

INSERT INTO `lokasi` (`id_lokasi`, `alamat`, `latitude`, `longitude`) VALUES
(12, 'Pedamaran, Ogan Komering Ilir, Sumatera Selatan, Sumatra, Indonesia', -3.48557210, 104.93578270),
(13, 'Jalan Pertamina, Suka Rami, Penukal Utara, Penukal Abab Lematang Ilir, Sumatera Selatan, Sumatra, Indonesia', -3.18375310, 103.90602610),
(14, 'Jalan Pertamina, Suka Rami, Penukal Utara, Penukal Abab Lematang Ilir, Sumatera Selatan, Sumatra, Indonesia', -2.97610000, 104.77540000),
(15, 'Rimau Sungsang, Pulau Rimau, Banyuasin, Sumatera Selatan, Sumatra, Indonesia', -2.43667880, 104.64634800),
(16, 'Pematang Kijang, Jejawi, Ogan Komering Ilir, Sumatera Selatan, Sumatra, Indonesia', -3.22722930, 104.82493270),
(17, 'Tanjung Alai, Rantau Alai, Ogan Ilir, Sumatera Selatan, Sumatra, Indonesia', -3.41867110, 104.78760900),
(18, 'Muara Kati Baru I, Tiang Pumpung Kepungut, Musi Rawas, Sumatera Selatan, Sumatra, Indonesia', -3.26606040, 103.09497080),
(19, 'Sungai Lilin, Musi Banyuasin, Sumatera Selatan, Sumatra, Indonesia', -2.50530710, 104.04374630),
(20, 'Jalan TPH Sofyan Kenawas, Gandus, Palembang, Sumatera Selatan, Sumatra, 30149, Indonesia', -3.00656400, 104.68520100),
(21, 'Sungai Keruh, Musi Banyuasin, Sumatera Selatan, Sumatra, Indonesia', -3.10959210, 103.70015620),
(22, 'Campur Sari, Megang Sakti, Musi Rawas, Sumatera Selatan, Sumatra, Indonesia', -2.91146290, 102.98726410),
(23, 'Ibul Besar, Pemulutan, Ogan Ilir, Sumatera Selatan, Sumatra, Indonesia', -3.08839690, 104.77958970),
(24, 'Paya Bakal, Gelumbang, Muara Enim, Sumatera Selatan, Sumatra, Indonesia', -3.22789600, 104.40041770),
(26, 'Suka Bangun, Palembang, Sumatera Selatan', -2.93358337, 104.73395370),
(27, 'Talang Kelapa, Sumatera Selatan', -2.91456806, 104.78034127),
(28, 'Lorong Masjid, Wisma Pertamina, Palembang, Sumatera Selatan', -2.95626989, 104.76279046);

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notifikasi` int(11) NOT NULL,
  `id_teknisi` int(11) NOT NULL,
  `judul` varchar(100) NOT NULL,
  `pesan` text NOT NULL,
  `status_baca` enum('unread','read') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notifikasi`, `id_teknisi`, `judul`, `pesan`, `status_baca`, `created_at`) VALUES
(1, 2, 'Penugasan Tiket Baru', 'Tiket #37\nJenis Gangguan: Major\nLokasi: Talang Kelapa, Sumatera Selatan\nDeskripsi: Digigit Hewan\nTanggal: 20 Jul 2025 16:42\nStatus: SEGERA DITANGANI\nPrioritas: TINGGI', 'read', '2025-07-20 17:04:15'),
(2, 2, 'Penugasan Tiket Baru', 'Tiket #38\nJenis Gangguan: Major\nLokasi: Lorong Masjid, Wisma Pertamina, Palembang, Sumatera Selatan\nDeskripsi: Digigit Hewan\nTanggal: 21 Jul 2025 11:32\nStatus: SEGERA DITANGANI\nPrioritas: TINGGI', 'read', '2025-07-21 04:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `penugasan`
--

CREATE TABLE `penugasan` (
  `id_penugasan` int(11) NOT NULL,
  `id_tiket` int(11) NOT NULL,
  `id_teknisi` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penugasan`
--

INSERT INTO `penugasan` (`id_penugasan`, `id_tiket`, `id_teknisi`, `created_at`) VALUES
(18, 19, 2, '2025-06-23 17:58:59'),
(19, 20, 3, '2025-06-23 18:06:07'),
(20, 21, 5, '2025-06-23 18:09:15'),
(21, 22, 5, '2025-06-23 18:11:06'),
(22, 23, 2, '2025-06-23 18:20:58'),
(23, 29, 3, '2025-06-23 18:21:13'),
(24, 28, 5, '2025-06-23 18:21:30'),
(25, 27, 2, '2025-06-23 18:21:44'),
(26, 26, 3, '2025-06-23 18:21:59'),
(27, 25, 5, '2025-06-23 18:22:12'),
(28, 24, 5, '2025-06-23 18:22:24'),
(29, 30, 2, '2025-06-23 18:23:37'),
(30, 31, 5, '2025-06-23 18:25:10'),
(31, 32, 2, '2025-06-23 18:26:27'),
(32, 33, 2, '2025-06-23 18:27:16'),
(34, 36, 2, '2025-07-20 09:41:09'),
(35, 37, 2, '2025-07-20 16:58:11'),
(36, 38, 2, '2025-07-21 04:32:42');

-- --------------------------------------------------------

--
-- Table structure for table `supervisor`
--

CREATE TABLE `supervisor` (
  `id_supervisor` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `nama_lengkap` varchar(40) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `telegram_chat_id` varchar(20) DEFAULT NULL,
  `foto` varchar(50) DEFAULT 'default-avatar.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supervisor`
--

INSERT INTO `supervisor` (`id_supervisor`, `username`, `nama_lengkap`, `password`, `created_at`, `telegram_chat_id`, `foto`) VALUES
(6, 'supervisor', 'Sutarwan', '$2y$10$c6YefzFNqw3TuXoQENOPe.Yvt103heYtDDT9/GajG6qjzRm0mU/N.', '2025-06-10 08:46:33', '1767252837', 'user_6_6848ae6d835fb.png');

-- --------------------------------------------------------

--
-- Table structure for table `teknisi`
--

CREATE TABLE `teknisi` (
  `id_teknisi` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `nama_lengkap` varchar(40) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `telegram_chat_id` varchar(20) DEFAULT NULL,
  `foto` varchar(50) DEFAULT 'default-avatar.jpg'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teknisi`
--

INSERT INTO `teknisi` (`id_teknisi`, `username`, `nama_lengkap`, `password`, `created_at`, `telegram_chat_id`, `foto`) VALUES
(2, 'teknisi 1', 'Dwiki Fitriansyah', '$2y$10$QiQck7D/fLkt7slJ7F90JuF6HhRi.zeFKgHxdLHj/0ZIxNby6wO/S', '2025-04-25 17:02:41', '1767252837', 'user_2_683f475c858c3.png'),
(3, 'teknisi 2', 'Irwan Sadli', '$2y$10$oGN58OUE7VQPXPI6mF1KSO3NNchO2m8gwSUNjp/qiHBheqEM47ouS', '2025-05-10 07:56:18', '1767252837', 'default-avatar.jpg'),
(5, 'teknisi 3', 'Yayan Nasution', '$2y$10$TavVyhWwzI3tbVg1gZvlBOkOHLUW7ZJV/n/SJk7zq2s4Zlup2mtrW', '2025-06-10 05:15:20', '1767252837', 'default-avatar.jpg'),
(8, 'teknisi 4', 'Irwan', '$2y$10$MZh.ocHteS1d6oqiudPb4O5VE/2A..UiUBGanwTp9d.d.4ZJRIYki', '2025-06-12 03:48:48', '1767252837', 'default-avatar.jpg'),
(9, 'teknisi 5', 'Erik Sahala Simatupang', '$2y$10$yktopRhxLagTJ6d9GTr8UuviyKYyTg4Eq3grSx0ieMQyAgbf2xldK', '2025-06-12 03:49:49', '1767252837', 'default-avatar.jpg'),
(10, 'Teknisi 6', 'Hendra', '$2y$10$feKk2vvcFpfIyxb88fkNlu8PKX/Md.ew9CH4.PF1CAqKp7qQQs4Au', '2025-06-12 03:50:24', '1767252837', 'default-avatar.jpg'),
(11, 'teknisi 7', 'Imamsyah', '$2y$10$EFWuS4sX8GA4VzOyiGtR4eJIls6Rty6zsxqzKX4uzXFWei9equBM6', '2025-06-12 03:50:46', '1767252837', 'default-avatar.jpg'),
(13, 'teknisi 8', 'Andi Supri', '$2y$10$vcReoZzuNznckul8olrps.1OtG68Qu/Vuq1wNonp2yisfGnPWT/02', '2025-07-20 09:31:52', '5474102518', 'default-avatar.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tiket`
--

CREATE TABLE `tiket` (
  `id_tiket` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `id_lokasi` int(11) NOT NULL,
  `jenis_gangguan` varchar(20) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('open','on progress','selesai') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tiket`
--

INSERT INTO `tiket` (`id_tiket`, `id_admin`, `id_lokasi`, `jenis_gangguan`, `deskripsi`, `status`, `created_at`) VALUES
(19, 1, 12, 'Major', 'Gangguan yang terjadi di lokasi PALEMBANG pada titik 07OKI0039 MINANG_RAYA_MT dan 11OKI010-PEDAMARAN dikategorikan sebagai gangguan Major, yang menunjukkan tingkat kerusakan yang signifikan terhadap layanan jaringan. Penyebab gangguan ini adalah masalah pada Plug n Plug core ODC, yang mempengaruhi kestabilan jaringan.', 'selesai', '2025-06-23 17:57:35'),
(20, 1, 13, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07SKY0031 BUKIT INDAH SKY MT dan 07TLU0003 ELNUSA PENDOPO PL disebabkan oleh kabel yang terputus akibat terkena benda tajam.', 'selesai', '2025-06-23 18:06:01'),
(21, 1, 13, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07SKY0031 BUKIT INDAH SKY MT dan 07TLU0003 ELNUSA PENDOPO PL. Gangguan ini berulang, dengan penyebab Repetitif, yang menunjukkan bahwa masalah serupa terjadi lebih dari sekali.', 'selesai', '2025-06-23 18:09:10'),
(22, 1, 14, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07TLU0003 ELNUSA PENDOPO PL dan 07SKY0031 BUKIT INDAH SKY MT disebabkan oleh kabel yang terputus akibat terkena benda tajam (FOC Tersayat Benda Tajam).', 'on progress', '2025-06-23 18:10:53'),
(23, 1, 15, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07BNS0138 RIMAU_SUNGSANG_TB dan 07BNS0107 KARANG_BARU_BNS_MT disebabkan oleh kabel yang terputus akibat terkena benda tajam (FOC Tersayat Benda Tajam)', 'selesai', '2025-06-23 18:12:11'),
(24, 1, 16, 'Critical', 'Gangguan yang terjadi di PALEMBANG pada titik 07OKI0019 PEMATANG_KIJANG_TB dan 07OKI0038 AWAL_TERUSAN_MT disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang mengakibatkan gangguan serius.', 'on progress', '2025-06-23 18:13:02'),
(25, 1, 17, 'Critical', 'Gangguan yang terjadi di PALEMBANG pada titik 07OKI0035 TANJUNG ALAI MT dan 07OKI0019 PEMATANG_KIJANG_TB disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang menyebabkan gangguan dengan dampak besar terhadap jaringan.', 'on progress', '2025-06-23 18:14:11'),
(26, 1, 18, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 11LLG040 SIMPANG_MUARA_KATI_TB dan 07LLG0033 LUBUK_BESAR_PLB_TB disebabkan oleh kabel FOC yang digigit binatang (FOC Gigitan Binatang).', 'selesai', '2025-06-23 18:17:00'),
(27, 1, 19, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 11SKY001 ROAD_SUNGAI_LILIN dan 07SKY0050 SUMBER_HARUM_MT disebabkan oleh masalah pada [ODC] Ganti Patchcore.', 'selesai', '2025-06-23 18:18:16'),
(28, 1, 14, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07SKY0031 BUKIT INDAH SKY MT dan 07TLU003 ELNUSA PENDOPO disebabkan oleh masalah yang tercatat sebagai Repetitif.', 'on progress', '2025-06-23 18:19:12'),
(29, 1, 20, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07PLG0025 KENAWAS_HS dan 07PLG0122 LORONG_PANCASILA_PLG_MT disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang mengakibatkan gangguan besar pada layanan jaringan.', 'selesai', '2025-06-23 18:20:38'),
(30, 1, 21, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 11SKY037 SUNGAI_KERUH_MUBA dan 07SKY0039 SUKALALI_MT disebabkan oleh pekerjaan pihak ketiga yang terlibat dalam pekerjaan PU (Pekerjaan Umum), yang mengakibatkan gangguan pada jaringan FOC.', 'selesai', '2025-06-23 18:23:32'),
(31, 1, 22, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07LLG0022 CAMPURSARI_MN dan 07LLG0006 KARYAMULYA_LLG_MT disebabkan oleh masalah pada [ODC] Plug Unplug Core.', 'on progress', '2025-06-23 18:25:03'),
(32, 1, 23, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07OGI0059 SINGADEKANE3PLG_TB dan 07OGI0055 IBUL_BESAR_LS disebabkan oleh kabel FOC yang terputus akibat terkena benda tajam (FOC Tersayat Benda Tajam). ', 'selesai', '2025-06-23 18:26:22'),
(33, 1, 24, 'Major', 'Gangguan yang terjadi di PALEMBANG pada titik 07MEN0090 GELUMBANG1_PL dan 07MEN0095 PAYABAKAL_MT disebabkan oleh kabel FOC yang tertabrak kendaraan (FOC Tertabrak Kendaraan), yang mengakibatkan gangguan serius pada jaringan.', 'selesai', '2025-06-23 18:27:10'),
(36, 1, 26, 'Major', 'Tertimpa Pohon', 'selesai', '2025-07-20 09:32:32'),
(37, 1, 27, 'Major', 'Digigit Hewan', 'on progress', '2025-07-20 09:42:27'),
(38, 1, 28, 'Major', 'Digigit Hewan', 'selesai', '2025-07-21 04:32:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id_admin`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `laporan`
--
ALTER TABLE `laporan`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `tiket_id` (`id_tiket`),
  ADD KEY `laporan_ibfk_2` (`id_teknisi`);

--
-- Indexes for table `laporan_pending`
--
ALTER TABLE `laporan_pending`
  ADD PRIMARY KEY (`id_laporan_pending`),
  ADD KEY `id_tiket` (`id_tiket`),
  ADD KEY `id_teknisi` (`id_teknisi`),
  ADD KEY `id_supervisor` (`id_supervisor`);

--
-- Indexes for table `lokasi`
--
ALTER TABLE `lokasi`
  ADD PRIMARY KEY (`id_lokasi`),
  ADD UNIQUE KEY `latitude` (`latitude`,`longitude`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notifikasi`),
  ADD KEY `fk_notif_teknisi` (`id_teknisi`);

--
-- Indexes for table `penugasan`
--
ALTER TABLE `penugasan`
  ADD PRIMARY KEY (`id_penugasan`),
  ADD KEY `tiket_id` (`id_tiket`),
  ADD KEY `teknisi_id` (`id_teknisi`);

--
-- Indexes for table `supervisor`
--
ALTER TABLE `supervisor`
  ADD PRIMARY KEY (`id_supervisor`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `teknisi`
--
ALTER TABLE `teknisi`
  ADD PRIMARY KEY (`id_teknisi`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `tiket`
--
ALTER TABLE `tiket`
  ADD PRIMARY KEY (`id_tiket`),
  ADD KEY `admin_id` (`id_admin`),
  ADD KEY `lokasi_id` (`id_lokasi`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id_admin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `laporan`
--
ALTER TABLE `laporan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `laporan_pending`
--
ALTER TABLE `laporan_pending`
  MODIFY `id_laporan_pending` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lokasi`
--
ALTER TABLE `lokasi`
  MODIFY `id_lokasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notifikasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penugasan`
--
ALTER TABLE `penugasan`
  MODIFY `id_penugasan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `supervisor`
--
ALTER TABLE `supervisor`
  MODIFY `id_supervisor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `teknisi`
--
ALTER TABLE `teknisi`
  MODIFY `id_teknisi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `tiket`
--
ALTER TABLE `tiket`
  MODIFY `id_tiket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan`
--
ALTER TABLE `laporan`
  ADD CONSTRAINT `laporan_ibfk_1` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id_tiket`),
  ADD CONSTRAINT `laporan_ibfk_2` FOREIGN KEY (`id_teknisi`) REFERENCES `teknisi` (`id_teknisi`);

--
-- Constraints for table `laporan_pending`
--
ALTER TABLE `laporan_pending`
  ADD CONSTRAINT `laporan_pending_ibfk_1` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id_tiket`),
  ADD CONSTRAINT `laporan_pending_ibfk_2` FOREIGN KEY (`id_teknisi`) REFERENCES `teknisi` (`id_teknisi`),
  ADD CONSTRAINT `laporan_pending_ibfk_3` FOREIGN KEY (`id_supervisor`) REFERENCES `supervisor` (`id_supervisor`);

--
-- Constraints for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD CONSTRAINT `fk_notif_teknisi` FOREIGN KEY (`id_teknisi`) REFERENCES `teknisi` (`id_teknisi`);

--
-- Constraints for table `penugasan`
--
ALTER TABLE `penugasan`
  ADD CONSTRAINT `penugasan_ibfk_1` FOREIGN KEY (`id_tiket`) REFERENCES `tiket` (`id_tiket`),
  ADD CONSTRAINT `penugasan_ibfk_2` FOREIGN KEY (`id_teknisi`) REFERENCES `teknisi` (`id_teknisi`);

--
-- Constraints for table `tiket`
--
ALTER TABLE `tiket`
  ADD CONSTRAINT `tiket_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`),
  ADD CONSTRAINT `tiket_ibfk_2` FOREIGN KEY (`id_lokasi`) REFERENCES `lokasi` (`id_lokasi`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
