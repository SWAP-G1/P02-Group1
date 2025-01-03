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
    $upd_coursecode = isset($_POST["upd_coursecode"]) ? htmlspecialchars($_POST["upd_coursecode"]) : "";
    $upd_coursename = isset($_POST["upd_coursename"]) ? htmlspecialchars($_POST["upd_coursename"]) : "";
    $upd_diplomacode = isset($_POST["upd_diplomacode"]) ? htmlspecialchars($_POST["upd_diplomacode"]) : "";
    $upd_coursestartdate = isset($_POST["upd_coursestartdate"]) ? htmlspecialchars($_POST["upd_coursestartdate"]) : "";
    $upd_courseenddate = isset($_POST["upd_courseenddate"]) ? htmlspecialchars($_POST["upd_courseenddate"]) : "";
    $upd_status = isset($_POST["upd_status"]) ? htmlspecialchars($_POST["upd_status"]) : "";
    $original_coursecode = isset($_POST["original_coursecode"]) ? htmlspecialchars($_POST["original_coursecode"]) : "";
    $original_coursename = isset($_POST["original_coursename"]) ? htmlspecialchars($_POST["original_coursename"]) : "";

    // Initialize error message
    $error_message = "";

    // Regex pattern for validating course_code
    $course_code_pattern = "/^[A-Z]{1}[0-9]{2}$/"; // Must start with 1 letter followed by exactly 2 digits

    // Regex pattern for validating diploma_code
    $diploma_code_pattern = "/^[A-Z]{3,4}$/"; // Must be 3 or 4 capital letters

    // Validate the format of updated course_code using regex
    if (!preg_match($course_code_pattern, $upd_coursecode)) {
        $error_message = "Invalid course code format.";
    } 
    // Validate the format of updated course_code using regex
    elseif (!preg_match($course_code_pattern, $upd_coursecode)) {
        $error_message = "Invalid course code format.";
    } 
    else {
        // Check if the updated course_code is already used by another course
        $course_code_check_stmt = $con->prepare("SELECT * FROM course WHERE course_code = ? AND course_code != ?");
        $course_code_check_stmt->bind_param('ss', $upd_coursecode, $original_coursecode);
        $course_code_check_stmt->execute();
        $course_code_check_result = $course_code_check_stmt->get_result();

        // Check if the course_name already exists in the `course` table
        $course_name_check_stmt = $con->prepare("SELECT * FROM course WHERE course_name = ? AND course_name != ?");
        $course_name_check_stmt->bind_param('ss', $upd_coursename, $original_coursename);
        $course_name_check_stmt->execute();
        $course_name_check_result = $course_name_check_stmt->get_result();        

        // Check if the diploma_code exists in the `diploma` table
        $diploma_code_check_stmt = $con->prepare("SELECT * FROM diploma WHERE diploma_code = ?");
        $diploma_code_check_stmt->bind_param('s', $upd_diplomacode);
        $diploma_code_check_stmt->execute();
        $diploma_code_check_result = $diploma_code_check_stmt->get_result();


        // Ensure no duplicate course_code, ensure no duplicate course_name, validate diploma_code 
        if ($course_code_check_result->num_rows > 0) {
            $error_message = "The course code \"$upd_coursecode\" is already being used. Please use a unique course code.";
        } elseif ($course_name_check_result->num_rows > 0 && $upd_coursename !== $original_coursename) {
            $error_message = "The course name \"$upd_coursename\" is already being used. Please use a unique course name.";
        } elseif ($diploma_code_check_result->num_rows == 0) {
            $error_message = "The diploma code \"$upd_diploma_code\" does not exist. Please use an existing diploma code.";
        }
        else {
            // Prepare the SQL statement for insertion
            $stmt = $con->prepare("UPDATE course SET course_code = ?, course_name = ?, diploma_code = ?, course_start_date = ?, course_end_date = ?, status = ? WHERE course_code = ?");
            $stmt->bind_param('sssssss', $upd_coursecode, $upd_coursename, $upd_diplomacode, $upd_coursestartdate, $upd_courseenddate, $upd_status, $original_coursecode);

            // Execute the query
            if ($stmt->execute()) {
                header("Location: admin_course_create_form.php");
                exit();
            } else {
                $error_message = "Error executing UPDATE query: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        }

        // Close additional statements
        $course_code_check_stmt->close();
        $course_name_check_stmt->close();
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
