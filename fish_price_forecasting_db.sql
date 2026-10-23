-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 01:09 PM
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
(2, 'lizardfishssss', 'Synodontidae,ssss', 'Karahodsdsds', 1);

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
(2, 'dsadsa', 'Madridejos', 'Cebu', 1);

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
) ;

--
-- Dumping data for table `price_records`
--

INSERT INTO `price_records` (`id`, `market_id`, `fish_species_id`, `observed_on`, `price_per_kg`, `vendor_code`, `source_type`, `source_reference`, `collection_time`, `product_condition`, `remarks`, `recorded_by`, `created_at`) VALUES
(1, 1, 1, '2026-10-19', 200.00, 'v001', 'vendor_interview', 'dsd', '21:32:00', 'dsads', NULL, 1, '2026-10-19 13:32:17');

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
(1, 'Cheska Marie Jumantoc', 'marie@gmail.com', '$2y$10$eOBAvZQDHGwv36.nFBgGBOh3ouS5n27k9cQdF5zXyFQ41XUXjeOli', 'admin', '2026-10-19 13:04:15', NULL);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `model_evaluations`
--
ALTER TABLE `model_evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_records`
--
ALTER TABLE `price_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

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
