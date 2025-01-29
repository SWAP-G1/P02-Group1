-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 29, 2025 at 10:59 AM
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
  `class_type` enum('Semester','Term') NOT NULL,
  `faculty_identification_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`class_code`, `course_code`, `class_type`, `faculty_identification_code`) VALUES
('HC12', 'T00', 'Semester', 'F224'),
('PC01', 'T00', 'Semester', 'F224'),
('PC22', 'T00', 'Semester', 'F224'),
('YY12', 'T75', 'Semester', 'F224');

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
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_identification_code` varchar(4) NOT NULL,
  `school_code` varchar(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_identification_code`, `school_code`) VALUES
('F224', 'IIT'),
('F224', 'IIT');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `created_at`) VALUES
(1, 'hamizan@gmail.com', '0406ad26b2104f4fc09561c3d671a6797fc9986bcac0f2d25334f08f93eab3326824f33e33a5606800612c1cd91be77ddd36', '2025-01-19 07:53:27'),
(2, 'hamizvn@gmail.com', '402385a0f8b884e4d006508ef92e938d63d90ed5b4f24e2a85fc39bee65330abe4e4e0545360f9011324eda531313965d016', '2025-01-19 07:54:17'),
(8, 'hamizvn@gmail.com', '90c6cad9fb07a736d863ad97cd717710e0a8d4f710806b7f1fe353026e3d21bbd9b265b42e0cc3111b855817b50a6a235da0', '2025-01-19 09:04:08'),
(10, 'hamizvn@gmail.com', '98b6c13ef902eb87f120a69a16566611f0d42048676ac5cc2457c2709b891a52aed0201bdfc693e5929458c6dc82743bcfc5', '2025-01-19 09:05:04'),
(13, 'syedalwee07@gmail.com', '89a4deda2970de39ae7cc9e7ddd27fb69c42fc6b17fe330cadd92c024ea19eff94e3b27408719caedbc90cf74bb45967c9cf', '2025-01-26 13:08:00'),
(14, 'syedalwee07@gmail.com', '699ea6bf4e8f120965df1e90c292aee13d4948fa301bdb695c17102cd45f6a94d53abad18cbc5303a227c73572ea74bfec34', '2025-01-26 13:12:31');

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

--
-- Dumping data for table `semester_gpa_to_course_code`
--

INSERT INTO `semester_gpa_to_course_code` (`grade_id`, `identification_code`, `course_code`, `course_score`, `grade`) VALUES
(16, '239B', 'T00', 3.0, 'B');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `identification_code` varchar(4) NOT NULL,
  `diploma_code` varchar(5) NOT NULL,
  `class_code` varchar(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`identification_code`, `diploma_code`, `class_code`) VALUES
('239B', 'CDF', 'PC22'),
('234A', 'ACFN', 'HC12'),
('263G', 'CDF', 'HC12');

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
  `full_name` varchar(50) NOT NULL,
  `login_tracker` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`identification_code`, `email`, `password`, `role_id`, `phone_number`, `full_name`, `login_tracker`) VALUES
('234A', 'syedalwee09@gmail.com', '$2y$10$XbdH2QHpltLDkkxGfhQl3.MJgBeMVemf7SZFVPJRLAB3iOWeD4rri', 3, 82424436, 'alwee', 1),
('239B', 'syedalwee08@gmail.com', '$2y$10$XbdH2QHpltLDkkxGfhQl3.MJgBeMVemf7SZFVPJRLAB3iOWeD4rri', 3, 82424436, 'HAMIZAN BIN ALWEE', 1),
('263G', 'syedalwee07@gmail.com', '$2y$10$XbdH2QHpltLDkkxGfhQl3.MJgBeMVemf7SZFVPJRLAB3iOWeD4rri', 3, 82424436, 'alwee', 1),
('A001', 'admin@gmail.com', '$2y$10$oeRWBpo3K7kGG814fCE7KuquNTg9lP6jqjZkWc/LPKr9F7U1jBzc.', 1, 93893589, 'ADMIN', 0),
('F223', 'fac2@gmail.com', '$2y$10$jSwwiX7ZYZq5AH.JG.4WmeL4d2NLGXoir72.VBJkHT1NzpDsoJraW', 2, 12343, 'AGUS SALIM', 0),
('F224', 'fac@gmail.com', '$2y$10$T7ZrKH06qdFgX5e/kC6e2uxBdVI4MSwYqd8RJui6KCU.mRGio2sdK', 2, 93893588, 'RAPHAEL FOO', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`class_code`),
  ADD KEY `course_code_idx` (`course_code`),
  ADD KEY `faculty_identification_code_idx` (`faculty_identification_code`) USING BTREE;

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
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD KEY `identification_code_idx` (`faculty_identification_code`) USING BTREE,
  ADD KEY `faculty_school_code_idx` (`school_code`) USING BTREE;

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `class_code_idx` (`class_code`) USING BTREE;

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
  ADD KEY `role_id_idx` (`role_id`),
  ADD KEY `email_idx` (`email`) USING BTREE,
  ADD KEY `full_name_idx` (`full_name`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `semester_gpa_to_course_code`
--
ALTER TABLE `semester_gpa_to_course_code`
  MODIFY `grade_id` int(4) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `class`
--
ALTER TABLE `class`
  ADD CONSTRAINT `course_code_fk` FOREIGN KEY (`course_code`) REFERENCES `course` (`course_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_faculty` FOREIGN KEY (`faculty_identification_code`) REFERENCES `faculty` (`faculty_identification_code`);

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
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`faculty_identification_code`) REFERENCES `user` (`identification_code`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `faculty_school_code_fk` FOREIGN KEY (`school_code`) REFERENCES `school` (`school_code`) ON DELETE CASCADE ON UPDATE CASCADE;

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
