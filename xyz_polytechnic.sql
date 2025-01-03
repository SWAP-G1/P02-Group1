-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 03, 2025 at 11:21 AM
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
-- Database: `xyz polytechnic`
--

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `class_code` varchar(4) NOT NULL,
  `course_code` varchar(3) NOT NULL,
  `class_type` enum('Semester','Term') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course`
--

CREATE TABLE `course` (
  `course_code` varchar(3) NOT NULL,
  `course_name` varchar(50) NOT NULL,
  `diploma_code` varchar(5) NOT NULL,
  `course_start_date` date NOT NULL,
  `course_end_date` date NOT NULL,
  `status` enum('To start','In-progress','Ended') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course`
--

INSERT INTO `course` (`course_code`, `course_name`, `diploma_code`, `course_start_date`, `course_end_date`, `status`) VALUES
('T00', 'Financial Shit', 'ACFN', '2025-01-23', '2025-01-17', 'To start'),
('T75', 'Digital Forensics', 'CDF', '2024-12-18', '2024-12-28', 'To start'),
('T89', 'Ethical Hacking', 'CDF', '2024-12-13', '2025-03-05', 'To start');

-- --------------------------------------------------------

--
-- Table structure for table `diploma`
--

CREATE TABLE `diploma` (
  `diploma_code` varchar(5) NOT NULL,
  `diploma_name` varchar(50) NOT NULL,
  `school_code` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `diploma`
--

INSERT INTO `diploma` (`diploma_code`, `diploma_name`, `school_code`) VALUES
('ACFN', 'ACCOUNTANCY & FINANCE', 'BUS'),
('CDF', 'Cybersecurity & Digital Forensics', 'IIT');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` enum('1','2','3') NOT NULL,
  `role_name` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`) VALUES
('1', 'Admin'),
('2', 'Faculty'),
('3', 'Student');

-- --------------------------------------------------------

--
-- Table structure for table `schedule`
--

CREATE TABLE `schedule` (
  `class_code` varchar(4) NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `day_of_week` varchar(9) NOT NULL,
  `classroom_location` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school`
--

CREATE TABLE `school` (
  `school_code` varchar(3) NOT NULL,
  `school_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school`
--

INSERT INTO `school` (`school_code`, `school_name`) VALUES
('BUS', 'Business'),
('IIT', 'Information and Informatics Technology');

-- --------------------------------------------------------

--
-- Table structure for table `semester_gpa_to_course_code`
--

CREATE TABLE `semester_gpa_to_course_code` (
  `grade_id` int(4) NOT NULL,
  `identification_code` varchar(4) NOT NULL,
  `course_code` varchar(3) NOT NULL,
  `course_score` decimal(2,1) NOT NULL,
  `grade` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `identification_code` varchar(4) NOT NULL,
  `diploma_code` varchar(5) NOT NULL,
  `class_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_score`
--

CREATE TABLE `student_score` (
  `identification_code` varchar(4) NOT NULL,
  `semester_gpa` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `identification_code` varchar(4) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(3) NOT NULL,
  `phone_number` int(8) NOT NULL,
  `full_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`identification_code`, `email`, `password`, `role_id`, `phone_number`, `full_name`) VALUES
('239B', 'hamizan@gmail.com', '$2y$10$q9/IW9y8vIbZwz.YN8snTO5vKO/gtD61dCeFIvnJQa0gB2mfMVDx6', 3, 82424436, 'HAMIZAN BIN ALWEE'),
('A001', 'admin@gmail.com', '$2y$10$oeRWBpo3K7kGG814fCE7KuquNTg9lP6jqjZkWc/LPKr9F7U1jBzc.', 1, 93893589, 'ADMIN'),
('F224', 'fac@gmail.com', '$2y$10$T7ZrKH06qdFgX5e/kC6e2uxBdVI4MSwYqd8RJui6KCU.mRGio2sdK', 2, 93893588, 'RAPHAEL FOO');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_code`),
  ADD KEY `course_code_idx` (`course_code`);

--
-- Indexes for table `course`
--
ALTER TABLE `course`
  ADD PRIMARY KEY (`course_code`),
  ADD KEY `diploma_code_idx` (`diploma_code`);

--
-- Indexes for table `diploma`
--
ALTER TABLE `diploma`
  ADD PRIMARY KEY (`diploma_code`),
  ADD KEY `school_code_idx` (`school_code`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `schedule`
--
ALTER TABLE `schedule`
  ADD KEY `class_code_idx` (`class_code`);

--
-- Indexes for table `school`
--
ALTER TABLE `school`
  ADD PRIMARY KEY (`school_code`);

--
-- Indexes for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `course_code_idx` (`course_code`),
  ADD KEY `identification_code_idx` (`identification_code`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD KEY `identification_code_idx` (`identification_code`),
  ADD KEY `diploma_code_idx` (`diploma_code`),
  ADD KEY `class_code` (`class_code`);

--
-- Indexes for table `student_score`
--
ALTER TABLE `student_score`
  ADD KEY `identification_code_idx` (`identification_code`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`identification_code`),
  ADD KEY `role_id_idx` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  MODIFY `grade_id` int(4) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `course_code_fk` FOREIGN KEY (`course_code`) REFERENCES `course` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `course`
--
ALTER TABLE `course`
  ADD CONSTRAINT `diploma_code_fk2` FOREIGN KEY (`diploma_code`) REFERENCES `diploma` (`diploma_code`);

--
-- Constraints for table `diploma`
--
ALTER TABLE `diploma`
  ADD CONSTRAINT `diploma_to_school_fk` FOREIGN KEY (`school_code`) REFERENCES `school` (`school_code`);

--
-- Constraints for table `schedule`
--
ALTER TABLE `schedule`
  ADD CONSTRAINT `schedule_to_class_code_fk` FOREIGN KEY (`class_code`) REFERENCES `class` (`class_code`);

--
-- Constraints for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  ADD CONSTRAINT `semester_course_code_fk` FOREIGN KEY (`course_code`) REFERENCES `course` (`course_code`),
  ADD CONSTRAINT `semester_gpa_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_class_code_fk` FOREIGN KEY (`class_code`) REFERENCES `class` (`class_code`),
  ADD CONSTRAINT `student_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_to_diploma_code_fk` FOREIGN KEY (`diploma_code`) REFERENCES `diploma` (`diploma_code`);

--
-- Constraints for table `student_score`
--
ALTER TABLE `student_score`
  ADD CONSTRAINT `student_score_identification_code_fk` FOREIGN KEY (`identification_code`) REFERENCES `user` (`identification_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
