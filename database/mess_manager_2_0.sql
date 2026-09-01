-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 30, 2026 at 07:16 AM
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
-- Database: `mess manager 2.0`
--

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `deposit_id` int(11) NOT NULL,
  `mess_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `deposit_date` date NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`deposit_id`, `mess_id`, `user_id`, `amount`, `deposit_date`, `note`, `created_at`) VALUES
(1, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:09:27'),
(2, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:17:39'),
(3, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:17:51'),
(4, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:24:51'),
(5, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:42:36'),
(6, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:49:41'),
(7, 2, 3, 500.00, '2026-08-28', '', '2026-08-28 13:53:54'),
(8, 3, 4, 500.00, '2026-08-30', '', '2026-08-30 04:26:27'),
(9, 3, 4, 1000000.00, '2026-08-30', '', '2026-08-30 04:52:47');

-- --------------------------------------------------------

--
-- Table structure for table `meal_expenses`
--

CREATE TABLE `meal_expenses` (
  `meal_expense_id` int(11) NOT NULL,
  `mess_id` int(11) NOT NULL,
  `month_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `market_list` text DEFAULT NULL,
  `market_user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_expenses`
--

INSERT INTO `meal_expenses` (`meal_expense_id`, `mess_id`, `month_id`, `expense_date`, `amount`, `market_list`, `market_user_id`, `created_at`) VALUES
(1, 3, 3, '2026-08-30', 500.00, '', 4, '2026-08-30 04:22:31'),
(2, 3, 3, '2026-08-30', 5000.00, '', 4, '2026-08-30 05:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `messes`
--

CREATE TABLE `messes` (
  `mess_id` int(11) NOT NULL,
  `mess_name` varchar(150) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messes`
--

INSERT INTO `messes` (`mess_id`, `mess_name`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Shahriar', 1, '2026-08-26 03:13:20', '2026-08-26 03:13:20'),
(2, 'Shahriar', 3, '2026-08-28 13:07:56', '2026-08-28 13:07:56'),
(3, 'Faysal', 4, '2026-08-30 03:55:29', '2026-08-30 03:55:29');

-- --------------------------------------------------------

--
-- Table structure for table `mess_members`
--

CREATE TABLE `mess_members` (
  `member_id` int(11) NOT NULL,
  `mess_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('manager','member') NOT NULL DEFAULT 'member',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mess_members`
--

INSERT INTO `mess_members` (`member_id`, `mess_id`, `user_id`, `role`, `joined_at`) VALUES
(1, 1, 1, 'manager', '2026-08-26 03:13:20'),
(2, 1, 2, 'member', '2026-08-26 03:49:41'),
(3, 2, 3, 'manager', '2026-08-28 13:07:56'),
(4, 3, 4, 'manager', '2026-08-30 03:55:29');

-- --------------------------------------------------------

--
-- Table structure for table `mess_months`
--

CREATE TABLE `mess_months` (
  `month_id` int(11) NOT NULL,
  `mess_id` int(11) NOT NULL,
  `month_name` varchar(50) NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mess_months`
--

INSERT INTO `mess_months` (`month_id`, `mess_id`, `month_name`, `status`, `started_at`, `closed_at`) VALUES
(1, 1, 'September', 'active', '2026-08-26 03:13:20', NULL),
(2, 2, 'September', 'active', '2026-08-28 13:07:56', NULL),
(3, 3, 'September', 'active', '2026-08-30 03:55:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `other_expenses`
--

CREATE TABLE `other_expenses` (
  `other_expense_id` int(11) NOT NULL,
  `mess_id` int(11) NOT NULL,
  `month_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `expense_list` text NOT NULL,
  `expense_type` enum('joint','individual') NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `other_expense_members`
--

CREATE TABLE `other_expense_members` (
  `id` int(11) NOT NULL,
  `other_expense_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `profile_image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Shahriar Hasan', 'shahriarh398@gmail.com', NULL, '$2y$10$62pZw0964MX4JH6XaIbsu.yUp5zHoEmpQfWUw7g.lhFkQwtg13gL2', NULL, 'active', '2026-08-25 16:08:53', '2026-08-25 16:08:53'),
(2, 'Shahriar', 'asdf@gmail.com', NULL, '$2y$10$408o.OkUcv1zNrAPMHpTOuvq/oUYOBT9ZHqcAfqvmT7RQCQge.SBe', NULL, 'active', '2026-08-26 03:04:43', '2026-08-26 03:04:43'),
(3, 'Hasan', 'asdfg@gmail.com', NULL, '$2y$10$7QMUtm1sSzGMM6hGpeQ0Nucms5eN2PTGJkZjYi8RV7mkTBGveSUZy', NULL, 'active', '2026-08-26 03:52:01', '2026-08-26 03:52:01'),
(4, 'Far', 'far1@gmail.com', NULL, '$2y$10$A.eucP/XGIRPyFFP1b7qSeHwC7yLSI/b8SFa32EJserPx9dYeX0Wi', NULL, 'active', '2026-08-30 03:54:17', '2026-08-30 03:54:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`deposit_id`);

--
-- Indexes for table `meal_expenses`
--
ALTER TABLE `meal_expenses`
  ADD PRIMARY KEY (`meal_expense_id`);

--
-- Indexes for table `messes`
--
ALTER TABLE `messes`
  ADD PRIMARY KEY (`mess_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `mess_members`
--
ALTER TABLE `mess_members`
  ADD PRIMARY KEY (`member_id`),
  ADD KEY `mess_id` (`mess_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mess_months`
--
ALTER TABLE `mess_months`
  ADD PRIMARY KEY (`month_id`),
  ADD KEY `mess_id` (`mess_id`);

--
-- Indexes for table `other_expenses`
--
ALTER TABLE `other_expenses`
  ADD PRIMARY KEY (`other_expense_id`);

--
-- Indexes for table `other_expense_members`
--
ALTER TABLE `other_expense_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `deposit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `meal_expenses`
--
ALTER TABLE `meal_expenses`
  MODIFY `meal_expense_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `messes`
--
ALTER TABLE `messes`
  MODIFY `mess_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mess_members`
--
ALTER TABLE `mess_members`
  MODIFY `member_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mess_months`
--
ALTER TABLE `mess_months`
  MODIFY `month_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `other_expenses`
--
ALTER TABLE `other_expenses`
  MODIFY `other_expense_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `other_expense_members`
--
ALTER TABLE `other_expense_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `messes`
--
ALTER TABLE `messes`
  ADD CONSTRAINT `messes_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `mess_members`
--
ALTER TABLE `mess_members`
  ADD CONSTRAINT `mess_members_ibfk_1` FOREIGN KEY (`mess_id`) REFERENCES `messes` (`mess_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mess_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `mess_months`
--
ALTER TABLE `mess_months`
  ADD CONSTRAINT `mess_months_ibfk_1` FOREIGN KEY (`mess_id`) REFERENCES `messes` (`mess_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
