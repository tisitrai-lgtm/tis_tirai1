-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2025 at 05:09 AM
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
-- Database: `sugarcane_project`
--

-- --------------------------------------------------------

--
-- Table structure for table `production_years`
--

CREATE TABLE `production_years` (
  `id` int(11) NOT NULL,
  `year_label` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_years`
--

INSERT INTO `production_years` (`id`, `year_label`) VALUES
(1, '68-69'),
(2, '69-70');

-- --------------------------------------------------------

--
-- Table structure for table `soil_data`
--

CREATE TABLE `soil_data` (
  `id` int(11) NOT NULL,
  `production_year` varchar(10) NOT NULL,
  `agency` varchar(255) DEFAULT NULL,
  `contract_number` varchar(255) DEFAULT NULL,
  `quota` varchar(255) DEFAULT NULL,
  `plot_id` varchar(255) DEFAULT NULL,
  `rai_area` int(11) DEFAULT NULL,
  `soil_type` int(11) DEFAULT NULL,
  `soil_image` varchar(255) DEFAULT NULL,
  `soil_preparation_details` int(11) DEFAULT NULL,
  `soil_preparation_image` varchar(255) DEFAULT NULL,
  `cane_variety` int(11) DEFAULT NULL,
  `cane_variety_image` varchar(255) DEFAULT NULL,
  `planting_details` int(11) DEFAULT NULL,
  `planting_image` varchar(255) DEFAULT NULL,
  `watering_details` int(11) DEFAULT NULL,
  `watering_image` varchar(255) DEFAULT NULL,
  `germination_percentage` int(11) DEFAULT NULL,
  `germination_image` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `soil_data`
--

INSERT INTO `soil_data` (`id`, `production_year`, `agency`, `contract_number`, `quota`, `plot_id`, `rai_area`, `soil_type`, `soil_image`, `soil_preparation_details`, `soil_preparation_image`, `cane_variety`, `cane_variety_image`, `planting_details`, `planting_image`, `watering_details`, `watering_image`, `germination_percentage`, `germination_image`, `notes`, `created_at`) VALUES
(15, '68-69', 'เมือง', '002411', 'สุภารัต จาจี', '10555', 25, 1, '1752313777_68722fb145c78_47425.jpg', 1, '1752313777_68722fb145def_47296.jpg', 1, '1752313777_68722fb14601c_47425.jpg', 2, '1752313777_68722fb146206_47296.jpg', 1, '1752313777_68722fb146391_47425.jpg', 50, '1752313777_68722fb14651d_47296.jpg', '555555555555555555555555555555555555555555555555555555555', '2025-07-12 09:49:37'),
(16, '69-70', 'เมือง', '002411', 'สุภารัต จาจี', '10555', 25, 2, '1752459825_68746a315f8c1_47296.jpg', 1, '1752459825_68746a315fb3a_47425.jpg', 1, '1752459825_68746a315fd16_47296.jpg', 1, '1752459825_68746a315ffe6_47425.jpg', 1, '1752459825_68746a31601f3_47296.jpg', 50, '1752459825_68746a316034c_47425.jpg', '', '2025-07-14 02:23:45'),
(17, '68-69', 'เมือง', '002411', 'สุภารัต จาจี', '1052239', 95, 0, '', 0, '', 0, '', 0, '', 0, '', 50, '', '', '2025-07-14 02:26:02'),
(18, '68-69', 'เมือง', '002411', 'สุภารัต จาจี', '9654443', 95, 1, '1752476148_6874a9f43551e_47425.jpg', 1, '1752917538_687b6622cfbdc_unnamed.png', 1, '', 1, '', 1, '', 10, '', '', '2025-07-14 06:55:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `production_years`
--
ALTER TABLE `production_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year_label` (`year_label`);

--
-- Indexes for table `soil_data`
--
ALTER TABLE `soil_data`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `production_years`
--
ALTER TABLE `production_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `soil_data`
--
ALTER TABLE `soil_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
