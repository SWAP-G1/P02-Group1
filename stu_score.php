<?php
session_start();

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");

if (!$connect) {
    die("Connection failed: " . mysqli_connect_error());
}

// Variable to hold error message
$error_message = '';

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Check if the student is logged in (role_id = 3)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 3) {
    // Redirect to login page if the user is not logged in as a student
    header("Location: testlogin.php");
    exit();
}

// Get the logged-in student's identification code from session
$identification_code = $_SESSION['session_identification_code'] ?? '';

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error_message = 'Error: Invalid CSRF token!';
        include 'error_page.php';
        exit();
    }
}

// Fetch GPA and grades for the logged-in student
$query_gpa = $connect->prepare("SELECT semester_gpa FROM student_score WHERE identification_code = ?");
$query_gpa->bind_param('s', $identification_code);
$query_gpa->execute();
$query_gpa->bind_result($gpa);
$query_gpa->fetch();
$query_gpa->close();

// Fetch student's course grades
$query_grades = $connect->prepare("SELECT course_code, course_score, grade FROM semester_gpa_to_course_code WHERE identification_code = ?");
$query_grades->bind_param('s', $identification_code);
$query_grades->execute();
$result_grades = $query_grades->get_result();
$query_grades->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Scores</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src="bwlogo-removebg.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
        <a href="stu_dashboard.php">Home</a>
        <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student GPA and Grades</h2>
            <p><strong>GPA: </strong><?php echo htmlspecialchars($gpa, ENT_QUOTES, 'UTF-8'); ?></p>

            <h3>Course Grades</h3>
            <table border="1" bgcolor="white" align="center">
                <tr>
                    <th>Course Code</th>
                    <th>Course Score</th>
                    <th>Grade</th>
                </tr>

                <?php while ($row = $result_grades->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['course_score'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($row['grade'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

</body>
</html>
