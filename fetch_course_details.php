<?php
session_start();
header('Content-Type: application/json');

// Establish database connection
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    // Return a JSON error if the database connection fails
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

// Sanitize and validate input
$class_code = trim($_GET['class_code'] ?? '');
if (empty($class_code)) {
    echo json_encode(['error' => 'Class code is required.']);
    exit;
}

// Prepare the SQL query securely using prepared statements
$stmt = $con->prepare("
    SELECT 
        c.course_code, 
        c.course_name 
    FROM 
        class cl
    JOIN 
        course c ON cl.course_code = c.course_code
    WHERE 
        cl.class_code = ?
");

// Bind the parameter and execute the query
$stmt->bind_param('s', $class_code);
$stmt->execute();
$result = $stmt->get_result();

// Check if a record is found
if ($result->num_rows > 0) {
    // Fetch and return the result as a JSON object
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    // Return an error message if no record matches
    echo json_encode(['error' => 'Class code not found.']);
}

// Clean up: Close the statement and connection
$stmt->close();
$con->close();
?>
