<?php
// Start the session to manage user authentication and CSRF protection
session_start();
// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Connect to the database 'xyz polytechnic'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
// Check if the database connection was successful
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Verify if the user is logged in and has admin privileges (role = 1)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect non-logged-in users or non-admin users to the login page
    header("Location: ../login.php");
    exit();
}

// Generate CSRF token if it's not already set for the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to validate the CSRF token
function check_csrf_token($csrf_token) {
    // Check if CSRF token matches the one stored in the session
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    return true;
}

// Check if the delete operation is requested
if (isset($_GET['operation']) && $_GET['operation'] == 'delete') {
    // Retrieve CSRF token from the request
    $csrf_token = $_GET["csrf_token"] ?? '';
    // Validate the CSRF token
    if (!check_csrf_token($csrf_token)) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Get the ID of the record to be deleted from URL parameters
    $id = $_GET["id"] ?? '';

    if ($id) {
        // Fetch the course_code associated with the given grade_id
        $query = $connect->prepare("SELECT course_code FROM semester_gpa_to_course_code WHERE grade_id = ?");
        $query->bind_param('i', $id);
        $query->execute();
        $query->bind_result($course_code);
        $query->fetch();
        $query->close();

        // Check if a course was found for the provided grade ID
        if (!$course_code) {
            header("Location: admin_score.php?error=" . urlencode("No course found for the given student score."));
            exit();
        }

        // Verify if the course status is "Ended" before allowing deletion
        $course_query = $connect->prepare("SELECT status FROM course WHERE course_code = ?");
        $course_query->bind_param('s', $course_code);
        $course_query->execute();
        $course_query->bind_result($course_status);
        $course_query->fetch();
        $course_query->close();

        // Prevent deletion if the course is still ongoing
        if ($course_status !== 'Ended') {
            header("Location: admin_score.php?error=" . urlencode("Cannot delete student score. Course is still in progress."));
            exit();
        }

        // Proceed to delete the student score if the course has ended
        $delete_query = $connect->prepare("DELETE FROM semester_gpa_to_course_code WHERE grade_id=?");
        $delete_query->bind_param('i', $id);
        if ($delete_query->execute()) {
            // Regenerate CSRF token after successful deletion to maintain security
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: admin_score.php?success=3"); // Redirect with success message
            exit();
        } else {
            header("Location: admin_score.php?error=" . urlencode("Unable to delete record."));
            exit();
        }
    } else {
        // Handle case where no ID was provided for deletion
        header("Location: admin_score.php?error=" . urlencode("No ID provided for deletion."));
        exit();
    }
} else {
    // Handle cases where the delete operation was not properly specified
    header("Location: admin_score.php?error=" . urlencode("Error executing DELETE query."));
    exit();
}
?>
