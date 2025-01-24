<?php
session_start();
// Define the session timeout duration (10 minutes)
define('SESSION_TIMEOUT', 600); // 600 seconds = 10 minutes
define('WARNING_TIME', 60); // 60 seconds (1 minute before session ends)
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout

// Function to check and handle session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        // Calculate the elapsed time since the last activity
        $inactive_time = time() - $_SESSION['last_activity'];

        // If the elapsed time exceeds the timeout duration, just return
        if ($inactive_time > SESSION_TIMEOUT) {
            return; // Let JavaScript handle logout
        }
    }

    // Update 'last_activity' timestamp for session tracking
    $_SESSION['last_activity'] = time();
}

// Call the session timeout check at the beginning
checkSessionTimeout();

// Calculate remaining session time for the user
$remaining_time = (isset($_SESSION['last_activity'])) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) 
    : SESSION_TIMEOUT;

// Establish a connection to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");

// Check for database connection errors
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role (faculty role: 2)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: testlogin.php");
    exit();
}

// Query to fetch all class codes and their associated course names
$class_query = "
    SELECT c.class_code, co.course_name
    FROM class c
    JOIN course co ON c.course_code = co.course_code
";
$class_result = mysqli_query($con, $class_query);

// Store class codes and course names in an array
$class_codes = [];
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_codes[] = [
            'class_code' => $row['class_code'],
            'course_name' => $row['course_name']
        ];
    }
}

// Query to fetch all diploma codes and names
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diploma_result = mysqli_query($con, $diploma_query);

// Fetch student details based on the given student ID
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

if (!empty($student_id)) {
    $stmt = $con->prepare("
        SELECT u.full_name, u.phone_number, u.identification_code, s.diploma_code, s.class_code, co.course_name
        FROM user u
        JOIN student s ON u.identification_code = s.identification_code
        JOIN class c ON s.class_code = c.class_code
        JOIN course co ON c.course_code = co.course_code
        WHERE u.identification_code = ?
    ");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize variables to store fetched student details
    $existing_classes = [];
    $student_name = $phone_number = $diploma_code = $identification_code = null;

    // Process the fetched data
    while ($row = $result->fetch_assoc()) {
        $student_name = $row['full_name'];
        $phone_number = $row['phone_number'];
        $diploma_code = $row['diploma_code'];
        $identification_code = $row['identification_code'];
        $existing_classes[] = [
            'class_code' => $row['class_code'],
            'course_name' => $row['course_name']
        ];
    }

    // If no classes are found, set an error message
    if (empty($existing_classes)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Error: Student record not found."));
        exit();
    }
    
    // Validate student ID format (3 digits followed by 1 uppercase letter)
    $pattern_student_id = '/^\d{3}[A-Z]$/';
    if (!preg_match($pattern_student_id, $student_id)) {
        header("Location: faculty_create_stu_recordform.php?error=" . urlencode("Error: Invalid student ID format."));
        exit();
    }
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
            <a href="faculty_create_stu_recordform.php">Back to Student Records</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Update Student Record</h2>
            <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<p style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</p>';
            }
            // If ?success=1 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<p style="color: green; font-weight: bold;">Student record updated successfully.</p>';
            }
            ?>
                <form method="POST" action="faculty_update_stu_record.php?student_id=<?php echo htmlspecialchars($student_id); ?>">
                    <div class="form-group">
                        <label class="label" for="student_name">Student Name</label>
                        <input type="text" name="upd_student_name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label" for="phone_number">Phone Number</label>
                        <input type="text" name="upd_phone_number" maxlength="8" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="label" for="diploma_code">Diploma Name</label>
                        <select name="upd_diploma_code" required>
                            <option value="" disabled>Select a Diploma Name</option>
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
                            foreach ($class_codes as $class) {
                                $selected = (!empty($existing_classes[0]) && $class['class_code'] === $existing_classes[0]['class_code']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                     htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                     "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label" for="class_code_2">Class Code 2</label>
                        <select name="upd_class_code_2" required>
                            <option value="" disabled>Select a Class Code</option>
                            <?php
                            foreach ($class_codes as $class) {
                                $selected = (!empty($existing_classes[1]) && $class['class_code'] === $existing_classes[1]['class_code']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                     htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                     "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label" for="class_code_3">Class Code 3</label>
                        <select name="upd_class_code_3" required>
                            <option value="" disabled>Select a Class Code</option>
                            <?php
                            foreach ($class_codes as $class) {
                                $selected = (!empty($existing_classes[2]) && $class['class_code'] === $existing_classes[2]['class_code']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                     htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                     "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit">Update Record</button>
                </form>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>
    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>
    <script>
        // Remaining time in seconds (calculated in PHP)
        const remainingTime = <?php echo $remaining_time; ?>;
        const warningTime = <?php echo WARNING_TIME; ?>; // 1 minute before session ends
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning 3 seconds before logout

        // Function to show the logout warning modal
        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message;
            modal.style.display = "flex";

            modalButton.onclick = function () {
                modal.style.display = "none";
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            };
        }

        // Notify user 1 minute before logout
        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning(
                    "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
                );
            }, (remainingTime - warningTime) * 1000);
        }

        // Final notification 3 seconds before logout
        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", "logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }
        setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
        }, 10000);

        // Automatically log the user out when the session expires
        setTimeout(() => {
            window.location.href = "logout.php";
        }, remainingTime * 1000);

        // Scroll to top functionality
        function scroll_to_top() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>
