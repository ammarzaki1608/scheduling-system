-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Sep 11, 2025 at 10:13 PM
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
-- Database: `appointment_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `Appointment_ID` int(11) NOT NULL,
  `Agent_ID` int(11) NOT NULL,
  `Customer_Name` varchar(150) NOT NULL,
  `Case_Number` varchar(100) NOT NULL,
  `Subject` varchar(255) NOT NULL,
  `Start_At` datetime NOT NULL,
  `End_At` datetime NOT NULL,
  `Status` enum('Pending','Completed','Missed') DEFAULT 'Pending',
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`Appointment_ID`, `Agent_ID`, `Customer_Name`, `Case_Number`, `Subject`, `Start_At`, `End_At`, `Status`, `Created_At`) VALUES
(1, 2, 'Ali', '23487052', 'Wifi Issue', '2025-09-28 03:30:00', '2025-09-28 05:00:00', 'Pending', '2025-09-11 19:48:02'),
(2, 2, 'Ahmad', '22786717', 'Print Issue', '2025-09-12 15:50:00', '2025-09-12 16:50:00', 'Pending', '2025-09-11 19:50:49'),
(3, 2, 'Adam', '21346789', 'scanner issue', '2025-09-19 04:06:00', '2025-09-19 05:06:00', 'Pending', '2025-09-11 20:06:29');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `Notification_ID` int(11) NOT NULL,
  `Appointment_ID` int(11) NOT NULL,
  `Message` text NOT NULL,
  `Type` enum('Reminder','Missed','System') NOT NULL,
  `Send_At` datetime NOT NULL,
  `Is_Read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pods`
--

CREATE TABLE `pods` (
  `Pod_ID` int(11) NOT NULL,
  `Pod_Name` varchar(100) NOT NULL,
  `Team_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pods`
--

INSERT INTO `pods` (`Pod_ID`, `Pod_Name`, `Team_ID`) VALUES
(1, 'Pod Alpha', 1),
(2, 'Pod Alpha', 1);

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `Team_ID` int(11) NOT NULL,
  `Team_Name` varchar(100) NOT NULL,
  `Color_Code` varchar(7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`Team_ID`, `Team_Name`, `Color_Code`) VALUES
(1, 'Support Team A', '#3498db'),
(2, 'Support Team B', '#ff4013');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `User_Name` varchar(100) NOT NULL,
  `Email` varchar(150) NOT NULL,
  `Role` varchar(20) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Created_At` timestamp NOT NULL DEFAULT current_timestamp(),
  `Pod_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `User_Name`, `Email`, `Role`, `Password`, `Created_At`, `Pod_ID`) VALUES
(1, 'Admin User', 'admin@example.com', 'admin', '$2y$10$43tpt/oqN/AqvH0/4A/Pyu2FD3PLDmnzD164QO3BhYQ6O7E.FNM3W', '2025-09-04 11:57:08', NULL),
(2, 'Agent User', 'agent@example.com', 'agent', '$2y$10$XsjpQr9a3vrYX7GKNSOlBuFbO/iwkSpKVOHtXjVG58bb0WE819Zk2', '2025-09-04 11:57:08', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`Appointment_ID`),
  ADD KEY `Agent_ID` (`Agent_ID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`Notification_ID`),
  ADD KEY `Appointment_ID` (`Appointment_ID`);

--
-- Indexes for table `pods`
--
ALTER TABLE `pods`
  ADD PRIMARY KEY (`Pod_ID`),
  ADD KEY `Team_ID` (`Team_ID`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`Team_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `Pod_ID` (`Pod_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `Appointment_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `Notification_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pods`
--
ALTER TABLE `pods`
  MODIFY `Pod_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `Team_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`Agent_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`Appointment_ID`) REFERENCES `appointments` (`Appointment_ID`) ON DELETE CASCADE;

--
-- Constraints for table `pods`
--
ALTER TABLE `pods`
  ADD CONSTRAINT `pods_ibfk_1` FOREIGN KEY (`Team_ID`) REFERENCES `teams` (`Team_ID`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`Pod_ID`) REFERENCES `pods` (`Pod_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
