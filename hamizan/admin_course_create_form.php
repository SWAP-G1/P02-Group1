<?php
session_start(); // Start the session

$con = mysqli_connect("localhost","root","","xyz polytechnic"); // Connect to database
if (!$con){
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Generate CSRF token if not already set
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: testlogin.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Example</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="admin_dashboard">Home</a>
            <a href="logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Course Management</h2>
            <p>Add, update, and organize course records.</p>
        </div>

        <div class="card">
            <h3>Course Details</h3>
            <form method="POST" action="admin_course_create.php">
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="course_code" placeholder="Enter Course code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_name">Course Name</label>
                    <input type="text" name="course_name" placeholder="Enter Course Name" required>
                </div>
                <div class="form-group">
                    <label class="label" for="diploma_code">Diploma Code</label>
                    <input type="text" name="diploma_code" placeholder="Enter Diploma Code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_start_date">Course Start Date</label>
                    <input type="date" name="course_start_date" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_end_date">Course End Date</label>
                    <input type="date" name="course_end_date" required>
                </div>
                <div class="form-group">
                    <label class="label" for="status">Status</label>
                    <select id="role_id" name="role_id" required><br>
                        <option value="To start">To start</option>
                        <option value="In-progress">In-progress</option>
                        <option value="Ended">Ended</option>
                    </select><br>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Course Records</h3>
            <?php
            // Prepare the statement
            $stmt = $con->prepare("SELECT * FROM course");

            // Execute the statement
            $stmt->execute();

            // Obtain the result set
            $result = $stmt->get_result();

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Course Code</th><th>Course Name</th><th>Diploma Code</th><th>Course Start Date</th><th>Course End Date</th><th>Status</th><th colspan="2">Operations</th></tr>';

            // Extract the data row by row
            while ($row = $result->fetch_assoc()) {
                echo '<tr>';
                echo '<td>' . $row['course_code'] . '</td>'; // Corrected column name
                echo '<td>' . $row['course_name'] . '</td>'; // Corrected column name
                echo '<td>' . $row['diploma_code'] . '</td>'; // Corrected column name
                echo '<td>' . $row['course_start_date'] . '</td>'; // Corrected column name
                echo '<td>' . $row['course_end_date'] . '</td>'; // Corrected column name
                echo '<td>' . $row['status'] . '</td>'; // Corrected column name
                echo '<td> <a href="admin_course_update_form.php?course_code=' . $row['course_code'] . '">Edit</a> </td>';
                echo '<td> <a href="admin_course_delete.php?course_code=' . $row['course_code'] . '&csrf_token=' . $_SESSION['csrf_token'] . '">Delete</a> </td>';
                echo '</tr>';
            }

            echo '</table>';

            // Close the database connection
            $con->close();
            ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

</body>
</html>
