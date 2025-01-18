<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database

if (!$con) {
    die('Could not connect to the database: ' . mysqli_connect_error());
}

// Verify CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token. Possible CSRF attack detected.');
}

// Get and validate the student ID from the GET request
if (isset($_GET["student_id"])) {
    $student_id_code = htmlspecialchars($_GET["student_id"]);

    // Define the regex pattern: 3 digits followed by a letter
    $pattern = '/^\d{3}[A-Z]$/';

    if (preg_match($pattern, $student_id_code)) {
        // Delete the record from the `student` table
        $stmt = $con->prepare("DELETE FROM student WHERE identification_code = ?");
        $stmt->bind_param('s', $student_id_code);

        if ($stmt->execute()) {
            $stmt->close();

            // Delete the record from the `user` table
            $stmt = $con->prepare("DELETE FROM user WHERE identification_code = ?");
            $stmt->bind_param('s', $student_id_code);

            if ($stmt->execute()) {
                $stmt->close();
                header("Location: admin_create_stu_recordform.php?success=3");
                exit();
            } else {
                header("Location: admin_create_stu_recordform.php?error=" . urlencode("Error deleting user record: " . $stmt->error));
                exit();
            }
        } else {
            header("Location: admin_create_stu_recordform.php?error=" . urlencode("Error deleting student record: " . $stmt->error));
            exit();
        }
    } else {
        // Invalid Student ID format
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Invalid Student ID format. It must be 3 digits followed by an alphabet."));
        exit();
    }
} else {
    // Student ID not provided
    header("Location: admin_create_stu_recordform.php?error=" . urlencode("Student ID not provided."));
    exit();
}

// Close SQL connection
$con->close();
?>
