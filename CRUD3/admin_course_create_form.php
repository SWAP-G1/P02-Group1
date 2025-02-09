<?php
// Start a new session or resume an existing session
session_start();

// Regenerate the session ID to help prevent session fixation attacks
session_regenerate_id(true);

// Define constants for session timeout management
define('SESSION_TIMEOUT', 600);       // Total session timeout duration in seconds (10 minutes)
define('WARNING_TIME', 60);           // Time in seconds before timeout to warn the user (1 minute)
define('FINAL_WARNING_TIME', 3);      // Time in seconds before timeout for final warning (3 seconds)

/**
 * Function to check the session's last activity timestamp.
 * If the session has been inactive longer than SESSION_TIMEOUT, it does not update the timestamp.
 * Otherwise, it updates the last activity time to the current time.
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        // Calculate the time (in seconds) elapsed since the last recorded activity
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) {
            // If inactive time exceeds timeout, simply return (in a more advanced version, you might logout the user)
            return;
        }
    }
    // Update the last activity timestamp to the current time
    $_SESSION['last_activity'] = time();
}

// Check and update session timeout
checkSessionTimeout();

// Calculate how much time remains before the session times out
$remaining_time = (isset($_SESSION['last_activity']))
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity'])
    : SESSION_TIMEOUT;

// Establish a connection to the MySQL database using MySQLi
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    // If the connection fails, output an error message and stop execution
    die('Could not connect: ' . mysqli_connect_errno());
}

// Check that the user is logged in as an admin (session_role should equal 1 for admin users)
// If the session does not have the correct role, redirect the user to the login page
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Retrieve the full name of the logged-in admin from the session for display purposes
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch all diplomas from the database (used later to populate a dropdown in the course creation form)
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diplomas = $con->query($diploma_query);

// Generate a CSRF (Cross-Site Request Forgery) token if one is not already set in the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Set the character encoding and viewport for responsive design -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Course Management</title>
    <!-- Link to external CSS stylesheet -->
    <link rel="stylesheet" href="../styles.css">
    <!-- Link to external Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation bar at the top of the page -->
    <div class="navbar">
        <div class="navbar-brand">
            <!-- School logo image -->
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <!-- Navigation links -->
        <nav>
            <a href="../admin_dashboard.php">Home</a>
            <a href="../logout.php">Logout</a>
            <!-- Display the admin's full name securely -->
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <!-- Main container for the page content -->
    <div class="container">
        <!-- Card for displaying course management messages -->
        <div class="card">
            <h2>Course Management</h2>
            <p>Create and manage courses across all schools.</p>
            <?php
                // Check for error messages passed via GET parameters and display them
                if (isset($_GET['error'])) {
                    echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
                }
                // Check for success messages passed via GET parameters and display the appropriate message
                if (isset($_GET['success'])) {
                    if ($_GET['success'] == 1) {
                        echo '<div id="message" class="success-message">Course created successfully.</div>';
                    } else if ($_GET['success'] == 2) {
                        echo '<div id="message" class="success-message">Course updated successfully.</div>';
                    } else if ($_GET['success'] == 3) {
                        echo '<div id="message" class="success-message">Course deleted successfully.</div>';
                    }
                }
            ?>
        </div>

        <!-- Card for course creation form -->
        <div class="card">
            <h3>Course Details</h3>
            <!-- Form to create a new course. It calls validateDates() on submission to ensure date inputs are valid -->
            <form method="POST" action="admin_course_create.php" onsubmit="return validateDates()">
                <!-- Course Code input -->
                <div class="form-group">
                    <label>Course Code</label>
                    <input type="text" name="course_code">
                </div>
                <!-- Course Name input -->
                <div class="form-group">
                    <label>Course Name</label>
                    <input type="text" name="course_name">
                </div>
                <!-- Diploma Code dropdown: populated from the diploma query -->
                <div class="form-group">
                    <label>Diploma Code</label>
                    <select name="diploma_code" required>
                        <?php while($row = $diplomas->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['diploma_code']) ?>">
                                <?= htmlspecialchars($row['diploma_code'] . ' - ' . $row['diploma_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <!-- Course Status dropdown -->
                <div class="form-group">
                    <label>Status</label>
                    <!-- When status is changed, it calls toggleDateFields() to show/hide date fields (function definition not shown in this snippet) -->
                    <select name="status" onchange="toggleDateFields()" required>
                        <option value="No Status" selected>No Status</option>
                        <option value="To start">To Start</option>
                        <option value="In-progress">In Progress</option>
                        <option value="Ended">Ended</option>
                    </select>
                </div>
                <!-- Course Start Date input -->
                <div class="form-group">
                    <label class="label">Start Date</label>
                    <!-- If editing, prefill value from $course_row (if set). Otherwise, leave blank. -->
                    <input type="date" name="course_start_date" id="course_start_date" 
                        value="<?php echo isset($course_row['course_start_date']) ? htmlspecialchars($course_row['course_start_date']) : ''; ?>">
                </div>
                <!-- Course End Date input -->
                <div class="form-group">
                    <label class="label">End Date</label>
                    <!-- If editing, prefill value from $course_row (if set). Otherwise, leave blank. -->
                    <input type="date" name="course_end_date" id="course_end_date" 
                        value="<?php echo isset($course_row['course_end_date']) ? htmlspecialchars($course_row['course_end_date']) : ''; ?>">
                </div>
                <!-- Hidden field to include the CSRF token for security -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <!-- Submit button for creating the course -->
                <button type="submit">Create Course</button>
            </form>
        </div>

        <!-- Card for displaying the list of existing courses -->
        <div class="card">
            <h3>Course Records</h3>
            <!-- Button to scroll back to the top of the page -->
            <button id="scrollToTop" class="button" onclick="scroll_to_top()">
                <img src="../scroll_up.png" alt="Scroll to top">
            </button>
            <?php
            // Query to fetch all courses, including related diploma and school details.
            $course_query = "SELECT c.*, d.diploma_name, s.school_name 
                           FROM course c 
                           JOIN diploma d ON c.diploma_code = d.diploma_code
                           JOIN school s ON d.school_code = s.school_code 
                           ORDER BY c.course_code";
            $course_result = $con->query($course_query);

            // Check if there are any courses returned by the query
            if ($course_result->num_rows > 0) {
                // Start the table to display course records
                echo '<table border="1" bgcolor="white" align="center">';
                echo '<tr>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Diploma</th>
                        <th>School</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th colspan="2">Operations</th>
                    </tr>';

                // Loop through each course record and output a table row
                while ($course_row = $course_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($course_row['course_code']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['course_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['diploma_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($course_row['school_name']) . '</td>';
                    // Format start date if available; otherwise, show 'No Start Date'
                    echo '<td>' . ($course_row['course_start_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_start_date']))) : 'No Start Date') . '</td>';
                    // Format end date if available; otherwise, show 'No End Date'
                    echo '<td>' . ($course_row['course_end_date'] ? htmlspecialchars(date('Y-m-d', strtotime($course_row['course_end_date']))) : 'No End Date') . '</td>';
                    // Display course status; if not set, show 'No Status'
                    echo '<td>' . htmlspecialchars(($course_row['status'] ? $course_row['status'] : 'No Status')) . '</td>';
                    // Provide a link to the course edit form, passing the course code via GET
                    echo '<td> <a href="admin_course_update_form.php?course_code=' . htmlspecialchars($course_row['course_code']) . '">Edit</a> </td>';
                    // Provide a link for deletion which triggers a JavaScript confirmation modal
                    echo '<td> <a href="#" onclick="confirmDelete(\'' . htmlspecialchars($course_row['course_code']) . '\')">Delete</a> </td>';
                    echo '</tr>';
                }
                // End of table
                echo '</table>';
            } else {
                // If no courses were found, display a message
                echo '<p class="text-center">No courses found.</p>';
            }
            ?>
        </div>
    </div>
</body>

<!-- Footer section of the page -->
<footer class="footer">
    <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
</footer>

<!-- Modal for confirming deletion of a course -->
<div id="confirmationModal" class="modal" style="display: none;">
    <div class="modal-content">
        <!-- Message will be set by JavaScript -->
        <p id="confirmationMessage"></p>
        <!-- Confirmation button which will trigger deletion -->
        <button id="confirmationButton">Yes</button>
        <!-- Cancel button to close the modal -->
        <button onclick="hideModal()">Cancel</button>
    </div>
</div>

<!-- JavaScript Section -->
<script>
    /**
     * Function to validate the start and end dates before form submission.
     * It ensures that the end date is after the start date.
     */
    function validateDates() {
        // Retrieve the values from the date inputs using their IDs
        const startDate = document.getElementById('course_start_date').value;
        const endDate = document.getElementById('course_end_date').value;
        
        // Convert the date strings into Date objects for comparison
        if (new Date(endDate) <= new Date(startDate)) {
            // If the end date is not after the start date, alert the user and cancel submission
            alert('End date must be after start date');
            return false;
        }
        return true;
    }

    /**
     * Function to show a confirmation modal when a user attempts to delete a course.
     * @param {string} courseCode - The unique code of the course to be deleted.
     */
    function confirmDelete(courseCode) {
        // Get modal elements by their IDs
        const modal = document.getElementById("confirmationModal");
        const modalMessage = document.getElementById("confirmationMessage");
        const modalButton = document.getElementById("confirmationButton");

        // Set the confirmation message text
        modalMessage.innerText = "Are you sure you want to delete this course?";
        // Display the modal by setting its display style to flex
        modal.style.display = "flex";

        // When the user clicks "Yes", redirect to the deletion script with the course code and CSRF token in the URL
        modalButton.onclick = function () {
            window.location.href = 'admin_course_delete.php?course_code=' + encodeURIComponent(courseCode) + '&csrf_token=<?= htmlspecialchars($_SESSION["csrf_token"]) ?>';
        };
    }

    /**
     * Function to hide the deletion confirmation modal.
     */
    function hideModal() {
        document.getElementById("confirmationModal").style.display = "none";
    }

    // Pass PHP variables to JavaScript for session timeout warnings
    const remainingTime = <?php echo $remaining_time; ?>;
    const warningTime = <?php echo WARNING_TIME; ?>;
    const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>;

    /**
     * Function to display a logout warning message.
     * If a redirect URL is provided, it will redirect the user after acknowledgment.
     * @param {string} message - The warning message to display.
     * @param {string|null} redirectUrl - The URL to redirect to (if applicable).
     */
    function showLogoutWarning(message, redirectUrl = null) {
        // Get the modal elements (you would need to have the corresponding HTML elements with these IDs)
        const modal = document.getElementById("logoutWarningModal");
        const modalMessage = document.getElementById("logoutWarningMessage");
        const modalButton = document.getElementById("logoutWarningButton");

        // Set the message and display the modal
        modalMessage.innerText = message;
        modal.style.display = "flex";

        // When the user acknowledges, hide the modal and possibly redirect
        modalButton.onclick = function () {
            modal.style.display = "none";
            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        };
    }

    // Set a timeout to show the first logout warning (1 minute before session timeout)
    if (remainingTime > warningTime) {
        setTimeout(() => {
            showLogoutWarning(
                "You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in."
            );
        }, (remainingTime - warningTime) * 1000);
    }

    // Set a timeout to show the final logout warning (3 seconds before session timeout) and then log out
    if (remainingTime > finalWarningTime) {
        setTimeout(() => {
            showLogoutWarning("You will be logged out due to inactivity.", "../logout.php");
        }, (remainingTime - finalWarningTime) * 1000);
    }

    // Automatically hide any notification message after 10 seconds
    setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
    }, 10000);

    /**
     * Function to smoothly scroll the page back to the top.
     */
    function scroll_to_top() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }
</script>
</html>
