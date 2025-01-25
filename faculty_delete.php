<?php
session_start();

// Connect to the database 'xyz polytechnic_danial'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: faculty_score.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Variable to hold the error message
$error_message = '';

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        global $error_message;
        $error_message = 'Error: Invalid CSRF token!';
        return false;
    }
    return true;
}

// Check if the delete request is made
if (isset($_GET['operation']) && $_GET['operation'] == 'delete') {
    $csrf_token = $_GET["csrf_token"] ?? '';
    if (!check_csrf_token($csrf_token)) {
        // If CSRF token is invalid, display the error message
        include 'error_page.php';
        exit();
    }

    // Get the ID from the URL parameters
    $id = $_GET["id"] ?? '';

    if ($id) {
        // Delete the record from the database
        $query = $connect->prepare("DELETE FROM semester_gpa_to_course_code WHERE grade_id=?");
        $query->bind_param('i', $id); // Bind the parameter
        if ($query->execute()) {
            // If the record was deleted, redirect to the admin page
            header("Location: faculty_score.php");
            exit();
        } else {
            // If deletion failed, set the error message
            $error_message = 'Error: Unable to delete record!';
            include 'error_page.php';
            exit();
        }
    } else {
        // If no ID is provided in the URL, set an error message and show the error page
        $error_message = 'Error: No ID provided for deletion!';
        include 'error_page.php';
        exit();
    }
} else {
    // If the delete operation is not specified, set an error message and show the error page
    $error_message = 'Error: Invalid operation!';
    include 'error_page.php';
    exit();
}
?>
