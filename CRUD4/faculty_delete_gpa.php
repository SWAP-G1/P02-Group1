<?php
session_start();

// Connect to the database 'xyz polytechnic_danial'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location:  ../login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    return true;
}

// Check if the delete request is made
if (isset($_GET['operation']) && $_GET['operation'] == 'delete') {
    $csrf_token = $_GET["csrf_token"] ?? '';
    if (!check_csrf_token($csrf_token)) {
        // If CSRF token is invalid, display the error message
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Get the ID from the URL parameters
    $id = $_GET["id"] ?? '';

    if ($id) {
        // Delete the record from the database
        $query = $connect->prepare("DELETE FROM student_score WHERE identification_code=?");
        $query->bind_param('i', $id); // Bind the parameter
        if ($query->execute()) {
            // If the record was deleted, redirect to the admin page
            header("Location: faculty_gpa.php?success=3");
            exit();
        } else {
            // If deletion failed, set the error message
            header("Location: faculty_gpa.php?error=" . urlencode("Unable to delete GPA record."));
            exit();
        }
    } else {
        // If no ID is provided in the URL, set an error message and show the error page
        header("Location: faculty_gpa.php?error=" . urlencode("No ID provided for deletion."));
        exit();
    }
} else {
    // If the delete operation is not specified, set an error message and show the error page
    header("Location: faculty_gpa.php?error=" . urlencode("Error executing DELETE query."));
    exit();
}
?>
