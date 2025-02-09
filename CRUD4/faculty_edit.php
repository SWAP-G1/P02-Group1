<?php
// Start the session
session_start();
session_regenerate_id(true);

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

$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get the user's full name from the session
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name']:"";

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
}

// Function to assign grades based on course score
function assign_grade($course_score) {
    // Check if course_score is numeric
    if (!is_numeric($course_score)) {
        return 'X'; // Return 'X' if the score is not a number
    }
 // Assign grades based on score ranges
    if ($course_score == 4.0) {
        return 'A';
    } elseif ($course_score >= 3.5 && $course_score < 4.0) {
        return 'B+';
    } elseif ($course_score >= 3.0 && $course_score < 3.5) {
        return 'B';
    } elseif ($course_score >= 2.5 && $course_score < 3.0) {
        return 'C+';
    } elseif ($course_score >= 2.0 && $course_score < 2.5) {
        return 'C';
    } elseif ($course_score >= 1.5 && $course_score < 2.0) {
        return 'D+';
    } elseif ($course_score >= 1.0 && $course_score < 1.5) {
        return 'D';
    } elseif ($course_score >= 0.0 && $course_score < 1.0) {
        return 'F';
    } else {
        return 'X';// Return 'X' for invalid scores
    }
}



// Check if an ID is passed via GET and retrieve record data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare and execute the select query
    $query = $connect->prepare("SELECT identification_code, course_code, course_score, grade FROM semester_gpa_to_course_code WHERE grade_id = ?");
    $query->bind_param('i', $id);
    $query->execute();
    $query->bind_result($identification_code, $course_code, $course_score, $grade);
    $query->fetch();

}

// Check if the form is submitted for an update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_button'])) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    check_csrf_token($csrf_token);
  // Retrieve form data
    $id = $_POST['id'];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score_input = $_POST["course_score"];

        // Strict validation for course_score
    if (!is_numeric($course_score_input)) {
        header("Location: faculty_score.php?error=" . urlencode("Invalid input. Course score must be a number."));
        exit();
    }

    $course_score = (float)$course_score_input; // Cast to float after validation
    $grade = assign_grade($course_score);

        // Continue with existing logic
    if ($grade == 'X') {
        header("Location: faculty_score.php?error=" . urlencode("Invalid score (0-4 only)"));
        exit();
    }

    // Prepare and execute the update query
    $update_query = $connect->prepare("UPDATE semester_gpa_to_course_code SET course_score = ?, grade = ? WHERE grade_id = ?");
    $update_query->bind_param('dsi', $course_score, $grade, $id);
    if ($update_query->execute()) {
        // Regenerate CSRF token after form submission
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: faculty_score.php?success=2");
        exit();
    } else {
        header("Location: faculty_score.php?error=" . urlencode("Failed to update record."));
        exit();
    }
    $update_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Specifies the character encoding for the document -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Ensures proper rendering and touch zooming on mobile devices -->
    <title>Edit Record</title> <!-- Title of the web page displayed on the browser tab -->
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet"> <!-- Link to Google Fonts -->
</head>
<body>

    <div class="navbar"> <!-- Navigation bar container -->
        <div class="navbar-brand"> <!-- Branding section in the navbar -->
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> <!-- School logo image -->
            <h1>XYZ Polytechnic Management</h1> <!-- Application title -->
        </div>
        <nav> <!-- Navigation links -->
            <a href=" ../faculty_dashboard.php">Home</a> <!-- Link to faculty dashboard -->
            <a href=" ../logout.php">Logout</a> <!-- Logout link -->
            <a><?php echo htmlspecialchars($full_name); ?></a> <!-- Displays the full name of the logged-in user -->
        </nav>
    </div>

    <div class="container"> <!-- Main container for the content -->
        <div class="card"> <!-- Card layout for editing score record -->
            <h2>Edit Score Record</h2> <!-- Section title -->
            <p>Changes student's Course Score and Grade.</p> <!-- Description of the section -->
            <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>'; // Display error message if present
            }

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Class updated successfully.</div>'; // Display success message
            }
            ?>
        </div>

        <div class="card"> <!-- Card for student score details -->
            <h3>Student Score Details</h3> <!-- Section title -->
            <form method="post" action="faculty_edit.php"> <!-- Form for updating student scores -->
                <div class="form-group"> <!-- Group for student ID -->
                    <label class="label" for="identification_code">Student Identification Code</label> <!-- Label for ID field -->
                    <input type="text" name="identification_code" value="<?php echo htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Student ID input (read-only) -->
                </div>
                <div class="form-group"> <!-- Group for course code -->
                    <label class="label" for="course_code">Course Code</label> <!-- Label for course code field -->
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Course code input (read-only) -->
                </div>
                <div class="form-group"> <!-- Group for course score -->
                    <label class="label" for="course_score">Course Score</label> <!-- Label for course score field -->
                    <input type="text" name="course_score" value="<?php echo htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8'); ?>" /> <!-- Course score input (editable) -->
                </div>
                <div class="form-group"> <!-- Group for grade -->
                    <label class="label" for="grade">Grade</label> <!-- Label for grade field -->
                    <input type="text" name="grade" value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Grade input (read-only) -->
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- Hidden CSRF token for security -->
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" /> <!-- Hidden student record ID -->
                <button type="submit" name="update_button" value="Update Button">Update Score</button> <!-- Submit button -->
            </form>
        </div>
    </div>

    <footer class="footer"> <!-- Footer section -->
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p> <!-- Copyright notice -->
    </footer>

    <div id="logoutWarningModal" class="modal" style="display: none;"> <!-- Modal for logout warning -->
        <div class="modal-content"> <!-- Modal content wrapper -->
            <p id="logoutWarningMessage"></p> <!-- Placeholder for logout warning message -->
            <button id="logoutWarningButton">OK</button> <!-- Button to close the modal -->
        </div>
    </div>

    <script>
        // Remaining time in seconds (calculated in PHP)
        const remainingTime = <?php echo $remaining_time; ?>; // Remaining session time
        const warningTime = <?php echo WARNING_TIME; ?>; // 1 minute before session ends
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning 3 seconds before logout

        // Function to show the logout warning modal
        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal"); // Get the modal element
            const modalMessage = document.getElementById("logoutWarningMessage"); // Get the message element
            const modalButton = document.getElementById("logoutWarningButton"); // Get the button element

            modalMessage.innerText = message; // Set the warning message
            modal.style.display = "flex"; // Display the modal

            modalButton.onclick = function () { // Define button click behavior
                modal.style.display = "none"; // Hide the modal
                if (redirectUrl) { // If a redirect URL is provided
                    window.location.href = redirectUrl; // Redirect the user
                }
            };
        }

        // Notify user 1 minute before logout
        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning(
                    "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
                );
            }, (remainingTime - warningTime) * 1000); // Trigger warning based on remaining time
        }

        // Final notification 3 seconds before logout
        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php"); // Final logout warning
            }, (remainingTime - finalWarningTime) * 1000);
        }

        // Hide the message element after 10 seconds
        setTimeout(function() {
            const messageElement = document.getElementById('message'); // Get the message element
            if (messageElement) {
                messageElement.style.display = 'none'; // Hide the message element
            }
        }, 10000);

        // Automatically log the user out when the session expires
        setTimeout(() => {
            window.location.href = " ../logout.php"; // Redirect to logout page when session expires
        }, remainingTime * 1000);

        // Scroll to top functionality
        function scroll_to_top() {
            window.scrollTo({
                top: 0, // Scroll to the top of the page
                behavior: 'smooth' // Smooth scrolling effect
            });
        }
    </script>

</body>
</html>
