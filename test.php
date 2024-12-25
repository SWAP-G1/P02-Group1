<?php
// Start session
session_start();

// Generate CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Initialize variables
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("CSRF token validation failed.");
    }

    $class_code = isset($_POST["class_code"]) ? htmlspecialchars($_POST["class_code"]) : "";
    $course_code = isset($_POST["course_code"]) ? htmlspecialchars($_POST["course_code"]) : "";
    $class_type = isset($_POST["class_type"]) ? htmlspecialchars($_POST["class_type"]) : "";

    // Regex patterns
    $class_code_pattern = "/^[A-Z]{2}[0-9]{2}$/";
    $course_code_pattern = "/^[A-Z][0-9]{2}$/";

    // Validate input
    if (!preg_match($class_code_pattern, $class_code)) {
        $error_message = "Invalid class code format.";
    } elseif (!preg_match($course_code_pattern, $course_code)) {
        $error_message = "Invalid course code format.";
    } else {
        // Check if course_code exists
        $course_check_stmt = $con->prepare("SELECT * FROM course WHERE course_code = ?");
        $course_check_stmt->bind_param('s', $course_code);
        $course_check_stmt->execute();
        $course_check_result = $course_check_stmt->get_result();

        // Check if class_code exists
        $class_check_stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
        $class_check_stmt->bind_param('s', $class_code);
        $class_check_stmt->execute();
        $class_check_result = $class_check_stmt->get_result();

        if ($course_check_result->num_rows == 0) {
            $error_message = "The course code \"$course_code\" does not exist.";
        } elseif ($class_check_result->num_rows > 0) {
            $error_message = "The class code \"$class_code\" already exists.";
        } else {
            // Insert class
            $stmt = $con->prepare("INSERT INTO `class` (`class_code`, `course_code`, `class_type`) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $class_code, $course_code, $class_type);
            if ($stmt->execute()) {
                header("Location: admin_class_create_form.php");
                exit();
            } else {
                $error_message = "Error executing query: " . $stmt->error;
            }
            $stmt->close();
        }

        $course_check_stmt->close();
        $class_check_stmt->close();
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Class Create Form</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="#">Home</a>
            <a href="#">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Add, update, and organize class records.</p>
        </div>

        <div class="card">
            <h3>Class Details</h3>
            <?php if (!empty($error_message)) { echo "<p style='color: red;'>$error_message</p>"; } ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="class_code" placeholder="Enter Class code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="course_code" placeholder="Enter Course Code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="class_type" required>
                        <option value="" disabled selected>Select a Class Type</option>
                        <option value="Semester">Semester</option>
                        <option value="Term">Term</option>
                    </select>
                </div>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>
</body>
</html>
