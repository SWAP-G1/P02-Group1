<!-- admin_course_delete.php -->
<?php
session_start();

// Database connection
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Connection failed: ' . mysqli_connect_error());
}

// CSRF validation
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

// Delete course
if (isset($_GET['course_code'])) {
    $stmt = $con->prepare("DELETE FROM course WHERE course_code = ?");
    $stmt->bind_param('s', $_GET['course_code']);
    
    if ($stmt->execute()) {
        // Regenerate CSRF token
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Redirect back to course management
        header("Location: admin_course_create_form.php?success=3");
        exit();
    } else {
        die('Error deleting course');
    }
} else {
    die('Course code not provided');
}
?>