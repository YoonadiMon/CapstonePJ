-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 30, 2026 at 08:04 AM
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
-- Database: `capstonedb`
--

-- Drop the database if it exists
DROP DATABASE IF EXISTS `capstonedb`;

-- Create a fresh database
CREATE DATABASE `capstonedb`;

-- Select the database to use
USE `capstonedb`;

-- --------------------------------------------------------

--
-- Table structure for table `tblactivity_log`
--

CREATE TABLE `tblactivity_log` (
  `logID` int(11) NOT NULL,
  `requestID` int(11) DEFAULT NULL,
  `jobID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `dateTime` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblactivity_log`
--

INSERT INTO `tblactivity_log` (`logID`, `requestID`, `jobID`, `userID`, `type`, `action`, `description`, `dateTime`) VALUES
(1, 1, NULL, 5, 'Request', 'Create Request', 'Provider created a new collection request', '2026-03-10 21:01:46'),
(2, 2, 1, 1, 'Job', 'Job Assigned', 'Admin assigned collector to job', '2026-03-10 21:01:46'),
(3, 3, 2, 10, 'Job', 'Job Started', 'Collector started the collection job', '2026-03-10 21:01:46'),
(4, 3, 2, 10, 'Job', 'Job Completed', 'Collection completed and items delivered', '2026-03-10 21:01:46'),
(5, 4, NULL, 1, 'Request', 'Request Rejected', 'Admin rejected request due to service area', '2026-03-10 21:01:46'),
(6, 1, NULL, 5, 'Request', 'Create', 'Provider created a new collection request', '2026-02-23 09:15:00'),
(7, 2, NULL, 6, 'Request', 'Create', 'Provider created a new collection request', '2026-02-26 10:30:00'),
(8, 2, NULL, 1, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-02-28 14:20:00'),
(9, 2, NULL, 1, 'Request', 'Assignment', 'Assigned collector Jason Wong (ID: 9), vehicle BKL1234 (ID: 1), centre APU E-Waste Recycling Centre (ID: 1)', '2026-02-28 15:30:00'),
(10, 2, 1, 1, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-02-28 15:31:00'),
(11, 3, NULL, 7, 'Request', 'Create', 'Provider created a new collection request', '2026-02-26 11:45:00'),
(12, 3, NULL, 2, 'Request', 'Status Change', 'Changed from Pending to Approved', '2026-02-28 16:30:00'),
(13, 3, NULL, 2, 'Request', 'Assignment', 'Assigned collector Aina Sofea (ID: 10), vehicle PKN5678 (ID: 2), centre Penang Eco Waste Collection Centre (ID: 3)', '2026-03-07 16:45:00'),
(14, 3, 2, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-07 16:46:00'),
(15, 3, 2, 10, 'Job', 'Accepted', NULL, '2026-03-08 09:00:00'),
(16, 3, 2, 10, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-08 09:00:30'),
(17, 3, 2, 10, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-08 09:01:00'),
(18, 3, 2, 10, 'Job', 'Departed', 'Departed from Kuala Lumpur base to Penang with vehicle PKN5678 (ID: 2)', '2026-03-12 05:30:00'),
(19, 3, 2, 10, 'Job', 'Arrived', 'Arrived at pickup location in Penang', '2026-03-12 08:45:00'),
(20, 3, 2, 10, 'Request', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-12 08:46:00'),
(21, 3, 2, 10, 'Job', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-12 08:46:30'),
(22, 3, 2, 10, 'Item', 'Pickup', 'Item Photocopier (ID: 6) picked up', '2026-03-12 09:00:00'),
(23, 3, 2, 10, 'Item', 'Status Change', 'Item Photocopier (ID: 6) - Changed from Pending to Collected', '2026-03-12 09:01:00'),
(24, 3, 2, 10, 'Item', 'Pickup', 'Item Scanner (ID: 5) picked up', '2026-03-12 09:20:00'),
(25, 3, 2, 10, 'Item', 'Status Change', 'Item Scanner (ID: 5) - Changed from Pending to Collected', '2026-03-12 09:21:00'),
(26, 3, 2, 10, 'Job', 'Items Collected', 'All 2 items collected - total weight: 61.0kg on vehicle PKN5678 (ID: 2)', '2026-03-12 09:30:00'),
(27, 3, 2, 10, 'Issue', 'Create', 'Issue (ID: 1) - Vehicle - Tyre pressure issue on vehicle PKN5678 (ID: 2)', '2026-03-12 09:35:00'),
(28, 3, 2, 1, 'Issue', 'Assigned', 'Assigned to admin Farid Hakim (ID: 1)', '2026-03-12 09:45:00'),
(29, 3, 2, 1, 'Issue', 'Status Change', 'Issue (ID: 1) – Changed from Open to Assigned', '2026-03-12 09:46:00'),
(30, 3, 2, 1, 'Issue', 'Action Taken', 'Advised: stop at nearest petrol station to refill tyre pressure', '2026-03-12 09:50:00'),
(31, 3, 2, 10, 'Issue', 'Status Change', 'Issue (ID: 1) – Changed from Assigned to Resolved', '2026-03-12 10:15:00'),
(32, 3, 2, 10, 'Issue', 'Resolved', 'Tyre pressure refilled at petrol station, journey resumed', '2026-03-12 10:16:00'),
(33, 3, 2, 10, 'Job', 'Departed', 'Departed from pickup location to Penang Eco Waste Collection Centre', '2026-03-12 10:30:00'),
(34, 3, 2, 10, 'Job', 'Arrived', 'Arrived at Penang Eco Waste Collection Centre (ID: 3) with vehicle PKN5678 (ID: 2)', '2026-03-12 11:00:00'),
(35, 3, 2, 10, 'Item', 'Dropoff', 'Item Scanner (ID:5) dropped at Penang Eco Waste Collection Centre (ID: 3)', '2026-03-12 11:05:00'),
(36, 3, 2, 10, 'Item', 'Status Change', 'Item Scanner (ID:5) - Changed from Collected to Received', '2026-03-12 11:06:00'),
(37, 3, 2, 10, 'Item', 'Dropoff', 'Item Photocopier (ID:6) dropped at Penang Eco Waste Collection Centre (ID: 3)', '2026-03-12 11:25:00'),
(38, 3, 2, 10, 'Item', 'Status Change', 'Item Photocopier (ID:6) - Changed from Collected to Received', '2026-03-12 11:26:00'),
(39, 3, 2, 10, 'Job', 'All Items Dropped', 'All 2 items delivered to recycling centre', '2026-03-12 11:30:00'),
(40, 3, 2, 10, 'Request', 'Status Change', 'Changed from Ongoing to Collected', '2026-03-12 11:31:00'),
(41, 3, 2, 10, 'Job', 'Status Change', 'Changed from Ongoing to Picked Up', '2026-03-12 11:32:00'),
(42, 3, 2, 10, 'Job', 'Departed', 'Departed from centre to return to Kuala Lumpur base', '2026-03-12 12:00:00'),
(43, 3, 2, 10, 'Job', 'Returned', 'Returned to Kuala Lumpur base with vehicle PKN5678 (ID:2)', '2026-03-12 18:20:00'),
(44, 3, 2, 3, 'Item', 'Status Change', 'Item Scanner (ID:5) - Changed from Received to Processed', '2026-03-12 14:25:00'),
(45, 3, 2, 3, 'Item', 'Status Change', 'Item Scanner (ID:5) - Changed from Processed to Recycled', '2026-03-12 14:30:00'),
(46, 3, 2, 3, 'Item', 'Status Change', 'Item Photocopier (ID:6) - Changed from Received to Processed', '2026-03-12 15:15:00'),
(47, 3, 2, 3, 'Item', 'Status Change', 'Item Photocopier (ID:6) - Changed from Processed to Recycled', '2026-03-13 10:30:00'),
(48, 3, 2, 3, 'Request', 'Status Change', 'Changed from Collected to Completed', '2026-03-13 10:31:00'),
(49, 3, 2, 10, 'Job', 'Status Change', 'Changed from Picked Up to Completed', '2026-03-13 10:32:00'),
(50, 3, 2, 10, 'Job', 'Completed', NULL, '2026-03-13 10:33:00'),
(51, 4, NULL, 8, 'Request', 'Create', 'Provider created a new collection request', '2026-03-08 13:20:00'),
(52, 4, NULL, 2, 'Request', 'Rejected', 'Reason: Address not within service area', '2026-03-08 16:45:00'),
(53, 4, NULL, 2, 'Item', 'Status Change', 'Item LED TV (ID: 4) - Changed from Pending to Cancelled (request rejected)', '2026-03-08 16:46:00'),
(54, 5, NULL, 7, 'Request', 'Create', 'Provider created a new collection request', '2026-03-04 10:00:00'),
(55, 6, NULL, 8, 'Request', 'Create', 'Provider created a new collection request', '2026-03-05 14:30:00'),
(56, 6, NULL, 3, 'Request', 'Status Change', 'Changed from Pending to Approved', '2026-03-07 11:15:00'),
(57, 6, NULL, 3, 'Request', 'Assignment', 'Assigned collector Ahmad Faiz (ID: 11), vehicle PNG3344 (ID: 4), centre Johor Sustainable Recycling Centre (ID: 4)', '2026-03-07 14:30:00'),
(58, 6, 3, 3, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-07 14:31:00'),
(59, 7, NULL, 3, 'Request', 'Create', 'Provider created a new collection request', '2026-03-20 08:00:00'),
(60, 8, NULL, 4, 'Request', 'Create', 'Provider created a new collection request', '2026-03-21 09:15:00'),
(61, 9, NULL, 5, 'Request', 'Create', 'Provider created a new collection request', '2026-03-20 10:00:00'),
(62, 9, NULL, 1, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-22 09:30:00'),
(63, 9, 4, 1, 'Request', 'Assignment', 'Assigned collector Jason Wong (ID:9), vehicle BKL1234 (ID:1), centre APU E-Waste Recycling Centre (ID:1)', '2026-03-23 11:00:00'),
(64, 9, 4, 1, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-23 11:00:01'),
(65, 10, NULL, 6, 'Request', 'Create', 'Provider created a new collection request', '2026-03-22 14:30:00'),
(66, 10, NULL, 2, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-24 10:15:00'),
(67, 10, 5, 2, 'Request', 'Assignment', 'Assigned collector Aina Sofea (ID:10), vehicle PKN5678 (ID:2), centre Penang Eco Waste Collection Centre (ID:3)', '2026-03-25 09:45:00'),
(68, 10, 5, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-25 09:45:01'),
(69, 11, NULL, 3, 'Request', 'Create', 'Provider created a new collection request', '2026-03-01 08:00:00'),
(70, 11, NULL, 1, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-02 10:00:00'),
(71, 11, 6, 1, 'Request', 'Assignment', 'Assigned collector Jason Wong (ID:9), vehicle BKL1234 (ID:1), centre APU E-Waste Recycling Centre (ID:1)', '2026-03-03 09:00:00'),
(72, 11, 6, 1, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-03 09:00:01'),
(73, 11, 6, 9, 'Job', 'Accepted', NULL, '2026-03-04 14:30:00'),
(74, 11, 6, 9, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-04 14:30:15'),
(75, 11, 6, 9, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-04 14:31:00'),
(76, 11, 6, 9, 'Job', 'Departed', 'Departed from Kuala Lumpur base to pickup location with vehicle BKL1234 (ID:1)', '2026-03-11 09:50:00'),
(77, 11, 6, 9, 'Job', 'Arrived', 'Arrived at pickup location in Ampang', '2026-03-11 10:15:00'),
(78, 11, 6, 9, 'Request', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-11 10:16:00'),
(79, 11, 6, 9, 'Job', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-11 10:16:05'),
(80, 11, 6, 9, 'Item', 'Pickup', 'Item Laptop (ID:16) picked up', '2026-03-11 10:25:00'),
(81, 11, 6, 9, 'Item', 'Status Change', 'Item Laptop (ID:16) - Changed from Pending to Collected', '2026-03-11 10:26:00'),
(82, 11, 6, 9, 'Job', 'Items Collected', 'All 1 item collected - total weight: 1.9kg on vehicle BKL1234 (ID:1)', '2026-03-11 10:27:00'),
(83, 11, 6, 9, 'Job', 'Departed', 'Departed from pickup location to APU E-Waste Recycling Centre', '2026-03-11 10:45:00'),
(84, 11, 6, 9, 'Job', 'Arrived', 'Arrived at APU E-Waste Recycling Centre (ID:1) with vehicle BKL1234 (ID:1)', '2026-03-11 11:30:00'),
(85, 11, 6, 9, 'Item', 'Dropoff', 'Item Laptop (ID:16) dropped at APU E-Waste Recycling Centre (ID:1)', '2026-03-11 11:35:00'),
(86, 11, 6, 9, 'Item', 'Status Change', 'Item Laptop (ID:16) - Changed from Collected to Received', '2026-03-11 11:36:00'),
(87, 11, 6, 9, 'Job', 'All Items Dropped', 'All 1 item delivered to recycling centre', '2026-03-11 11:40:00'),
(88, 11, 6, 9, 'Request', 'Status Change', 'Changed from Ongoing to Collected', '2026-03-11 11:41:00'),
(89, 11, 6, 9, 'Job', 'Status Change', 'Changed from Ongoing to Picked Up', '2026-03-11 11:42:00'),
(90, 11, 6, 9, 'Job', 'Departed', 'Departed from centre to return to Kuala Lumpur base', '2026-03-11 12:00:00'),
(91, 11, 6, 9, 'Job', 'Returned', 'Returned to Kuala Lumpur base with vehicle BKL1234 (ID:1)', '2026-03-11 16:20:00'),
(92, 11, 6, 9, 'Job', 'Status Change', 'Changed from Picked Up to Completed', '2026-03-11 16:21:00'),
(93, 11, 6, 9, 'Job', 'Completed', NULL, '2026-03-11 16:21:05'),
(94, 12, NULL, 4, 'Request', 'Create', 'Provider created a new collection request', '2026-03-02 09:00:00'),
(95, 12, NULL, 2, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-03 11:30:00'),
(96, 12, 7, 2, 'Request', 'Assignment', 'Assigned collector Aina Sofea (ID:10), vehicle PKN5678 (ID:2), centre Selangor Green Recycling Hub (ID:2)', '2026-03-04 09:00:00'),
(97, 12, 7, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-04 09:00:01'),
(98, 12, 7, 10, 'Job', 'Accepted', NULL, '2026-03-05 14:20:00'),
(99, 12, 7, 10, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-05 14:20:30'),
(100, 12, 7, 10, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-05 14:21:00'),
(101, 12, 7, 10, 'Job', 'Departed', 'Departed from Kuala Lumpur base to Penang with vehicle PKN5678 (ID:2)', '2026-03-12 09:20:00'),
(102, 12, 7, 10, 'Job', 'Arrived', 'Arrived at pickup location in Penang', '2026-03-12 11:45:00'),
(103, 12, 7, 10, 'Request', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-12 11:46:00'),
(104, 12, 7, 10, 'Job', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-12 11:46:05'),
(105, 12, 7, 10, 'Item', 'Pickup', 'Item Desktop PC (ID:17) picked up', '2026-03-12 12:00:00'),
(106, 12, 7, 10, 'Item', 'Status Change', 'Item Desktop PC (ID:17) - Changed from Pending to Collected', '2026-03-12 12:01:00'),
(107, 12, 7, 10, 'Item', 'Pickup', 'Item Monitor (ID:18) picked up', '2026-03-12 12:10:00'),
(108, 12, 7, 10, 'Item', 'Status Change', 'Item Monitor (ID:18) - Changed from Pending to Collected', '2026-03-12 12:11:00'),
(109, 12, 7, 10, 'Job', 'Items Collected', 'All 2 items collected - total weight: 15.3kg on vehicle PKN5678 (ID:2)', '2026-03-12 12:20:00'),
(110, 12, 7, 10, 'Job', 'Departed', 'Departed from pickup location to Selangor Green Recycling Hub', '2026-03-12 12:35:00'),
(111, 12, 7, 10, 'Job', 'Arrived', 'Arrived at Selangor Green Recycling Hub (ID:2) with vehicle PKN5678 (ID:2)', '2026-03-12 15:30:00'),
(112, 12, 7, 10, 'Item', 'Dropoff', 'Item Desktop PC (ID:17) dropped at Selangor Green Recycling Hub (ID:2)', '2026-03-12 15:40:00'),
(113, 12, 7, 10, 'Item', 'Status Change', 'Item Desktop PC (ID:17) - Changed from Collected to Received', '2026-03-12 15:41:00'),
(114, 12, 7, 10, 'Item', 'Dropoff', 'Item Monitor (ID:18) dropped at Selangor Green Recycling Hub (ID:2)', '2026-03-12 15:50:00'),
(115, 12, 7, 10, 'Item', 'Status Change', 'Item Monitor (ID:18) - Changed from Collected to Received', '2026-03-12 15:51:00'),
(116, 12, 7, 10, 'Job', 'All Items Dropped', 'All 2 items delivered to recycling centre', '2026-03-12 16:00:00'),
(117, 12, 7, 10, 'Request', 'Status Change', 'Changed from Ongoing to Collected', '2026-03-12 16:01:00'),
(118, 12, 7, 10, 'Job', 'Status Change', 'Changed from Ongoing to Picked Up', '2026-03-12 16:01:30'),
(119, 12, 7, 10, 'Job', 'Departed', 'Departed from centre to return to Kuala Lumpur base', '2026-03-12 16:30:00'),
(120, 12, 7, 10, 'Job', 'Returned', 'Returned to Kuala Lumpur base with vehicle PKN5678 (ID:2)', '2026-03-12 17:45:00'),
(121, 12, 7, 10, 'Job', 'Status Change', 'Changed from Picked Up to Completed', '2026-03-12 17:46:00'),
(122, 12, 7, 10, 'Job', 'Completed', NULL, '2026-03-12 17:46:10'),
(123, 12, 7, 3, 'Item', 'Status Change', 'Item Desktop PC (ID:17) - Changed from Received to Processed', '2026-03-13 09:30:00'),
(124, 12, 7, 3, 'Points', 'Awarded', 'Provider (ID:4) awarded 45 points for item #17 (Processed).', '2026-03-13 09:30:01'),
(125, 12, 7, 3, 'Item', 'Status Change', 'Item Monitor (ID:18) - Changed from Received to Recycled', '2026-03-14 10:15:00'),
(126, 12, 7, 3, 'Points', 'Awarded', 'Provider (ID:4) awarded 40 points for item #18 (Recycled).', '2026-03-14 10:15:05'),
(127, 12, 7, 3, 'Request', 'Status Change', 'Changed from Collected to Completed', '2026-03-14 10:16:00'),
(128, 13, NULL, 5, 'Request', 'Create', 'Provider created a new collection request', '2026-03-23 11:00:00'),
(129, 13, NULL, 1, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-24 09:00:00'),
(130, 13, 8, 1, 'Request', 'Assignment', 'Assigned collector Jason Wong (ID:9), vehicle BKL1234 (ID:1), centre APU E-Waste Recycling Centre (ID:1)', '2026-03-25 10:30:00'),
(131, 13, 8, 1, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-25 10:30:01'),
(132, 13, 8, 9, 'Job', 'Accepted', NULL, '2026-03-26 14:00:00'),
(133, 13, 8, 9, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-26 14:00:30'),
(134, 13, 8, 9, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-26 14:01:00'),
(135, 14, NULL, 6, 'Request', 'Create', 'Provider created a new collection request', '2026-03-23 14:00:00'),
(136, 14, NULL, 2, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-24 11:30:00'),
(137, 14, 9, 2, 'Request', 'Assignment', 'Assigned collector Aina Sofea (ID:10), vehicle PKN5678 (ID:2), centre Penang Eco Waste Collection Centre (ID:3)', '2026-03-25 15:00:00'),
(138, 14, 9, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-25 15:00:01'),
(139, 14, 9, 10, 'Job', 'Accepted', NULL, '2026-03-26 16:30:00'),
(140, 14, 9, 10, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-26 16:30:30'),
(141, 14, 9, 10, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-26 16:31:00'),
(142, 15, NULL, 3, 'Request', 'Create', 'Provider created a new collection request', '2026-03-26 08:00:00'),
(143, 15, NULL, 2, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-27 09:15:00'),
(144, 15, 10, 2, 'Request', 'Assignment', 'Assigned collector Jason Wong (ID:9), vehicle BKL1234 (ID:1), centre APU E-Waste Recycling Centre (ID:1)', '2026-03-28 11:00:00'),
(145, 15, 10, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-28 11:00:01'),
(146, 15, 10, 9, 'Job', 'Accepted', NULL, '2026-03-29 08:45:00'),
(147, 15, 10, 9, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-29 08:45:30'),
(148, 15, 10, 9, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-29 08:46:00'),
(149, 16, NULL, 5, 'Request', 'Create', 'Provider created a new collection request', '2026-03-25 09:00:00'),
(150, 16, NULL, 2, 'Request', 'Status Change', 'Request status changed from Pending to Approved', '2026-03-26 10:30:00'),
(151, 16, 11, 2, 'Request', 'Assignment', 'Assigned collector Aina Sofea (ID:10), vehicle PKN5678 (ID:2), centre Penang Eco Waste Collection Centre (ID:3)', '2026-03-27 08:00:00'),
(152, 16, 11, 2, 'Job', 'Create', 'Job awaiting collector acceptance', '2026-03-27 08:00:01'),
(153, 16, 11, 10, 'Job', 'Accepted', NULL, '2026-03-28 14:00:00'),
(154, 16, 11, 10, 'Job', 'Status Change', 'Changed from Pending to Scheduled', '2026-03-28 14:00:30'),
(155, 16, 11, 10, 'Request', 'Status Change', 'Changed from Approved to Scheduled', '2026-03-28 14:01:00'),
(156, 16, 11, 10, 'Job', 'Departed', 'Departed from Kuala Lumpur base to Penang with vehicle PKN5678 (ID:2)', '2026-03-31 09:30:00'),
(157, 16, 11, 10, 'Job', 'Arrived', 'Arrived at pickup location in Penang', '2026-03-31 10:15:00'),
(158, 16, 11, 10, 'Request', 'Status Change', 'Changed from Scheduled to Ongoing', '2026-03-31 10:16:00'),
(159, 16, 11, 10, 'Issue', 'Create', 'Issue (ID:2) - Operational - Provider not available at pickup address', '2026-03-31 10:20:00'),
(160, 16, 11, 2, 'Issue', 'Assigned', 'Assigned to admin Farid Hakim (ID:2)', '2026-03-31 10:30:00'),
(161, 16, 11, 2, 'Issue', 'Status Change', 'Issue (ID:2) – Changed from Open to Assigned', '2026-03-31 10:31:00'),
(162, 16, 11, 2, 'Issue', 'Action Taken', 'Advised: contacted provider, no response. Cancelling request.', '2026-03-31 10:45:00'),
(163, 16, 11, 2, 'Request', 'Status Change', 'Request status changed from Ongoing to Rejected', '2026-03-31 10:50:00'),
(164, 16, 11, 2, 'Job', 'Status Change', 'Changed from Ongoing to Cancelled', '2026-03-31 10:51:00'),
(165, 16, 11, 2, 'Item', 'Status Change', 'Item Television (ID:23) - Changed from Pending to Cancelled (request cancelled)', '2026-03-31 10:52:00'),
(166, 16, 11, 2, 'Issue', 'Status Change', 'Issue (ID:2) – Changed from Assigned to Resolved', '2026-03-31 10:55:00'),
(167, 16, 11, 2, 'Issue', 'Resolved', 'Provider unresponsive – request and job cancelled.', '2026-03-31 10:55:10'),
(168, 2, 1, 1, 'Job', 'Status Change', 'Job #1 rejected due to expiry – scheduled on 2026-03-11, collector last logged in on 2026-04-05', '2026-03-30 04:01:39'),
(169, 6, 3, 1, 'Job', 'Status Change', 'Job #3 rejected due to expiry – scheduled on 2026-03-15, collector last logged in on 2026-03-25', '2026-03-30 04:01:39');

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

CREATE TABLE `tbladmin` (
  `adminID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin` (`adminID`) VALUES
(1),
(2);

-- --------------------------------------------------------

--
-- Table structure for table `tblcentre`
--

CREATE TABLE `tblcentre` (
  `centreID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` varchar(255) NOT NULL,
  `state` enum('Johor','Kedah','Kelantan','Melaka','Negeri Sembilan','Pahang','Perak','Perlis','Penang','Selangor','Terengganu','Kuala Lumpur','Putrajaya') NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcentre`
--

INSERT INTO `tblcentre` (`centreID`, `name`, `address`, `state`, `postcode`, `contact`, `status`) VALUES
(1, 'APU E-Waste Recycling Centre', 'Unit 12-3, Technology Park Malaysia, Jalan Teknokrat 3, Bukit Jalil, Kuala Lumpur', 'Kuala Lumpur', '57000', '0389961000', 'Active'),
(2, 'Selangor Green Recycling Hub', 'Lot 21, Jalan Teknologi 5, Cyberjaya Industrial Park, Cyberjaya, Selangor', 'Selangor', '63000', '0383182000', 'Active'),
(3, 'Penang Eco Waste Collection Centre', 'No. 8, Lintang Bayan Lepas 4, Bayan Lepas Industrial Zone, Bayan Lepas, Pulau Pinang', 'Penang', '11900', '046448800', 'Active'),
(4, 'Johor Sustainable Recycling Centre', 'No. 15, Jalan Harmonium 23/1, Taman Desa Tebrau Industrial Area, Johor Bahru, Johor', 'Johor', '81100', '073522200', 'Active'),
(5, 'Perak Electronic Waste Facility', 'Lot 5, Persiaran Perindustrian Lahat 2, Kawasan Perindustrian Lahat, Ipoh, Perak', 'Perak', '31500', '053224100', 'Active'),
(6, 'Negeri Sembilan GreenTech Centre', 'No. 3, Jalan Sendayan Tech Valley 1, Bandar Sri Sendayan Industrial Area, Seremban, Negeri Sembilan', 'Negeri Sembilan', '71950', '067914400', 'Active'),
(7, 'Pahang Eco Recovery Centre', 'No. 27, Jalan IM 7/2, Indera Mahkota Industrial Park, Kuantan, Pahang', 'Pahang', '25200', '095678900', 'Active'),
(8, 'Melaka Smart Recycling Station', 'Lot 10, Jalan Ayer Keroh Industrial 3, Ayer Keroh Industrial Park, Melaka', 'Melaka', '75450', '062337700', 'Active'),
(9, 'Kedah Environmental Collection Centre', 'No. 6, Jalan Hi-Tech 4, Kulim Hi-Tech Park, Kulim, Kedah', 'Kedah', '09000', '044032200', 'Active'),
(10, 'Terengganu Green Disposal Facility', 'Lot 18, Jalan Gong Badak Industrial 2, Gong Badak Industrial Estate, Kuala Terengganu, Terengganu', 'Terengganu', '21300', '096655500', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `tblcentre_accepted_type`
--

CREATE TABLE `tblcentre_accepted_type` (
  `centreID` int(11) NOT NULL,
  `itemTypeID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcentre_accepted_type`
--

INSERT INTO `tblcentre_accepted_type` (`centreID`, `itemTypeID`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(1, 24),
(1, 25),
(1, 26),
(1, 27),
(1, 28),
(1, 29),
(1, 30),
(2, 1),
(2, 2),
(2, 3),
(2, 4),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 12),
(2, 13),
(2, 14),
(2, 15),
(2, 16),
(2, 17),
(2, 18),
(2, 19),
(2, 20),
(2, 21),
(2, 22),
(2, 23),
(2, 24),
(2, 30),
(3, 1),
(3, 2),
(3, 3),
(3, 4),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 9),
(3, 10),
(3, 11),
(3, 12),
(3, 13),
(3, 14),
(3, 15),
(3, 16),
(3, 17),
(3, 18),
(3, 19),
(3, 20),
(3, 21),
(3, 22),
(3, 23),
(3, 24),
(3, 25),
(3, 26),
(3, 27),
(3, 28),
(3, 29),
(3, 30),
(4, 1),
(4, 2),
(4, 3),
(4, 4),
(4, 5),
(4, 6),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(4, 11),
(4, 12),
(4, 13),
(4, 14),
(4, 15),
(4, 16),
(4, 17),
(4, 18),
(4, 19),
(4, 20),
(4, 21),
(4, 22),
(4, 23),
(4, 24),
(4, 25),
(4, 26),
(4, 27),
(4, 28),
(4, 29),
(4, 30),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(5, 8),
(5, 9),
(5, 10),
(5, 11),
(5, 12),
(6, 1),
(6, 2),
(6, 3),
(6, 4),
(6, 5),
(6, 6),
(6, 7),
(6, 8),
(6, 9),
(6, 10),
(6, 18),
(6, 19),
(6, 20),
(6, 21),
(6, 22),
(6, 23),
(6, 29),
(6, 30),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 6),
(7, 7),
(7, 8),
(7, 9),
(7, 10),
(7, 11),
(7, 12),
(7, 13),
(7, 14),
(7, 15),
(7, 16),
(7, 17),
(7, 18),
(7, 19),
(7, 20),
(7, 26),
(7, 27),
(8, 1),
(8, 2),
(8, 3),
(8, 4),
(8, 5),
(8, 6),
(8, 7),
(8, 8),
(8, 23),
(8, 24),
(8, 25),
(8, 26),
(8, 27),
(8, 28),
(8, 29),
(8, 30),
(9, 1),
(9, 2),
(9, 3),
(9, 4),
(9, 5),
(9, 6),
(9, 7),
(9, 8),
(9, 9),
(9, 10),
(9, 11),
(9, 12),
(9, 29),
(9, 30),
(10, 1),
(10, 2),
(10, 3),
(10, 4),
(10, 5),
(10, 6),
(10, 7),
(10, 8),
(10, 9),
(10, 10),
(10, 18),
(10, 19),
(10, 20),
(10, 21),
(10, 22),
(10, 23),
(10, 24),
(10, 25),
(10, 26),
(10, 30);

-- --------------------------------------------------------

--
-- Table structure for table `tblcollection_request`
--

CREATE TABLE `tblcollection_request` (
  `requestID` int(11) NOT NULL,
  `providerID` int(11) NOT NULL,
  `pickupAddress` varchar(255) NOT NULL,
  `pickupState` varchar(100) NOT NULL,
  `pickupPostcode` varchar(10) NOT NULL,
  `preferredDateTime` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `createdAt` datetime DEFAULT current_timestamp(),
  `rejectionReason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcollection_request`
--

INSERT INTO `tblcollection_request` (`requestID`, `providerID`, `pickupAddress`, `pickupState`, `pickupPostcode`, `preferredDateTime`, `status`, `createdAt`, `rejectionReason`) VALUES
(1, 3, 'No 12 Jalan Ampang', 'Kuala Lumpur', '50450', '2026-03-10 10:00:00', 'Pending', '2026-03-10 21:01:45', NULL),
(2, 4, '45 Jalan Bukit Bintang', 'Kuala Lumpur', '55100', '2026-03-11 14:30:00', 'Approved', '2026-03-10 21:01:45', NULL),
(3, 5, '22 Lebuh Pantai', 'Penang', '10300', '2026-03-12 09:00:00', 'Completed', '2026-03-10 21:01:45', NULL),
(4, 6, '88 Jalan Gurney', 'Penang', '10250', '2026-03-13 16:00:00', 'Rejected', '2026-03-10 21:01:45', 'Address not within service area'),
(5, 7, '10 Jalan Sutera', 'Johor', '81200', '2026-03-14 11:15:00', 'Pending', '2026-03-10 21:01:45', NULL),
(6, 8, '5 Jalan Austin Heights', 'Johor', '81100', '2026-03-15 13:45:00', 'Approved', '2026-03-10 21:01:45', NULL),
(7, 3, 'No 12 Jalan Ampang', 'Kuala Lumpur', '50450', '2026-03-30 10:00:00', 'Pending', '2026-03-20 08:00:00', NULL),
(8, 4, '45 Jalan Bukit Bintang', 'Kuala Lumpur', '55100', '2026-03-31 14:30:00', 'Pending', '2026-03-21 09:15:00', NULL),
(9, 5, '22 Lebuh Pantai', 'Penang', '10300', '2026-03-30 09:00:00', 'Approved', '2026-03-20 10:00:00', NULL),
(10, 6, '88 Jalan Gurney', 'Penang', '10250', '2026-04-01 11:00:00', 'Approved', '2026-03-22 14:30:00', NULL),
(11, 3, 'No 12 Jalan Ampang', 'Kuala Lumpur', '50450', '2026-03-11 10:00:00', 'Collected', '2026-03-01 08:00:00', NULL),
(12, 4, '45 Jalan Bukit Bintang', 'Kuala Lumpur', '55100', '2026-03-12 09:30:00', 'Completed', '2026-03-02 09:00:00', NULL),
(13, 5, '22 Lebuh Pantai', 'Penang', '10300', '2026-04-02 10:00:00', 'Scheduled', '2026-03-23 11:00:00', NULL),
(14, 6, '88 Jalan Gurney', 'Penang', '10250', '2026-04-02 14:00:00', 'Scheduled', '2026-03-23 14:00:00', NULL),
(15, 3, 'No 12 Jalan Ampang', 'Kuala Lumpur', '50450', '2026-04-05 09:00:00', 'Scheduled', '2026-03-26 08:00:00', NULL),
(16, 5, '22 Lebuh Pantai', 'Penang', '10300', '2026-03-31 10:00:00', 'Rejected', '2026-03-25 09:00:00', 'Provider not available – cancellation by admin');

-- --------------------------------------------------------

--
-- Table structure for table `tblcollector`
--

CREATE TABLE `tblcollector` (
  `collectorID` int(11) NOT NULL,
  `licenseNum` varchar(100) NOT NULL,
  `status` enum('active','inactive','suspended','on duty') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblcollector`
--

INSERT INTO `tblcollector` (`collectorID`, `licenseNum`, `status`) VALUES
(9, '900101-14-5678', 'active'),
(10, '880305-10-4321', 'active'),
(11, '920712-08-1122', 'active'),
(12, '930415-08-3344', 'active'),
(13, '900101-01-1234', 'active'),
(14, '880202-02-5678', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tblissue`
--

CREATE TABLE `tblissue` (
  `issueID` int(11) NOT NULL,
  `requestID` int(11) NOT NULL,
  `jobID` int(11) DEFAULT NULL,
  `reportedBy` int(11) NOT NULL,
  `assignedAdminID` int(11) DEFAULT NULL,
  `assignedAt` datetime DEFAULT NULL,
  `issueType` enum('Operational','Vehicle','Safety','Technical','Other') NOT NULL,
  `severity` enum('Low','Medium','High','Critical') NOT NULL,
  `subject` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','Assigned','Resolved') NOT NULL DEFAULT 'Open',
  `reportedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `resolvedAt` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblissue`
--

INSERT INTO `tblissue` (`issueID`, `requestID`, `jobID`, `reportedBy`, `assignedAdminID`, `assignedAt`, `issueType`, `severity`, `subject`, `description`, `status`, `reportedAt`, `resolvedAt`, `notes`) VALUES
(1, 3, 2, 10, 1, '2026-03-12 10:00:00', 'Vehicle', 'Medium', 'Tyre pressure issue', 'Collector reported that the vehicle tyre pressure dropped during the journey to the collection point.', 'Resolved', '2026-03-12 09:45:00', '2026-03-12 10:10:00', 'Admin advised collector to stop at nearest petrol station to refill tyre pressure.'),
(2, 16, 11, 10, 2, '2026-03-31 10:30:00', 'Operational', 'Medium', 'Provider not available at pickup address', 'Collector arrived but provider was not present at the address. Attempted contact via phone, no answer.', 'Resolved', '2026-03-31 10:20:00', '2026-03-31 10:55:00', 'Admin contacted provider – no response. Cancelled the request and job. Issue resolved.');

-- --------------------------------------------------------

--
-- Table structure for table `tblitem`
--

CREATE TABLE `tblitem` (
  `itemID` int(11) NOT NULL,
  `requestID` int(11) NOT NULL,
  `centreID` int(11) DEFAULT NULL,
  `itemTypeID` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `weight` decimal(5,2) NOT NULL,
  `length` decimal(5,2) NOT NULL,
  `width` decimal(5,2) NOT NULL,
  `height` decimal(5,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Collected','Received','Processed','Recycled','Cancelled') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblitem`
--

INSERT INTO `tblitem` (`itemID`, `requestID`, `centreID`, `itemTypeID`, `description`, `model`, `brand`, `weight`, `length`, `width`, `height`, `image`, `status`) VALUES
(1, 1, NULL, 1, 'Used laptop', 'ThinkPad X1', 'Lenovo', 1.45, 32.00, 22.00, 2.00, 'laptop1.jpg', 'Pending'),
(2, 1, NULL, 3, '24-inch monitor – Slightly scratched', 'UltraSharp U2419H', 'Dell', 4.50, 54.00, 32.00, 5.00, 'monitor1.jpg', 'Pending'),
(3, 2, 1, 2, 'Gaming PC tower – Working', 'Predator Orion', 'Acer', 12.00, 50.00, 20.00, 45.00, 'pc1.jpg', 'Pending'),
(4, 2, 1, 4, 'Inkjet printer – Old / Needs ink refill', 'PIXMA G6020', 'Canon', 7.00, 45.00, 36.00, 20.00, 'printer1.jpg', 'Pending'),
(5, 3, 3, 5, 'Office scanner – Damaged', 'ScanJet Pro', 'HP', 6.00, 45.00, 25.00, 15.00, 'scanner1.jpg', 'Recycled'),
(6, 3, 3, 6, 'Photocopier – Broken', 'iR C3330', 'Canon', 55.00, 120.00, 70.00, 90.00, 'photocopier1.jpg', 'Processed'),
(7, 4, NULL, 18, 'LED TV 55-inch – Screen cracked', 'OLED55C1', 'LG', 14.00, 123.00, 72.00, 8.00, 'tv1.jpg', 'Cancelled'),
(8, 5, NULL, 12, 'External hard drive 2TB', NULL, 'WD', 0.20, 11.00, 8.00, 2.00, 'hdd1.jpg', 'Pending'),
(9, 6, NULL, 24, 'Tablet accessories – Slightly worn', 'TabCase', 'Samsung', 0.30, 25.00, 18.00, 2.50, 'tablet_accessory1.jpg', 'Pending'),
(10, 7, NULL, 1, 'Old laptop, still functional', 'Latitude 5420', 'Dell', 1.80, 32.00, 22.00, 2.50, NULL, 'Pending'),
(11, 8, NULL, 3, '24-inch monitor, minor scratches', 'P2419H', 'Dell', 4.20, 54.00, 32.00, 5.00, NULL, 'Pending'),
(12, 8, NULL, 8, 'Wireless keyboard, missing keys', 'K375s', 'Logitech', 0.60, 43.00, 14.00, 2.00, NULL, 'Pending'),
(13, 9, 1, 4, 'Inkjet printer, needs ink', 'PIXMA TS3150', 'Canon', 5.20, 43.00, 30.00, 16.00, NULL, 'Pending'),
(14, 10, 3, 5, 'Flatbed scanner, glass cracked', 'ScanJet 300', 'HP', 4.80, 45.00, 30.00, 8.00, NULL, 'Pending'),
(15, 10, 3, 10, '2TB external drive, works', 'My Passport', 'WD', 0.22, 11.00, 8.00, 1.80, NULL, 'Pending'),
(16, 11, 1, 1, 'Old laptop, working', 'ThinkPad T460', 'Lenovo', 1.90, 33.00, 23.00, 2.00, NULL, 'Received'),
(17, 12, 2, 2, 'Desktop PC, not booting', 'OptiPlex 7020', 'Dell', 9.50, 44.00, 18.00, 40.00, NULL, 'Processed'),
(18, 12, 2, 3, '27-inch monitor, dead pixels', 'U2719D', 'Dell', 5.80, 62.00, 36.00, 6.00, NULL, 'Recycled'),
(19, 13, NULL, 1, 'MacBook Pro, screen issue', 'A1708', 'Apple', 1.50, 30.00, 21.00, 1.60, NULL, 'Pending'),
(20, 14, NULL, 4, 'Laser printer, paper jam', 'LaserJet M402', 'HP', 11.20, 40.00, 38.00, 27.00, NULL, 'Pending'),
(21, 14, NULL, 5, 'Document scanner, works', 'ImageFormula', 'Canon', 3.80, 31.00, 23.00, 19.00, NULL, 'Pending'),
(22, 15, NULL, 13, 'Wi-Fi router, intermittent', 'AC1900', 'TP-Link', 0.45, 20.00, 20.00, 4.00, NULL, 'Pending'),
(23, 16, NULL, 18, '55-inch LED TV, screen cracked', 'UN55NU6900', 'Samsung', 15.30, 124.00, 72.00, 8.00, NULL, 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `tblitem_type`
--

CREATE TABLE `tblitem_type` (
  `itemTypeID` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `recycle_points` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblitem_type`
--

INSERT INTO `tblitem_type` (`itemTypeID`, `name`, `recycle_points`) VALUES
(1, 'Laptop', 50),
(2, 'PC / CPU', 45),
(3, 'Monitor', 40),
(4, 'Printer', 35),
(5, 'Scanner', 30),
(6, 'Photocopier', 30),
(7, 'Fax Machine', 25),
(8, 'Keyboard', 10),
(9, 'Mouse', 10),
(10, 'External Hard Drive', 20),
(11, 'USB Flash Drive', 15),
(12, 'Power Bank', 15),
(13, 'Router', 15),
(14, 'Modem', 10),
(15, 'Cables', 5),
(16, 'Extension Cord', 5),
(17, 'Adapters', 5),
(18, 'Television', 40),
(19, 'Speaker', 20),
(20, 'Headphones / Earphones', 10),
(21, 'Projector', 35),
(22, 'Camera', 25),
(23, 'Television Accessories', 10),
(24, 'Gaming Accessories', 10),
(25, 'Tablet Accessories', 10),
(26, 'CDs / DVDs', 5),
(27, 'Camera Accessories', 5),
(28, 'Electric Kitchen Appliances', 5),
(29, 'Electric Home Appliances', 5),
(30, 'Other Electronics', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tbljob`
--

CREATE TABLE `tbljob` (
  `jobID` int(11) NOT NULL,
  `requestID` int(11) NOT NULL,
  `collectorID` int(11) NOT NULL,
  `vehicleID` int(11) NOT NULL,
  `scheduledDate` date NOT NULL,
  `scheduledTime` time NOT NULL,
  `estimatedEndTime` time DEFAULT NULL,
  `status` enum('Pending','Scheduled','Ongoing','Completed','Rejected','Cancelled') DEFAULT 'Pending',
  `rejectionReason` varchar(255) DEFAULT NULL,
  `startedAt` datetime DEFAULT NULL,
  `completedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbljob`
--

INSERT INTO `tbljob` (`jobID`, `requestID`, `collectorID`, `vehicleID`, `scheduledDate`, `scheduledTime`, `estimatedEndTime`, `status`, `rejectionReason`, `startedAt`, `completedAt`) VALUES
(1, 2, 9, 1, '2026-03-11', '14:30:00', '16:00:00', 'Rejected', 'Expired – job was not accepted before the scheduled date', NULL, NULL),
(2, 3, 10, 2, '2026-03-12', '09:00:00', '18:30:00', 'Completed', NULL, '2026-03-12 09:05:00', '2026-03-12 18:20:00'),
(3, 6, 11, 4, '2026-03-15', '13:45:00', '20:30:00', 'Rejected', 'Expired – job was not accepted before the scheduled date', NULL, NULL),
(4, 9, 9, 1, '2026-04-10', '09:00:00', '12:00:00', 'Pending', NULL, NULL, NULL),
(5, 10, 10, 2, '2026-04-10', '14:00:00', '18:00:00', 'Pending', NULL, NULL, NULL),
(6, 11, 9, 1, '2026-03-11', '10:00:00', '12:00:00', 'Completed', NULL, '2026-03-11 09:50:00', '2026-03-11 16:20:00'),
(7, 12, 10, 2, '2026-03-12', '09:30:00', '12:30:00', 'Completed', NULL, '2026-03-12 09:20:00', '2026-03-12 17:45:00'),
(8, 13, 9, 1, '2026-04-02', '10:00:00', '12:00:00', 'Scheduled', NULL, NULL, NULL),
(9, 14, 10, 2, '2026-04-02', '14:00:00', '16:30:00', 'Scheduled', NULL, NULL, NULL),
(10, 15, 9, 1, '2026-04-05', '09:00:00', '11:30:00', 'Scheduled', NULL, NULL, NULL),
(11, 16, 10, 2, '2026-03-31', '10:00:00', '12:00:00', 'Cancelled', 'Job cancelled due to provider unavailability', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tblmaintenance`
--

CREATE TABLE `tblmaintenance` (
  `maintenanceID` int(11) NOT NULL,
  `vehicleID` int(11) NOT NULL,
  `type` enum('Routine','Repair','Inspection') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `startDate` date NOT NULL,
  `endDate` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblmaintenance`
--

INSERT INTO `tblmaintenance` (`maintenanceID`, `vehicleID`, `type`, `description`, `startDate`, `endDate`, `status`) VALUES
(1, 3, 'Repair', 'Engine maintenance and oil change', '2026-03-05', NULL, 'In Progress'),
(2, 1, 'Inspection', 'Monthly safety inspection', '2026-03-01', '2026-03-01', 'Completed'),
(3, 5, 'Routine', 'Tyre replacement and cleaning', '2026-03-20', NULL, 'Scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `tblprovider`
--

CREATE TABLE `tblprovider` (
  `providerID` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postcode` varchar(10) NOT NULL,
  `point` int(11) DEFAULT 0,
  `suspended` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblprovider`
--

INSERT INTO `tblprovider` (`providerID`, `address`, `state`, `postcode`, `point`, `suspended`) VALUES
(3, 'No 12 Jalan Ampang', 'Kuala Lumpur', '50450', 120, 0),
(4, '45 Jalan Bukit Bintang', 'Kuala Lumpur', '55100', 165, 0),
(5, '22 Lebuh Pantai', 'Penang', '10300', 150, 0),
(6, '88 Jalan Gurney', 'Penang', '10250', 60, 0),
(7, '10 Jalan Sutera', 'Johor', '81200', 200, 0),
(8, '5 Jalan Austin Heights', 'Johor', '81100', 95, 0),
(15, 'No. 27, Jalan Seri Mutiara 3, Taman Seri Mutiara, Batu Caves', 'Selangor', '68100', 0, 0),
(16, 'No. 118, Jalan Kenari 5/12, Bandar Puchong Jaya, Puchong,', 'Selangor', '47100', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tblusers`
--

CREATE TABLE `tblusers` (
  `userID` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `userType` enum('admin','provider','collector') DEFAULT 'provider',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastLogin` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblusers`
--

INSERT INTO `tblusers` (`userID`, `username`, `fullname`, `email`, `password`, `phone`, `userType`, `createdAt`, `lastLogin`) VALUES
(1, 'admin01', 'Farid Hakim', 'farid.admin@example.com', '$2b$12$Lie7YMpPWbLFs4gbsn0rBeEApju6h0kTma6/1vEjPMjYajlKVEBJG', '0181122334', 'admin', '2026-03-10 05:01:45', '2026-03-14 10:16:00'),
(2, 'admin02', 'Nur Aisyah', 'aisyah.admin@example.com', '$2b$12$40JfCz98ljyCKNeLZix0a.32RjzGut2eB16I5H4AWwO89cIw2nXAC', '0192233445', 'admin', '2026-03-10 05:01:45', '2026-04-04 10:55:00'),
(3, 'provider03', 'Daniel Lee', 'daniel.provider@example.com', '$2b$12$BeyMpwV6SS4Nq4YkizYlE.pCQBE4ZnY45cv.OGn2lVN2V27sfjL0.', '0123344556', 'provider', '2026-03-10 05:01:45', '2026-03-14 10:16:00'),
(4, 'provider04', 'Amirah Zainal', 'amirah.provider@example.com', '$2b$12$ojxeFBnUHwrLpskLI91Ryum.NU1O3IMxvU72p5gRmVuelMcWBcLfG', '0134455667', 'provider', '2026-03-10 05:01:45', '2026-03-14 10:16:00'),
(5, 'provider05', 'Kumar Raj', 'kumar.provider@example.com', '$2b$12$dry6meWw2COvcwHZtMY1te.2tfFbk4zS16ud7IubCIlZqg9V2133m', '0145566778', 'provider', '2026-03-10 05:01:45', '2026-04-04 10:52:00'),
(6, 'provider06', 'Hannah Lim', 'hannah.provider@example.com', '$2b$12$xmKoSMENRNPns49mUNj0lukNzb/HUGQFls7z5jo8bx0W7TkQPf1Oe', '0156677889', 'provider', '2026-03-10 05:01:45', '2026-03-26 16:31:00'),
(7, 'provider07', 'Mohd Firdaus', 'firdaus.collector@example.com', '$2b$12$gNCVZQHrZH.BJSvlA6z4SeedgrRf6lL5rbb/xq/j5tewCFh/j3.iq', '0189900112', 'provider', '2026-03-10 05:01:45', '2026-03-20 08:00:00'),
(8, 'provider08', 'Grace Tan', 'grace.collector@example.com', '$2b$12$Sfnu4ntWdVAp7m46QWyZSeY/qhxiEEqVVUCUjG1bayj1.pTEuIBZu', '0191011223', 'provider', '2026-03-10 05:01:45', '2026-03-21 09:15:00'),
(9, 'collector09', 'Jason Wong', 'jason.collector@example.com', '$2b$12$OhBprtYEYNJfiPqv2L4joea29ik5Y3Bm47Cglj9UKns2n3Y8Xfy9i', '0167788990', 'collector', '2026-03-10 05:01:45', '2026-04-05 09:00:00'),
(10, 'collector10', 'Aina Sofea', 'aina.collector@example.com', '$2b$12$pmnlv9yjnuxIRnM0BM8K9u8woaUmv0ry.AAkzsu/RJtDdUvUtoaXK', '0178899001', 'collector', '2026-03-10 05:01:45', '2026-04-04 10:20:00'),
(11, 'collector11', 'Ahmad Faiz', 'ahmad.collector@example.com', '$2b$12$X/JpJ5mdCnOxwpVnQ5a/yeICZheEhqRjOln0UUMBSpArvN748oLCu', '0134567890', 'collector', '2026-03-18 18:20:15', '2026-03-25 09:45:01'),
(12, 'collector12', 'Siti Aminah', 'siti.collector@example.com', '$2b$12$JwTjny/G4gpl3o/QsvAfOu3WQCpfh/ayFZ0mzkbgztmRr1iWx4fZ2', '0145678901', 'collector', '2026-03-18 18:20:15', '2026-03-20 00:00:00'),
(13, 'collector13', 'Ahmad Zaki', 'ahmad.zaki@example.com', '$2b$12$Lie7YMpPWbLFs4gbsn0rBeEApju6h0kTma6/1vEjPMjYajlKVEBJG', '01122334455', 'collector', '2026-03-20 00:00:00', '2026-03-20 00:00:00'),
(14, 'collector14', 'Siti Nurhaliza', 'siti.nur@example.com', '$2b$12$Lie7YMpPWbLFs4gbsn0rBeEApju6h0kTma6/1vEjPMjYajlKVEBJG', '01233445566', 'collector', '2026-03-20 00:00:00', '2026-03-20 00:00:00'),
(15, 'provider15', 'Lia Ng', 'lia.ng@example.com', '$2b$12$Lie7YMpPWbLFs4gbsn0rBeEApju6h0kTma6/1vEjPMjYajlKVEBJG', '01344556677', 'provider', '2026-03-20 00:00:00', '2026-03-20 00:00:00'),
(16, 'provider16', 'Jolin Tan', 'jolin.tan@example.com', '$2b$12$Lie7YMpPWbLFs4gbsn0rBeEApju6h0kTma6/1vEjPMjYajlKVEBJG', '01455667788', 'provider', '2026-03-20 00:00:00', '2026-03-20 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `tblvehicle`
--

CREATE TABLE `tblvehicle` (
  `vehicleID` int(11) NOT NULL,
  `plateNum` varchar(20) NOT NULL,
  `model` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `capacityWeight` decimal(6,2) NOT NULL,
  `status` enum('Available','In Use','Maintenance','Inactive') DEFAULT 'Available',
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tblvehicle`
--

INSERT INTO `tblvehicle` (`vehicleID`, `plateNum`, `model`, `type`, `capacityWeight`, `status`, `createdAt`) VALUES
(1, 'BKL1234', 'Toyota HiAce', 'Van', 1200.00, 'Available', '2026-03-10 13:01:45'),
(2, 'PKN5678', 'Nissan NV200', 'Van', 1000.00, 'Available', '2026-03-10 13:01:45'),
(3, 'JHR8899', 'Isuzu NPR', 'Truck', 3500.00, 'Maintenance', '2026-03-10 13:01:45'),
(4, 'PNG3344', 'Toyota Hilux', 'Pickup', 900.00, 'Available', '2026-03-10 13:01:45'),
(5, 'SGR7788', 'Ford Transit', 'Van', 1500.00, 'In Use', '2026-03-10 13:01:45');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `requestID` (`requestID`),
  ADD KEY `jobID` (`jobID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `tblcentre`
--
ALTER TABLE `tblcentre`
  ADD PRIMARY KEY (`centreID`);

--
-- Indexes for table `tblcentre_accepted_type`
--
ALTER TABLE `tblcentre_accepted_type`
  ADD PRIMARY KEY (`centreID`,`itemTypeID`),
  ADD KEY `itemTypeID` (`itemTypeID`);

--
-- Indexes for table `tblcollection_request`
--
ALTER TABLE `tblcollection_request`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `providerID` (`providerID`);

--
-- Indexes for table `tblcollector`
--
ALTER TABLE `tblcollector`
  ADD PRIMARY KEY (`collectorID`),
  ADD UNIQUE KEY `licenseNum` (`licenseNum`);

--
-- Indexes for table `tblissue`
--
ALTER TABLE `tblissue`
  ADD PRIMARY KEY (`issueID`),
  ADD KEY `fk_issue_request` (`requestID`),
  ADD KEY `fk_issue_job` (`jobID`),
  ADD KEY `fk_issue_reportedBy` (`reportedBy`),
  ADD KEY `fk_issue_assignedAdmin` (`assignedAdminID`);

--
-- Indexes for table `tblitem`
--
ALTER TABLE `tblitem`
  ADD PRIMARY KEY (`itemID`),
  ADD KEY `centreID` (`centreID`),
  ADD KEY `itemTypeID` (`itemTypeID`);

--
-- Indexes for table `tblitem_type`
--
ALTER TABLE `tblitem_type`
  ADD PRIMARY KEY (`itemTypeID`);

--
-- Indexes for table `tbljob`
--
ALTER TABLE `tbljob`
  ADD PRIMARY KEY (`jobID`),
  ADD KEY `requestID` (`requestID`),
  ADD KEY `collectorID` (`collectorID`),
  ADD KEY `vehicleID` (`vehicleID`);

--
-- Indexes for table `tblmaintenance`
--
ALTER TABLE `tblmaintenance`
  ADD PRIMARY KEY (`maintenanceID`),
  ADD KEY `vehicleID` (`vehicleID`);

--
-- Indexes for table `tblprovider`
--
ALTER TABLE `tblprovider`
  ADD PRIMARY KEY (`providerID`);

--
-- Indexes for table `tblusers`
--
ALTER TABLE `tblusers`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `tblvehicle`
--
ALTER TABLE `tblvehicle`
  ADD PRIMARY KEY (`vehicleID`),
  ADD UNIQUE KEY `plateNum` (`plateNum`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `tblcentre`
--
ALTER TABLE `tblcentre`
  MODIFY `centreID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tblcollection_request`
--
ALTER TABLE `tblcollection_request`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tblissue`
--
ALTER TABLE `tblissue`
  MODIFY `issueID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tblitem`
--
ALTER TABLE `tblitem`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tblitem_type`
--
ALTER TABLE `tblitem_type`
  MODIFY `itemTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbljob`
--
ALTER TABLE `tbljob`
  MODIFY `jobID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tblmaintenance`
--
ALTER TABLE `tblmaintenance`
  MODIFY `maintenanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tblusers`
--
ALTER TABLE `tblusers`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tblvehicle`
--
ALTER TABLE `tblvehicle`
  MODIFY `vehicleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tblactivity_log`
--
ALTER TABLE `tblactivity_log`
  ADD CONSTRAINT `tblactivity_log_ibfk_1` FOREIGN KEY (`requestID`) REFERENCES `tblcollection_request` (`requestID`),
  ADD CONSTRAINT `tblactivity_log_ibfk_2` FOREIGN KEY (`jobID`) REFERENCES `tbljob` (`jobID`),
  ADD CONSTRAINT `tblactivity_log_ibfk_3` FOREIGN KEY (`userID`) REFERENCES `tblusers` (`userID`);

--
-- Constraints for table `tbladmin`
--
ALTER TABLE `tbladmin`
  ADD CONSTRAINT `tbladmin_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `tblusers` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `tblcentre_accepted_type`
--
ALTER TABLE `tblcentre_accepted_type`
  ADD CONSTRAINT `tblcentre_accepted_type_ibfk_1` FOREIGN KEY (`centreID`) REFERENCES `tblcentre` (`centreID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tblcentre_accepted_type_ibfk_2` FOREIGN KEY (`itemTypeID`) REFERENCES `tblitem_type` (`itemTypeID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tblcollection_request`
--
ALTER TABLE `tblcollection_request`
  ADD CONSTRAINT `tblcollection_request_ibfk_1` FOREIGN KEY (`providerID`) REFERENCES `tblprovider` (`providerID`) ON DELETE CASCADE;

--
-- Constraints for table `tblcollector`
--
ALTER TABLE `tblcollector`
  ADD CONSTRAINT `tblcollector_ibfk_1` FOREIGN KEY (`collectorID`) REFERENCES `tblusers` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `tblissue`
--
ALTER TABLE `tblissue`
  ADD CONSTRAINT `fk_issue_assignedAdmin` FOREIGN KEY (`assignedAdminID`) REFERENCES `tblusers` (`userID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_issue_job` FOREIGN KEY (`jobID`) REFERENCES `tbljob` (`jobID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_reportedBy` FOREIGN KEY (`reportedBy`) REFERENCES `tblusers` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_issue_request` FOREIGN KEY (`requestID`) REFERENCES `tblcollection_request` (`requestID`) ON DELETE CASCADE;

--
-- Constraints for table `tblitem`
--
ALTER TABLE `tblitem`
  ADD CONSTRAINT `tblitem_ibfk_1` FOREIGN KEY (`centreID`) REFERENCES `tblcentre` (`centreID`),
  ADD CONSTRAINT `tblitem_ibfk_2` FOREIGN KEY (`itemTypeID`) REFERENCES `tblitem_type` (`itemTypeID`);

--
-- Constraints for table `tbljob`
--
ALTER TABLE `tbljob`
  ADD CONSTRAINT `tbljob_ibfk_1` FOREIGN KEY (`requestID`) REFERENCES `tblcollection_request` (`requestID`),
  ADD CONSTRAINT `tbljob_ibfk_2` FOREIGN KEY (`collectorID`) REFERENCES `tblcollector` (`collectorID`),
  ADD CONSTRAINT `tbljob_ibfk_3` FOREIGN KEY (`vehicleID`) REFERENCES `tblvehicle` (`vehicleID`);

--
-- Constraints for table `tblmaintenance`
--
ALTER TABLE `tblmaintenance`
  ADD CONSTRAINT `tblmaintenance_ibfk_1` FOREIGN KEY (`vehicleID`) REFERENCES `tblvehicle` (`vehicleID`);

--
-- Constraints for table `tblprovider`
--
ALTER TABLE `tblprovider`
  ADD CONSTRAINT `tblprovider_ibfk_1` FOREIGN KEY (`providerID`) REFERENCES `tblusers` (`userID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
