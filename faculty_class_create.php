<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }

    // Retrieve form data
    $class_code = isset($_POST["class_code"]) ? htmlspecialchars($_POST["class_code"]) : "";
    $course_code = isset($_POST["course_code"]) ? htmlspecialchars($_POST["course_code"]) : "";
    $class_type = isset($_POST["class_type"]) ? htmlspecialchars($_POST["class_type"]) : "";

    // Initialize error message
    $error_message = "";

    // Regex pattern for validating class_code
    $class_code_pattern = "/^[A-Z]{2}[0-9]{2}$/"; // Must start with 2 letters followed by exactly 2 digits

    // Regex pattern for validating course_code
    $course_code_pattern = "/^[A-Z][0-9]{2}$/"; // Must start with a capital letter followed by exactly 2 digits
   
    // Validate the format of class_code using regex
    if (!preg_match($class_code_pattern, $class_code)) {
        $error_message = "Invalid class code format.";
    } 
    // Validate the format of course_code using regex
    elseif (!preg_match($course_code_pattern, $course_code)) {
        $error_message = "Invalid course code format.";
    } 
    else {
        // Check if the course_code exists in the `course` table
        $course_check_stmt = $con->prepare("SELECT * FROM course WHERE course_code = ?");
        $course_check_stmt->bind_param('s', $course_code);
        $course_check_stmt->execute();
        $course_check_result = $course_check_stmt->get_result();

        // Check if the class_code already exists in the `class` table
        $class_check_stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
        $class_check_stmt->bind_param('s', $class_code);
        $class_check_stmt->execute();
        $class_check_result = $class_check_stmt->get_result();

        // Validate course_code and ensure no duplicate class_code
        if ($course_check_result->num_rows == 0) {
            $error_message = "The course code \"$course_code\" does not exist. Please use a valid course code.";
        } elseif ($class_check_result->num_rows > 0) {
            $error_message = "The class code \"$class_code\" already exists. Please use a unique class code.";
        } else {
            // Prepare the SQL statement for insertion
            $stmt = $con->prepare("INSERT INTO `class` (`class_code`, `course_code`, `class_type`) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $class_code, $course_code, $class_type);
            // Execute the query
            if ($stmt->execute()) {
                header("Location: faculty_class_create_form.php");
                exit();
            } else {
                $error_message = "Error executing INSERT query: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        }

        // Close additional statements
        $course_check_stmt->close();
        $class_check_stmt->close();
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
