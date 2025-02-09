<?php
// Start the session to manage user data
session_start();
// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Connect to the database 'xyz polytechnic_danial'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
// Check if the connection to the database is successful
if ($connect->connect_error) {
    // Terminate the script if connection fails and display the error message
    die("Connection failed: " . $connect->connect_error);
}

// Check if the user is logged in and has the correct role (admin role = 1)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Create a random CSRF token
}

// Function to check the validity of CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        // Terminate the script if CSRF token is invalid to prevent CSRF attacks
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    return true; // Return true if CSRF token is valid
}

// Check if the delete request is made
if (isset($_GET['operation']) && $_GET['operation'] == 'delete') {
    // Retrieve CSRF token from the URL parameters
    $csrf_token = $_GET["csrf_token"] ?? '';
    if (!check_csrf_token($csrf_token)) {
        // If CSRF token is invalid, display the error message
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Get the identification_code from the URL parameters
    $identification_code = $_GET["identification_code"] ?? '';

    if ($identification_code) {
        // Prepare the SQL statement to delete the record securely
        $query = $connect->prepare("DELETE FROM student_score WHERE identification_code=?");
        $query->bind_param('s', $identification_code); // Bind the identification_code parameter
        if ($query->execute()) {
            // Regenerate CSRF token after successful deletion to prevent reuse
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // If the record was deleted, redirect to the admin page with success message
            header("Location: admin_gpa.php?success=3");
            exit();
        } else {
            // If deletion failed, set the error message and redirect
            header("Location: admin_gpa.php?error=" . urlencode("Unable to delete GPA record."));
            exit();
        }
    } else {
        // If no ID is provided in the URL, set an error message and redirect
        header("Location: admin_gpa.php?error=" . urlencode("No ID provided for deletion."));
        exit();
    }
} else {
    // If the delete operation is not specified, set an error message and redirect
    header("Location: admin_gpa.php?error=" . urlencode("Error executing DELETE query."));
    exit();
}
?>
