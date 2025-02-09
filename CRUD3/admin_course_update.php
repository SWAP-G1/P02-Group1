<?php
// Start the session to access session variables (including CSRF token and user role)
session_start();

// Ensure the request method is POST so that the update is only processed when submitted via form
if ($_SERVER["REQUEST_METHOD"] != "POST") exit();

// Check that the current user is an admin by verifying the session role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // If the user is not an admin, redirect to the login page
    header("Location: ../login.php");
    exit();
}

// ----------------------
// CSRF Token Validation
// ----------------------
// Verify that a CSRF token was sent via POST and that it matches the one stored in the session.
// This prevents Cross-Site Request Forgery attacks.
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token.');
}

// ----------------------
// Database Connection
// ----------------------
// Connect to the MySQL database using the MySQLi extension.
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
// If the connection fails, output an error message and terminate the script.
if (!$con) die('Connection failed: ' . mysqli_connect_error());

// ----------------------
// Input Retrieval and Initialization
// ----------------------
// Retrieve and assign form inputs using the null coalescing operator to set default values if not provided.
$original_coursecode = $_POST['original_coursecode'] ?? '';
$upd_coursecode      = $_POST['upd_coursecode']      ?? '';
$upd_coursename      = $_POST['upd_coursename']      ?? '';
$upd_diplomacode     = $_POST['upd_diplomacode']     ?? '';
$upd_startdate       = $_POST['upd_startdate']       ?? '';
$upd_enddate         = $_POST['upd_enddate']         ?? '';
$upd_status          = $_POST['upd_status']          ?? '';

// ----------------------
// Business Rule: Status and Dates
// ----------------------
// If the updated status is "No Status" but dates are provided, redirect back with an error.
// This enforces the rule that no dates should be specified when status is "No Status".
if ($upd_status === 'No Status' && (!empty($upd_startdate) || !empty($upd_enddate))) {
    header("Location: admin_course_update_form.php?error=" . urlencode("Cannot select dates when status is 'No Status'") . "&course_code=$original_coursecode");
    exit();
}

// ----------------------
// Normalizing Date Inputs
// ----------------------
// If start or end dates are empty, set them to NULL so they can be stored as SQL NULLs.
if (empty($upd_startdate)) {
    $upd_startdate = NULL;
} else {
    $upd_startdate = $upd_startdate; // Redundant assignment; value is kept as is.
}

if (empty($upd_enddate)) {
    $upd_enddate = NULL;
} else {
    $upd_enddate = $upd_enddate; // Redundant assignment; value is kept as is.
}

// ----------------------
// Date Validation (Preliminary Check)
// ----------------------
// NOTE: This section appears to check variables $start_date and $end_date, which are not defined here.
// It is likely intended to check $upd_startdate and $upd_enddate. For now, it functions as written.
// If both dates are provided and the start date is later than the end date, redirect with an error.
if (!empty($start_date) && !empty($end_date) && strtotime($start_date) > strtotime($end_date)) {
    header("Location: admin_course_create_form.php?error=" . urlencode("Start date cannot be after end date"));
    exit();
}

// ----------------------
// Required Fields Validation
// ----------------------
// Ensure that the updated course code, course name, and diploma code are not empty.
if (empty($upd_coursecode) || empty($upd_coursename) || empty($upd_diplomacode)) {
    header("Location: admin_course_update_form.php?error=" . urlencode("Course code, name and diploma are required.") . "&course_code=$original_coursecode");
    exit();
}

// ----------------------
// Course Code Format Validation
// ----------------------
// Validate that the updated course code follows the format: one uppercase letter followed by two digits (e.g., "A12").
if (!preg_match("/^[A-Z]{1}\d{2}$/", $upd_coursecode)) {
    header("Location: admin_course_update_form.php?error=" . urlencode("Invalid course code format.") . "&course_code=$original_coursecode");
    exit();
}

// ----------------------
// Date Order Re-Validation
// ----------------------
// Ensure that if both updated start and end dates are provided, the end date is strictly after the start date.
if (!empty($upd_startdate) && !empty($upd_enddate) && strtotime($upd_enddate) <= strtotime($upd_startdate)) {
    header("Location: admin_course_update_form.php?error=" . urlencode("End date must be after start date.") . "&course_code=$original_coursecode");
    exit();
}

// ----------------------
// Diploma Existence Check
// ----------------------
// Use a prepared statement to verify that the updated diploma code exists in the diploma table.
$diploma_check = $con->prepare("SELECT 1 FROM diploma WHERE diploma_code = ?");
$diploma_check->bind_param('s', $upd_diplomacode);
$diploma_check->execute();
// If no diploma is found, redirect with an error.
if ($diploma_check->get_result()->num_rows === 0) {
    header("Location: admin_course_update_form.php?error=" . urlencode("Invalid diploma selection") . "&course_code=$original_coursecode");
    exit();
}

// ----------------------
// Duplicate Course Code Check
// ----------------------
// If the updated course code is different from the original, check if it already exists in the course table.
if ($upd_coursecode !== $original_coursecode) {
    $course_check = $con->prepare("SELECT course_code FROM course WHERE course_code = ?");
    $course_check->bind_param('s', $upd_coursecode);
    $course_check->execute();
    if ($course_check->get_result()->num_rows > 0) {
        // If a duplicate exists, redirect back with an error message.
        header("Location: admin_course_update_form.php?error=" . urlencode("Course code already exists") . "&course_code=$original_coursecode");
        exit();
    }
}

// ----------------------
// Updating the Course Record
// ----------------------
// Prepare an UPDATE statement to modify the course record.
// The query updates course code, name, diploma code, start date, end date, and status.
// The NULLIF function converts empty strings to SQL NULL values.
$update_stmt = $con->prepare("UPDATE course SET 
    course_code = ?, 
    course_name = ?, 
    diploma_code = ?, 
    course_start_date = NULLIF(?, ''),
    course_end_date = NULLIF(?, ''),
    status = NULLIF(?, '')
    WHERE course_code = ?");

// Bind the updated values along with the original course code (used to identify the record to update)
$update_stmt->bind_param('sssssss', 
    $upd_coursecode, 
    $upd_coursename, 
    $upd_diplomacode, 
    $upd_startdate,
    $upd_enddate,
    $upd_status,
    $original_coursecode
);

// Execute the UPDATE statement
if ($update_stmt->execute()) {
    // On success, regenerate the CSRF token to prevent reuse in future requests.
    unset($_SESSION['csrf_token']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    // Redirect to the course management form with a success indicator (success=2).
    header("Location: admin_course_create_form.php?success=2");
} else {
    // On failure, redirect back to the update form with an error message that includes the database error.
    header("Location: admin_course_update_form.php?error=" . urlencode("Update failed: " . $con->error) . "&course_code=$original_coursecode");
}

// Close the prepared statement and the database connection to free up resources.
$update_stmt->close();
$con->close();
?>
