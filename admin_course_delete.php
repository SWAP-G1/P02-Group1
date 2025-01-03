<?php
session_start(); // Start the session

// Connect to database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Verify CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token. Possible CSRF attack detected.');
}

// Prepare the statement 
$stmt = $con->prepare("DELETE FROM course WHERE course_code=?");

$del_coursecode = htmlspecialchars($_GET["course_code"]);

$stmt->bind_param('s', $del_coursecode); // Bind the parameters
if ($stmt->execute()) {
    echo "Delete Query executed.";
    header("location:admin_course_create_form.php");
    exit();
} else {
    echo "Error executing DELETE query.";
}
?>
