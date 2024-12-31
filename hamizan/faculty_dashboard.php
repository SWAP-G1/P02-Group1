<?php
session_start(); // Start the session

$con = mysqli_connect("localhost","root","","xyz polytechnic"); //connect to database
if (!$con){
	die('Could not connect: ' . mysqli_connect_errno()); //return error is connect fail
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    // Redirect to login page if the user is not logged in or not a faculty
    header("Location: testlogin.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
$identification_code = isset($_SESSION['session_identification_code']) ? $_SESSION['session_identification_code'] : "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css"> <!-- Link to the CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
    <title>Faculty Dashboard</title>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>Polytechnic Management</h1>
        </div>
        <div class="logout-button">
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <!-- Welcome Message -->
    <div class="welcome-message" style="text-align: center; margin: 20px;">
        <h2>Welcome <?php echo htmlspecialchars($full_name); ?>, <?php echo htmlspecialchars($identification_code); ?></h2>
        <p>Here is your faculty dashboard.</p>
    </div>

    <!-- Faculty Dashboard Content -->
    <div class="card-grid-container">
    <!-- User Management Widget -->
    <a href="user_management.php" class="widget-card">
        <h2>Student Management</h2>
        <p>Manage students and their details here.</p>
    </a>

    <!-- Course Management Widget -->
    <a href="course_management.php" class="widget-card">
        <h2>Course Management</h2>
        <p>Manage courses and their details here.</p>
    </a>

    <!-- Class Management Widget -->
    <a href="faculty_class_create_form.php" class="widget-card">
        <h2>Class Management</h2>
        <p>Manage class schedules and related info here.</p>
    </a>

    <!-- Grades Management Widget -->
    <a href="grades_management.php" class="widget-card">
        <h2>Grades Management</h2>
        <p>Manage and view student grades here.</p>
    </a>
</div>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Management. All Rights Reserved.</p>
    </footer>
</body>
</html>
