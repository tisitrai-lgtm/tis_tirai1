-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 09:23 AM
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
-- Database: `ktis_smart_field`
--

-- --------------------------------------------------------

--
-- Table structure for table `check_items_cut`
--

CREATE TABLE `check_items_cut` (
  `item_id` int(11) NOT NULL,
  `item_name_cut` varchar(150) NOT NULL,
  `section_no` tinyint(2) NOT NULL DEFAULT 1,
  `section_label` varchar(150) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_items_cut`
--

INSERT INTO `check_items_cut` (`item_id`, `item_name_cut`, `section_no`, `section_label`) VALUES
(1, 'ใบมีดตัดยอดครบและมีความคม', 1, '1. ระบบตัดยอด'),
(2, 'การหมุนของตัดยอด', 1, '1. ระบบตัดยอด'),
(3, 'เล็บทั้ง 2 ข้าง', 2, '2. เกลียวแบ่งอ้อย / ทุ่น / เล็บ'),
(4, 'การหมุนของเกลียวแบ่งอ้อย', 2, '2. เกลียวแบ่งอ้อย / ทุ่น / เล็บ'),
(5, 'ไม่มีวัชพืชพันเกลียวแบ่งอ้อย', 2, '2. เกลียวแบ่งอ้อย / ทุ่น / เล็บ'),
(6, 'ทุ่นทั้ง 2 ข้างไม่แตกร้าว', 2, '2. เกลียวแบ่งอ้อย / ทุ่น / เล็บ'),
(7, 'ใบมีดตัดโคน 10 ใบ ครบ', 3, '3. ชุดตัดโคน'),
(8, 'การหมุนของชุดตัดโคน', 3, '3. ชุดตัดโคน'),
(9, 'ใบมีดตัดโคนคม (ตัดแล้วตอไม่แตก)', 3, '3. ชุดตัดโคน'),
(10, 'ไม่มีวัชพืชพันชุดตัดโคน', 3, '3. ชุดตัดโคน'),
(11, 'โรลเลอร์หมุนปกติทุกชุด', 4, '4. ชุดโรลเลอร์ต่างๆ'),
(12, 'ไม่มีวัชพืชพันชุดโรลเลอร์', 4, '4. ชุดโรลเลอร์ต่างๆ'),
(13, 'ใบมีดสับท่อนครบและคม', 5, '5. ชุดสับท่อน / ล้อช่วยแรง'),
(14, 'การสับท่อนอ้อยไม่มีการแตก', 5, '5. ชุดสับท่อน / ล้อช่วยแรง'),
(15, 'พัดลมทำความสะอาดหมุนปกติและมีลมแรง', 6, '6. พัดลมทำความสะอาด'),
(16, 'ไม่มีวัชพืชและดินเกาะใบพัดลม', 6, '6. พัดลมทำความสะอาด'),
(17, 'ใบพัดลมเล็กหมุนปกติและมีลมแรง', 7, '8. พัดลมเล็ก'),
(18, 'ความสะอาดของใบพัดลมเล็ก', 7, '8. พัดลมเล็ก'),
(19, 'ความสะอาดตัวรถทั่วไป', 8, '9. ความสะอาดตัวรถ');

-- --------------------------------------------------------

--
-- Table structure for table `check_items_field`
--

CREATE TABLE `check_items_field` (
  `item_id` int(11) NOT NULL,
  `item_name_field` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_items_field`
--

INSERT INTO `check_items_field` (`item_id`, `item_name_field`) VALUES
(1, 'ปกติ'),
(2, 'อ้อยล้ม'),
(3, 'หญ้ารก'),
(4, 'ร่องลึก/ไม่พวนพูนโคน'),
(5, 'แปลงเคยถูกน้ำท่วม'),
(6, 'อ้อยไฟไหม้'),
(7, 'น้ำแช่ขัง'),
(8, 'อื่นๆ');

-- --------------------------------------------------------

--
-- Table structure for table `check_results`
--

CREATE TABLE `check_results` (
  `result_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `pass` tinyint(1) NOT NULL,
  `note` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `fail_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_results`
--

INSERT INTO `check_results` (`result_id`, `session_id`, `item_id`, `pass`, `note`, `image_path`, `fail_image`) VALUES
(35, 5, 1, 1, NULL, NULL, NULL),
(36, 5, 2, 1, NULL, NULL, NULL),
(37, 5, 3, 1, NULL, NULL, NULL),
(38, 5, 4, 1, NULL, NULL, NULL),
(39, 5, 5, 1, NULL, NULL, NULL),
(40, 5, 6, 1, NULL, NULL, NULL),
(41, 5, 7, 1, NULL, NULL, NULL),
(42, 5, 8, 1, NULL, NULL, NULL),
(43, 5, 9, 1, NULL, NULL, NULL),
(44, 5, 10, 1, NULL, NULL, NULL),
(45, 5, 11, 1, NULL, NULL, NULL),
(46, 5, 12, 1, NULL, NULL, NULL),
(47, 5, 13, 1, NULL, NULL, NULL),
(48, 5, 14, 1, NULL, NULL, NULL),
(49, 5, 15, 1, NULL, NULL, NULL),
(50, 5, 16, 1, NULL, NULL, NULL),
(51, 5, 17, 1, NULL, NULL, NULL),
(52, 5, 18, 1, NULL, NULL, NULL),
(53, 5, 19, 1, NULL, NULL, NULL),
(54, 6, 1, 1, NULL, NULL, NULL),
(55, 6, 2, 1, NULL, NULL, NULL),
(56, 6, 3, 1, NULL, NULL, NULL),
(57, 6, 4, 1, NULL, NULL, NULL),
(58, 6, 5, 1, NULL, NULL, NULL),
(59, 6, 6, 1, NULL, NULL, NULL),
(60, 6, 7, 1, NULL, NULL, NULL),
(61, 6, 8, 1, NULL, NULL, NULL),
(62, 6, 9, 1, NULL, NULL, NULL),
(63, 6, 10, 1, NULL, NULL, NULL),
(64, 6, 11, 1, NULL, NULL, NULL),
(65, 6, 12, 1, NULL, NULL, NULL),
(66, 6, 13, 1, NULL, NULL, NULL),
(67, 6, 14, 1, NULL, NULL, NULL),
(68, 6, 15, 1, NULL, NULL, NULL),
(69, 6, 16, 1, NULL, NULL, NULL),
(70, 6, 17, 1, NULL, NULL, NULL),
(71, 6, 18, 1, NULL, NULL, NULL),
(72, 6, 19, 1, NULL, NULL, NULL),
(73, 7, 1, 0, '545415454', NULL, NULL),
(74, 7, 2, 0, '+6656555', NULL, NULL),
(75, 7, 3, 1, NULL, NULL, NULL),
(76, 7, 4, 1, NULL, NULL, NULL),
(77, 7, 5, 1, NULL, NULL, NULL),
(78, 7, 6, 0, '92551561561', NULL, NULL),
(79, 7, 7, 1, NULL, NULL, NULL),
(80, 7, 8, 1, NULL, NULL, NULL),
(81, 7, 9, 1, NULL, NULL, NULL),
(82, 7, 10, 1, NULL, NULL, NULL),
(83, 7, 11, 1, NULL, NULL, NULL),
(84, 7, 12, 1, NULL, NULL, NULL),
(85, 7, 13, 1, NULL, NULL, NULL),
(86, 7, 14, 1, NULL, NULL, NULL),
(87, 7, 15, 1, NULL, NULL, NULL),
(88, 7, 16, 1, NULL, NULL, NULL),
(89, 7, 17, 0, '555', NULL, NULL),
(90, 7, 18, 1, NULL, NULL, NULL),
(91, 7, 19, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `check_sessions`
--

CREATE TABLE `check_sessions` (
  `session_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `harvester_number` varchar(30) NOT NULL,
  `working_hour` decimal(10,2) DEFAULT NULL,
  `crop_year` varchar(10) NOT NULL,
  `overall_pass` tinyint(1) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `field_condition` varchar(100) DEFAULT NULL COMMENT 'สภาพแปลง',
  `field_condition_etc` varchar(255) DEFAULT NULL COMMENT 'อื่นๆ กรณีเลือกอื่นๆ',
  `img_harvester` varchar(255) DEFAULT NULL COMMENT 'รูปรถตัด',
  `img_field` varchar(255) DEFAULT NULL COMMENT 'รูปแปลงอ้อย',
  `img_user` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_sessions`
--

INSERT INTO `check_sessions` (`session_id`, `emp_id`, `harvester_number`, `working_hour`, `crop_year`, `overall_pass`, `remark`, `checked_at`, `field_condition`, `field_condition_etc`, `img_harvester`, `img_field`, `img_user`) VALUES
(5, 'TIS-111', '17', NULL, '69/70', NULL, NULL, '2026-06-19 07:23:23', 'อ้อยล้ม', NULL, 'im_user_check/2026-06-25/1782379977_2440.jpg', NULL, NULL),
(6, 'TIS-111', '18', NULL, '69/70', NULL, NULL, '2026-06-20 03:30:22', 'อ้อยไฟไหม้', NULL, 'im_user_check/2026-06-20/1781926222_2000.jpg', 'im_user_check/2026-06-20/1781926222_5006.jpg', NULL),
(7, 'TIS-111', '18', NULL, '69/70', NULL, NULL, '2026-06-23 01:56:15', 'อ้อยล้ม', NULL, 'im_user_check/2026-06-23/1782179774_4349.jpg', 'im_user_check/2026-06-23/1782179775_7047.jpg', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `ID` int(10) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `emp_pass` varchar(255) DEFAULT NULL,
  `emp_name` varchar(50) NOT NULL,
  `emp_unit` varchar(50) NOT NULL,
  `emp_level` varchar(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`ID`, `emp_id`, `emp_pass`, `emp_name`, `emp_unit`, `emp_level`) VALUES
(1, 'admin01', '$2y$10$gPNxiSOw2OdIydgWVFIuJ.wAhZbm/yiGebC54iarBlLARG3eIuSme', 'ผู้จัดการ ออฟฟิศกลาง', 'ประจำออฟฟิตกลาง', 'a'),
(5, 'TIS-111', '$2y$10$0RpaCzmHYe7OoferVkB9s.GqS0VQ9JcsPBQK/BxsgaBL2ebBMlGem', 'นายพัชกร จันทนะโพธิ', '111 บางขลัง', 'u'),
(6, 'TIS-131', '$2y$10$9NXd4VDroO5dCpH22ltbtOcUdQ4Nz8gmdVG/0fBKmULFCkAyHTQiC', 'นายไพโรจ์', '131 ศรีนครเหนือ', 'u');

-- --------------------------------------------------------

--
-- Table structure for table `employee_harvester`
--

CREATE TABLE `employee_harvester` (
  `id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `harvester_id` int(11) NOT NULL,
  `assigned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `harvesters`
--

CREATE TABLE `harvesters` (
  `harvester_id` int(11) NOT NULL,
  `harvester_number` varchar(50) NOT NULL COMMENT 'เบอร์รถตัด เช่น รถตัดเบอร์ 1',
  `harvester_name` varchar(100) DEFAULT NULL COMMENT 'ชื่อเพิ่มเติม (ถ้ามี)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=ใช้งาน, 0=ปลดระวาง',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `harvesters`
--

INSERT INTO `harvesters` (`harvester_id`, `harvester_number`, `harvester_name`, `is_active`, `created_at`) VALUES
(1, 'รถตัดเบอร์ 1', NULL, 1, '2026-07-07 13:35:44'),
(2, 'รถตัดเบอร์ 2', NULL, 1, '2026-07-07 13:35:44'),
(3, 'รถตัดเบอร์ 3', NULL, 1, '2026-07-07 13:35:44'),
(4, 'รถตัดเบอร์ 4', NULL, 1, '2026-07-07 13:35:44'),
(5, 'รถตัดเบอร์ 5', NULL, 1, '2026-07-07 13:35:44'),
(6, 'รถตัดเบอร์ 6', NULL, 1, '2026-07-07 13:35:44'),
(7, 'รถตัดเบอร์ 7', NULL, 1, '2026-07-07 13:35:44'),
(8, 'รถตัดเบอร์ 8', NULL, 1, '2026-07-07 13:35:44'),
(9, 'รถตัดเบอร์ 9', NULL, 1, '2026-07-07 13:35:44'),
(10, 'รถตัดเบอร์ 10', NULL, 1, '2026-07-07 13:35:44'),
(11, 'รถตัดเบอร์ 11', NULL, 1, '2026-07-07 13:35:44'),
(12, 'รถตัดเบอร์ 12', NULL, 1, '2026-07-07 13:35:44'),
(13, 'รถตัดเบอร์ 13', NULL, 1, '2026-07-07 13:35:44'),
(14, 'รถตัดเบอร์ 14', NULL, 1, '2026-07-07 13:35:44'),
(15, 'รถตัดเบอร์ 15', NULL, 1, '2026-07-07 13:35:44'),
(16, 'รถตัดเบอร์ 16', NULL, 1, '2026-07-07 13:35:44'),
(17, 'รถตัดเบอร์ 17', NULL, 1, '2026-07-07 13:35:44'),
(18, 'รถตัดเบอร์ 18', NULL, 1, '2026-07-07 13:35:44'),
(19, 'รถตัดเบอร์ 19', NULL, 1, '2026-07-07 13:35:44'),
(20, 'รถตัดเบอร์ 20', NULL, 1, '2026-07-07 13:35:44'),
(21, 'รถตัดเบอร์ 21', NULL, 1, '2026-07-07 13:35:44'),
(22, 'รถตัดเบอร์ 22', NULL, 1, '2026-07-07 13:35:44'),
(23, 'รถตัดเบอร์ 23', NULL, 1, '2026-07-07 13:35:44'),
(24, 'รถตัดเบอร์ 24', NULL, 1, '2026-07-07 13:35:44'),
(25, 'รถตัดเบอร์ 25', NULL, 1, '2026-07-07 13:35:44'),
(26, 'รถตัดเบอร์ 26', NULL, 1, '2026-07-07 13:35:44'),
(27, 'รถตัดเบอร์ 27', NULL, 1, '2026-07-07 13:35:44'),
(28, 'รถตัดเบอร์ 28', NULL, 1, '2026-07-07 13:35:44'),
(29, 'รถตัดเบอร์ 29', NULL, 1, '2026-07-07 13:35:44'),
(30, 'รถตัดเบอร์ 30', NULL, 1, '2026-07-07 13:35:44'),
(31, 'รถตัดเบอร์ 31', NULL, 1, '2026-07-07 13:35:44'),
(32, 'รถตัดเบอร์ 32', NULL, 1, '2026-07-07 13:35:44'),
(33, 'รถตัดเบอร์ 33', NULL, 1, '2026-07-07 13:35:44'),
(34, 'รถตัดเบอร์ 34', NULL, 1, '2026-07-07 13:35:44'),
(35, 'รถตัดเบอร์ 35', NULL, 1, '2026-07-07 13:35:44'),
(36, 'รถตัดเบอร์ 36', NULL, 1, '2026-07-07 13:35:44'),
(37, 'รถตัดเบอร์ 37', NULL, 1, '2026-07-07 13:35:44'),
(38, 'รถตัดเบอร์ 38', NULL, 1, '2026-07-07 13:35:44'),
(39, 'รถตัดเบอร์ 39', NULL, 1, '2026-07-07 13:35:44'),
(40, 'รถตัดเบอร์ 40', NULL, 1, '2026-07-07 13:35:44'),
(41, 'รถตัดเบอร์ 41', NULL, 1, '2026-07-07 13:35:44'),
(42, 'รถตัดเบอร์ 42', NULL, 1, '2026-07-07 13:35:44'),
(43, 'รถตัดเบอร์ 43', NULL, 1, '2026-07-07 13:35:44'),
(44, 'รถตัดเบอร์ 44', NULL, 1, '2026-07-07 13:35:44'),
(45, 'รถตัดเบอร์ 45', NULL, 1, '2026-07-07 13:35:44'),
(46, 'รถตัดเบอร์ 46', NULL, 1, '2026-07-07 13:35:44'),
(47, 'รถตัดเบอร์ 47', NULL, 1, '2026-07-07 13:35:44'),
(48, 'รถตัดเบอร์ 48', NULL, 1, '2026-07-07 13:35:44'),
(49, 'รถตัดเบอร์ 49', NULL, 1, '2026-07-07 13:35:44'),
(50, 'รถตัดเบอร์ 50', NULL, 1, '2026-07-07 13:35:44');

-- --------------------------------------------------------

--
-- Table structure for table `harvester_checks`
--

CREATE TABLE `harvester_checks` (
  `check_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `harvester_number` varchar(30) NOT NULL,
  `check_blade` tinyint(1) NOT NULL,
  `check_top_cutter` tinyint(1) NOT NULL,
  `check_chopper` tinyint(1) NOT NULL,
  `check_base_cutter` tinyint(1) NOT NULL,
  `check_extractor` tinyint(1) NOT NULL,
  `crop_year` varchar(10) NOT NULL,
  `check_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `noti_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `target_unit` varchar(100) DEFAULT NULL,
  `noti_text` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `target_unit` varchar(50) NOT NULL,
  `truck_number` varchar(30) NOT NULL,
  `post_text` text NOT NULL,
  `problem_detail` text NOT NULL,
  `problem_detail_2` varchar(150) DEFAULT NULL,
  `problem_detail_3` varchar(150) DEFAULT NULL,
  `post_image` varchar(255) NOT NULL,
  `post_image_2` varchar(255) DEFAULT NULL,
  `post_image_3` varchar(255) DEFAULT NULL,
  `crop_year` varchar(10) NOT NULL,
  `job_status` enum('pending','success') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_reactions`
--

CREATE TABLE `post_reactions` (
  `reaction_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `reaction_type` enum('like','love','wow') NOT NULL DEFAULT 'like',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problem_reports`
--

CREATE TABLE `problem_reports` (
  `report_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `category` varchar(20) NOT NULL COMMENT 'system หรือ field',
  `prob_type` varchar(150) NOT NULL,
  `prob_detail` text NOT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `img_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','inprogress','done') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL COMMENT 'admin ตอบกลับ',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `problem_types`
--

CREATE TABLE `problem_types` (
  `problem_id` int(11) NOT NULL,
  `problem_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `problem_types`
--

INSERT INTO `problem_types` (`problem_id`, `problem_name`) VALUES
(1, 'อ้อยไฟไหม้'),
(7, 'อ้อยสกปรก'),
(8, 'ใบอ้อยเยอะ'),
(9, 'ดิน/โคลนเกาะล้อ'),
(10, 'ยางรัดฟ่อนหลุด'),
(11, 'อ้อยเกินน้ำหนักบรรทุก'),
(12, 'สิ่งเจือปนในอ้อย');

-- --------------------------------------------------------

--
-- Table structure for table `replies`
--

CREATE TABLE `replies` (
  `reply_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `emp_name` varchar(50) DEFAULT NULL,
  `emp_unit` varchar(50) DEFAULT NULL,
  `reply_text` text NOT NULL,
  `reply_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reply_logs`
--

CREATE TABLE `reply_logs` (
  `log_id` int(11) NOT NULL,
  `reply_id` int(11) NOT NULL,
  `old_text` text NOT NULL,
  `old_created_at` timestamp NULL DEFAULT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `action_by` varchar(20) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `log_details` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `action_by`, `action_type`, `target_id`, `log_details`, `created_at`) VALUES
(222, 'admin01', 'EDIT_SETTING', 'company_address', 'แก้ไขค่า company_address = 42/1 หมู่ที่ 8 บ้านหาดเสือเต้น ตำบลคุ้งตะเภา อำเภอเมืองอุตรดิตถ์ จังหวัดอุตรดิตถ์', '2026-06-16 02:15:34'),
(223, 'admin01', 'EDIT_SETTING', 'company_name_en', 'แก้ไขค่า company_name_en = Thai Identity Sugar Factory Co., Ltd.', '2026-06-16 02:15:34'),
(224, 'admin01', 'EDIT_SETTING', 'company_name_th', 'แก้ไขค่า company_name_th = บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด', '2026-06-16 02:15:34'),
(225, 'admin01', 'EDIT_SETTING', 'department_name', 'แก้ไขค่า department_name = ฝ่ายไร่', '2026-06-16 02:15:34'),
(226, 'admin01', 'EDIT_SETTING', 'developer_credit', 'แก้ไขค่า developer_credit = Supanat_SK.', '2026-06-16 02:15:34'),
(227, 'admin01', 'EDIT_SETTING', 'current_crop_year', 'แก้ไขค่า current_crop_year = 69/70', '2026-06-16 02:15:34'),
(228, 'admin01', 'EDIT_SETTING', 'image_quality', 'แก้ไขค่า image_quality = 75', '2026-06-16 02:15:34'),
(229, 'admin01', 'EDIT_SETTING', 'max_image_size', 'แก้ไขค่า max_image_size = 800', '2026-06-16 02:15:34'),
(230, 'admin01', 'EDIT_SETTING', 'system_name', 'แก้ไขค่า system_name = KTIS SMART FIELD', '2026-06-16 02:15:34'),
(231, 'admin01', 'EDIT_SETTING', 'system_version', 'แก้ไขค่า system_version = 1.1.0', '2026-06-16 02:15:34'),
(232, 'admin01', 'CREATE_POST', '19', 'สร้างโพสต์ #19 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / ใบอ้อยเยอะ / สิ่งเจือปนในอ้อย', '2026-06-17 07:12:23'),
(233, 'admin01', 'CREATE_POST', '20', 'สร้างโพสต์ #20 หน่วย:131 ศรีนครเหนือ รถ:สท80-1428 ปัญหา:อ้อยสกปรก / ยางรัดฟ่อนหลุด / ใบอ้อยเยอะ', '2026-06-17 07:12:54'),
(234, 'admin01', 'CHANGE_STATUS', '19', 'เปลี่ยนสถานะโพสต์ #19 เป็น success', '2026-06-17 07:15:48'),
(235, 'admin01', 'CHANGE_STATUS', '19', 'เปลี่ยนสถานะโพสต์ #19 เป็น pending', '2026-06-17 07:15:54'),
(236, 'admin01', 'CREATE_POST', '21', 'สร้างโพสต์ #21 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / สิ่งเจือปนในอ้อย / อ้อยเกินน้ำหนักบรรทุก', '2026-06-20 08:05:23'),
(237, 'admin01', 'CREATE_POST', '22', 'สร้างโพสต์ #22 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / อ้อยไฟไหม้', '2026-06-23 08:00:49'),
(238, 'admin01', 'CREATE_POST', '23', 'สร้างโพสต์ #23 หน่วย:111 บางขลัง รถ:สท80-1428 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / อ้อยไฟไหม้', '2026-06-23 08:05:49'),
(239, 'admin01', 'CREATE_POST', '24', 'สร้างโพสต์ #24 หน่วย:111 บางขลัง รถ:สท80-1428 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / อ้อยไฟไหม้', '2026-06-23 08:08:07'),
(240, 'admin01', 'CREATE_POST', '25', 'สร้างโพสต์ #25 หน่วย:144 ศรีสำโรง รถ:สท80-1428 ปัญหา:อ้อยไฟไหม้ / อ้อยไฟไหม้ / อ้อยไฟไหม้', '2026-06-23 08:08:21'),
(241, 'admin01', 'CREATE_POST', '26', 'สร้างโพสต์ #26 หน่วย:111 บางขลัง รถ:สท80-1428 ปัญหา:อ้อยสกปรก / อ้อยสกปรก / อ้อยสกปรก', '2026-06-23 08:08:59'),
(242, 'admin01', 'CREATE_POST', '27', 'สร้างโพสต์ #27 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / อ้อยไฟไหม้', '2026-06-23 08:21:41'),
(243, 'admin01', 'CREATE_POST', '28', 'สร้างโพสต์ #28 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยสกปรก / อ้อยสกปรก / อ้อยสกปรก', '2026-06-23 08:22:11'),
(244, 'admin01', 'CREATE_POST', '29', 'สร้างโพสต์ #29 หน่วย:111 บางขลัง รถ:สท.80-1427 ปัญหา:อ้อยไฟไหม้ / ใบอ้อยเยอะ / อ้อยสกปรก', '2026-06-24 03:47:20'),
(245, 'admin01', 'CREATE_POST', '30', 'สร้างโพสต์ #30 หน่วย:111 บางขลัง รถ:สท.80-1427 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / อ้อยไฟไหม้', '2026-06-24 06:15:26'),
(246, 'admin01', 'CREATE_POST', '31', 'สร้างโพสต์ #31 หน่วย:111 บางขลัง รถ:สท.80-1427 ปัญหา:อ้อยไฟไหม้ / อ้อยไฟไหม้ / ใบอ้อยเยอะ', '2026-06-24 07:39:09'),
(247, 'admin01', 'CREATE_POST', '32', 'สร้างโพสต์ #32 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / อ้อยไฟไหม้ / อ้อยสกปรก', '2026-06-26 02:49:12'),
(248, 'admin01', 'CREATE_POST', '33', 'สร้างโพสต์ #33 หน่วย:111 บางขลัง รถ:สท80-1428 ปัญหา:อ้อยไฟไหม้ / อ้อยสกปรก / ดิน/โคลนเกาะล้อ', '2026-06-26 06:05:09');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_key` varchar(50) NOT NULL COMMENT 'ชื่อ key ของการตั้งค่า',
  `setting_value` text NOT NULL COMMENT 'ค่าของการตั้งค่า',
  `setting_label` varchar(100) NOT NULL COMMENT 'ชื่อแสดงผลภาษาไทย',
  `setting_group` varchar(30) NOT NULL DEFAULT 'general' COMMENT 'กลุ่ม: general / company / system',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_label`, `setting_group`, `updated_at`) VALUES
('company_address', '42/1 หมู่ที่ 8 บ้านหาดเสือเต้น ตำบลคุ้งตะเภา อำเภอเมืองอุตรดิตถ์ จังหวัดอุตรดิตถ์', 'ที่อยู่บริษัท', 'company', '2026-06-11 04:40:39'),
('company_name_en', 'Thai Identity Sugar Factory Co., Ltd.', 'ชื่อบริษัท (ภาษาอังกฤษ)', 'company', '2026-06-11 04:40:39'),
('company_name_th', 'บริษัท น้ำตาลไทยเอกลักษณ์ จำกัด', 'ชื่อบริษัท (ภาษาไทย)', 'company', '2026-06-11 04:40:39'),
('current_crop_year', '69/70', 'ปีการผลิตปัจจุบัน (default login)', 'system', '2026-06-11 04:40:39'),
('department_name', 'ฝ่ายไร่', 'ชื่อฝ่าย', 'company', '2026-06-16 02:15:34'),
('developer_credit', 'Supanat_SK.', 'เครดิตผู้พัฒนา', 'company', '2026-06-11 07:19:46'),
('image_quality', '75', 'คุณภาพรูปภาพ (%)', 'system', '2026-06-11 04:40:39'),
('max_image_size', '800', 'ขนาดรูปภาพสูงสุด (px)', 'system', '2026-06-11 04:40:39'),
('system_name', 'KTIS SMART FIELD', 'ชื่อระบบ', 'system', '2026-06-11 04:40:39'),
('system_version', '1.1.0', 'เวอร์ชันระบบ', 'system', '2026-06-11 07:21:41');

-- --------------------------------------------------------

--
-- Table structure for table `zones`
--

CREATE TABLE `zones` (
  `zone_id` varchar(10) NOT NULL COMMENT 'รหัสหน่วยส่งเสริม',
  `zone_name` varchar(100) NOT NULL COMMENT 'ชื่อหน่วยส่งเสริม/พื้นที่'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zones`
--

INSERT INTO `zones` (`zone_id`, `zone_name`) VALUES
('000', 'ประจำออฟฟิตกลาง'),
('111', 'บางขลัง'),
('112', 'ทุ่งเสลี่ยม'),
('113', 'ตลิ่งชัน'),
('114', 'ตาก'),
('115', 'ศรีสัชนาลัย'),
('121', 'ท่าชัยใต้ 1'),
('122', 'ท่าชัยใต้ 2'),
('123', 'ท่าชัย'),
('124', 'ท่าชัยเหนือ'),
('131', 'ศรีนครเหนือ'),
('132', 'สวรรคโลก'),
('134', 'ชัยคีรี'),
('141', 'เขาหลวง 1'),
('142', 'เขาหลวง 2'),
('143', 'คีรีมาศ'),
('144', 'ศรีสำโรง'),
('211', 'ศรีนครใต้'),
('213', 'วัดโบสถ์'),
('214', 'พรหมพิราม'),
('215', 'หนองตม'),
('216', 'พิชัย'),
('221', 'เมือง'),
('222', 'น้ำปาด'),
('2222', 'แพร่'),
('231', 'น้ำอ่าง'),
('233', 'ชาติตระการ'),
('234', 'บ่อทอง');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `check_items_cut`
--
ALTER TABLE `check_items_cut`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `check_items_field`
--
ALTER TABLE `check_items_field`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `check_results`
--
ALTER TABLE `check_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `fk_results_session` (`session_id`);

--
-- Indexes for table `check_sessions`
--
ALTER TABLE `check_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `fk_sessions_employee` (`emp_id`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `idx_emp_id_unique` (`emp_id`);

--
-- Indexes for table `employee_harvester`
--
ALTER TABLE `employee_harvester`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_assign` (`emp_id`,`harvester_id`);

--
-- Indexes for table `harvesters`
--
ALTER TABLE `harvesters`
  ADD PRIMARY KEY (`harvester_id`),
  ADD UNIQUE KEY `unique_harvester_number` (`harvester_number`);

--
-- Indexes for table `harvester_checks`
--
ALTER TABLE `harvester_checks`
  ADD PRIMARY KEY (`check_id`),
  ADD KEY `idx_harvester_date` (`check_date`,`crop_year`),
  ADD KEY `fk_checks_employee` (`emp_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`noti_id`),
  ADD KEY `idx_noti_emp` (`emp_id`,`is_read`),
  ADD KEY `fk_noti_posts` (`post_id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `idx_posts_crop` (`crop_year`),
  ADD KEY `idx_posts_unit` (`target_unit`),
  ADD KEY `idx_posts_date_status` (`created_at`,`job_status`),
  ADD KEY `fk_posts_employee` (`emp_id`);

--
-- Indexes for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `unique_reaction` (`post_id`,`emp_id`),
  ADD KEY `fk_reaction_post` (`post_id`);

--
-- Indexes for table `problem_reports`
--
ALTER TABLE `problem_reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_emp_id` (`emp_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `problem_types`
--
ALTER TABLE `problem_types`
  ADD PRIMARY KEY (`problem_id`);

--
-- Indexes for table `replies`
--
ALTER TABLE `replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `idx_reply_post` (`post_id`),
  ADD KEY `fk_replies_employee` (`emp_id`);

--
-- Indexes for table `reply_logs`
--
ALTER TABLE `reply_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `reply_id` (`reply_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_logs_employee` (`action_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `zones`
--
ALTER TABLE `zones`
  ADD PRIMARY KEY (`zone_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `check_items_cut`
--
ALTER TABLE `check_items_cut`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `check_items_field`
--
ALTER TABLE `check_items_field`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `check_results`
--
ALTER TABLE `check_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `check_sessions`
--
ALTER TABLE `check_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_harvester`
--
ALTER TABLE `employee_harvester`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `harvesters`
--
ALTER TABLE `harvesters`
  MODIFY `harvester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `harvester_checks`
--
ALTER TABLE `harvester_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `noti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `post_reactions`
--
ALTER TABLE `post_reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `problem_reports`
--
ALTER TABLE `problem_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `problem_types`
--
ALTER TABLE `problem_types`
  MODIFY `problem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reply_logs`
--
ALTER TABLE `reply_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `check_results`
--
ALTER TABLE `check_results`
  ADD CONSTRAINT `fk_results_session` FOREIGN KEY (`session_id`) REFERENCES `check_sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `check_sessions`
--
ALTER TABLE `check_sessions`
  ADD CONSTRAINT `fk_sessions_employee` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE;

--
-- Constraints for table `harvester_checks`
--
ALTER TABLE `harvester_checks`
  ADD CONSTRAINT `fk_checks_employee` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_noti_employee` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_noti_posts` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `fk_posts_employee` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE;

--
-- Constraints for table `post_reactions`
--
ALTER TABLE `post_reactions`
  ADD CONSTRAINT `fk_reaction_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `replies`
--
ALTER TABLE `replies`
  ADD CONSTRAINT `fk_replies_employee` FOREIGN KEY (`emp_id`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_replies_posts` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reply_logs`
--
ALTER TABLE `reply_logs`
  ADD CONSTRAINT `reply_logs_ibfk_1` FOREIGN KEY (`reply_id`) REFERENCES `replies` (`reply_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `fk_logs_employee` FOREIGN KEY (`action_by`) REFERENCES `employee` (`emp_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
