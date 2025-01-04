<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database

// Initialize error message
$error_message = "";

// Check database connection
if (!$con) {
    die('Could not connect to the database: ' . mysqli_connect_error());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role (role id = 3 for students)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 3) {
    // Redirect to login page if the user is not a student
    header("Location: testlogin.php");
    exit();
}

// Fetch student's full name from the session
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch student record using session-stored student identification code
$student_id_code = isset($_SESSION['session_id_code']) ? $_SESSION['session_id_code'] : "";

$stmt = $con->prepare("
    SELECT 
        u.full_name, 
        u.phone_number, 
        s.class_code, 
        s.diploma_code 
    FROM 
        user u 
    JOIN 
        student s ON u.identification_code = s.identification_code 
    WHERE 
        u.identification_code = ?
");

$stmt->bind_param('s', $student_id_code);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View My Profile</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="stu_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>My Profile</h2>
            <?php if ($student_data): ?>
                <table border="1" bgcolor="white" align="center">
                    <tr>
                        <th>Full Name</th>
                        <th>Phone Number</th>
                        <th>Diploma Code</th>
                        <th>Class Code</th>
                    </tr>
                    <tr>
                        <td><?php echo htmlspecialchars($student_data['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student_data['phone_number']); ?></td>
                        <td><?php echo htmlspecialchars($student_data['diploma_code']); ?></td>
                        <td><?php echo htmlspecialchars($student_data['class_code']); ?></td>
                    </tr>
                </table>
            <?php else: ?>
                <p>No records found for your profile.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
// Close database connection
$con->close();
?>
