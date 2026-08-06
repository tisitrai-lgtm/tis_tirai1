-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 15, 2026 at 10:04 AM
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
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('department_name', 'ฝ่ายไร่wihj', 'ชื่อฝ่าย', 'company', '2026-06-11 04:49:43'),
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
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `check_sessions`
--
ALTER TABLE `check_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `ID` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `harvester_checks`
--
ALTER TABLE `harvester_checks`
  MODIFY `check_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `noti_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reply_logs`
--
ALTER TABLE `reply_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

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
