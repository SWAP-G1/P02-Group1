<?php
// Start the session
session_start(); // Initializes a new session or resumes an existing session
session_regenerate_id(true); // Regenerates session ID to prevent session fixation attacks

define('SESSION_TIMEOUT', 600); // Session timeout set to 10 minutes (600 seconds)
define('WARNING_TIME', 60); // Warning issued 1 minute before session timeout
define('FINAL_WARNING_TIME', 3); // Final warning issued 3 seconds before session timeout

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

// Establish connection to MySQL database
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error); // Terminate script if connection fails
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Creates a secure CSRF token
}

// Retrieve user's full name from session
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name']:"";

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.'); // Terminate if CSRF token is invalid
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
        return 'X'; // Invalid score
    }
}

// Check if an ID is passed via GET and retrieve record data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare and execute the select query
    $query = $connect->prepare("SELECT identification_code, course_code, course_score, grade FROM semester_gpa_to_course_code WHERE grade_id = ?");
    $query->bind_param('i', $id); // Bind ID parameter
    $query->execute(); // Execute the query
    $query->bind_result($identification_code, $course_code, $course_score, $grade); // Bind result variables
    $query->fetch(); // Fetch the result
}

// Check if the form is submitted for an update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_button'])) {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    check_csrf_token($csrf_token);

    $id = $_POST['id'];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score_input = $_POST["course_score"];

    // Strict validation for course_score
    if (!is_numeric($course_score_input)) {
        header("Location: admin_score.php?error=" . urlencode("Invalid input. Course score must be a number."));
        exit(); // Stop script execution
    }

    $course_score = (float)$course_score_input; // Cast to float after validation
    $grade = assign_grade($course_score); // Assign grade based on score

    // Continue with existing logic
    if ($grade == 'X') {
        header("Location: admin_score.php?error=" . urlencode("Invalid score (0-4 only)"));
        exit();
    }

    // Prepare and execute the update query
    $update_query = $connect->prepare("UPDATE semester_gpa_to_course_code SET course_score = ?, grade = ? WHERE grade_id = ?");
    $update_query->bind_param('dsi', $course_score, $grade, $id); // Bind parameters for the update
    if ($update_query->execute()) {
        // Regenerate CSRF token after form submission
        unset($_SESSION['csrf_token']);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
        header("Location: admin_score.php?success=2");
        exit();
    } else {
        header("Location: admin_score.php?error=" . urlencode("Failed to update record."));
        exit();
    }
    $update_query->close(); // Close the statement
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Defines character encoding for the document -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Ensures proper rendering on mobile devices -->
    <title>Edit Record</title> <!-- Sets the title of the web page -->
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to external CSS file for styling -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet"> <!-- Import Google Fonts -->
</head>
<body>

    <div class="navbar"> <!-- Navigation bar section -->
        <div class="navbar-brand"> <!-- Branding section -->
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> <!-- School logo image -->
            <h1>XYZ Polytechnic Management</h1> <!-- Website heading -->
        </div>
        <nav> <!-- Navigation links -->
            <a href=" ../admin_dashboard.php">Home</a> <!-- Link to dashboard -->
            <a href=" ../logout.php">Logout</a> <!-- Link to logout -->
            <a><?php echo htmlspecialchars($full_name); ?></a> <!-- Displays logged-in user's name securely -->
        </nav>
    </div>

    <div class="container"> <!-- Main content container -->
        <div class="card"> <!-- Card for section layout -->
            <h2>Edit Score Record</h2> <!-- Section heading -->
            <p>Changes student's Course Score and Grade.</p> <!-- Brief description -->
            <?php
            // Display error message if present in URL
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }

            // Display success message if record update was successful
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Class updated successfully.</div>';
            }
            ?>
        </div>

        <div class="card"> <!-- Card containing the form -->
            <h3>Student Score Details</h3> <!-- Form heading -->
            <form method="post" action="admin_edit.php"> <!-- Form submission to admin_edit.php -->
                <div class="form-group"> <!-- Form group for identification code -->
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <input type="text" name="identification_code" value="<?php echo htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Read-only field -->
                </div>
                <div class="form-group"> <!-- Form group for course code -->
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Read-only field -->
                </div>
                <div class="form-group"> <!-- Form group for course score -->
                    <label class="label" for="course_score">Course Score</label>
                    <input type="text" name="course_score" value="<?php echo htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8'); ?>" /> <!-- Editable field -->
                </div>
                <div class="form-group"> <!-- Form group for grade -->
                    <label class="label" for="grade">Grade</label>
                    <input type="text" name="grade" value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" readonly /> <!-- Read-only field -->
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- CSRF token for security -->
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" /> <!-- Hidden ID field -->
                <button type="submit" name="update_button" value="Update Button">Update Score</button> <!-- Submit button -->
            </form>
        </div>
    </div>
<footer class="footer"> <!-- Footer section -->
    <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p> <!-- Copyright information -->
</footer>

<div id="logoutWarningModal" class="modal" style="display: none;"> <!-- Modal for logout warning -->
    <div class="modal-content">
        <p id="logoutWarningMessage"></p> <!-- Placeholder for dynamic message -->
        <button id="logoutWarningButton">OK</button> <!-- Button to close modal -->
    </div>
</div>

    <script>
        // Set session timeout warnings and auto-logout
        const remainingTime = <?php echo $remaining_time; ?>; // Session time remaining
        const warningTime = <?php echo WARNING_TIME; ?>; // Time before showing the first warning
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Time before showing the final warning

        // Function to display logout warnings
        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message; // Set warning message
            modal.style.display = "flex"; // Show modal

            modalButton.onclick = function () { // Button closes the modal
                modal.style.display = "none";
                if (redirectUrl) {
                    window.location.href = redirectUrl; // Redirect if URL provided
                }
            };
        }

        // Show first warning 1 minute before session expires
        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
            }, (remainingTime - warningTime) * 1000);
        }

        // Show final warning 3 seconds before session expires
        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }

        // Hide message after 10 seconds if present
        setTimeout(function() {
            const messageElement = document.getElementById('message');
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, 10000);

        // Auto logout after session expires
        setTimeout(() => {
            window.location.href = " ../logout.php"; // Redirect to logout page
        }, remainingTime * 1000);

        // Scroll-to-top button functionality
        function scroll_to_top() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Smooth scrolling effect
            });
        }
    </script>

</body>
</html>
