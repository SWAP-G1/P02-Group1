<?php
header('Content-Type: application/json');

// Connect to the database
$con = mysqli_connect("localhost", "admin", "admin", "xyz polytechnic");

if (!$con) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// Get the class code from the query string
$class_code = htmlspecialchars($_GET['class_code'] ?? '');

if (!$class_code) {
    echo json_encode(['error' => 'Class code is required.']);
    exit;
}

// Prepare the SQL query to fetch course details
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
$stmt->bind_param('s', $class_code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Class code not found.']);
}

// Close the database connection
$con->close();
?>
