-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2026 at 11:08 AM
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
-- Database: `ecl2`
--

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 2,
  `email` varchar(255) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tel` varchar(13) NOT NULL,
  `status` enum('Y','N') NOT NULL DEFAULT 'Y',
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `role_id`, `email`, `pass`, `name`, `tel`, `status`, `create_at`) VALUES
(1, 1, 'jj@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'jubzaaaaa', '00000000', 'Y', '2026-10-27 11:02:00'),
(2, 2, 'user1@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'user1', '11111111', 'Y', '2026-10-28 11:28:57'),
(3, 2, 'user2@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'user2', '2222222222', 'Y', '2026-10-28 12:10:40'),
(4, 1, 'admin@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'admin', '00000000', 'Y', '2026-10-28 12:11:09'),
(6, 2, 'user@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'user', '0000000000', 'Y', '2026-10-28 13:45:29'),
(7, 2, 'Nick@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'asdas', '123456', 'Y', '2026-10-30 08:33:03'),
(8, 2, 'pp@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'p elecwater', '0888', 'Y', '2026-10-30 09:59:00'),
(9, 2, 'sad@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'sdfsd', '12345', 'Y', '2026-10-30 10:38:11'),
(10, 2, 'Nick12@gmail.com', '12345678', 'dsfsd', 'dsfsd', 'Y', '2026-11-02 10:56:06'),
(11, 2, 'Nick1@gmail.com', '12345678', 'zdsfasd', '0888', 'Y', '2026-11-02 10:56:28'),
(12, 2, 'N@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'zdsfasd', '0888', 'Y', '2026-11-02 11:06:25'),
(13, 2, 'Niii@gmail.com', '25f9e794323b453885f5181f1b624d0b', 'zdsfasd', '0888', 'Y', '2026-11-06 12:56:57'),
(14, 2, 'tttty32yw@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'zdsfasd', '1233', 'Y', '2026-11-09 09:56:05'),
(15, 2, 'Nicksss@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'sirwat', '0623407238', 'Y', '2026-11-09 14:32:52'),
(16, 2, '2345@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'sirwat', '0623407238', 'Y', '2026-11-09 14:47:03'),
(17, 2, 'fushi@gmail.com', '25d55ad283aa400af464c76d713c07ad', 'sirwat', '0623407238', 'Y', '2026-11-10 17:37:07');

-- --------------------------------------------------------

--
-- Table structure for table `eco_point_ledger`
--

CREATE TABLE `eco_point_ledger` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `point` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eco_point_ledger`
--

INSERT INTO `eco_point_ledger` (`id`, `company_id`, `order_id`, `point`, `reason`, `create_at`) VALUES
(1, 2, NULL, 40, 'ลดการใช้ไฟฟ้า (บันทึก #1)', '2026-10-28 13:48:56'),
(2, 2, NULL, 4, 'ลดการใช้น้ำประปา (บันทึก #3)', '2026-10-28 13:49:41'),
(3, 3, NULL, 810, 'ลดการใช้ไฟฟ้า ประจำเดือน สิงหาคม 2569 (บันทึก #5)', '2026-10-28 15:04:47'),
(4, 3, NULL, 1195, 'ลดการใช้ไฟฟ้า ประจำเดือน กันยายน 2569 (บันทึก #6)', '2026-10-28 15:05:22'),
(5, 8, NULL, 240, 'ลดการใช้พลังงานประจำเดือน ตุลาคม 2569', '2026-10-30 10:04:49'),
(6, 8, NULL, 200, 'สำเร็จภารกิจ: ปลูกต้นไม้', '2026-10-30 10:10:47'),
(7, 7, NULL, 99, 'ลดการใช้พลังงานประจำเดือน ตุลาคม 2569', '2026-10-30 10:47:57'),
(8, 8, NULL, 101, 'สำเร็จภารกิจ: นิกก้อ', '2026-11-11 13:31:42'),
(9, 7, NULL, 101, 'สำเร็จภารกิจ: นิกก้อ', '2026-11-11 14:19:39');

-- --------------------------------------------------------

--
-- Table structure for table `eco_setting`
--

CREATE TABLE `eco_setting` (
  `setting_key` varchar(255) NOT NULL,
  `setting_value` decimal(10,4) NOT NULL,
  `label` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `eco_setting`
--

INSERT INTO `eco_setting` (`setting_key`, `setting_value`, `label`, `unit`) VALUES
('baseline_elec', 8.0000, '????????????????? (Baseline)', 'kWh/???'),
('baseline_water', 0.5000, '???????????????????? (Baseline)', '??.?./???'),
('factor_elec', 0.4999, 'Emission Factor ?????', 'kgCO2e/kWh'),
('factor_water', 0.7000, 'Emission Factor ????????', 'kgCO2e/??.?.'),
('point_per_kg', 10.0000, '???????????????? 1 kgCO2e', '????');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `pack_id` int(11) DEFAULT NULL,
  `status_mem` enum('Y','N') NOT NULL DEFAULT 'Y',
  `date_mem` date DEFAULT NULL,
  `end_mem` date DEFAULT NULL,
  `pay_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cert_status` enum('N','Y') DEFAULT NULL,
  `cert_date` date DEFAULT NULL,
  `cert_req` date DEFAULT NULL,
  `newpack_id` int(11) DEFAULT NULL,
  `newpay` int(11) DEFAULT NULL,
  `new_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `company_id`, `pack_id`, `status_mem`, `date_mem`, `end_mem`, `pay_price`, `cert_status`, `cert_date`, `cert_req`, `newpack_id`, `newpay`, `new_date`) VALUES
(1, 2, 1, 'Y', '2026-10-28', '2027-10-28', 0.00, 'Y', '2026-10-30', '2026-10-29', NULL, NULL, NULL),
(2, 3, 2, 'Y', '2026-10-28', '2027-10-28', 0.00, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 7, 1, 'Y', '2026-11-11', '2027-11-11', 6999.00, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 8, 2, 'Y', '2026-10-30', '2027-10-30', 3000.00, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 15, 1, 'Y', '2026-11-11', '2027-11-11', 6999.00, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 17, 2, 'Y', '2026-11-11', '2027-11-11', 0.00, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 7, 1, 'Y', '2026-11-11', '2027-11-11', 0.00, NULL, NULL, NULL, 2, 9999, '2026-11-11');

-- --------------------------------------------------------

--
-- Table structure for table `mission`
--

CREATE TABLE `mission` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `descrip` text DEFAULT NULL,
  `status` enum('draft','active','close') NOT NULL DEFAULT 'draft',
  `point_reward` int(11) NOT NULL,
  `co2e_save` decimal(10,2) NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mission`
--

INSERT INTO `mission` (`id`, `title`, `detail`, `descrip`, `status`, `point_reward`, `co2e_save`, `start_date`, `end_date`, `create_at`) VALUES
(1, 'นิกก้อ', 'ทำตัวตลกกว่าพี่นิก', 'เคยเห็นอันนี้มั้ย', 'active', 101, 0.00, '2026-10-28 00:00:00', '2026-10-31 00:00:00', '2026-10-28 12:18:35'),
(2, 'ปลูกต้นไม้', 'ปลูกต้นไม้บริเวณโรงงาน ช่วยลดโลกร้อน', '', 'active', 200, 1.00, '2026-10-28 00:00:00', '2026-10-30 00:00:00', '2026-10-28 12:19:33');

-- --------------------------------------------------------

--
-- Table structure for table `mission_parti`
--

CREATE TABLE `mission_parti` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `status` enum('join','pending','complete','reject') NOT NULL DEFAULT 'join',
  `evidence_url` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `submit_at` datetime DEFAULT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mission_parti`
--

INSERT INTO `mission_parti` (`id`, `mission_id`, `company_id`, `status`, `evidence_url`, `note`, `submit_at`, `create_at`) VALUES
(1, 2, 3, 'reject', 'ev_6ae1ae1dbff3f.png', '', '2026-10-28 15:11:09', '2026-10-28 15:10:54'),
(2, 1, 3, 'join', '', NULL, NULL, '2026-10-30 09:54:52'),
(3, 2, 8, 'complete', 'ev_6ae40a834bc38.png', '1231454', '2026-10-30 10:09:55', '2026-10-30 10:09:40'),
(4, 1, 8, 'complete', 'ev_6ae41063e9933.png', 'ผหกผ', '2026-10-30 10:34:59', '2026-10-30 10:11:00'),
(5, 1, 7, 'complete', 'ev_6af416943675b.png', 'ไม่มี', '2026-11-11 14:17:40', '2026-11-11 14:17:19');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `head` varchar(255) NOT NULL,
  `detail` text NOT NULL,
  `update_date` date DEFAULT NULL,
  `pic` varchar(255) NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `head`, `detail`, `update_date`, `pic`, `create_at`) VALUES
(1, 'หมีขั้วโลกไก่', 'มีหมีขั้วโลกแต่งชุดคอสเพลย์เป็นไก่ กะต๊ากๆ', '2026-10-28', '', '2026-10-28 12:20:38'),
(2, 'พี่นิกเล่าเรื่องผี', 'ตอนกลางคืนได้ยินเสียง วี๊ดๆๆ เหมือนเปรตน่ากลัวมากๆ ความจริงคือ รปภ. เป่านกหวีด', '2026-10-28', '', '2026-10-28 12:21:41'),
(12, 'ลดคาร์บอนเพื่อโลก', 'วันนี่รัฐมนตรีว่าการกระทรวงพลังงานแถลงการณ์ว่า โลกลดคาร์บอนได้มากถึง 3% ซึ่งมีผลตอบแทนโดยการให้เงินรางวัลจากการนำหลักฐานไปแลกแล้วรับเป็นเงินได้เลย', '0000-00-00', 'Logo1.png', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `noti`
--

CREATE TABLE `noti` (
  `id` int(11) NOT NULL,
  `recip_id` int(11) DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'general',
  `link` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `noti`
--

INSERT INTO `noti` (`id`, `recip_id`, `message`, `type`, `link`, `is_read`, `create_at`) VALUES
(1, 6, 'สั่งซื้อ น้ำปั่น จำนวน 1 ชิ้น สำเร็จ (เลขที่ ORD20261028582BB)', 'order', 'product.php?tab=orders', 1, '2026-10-28 13:46:32'),
(2, 6, 'คำสั่งซื้อ #ORD20261028582BB จัดส่งพัสดุเรียบร้อยแล้ว!', 'order', 'product.php?tab=orders', 1, '2026-10-28 13:48:04'),
(3, 3, 'สั่งซื้อ กล้องวงจรปิด จำนวน 1 ชิ้น สำเร็จ (เลขที่ ORD20261028E7855)', 'order', 'product.php?tab=orders', 1, '2026-10-28 15:11:26'),
(4, 6, 'คำสั่งซื้อ #ORD20261028582BB สำเร็จเรียบร้อย ขอบคุณที่สนับสนุนผลิตภัณฑ์รักษ์โลก', 'order', 'product.php?tab=orders', 0, '2026-10-30 08:38:35'),
(5, 3, 'คำสั่งซื้อ #ORD20261028E7855 สำเร็จเรียบร้อย ขอบคุณที่สนับสนุนผลิตภัณฑ์รักษ์โลก', 'order', 'product.php?tab=orders', 1, '2026-10-30 08:38:39'),
(6, 1, '📦 asdas ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 08:40:43'),
(7, 4, '📦 asdas ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 08:40:43'),
(9, 1, '📦 asdas ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 08:40:57'),
(10, 4, '📦 asdas ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 08:40:57'),
(12, 7, '❌ หลักฐานค่าไฟ ประจำเดือน ตุลาคม 2569 ไม่ผ่านการตรวจสอบ เหตุผล: ปลอม', 'utility', 'utility.php', 1, '2026-10-30 09:38:07'),
(13, 7, '❌ หลักฐานค่าน้ำ ประจำเดือน ตุลาคม 2569 ไม่ผ่านการตรวจสอบ เหตุผล: ปลอม', 'utility', 'utility.php', 1, '2026-10-30 09:38:13'),
(14, 3, 'หลักฐานภารกิจ \'ปลูกต้นไม้\' ไม่ผ่านการอนุมัติ เหตุผล: ปลอม กรุณาตรวจสอบและส่งใหม่', 'mission', 'mission.php', 1, '2026-10-30 09:53:30'),
(15, 8, 'สั่งซื้อ น้ำปั่น จำนวน 1 ชิ้น สำเร็จ (เลขที่ ORD202610303A853)', 'order', 'product.php?tab=orders', 1, '2026-10-30 10:00:13'),
(16, 1, '📦 p elecwater ขอสมัคร มาตรฐาน (ชำระ 6,999 บาท)', 'general', 'admin/packages.php', 1, '2026-10-30 10:01:15'),
(17, 4, '📦 p elecwater ขอสมัคร มาตรฐาน (ชำระ 6,999 บาท)', 'general', 'admin/packages.php', 0, '2026-10-30 10:01:15'),
(19, 2, '✅ หลักฐานค่าไฟ ประจำเดือน ตุลาคม 2569 ได้รับการอนุมัติแล้ว', 'utility', 'utility.php', 1, '2026-10-30 10:05:40'),
(20, 2, '✅ หลักฐานค่าไฟ ประจำเดือน ตุลาคม 2569 ได้รับการอนุมัติแล้ว', 'utility', 'utility.php', 1, '2026-10-30 10:05:42'),
(21, 8, '✅ หลักฐานค่าไฟ ประจำเดือน ตุลาคม 2569 ได้รับการอนุมัติแล้ว', 'utility', 'utility.php', 1, '2026-10-30 10:06:09'),
(22, 8, '❌ หลักฐานค่าน้ำ ประจำเดือน ตุลาคม 2569 ไม่ผ่านการตรวจสอบ เหตุผล: ปลอม', 'utility', 'utility.php', 1, '2026-10-30 10:06:30'),
(23, 1, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:07:18'),
(24, 4, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:07:18'),
(26, 8, 'ยินดีด้วย! ภารกิจ \'ปลูกต้นไม้\' ได้รับการอนุมัติแล้ว (+200 แต้ม)', 'mission', 'mission.php', 1, '2026-10-30 10:10:47'),
(27, 8, 'หลักฐานภารกิจ \'นิกก้อ\' ไม่ผ่านการอนุมัติ เหตุผล: ปลอม กรุณาตรวจสอบและส่งใหม่', 'mission', 'mission.php', 1, '2026-10-30 10:11:16'),
(28, 1, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:12:57'),
(29, 4, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:12:57'),
(31, 1, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:18:16'),
(32, 4, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:18:16'),
(34, 1, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:18:58'),
(35, 4, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:18:58'),
(37, 1, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:19:50'),
(38, 4, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:19:50'),
(40, 1, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:22:38'),
(41, 4, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:22:38'),
(43, 1, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:22:53'),
(44, 4, '📦 p elecwater ขอเปลี่ยนจาก พรีเมี่ยม เป็น มาตรฐาน (ชำระ 0 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:22:53'),
(46, 1, '🛍️ p elecwater สั่งซื้อสินค้าใหม่ เลขที่ ', 'general', 'admin/products.php', 1, '2026-10-30 10:27:17'),
(47, 4, '🛍️ p elecwater สั่งซื้อสินค้าใหม่ เลขที่ ', 'general', 'admin/products.php', 0, '2026-10-30 10:27:17'),
(49, 8, 'สั่งซื้อ กล้องวงจรปิด จำนวน 1 ชิ้น สำเร็จ (เลขที่ ORD20261030EC20F)', 'order', 'product.php?tab=orders', 1, '2026-10-30 10:29:36'),
(50, 1, '🛍️ p elecwater สั่งซื้อสินค้าใหม่ เลขที่ ', 'general', 'admin/products.php', 1, '2026-10-30 10:29:36'),
(51, 4, '🛍️ p elecwater สั่งซื้อสินค้าใหม่ เลขที่ ', 'general', 'admin/products.php', 0, '2026-10-30 10:29:36'),
(53, 1, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:34:29'),
(54, 4, '📦 p elecwater ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:34:29'),
(56, 1, '📋 p elecwater ส่งหลักฐานภารกิจ รอตรวจสอบ', 'general', 'admin/mission_review.php', 1, '2026-10-30 10:34:59'),
(57, 4, '📋 p elecwater ส่งหลักฐานภารกิจ รอตรวจสอบ', 'general', 'admin/mission_review.php', 0, '2026-10-30 10:34:59'),
(59, 1, '📦 asdas ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 1, '2026-10-30 10:40:54'),
(60, 4, '📦 asdas ขอเปลี่ยนจาก มาตรฐาน เป็น พรีเมี่ยม (ชำระ 3,000 บาท หลังหักส่วนลด)', 'general', 'admin/packages.php', 0, '2026-10-30 10:40:54'),
(62, 7, '❌ หลักฐานค่าไฟ ประจำเดือน ตุลาคม 2569 ไม่ผ่านการตรวจสอบ เหตุผล: ปลอม', 'utility', 'utility.php', 1, '2026-10-30 12:10:02'),
(63, 7, '❌ หลักฐานค่าน้ำ ประจำเดือน ตุลาคม 2569 ไม่ผ่านการตรวจสอบ เหตุผล: ปลอม', 'utility', 'utility.php', 1, '2026-10-30 12:10:08'),
(64, 2, '📜 ใบรับรองของคุณถูกส่งไปทางอีเมลแล้ว กรุณาตรวจสอบกล่องจดหมาย Gmail', 'general', 'index.php', 1, '2026-10-30 12:12:13'),
(65, 8, 'คำสั่งซื้อ #ORD20261030EC20F จัดส่งพัสดุเรียบร้อยแล้ว!', 'order', 'product.php?tab=orders', 0, '2026-11-09 20:02:57'),
(66, 8, 'คำสั่งซื้อ #ORD20261030EC20F สำเร็จเรียบร้อย ขอบคุณที่สนับสนุนผลิตภัณฑ์รักษ์โลก', 'order', 'product.php?tab=orders', 0, '2026-11-09 20:15:25'),
(67, 3, 'คำสั่งซื้อ #ORD20261028E7855 สำเร็จเรียบร้อย ขอบคุณที่สนับสนุนผลิตภัณฑ์รักษ์โลก', 'order', 'product.php?tab=orders', 0, '2026-11-09 20:15:31'),
(68, 3, 'คำสั่งซื้อ #ORD20261028E7855 สำเร็จเรียบร้อย ขอบคุณที่สนับสนุนผลิตภัณฑ์รักษ์โลก', 'order', 'product.php?tab=orders', 0, '2026-11-09 20:15:31'),
(69, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น รอดำเนินการ', 'order', 'product.php', 0, '2026-11-11 12:18:23'),
(70, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น ชำระเงินแล้ว', 'order', 'product.php', 0, '2026-11-11 12:18:27'),
(71, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น จัดส่งแล้ว', 'order', 'product.php', 0, '2026-11-11 12:18:29'),
(72, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น สำเร็จ', 'order', 'product.php', 0, '2026-11-11 12:18:31'),
(73, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น ยกเลิก', 'order', 'product.php', 0, '2026-11-11 12:18:36'),
(74, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น สำเร็จ', 'order', 'product.php', 0, '2026-11-11 12:18:58'),
(75, 8, 'ยินดีด้วย! ภารกิจ \'นิกก้อ\' ได้รับการอนุมัติแล้ว (+101 แต้ม)', 'mission', 'mission.php', 0, '2026-11-11 13:31:42'),
(76, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น จัดส่งแล้ว', 'order', 'product.php', 0, '2026-11-11 13:33:28'),
(77, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น สำเร็จ', 'order', 'product.php', 0, '2026-11-11 13:33:31'),
(78, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น ยกเลิก', 'order', 'product.php', 0, '2026-11-11 13:33:34'),
(79, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น ชำระเงินแล้ว', 'order', 'product.php', 0, '2026-11-11 13:33:36'),
(80, 8, 'คำสั่งซื้อ ORD20261030EC20F เปลี่ยนสถานะเป็น รอดำเนินการ', 'order', 'product.php', 0, '2026-11-11 13:33:38'),
(81, 7, 'ยินดีด้วย! ภารกิจ \'นิกก้อ\' ได้รับการอนุมัติแล้ว (+101 แต้ม)', 'mission', 'mission.php', 1, '2026-11-11 14:19:39'),
(82, 1, 'asdas (Nick@gmail.com) ขอรีเซ็ตรหัสผ่าน', 'general', 'admin/user.php?find=Nick%40gmail.com', 1, '2026-11-11 14:24:23'),
(83, 4, 'asdas (Nick@gmail.com) ขอรีเซ็ตรหัสผ่าน', 'general', 'admin/user.php?find=Nick%40gmail.com', 0, '2026-11-11 14:24:23'),
(84, 1, 'องค์กร \'asdas\' ขอเปลี่ยนเป็นแพ็กเกจ พรีเมี่ยม', 'pack', 'admin/pack_review.php', 0, '2026-11-11 14:33:13'),
(85, 4, 'องค์กร \'asdas\' ขอเปลี่ยนเป็นแพ็กเกจ พรีเมี่ยม', 'pack', 'admin/pack_review.php', 0, '2026-11-11 14:33:13');

-- --------------------------------------------------------

--
-- Table structure for table `pack`
--

CREATE TABLE `pack` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` int(11) NOT NULL,
  `detail` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pack`
--

INSERT INTO `pack` (`id`, `name`, `price`, `detail`) VALUES
(1, 'มาตรฐาน', 6999, 'ระบบบันทึกค่าน้ำค่าไฟ ระบบภารกิจสะสมเหรียญแลกแต้มส่วนลดราคาสินค้า'),
(2, 'พรีเมี่ยม', 9999, 'ระบบบันทึกค่าน้ำค่าไฟ ระบบภารกิจสะสมเหรียญแลกแต้มส่วนลดราคาสินค้า รายงานการบันทึกค่าน้ำค่าไฟ ขออกใบเกียรติบัตรการช่วยลดคาร์บอน');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `id_pro` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `detail` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `point_disc` int(11) NOT NULL DEFAULT 0,
  `num` int(11) NOT NULL DEFAULT 10,
  `pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`id_pro`, `name`, `shop_name`, `detail`, `price`, `point_disc`, `num`, `pic`, `status`, `create_at`) VALUES
(1, 'กล้องวงจรปิด', 'ร้านสมาร์ทอีโค่', 'กล้องวงจรปิด หมุนได้ 180 องศา', 1290.00, 290, 9, 'prod_1793175220_106.png', 'active', '2026-10-28 12:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_order`
--

CREATE TABLE `product_order` (
  `id` int(11) NOT NULL,
  `order_no` varchar(50) NOT NULL,
  `company_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `product_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `point_use` int(11) NOT NULL,
  `disc_amount` decimal(10,2) NOT NULL,
  `final_price` decimal(10,2) NOT NULL,
  `buy_name` varchar(255) NOT NULL,
  `buy_tel` varchar(255) NOT NULL,
  `ship_addr` text NOT NULL,
  `pay_method` varchar(255) NOT NULL,
  `status` enum('pending','paid','ship','complete','cancel') NOT NULL DEFAULT 'pending',
  `create_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_order`
--

INSERT INTO `product_order` (`id`, `order_no`, `company_id`, `product_id`, `qty`, `product_price`, `total_price`, `point_use`, `disc_amount`, `final_price`, `buy_name`, `buy_tel`, `ship_addr`, `pay_method`, `status`, `create_at`) VALUES
(2, 'ORD20261028E7855', 3, 1, 1, 1290.00, 1290.00, 0, 0.00, 1290.00, 'user2', '2222222222', 'sdahfsdf', 'transfer', 'complete', '2026-10-28 15:11:26'),
(4, 'ORD20261030EC20F', 8, 1, 1, 1290.00, 1290.00, 0, 0.00, 1290.00, 'p elecwater', '0888', 'zx', 'cod', 'pending', '2026-10-30 10:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role`) VALUES
(1, 'แอดมิน'),
(2, 'ผู้ใช้');

-- --------------------------------------------------------

--
-- Table structure for table `utility_logs`
--

CREATE TABLE `utility_logs` (
  `id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  `type` enum('elec','water') NOT NULL,
  `meter_start` decimal(10,2) DEFAULT 0.00,
  `meter_read` decimal(10,2) DEFAULT NULL,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `prev_amount` decimal(10,2) DEFAULT 0.00,
  `amount` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `day_elapse` int(11) DEFAULT NULL,
  `save_unit` decimal(10,2) NOT NULL,
  `co2e_save` decimal(10,2) NOT NULL,
  `evidence_url` varchar(255) NOT NULL,
  `note` text DEFAULT NULL,
  `record_date` datetime NOT NULL,
  `create_at` datetime NOT NULL DEFAULT current_timestamp(),
  `evidence_prev_url` varchar(255) DEFAULT NULL,
  `review_status` char(1) NOT NULL DEFAULT 'N',
  `review_note` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utility_logs`
--

INSERT INTO `utility_logs` (`id`, `company_id`, `type`, `meter_start`, `meter_read`, `unit_price`, `prev_amount`, `amount`, `cost`, `day_elapse`, `save_unit`, `co2e_save`, `evidence_url`, `note`, `record_date`, `create_at`, `evidence_prev_url`, `review_status`, `review_note`) VALUES
(1, 2, 'elec', 0.00, 15.00, 0.00, 0.00, 0.00, 0.00, 1, 8.00, 4.00, '', '', '2026-10-27 00:00:00', '2026-10-28 13:48:56', NULL, 'Y', ''),
(2, 2, 'elec', 0.00, 35.00, 0.00, 0.00, 20.00, 0.00, 1, 0.00, 0.00, '', '', '2026-10-28 00:00:00', '2026-10-28 13:49:24', NULL, 'Y', ''),
(4, 3, 'elec', 0.00, 123.00, 0.00, 0.00, 0.00, 0.00, 31, 0.00, 0.00, '', '', '2026-07-01 00:00:00', '2026-10-28 15:04:29', NULL, 'N', NULL),
(5, 3, 'elec', 0.00, 201.00, 0.00, 0.00, 78.00, 0.00, 31, 162.00, 80.98, '', '', '2026-08-01 00:00:00', '2026-10-28 15:04:47', NULL, 'N', NULL),
(6, 3, 'elec', 0.00, 202.00, 0.00, 0.00, 1.00, 0.00, 30, 239.00, 119.48, '', '', '2026-09-01 00:00:00', '2026-10-28 15:05:22', NULL, 'N', NULL),
(7, 3, 'water', 0.00, 154.00, 0.00, 0.00, 0.00, 0.00, 31, 0.00, 0.00, '', '', '2026-07-01 00:00:00', '2026-10-28 15:08:50', NULL, 'N', NULL),
(9, 3, 'water', 0.00, 199.00, 0.00, 0.00, 45.00, 0.00, 31, 0.00, 0.00, '', '', '2026-08-01 00:00:00', '2026-10-28 15:09:24', NULL, 'N', NULL),
(12, 8, 'elec', 120.00, 120.00, 4.50, 20.00, 0.00, 0.00, NULL, 90.00, 10.00, '', '', '2026-10-01 00:00:00', '2026-10-30 10:04:49', '', 'Y', ''),
(13, 8, 'water', 120.00, 120.00, 18.00, 20.00, 0.00, 0.00, NULL, 360.00, 14.00, '', '', '2026-10-01 00:00:00', '2026-10-30 10:04:49', '', 'R', 'ปลอม'),
(14, 7, 'elec', 12.00, 21.00, 4.50, 12.00, 9.00, 40.50, NULL, 13.50, 1.50, '', '', '2026-10-01 00:00:00', '2026-10-30 10:47:57', '', 'R', 'ปลอม'),
(15, 7, 'water', 12.00, 12.00, 18.00, 12.00, 0.00, 0.00, NULL, 216.00, 8.40, '', '', '2026-10-01 00:00:00', '2026-10-30 10:47:57', '', 'R', 'ปลอม');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `eco_point_ledger`
--
ALTER TABLE `eco_point_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `eco_setting`
--
ALTER TABLE `eco_setting`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pack_id` (`pack_id`),
  ADD KEY `fk_members_company` (`company_id`);

--
-- Indexes for table `mission`
--
ALTER TABLE `mission`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mission_parti`
--
ALTER TABLE `mission_parti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `fk_mission_parti` (`company_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `noti`
--
ALTER TABLE `noti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipeint_id` (`recip_id`);

--
-- Indexes for table `pack`
--
ALTER TABLE `pack`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`id_pro`);

--
-- Indexes for table `product_order`
--
ALTER TABLE `product_order`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_product_order` (`company_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utility_logs`
--
ALTER TABLE `utility_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_utility_logs` (`company_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `eco_point_ledger`
--
ALTER TABLE `eco_point_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `mission`
--
ALTER TABLE `mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mission_parti`
--
ALTER TABLE `mission_parti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `noti`
--
ALTER TABLE `noti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `pack`
--
ALTER TABLE `pack`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `id_pro` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `product_order`
--
ALTER TABLE `product_order`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `utility_logs`
--
ALTER TABLE `utility_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `company`
--
ALTER TABLE `company`
  ADD CONSTRAINT `role_id` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `eco_point_ledger`
--
ALTER TABLE `eco_point_ledger`
  ADD CONSTRAINT `company_id` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `order_id` FOREIGN KEY (`order_id`) REFERENCES `product_order` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_company` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `pack_id` FOREIGN KEY (`pack_id`) REFERENCES `pack` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mission_parti`
--
ALTER TABLE `mission_parti`
  ADD CONSTRAINT `fk_mission_parti` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `mission_id` FOREIGN KEY (`mission_id`) REFERENCES `mission` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `noti`
--
ALTER TABLE `noti`
  ADD CONSTRAINT `recipeint_id` FOREIGN KEY (`recip_id`) REFERENCES `company` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `product_order`
--
ALTER TABLE `product_order`
  ADD CONSTRAINT `fk_product_order` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `product_id` FOREIGN KEY (`product_id`) REFERENCES `product` (`id_pro`) ON DELETE CASCADE;

--
-- Constraints for table `utility_logs`
--
ALTER TABLE `utility_logs`
  ADD CONSTRAINT `fk_utility_logs` FOREIGN KEY (`company_id`) REFERENCES `company` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
