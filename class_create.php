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

    // Prepare the SQL statement
    $stmt = $con->prepare("INSERT INTO `class` (`class_code`, `course_code`, `class_type`) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $class_code, $course_code, $class_type);

    // Execute the query
    if ($stmt->execute()) {
        echo "Insert Query executed successfully.";
        header("location:class_record.php");

    } else {
        echo "Error executing INSERT query: " . $stmt->error;
    }

    // Close the statement and database connection
    $stmt->close();
    $con->close();
}
?>
