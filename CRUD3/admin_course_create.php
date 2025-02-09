<?php
// Start the session to access session variables
session_start();

// Check if the user is logged in as an admin
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // If the session role is not set or the user is not an admin, redirect to the login page
    header("Location: ../login.php");
    exit();
}

// CSRF Token Verification:
// Check that a CSRF token is present in the POST data and that it matches the token stored in the session
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    // If the token is missing or invalid, terminate the script with an error message
    die("Invalid CSRF token");
}

// Connect to the MySQL database using MySQLi
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    // If the connection fails, stop execution and display the connection error
    die('Could not connect: ' . mysqli_connect_errno());
}

// Retrieve and initialize inputs from the POST request using the null coalescing operator
$course_code = $_POST['course_code'] ?? '';
$course_name = $_POST['course_name'] ?? '';
$diploma_code = $_POST['diploma_code'] ?? '';
$start_date = $_POST['course_start_date'] ?? '';
$end_date = $_POST['course_end_date'] ?? '';
$status = $_POST['status'] ?? '';

// Check if the request method is POST (ensuring data is coming from form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Set default values if not provided
    $status = $_POST['status'] ?? 'No Status';
    $start_date = $_POST['course_start_date'] ?? '';
    $end_date = $_POST['course_end_date'] ?? '';

    // If the status is 'No Status' but dates are provided, redirect with an error
    if ($status === 'No Status' && (!empty($start_date) || !empty($end_date))) {
        header("Location: admin_course_create_form.php?error=" . urlencode("Cannot specify dates when status is 'No Status'"));
        exit();
    }
}

// Normalize date inputs:
// If start date or end date is empty, set them to NULL (for database compatibility)
if (empty($start_date)) {
    $start_date = NULL;
} else {
    $start_date = $start_date;
}

if (empty($end_date)) {
    $end_date = NULL;
} else {
    $end_date = $end_date;
}

// Validate date logic:
// Check if both dates are provided and ensure the start date is not later than the end date
if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Start date cannot be after end date"));
    exit();
}

// Validate required fields:
// Ensure that course code, course name, and diploma code are provided
if (empty($course_code) || empty($course_name) || empty($diploma_code)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Course code and Course name are required."));
    exit();
}

// Validate the course code format using a regular expression:
// The expected format is one uppercase letter followed by two digits (e.g., "A12")
if (!preg_match("/^[A-Z]{1}\d{2}$/", $course_code)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Invalid course code format"));
    exit();
}

// Revalidate date order for extra safety:
// Ensure that if both start and end dates are provided, the end date is strictly after the start date
if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("End date must be after start date"));
    exit();
}

// Verify that the diploma code exists in the database:
// Prepare a statement to select a record from the diploma table matching the provided diploma code
$diploma_check = $con->prepare("SELECT 1 FROM diploma WHERE diploma_code = ?");
$diploma_check->bind_param('s', $diploma_code);
$diploma_check->execute();

// If no diploma is found, redirect back with an error message
if ($diploma_check->get_result()->num_rows === 0) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Invalid diploma code"));
    exit();
}

// Check for duplicate courses:
// Prepare a statement to select any existing course with the same course code
$course_check = $con->prepare("SELECT course_code FROM course WHERE course_code = ?");
$course_check->bind_param('s', $course_code);
$course_check->execute();

// If a course with the same course code already exists, redirect with an error
if ($course_check->get_result()->num_rows > 0) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Course code already exists"));
    exit();
}

// Insert the new course into the database:
// Prepare the INSERT statement with placeholders for the course data
$insert = $con->prepare("
    INSERT INTO course 
    (course_code, course_name, diploma_code, course_start_date, course_end_date, status)
    VALUES (?, ?, ?, ?, ?, ?)
");
// Bind the form data to the prepared statement (all as strings)
$insert->bind_param('ssssss', $course_code, $course_name, $diploma_code, $start_date, $end_date, $status);

// Execute the insertion query
if ($insert->execute()) {
    // If the insertion is successful, regenerate the CSRF token for the next form submission
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // Redirect back to the course creation form with a success message (success=1)
    header("Location: admin_course_create_form.php?success=1");
} else {
    // If the insertion fails, redirect back with an error message that includes the database error
    header("Location: admin_course_create_form.php?error=" . urlencode("Error creating course: " . $con->error));
}

// Close the database connection
$con->close();
?>
