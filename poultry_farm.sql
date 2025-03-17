-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 14, 2025 at 09:27 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `poultry_farm`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','manager','staff') NOT NULL DEFAULT 'staff',
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `full_name`, `role`, `phone`, `status`, `last_login`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'System Administrator', 'admin', '1234567890', 'active', NULL, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `flocks`
--

CREATE TABLE `flocks` (
  `id` int(11) NOT NULL,
  `flock_id` varchar(20) NOT NULL,
  `breed` varchar(50) NOT NULL,
  `batch_name` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `acquisition_date` date NOT NULL,
  `acquisition_age` int(11) NOT NULL DEFAULT 0,
  `source` varchar(100) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('active','sold','culled','completed') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flocks`
--

INSERT INTO `flocks` (`id`, `flock_id`, `breed`, `batch_name`, `quantity`, `acquisition_date`, `acquisition_age`, `source`, `cost`, `notes`, `status`, `created_by`, `created_at`) VALUES
(1, 'FL-2025-001', 'Leghorn', 'Batch A', 500, '2025-01-15', 1, 'ABC Hatchery', '25000.00', 'Initial flock', 'active', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `flock_daily_records`
--

CREATE TABLE `flock_daily_records` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `mortality` int(11) NOT NULL DEFAULT 0,
  `culls` int(11) NOT NULL DEFAULT 0,
  `weight` decimal(10,2) DEFAULT NULL,
  `feed_consumption` decimal(10,2) DEFAULT NULL,
  `water_consumption` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flock_daily_records`
--

INSERT INTO `flock_daily_records` (`id`, `flock_id`, `record_date`, `mortality`, `culls`, `weight`, `feed_consumption`, `water_consumption`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, '2025-01-15', 0, 0, '45.50', '25.00', '75.00', 'Initial record', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `feed_types`
--

CREATE TABLE `feed_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `protein_content` decimal(5,2) DEFAULT NULL,
  `recommended_age` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed_types`
--

INSERT INTO `feed_types` (`id`, `name`, `description`, `protein_content`, `recommended_age`, `created_at`) VALUES
(1, 'Starter Feed', 'High protein feed for chicks', '22.00', '0-8 weeks', '2025-03-14 21:27:36'),
(2, 'Grower Feed', 'Balanced feed for growing birds', '18.00', '8-18 weeks', '2025-03-14 21:27:36'),
(3, 'Layer Feed', 'Calcium-rich feed for laying hens', '16.00', '18+ weeks', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `feed_inventory`
--

CREATE TABLE `feed_inventory` (
  `id` int(11) NOT NULL,
  `feed_type_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` enum('kg','lb','ton') NOT NULL DEFAULT 'kg',
  `purchase_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `cost_per_unit` decimal(10,2) NOT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed_inventory`
--

INSERT INTO `feed_inventory` (`id`, `feed_type_id`, `batch_number`, `quantity`, `unit`, `purchase_date`, `expiry_date`, `cost_per_unit`, `supplier`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'FEED-001', '500.00', 'kg', '2025-01-10', '2025-04-10', '2.50', 'Feed Supplier Inc.', 'Initial feed inventory', 1, '2025-03-14 21:27:36'),
(2, 2, 'FEED-002', '750.00', 'kg', '2025-01-10', '2025-04-10', '2.25', 'Feed Supplier Inc.', 'Initial feed inventory', 1, '2025-03-14 21:27:36'),
(3, 3, 'FEED-003', '1000.00', 'kg', '2025-01-10', '2025-04-10', '2.00', 'Feed Supplier Inc.', 'Initial feed inventory', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `feed_consumption`
--

CREATE TABLE `feed_consumption` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `feed_inventory_id` int(11) NOT NULL,
  `consumption_date` date NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feed_consumption`
--

INSERT INTO `feed_consumption` (`id`, `flock_id`, `feed_inventory_id`, `consumption_date`, `quantity`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, 1, '2025-01-15', '25.00', 'Initial feed consumption record', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `egg_production`
--

CREATE TABLE `egg_production` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `collection_date` date NOT NULL,
  `total_eggs` int(11) NOT NULL DEFAULT 0,
  `broken_eggs` int(11) NOT NULL DEFAULT 0,
  `small_eggs` int(11) NOT NULL DEFAULT 0,
  `medium_eggs` int(11) NOT NULL DEFAULT 0,
  `large_eggs` int(11) NOT NULL DEFAULT 0,
  `xlarge_eggs` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `egg_production`
--

INSERT INTO `egg_production` (`id`, `flock_id`, `collection_date`, `total_eggs`, `broken_eggs`, `small_eggs`, `medium_eggs`, `large_eggs`, `xlarge_eggs`, `notes`, `collected_by`, `created_at`) VALUES
(1, 1, '2025-03-01', 450, 5, 50, 200, 150, 45, 'Good production day', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `egg_inventory`
--

CREATE TABLE `egg_inventory` (
  `id` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `small_eggs` int(11) NOT NULL DEFAULT 0,
  `medium_eggs` int(11) NOT NULL DEFAULT 0,
  `large_eggs` int(11) NOT NULL DEFAULT 0,
  `xlarge_eggs` int(11) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `egg_inventory`
--

INSERT INTO `egg_inventory` (`id`, `production_date`, `small_eggs`, `medium_eggs`, `large_eggs`, `xlarge_eggs`, `updated_by`, `created_at`) VALUES
(1, '2025-03-01', 50, 200, 150, 45, 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `record_date` date NOT NULL,
  `record_type` enum('routine_check','treatment','vaccination','mortality') NOT NULL,
  `diagnosis` varchar(100) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medication` varchar(100) DEFAULT NULL,
  `dosage` varchar(50) DEFAULT NULL,
  `mortality` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`id`, `flock_id`, `record_date`, `record_type`, `diagnosis`, `symptoms`, `treatment`, `medication`, `dosage`, `mortality`, `notes`, `performed_by`, `created_at`) VALUES
(1, 1, '2025-01-20', 'routine_check', NULL, NULL, NULL, NULL, NULL, 0, 'Routine health check - all birds appear healthy', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_types`
--

CREATE TABLE `vaccination_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `recommended_age` int(11) DEFAULT NULL,
  `repeat_after` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_types`
--

INSERT INTO `vaccination_types` (`id`, `name`, `description`, `recommended_age`, `repeat_after`, `created_at`) VALUES
(1, 'Newcastle Disease', 'Vaccination against Newcastle disease', 7, 90, '2025-03-14 21:27:36'),
(2, 'Infectious Bronchitis', 'Vaccination against Infectious Bronchitis', 14, 90, '2025-03-14 21:27:36'),
(3, 'Marek\'s Disease', 'Vaccination against Marek\'s Disease', 1, NULL, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `vaccination_records`
--

CREATE TABLE `vaccination_records` (
  `id` int(11) NOT NULL,
  `flock_id` int(11) NOT NULL,
  `vaccination_type_id` int(11) NOT NULL,
  `vaccination_date` date NOT NULL,
  `next_vaccination_date` date DEFAULT NULL,
  `administered_by` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vaccination_records`
--

INSERT INTO `vaccination_records` (`id`, `flock_id`, `vaccination_type_id`, `vaccination_date`, `next_vaccination_date`, `administered_by`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, 1, '2025-01-22', '2025-04-22', 'Dr. Smith', 'Initial vaccination', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `customer_type` enum('individual','business','wholesale','retail') NOT NULL DEFAULT 'individual',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `customer_type`, `notes`, `created_by`, `created_at`) VALUES
(1, 'Local Market', 'John Doe', 'john@localmarket.com', '1234567890', '123 Market St, City', 'business', 'Regular customer', 1, '2025-03-14 21:27:36'),
(2, 'Jane Smith', NULL, 'jane@example.com', '0987654321', '456 Main St, Town', 'individual', 'Buys eggs weekly', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(20) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `sale_date` date NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('paid','partial','pending') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','credit','bank_transfer','mobile_money') DEFAULT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`net_amount` - `paid_amount`) STORED,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_id`, `sale_date`, `total_amount`, `discount_amount`, `tax_amount`, `net_amount`, `payment_status`, `payment_method`, `paid_amount`, `notes`, `created_by`, `created_at`) VALUES
(1, 'INV-202503-001', 1, '2025-03-02', '1200.00', '0.00', '0.00', '1200.00', 'paid', 'cash', '1200.00', 'Sale of eggs', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `item_type` enum('egg','bird','feed','other') NOT NULL,
  `item_description` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `item_type`, `item_description`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 'egg', 'Large Eggs (Tray)', '40.00', '30.00', '1200.00', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `egg_sales`
--

CREATE TABLE `egg_sales` (
  `id` int(11) NOT NULL,
  `sale_item_id` int(11) NOT NULL,
  `egg_size` enum('small','medium','large','xlarge') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `egg_sales`
--

INSERT INTO `egg_sales` (`id`, `sale_item_id`, `egg_size`, `quantity`, `unit_price`, `created_at`) VALUES
(1, 1, 'large', 40, '30.00', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit','bank_transfer','mobile_money') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `sale_id`, `payment_date`, `amount`, `payment_method`, `reference_number`, `notes`, `received_by`, `created_at`) VALUES
(1, 1, '2025-03-02', '1200.00', 'cash', NULL, 'Full payment', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `expense_category_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `payment_method` enum('cash','credit','bank_transfer','mobile_money') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_category_id`, `expense_date`, `amount`, `description`, `payment_method`, `reference_number`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, '2025-01-10', '1250.00', 'Feed purchase', 'cash', NULL, 'Initial feed stock', 1, '2025-03-14 21:27:36'),
(2, 2, '2025-01-15', '500.00', 'Medication purchase', 'cash', NULL, 'Routine medications', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Feed', 'Expenses related to feed purchases', '2025-03-14 21:27:36'),
(2, 'Medication', 'Expenses related to medications and vaccines', '2025-03-14 21:27:36'),
(3, 'Equipment', 'Expenses related to equipment purchases', '2025-03-14 21:27:36'),
(4, 'Utilities', 'Expenses related to utilities (electricity, water, etc.)', '2025-03-14 21:27:36'),
(5, 'Labor', 'Expenses related to labor and salaries', '2025-03-14 21:27:36'),
(6, 'Maintenance', 'Expenses related to maintenance and repairs', '2025-03-14 21:27:36'),
(7, 'Other', 'Other miscellaneous expenses', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `position` varchar(50) NOT NULL,
  `hire_date` date NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `status` enum('active','inactive','terminated','on_leave') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `employee_id`, `first_name`, `last_name`, `gender`, `date_of_birth`, `email`, `phone`, `address`, `position`, `hire_date`, `salary`, `status`, `notes`, `created_by`, `created_at`) VALUES
(1, 'EMP-001', 'John', 'Doe', 'male', '1985-05-15', 'john@example.com', '1234567890', '123 Employee St, City', 'Farm Manager', '2024-12-01', '5000.00', 'active', 'Farm manager with 10 years experience', 1, '2025-03-14 21:27:36'),
(2, 'EMP-002', 'Jane', 'Smith', 'female', '1990-08-20', 'jane@example.com', '0987654321', '456 Worker Ave, Town', 'Farm Worker', '2025-01-15', '2500.00', 'active', 'Responsible for daily feeding and egg collection', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('present','absent','half_day','leave') NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `hours_worked` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `employee_id`, `attendance_date`, `status`, `time_in`, `time_out`, `hours_worked`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, '2025-03-01', 'present', '08:00:00', '17:00:00', '9.00', NULL, 1, '2025-03-14 21:27:36'),
(2, 2, '2025-03-01', 'present', '08:30:00', '17:30:00', '9.00', NULL, 1, '2025-03-14 21:27:36  1, '2025-03-14 21:27:36'),
(2, 2, '2025-03-01', 'present', '08:30:00', '17:30:00', '9.00', NULL, 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `pay_period_start` date NOT NULL,
  `pay_period_end` date NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL,
  `overtime_hours` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overtime_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(10,2) GENERATED ALWAYS AS (`basic_salary` + (`overtime_hours` * `overtime_rate`) + `allowances` - `deductions` - `tax`) STORED,
  `payment_method` enum('cash','bank_transfer','mobile_money') DEFAULT NULL,
  `payment_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `employee_id`, `pay_period_start`, `pay_period_end`, `basic_salary`, `overtime_hours`, `overtime_rate`, `allowances`, `deductions`, `tax`, `payment_method`, `payment_status`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, '2025-02-01', '2025-02-28', '5000.00', '0.00', '0.00', '500.00', '250.00', '525.00', 'bank_transfer', 'paid', 'February 2025 salary', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Feed', 'Feed supplies', '2025-03-14 21:27:36'),
(2, 'Medication', 'Medications and vaccines', '2025-03-14 21:27:36'),
(3, 'Equipment', 'Farm equipment', '2025-03-14 21:27:36'),
(4, 'Packaging', 'Packaging materials', '2025-03-14 21:27:36'),
(5, 'Cleaning', 'Cleaning supplies', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `location` varchar(50) DEFAULT NULL,
  `status` enum('available','low_stock','out_of_stock') NOT NULL DEFAULT 'available',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `category_id`, `item_name`, `description`, `unit`, `quantity`, `minimum_quantity`, `unit_cost`, `location`, `status`, `created_by`, `created_at`) VALUES
(1, 2, 'Newcastle Vaccine', 'Vaccine for Newcastle disease', 'vial', '50.00', '10.00', '15.00', 'Storage Room A', 'available', 1, '2025-03-14 21:27:36'),
(2, 4, 'Egg Trays', '30-egg capacity trays', 'piece', '500.00', '100.00', '2.00', 'Storage Room B', 'available', 1, '2025-03-14 21:27:36'),
(3, 5, 'Disinfectant', 'General purpose disinfectant', 'liter', '20.00', '5.00', '8.00', 'Storage Room A', 'available', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `transaction_type` enum('purchase','usage','adjustment','transfer') NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `transaction_date` datetime NOT NULL,
  `unit_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_transactions`
--

INSERT INTO `inventory_transactions` (`id`, `item_id`, `transaction_type`, `quantity`, `transaction_date`, `unit_cost`, `total_cost`, `reference`, `notes`, `performed_by`, `created_at`) VALUES
(1, 1, 'purchase', '50.00', '2025-01-10 10:00:00', '15.00', '750.00', 'PO-001', 'Initial stock', 1, '2025-03-14 21:27:36'),
(2, 2, 'purchase', '500.00', '2025-01-10 10:30:00', '2.00', '1000.00', 'PO-002', 'Initial stock', 1, '2025-03-14 21:27:36'),
(3, 3, 'purchase', '20.00', '2025-01-10 11:00:00', '8.00', '160.00', 'PO-003', 'Initial stock', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `supplier_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `email`, `phone`, `address`, `supplier_type`, `notes`, `created_by`, `created_at`) VALUES
(1, 'Feed Supplier Inc.', 'Robert Johnson', 'robert@feedsupplier.com', '1234567890', '789 Supplier Rd, City', 'Feed', 'Main feed supplier', 1, '2025-03-14 21:27:36'),
(2, 'Poultry Health Products', 'Sarah Williams', 'sarah@phproducts.com', '0987654321', '456 Health St, Town', 'Medication', 'Supplier for medications and vaccines', 1, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_description`, `setting_group`, `created_at`) VALUES
(1, 'site_name', 'Poultry Farm Management System', 'Name of the site', 'general', '2025-03-14 21:27:36'),
(2, 'company_name', 'ABC Poultry Farm', 'Company name', 'company', '2025-03-14 21:27:36'),
(3, 'company_address', '123 Farm Road, City, Country', 'Company address', 'company', '2025-03-14 21:27:36'),
(4, 'company_phone', '1234567890', 'Company phone', 'company', '2025-03-14 21:27:36'),
(5, 'company_email', 'info@abcpoultryfarm.com', 'Company email', 'company', '2025-03-14 21:27:36'),
(6, 'currency', 'USD', 'Default currency', 'finance', '2025-03-14 21:27:36'),
(7, 'tax_rate', '10', 'Default tax rate percentage', 'finance', '2025-03-14 21:27:36'),
(8, 'date_format', 'Y-m-d', 'Default date format', 'general', '2025-03-14 21:27:36'),
(9, 'time_format', 'H:i:s', 'Default time format', 'general', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `activity_type` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `activity_type`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'User logged in: admin', '127.0.0.1', 'Mozilla/5.0', '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `notification_type`, `is_read`, `created_at`) VALUES
(1, 1, 'Welcome', 'Welcome to the Poultry Farm Management System', 'system', 0, '2025-03-14 21:27:36');

-- --------------------------------------------------------

--
-- Table structure for table `system_backups`
--

CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL,
  `backup_name` varchar(100) NOT NULL,
  `backup_file` varchar(255) NOT NULL,
  `backup_size` int(11) NOT NULL,
  `backup_type` enum('full','partial') NOT NULL DEFAULT 'full',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `flocks`
--
ALTER TABLE `flocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `flock_id` (`flock_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `flock_daily_records`
--
ALTER TABLE `flock_daily_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `flock_date` (`flock_id`,`record_date`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `feed_types`
--
ALTER TABLE `feed_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feed_inventory`
--
ALTER TABLE `feed_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `feed_type_id` (`feed_type_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `feed_consumption`
--
ALTER TABLE `feed_consumption`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flock_id` (`flock_id`),
  ADD KEY `feed_inventory_id` (`feed_inventory_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `egg_production`
--
ALTER TABLE `egg_production`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `flock_date` (`flock_id`,`collection_date`),
  ADD KEY `collected_by` (`collected_by`);

--
-- Indexes for table `egg_inventory`
--
ALTER TABLE `egg_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_date` (`production_date`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flock_id` (`flock_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `vaccination_types`
--
ALTER TABLE `vaccination_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flock_id` (`flock_id`),
  ADD KEY `vaccination_type_id` (`vaccination_type_id`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `egg_sales`
--
ALTER TABLE `egg_sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_item_id` (`sale_item_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `received_by` (`received_by`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_category_id` (`expense_category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_date` (`employee_id`,`attendance_date`),
  ADD KEY `recorded_by` (`recorded_by`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_period` (`employee_id`,`pay_period_start`,`pay_period_end`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `performed_by` (`performed_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flocks`
--
ALTER TABLE `flocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flock_daily_records`
--
ALTER TABLE `flock_daily_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feed_types`
--
ALTER TABLE `feed_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feed_inventory`
--
ALTER TABLE `feed_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feed_consumption`
--
ALTER TABLE `feed_consumption`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `egg_production`
--
ALTER TABLE `egg_production`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `egg_inventory`
--
ALTER TABLE `egg_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `vaccination_types`
--
ALTER TABLE `vaccination_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `egg_sales`
--
ALTER TABLE `egg_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_backups`
--
ALTER TABLE `system_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `flocks`
--
ALTER TABLE `flocks`
  ADD CONSTRAINT `flocks_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `flock_daily_records`
--
ALTER TABLE `flock_daily_records`
  ADD CONSTRAINT `flock_daily_records_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `flocks` (`id`),
  ADD CONSTRAINT `flock_daily_records_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `feed_inventory`
--
ALTER TABLE `feed_inventory`
  ADD CONSTRAINT `feed_inventory_ibfk_1` FOREIGN KEY (`feed_type_id`) REFERENCES `feed_types` (`id`),
  ADD CONSTRAINT `feed_inventory_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `feed_consumption`
--
ALTER TABLE `feed_consumption`
  ADD CONSTRAINT `feed_consumption_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `flocks` (`id`),
  ADD CONSTRAINT `feed_consumption_ibfk_2` FOREIGN KEY (`feed_inventory_id`) REFERENCES `feed_inventory` (`id`),
  ADD CONSTRAINT `feed_consumption_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `egg_production`
--
ALTER TABLE `egg_production`
  ADD CONSTRAINT `egg_production_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `flocks` (`id`),
  ADD CONSTRAINT `egg_production_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `egg_inventory`
--
ALTER TABLE `egg_inventory`
  ADD CONSTRAINT `egg_inventory_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `health_records`
--
ALTER TABLE `health_records`
  ADD CONSTRAINT `health_records_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `flocks` (`id`),
  ADD CONSTRAINT `health_records_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `vaccination_records`
--
ALTER TABLE `vaccination_records`
  ADD CONSTRAINT `vaccination_records_ibfk_1` FOREIGN KEY (`flock_id`) REFERENCES `flocks` (`id`),
  ADD CONSTRAINT `vaccination_records_ibfk_2` FOREIGN KEY (`vaccination_type_id`) REFERENCES `vaccination_types` (`id`),
  ADD CONSTRAINT `vaccination_records_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`);

--
-- Constraints for table `egg_sales`
--
ALTER TABLE `egg_sales`
  ADD CONSTRAINT `egg_sales_ibfk_1` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`),
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `payroll_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`),
  ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `inventory_transactions_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `system_backups`
--
ALTER TABLE `system_backups`
  ADD CONSTRAINT `system_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

