<?php
// Start or resume the session and regenerate the session ID for enhanced security.
session_start();
session_regenerate_id(true);

// Define constants for session timeout management.
define('SESSION_TIMEOUT', 600);       // Total session timeout in seconds (10 minutes)
define('WARNING_TIME', 60);           // Time (in seconds) before timeout to show the first warning (1 minute)
define('FINAL_WARNING_TIME', 3);      // Time (in seconds) before timeout to show the final warning (3 seconds)

/**
 * Function to check and update the session's last activity timestamp.
 * If the user has been inactive, it calculates the inactive time and,
 * if the timeout is exceeded, it simply returns (you might log out the user in a fuller implementation).
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity']; // Calculate inactive duration
        if ($inactive_time > SESSION_TIMEOUT)
            return;
    }
    // Update last activity timestamp to current time
    $_SESSION['last_activity'] = time();
}

// Execute the session timeout check
checkSessionTimeout();

// Calculate the remaining time for the session before it expires.
$remaining_time = isset($_SESSION['last_activity'])
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity'])
    : SESSION_TIMEOUT;

// Establish a connection to the MySQL database using MySQLi.
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con)
    die('Could not connect: ' . mysqli_connect_errno());

// Generate a CSRF token if one is not already set in the session.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Verify that the current user is an admin by checking the session role.
// If not an admin, redirect the user to the login page.
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch all diploma records from the database (no school restriction for admin).
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diploma_result = $con->query($diploma_query);

// Validate that a course_code parameter is provided via the GET method.
// If not provided or empty, redirect back to the course management form.
if (!isset($_GET["course_code"]) || empty($_GET["course_code"])) {
    header("Location: admin_course_create_form.php");
    exit();
}

// Retrieve and sanitize the course code from the GET parameter to prevent XSS.
$edit_coursecode = htmlspecialchars($_GET["course_code"]);

// Get the full name of the admin from the session for display purposes.
$full_name = $_SESSION['session_full_name'] ?? "";

// Prepare a SQL statement to fetch the details of the course to be updated using its course code.
$course_query = "SELECT c.* FROM course c WHERE c.course_code = ?";
$course_stmt = $con->prepare($course_query);
$course_stmt->bind_param('s', $edit_coursecode); // Bind the course code as a string
$course_stmt->execute();
$course_result = $course_stmt->get_result();

// If no course is found with the provided course code, redirect back to the course management page.
if ($course_result->num_rows === 0) {
    header("Location: admin_course_create_form.php");
    exit();
}

// Fetch the course details as an associative array to prefill the update form.
$course_row = $course_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set the character encoding and viewport settings for responsiveness -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course - Admin</title>
    <!-- Link to external CSS stylesheet -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Link to Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation bar with logo and navigation links -->
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="../admin_dashboard">Home</a>
            <a href="../logout.php">Logout</a>
            <!-- Display the admin's full name, safely output using htmlspecialchars() -->
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <!-- Main container for the update form and messages -->
    <div class="container">
        <!-- Card for displaying page title and any error/success messages -->
        <div class="card">
            <h2>Course Management</h2>
            <p>Update course details.</p>
            <?php
            // If an error message is passed via the GET parameter, display it.
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            // Display success messages based on the success code in the GET parameter.
            if (isset($_GET['success'])) {
                if ($_GET['success'] == 2) {
                    echo '<div id="message" class="success-message">Course updated successfully.</div>';
                } else if ($_GET['success'] == 1) {
                    echo '<div id="message" class="success-message">Course created successfully.</div>';
                }
            }
            ?>
        </div>

        <!-- Card containing the update form -->
        <div class="card">
            <h3>Update Course Details</h3>
            <!-- The form submits via POST to admin_course_update.php and uses onsubmit for client-side validation -->
            <form method="POST" action="admin_course_update.php" onsubmit="return validateForm()">
                <!-- Hidden field to pass the original course code (used for identifying the record to update) -->
                <input type="hidden" name="original_coursecode" value="<?php echo htmlspecialchars($course_row['course_code']); ?>">
                <!-- Hidden field for the CSRF token to prevent cross-site request forgery -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <!-- Input field for updating the course code -->
                <div class="form-group">
                    <label class="label">Course Code</label>
                    <input type="text" name="upd_coursecode"
                           value="<?php echo htmlspecialchars($course_row['course_code']); ?>" required>
                </div>

                <!-- Input field for updating the course name -->
                <div class="form-group">
                    <label class="label">Course Name</label>
                    <input type="text" name="upd_coursename"
                           value="<?php echo htmlspecialchars($course_row['course_name']); ?>" required>
                </div>

                <!-- Dropdown selection for the diploma code -->
                <div class="form-group">
                    <label class="label">Diploma</label>
                    <select name="upd_diplomacode" required>
                        <?php 
                        // Reset the diploma result pointer in case it was advanced
                        mysqli_data_seek($diploma_result, 0);
                        // Loop through each diploma and create an option element.
                        while ($diploma_row = $diploma_result->fetch_assoc()):
                            // Determine if the current diploma should be selected.
                            $selected = $diploma_row['diploma_code'] === $course_row['diploma_code'] ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($diploma_row['diploma_code']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($diploma_row['diploma_code'] . ' - ' . $diploma_row['diploma_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Dropdown for selecting the course status -->
                <div class="form-group">
                    <label class="label">Status</label>
                    <select name="upd_status" onchange="toggleDateFields()" required>
                        <option value="No Status" <?= $course_row['status'] === 'No Status' ? 'selected' : '' ?>>No Status</option>
                        <option value="To start" <?= $course_row['status'] === 'To start' ? 'selected' : '' ?>>To Start</option>
                        <option value="In-progress" <?= $course_row['status'] === 'In-progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Ended" <?= $course_row['status'] === 'Ended' ? 'selected' : '' ?>>Ended</option>
                    </select>
                </div>

                <!-- Input field for updating the start date -->
                <div class="form-group">
                    <label class="label">Start Date</label>
                    <input type="date" name="upd_startdate" 
                        value="<?php echo $course_row['course_start_date'] ? htmlspecialchars($course_row['course_start_date']) : ''; ?>">
                </div>

                <!-- Input field for updating the end date -->
                <div class="form-group">
                    <label class="label">End Date</label>
                    <input type="date" name="upd_enddate" 
                        value="<?php echo $course_row['course_end_date'] ? htmlspecialchars($course_row['course_end_date']) : ''; ?>">
                </div>

                <!-- Submit button for the update form -->
                <button type="submit">Update Course</button>
            </form>
        </div>
    </div>

    <!-- Modal for logout warnings due to session inactivity -->
    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>

    <!-- JavaScript Section for client-side form validation and session timeout warnings -->
    <script>
    // Function to validate the update form before submission
    function validateForm() {
        // Retrieve the start and end date values from the form and convert them to Date objects
        const startDate = new Date(document.getElementsByName('upd_startdate')[0].value);
        const endDate = new Date(document.getElementsByName('upd_enddate')[0].value);
        const errorMessageDiv = document.getElementById('message');

        // Check if the start date is after the end date
        if (startDate > endDate) {
            errorMessageDiv.textContent = 'Start date cannot be after the end date!';
            errorMessageDiv.style.display = 'block';
            errorMessageDiv.classList.add('error-message');
            return false; // Prevent form submission
        }

        // Check if the end date is before the start date
        if (endDate < startDate) {
            errorMessageDiv.textContent = 'End date must be after start date';
            errorMessageDiv.style.display = 'block';
            errorMessageDiv.classList.add('error-message');
            return false; // Prevent form submission
        }

        // If validation passes, hide any error messages and allow form submission
        errorMessageDiv.style.display = 'none';
        return true;
    }

    // Function to enable or disable date fields based on the selected status
    function toggleDateFields() {
        const status = document.querySelector('select[name="upd_status"]').value;
        const startDateInput = document.querySelector('input[name="upd_startdate"]');
        const endDateInput = document.querySelector('input[name="upd_enddate"]');

        // If "No Status" is selected, disable and clear the date inputs.
        if (status === 'No Status') {
            startDateInput.disabled = true;
            endDateInput.disabled = true;
            startDateInput.value = '';
            endDateInput.value = '';
        } else {
            // Otherwise, ensure that date inputs are enabled.
            startDateInput.disabled = false;
            endDateInput.disabled = false;
        }
    }

    // Run toggleDateFields when the page loads to set the initial state of the date fields.
    window.onload = function() {
        toggleDateFields();
    };

    // Get session timeout values from PHP and assign them to JavaScript variables.
    const remainingTime = <?php echo $remaining_time; ?>;
    const warningTime = <?php echo WARNING_TIME; ?>;
    const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>;

    // Function to display a logout warning modal with a custom message and optional redirect URL.
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

    // Set a timer to display the first logout warning 1 minute before the session expires.
    if (remainingTime > warningTime) {
        setTimeout(() => {
            showLogoutWarning(
                "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
            );
        }, (remainingTime - warningTime) * 1000);
    }

    // Set a timer to display the final logout warning and redirect 3 seconds before the session expires.
    if (remainingTime > finalWarningTime) {
        setTimeout(() => {
            showLogoutWarning("You will be logged out due to inactivity.", "../logout.php");
        }, (remainingTime - finalWarningTime) * 1000);
    }

    // Auto-hide any message element (error or success) after 10 seconds.
    setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }, 10000);

    // Function to smoothly scroll to the top of the page.
    function scroll_to_top() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
    </script>
</body>
</html>
