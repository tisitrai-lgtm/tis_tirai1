-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 11, 2026 at 06:16 AM
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
  `item_name_cut` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_items_cut`
--

INSERT INTO `check_items_cut` (`item_id`, `item_name_cut`) VALUES
(1, 'ใบพัดสับท่อน'),
(2, 'ตัดยอดอ้อย'),
(3, 'ตัดต่อ'),
(4, 'ตัดโคนอ้อย'),
(5, 'พัดลมดูดใบ');

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
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_results`
--

INSERT INTO `check_results` (`result_id`, `session_id`, `item_id`, `pass`, `note`) VALUES
(1, 1, 1, 1, NULL),
(2, 1, 2, 1, NULL),
(3, 1, 3, 1, NULL),
(4, 1, 4, 1, NULL),
(5, 1, 5, 1, NULL),
(6, 2, 1, 0, NULL),
(7, 2, 2, 1, NULL),
(8, 2, 3, 1, NULL),
(9, 2, 4, 1, NULL),
(10, 2, 5, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `check_sessions`
--

CREATE TABLE `check_sessions` (
  `session_id` int(11) NOT NULL,
  `emp_id` varchar(20) NOT NULL,
  `harvester_number` varchar(30) NOT NULL,
  `crop_year` varchar(10) NOT NULL,
  `overall_pass` tinyint(1) DEFAULT NULL,
  `remark` text DEFAULT NULL,
  `checked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `field_condition` varchar(100) DEFAULT NULL COMMENT 'สภาพแปลง',
  `field_condition_etc` varchar(255) DEFAULT NULL COMMENT 'อื่นๆ กรณีเลือกอื่นๆ',
  `img_harvester` varchar(255) DEFAULT NULL COMMENT 'รูปรถตัด',
  `img_field` varchar(255) DEFAULT NULL COMMENT 'รูปแปลงอ้อย'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `check_sessions`
--

INSERT INTO `check_sessions` (`session_id`, `emp_id`, `harvester_number`, `crop_year`, `overall_pass`, `remark`, `checked_at`, `field_condition`, `field_condition_etc`, `img_harvester`, `img_field`) VALUES
(1, 'TIS-111', 'MC-1', '69/70', NULL, NULL, '2026-06-10 09:41:20', 'ปกติ', NULL, 'im_user_check/2026-06-10/1781084480_6594.jpg', 'im_user_check/2026-06-10/1781084480_4127.jpg'),
(2, 'TIS-111', 'MC-1', '69/70', NULL, NULL, '2026-06-10 09:41:37', 'ปกติ', NULL, 'im_user_check/2026-06-10/1781084497_2478.jpg', 'im_user_check/2026-06-10/1781084497_5144.jpg');

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
(5, 'TIS-111', '$2y$10$0RpaCzmHYe7OoferVkB9s.GqS0VQ9JcsPBQK/BxsgaBL2ebBMlGem', 'นายพัชกร จันทนะโพธิ', '111 บางขลัง', 'u');

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

--
-- Dumping data for table `harvester_checks`
--

INSERT INTO `harvester_checks` (`check_id`, `emp_id`, `harvester_number`, `check_blade`, `check_top_cutter`, `check_chopper`, `check_base_cutter`, `check_extractor`, `crop_year`, `check_date`, `created_at`) VALUES
(1, 'admin01', 'MC-1', 1, 1, 1, 1, 1, '69/70', '2026-06-05', '2026-06-05 07:40:51'),
(2, 'admin01', 'MC-1', 0, 0, 1, 1, 1, '69/70', '2026-06-06', '2026-06-06 04:10:02'),
(3, 'admin01', 'MC-1', 0, 0, 1, 1, 1, '69/70', '2026-06-06', '2026-06-06 04:11:39'),
(4, 'TIS-111', 'MC-1', 1, 1, 1, 1, 1, '69/70', '2026-06-10', '2026-06-10 09:41:20'),
(5, 'TIS-111', 'MC-1', 0, 1, 1, 1, 1, '69/70', '2026-06-10', '2026-06-10 09:41:37');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`noti_id`, `post_id`, `emp_id`, `target_unit`, `noti_text`, `is_read`, `created_at`) VALUES
(11, 12, 'TIS-111', '111 บางขลัง', 'ออฟฟิศกลางแจ้งตรวจสอบรถอ้อย: ทะเบียน สท80-1427', 1, '2026-06-06 03:57:33'),
(12, 12, 'admin01', '111 บางขลัง', 'คุณ พนักงาน ได้รายงานความคืบหน้าในโพสต์รถตัดของคุณ', 1, '2026-06-06 03:58:13'),
(14, 13, 'TIS-111', '111 บางขลัง', 'แจ้งรถสกปรก: สท80-1427 — อ้อยไฟไหม้ / ใบอ้อยเยอะ / ใบอ้อยเยอะ', 1, '2026-06-08 03:40:51'),
(16, 14, 'TIS-111', '111 บางขลัง', 'แจ้งรถสกปรก: สท80-1427 — อ้อยเกินน้ำหนักบรรทุก / ใบอ้อยเยอะ / อ้อยไฟไหม้', 1, '2026-06-08 03:41:22'),
(17, 13, 'admin01', '111 บางขลัง', 'คุณ นายพัชกร จันทนะโพธิ ได้รายงานความคืบหน้าในโพสต์รถตัดของคุณ', 1, '2026-06-08 03:42:49'),
(18, 14, 'admin01', '111 บางขลัง', 'คุณ นายพัชกร จันทนะโพธิ ได้รายงานความคืบหน้าในโพสต์รถตัดของคุณ', 1, '2026-06-08 03:43:01'),
(19, 15, 'TIS-111', '111 บางขลัง', 'แจ้งรถสกปรก: สท80-1427 — อ้อยไฟไหม้ / ใบอ้อยเยอะ', 1, '2026-06-10 08:50:09'),
(20, 15, 'admin01', '111 บางขลัง', 'คุณ นายพัชกร จันทนะโพธิ ได้รายงานความคืบหน้าในโพสต์แจ้งปัญหาของคุณ', 1, '2026-06-10 08:50:46');

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

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `emp_id`, `target_unit`, `truck_number`, `post_text`, `problem_detail`, `problem_detail_2`, `problem_detail_3`, `post_image`, `post_image_2`, `post_image_3`, `crop_year`, `job_status`, `created_at`, `updated_at`) VALUES
(1, 'admin01', 'ปก', 'หก', 'หกห', 'หกห', NULL, NULL, 'uploads/2026-06-05/1780645549_2640.jpg', 'uploads/2026-06-05/1780645549_5797.jpg', 'uploads/2026-06-05/1780645549_4666.jpg', '69/70', 'pending', '2026-06-05 07:45:49', NULL),
(2, 'admin01', '111 บางขลัง', '1555', '', 'sfsdsd', NULL, NULL, 'uploads/2026-06-05/1780646067_7075.png', 'uploads/2026-06-05/1780646067_3673.jpg', 'uploads/2026-06-05/1780646067_1275.jpg', '69/70', 'success', '2026-06-05 07:54:27', '2026-06-05 07:57:03'),
(7, 'admin01', '111 บางขลัง', '1555', '', 'หห', NULL, NULL, 'uploads/2026-06-05/1780650111_1850.png', NULL, NULL, '69/70', 'pending', '2026-06-05 09:01:51', NULL),
(12, 'admin01', '111 บางขลัง', 'สท80-1427', 'บบกดดกยาิยนดเ่ืน่กด', 'อ้อยไฟไหม้', NULL, NULL, 'uploads/2026-06-06/1780718253_5570.png', 'uploads/2026-06-06/1780718253_2126.jpg', 'uploads/2026-06-06/1780718253_7904.jpg', '69/70', 'pending', '2026-06-06 03:57:33', '2026-06-08 03:31:09'),
(13, 'admin01', '111 บางขลัง', 'สท80-1427', 'สาสววว', 'อ้อยไฟไหม้', 'ใบอ้อยเยอะ', 'ใบอ้อยเยอะ', 'uploads/2026-06-08/p1_1780890051_3876.jpg', 'uploads/2026-06-08/p2_1780890051_4059.jpg', 'uploads/2026-06-08/p3_1780890051_5124.jpg', '69/70', 'pending', '2026-06-08 03:40:51', NULL),
(14, 'admin01', '111 บางขลัง', 'สท80-1427', '', 'อ้อยเกินน้ำหนักบรรทุก', 'ใบอ้อยเยอะ', 'อ้อยไฟไหม้', 'uploads/2026-06-08/p1_1780890082_9679.jpg', NULL, NULL, '69/70', 'pending', '2026-06-08 03:41:22', '2026-06-10 01:32:44'),
(15, 'admin01', '111 บางขลัง', 'สท80-1427', 'ทอสอบ', 'อ้อยไฟไหม้', 'ใบอ้อยเยอะ', NULL, 'uploads/2026-06-10/p1_1781081409_4165.jpg', NULL, NULL, '69/70', 'success', '2026-06-10 08:50:09', '2026-06-10 08:51:24');

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

--
-- Dumping data for table `replies`
--

INSERT INTO `replies` (`reply_id`, `post_id`, `emp_id`, `emp_name`, `emp_unit`, `reply_text`, `reply_image`, `created_at`, `updated_at`) VALUES
(1, 1, 'admin01', NULL, NULL, 'หกหกห', NULL, '2026-06-05 07:46:04', NULL),
(2, 1, 'admin01', NULL, NULL, 'หกห', 'uploads/2026-06-05/reply_1780645575_9579.jpg', '2026-06-05 07:46:15', NULL),
(5, 12, 'admin01', 'ผู้จัดการ ออฟฟิศกลาง', 'ประจำออฟฟิตกลาง', '1', NULL, '2026-06-06 04:36:13', NULL),
(6, 13, 'TIS-111', 'นายพัชกร จันทนะโพธิ', '111 บางขลัง', 'เรียบร้อยแล้วครับ', 'uploads/2026-06-08/reply_1780890169_6a263a39f0eaf.jpg', '2026-06-08 03:42:49', NULL),
(7, 14, 'TIS-111', 'นายพัชกร จันทนะโพธิ', '111 บางขลัง', 'แก้แล้วครับ111', 'uploads/2026-06-08/reply_1780890181_6a263a4584d18.jpg', '2026-06-08 03:43:01', '2026-06-08 03:46:38'),
(8, 15, 'TIS-111', 'นายพัชกร จันทนะโพธิ', '111 บางขลัง', 'รับทราบ', 'uploads/2026-06-10/reply_1781081446_6a292566d71d3.png', '2026-06-10 08:50:46', NULL);

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

--
-- Dumping data for table `reply_logs`
--

INSERT INTO `reply_logs` (`log_id`, `reply_id`, `old_text`, `old_created_at`, `edited_at`) VALUES
(5, 7, 'แก้แล้วครับ', '2026-06-08 03:43:01', '2026-06-08 03:46:33');

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
(1, 'admin01', 'CHANGE_STATUS', '12', 'เปลี่ยนสถานะโพสต์ #12 เป็น success', '2026-06-08 03:31:01'),
(2, 'admin01', 'CHANGE_STATUS', '12', 'เปลี่ยนสถานะโพสต์ #12 เป็น pending', '2026-06-08 03:31:09'),
(3, 'admin01', 'CREATE_POST', '13', 'สร้างโพสต์ #13 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / ใบอ้อยเยอะ / ใบอ้อยเยอะ', '2026-06-08 03:40:51'),
(4, 'admin01', 'CREATE_POST', '14', 'สร้างโพสต์ #14 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยเกินน้ำหนักบรรทุก / ใบอ้อยเยอะ / อ้อยไฟไหม้', '2026-06-08 03:41:22'),
(5, 'admin01', 'CHANGE_STATUS', '14', 'เปลี่ยนสถานะโพสต์ #14 เป็น success', '2026-06-08 03:47:15'),
(6, 'admin01', 'CHANGE_STATUS', '14', 'เปลี่ยนสถานะโพสต์ #14 เป็น pending', '2026-06-10 01:32:44'),
(7, 'admin01', 'CREATE_POST', '15', 'สร้างโพสต์ #15 หน่วย:111 บางขลัง รถ:สท80-1427 ปัญหา:อ้อยไฟไหม้ / ใบอ้อยเยอะ', '2026-06-10 08:50:09'),
(8, 'admin01', 'CHANGE_STATUS', '15', 'เปลี่ยนสถานะโพสต์ #15 เป็น success', '2026-06-10 08:51:24');

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
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `check_items_field`
--
ALTER TABLE `check_items_field`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `check_results`
--
ALTER TABLE `check_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `check_sessions`
--
ALTER TABLE `check_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `harvester_checks`
--
ALTER TABLE `harvester_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `noti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `problem_types`
--
ALTER TABLE `problem_types`
  MODIFY `problem_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `replies`
--
ALTER TABLE `replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reply_logs`
--
ALTER TABLE `reply_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
