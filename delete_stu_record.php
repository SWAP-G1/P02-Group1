<html>
<body>  
<?php
$con = mysqli_connect("localhost", "admin", "admin", "xyz polytechnic"); // Connect to database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Initialize error message variable
$error_message = "";

// Get and validate the student ID from the GET request
if (isset($_GET["student_id"])) {
    $student_id_code = htmlspecialchars($_GET["student_id"]);

    // Define the regex pattern: 3 digits followed by a letter
    $pattern = '/^\d{3}[A-Z]$/';

    if (preg_match($pattern, $student_id_code)) {
        // Begin transaction for safe deletion
        $con->begin_transaction();

        try {
            // Step 1: Delete from `student` table
            $stmt = $con->prepare("DELETE FROM student WHERE identification_code = ?");
            $stmt->bind_param('s', $student_id_code);

            if (!$stmt->execute()) {
                throw new Exception("Error deleting student record: " . $stmt->error);
            }

            // Step 2: Delete from `user` table
            $stmt = $con->prepare("DELETE FROM user WHERE identification_code = ?");
            $stmt->bind_param('s', $student_id_code);

            if (!$stmt->execute()) {
                throw new Exception("Error deleting user record: " . $stmt->error);
            }

            // Commit transaction
            $con->commit();

            // Redirect to the student profile form upon successful deletion
            header("Location: create_stu_recordform.php");
            exit;

        } catch (Exception $e) {
            // Rollback transaction in case of error
            $con->rollback();
            $error_message = "Error deleting record: " . $e->getMessage();
        }

    } else {
        // If validation fails
        $error_message = "Invalid Student ID format. It must be 3 digits followed by an alphabet.";
    }
} else {
    $error_message = "Student ID not provided.";
}

// Close SQL connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS -->
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if (!empty($error_message)): ?>
                <h2>Error</h2>
                <p style="color: red;"><?php echo $error_message; ?></p>
                <button onclick="window.history.back()">Back</button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
