-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2026 at 02:52 PM
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
-- Database: `fish_price_forecasting_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `fish_species`
--

CREATE TABLE `fish_species` (
  `id` int(10) UNSIGNED NOT NULL,
  `common_name` varchar(120) NOT NULL,
  `scientific_name` varchar(180) DEFAULT NULL,
  `local_name` varchar(120) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_species`
--

INSERT INTO `fish_species` (`id`, `common_name`, `scientific_name`, `local_name`, `is_active`) VALUES
(1, 'lizardfish', 'Synodontidae,', 'Karaho', 1),
(2, 'lizardfishssss', 'Synodontidae,ssss', 'Karahodsdsds', 1),
(3, 'Alumahan', 'Indian Mackerel', 'Alumahan', 1),
(4, 'Bangus, Large', 'Milkfish', 'Bangus', 1),
(5, 'Bangus, Medium', 'Milkfish', 'Bangus, Medium', 1),
(6, 'Bolinaw (Anchovies)', NULL, NULL, 1),
(7, 'Budburon (Round Scad)', NULL, NULL, 1),
(8, 'Buraw (Big-eyed Scad)', NULL, NULL, 1),
(9, 'Dalagang Bukid (Fusiliers)', NULL, NULL, 1),
(10, 'Danggit (Rabbitfish)', NULL, NULL, 1),
(11, 'Katambak (Sea Bream)', NULL, NULL, 1),
(12, 'Kitong/Samaral', NULL, NULL, 1),
(13, 'Lagaw/Bisugo (Threadfin Bream)', NULL, NULL, 1),
(14, 'Lambay (Blue Swimming Crab)', NULL, NULL, 1),
(15, 'Lumyagan (Squid)', NULL, NULL, 1),
(16, 'Anduhaw (Mackerel)', NULL, NULL, 1),
(17, 'Mamsa (Trevally)', NULL, NULL, 1),
(18, 'Mangsi/Malangsi', NULL, NULL, 1),
(19, 'Molmol (Parrot Fish)', NULL, NULL, 1),
(20, 'Nokus (Cuttlefish)', NULL, NULL, 1),
(21, 'Pasayan (Litopenaeus vannamei)', NULL, NULL, 1),
(22, 'Sardines (Tamban)', NULL, NULL, 1),
(23, 'Squid (Pusit Bisaya), Local', NULL, NULL, 1),
(24, 'Tamarong (Blackfin Scad)', NULL, NULL, 1),
(25, 'Tambakol (Yellow-Fin Tuna)', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `forecasts`
--

CREATE TABLE `forecasts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `forecast_run_id` bigint(20) UNSIGNED NOT NULL,
  `market_id` int(10) UNSIGNED NOT NULL,
  `fish_species_id` int(10) UNSIGNED NOT NULL,
  `target_date` date NOT NULL,
  `predicted_price_per_kg` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `forecast_runs`
--

CREATE TABLE `forecast_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `model_version` varchar(80) DEFAULT NULL,
  `training_start` date DEFAULT NULL,
  `training_end` date DEFAULT NULL,
  `test_start` date DEFAULT NULL,
  `test_end` date DEFAULT NULL,
  `horizon_days` int(10) UNSIGNED NOT NULL,
  `parameters_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters_json`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `markets`
--

CREATE TABLE `markets` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `municipality` varchar(100) NOT NULL DEFAULT 'Madridejos',
  `province` varchar(100) NOT NULL DEFAULT 'Cebu',
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `markets`
--

INSERT INTO `markets` (`id`, `name`, `municipality`, `province`, `is_active`) VALUES
(1, 'Madridejos', 'Madridejos', 'Cebu', 1),
(2, 'Estancia ilo-ilo', 'Estancia ilo-ilo', 'Estancia ilo-ilo', 1),
(3, 'WET MARKET', 'Madridejos', 'Cebu', 1),
(4, 'Bantayan Area', 'Bantayan', 'Cebu', 1);

-- --------------------------------------------------------

--
-- Table structure for table `model_evaluations`
--

CREATE TABLE `model_evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `forecast_run_id` bigint(20) UNSIGNED NOT NULL,
  `market_id` int(10) UNSIGNED DEFAULT NULL,
  `fish_species_id` int(10) UNSIGNED DEFAULT NULL,
  `mae` decimal(12,4) NOT NULL,
  `rmse` decimal(12,4) NOT NULL,
  `test_observations` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_records`
--

CREATE TABLE `price_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `market_id` int(10) UNSIGNED NOT NULL,
  `fish_species_id` int(10) UNSIGNED NOT NULL,
  `observed_on` date NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `vendor_code` varchar(50) NOT NULL DEFAULT 'MARKET_AVERAGE',
  `source_type` enum('vendor_interview','market_record','government_record','other') NOT NULL,
  `source_reference` varchar(255) DEFAULT NULL,
  `collection_time` time DEFAULT NULL,
  `product_condition` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `price_records`
--

INSERT INTO `price_records` (`id`, `market_id`, `fish_species_id`, `observed_on`, `price_per_kg`, `vendor_code`, `source_type`, `source_reference`, `collection_time`, `product_condition`, `remarks`, `recorded_by`, `created_at`) VALUES
(1, 1, 1, '2026-10-19', 200.00, 'v001', 'vendor_interview', 'dsd', '21:32:00', 'dsads', NULL, 1, '2026-10-19 13:32:17'),
(2, 3, 3, '2026-10-14', 270.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(3, 3, 4, '2026-10-14', 260.00, 'S-001', 'government_record', 'Bantayan Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(4, 3, 5, '2026-10-14', 220.00, 'S-001', 'government_record', 'Bantayan Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(5, 3, 6, '2026-10-14', 160.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(6, 3, 7, '2026-10-14', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(7, 3, 8, '2026-10-14', 270.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(8, 3, 9, '2026-10-14', 210.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(9, 3, 10, '2026-10-14', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(10, 3, 11, '2026-10-14', 310.00, 'S-001', 'government_record', 'Madridejos Area', NULL, 'FRESH AND CHILLED', NULL, 1, '2026-11-12 13:38:59'),
(11, 3, 12, '2026-10-14', 260.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(12, 3, 13, '2026-10-14', 310.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(13, 3, 14, '2026-10-14', 350.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(14, 3, 15, '2026-10-14', 220.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(15, 3, 16, '2026-10-14', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(16, 3, 17, '2026-10-14', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(17, 3, 18, '2026-10-14', 90.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(18, 3, 19, '2026-10-14', 190.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(19, 3, 20, '2026-10-14', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(20, 3, 21, '2026-10-14', 360.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(21, 3, 22, '2026-10-14', 120.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(22, 3, 23, '2026-10-14', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(23, 3, 24, '2026-10-14', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(24, 3, 25, '2026-10-14', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(25, 3, 3, '2026-10-14', 270.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(26, 3, 4, '2026-10-14', 260.00, 'S-002', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(27, 3, 5, '2026-10-14', 210.00, 'S-002', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(28, 3, 6, '2026-10-14', 160.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(29, 3, 7, '2026-10-14', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(30, 3, 8, '2026-10-14', 270.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(31, 3, 9, '2026-10-14', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(32, 3, 10, '2026-10-14', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(33, 3, 11, '2026-10-14', 310.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(34, 3, 12, '2026-10-14', 260.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(35, 3, 13, '2026-10-14', 310.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(36, 3, 14, '2026-10-14', 360.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(37, 3, 15, '2026-10-14', 220.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(38, 3, 16, '2026-10-14', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(39, 3, 17, '2026-10-14', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(40, 3, 18, '2026-10-14', 90.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(41, 3, 19, '2026-10-14', 190.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(42, 3, 20, '2026-10-14', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(43, 3, 21, '2026-10-14', 360.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(44, 3, 22, '2026-10-14', 120.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(45, 3, 23, '2026-10-14', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(46, 3, 24, '2026-10-14', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(47, 3, 25, '2026-10-14', 230.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(48, 3, 3, '2026-10-14', 280.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(49, 3, 4, '2026-10-14', 270.00, 'S-003', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(50, 3, 5, '2026-10-14', 210.00, 'S-003', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(51, 3, 6, '2026-10-14', 150.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(52, 3, 7, '2026-10-14', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(53, 3, 8, '2026-10-14', 270.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(54, 3, 9, '2026-10-14', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(55, 3, 10, '2026-10-14', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(56, 3, 11, '2026-10-14', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(57, 3, 12, '2026-10-14', 260.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(58, 3, 13, '2026-10-14', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(59, 3, 14, '2026-10-14', 360.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(60, 3, 15, '2026-10-14', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(61, 3, 16, '2026-10-14', 240.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(62, 3, 17, '2026-10-14', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(63, 3, 18, '2026-10-14', 90.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(64, 3, 19, '2026-10-14', 190.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(65, 3, 20, '2026-10-14', 400.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(66, 3, 21, '2026-10-14', 360.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(67, 3, 22, '2026-10-14', 120.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(68, 3, 23, '2026-10-14', 410.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(69, 3, 24, '2026-10-14', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(70, 3, 25, '2026-10-14', 230.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(71, 3, 3, '2026-10-14', 280.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(72, 3, 4, '2026-10-14', 250.00, 'S-004', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(73, 3, 5, '2026-10-14', 210.00, 'S-004', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(74, 3, 6, '2026-10-14', 150.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(75, 3, 7, '2026-10-14', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(76, 3, 8, '2026-10-14', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(77, 3, 9, '2026-10-14', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(78, 3, 10, '2026-10-14', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(79, 3, 11, '2026-10-14', 310.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(80, 3, 12, '2026-10-14', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(81, 3, 13, '2026-10-14', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(82, 3, 14, '2026-10-14', 350.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(83, 3, 15, '2026-10-14', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(84, 3, 16, '2026-10-14', 230.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(85, 3, 17, '2026-10-14', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(86, 3, 18, '2026-10-14', 90.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(87, 3, 19, '2026-10-14', 190.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(88, 3, 20, '2026-10-14', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(89, 3, 21, '2026-10-14', 350.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(90, 3, 22, '2026-10-14', 120.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(91, 3, 23, '2026-10-14', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(92, 3, 24, '2026-10-14', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(93, 3, 25, '2026-10-14', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(94, 3, 3, '2026-10-14', 270.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(95, 3, 4, '2026-10-14', 260.00, 'S-005', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(96, 3, 5, '2026-10-14', 220.00, 'S-005', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(97, 3, 6, '2026-10-14', 150.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(98, 3, 7, '2026-10-14', 210.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(99, 3, 8, '2026-10-14', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(100, 3, 9, '2026-10-14', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(101, 3, 10, '2026-10-14', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(102, 3, 11, '2026-10-14', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(103, 3, 12, '2026-10-14', 270.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(104, 3, 13, '2026-10-14', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(105, 3, 14, '2026-10-14', 360.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(106, 3, 15, '2026-10-14', 260.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(107, 3, 16, '2026-10-14', 230.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(108, 3, 17, '2026-10-14', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(109, 3, 18, '2026-10-14', 80.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(110, 3, 19, '2026-10-14', 180.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(111, 3, 20, '2026-10-14', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(112, 3, 21, '2026-10-14', 350.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(113, 3, 22, '2026-10-14', 120.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(114, 3, 23, '2026-10-14', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(115, 3, 24, '2026-10-14', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(116, 3, 25, '2026-10-14', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(117, 3, 3, '2026-10-16', 260.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(118, 3, 4, '2026-10-16', 250.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(119, 3, 5, '2026-10-16', 230.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(120, 3, 6, '2026-10-16', 100.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(121, 3, 7, '2026-10-16', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(122, 3, 8, '2026-10-16', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(123, 3, 9, '2026-10-16', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(124, 3, 10, '2026-10-16', 220.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(125, 3, 11, '2026-10-16', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(126, 3, 12, '2026-10-16', 270.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(127, 3, 13, '2026-10-16', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(128, 3, 14, '2026-10-16', 360.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(129, 3, 15, '2026-10-16', 260.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(130, 3, 16, '2026-10-16', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(131, 3, 17, '2026-10-16', 310.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(132, 3, 18, '2026-10-16', 90.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(133, 3, 19, '2026-10-16', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(134, 3, 20, '2026-10-16', 390.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(135, 3, 21, '2026-10-16', 350.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(136, 3, 22, '2026-10-16', 110.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(137, 3, 23, '2026-10-16', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(138, 3, 24, '2026-10-16', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(139, 3, 25, '2026-10-16', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(140, 3, 3, '2026-10-16', 260.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(141, 3, 4, '2026-10-16', 250.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(142, 3, 5, '2026-10-16', 230.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(143, 3, 6, '2026-10-16', 140.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(144, 3, 7, '2026-10-16', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(145, 3, 8, '2026-10-16', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(146, 3, 9, '2026-10-16', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(147, 3, 10, '2026-10-16', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(148, 3, 11, '2026-10-16', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(149, 3, 12, '2026-10-16', 270.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(150, 3, 13, '2026-10-16', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(151, 3, 14, '2026-10-16', 360.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(152, 3, 15, '2026-10-16', 260.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(153, 3, 16, '2026-10-16', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(154, 3, 17, '2026-10-16', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(155, 3, 18, '2026-10-16', 90.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(156, 3, 19, '2026-10-16', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(157, 3, 20, '2026-10-16', 390.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(158, 3, 21, '2026-10-16', 300.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(159, 3, 22, '2026-10-16', 120.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(160, 3, 23, '2026-10-16', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(161, 3, 24, '2026-10-16', 220.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(162, 3, 25, '2026-10-16', 220.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(163, 3, 3, '2026-10-16', 270.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(164, 3, 4, '2026-10-16', 260.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(165, 3, 5, '2026-10-16', 230.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(166, 3, 6, '2026-10-16', 140.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(167, 3, 7, '2026-10-16', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(168, 3, 8, '2026-10-16', 230.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(169, 3, 9, '2026-10-16', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(170, 3, 10, '2026-10-16', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(171, 3, 11, '2026-10-16', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(172, 3, 12, '2026-10-16', 270.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(173, 3, 13, '2026-10-16', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(174, 3, 14, '2026-10-16', 360.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(175, 3, 15, '2026-10-16', 260.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(176, 3, 16, '2026-10-16', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(177, 3, 17, '2026-10-16', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(178, 3, 18, '2026-10-16', 80.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(179, 3, 19, '2026-10-16', 190.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(180, 3, 20, '2026-10-16', 400.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(181, 3, 21, '2026-10-16', 300.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(182, 3, 22, '2026-10-16', 120.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(183, 3, 23, '2026-10-16', 410.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(184, 3, 24, '2026-10-16', 220.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(185, 3, 25, '2026-10-16', 220.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(186, 3, 3, '2026-10-16', 270.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(187, 3, 4, '2026-10-16', 260.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(188, 3, 5, '2026-10-16', 220.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(189, 3, 6, '2026-10-16', 140.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(190, 3, 7, '2026-10-16', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(191, 3, 8, '2026-10-16', 230.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(192, 3, 9, '2026-10-16', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(193, 3, 10, '2026-10-16', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(194, 3, 11, '2026-10-16', 310.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(195, 3, 12, '2026-10-16', 270.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(196, 3, 13, '2026-10-16', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(197, 3, 14, '2026-10-16', 350.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(198, 3, 15, '2026-10-16', 270.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(199, 3, 16, '2026-10-16', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(200, 3, 17, '2026-10-16', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(201, 3, 18, '2026-10-16', 80.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(202, 3, 19, '2026-10-16', 190.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(203, 3, 20, '2026-10-16', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(204, 3, 21, '2026-10-16', 300.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(205, 3, 22, '2026-10-16', 110.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(206, 3, 23, '2026-10-16', 410.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(207, 3, 24, '2026-10-16', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(208, 3, 25, '2026-10-16', 220.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(209, 3, 3, '2026-10-16', 260.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(210, 3, 4, '2026-10-16', 260.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(211, 3, 5, '2026-10-16', 220.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(212, 3, 6, '2026-10-16', 100.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(213, 3, 7, '2026-10-16', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(214, 3, 8, '2026-10-16', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(215, 3, 9, '2026-10-16', 210.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(216, 3, 10, '2026-10-16', 220.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(217, 3, 11, '2026-10-16', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(218, 3, 12, '2026-10-16', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(219, 3, 13, '2026-10-16', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(220, 3, 14, '2026-10-16', 350.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(221, 3, 15, '2026-10-16', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(222, 3, 16, '2026-10-16', 270.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(223, 3, 17, '2026-10-16', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(224, 3, 18, '2026-10-16', 80.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(225, 3, 19, '2026-10-16', 190.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(226, 3, 20, '2026-10-16', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(227, 3, 21, '2026-10-16', 350.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(228, 3, 22, '2026-10-16', 120.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(229, 3, 23, '2026-10-16', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(230, 3, 24, '2026-10-16', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(231, 3, 25, '2026-10-16', 220.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(232, 3, 3, '2026-10-19', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(233, 3, 4, '2026-10-19', 260.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(234, 3, 5, '2026-10-19', 230.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(235, 3, 6, '2026-10-19', 160.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(236, 3, 7, '2026-10-19', 210.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(237, 3, 8, '2026-10-19', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(238, 3, 9, '2026-10-19', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(239, 3, 10, '2026-10-19', 180.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(240, 3, 11, '2026-10-19', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(241, 3, 12, '2026-10-19', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(242, 3, 13, '2026-10-19', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(243, 3, 14, '2026-10-19', 350.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(244, 3, 15, '2026-10-19', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(245, 3, 16, '2026-10-19', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(246, 3, 17, '2026-10-19', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(247, 3, 18, '2026-10-19', 80.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(248, 3, 19, '2026-10-19', 180.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(249, 3, 20, '2026-10-19', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(250, 3, 21, '2026-10-19', 350.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(251, 3, 22, '2026-10-19', 110.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(252, 3, 23, '2026-10-19', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(253, 3, 24, '2026-10-19', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(254, 3, 25, '2026-10-19', 280.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(255, 3, 3, '2026-10-19', 230.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(256, 3, 4, '2026-10-19', 260.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(257, 3, 5, '2026-10-19', 230.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(258, 3, 6, '2026-10-19', 150.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(259, 3, 7, '2026-10-19', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(260, 3, 8, '2026-10-19', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(261, 3, 9, '2026-10-19', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(262, 3, 10, '2026-10-19', 180.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(263, 3, 11, '2026-10-19', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(264, 3, 12, '2026-10-19', 230.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(265, 3, 13, '2026-10-19', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(266, 3, 14, '2026-10-19', 350.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(267, 3, 15, '2026-10-19', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(268, 3, 16, '2026-10-19', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(269, 3, 17, '2026-10-19', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(270, 3, 18, '2026-10-19', 80.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(271, 3, 19, '2026-10-19', 180.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(272, 3, 20, '2026-10-19', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(273, 3, 21, '2026-10-19', 350.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(274, 3, 22, '2026-10-19', 110.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(275, 3, 23, '2026-10-19', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(276, 3, 24, '2026-10-19', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(277, 3, 25, '2026-10-19', 280.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(278, 3, 3, '2026-10-19', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(279, 3, 4, '2026-10-19', 250.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(280, 3, 5, '2026-10-19', 220.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(281, 3, 6, '2026-10-19', 150.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(282, 3, 7, '2026-10-19', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(283, 3, 8, '2026-10-19', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(284, 3, 9, '2026-10-19', 190.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(285, 3, 10, '2026-10-19', 170.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(286, 3, 11, '2026-10-19', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(287, 3, 12, '2026-10-19', 230.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(288, 3, 13, '2026-10-19', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(289, 3, 14, '2026-10-19', 350.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(290, 3, 15, '2026-10-19', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(291, 3, 16, '2026-10-19', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(292, 3, 17, '2026-10-19', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(293, 3, 18, '2026-10-19', 90.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(294, 3, 19, '2026-10-19', 180.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(295, 3, 20, '2026-10-19', 400.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(296, 3, 21, '2026-10-19', 350.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(297, 3, 22, '2026-10-19', 120.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(298, 3, 23, '2026-10-19', 400.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(299, 3, 24, '2026-10-19', 240.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(300, 3, 25, '2026-10-19', 280.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(301, 3, 3, '2026-10-19', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(302, 3, 4, '2026-10-19', 250.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(303, 3, 5, '2026-10-19', 220.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(304, 3, 6, '2026-10-19', 160.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(305, 3, 7, '2026-10-19', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(306, 3, 8, '2026-10-19', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(307, 3, 9, '2026-10-19', 190.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(308, 3, 10, '2026-10-19', 170.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(309, 3, 11, '2026-10-19', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(310, 3, 12, '2026-10-19', 230.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(311, 3, 13, '2026-10-19', 310.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(312, 3, 14, '2026-10-19', 350.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(313, 3, 15, '2026-10-19', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(314, 3, 16, '2026-10-19', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(315, 3, 17, '2026-10-19', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(316, 3, 18, '2026-10-19', 90.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(317, 3, 19, '2026-10-19', 180.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(318, 3, 20, '2026-10-19', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(319, 3, 21, '2026-10-19', 350.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(320, 3, 22, '2026-10-19', 120.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(321, 3, 23, '2026-10-19', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(322, 3, 24, '2026-10-19', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(323, 3, 25, '2026-10-19', 280.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(324, 3, 3, '2026-10-19', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(325, 3, 4, '2026-10-19', 250.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(326, 3, 5, '2026-10-19', 220.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(327, 3, 6, '2026-10-19', 160.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(328, 3, 7, '2026-10-19', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(329, 3, 8, '2026-10-19', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(330, 3, 9, '2026-10-19', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:38:59'),
(331, 3, 10, '2026-10-19', 180.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(332, 3, 11, '2026-10-19', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(333, 3, 12, '2026-10-19', 220.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(334, 3, 13, '2026-10-19', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(335, 3, 14, '2026-10-19', 350.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(336, 3, 15, '2026-10-19', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(337, 3, 16, '2026-10-19', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(338, 3, 17, '2026-10-19', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(339, 3, 18, '2026-10-19', 90.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(340, 3, 19, '2026-10-19', 180.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(341, 3, 20, '2026-10-19', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(342, 3, 21, '2026-10-19', 350.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(343, 3, 22, '2026-10-19', 120.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(344, 3, 23, '2026-10-19', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(345, 3, 24, '2026-10-19', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(346, 3, 25, '2026-10-19', 280.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(347, 3, 3, '2026-10-21', 280.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(348, 3, 4, '2026-10-21', 260.00, 'S-001', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(349, 3, 5, '2026-10-21', 220.00, 'S-001', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(350, 3, 6, '2026-10-21', 140.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(351, 3, 7, '2026-10-21', 210.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(352, 3, 8, '2026-10-21', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(353, 3, 9, '2026-10-21', 210.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(354, 3, 10, '2026-10-21', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(355, 3, 11, '2026-10-21', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(356, 3, 12, '2026-10-21', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(357, 3, 13, '2026-10-21', 270.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(358, 3, 14, '2026-10-21', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(359, 3, 15, '2026-10-21', 260.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(360, 3, 16, '2026-10-21', 230.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(361, 3, 17, '2026-10-21', 280.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(362, 3, 18, '2026-10-21', 90.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(363, 3, 19, '2026-10-21', 170.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(364, 3, 20, '2026-10-21', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(365, 3, 21, '2026-10-21', 300.00, 'S-001', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(366, 3, 22, '2026-10-21', 120.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(367, 3, 23, '2026-10-21', 150.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(368, 3, 24, '2026-10-21', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(369, 3, 25, '2026-10-21', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(370, 3, 3, '2026-10-21', 270.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(371, 3, 4, '2026-10-21', 260.00, 'S-002', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(372, 3, 5, '2026-10-21', 220.00, 'S-002', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(373, 3, 6, '2026-10-21', 140.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(374, 3, 7, '2026-10-21', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(375, 3, 8, '2026-10-21', 230.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(376, 3, 9, '2026-10-21', 210.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(377, 3, 10, '2026-10-21', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(378, 3, 11, '2026-10-21', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(379, 3, 12, '2026-10-21', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(380, 3, 13, '2026-10-21', 280.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(381, 3, 14, '2026-10-21', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(382, 3, 15, '2026-10-21', 260.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(383, 3, 16, '2026-10-21', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(384, 3, 17, '2026-10-21', 270.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(385, 3, 18, '2026-10-21', 90.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(386, 3, 19, '2026-10-21', 170.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(387, 3, 20, '2026-10-21', 400.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(388, 3, 21, '2026-10-21', 300.00, 'S-002', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(389, 3, 22, '2026-10-21', 120.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(390, 3, 23, '2026-10-21', 160.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(391, 3, 24, '2026-10-21', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(392, 3, 25, '2026-10-21', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00');
INSERT INTO `price_records` (`id`, `market_id`, `fish_species_id`, `observed_on`, `price_per_kg`, `vendor_code`, `source_type`, `source_reference`, `collection_time`, `product_condition`, `remarks`, `recorded_by`, `created_at`) VALUES
(393, 3, 3, '2026-10-21', 270.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(394, 3, 4, '2026-10-21', 270.00, 'S-003', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(395, 3, 5, '2026-10-21', 220.00, 'S-003', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(396, 3, 6, '2026-10-21', 140.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(397, 3, 7, '2026-10-21', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(398, 3, 8, '2026-10-21', 240.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(399, 3, 9, '2026-10-21', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(400, 3, 10, '2026-10-21', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(401, 3, 11, '2026-10-21', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(402, 3, 12, '2026-10-21', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(403, 3, 13, '2026-10-21', 280.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(404, 3, 14, '2026-10-21', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(405, 3, 15, '2026-10-21', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(406, 3, 16, '2026-10-21', 240.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(407, 3, 17, '2026-10-21', 270.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(408, 3, 18, '2026-10-21', 80.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(409, 3, 19, '2026-10-21', 180.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(410, 3, 20, '2026-10-21', 400.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(411, 3, 21, '2026-10-21', 300.00, 'S-003', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(412, 3, 22, '2026-10-21', 120.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(413, 3, 23, '2026-10-21', 160.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(414, 3, 24, '2026-10-21', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(415, 3, 25, '2026-10-21', 260.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(416, 3, 3, '2026-10-21', 270.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(417, 3, 4, '2026-10-21', 260.00, 'S-004', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(418, 3, 5, '2026-10-21', 220.00, 'S-004', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(419, 3, 6, '2026-10-21', 150.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(420, 3, 7, '2026-10-21', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(421, 3, 8, '2026-10-21', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(422, 3, 9, '2026-10-21', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(423, 3, 10, '2026-10-21', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(424, 3, 11, '2026-10-21', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(425, 3, 12, '2026-10-21', 210.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(426, 3, 13, '2026-10-21', 280.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(427, 3, 14, '2026-10-21', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(428, 3, 15, '2026-10-21', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(429, 3, 16, '2026-10-21', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(430, 3, 17, '2026-10-21', 270.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(431, 3, 18, '2026-10-21', 80.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(432, 3, 19, '2026-10-21', 180.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(433, 3, 20, '2026-10-21', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(434, 3, 21, '2026-10-21', 300.00, 'S-004', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(435, 3, 22, '2026-10-21', 110.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(436, 3, 23, '2026-10-21', 160.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(437, 3, 24, '2026-10-21', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(438, 3, 25, '2026-10-21', 260.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(439, 3, 3, '2026-10-21', 280.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(440, 3, 4, '2026-10-21', 260.00, 'S-005', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(441, 3, 5, '2026-10-21', 220.00, 'S-005', 'government_record', 'Estancia - Bantayan', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(442, 3, 6, '2026-10-21', 150.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(443, 3, 7, '2026-10-21', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(444, 3, 8, '2026-10-21', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(445, 3, 9, '2026-10-21', 220.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(446, 3, 10, '2026-10-21', 210.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(447, 3, 11, '2026-10-21', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(448, 3, 12, '2026-10-21', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(449, 3, 13, '2026-10-21', 270.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(450, 3, 14, '2026-10-21', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(451, 3, 15, '2026-10-21', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(452, 3, 16, '2026-10-21', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(453, 3, 17, '2026-10-21', 280.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(454, 3, 18, '2026-10-21', 80.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(455, 3, 19, '2026-10-21', 170.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(456, 3, 20, '2026-10-21', 490.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(457, 3, 21, '2026-10-21', 300.00, 'S-005', 'government_record', 'Estancia ilo-ilo', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(458, 3, 22, '2026-10-21', 110.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(459, 3, 23, '2026-10-21', 150.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(460, 3, 24, '2026-10-21', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(461, 3, 25, '2026-10-21', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(462, 3, 3, '2026-10-23', 280.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(463, 3, 4, '2026-10-23', 260.00, 'S-001', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(464, 3, 5, '2026-10-23', 200.00, 'S-001', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(465, 3, 6, '2026-10-23', 150.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(466, 3, 7, '2026-10-23', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(467, 3, 8, '2026-10-23', 240.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(468, 3, 9, '2026-10-23', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(469, 3, 10, '2026-10-23', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(470, 3, 11, '2026-10-23', 310.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(471, 3, 12, '2026-10-23', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(472, 3, 13, '2026-10-23', 300.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(473, 3, 14, '2026-10-23', 380.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(474, 3, 15, '2026-10-23', 280.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(475, 3, 16, '2026-10-23', 250.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(476, 3, 17, '2026-10-23', 310.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(477, 3, 18, '2026-10-23', 90.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(478, 3, 19, '2026-10-23', 180.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(479, 3, 20, '2026-10-23', 400.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(480, 3, 21, '2026-10-23', 360.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(481, 3, 22, '2026-10-23', 170.00, 'S-001', 'government_record', 'Estancia', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(482, 3, 23, '2026-10-23', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(483, 3, 24, '2026-10-23', 200.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(484, 3, 25, '2026-10-23', 260.00, 'S-001', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(485, 3, 3, '2026-10-23', 280.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(486, 3, 4, '2026-10-23', 260.00, 'S-002', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(487, 3, 5, '2026-10-23', 200.00, 'S-002', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(488, 3, 6, '2026-10-23', 150.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(489, 3, 7, '2026-10-23', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(490, 3, 8, '2026-10-23', 240.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(491, 3, 9, '2026-10-23', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(492, 3, 10, '2026-10-23', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(493, 3, 11, '2026-10-23', 310.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(494, 3, 12, '2026-10-23', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(495, 3, 13, '2026-10-23', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(496, 3, 14, '2026-10-23', 380.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(497, 3, 15, '2026-10-23', 300.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(498, 3, 16, '2026-10-23', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(499, 3, 17, '2026-10-23', 310.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(500, 3, 18, '2026-10-23', 90.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(501, 3, 19, '2026-10-23', 180.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(502, 3, 20, '2026-10-23', 420.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(503, 3, 21, '2026-10-23', 360.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(504, 3, 22, '2026-10-23', 170.00, 'S-002', 'government_record', 'Estancia', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(505, 3, 23, '2026-10-23', 200.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(506, 3, 24, '2026-10-23', 250.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(507, 3, 25, '2026-10-23', 260.00, 'S-002', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(508, 3, 3, '2026-10-23', 280.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(509, 3, 4, '2026-10-23', 260.00, 'S-003', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(510, 3, 5, '2026-10-23', 200.00, 'S-003', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(511, 3, 6, '2026-10-23', 150.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(512, 3, 7, '2026-10-23', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(513, 3, 8, '2026-10-23', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(514, 3, 9, '2026-10-23', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(515, 3, 10, '2026-10-23', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(516, 3, 11, '2026-10-23', 310.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(517, 3, 12, '2026-10-23', 250.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(518, 3, 13, '2026-10-23', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(519, 3, 14, '2026-10-23', 350.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(520, 3, 15, '2026-10-23', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(521, 3, 16, '2026-10-23', 240.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(522, 3, 17, '2026-10-23', 300.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(523, 3, 18, '2026-10-23', 90.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(524, 3, 19, '2026-10-23', 180.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(525, 3, 20, '2026-10-23', 420.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(526, 3, 21, '2026-10-23', 360.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(527, 3, 22, '2026-10-23', 170.00, 'S-003', 'government_record', 'Estancia', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(528, 3, 23, '2026-10-23', 200.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(529, 3, 24, '2026-10-23', 210.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(530, 3, 25, '2026-10-23', 260.00, 'S-003', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(531, 3, 3, '2026-10-23', 280.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(532, 3, 4, '2026-10-23', 260.00, 'S-004', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(533, 3, 5, '2026-10-23', 200.00, 'S-004', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(534, 3, 6, '2026-10-23', 150.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(535, 3, 7, '2026-10-23', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(536, 3, 8, '2026-10-23', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(537, 3, 9, '2026-10-23', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(538, 3, 10, '2026-10-23', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(539, 3, 11, '2026-10-23', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(540, 3, 12, '2026-10-23', 240.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(541, 3, 13, '2026-10-23', 310.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(542, 3, 14, '2026-10-23', 350.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(543, 3, 15, '2026-10-23', 280.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(544, 3, 16, '2026-10-23', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(545, 3, 17, '2026-10-23', 300.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(546, 3, 18, '2026-10-23', 90.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(547, 3, 19, '2026-10-23', 170.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(548, 3, 20, '2026-10-23', 400.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(549, 3, 21, '2026-10-23', 350.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(550, 3, 22, '2026-10-23', 170.00, 'S-004', 'government_record', 'Estancia', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(551, 3, 23, '2026-10-23', 200.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(552, 3, 24, '2026-10-23', 260.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(553, 3, 25, '2026-10-23', 250.00, 'S-004', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(554, 3, 3, '2026-10-23', 270.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(555, 3, 4, '2026-10-23', 260.00, 'S-005', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(556, 3, 5, '2026-10-23', 210.00, 'S-005', 'government_record', 'Bantayan Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(557, 3, 6, '2026-10-23', 150.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(558, 3, 7, '2026-10-23', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(559, 3, 8, '2026-10-23', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(560, 3, 9, '2026-10-23', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(561, 3, 10, '2026-10-23', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(562, 3, 11, '2026-10-23', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(563, 3, 12, '2026-10-23', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(564, 3, 13, '2026-10-23', 310.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(565, 3, 14, '2026-10-23', 350.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(566, 3, 15, '2026-10-23', 280.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(567, 3, 16, '2026-10-23', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(568, 3, 17, '2026-10-23', 300.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(569, 3, 18, '2026-10-23', 90.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(570, 3, 19, '2026-10-23', 170.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(571, 3, 20, '2026-10-23', 400.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(572, 3, 21, '2026-10-23', 350.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(573, 3, 22, '2026-10-23', 170.00, 'S-005', 'government_record', 'Estancia', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(574, 3, 23, '2026-10-23', 200.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(575, 3, 24, '2026-10-23', 240.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00'),
(576, 3, 25, '2026-10-23', 250.00, 'S-005', 'government_record', 'Madridejos Area', NULL, NULL, NULL, 1, '2026-11-12 13:39:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','encoder','viewer') NOT NULL DEFAULT 'viewer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password_hash`, `role`, `created_at`, `profile_picture`) VALUES
(1, 'Cheska Marie Jumantoc', 'marie@gmail.com', '$2y$10$P3BYEVYcHoz4dXaBKsUEguswPuCibo1OeAwaVKVLkZupH0DEpfQq.', 'admin', '2026-10-19 13:04:15', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fish_species`
--
ALTER TABLE `fish_species`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `forecasts`
--
ALTER TABLE `forecasts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_forecast` (`forecast_run_id`,`market_id`,`fish_species_id`,`target_date`),
  ADD KEY `market_id` (`market_id`),
  ADD KEY `fish_species_id` (`fish_species_id`);

--
-- Indexes for table `forecast_runs`
--
ALTER TABLE `forecast_runs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `markets`
--
ALTER TABLE `markets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_market` (`name`,`municipality`);

--
-- Indexes for table `model_evaluations`
--
ALTER TABLE `model_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `forecast_run_id` (`forecast_run_id`),
  ADD KEY `market_id` (`market_id`),
  ADD KEY `fish_species_id` (`fish_species_id`);

--
-- Indexes for table `price_records`
--
ALTER TABLE `price_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_observation` (`market_id`,`fish_species_id`,`observed_on`,`vendor_code`),
  ADD KEY `fk_price_user` (`recorded_by`),
  ADD KEY `idx_price_history` (`fish_species_id`,`market_id`,`observed_on`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fish_species`
--
ALTER TABLE `fish_species`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `forecasts`
--
ALTER TABLE `forecasts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `forecast_runs`
--
ALTER TABLE `forecast_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `markets`
--
ALTER TABLE `markets`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `model_evaluations`
--
ALTER TABLE `model_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_records`
--
ALTER TABLE `price_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=577;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `forecasts`
--
ALTER TABLE `forecasts`
  ADD CONSTRAINT `forecasts_ibfk_1` FOREIGN KEY (`forecast_run_id`) REFERENCES `forecast_runs` (`id`),
  ADD CONSTRAINT `forecasts_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `markets` (`id`),
  ADD CONSTRAINT `forecasts_ibfk_3` FOREIGN KEY (`fish_species_id`) REFERENCES `fish_species` (`id`);

--
-- Constraints for table `model_evaluations`
--
ALTER TABLE `model_evaluations`
  ADD CONSTRAINT `model_evaluations_ibfk_1` FOREIGN KEY (`forecast_run_id`) REFERENCES `forecast_runs` (`id`),
  ADD CONSTRAINT `model_evaluations_ibfk_2` FOREIGN KEY (`market_id`) REFERENCES `markets` (`id`),
  ADD CONSTRAINT `model_evaluations_ibfk_3` FOREIGN KEY (`fish_species_id`) REFERENCES `fish_species` (`id`);

--
-- Constraints for table `price_records`
--
ALTER TABLE `price_records`
  ADD CONSTRAINT `fk_price_market` FOREIGN KEY (`market_id`) REFERENCES `markets` (`id`),
  ADD CONSTRAINT `fk_price_species` FOREIGN KEY (`fish_species_id`) REFERENCES `fish_species` (`id`),
  ADD CONSTRAINT `fk_price_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
