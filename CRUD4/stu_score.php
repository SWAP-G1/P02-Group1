<?php
// Start the session
session_start();

// Define session timeout settings
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

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) {
    die("Connection failed: " . mysqli_connect_error()); // Handle database connection error
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate secure CSRF token
}

// Get full name from session if available
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Check if the student is logged in (role_id = 3)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 3) {
    header("Location: ../login.php"); // Redirect to login page if user is not a student
    exit();
}

// Get the logged-in student's identification code from session
$identification_code = $_SESSION['session_identification_code'] ?? '';

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        header("Location: stu_score.php?error=" . urlencode("Invalid CSRF token!")); // Redirect with error if CSRF token is invalid
        exit();
    }
}

// Fetch GPA and grades for the logged-in student
$query_gpa = $connect->prepare("SELECT semester_gpa FROM student_score WHERE identification_code = ?");
$query_gpa->bind_param('s', $identification_code); // Bind the student's ID
$query_gpa->execute(); // Execute the query
$query_gpa->bind_result($gpa); // Bind the result to $gpa
$query_gpa->fetch(); // Fetch the GPA result
$query_gpa->close(); // Close the prepared statement

// Fetch student's course grades
$query_grades = $connect->prepare("SELECT course_code, course_score, grade FROM semester_gpa_to_course_code WHERE identification_code = ?");
$query_grades->bind_param('s', $identification_code); // Bind the student's ID
$query_grades->execute(); // Execute the query
$result_grades = $query_grades->get_result(); // Get the result set
$query_grades->close(); // Close the prepared statement
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Set character encoding to UTF-8 -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Ensure proper scaling on different devices -->
    <title>Student Scores</title> <!-- Page title -->
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to external stylesheet for page styling -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet"> <!-- Import Google Fonts for better typography -->
</head>
<body>
    <!-- Navigation bar section -->
    <div class="navbar">
        <div class="navbar-brand"> <!-- Brand/logo section -->
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> <!-- Display logo image -->
            <h1>XYZ Polytechnic Management</h1> <!-- Display website heading -->
        </div>
        <nav> <!-- Navigation links -->
            <a href=" ../stu_dashboard.php">Home</a> <!-- Link to student dashboard -->
            <a href=" ../logout.php">Logout</a> <!-- Logout link -->
            <a><?php echo htmlspecialchars($full_name); ?></a> <!-- Display student's full name securely -->
        </nav>
    </div>
<!-- Main container for GPA and grades -->
    <div class="container">
        <div class="card">
            <h2>Student GPA and Grades</h2>
            <p><strong>GPA: </strong><?php echo htmlspecialchars($gpa, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="message">Student grade created successfully.</div>';
            }            

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="message">Student grade updated successfully.</div>';
            }

            // If ?success=3 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" class="message">Student grade deleted successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>

            <h3>Course Grades</h3>
            <table border="1" bgcolor="white" align="center"> <!-- Table to display course grades -->
                <tr>
                    <th>Course Code</th> <!-- Header for course code -->
                    <th>Course Score</th> <!-- Header for course score -->
                    <th>Grade</th> <!-- Header for grade -->
                </tr>

                <?php while ($row = $result_grades->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['course_code'], ENT_QUOTES, 'UTF-8'); ?></td> <!-- Securely display course code -->
                        <td><?php echo htmlspecialchars($row['course_score'], ENT_QUOTES, 'UTF-8'); ?></td> <!-- Securely display course score -->
                        <td><?php echo htmlspecialchars($row['grade'], ENT_QUOTES, 'UTF-8'); ?></td> <!-- Securely display grade -->
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- Hidden CSRF token field for security -->
    </div>

    <footer class="footer"> <!-- Footer section -->
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p> <!-- Footer copyright information -->
    </footer>

    <!-- Modal for logout warnings -->
    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p> <!-- Placeholder for logout warning message -->
            <button id="logoutWarningButton">OK</button> <!-- Button to dismiss the warning modal -->
        </div>
    </div>

    <script>
        // JavaScript to handle session timeout warnings and auto-logout
        const remainingTime = <?php echo $remaining_time; ?>; // Time remaining for the session
        const warningTime = <?php echo WARNING_TIME; ?>; // Time to trigger the first warning
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Time to trigger the final warning

        // Function to show the logout warning modal
        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message; // Display the warning message
            modal.style.display = "flex"; // Show the modal

            modalButton.onclick = function () { // Handle modal button click
                modal.style.display = "none"; // Hide modal
                if (redirectUrl) {
                    window.location.href = redirectUrl; // Redirect if URL is provided
                }
            };
        }

        // Notify user 1 minute before logout due to inactivity
        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
            }, (remainingTime - warningTime) * 1000);
        }

        // Final notification 3 seconds before automatic logout
        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }

        // Automatically hide messages after 10 seconds
        setTimeout(function() {
            const messageElement = document.getElementById('message');
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, 10000);

        // Automatically log the user out when the session expires
        setTimeout(() => {
            window.location.href = " ../logout.php"; // Redirect to logout page
        }, remainingTime * 1000);

        // Smooth scroll to the top of the page
        function scroll_to_top() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>

