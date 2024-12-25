<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }

    // Retrieve form data
    $upd_classcode = isset($_POST["upd_classcode"]) ? htmlspecialchars($_POST["upd_classcode"]) : "";
    $upd_coursecode = isset($_POST["upd_coursecode"]) ? htmlspecialchars($_POST["upd_coursecode"]) : "";
    $upd_classtype = isset($_POST["upd_classtype"]) ? htmlspecialchars($_POST["upd_classtype"]) : "";
    $original_classcode = isset($_POST["original_classcode"]) ? htmlspecialchars($_POST["original_classcode"]) : "";

    // Initialize error message
    $error_message = "";

    // Regex pattern for validating class_code
    $class_code_pattern = "/^[A-Z]{2}[0-9]{2}$/"; // Must start with 2 letters followed by exactly 2 digits

    // Regex pattern for validating course_code
    $course_code_pattern = "/^[A-Z][0-9]{2}$/"; // Must start with a capital letter followed by exactly 2 digits

    // Validate the format of updated class_code using regex
    if (!preg_match($class_code_pattern, $upd_classcode)) {
        $error_message = "Invalid class code format.";
    } 
    // Validate the format of updated course_code using regex
    elseif (!preg_match($course_code_pattern, $upd_coursecode)) {
        $error_message = "Invalid course code format.";
    } 
    else {
        // Check if the course_code exists in the `course` table
        $course_check_stmt = $con->prepare("SELECT * FROM course WHERE course_code = ?");
        $course_check_stmt->bind_param('s', $upd_coursecode);
        $course_check_stmt->execute();
        $course_check_result = $course_check_stmt->get_result();

        // Check if the updated class_code is already used by another class
        $class_check_stmt = $con->prepare("SELECT * FROM class WHERE class_code = ? AND class_code != ?");
        $class_check_stmt->bind_param('ss', $upd_classcode, $original_classcode);
        $class_check_stmt->execute();
        $class_check_result = $class_check_stmt->get_result();

        // Validate course_code and ensure no duplicate class_code
        if ($course_check_result->num_rows == 0) {
            $error_message = "The course code \"$upd_coursecode\" does not exist. Please use a valid course code.";
        } elseif ($class_check_result->num_rows > 0) {
            $error_message = "The class code \"$upd_classcode\" already exists. Please use a unique class code.";
        } else {
            // Prepare the SQL statement for updating the record
            $stmt = $con->prepare("UPDATE class SET class_code = ?, course_code = ?, class_type = ? WHERE class_code = ?");
            $stmt->bind_param('ssss', $upd_classcode, $upd_coursecode, $upd_classtype, $original_classcode);

            // Execute the query
            if ($stmt->execute()) {
                header("Location: admin_class_create_form.php");
                exit();
            } else {
                $error_message = "Error executing UPDATE query: " . $stmt->error;
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
