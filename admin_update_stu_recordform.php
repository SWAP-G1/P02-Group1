<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
$error_message = "";

if (!$con) {
    $error_message = 'Could not connect: ' . mysqli_connect_errno();
}
// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: testlogin.php");
    exit();
}
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
?>
<html>
    <head>
    <script>
        // Function to fetch course details for a given class code
        async function fetchCourseDetails(inputId, courseCodeId, courseNameId) {
            const classCode = document.getElementById(inputId).value;
            if (!classCode) {
                alert("Please enter a class code.");
                return;
            }

            try {
                const response = await fetch(`fetch_course_details.php?class_code=${classCode}`);
                const data = await response.json();
                if (data.error) {
                    alert(data.error);
                    document.getElementById(courseCodeId).textContent = "N/A";
                    document.getElementById(courseNameId).textContent = "N/A";
                } else {
                    document.getElementById(courseCodeId).textContent = data.course_code;
                    document.getElementById(courseNameId).textContent = data.course_name;
                }
            } catch (err) {
                alert("Failed to fetch course details. Please try again.");
            }
        }
    </script>
    </head>

<body>
<?php

// Catch the submitted student ID
$student_id = htmlspecialchars($_GET["student_id"]);

// Initialize error message
$error_message = "";

// Validate student ID format (3 digits followed by 1 uppercase letter)
$pattern_student_id = '/^\d{3}[A-Z]$/';
if (!preg_match($pattern_student_id, $student_id)) {
    $error_message = "Error: Invalid student ID format.";
}

// Proceed only if no validation error
if (!$error_message) {
    // Fetch student details along with class codes
    $stmt = $con->prepare("
        SELECT u.full_name, u.phone_number, u.identification_code, s.diploma_code, s.class_code
        FROM user u
        JOIN student s ON u.identification_code = s.identification_code
        WHERE u.identification_code = ?
    ");
    $stmt->bind_param('s', $student_id); // Bind the student ID parameter
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize variables for class codes
    $class_codes = ['', '', ''];

    // Fetch the rows and assign class codes to appropriate slots
    $index = 0;
    while ($row = $result->fetch_assoc()) {
        if ($index < 3) {
            $class_codes[$index] = $row['class_code'];
            $student_name = $row['full_name'];
            $phone_number = $row['phone_number'];
            $diploma_code = $row['diploma_code'];
            $identification_code = $row['identification_code'];
        }
        $index++;
    }

    // If no records are found, show an error
    if ($index === 0) {
        $error_message = "Error: Student record not found.";
    }
}

// Close the database connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Record</title>
    <link rel="stylesheet" href="main_styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="admin_create_stu_recordform.php">Back to Student Records</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Update Student Record</h2>
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
                <button onclick="window.history.back()">Back</button>
            <?php else: ?>
                <form method="POST" action="admin_update_stu_record.php?student_id=<?php echo htmlspecialchars($student_id); ?>">
                    <div class="form-group">
                        <label class="label" for="student_name">Student Name</label>
                        <input type="text" name="upd_student_name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label" for="phone_number">Phone Number</label>
                        <input type="text" name="upd_phone_number" maxlength="8" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label" for="diploma_code">Diploma Code</label>
                        <input type="text" name="upd_diploma_code" value="<?php echo htmlspecialchars($diploma_code); ?>" required>
                    </div>
                <div class="form-group">
                    <label class="label" for="class_code_1">Class Code 1</label>
                    <input type="text" id="class_code_1" name="upd_class_code_1" placeholder="Enter Class Code 1" value="<?php echo htmlspecialchars($class_codes[0]); ?>" required>
                    <button type="button" onclick="fetchCourseDetails('class_code_1', 'course_code_1', 'course_name_1')">Search</button>
                    <p>Course Code: <span id="course_code_1">N/A</span></p>
                    <p>Course Name: <span id="course_name_1">N/A</span></p>
                </div>

                <!-- Class Code 2 -->
                <div class="form-group">
                    <label class="label" for="class_code_2">Class Code 2</label>
                    <input type="text" id="class_code_2" name="upd_class_code_2" placeholder="Enter Class Code 2" value="<?php echo htmlspecialchars($class_codes[1]); ?>">
                    <button type="button" onclick="fetchCourseDetails('class_code_2', 'course_code_2', 'course_name_2')">Search</button>
                    <p>Course Code: <span id="course_code_2">N/A</span></p>
                    <p>Course Name: <span id="course_name_2">N/A</span></p>
                </div>

                <!-- Class Code 3 -->
                <div class="form-group">
                    <label class="label" for="class_code_3">Class Code 3</label>
                    <input type="text" id="class_code_3" name="upd_class_code_3" placeholder="Enter Class Code 3" value="<?php echo htmlspecialchars($class_codes[2]); ?>">
                    <button type="button" onclick="fetchCourseDetails('class_code_3', 'course_code_3', 'course_name_3')">Search</button>
                    <p>Course Code: <span id="course_code_3">N/A</span></p>
                    <p>Course Name: <span id="course_name_3">N/A</span></p>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit">Update Record</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>
</body>
</html>
