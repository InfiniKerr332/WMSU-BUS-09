-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 14, 2025 at 03:53 AM
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
-- Database: `wmsu_bus_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_alerts`
--

CREATE TABLE `admin_alerts` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `alert_type` enum('deadline_warning','72hour_approaching') DEFAULT 'deadline_warning',
  `alert_sent` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `deadline_datetime` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `bus_name` varchar(50) NOT NULL,
  `plate_no` varchar(20) NOT NULL,
  `capacity` int(11) DEFAULT 30,
  `status` enum('available','unavailable') DEFAULT 'available',
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_name`, `plate_no`, `capacity`, `status`, `deleted`, `deleted_at`, `created_at`) VALUES
(1, 'WMSU Bus 1', 'ABC-1234', 30, 'available', 0, NULL, '2025-10-13 22:23:46'),
(2, 'WMSU Bus 2', 'XYZ-5678', 30, 'available', 0, NULL, '2025-10-13 22:23:46'),
(3, 'WMSU Bus 3', 'DEF-9012', 30, 'available', 0, NULL, '2025-10-13 22:23:46'),
(10, 'Wmsu Bus 4', 'ACD-2324', 30, 'available', 0, NULL, '2025-11-06 03:36:43'),
(11, 'Wmsu Bus 5', 'DW1-4921', 40, 'available', 0, NULL, '2025-11-06 03:37:01'),
(12, 'WMSU Bus 6', '2016', 35, 'available', 0, NULL, '2025-11-06 12:51:08'),
(13, 'WMSU Bus 7', 'YEW-2145', 45, 'available', 0, NULL, '2025-11-07 04:38:20'),
(14, 'WMSU BUS X', 'AE2-314', 35, 'available', 0, NULL, '2025-11-07 13:12:38');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `assigned_bus_id` int(11) DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `license_no` varchar(50) DEFAULT NULL,
  `status` enum('available','unavailable') DEFAULT 'available',
  `deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `assigned_bus_id`, `contact_no`, `license_no`, `status`, `deleted`, `deleted_at`, `created_at`) VALUES
(1, 'Juan Dela Cruz', 1, '09111111111', 'N01-12-123456', 'available', 0, NULL, '2025-10-13 22:23:46'),
(2, 'Pedro Santos', 2, '09222222222', 'N02-13-234567', 'available', 0, NULL, '2025-10-13 22:23:46'),
(3, 'Maria Garcia', 3, '09333333333', 'N03-14-345678', 'available', 0, NULL, '2025-10-13 22:23:46'),
(6, 'Mark cordovilla', 10, '09936692953', 'N32-2122-334543', 'available', 0, NULL, '2025-11-06 12:12:29'),
(7, 'Kurt Ortega', NULL, '09949210421', 'N13-2792-3348973', 'available', 0, NULL, '2025-11-06 23:38:22'),
(8, 'Zambales', NULL, '09928374615', 'KE2-3214-2135125', 'available', 1, '2025-11-07 06:46:21', '2025-11-07 06:46:15');

-- --------------------------------------------------------

--
-- Table structure for table `driver_assignments`
--

CREATE TABLE `driver_assignments` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `assignment_status` enum('pending','accepted','rejected','completed') DEFAULT 'pending',
  `notified_at` timestamp NULL DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_logs`
--

CREATE TABLE `email_logs` (
  `id` int(11) NOT NULL,
  `recipient` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `status` enum('sent','failed','pending') DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_logs`
--

INSERT INTO `email_logs` (`id`, `recipient`, `subject`, `status`, `error_message`, `created_at`) VALUES
(1, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:54:56'),
(2, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:55:08'),
(3, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:55:12'),
(4, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:55:17'),
(5, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:56:55'),
(6, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:56:58'),
(7, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'failed', 'SMTP Error: Could not authenticate.', '2025-11-19 00:57:08'),
(8, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-11-19 01:08:06'),
(9, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 01:36:32'),
(10, 'admin@wmsu.edu.ph', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-11-19 01:41:32'),
(11, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-11-19 01:50:47'),
(12, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 02:48:36'),
(13, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 02:49:46'),
(14, 'admin@wmsu.edu.ph', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-11-19 02:59:05'),
(15, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 03:00:20'),
(16, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 03:02:46'),
(17, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 03:04:09'),
(18, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-11-19 14:48:55'),
(19, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-11-19 14:49:45'),
(20, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 14:53:26'),
(21, 'admin@wmsu.edu.ph', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-11-19 14:53:41'),
(22, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 14:54:21'),
(23, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:08:32'),
(24, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:12:21'),
(25, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:13:07'),
(26, 'admin@wmsu.edu.ph', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-11-19 15:13:20'),
(27, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:18:38'),
(28, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:18:49'),
(29, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 15:24:09'),
(30, 'admin@wmsu.edu.ph', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-11-19 15:25:21'),
(31, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-11-19 22:08:33'),
(32, 'admin@wmsu.edu.ph', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-11-19 23:51:29'),
(33, 'ae202401442@wmsu.edu.ph', 'Reservation Approved', 'sent', NULL, '2025-11-19 23:52:41'),
(34, 'admin@wmsu.edu.ph', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-11-20 00:03:51'),
(35, 'ae202401442@wmsu.edu.ph', 'Reservation Update', 'sent', NULL, '2025-11-20 00:06:51'),
(36, 'admin@wmsu.edu.ph', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-08 01:15:33'),
(37, 'ae202401442@wmsu.edu.ph', 'Reservation Cancelled', 'sent', NULL, '2025-12-08 02:13:59'),
(38, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-08 02:31:18'),
(39, 'ae202401442@wmsu.edu.ph', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-08 02:41:21'),
(40, 'ae202401442@wmus.edu.ph', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-08 02:56:04'),
(41, 'ae202401442@wmsu.edu.ph', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-08 02:57:06'),
(42, 'ae202401442@wmsu.edu.ph', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-08 03:20:15'),
(43, 'ae202401442@wmsu.edu.ph', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-08 03:33:32'),
(44, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 12:10:31'),
(45, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:24:04'),
(46, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:43:43'),
(47, 'kerrzaragoza43@gmail.com', 'New User Verification - Kerr', 'sent', NULL, '2025-12-13 12:45:17'),
(48, 'kerrzaragoza43@gmail.com', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 12:45:20'),
(49, 'ae202401442@wmsu.edu.ph', 'Account Rejected - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:53:25'),
(50, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:54:54'),
(51, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:55:52'),
(52, 'kerrzaragoza43@gmail.com', 'New User Verification - Kerr', 'sent', NULL, '2025-12-13 12:56:25'),
(53, 'kerrzaragoza43@gmail.com', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 12:56:28'),
(54, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 12:57:38'),
(55, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 13:04:47'),
(56, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 13:14:58'),
(57, 'ae202401442@wmsu.edu.ph', 'Reservation Approved - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 13:15:37'),
(58, 'ae202401442@wmsu.edu.ph', 'Reservation Cancelled', 'sent', NULL, '2025-12-13 13:18:42'),
(59, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 13:43:39'),
(60, 'ae202401442@wmsu.edu.ph', 'Reservation Approved - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 13:44:24'),
(61, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 13:49:13'),
(62, 'ae202401442@wmsu.edu.ph', 'Reservation Cancelled - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 13:50:17'),
(63, 'kerrzaragoza43@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 14:22:59'),
(64, 'ae202401442@wmsu.edu.ph', 'Reservation Approved - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 14:24:21'),
(65, 'kerrzaragoza43@gmail.com', 'WMSU Bus System - Test Email', 'sent', NULL, '2025-12-13 15:31:33'),
(66, 'ae202401442@wmsu.edu.ph', 'Reservation Deleted - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:33:33'),
(67, 'ae202401442@wmsu.edu.ph', 'Reservation Deleted - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:33:36'),
(68, 'ae202401442@wmsu.edu.ph', 'Reservation Deleted - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:33:41'),
(69, 'ae202401442@wmsu.edu.ph', 'Reservation Deleted - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:33:44'),
(70, 'ae202401442@wmsu.edu.ph', 'Account Deleted - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 15:33:48'),
(71, 'ae202401442@wmsu.edu.ph', 'Verify Your Email - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 15:36:16'),
(72, 'wmsubussystem@gmail.com', 'New User Verification - Kerr Xandrex Chua Zaragoza', 'sent', NULL, '2025-12-13 15:45:04'),
(73, 'wmsubussystem@gmail.com', 'New User Pending Approval - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:45:07'),
(74, 'ae202401442@wmsu.edu.ph', 'Account Approved - WMSU Bus Reserve System', 'sent', NULL, '2025-12-13 15:47:38'),
(75, 'wmsubussystem@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-13 15:49:34'),
(76, 'ae202401442@wmsu.edu.ph', 'Reservation Approved - WMSU Bus Reserve', 'sent', NULL, '2025-12-13 15:50:21'),
(77, 'wmsubussystem@gmail.com', 'New Bus Reservation - Driver Assignment Needed', 'sent', NULL, '2025-12-14 02:49:40'),
(78, 'ae20242831@wmsu.edu.ph', 'Reservation Approved - WMSU Bus Reserve', 'sent', NULL, '2025-12-14 02:50:35');

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `verification_token` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `token_expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('reservation','approval','rejection','cancellation','reminder','driver_assignment','verification') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 4, 'approval', '👤 New User Registration', 'Kerr Xandrex Zaragoza (ae202401442@wmsu.edu.ph) has registered and needs approval', 'admin/users.php?view=5', 1, '2025-11-19 01:41:28'),
(2, 4, 'approval', '👤 New User Registration', 'Kerr Xandrex Chua Zaragoza (ae202401442@wmsu.edu.ph) has registered and needs approval', 'admin/users.php?view=9', 1, '2025-11-19 02:59:01'),
(3, 4, 'approval', '👤 New User Registration', 'Kerr Xandrex Chua Zaragoza (ae202401442@wmsu.edu.ph) has registered and needs approval', 'admin/users.php?view=10', 1, '2025-11-19 14:53:37'),
(4, 4, 'approval', '👤 New User Registration', 'Kerr Xandrex Chua Zaragoza (ae202401442@wmsu.edu.ph) has registered and needs approval', 'admin/users.php?view=11', 1, '2025-11-19 15:13:17'),
(5, 4, 'approval', '👤 New User Registration', 'Kerr Xandrex Chua Zaragoza (ae202401442@wmsu.edu.ph) has registered and needs approval', 'admin/users.php?view=12', 1, '2025-11-19 15:25:17'),
(6, 12, 'approval', '✅ Reservation Approved', 'November 24, 2025 - Zamboanga City', 'student/my_reservations.php?view=15', 1, '2025-11-19 23:52:36'),
(7, 12, 'rejection', '❌ Reservation Not Approved', 'November 24, 2025 - No reason provided', 'student/reserve.php', 1, '2025-11-20 00:06:47'),
(8, 4, 'approval', 'New User: Kerr', 'Email verified - Awaiting approval', 'admin/users.php?view=13', 1, '2025-12-13 12:45:12'),
(9, 4, 'approval', 'New User: Kerr', 'Email verified - Awaiting approval', 'admin/users.php?view=14', 1, '2025-12-13 12:56:21'),
(10, 14, 'reservation', '✅ Reservation Submitted', 'Your reservation for December 19, 2025 is pending admin approval', 'student/my_reservations.php?view=21', 1, '2025-12-13 13:14:54'),
(11, 4, 'reservation', '🆕 New Reservation from Kerr', 'December 19, 2025 to Zamboanga City - Awaiting approval', 'admin/reservations.php?view=21', 1, '2025-12-13 13:14:54'),
(12, 14, 'approval', '✅ Reservation Approved', 'December 19, 2025 - Zamboanga City', 'student/my_reservations.php?view=21', 1, '2025-12-13 13:15:33'),
(13, 14, 'reservation', 'Reservation Submitted', 'Your reservation for December 25, 2025 is pending admin approval', 'student/my_reservations.php?view=22', 1, '2025-12-13 13:43:33'),
(14, 4, 'reservation', 'New Reservation from Kerr', 'December 25, 2025 to Mercedes - Awaiting approval', 'admin/reservations.php?view=22', 1, '2025-12-13 13:43:33'),
(15, 14, 'approval', 'Reservation Approved', 'December 25, 2025 - Mercedes', 'student/my_reservations.php?view=22', 1, '2025-12-13 13:44:21'),
(16, 14, 'reservation', 'Reservation Submitted', 'Your reservation for December 19, 2025 is pending admin approval', 'student/my_reservations.php?view=23', 1, '2025-12-13 13:49:10'),
(17, 4, 'reservation', 'New Reservation from Kerr', 'December 19, 2025 to Museum, Town Z.C - Awaiting approval', 'admin/reservations.php?view=23', 1, '2025-12-13 13:49:10'),
(18, 14, 'cancellation', 'Reservation Cancelled', 'Your reservation for December 19, 2025 has been cancelled', 'student/my_reservations.php', 1, '2025-12-13 13:50:13'),
(19, 4, 'cancellation', 'Reservation Cancelled by Kerr', 'December 19, 2025 to Museum, Town Z.C', 'admin/reservations.php', 1, '2025-12-13 13:50:13'),
(20, 14, 'reservation', 'Reservation Submitted', 'Your reservation for December 18, 2025 is pending admin approval', 'student/my_reservations.php?view=24', 0, '2025-12-13 14:22:55'),
(21, 4, 'reservation', 'New Reservation from Kerr', 'December 18, 2025 to Bolong - Awaiting approval', 'admin/reservations.php?view=24', 1, '2025-12-13 14:22:55'),
(22, 14, 'approval', 'Reservation Approved', 'December 18, 2025 - Bolong', 'student/my_reservations.php?view=24', 0, '2025-12-13 14:24:17'),
(23, 14, 'cancellation', 'Reservation Deleted by Admin', 'December 19, 2025 - we', 'student/reserve.php', 0, '2025-12-13 15:33:29'),
(24, 14, 'cancellation', 'Reservation Deleted by Admin', 'December 25, 2025 - we', 'student/reserve.php', 0, '2025-12-13 15:33:33'),
(25, 14, 'cancellation', 'Reservation Deleted by Admin', 'December 19, 2025 - we', 'student/reserve.php', 0, '2025-12-13 15:33:36'),
(26, 14, 'cancellation', 'Reservation Deleted by Admin', 'December 18, 2025 - we', 'student/reserve.php', 1, '2025-12-13 15:33:41'),
(27, 4, 'approval', 'New User: Kerr', 'Email verified - Review employee/teacher ID for approval', 'admin/users.php?view=15', 1, '2025-12-13 15:45:00'),
(28, 15, 'reservation', 'Reservation Submitted', 'Your reservation for December 17, 2025 is pending admin approval', 'student/my_reservations.php?view=25', 1, '2025-12-13 15:49:31'),
(29, 4, 'reservation', 'New Reservation from Kerr', 'December 17, 2025 to Bolong - Awaiting approval', 'admin/reservations.php?view=25', 1, '2025-12-13 15:49:31'),
(30, 15, 'approval', 'Reservation Approved', 'December 17, 2025 - Bolong', 'student/my_reservations.php?view=25', 1, '2025-12-13 15:50:16'),
(31, 17, 'reservation', 'Reservation Submitted', 'Your reservation for December 18, 2025 is pending admin approval', 'student/my_reservations.php?view=26', 0, '2025-12-14 02:49:34'),
(32, 4, 'reservation', 'New Reservation from Test', 'December 18, 2025 to DICT behind of city hall - Awaiting approval', 'admin/reservations.php?view=26', 1, '2025-12-14 02:49:34'),
(33, 17, 'approval', 'Reservation Approved', 'December 18, 2025 - DICT behind of city hall', 'student/my_reservations.php?view=26', 0, '2025-12-14 02:50:31');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bus_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `purpose` text NOT NULL,
  `destination` varchar(255) NOT NULL,
  `reservation_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `reservation_time` time NOT NULL,
  `return_time` time DEFAULT NULL,
  `passenger_count` int(11) DEFAULT 1,
  `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
  `admin_remarks` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `reminder_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `bus_id`, `driver_id`, `purpose`, `destination`, `reservation_date`, `return_date`, `reservation_time`, `return_time`, `passenger_count`, `status`, `admin_remarks`, `approved_by`, `approved_at`, `reminder_sent`, `created_at`) VALUES
(3, 6, 1, 1, 'official business meeting', 'Zamboanga City Hall', '2025-10-31', '2025-10-31', '10:00:00', '11:30:00', 20, 'approved', '', 4, '2025-10-28 00:18:04', 0, '2025-10-27 14:10:09'),
(25, 15, 1, 1, 'Meeting', 'Bolong', '2025-12-17', '2025-12-23', '12:48:00', '13:49:00', 30, 'approved', 'Good job', 4, '2025-12-13 15:50:16', 0, '2025-12-13 15:49:31'),
(26, 17, 14, 3, 'meeting', 'DICT behind of city hall', '2025-12-18', '2025-12-24', '10:49:00', '11:49:00', 35, 'approved', '', 4, '2025-12-14 02:50:31', 0, '2025-12-14 02:49:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `employee_id_image` varchar(255) DEFAULT NULL,
  `employee_id_back_image` varchar(255) DEFAULT NULL,
  `account_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `verification_token` varchar(64) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL,
  `approved_by_admin` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `role` enum('student','employee','teacher','admin') DEFAULT 'student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `middle_name`, `last_name`, `name`, `email`, `contact_no`, `department`, `position`, `password`, `employee_id_image`, `employee_id_back_image`, `account_status`, `email_verified`, `email_verified_at`, `verification_token`, `verification_expires`, `approved_by_admin`, `approved_at`, `rejection_reason`, `role`, `created_at`) VALUES
(4, 'WMSU', NULL, 'Administrator', 'WMSU Administrator', 'admin@wmsu.edu.ph', '09123456789', 'Administration', 'System Administrator', '$2y$10$2QzlHxcObiLkyKPuFv3oQunxAY9wfREBieNBPCr733vkTqsmsUHcK', NULL, NULL, 'approved', 0, NULL, NULL, NULL, NULL, '2025-10-27 08:29:20', NULL, 'admin', '2025-10-14 00:37:16'),
(6, 'Kerr', 'Xandrex Chua', 'Zaragoza', 'Kerr Xandrex Chua Zaragoza', 'ae202401443@wmsu.edu.ph', '09471028557', 'CCS', 'Teacher', '$2y$10$brPnuMYdtZCo.eYga7E75OBUndyDJeXqo96PjbqugPD.dYHO7VhHu', 'uploads/employee_ids/emp_front_1761571747_68ff73a35a775.png', 'uploads/employee_ids/emp_back_1761571747_68ff73a35ac76.png', 'approved', 0, NULL, NULL, NULL, 4, '2025-10-27 13:37:39', NULL, 'teacher', '2025-10-27 13:29:07'),
(7, 'Kerr', 'Xandrex Chua', 'Zaragoza', 'Kerr Xandrex Chua Zaragoza', 'ae202401444@wmsu.edu.ph', '09653188726', 'CCS', 'Employee', '$2y$10$pSA361j85Do.yW21GMMUxuWNQPxQ31VhDX06kwvJgysPyJmha3uk6', 'uploads/employee_ids/emp_front_1761572333_68ff75ed82194.png', 'uploads/employee_ids/emp_back_1761572333_68ff75ed8287e.png', 'rejected', 0, NULL, NULL, NULL, 4, '2025-11-06 12:51:53', 'Not valid.', 'employee', '2025-10-27 13:38:53'),
(8, NULL, NULL, NULL, 'Kurt  Ortega', 'ae20240214@wmsu.edu.ph', '09936437834', 'CCS', 'Employee', '$2y$10$3b9o/db1XD5FTTAXgRQAf.Sg1dugdg98LeUDBlFAbRR7uwDeqEolq', 'uploads/employee_ids/emp_front_1762497872_690d955034fd1.png', 'uploads/employee_ids/emp_back_1762497872_690d9550353b6.png', 'pending', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'employee', '2025-11-07 06:44:32'),
(15, NULL, NULL, NULL, 'Kerr Xandrex Chua Zaragoza', 'ae202401442@wmsu.edu.ph', '09653188726', 'BSIT', 'Teacher', '$2y$10$egi2LxRb0aVykcYTsLOLzelaGHlNyqo/ZsfJL6Hg53XsOqWh7gp/O', 'uploads/employee_ids/emp_front_1765640172_693d87ec9b69f.jpg', 'uploads/employee_ids/emp_back_1765640172_693d87ec9d20a.jpg', 'approved', 1, '2025-12-13 15:45:00', NULL, '2025-12-14 23:36:12', 4, '2025-12-13 15:47:34', NULL, 'teacher', '2025-12-13 15:36:12'),
(17, NULL, NULL, NULL, 'Test Teacher User', 'ae20242831@wmsu.edu.ph', '09123456789', 'CCS', 'Teacher', '$2y$10$2QzlHxcObiLkyKPuFv3oQunxAY9wfREBieNBPCr733vkTqsmsUHcK', NULL, NULL, 'approved', 1, '2025-12-14 02:48:32', NULL, NULL, NULL, NULL, NULL, 'teacher', '2025-12-14 02:48:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `alert_sent` (`alert_sent`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `plate_no` (`plate_no`),
  ADD KEY `idx_deleted` (`deleted`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_bus_id` (`assigned_bus_id`),
  ADD KEY `idx_deleted` (`deleted`);

--
-- Indexes for table `driver_assignments`
--
ALTER TABLE `driver_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `driver_id` (`driver_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipient` (`recipient`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `verification_token` (`verification_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_verified` (`is_verified`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_approved_by` (`approved_by_admin`),
  ADD KEY `idx_verification_token` (`verification_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `driver_assignments`
--
ALTER TABLE `driver_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_logs`
--
ALTER TABLE `email_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_alerts`
--
ALTER TABLE `admin_alerts`
  ADD CONSTRAINT `fk_admin_alerts_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `drivers`
--
ALTER TABLE `drivers`
  ADD CONSTRAINT `drivers_ibfk_1` FOREIGN KEY (`assigned_bus_id`) REFERENCES `buses` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `driver_assignments`
--
ALTER TABLE `driver_assignments`
  ADD CONSTRAINT `fk_driver_assignments_driver` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_driver_assignments_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `fk_email_verifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservations_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by_admin`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
