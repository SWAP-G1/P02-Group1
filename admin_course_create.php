<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }

    // Retrieve form data
    $course_code = isset($_POST["course_code"]) ? htmlspecialchars($_POST["course_code"]) : "";
    $course_name = isset($_POST["course_name"]) ? htmlspecialchars($_POST["course_name"]) : "";
    $diploma_code = isset($_POST["diploma_code"]) ? htmlspecialchars($_POST["diploma_code"]) : "";
    $course_start_date = isset($_POST["course_start_date"]) ? htmlspecialchars($_POST["course_start_date"]) : "";
    $course_end_date = isset($_POST["course_end_date"]) ? htmlspecialchars($_POST["course_end_date"]) : "";
    $status = isset($_POST["status"]) ? htmlspecialchars($_POST["status"]) : "";

    // Initialize error message
    $error_message = "";

    // Regex pattern for validating course_code
    $course_code_pattern = "/^[A-Z]{1}[0-9]{2}$/"; // Must start with 1 letter followed by exactly 2 digits

    // Regex pattern for validating diploma_code
    $diploma_code_pattern = "/^[A-Z]{3,4}$/"; // Must be 3 or 4 capital letters

    // Validate the format of course_code using regex
    if (!preg_match($course_code_pattern, $course_code)) {
        $error_message = "Invalid course code format.";
    }  
    elseif (!preg_match($diploma_code_pattern, $diploma_code)) {
            $error_message = "Invalid diploma code format.";
    }
    else {
        // Check if the course_code already exists in the `course` table
        $course_code_check_stmt = $con->prepare("SELECT * FROM course WHERE course_code = ?");
        $course_code_check_stmt->bind_param('s', $course_code);
        $course_code_check_stmt->execute();
        $course_code_check_result = $course_code_check_stmt->get_result();

        // Check if the course_name already exists in the `course` table
        $course_name_check_stmt = $con->prepare("SELECT * FROM course WHERE course_name = ?");
        $course_name_check_stmt->bind_param('s', $course_name);
        $course_name_check_stmt->execute();
        $course_name_check_result = $course_name_check_stmt->get_result();

        // Check if the diploma_code exists in the `course` table
        $diploma_code_check_stmt = $con->prepare("SELECT * FROM diploma WHERE diploma_code = ?");
        $diploma_code_check_stmt->bind_param('s', $diploma_code);
        $diploma_code_check_stmt->execute();
        $diploma_code_check_result = $diploma_code_check_stmt->get_result();        

        // Ensure no duplicate course_code, ensure no duplicate course_name, validate diploma_code 
        if ($course_code_check_result->num_rows > 0) {
            $error_message = "The course code \"$course_code\" already exists. Please use a unique course code.";
        } elseif ($course_name_check_result->num_rows > 0) {
            $error_message = "The coures name \"$course_name\" already exists. Please use a unique course code.";
        } elseif ($diploma_code_check_result->num_rows == 0) {
            $error_message = "The diploma code \"$diploma_code\" does not exist. Please use an existing diploma code.";
        }
        else {
            // Prepare the SQL statement for insertion
            $stmt = $con->prepare("INSERT INTO `course` (`course_code`, `course_name`, `diploma_code`, `course_start_date`, `course_end_date`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $course_code, $course_name, $diploma_code, $course_start_date, $course_end_date, $status);
            // Execute the query
            if ($stmt->execute()) {
                header("Location: admin_course_create_form.php");
                exit();
            } else {
                $error_message = "Error executing INSERT query: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        }

        // Close additional statements
        $course_code_check_stmt->close();
        $class_name_check_stmt->close();
        $diploma_code_check_stmt->close();
    }

    // Close the database connection
    $con->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
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