<?php
session_start();
// Define the session timeout duration (15 minutes)
define('SESSION_TIMEOUT', 300); // 900 seconds = 15 minutes
define('WARNING_TIME', 60); // 1 minute before session ends
define('FINAL_WARNING_TIME', 5); // Final warning 5 seconds before logout

// Function to check and handle session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        // Calculate the elapsed time since the last activity
        $inactive_time = time() - $_SESSION['last_activity'];

        // If the elapsed time exceeds the timeout duration, log out the user
        if ($inactive_time > SESSION_TIMEOUT) {
            session_unset(); // Remove all session variables
            session_destroy(); // Destroy the session
            header("Location: testlogin.php?timeout=1"); // Redirect to login page with timeout notice
            exit();
        }
    }

    // Update 'last_activity' timestamp
    $_SESSION['last_activity'] = time();
}

// Call the session timeout check at the beginning
checkSessionTimeout();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
$error_message = "";

if (!$con) {
    $error_message = 'Could not connect: ' . mysqli_connect_errno();
    die($error_message);
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: testlogin.php");
    exit();
}

// Fetch class codes
$class_query = "SELECT class_code FROM class";
$class_result = mysqli_query($con, $class_query);

// Store class codes in an array
$class_codes = [];
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_codes[] = $row['class_code'];
    }
}

// Fetch diploma codes and names
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diploma_result = mysqli_query($con, $diploma_query);

// Fetch student details
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

if (!empty($student_id)) {
    // Prepare and execute the SQL query to fetch student details
    $stmt = $con->prepare("
        SELECT u.full_name, u.phone_number, u.identification_code, s.diploma_code, s.class_code
        FROM user u
        JOIN student s ON u.identification_code = s.identification_code
        WHERE u.identification_code = ?
    ");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch student details and assign to variables
    $index = 0;
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if ($index < 3) {
                $class_codes[$index] = $row['class_code'];
                $student_name = $row['full_name'];
                $phone_number = $row['phone_number'];
                $diploma_code = $row['diploma_code'];
                $identification_code = $row['identification_code'];
            }
            $index++;
        }
    } else {
        $error_message = "Error: Student record not found.";
    }
}

// Validate student ID format (3 digits followed by 1 uppercase letter)
$pattern_student_id = '/^\d{3}[A-Z]$/';
if (!preg_match($pattern_student_id, $student_id)) {
    $error_message = "Error: Invalid student ID format.";
}

// Close the database connection
$con->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Record</title>
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
            <a href="admin_create_stu_recordform.php">Back to Student Records</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Update Student Record</h2>
            <?php if (!empty($error_message)): ?>
                <p style="color: red;"><?php echo $error_message; ?></p>
                <button onclick="window.history.back()">Back</button>
            <?php else: ?>
                <form method="POST" action="admin_update_stu_record.php?student_id=<?php echo htmlspecialchars($student_id); ?>">
                    <div class="form-group">
                        <label class="label" for="student_name">Student Name</label>
                        <input type="text" name="upd_student_name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label" for="phone_number">Phone Number</label>
                        <input type="text" name="upd_phone_number" maxlength="8" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    </div>
                    <div class="form-group">
                    <label class="label" for="diploma_code">Diploma Code</label>
                    <select name="upd_diploma_code" required>
                        <option value="" disabled>Select a Diploma Code</option>
                        <?php
                        if ($diploma_result && mysqli_num_rows($diploma_result) > 0) {
                            while ($row = mysqli_fetch_assoc($diploma_result)) {
                                $selected = ($row['diploma_code'] === $diploma_code) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($row['diploma_code']) . "' $selected>" . htmlspecialchars($row['diploma_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                    </div>

                    <div class="form-group">
                    <label class="label" for="class_code_1">Class Code 1</label>
                    <select name="upd_class_code_1" required>
                        <option value="" disabled>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $code) {
                            $selected = ($code === $class_codes[0]) ? 'selected' : ''; // Pre-select the first class code
                            echo "<option value='" . htmlspecialchars($code) . "' $selected>" . htmlspecialchars($code) . "</option>";
                        }
                        ?>
                    </select>
                    </div>


                    <div class="form-group">
                    <label class="label" for="class_code_2">Class Code 2</label>
                    <select name="upd_class_code_2" required>
                        <option value="" disabled selected>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $code) {
                            $selected = ($code === $class_codes[1]) ? 'selected' : ''; // Pre-select the first class code
                            echo "<option value='" . htmlspecialchars($code) . "' $selected>" . htmlspecialchars($code) . "</option>";
                        }
                        ?>
                    </select>
                    </div>

                <!-- Class Code 3 -->
                    <div class="form-group">
                    <label class="label" for="class_code_3">Class Code 3</label>
                    <select name="upd_class_code_3" required>
                        <option value="" disabled selected>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $code) {
                            $selected = ($code === $class_codes[2]) ? 'selected' : ''; // Pre-select the first class code
                            echo "<option value='" . htmlspecialchars($code) . "' $selected>" . htmlspecialchars($code) . "</option>";
                        }
                        ?>
                    </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit">Update Record</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>
<script>
    // Remaining time in seconds (calculated in PHP)
    const remainingTime = <?php echo $remaining_time; ?>;
    const warningTime = <?php echo WARNING_TIME; ?>; // 1 minute before session ends
    const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning 5 seconds before logout

    // Notify user 1 minute before logout
    if (remainingTime > warningTime) {
        setTimeout(() => {
            alert("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
        }, (remainingTime - warningTime) * 1000); // Convert to milliseconds
    }

    // Final notification 5 seconds before logout
    if (remainingTime > finalWarningTime) {
        setTimeout(() => {
            alert("You will be logged out in 5 seconds due to inactivity.");
        }, (remainingTime - finalWarningTime) * 1000); // Convert to milliseconds
    }

    // Automatically log the user out when the session expires
    setTimeout(() => {
        window.location.href = "testlogin.php?timeout=1";
    }, remainingTime * 1000); // Convert to milliseconds
</script>

</body>
</html>
