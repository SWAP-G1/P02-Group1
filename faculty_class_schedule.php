<?php
session_start(); // Start the session

// Connect to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Database connection
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); 
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: testlogin.php");
    exit();
}

// Retrieve the session full name
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch available class codes for the dropdown.
$class_codes = []; // This will store the unique class codes.
$class_query = "SELECT DISTINCT class_code FROM schedule"; // Query to get all unique class codes.
$class_result = mysqli_query($con, $class_query); 
if ($class_result) {
    while ($row = mysqli_fetch_assoc($class_result)) { 
        $class_codes[] = $row['class_code']; 
    }
}

// Initialize the schedule records.
$schedule_records = []; // This will hold the records we need to display.

// Process the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $class_code = isset($_POST['class_code']) ? htmlspecialchars($_POST['class_code']) : ""; // Get the class code from the form input.

    $stmt = !empty($class_code)
        ? $con->prepare("SELECT * FROM schedule WHERE class_code = ?") // Query to get specific class code records.
        : $con->prepare("SELECT * FROM schedule"); // Query to get all records if no class code is selected.

    if (!empty($class_code)) {
        $stmt->bind_param("s", $class_code); 
    }

    $stmt->execute(); 
    $result = $stmt->get_result(); 

    while ($row = $result->fetch_assoc()) { // Fetch each row as an array.
        $schedule_records[] = $row; // Add each record to the schedule records array.
    }

    $stmt->close(); 
}

// Close the database connection.
$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Schedule</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to the external CSS file for styling -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> 
            <h1>XYZ Polytechnic Management</h1> 
        </div>
        <nav>
            <a href="admin_dashboard.php">Home</a>
            <a href="logout.php">Logout</a> 
            <a><?php echo htmlspecialchars($full_name); ?></a> 
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Schedule Management</h2>
            <p>Search for a class schedule by selecting a class code below:</p>

            <form method="POST" action="" class="form-group"> 
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> 
                <label class="label" for="class_code">Select Class Code:</label>
                <select name="class_code" id="class_code"> <!-- Dropdown menu for class codes -->
                    <option value="">All Classes</option>
                    <?php foreach ($class_codes as $code): ?>
                        <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($code); ?></option> <!-- Populate dropdown with class codes -->
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button">Search</button> 
            </form>
        </div>

        <div class="card">
            <h3>Schedule Records</h3>
            <table border="1" bgcolor="white" align="center"> 
                <thead>
                    <tr>
                        <th>Class Code</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Classroom Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedule_records)): ?> <!-- Check if there are no records -->
                        <tr>
                            <td colspan="4" style="text-align: center;">No records found</td> 
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedule_records as $row): ?> <!-- Loop through the schedule records -->
                            <tr>
                                <td><?php echo htmlspecialchars($row['class_code']); ?></td> 
                                <td><?php echo htmlspecialchars($row['start_time']); ?></td> 
                                <td><?php echo htmlspecialchars($row['end_time']); ?></td> 
                                <td><?php echo htmlspecialchars($row['classroom_location']); ?></td> 
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p> 
    </footer>
</body>
</html>
