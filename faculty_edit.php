<?php
session_start();

$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        $error_message = 'Error: Invalid CSRF token!';
        include 'error_page.php';
        exit();
    }
}

// Function to assign grades based on course score
function assign_grade($course_score)
{
    if ($course_score == 4.0) {
        return 'A';
    } elseif ($course_score >= 3.5 && $course_score < 4.0) {
        return 'B+';
    } elseif ($course_score >= 3.0 && $course_score < 3.5) {
        return 'B';
    } elseif ($course_score >= 2.5 && $course_score < 3.0) {
        return 'C+';
    } elseif ($course_score >= 2.0 && $course_score < 2.5) {
        return 'C';
    } elseif ($course_score >= 1.5 && $course_score < 2.0) {
        return 'D+';
    } elseif ($course_score >= 1.0 && $course_score < 1.5) {
        return 'D';
    } elseif ($course_score >= 0.0 && $course_score < 1.0) {
        return 'F';
    } else {
        return 'X';
    }
}

// Check if an ID is passed via GET and retrieve record data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare and execute the select query
    $query = $connect->prepare("SELECT identification_code, course_code, course_score, grade FROM semester_gpa_to_course_code WHERE grade_id = ?");
    $query->bind_param('i', $id);
    $query->execute();
    $query->bind_result($identification_code, $course_code, $course_score, $grade);
    $query->fetch();
}

// Check if the form is submitted for an update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_button'])) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    check_csrf_token($csrf_token);

    $id = $_POST['id'];
    $identification_code = $_POST['identification_code'];
    $course_code = $_POST['course_code'];
    $course_score = (float)$_POST["course_score"];
    $grade = assign_grade($course_score); // Automatically assign grade

    if ($grade == 'X') {
        // If the combination exists, display an error message
        $error_message = 'Error: Course score has to be within 0.00 - 4.00!';
        include 'error_page.php';
        exit();
    }

    // Prepare and execute the update query
    $update_query = $connect->prepare("UPDATE semester_gpa_to_course_code SET course_score = ?, grade = ? WHERE grade_id = ?");
    $update_query->bind_param('dsi', $course_score, $grade, $id);
    if ($update_query->execute()) {
        echo "<script>
            alert('Student record updated successfully!');
            window.location.href = 'faculty_score.php';
        </script>";
        exit();
    } else {
        $error_message = 'Error: Failed to update record.';
        include 'error_page.php';
        exit();
    }
    $update_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src="bwlogo-removebg.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="faculty_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Edit Score Record</h2>
            <p>Changes student's Course Score and Grade.</p>
        </div>

        <div class="card">
            <h3>Student Score Details</h3>
            <form method="post" action="faculty_edit.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <input type="text" name="identification_code" value="<?php echo htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8'); ?>" readonly />
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8'); ?>" readonly />
                </div>
                <div class="form-group">
                    <label class="label" for="course_score">Course Score</label>
                    <input type="text" name="course_score" value="<?php echo htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="form-group">
                    <label class="label" for="grade">Grade</label>
                    <input type="text" name="grade" value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" readonly />
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" name="update_button" value="Update Button">Update Score</button>
            </form>
        </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

</body>
</html>
