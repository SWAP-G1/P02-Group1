<html>
<body>  

<?php
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to the database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Prepare the statement
$stmt = $con->prepare("SELECT * FROM class");

// Execute the statement
$stmt->execute();

// Obtain the result set
$result = $stmt->get_result();

echo '<table border="1" bgcolor="white" align="center">';
echo '<tr><th>Class Code</th><th>Course Code</th><th>Class Type</th><th colspan="2">CRUD</th></tr>';

// Extract the data row by row
while ($row = $result->fetch_assoc()) {
    echo '<tr>';
    echo '<td>' . $row['class_code'] . '</td>'; // Corrected column name
    echo '<td>' . $row['course_code'] . '</td>'; // Corrected column name
    echo '<td>' . $row['class_type'] . '</td>'; // Corrected column name
    echo '<td> <a href="class_update_form.php?class_code=' . $row['class_code'] . '">Edit</a> </td>';
    echo '<td> <a href="class_delete.php?class_code=' . $row['class_code'] . '">Delete</a> </td>';
    echo '</tr>';
}

echo '</table>';

// Close the database connection
$con->close();
?>
</body>
</html>
