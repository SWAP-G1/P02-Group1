<?php
// Start the session
session_start(); // Initiates a new session or resumes the existing session
session_regenerate_id(true); // Regenerates the session ID to prevent session fixation attacks

define('SESSION_TIMEOUT', 600); // Defines session timeout as 600 seconds (10 minutes)
define('WARNING_TIME', 60); // Sets warning time to 60 seconds before session expires
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before session logout
 
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

// Connect to the database 'xyz polytechnic'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Establishes connection to MySQL database
if (!$connect) {
    die('Could not connect: ' . mysqli_connect_errno()); // Outputs error if connection fails
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generates a secure CSRF token
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name']:""; // Retrieves the full name from session

// Function to check CSRF Token
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.'); // Stops execution if CSRF token is invalid
        exit();
    }
}

// View GPA functionality for 'xyz polytechnic' database
if (isset($_POST["view_button"])) {
    $csrf_token = $_POST["csrf_token"] ?? ''; // Retrieves CSRF token from POST request
    check_csrf_token($csrf_token); // Validate CSRF token

    $identification_code = $_POST["identification_code"];

    // Input validation: Check if identification_code is selected
    if (empty($identification_code)) {
        header("Location: admin_gpa.php?error=" . urlencode("Please select an Identification Code to view the GPA!"));
        exit();
    } else {
        // Query to calculate GPA based on the identification_code
        $query = $connect->prepare("SELECT identification_code, AVG(course_score) AS gpa FROM semester_gpa_to_course_code WHERE identification_code = ? GROUP BY identification_code"); // Prepare SQL query to calculate the average course score (GPA) for a given identification code
        $query->bind_param('s', $identification_code); // Bind the identification_code parameter to the SQL query to prevent SQL injection
        $query->execute(); // Execute the prepared SQL query
        $query->bind_result($identification_code_result, $gpa); // Bind the result of the query to PHP variables
        $query->fetch(); // Fetch the result of the query into the bound variables
        $query->close(); // Close the query to free up resources

        // Display the GPA in a JavaScript alert pop-up
        if (!empty($gpa)) {
            // Check for existing record and update or insert accordingly
            $check_query = $connect->prepare("SELECT COUNT(*) FROM student_score WHERE identification_code = ?"); // Prepare SQL query to check if GPA record already exists for the identification code
            $check_query->bind_param('s', $identification_code); // Bind the identification_code parameter to the SQL query
            $check_query->execute(); // Execute the query to check for existing records
            $check_query->bind_result($exists); // Bind the result to a variable to determine if a record exists
            $check_query->fetch(); // Fetch the result
            $check_query->close(); // Close the query to free up resources

            if ($exists > 0) {
                // Update existing record
                $update_query = $connect->prepare("UPDATE student_score SET semester_gpa = ? WHERE identification_code = ?"); // Prepare SQL query to update GPA if the record exists
                $update_query->bind_param('ds', $gpa, $identification_code); // Bind GPA and identification_code to the update query
                if ($update_query->execute()) { // Execute the update query
                    // Regenerate CSRF token after form submission
                    unset($_SESSION['csrf_token']); // Unset the old CSRF token for added security
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
                    header("Location: admin_gpa.php?success=2"); // Redirect with success message
                    exit();
                } else {
                    header("Location: admin_gpa.php?error=" . urlencode("Error updating GPA for Identification Code")); // Redirect with error message if update fails
                    exit();
                }
                $update_query->close(); // Close the update query
            } else {
                // Insert new record
                $insert_query = $connect->prepare("INSERT INTO student_score (identification_code, semester_gpa) VALUES (?, ?)"); // Prepare SQL query to insert new GPA record
                $insert_query->bind_param('sd', $identification_code, $gpa); // Bind identification_code and GPA to the insert query
                if ($insert_query->execute()) { // Execute the insert query
                    // Regenerate CSRF token after form submission
                    unset($_SESSION['csrf_token']); // Unset the old CSRF token for added security
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
                    header("Location: admin_gpa.php?success=2"); // Redirect with success message
                    exit();
                } else {
                    header("Location: admin_gpa.php?error=" . urlencode("Error inserting GPA for Identification Code")); // Redirect with error message if insert fails
                    exit();
                }
                $insert_query->close(); // Close the insert query
            }
        } else {
            header("Location: admin_gpa.php?error=" . urlencode("No GPA data found")); // Redirect with error message if no GPA data found
            exit();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Character encoding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive design for different devices -->
    <title>Student GPA</title> <!-- Page title -->
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet"> <!-- Import Google Fonts -->
</head>
<body>

    <div class="navbar"> <!-- Navigation bar -->
        <div class="navbar-brand"> <!-- Brand section with logo and title -->
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> <!-- School logo -->
            <h1>XYZ Polytechnic Management</h1> <!-- Website heading -->
        </div>
        <nav> <!-- Navigation links -->
            <a href=" ../admin_dashboard.php">Home</a> <!-- Home link -->
            <a href=" ../logout.php">Logout</a> <!-- Logout link -->
            <a><?php echo htmlspecialchars($full_name); ?></a> <!-- Display logged-in user's name securely -->
        </nav>
    </div>

    <div class="container"> <!-- Main content container -->
        <div class="card"> <!-- Card component for GPA overview -->
            <h2>Student GPA</h2>
            <p>View student's Grade Point Average. <a href="admin_score.php">VIEW STUDENT SCORES</a></p> <!-- Link to view student scores -->
            <?php
            // Display success or error messages based on the URL parameter
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="success-message">Student grade created successfully.</div>';
            }            

            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Student grade updated successfully.</div>';
            }

            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" class="success-message">Student grade deleted successfully.</div>';
            }

            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>'; // Securely display error message
            }
            ?>
        </div>

        <div class="card"> <!-- Card for GPA details -->
            <h3>Student GPA Details</h3>
            <form method="post" action="admin_gpa.php"> <!-- Form to select student identification code -->
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code" required> <!-- Dropdown for selecting student ID -->
                        <option value="">Select Identification Code</option>
                        <?php
                        // Fetch unique identification codes from the database
                        $result = $connect->query("SELECT DISTINCT identification_code FROM semester_gpa_to_course_code");
                        while ($row = $result->fetch_assoc()) {
                            $selected = isset($_POST['identification_code']) && $_POST['identification_code'] === $row['identification_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "' $selected>" . htmlspecialchars($row['identification_code']) . "</option>"; // Secure output
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- CSRF token for security -->
                <button type="submit" name="view_button" value="View GPA">View GPA</button> <!-- Submit button -->
            </form>
        </div>

        <div class="card"> <!-- Card to display GPA records -->
            <h3>Student GPA Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button> <!-- Scroll to top button -->
            <?php
            $con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Database connection
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno()); // Handle connection errors
            }

            $query = $connect->prepare("SELECT identification_code, semester_gpa FROM student_score"); // Prepare SQL query
            $query->execute(); // Execute query
            $query->bind_result($identification_code, $gpa); // Bind result variables

            echo '<table border="1" bgcolor="white" align="center">'; // Start table
            echo '<tr><th>Identification Code</th><th>GPA</th><th colspan="1">Operations</th></tr>'; // Table headers

            while ($query->fetch()) { // Fetch and display each row
                echo "<tr>";
                echo "<td>" . htmlspecialchars($identification_code) . "</td>"; // Secure output
                echo "<td>" . htmlspecialchars(number_format($gpa, 2)) . "</td>"; // Format GPA
                echo "<td><a href='#' onclick='confirmDelete(\"" . htmlspecialchars($identification_code) . "\", \"" . htmlspecialchars($_SESSION['csrf_token']) . "\")'>Delete</a></td>"; // Delete link
                echo "</tr>";
            }
            echo "</table>";

            $con->close(); // Close database connection
            ?>
        </div>
    </div>

    <footer class="footer"> <!-- Footer section -->
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

    <!-- Logout and Confirmation Modals -->
    <div id="logoutWarningModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="logoutWarningMessage"></p>
            <button id="logoutWarningButton">OK</button>
        </div>
    </div>

    <div id="confirmationModal" class="modal" style="display: none;">
        <div class="modal-content">
            <p id="confirmationMessage"></p>
            <button id="confirmationButton">Yes</button>
            <button onclick="hideModal()">Cancel</button>
        </div>
    </div>

    <script>
        const remainingTime = <?php echo $remaining_time; ?>; // Session timeout remaining time
        const warningTime = <?php echo WARNING_TIME; ?>; // Warning time before logout
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning time before logout

        function showLogoutWarning(message, redirectUrl = null) {
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message; // Display warning message
            modal.style.display = "flex";

            modalButton.onclick = function () {
                modal.style.display = "none"; // Hide modal on click
                if (redirectUrl) {
                    window.location.href = redirectUrl; // Redirect if URL provided
                }
            };
        }

        if (remainingTime > warningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
            }, (remainingTime - warningTime) * 1000); // Set warning timer
        }

        if (remainingTime > finalWarningTime) {
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php"); // Final warning
            }, (remainingTime - finalWarningTime) * 1000);
        }

        setTimeout(function() {
            const messageElement = document.getElementById('message');
            if (messageElement) {
                messageElement.style.display = 'none'; // Hide messages after 10 seconds
            }
        }, 10000);

        setTimeout(() => {
            window.location.href = " ../logout.php"; // Auto-logout when session expires
        }, remainingTime * 1000);

        function scroll_to_top() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Smooth scroll to top
            });
        }

        function confirmDelete(identification_code, csrfToken) {
            const modal = document.getElementById("confirmationModal"); // Get confirmation modal element
            const modalMessage = document.getElementById("confirmationMessage"); // Get modal message container
            const modalButton = document.getElementById("confirmationButton"); // Get confirmation button

            modalMessage.innerText = "Are you sure you want to delete this?"; // Display confirmation message
            modal.style.display = "flex"; // Show the modal

            modalButton.onclick = function () { // Event listener for confirmation button
                window.location.href = `admin_delete_gpa.php?operation=delete&identification_code=${identification_code}&csrf_token=${csrfToken}`; // Redirect to delete GPA record with CSRF protection
            };
        }

        function hideModal() {
            const modal = document.getElementById("confirmationModal"); // Get confirmation modal element
            modal.style.display = "none"; // Hide the modal when the cancel button is clicked
        }
    </script>

</body>
</html>
