-- Insert roles
INSERT INTO `role` (`role_id`, `role_name`) VALUES
('1', 'admin'),
('2', 'faculty'),
('3', 'student');

-- Insert schools
INSERT INTO `school` (`school_code`, `school_name`) VALUES
('IIT', 'School of Information Technology'),
('BUS', 'School of Business'),
('ENG', 'School of Engineering'),
('DES', 'School of Design');

-- Insert diplomas (2 per school)
INSERT INTO `diploma` (`diploma_code`, `diploma_name`, `school_code`) VALUES
('DPIT', 'Diploma in Information Technology', 'IIT'),
('DPIS', 'Diploma in Information Security', 'IIT'),
('DPBM', 'Diploma in Business Management', 'BUS'),
('DPFN', 'Diploma in Finance', 'BUS'),
('DPME', 'Diploma in Mechanical Engineering', 'ENG'),
('DPEE', 'Diploma in Electrical Engineering', 'ENG'),
('DPID', 'Diploma in Industrial Design', 'DES'),
('DPMD', 'Diploma in Media Design', 'DES');

-- Insert courses (2 per diploma)
INSERT INTO `course` (`course_code`, `course_name`, `diploma_code`, `course_start_date`, `course_end_date`, `status`) VALUES
-- IIT courses
('I01', 'Programming Fundamentals', 'DPIT', '2025-01-15', '2025-05-15', 'In-progress'),
('I02', 'Database Systems', 'DPIT', '2025-01-15', '2025-05-15', 'In-progress'),
('I21', 'Network Security', 'DPIS', '2025-01-15', '2025-05-15', 'In-progress'),
('I22', 'Cybersecurity Basics', 'DPIS', '2025-01-15', '2025-05-15', 'In-progress'),
-- BUS courses
('B01', 'Business Administration', 'DPBM', '2025-01-15', '2025-05-15', 'In-progress'),
('B02', 'Marketing Principles', 'DPBM', '2025-01-15', '2025-05-15', 'In-progress'),
('F21', 'Financial Accounting', 'DPFN', '2025-01-15', '2025-05-15', 'In-progress'),
('F22', 'Investment Banking', 'DPFN', '2025-01-15', '2025-05-15', 'In-progress'),
-- ENG courses
('M01', 'Mechanics', 'DPME', '2025-01-15', '2025-05-15', 'In-progress'),
('M02', 'Thermodynamics', 'DPME', '2025-01-15', '2025-05-15', 'In-progress'),
('E21', 'Circuit Theory', 'DPEE', '2025-01-15', '2025-05-15', 'In-progress'),
('E22', 'Power Systems', 'DPEE', '2025-01-15', '2025-05-15', 'In-progress'),
-- DES courses
('D01', 'Industrial Design Basics', 'DPID', '2025-01-15', '2025-05-15', 'In-progress'),
('D02', 'Product Design', 'DPID', '2025-01-15', '2025-05-15', 'In-progress'),
('M21', 'Digital Media', 'DPMD', '2025-01-15', '2025-05-15', 'In-progress'),
('M22', 'Visual Communication', 'DPMD', '2025-01-15', '2025-05-15', 'In-progress');

-- Insert faculty users
INSERT INTO `user` (`identification_code`, `email`, `password`, `role_id`, `phone_number`, `full_name`, `login_tracker`) VALUES
('F101', 'jiun110506@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654321, 'LIN ZHAO', 1),
('F102', 'f102@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654322, 'AHMAD RAZAK', 1),
('F103', 'f103@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654323, 'KIM MING', 1),
('F104', 'f104@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654324, 'SITI AMINAH', 1),
('F105', 'f105@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654324, 'SITI DELISHA', 1),
('F106', 'f106@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654325, 'SITI ALYA', 1),
('F107', 'f107@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654326, 'NUR AMANDA', 1),
('F108', 'f108@gmail.com', '$2y$10$YKNsMbM8nGlMWqcnMSVCDeqyb.gfOdjQXB2.QsvA/ObPkO6w6GRN.', 2, 87654327, 'ILHAN JIUN', 1);
-- Insert faculty assignments
INSERT INTO `faculty` (`faculty_identification_code`, `school_code`) VALUES
('F101', 'IIT'),
('F102', 'BUS'),
('F103', 'ENG'),
('F104', 'DES'),
('F105', 'IIT'),
('F106', 'BUS'),
('F107', 'ENG'),
('F108', 'DES');

-- Insert classes (2 per course)
INSERT INTO `class` (`class_code`, `course_code`, `class_type`, `faculty_identification_code`) VALUES
-- IIT classes
('IT01', 'I01', 'Semester', 'F101'),
('IT02', 'I02', 'Semester', 'F101'),
('IT03', 'I21', 'Semester', 'F101'),
('IT04', 'I22', 'Semester', 'F101'),
-- BUS classes
('BU01', 'B01', 'Semester', 'F102'),
('BU02', 'B02', 'Semester', 'F102'),
('BU03', 'F21', 'Semester', 'F102'),
('BU04', 'F22', 'Semester', 'F102'),
-- ENG classes
('EG01', 'M01', 'Semester', 'F103'),
('EG02', 'M02', 'Semester', 'F103'),
('EG03', 'E21', 'Semester', 'F103'),
('EG04', 'E22', 'Semester', 'F103'),
-- DES classes
('DS01', 'D01', 'Semester', 'F104'),
('DS02', 'D02', 'Semester', 'F104'),
('DS03', 'M21', 'Semester', 'F104'),
('DS04', 'M22', 'Semester', 'F104');

-- Insert student users (1 per class)
INSERT INTO `user` (`identification_code`, `email`, `password`, `role_id`, `phone_number`, `full_name`, `login_tracker`) VALUES
-- IIT students
('S101', 'hamizvn@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234561, 'LEE WEI', 1),
('S102', 's102@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234562, 'TAN MEI', 1),
('S103', 's103@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234563, 'WONG HAO', 1),
('S104', 's104@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234564, 'CHEN XIONG', 1),
-- BUS students
('S201', 'gaminghamizan@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234561, 'NURUL IMAN', 1),
('S202', 's202@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234562, 'RAHMAN SHAH', 1),
('S203', 's203@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234563, 'SITI ZUBAIDAH', 1),
('S204', 's204@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234564, 'MUHAMAD RIZAL', 1),
-- ENG students
('S301', 'chatgptacc987@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234565, 'LIM HONG', 1),
('S302', 's302@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234566, 'NG CHENG', 1),
('S303', 's303@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234567, 'KOH YUAN', 1),
('S304', 's304@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 91234568, 'ZHANG WEI', 1),
-- DES students
('S401', 'syedalwee07@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234565, 'DEWI PUTRI', 1),
('S402', 's402@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234566, 'RAVI KUMAR', 1),
('S403', 's403@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234567, 'MEI LING', 1),
('S404', 's404@gmail.com', '$2y$10$t1Yb0LEVO8/CDTKrBL81.eKv95nI3cDvZCm9Q/tMLY34rNv2s5QHG', 3, 81234568, 'PARK MIN', 1);

-- Insert student records
INSERT INTO `student` (`identification_code`, `diploma_code`, `class_code`) VALUES
-- IIT students
('S101', 'DPIT', 'IT01'),
('S102', 'DPIT', 'IT02'),
('S103', 'DPIS', 'IT03'),
('S104', 'DPIS', 'IT04'),
-- BUS students
('S201', 'DPBM', 'BU01'),
('S202', 'DPBM', 'BU02'),
('S203', 'DPFN', 'BU03'),
('S204', 'DPFN', 'BU04'),
-- ENG students
('S301', 'DPME', 'EG01'),
('S302', 'DPME', 'EG02'),
('S303', 'DPEE', 'EG03'),
('S304', 'DPEE', 'EG04'),
-- DES students
('S401', 'DPID', 'DS01'),
('S402', 'DPID', 'DS02'),
('S403', 'DPMD', 'DS03'),
('S404', 'DPMD', 'DS04');

-- Insert student scores (sample GPA for each student)
INSERT INTO `student_score` (`identification_code`, `semester_gpa`) VALUES
('S101', 3.50),
('S102', 3.67),
('S103', 3.45),
('S104', 3.78),
('S201', 3.56),
('S202', 3.89),
('S203', 3.67),
('S204', 3.45),
('S301', 3.78),
('S302', 3.90),
('S303', 3.45),
('S304', 3.67),
('S401', 3.89),
('S402', 3.56),
('S403', 3.78),
('S404', 3.90);