<?php
// Start the session
session_start();

// Regenerate session ID to prevent session fixation attacks
session_regenerate_id(true);

// Define session timeout and warning thresholds
define('SESSION_TIMEOUT', 600); // 10 minutes session timeout
define('WARNING_TIME', 60); // 1 minute warning before timeout
define('FINAL_WARNING_TIME', 3); // Final 3 seconds warning before logout

// Function to check session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity']; // Calculate inactive time
        if ($inactive_time > SESSION_TIMEOUT) return; // If session has timed out, do nothing
    }
    $_SESSION['last_activity'] = time(); // Update last activity time
}

// Check session timeout on each request
checkSessionTimeout();

// Calculate remaining session time
$remaining_time = isset($_SESSION['last_activity']) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity'])
    : SESSION_TIMEOUT;

// Establish a connection to the MySQL database
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) die('Connection failed: ' . mysqli_connect_error()); // Check connection

// Get faculty school code
$school_code = '';
if (isset($_SESSION['session_identification_code'])) {
    $faculty_id = $_SESSION['session_identification_code'];
    $school_query = $connect->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $school_query->bind_param('s', $faculty_id); // Bind parameter
    $school_query->execute(); // Execute query
    $school_query->bind_result($school_code); // Bind result to variable
    $school_query->fetch(); // Fetch the result
    $school_query->close(); // Close the statement
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Secure random token
}

// Retrieve full name from session
$full_name = $_SESSION['session_full_name'] ?? "";

// Function to validate CSRF token
function check_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token"); // Terminate script if CSRF token is invalid
    }
}

// Handle GPA view request
if (isset($_POST["view_button"])) {
    check_csrf_token($_POST["csrf_token"] ?? ''); // Validate CSRF token
    
    $identification_code = $_POST["identification_code"];
    
    if (empty($identification_code)) {
        header("Location: faculty_gpa.php?error=" . urlencode("Please select an Identification Code"));
        exit();
    }

    // Verify student belongs to the faculty's school
    // Count the number of matching records to verify if the student exists and belongs to the faculty's school
    // Select data from the 'student' table with alias 's'
    // Join 'student' table with 'diploma' table to get the school code
    // Filter results to match the student's ID and the faculty's school code
    $student_check = $connect->prepare(" 
        SELECT COUNT(*) 
        FROM student s  
        JOIN diploma d ON s.diploma_code = d.diploma_code 
        WHERE s.identification_code = ? AND d.school_code = ?
    ");
    $student_check->bind_param('ss', $identification_code, $school_code); // Bind parameters for student ID and school code
    $student_check->execute(); // Execute the prepared statement
    $student_check->bind_result($valid_student); // Bind the result to the variable $valid_student
    $student_check->fetch(); // Fetch the result from the executed query
    $student_check->close(); // Close the prepared statement

    // If the student does not belong to the faculty's school, redirect with an error message
    if (!$valid_student) {
        header("Location: faculty_gpa.php?error=" . urlencode("Student not in your school"));
        exit();
    }

    // Calculate GPA with school filter
    // Select the average course score
    // From the GPA table (alias: sg)
    // Join with student table to match IDs
    // Join with diploma table to get the school code
    // Filter by student ID and school code
    $gpa_query = $connect->prepare("
        SELECT AVG(sg.course_score) 
        FROM semester_gpa_to_course_code sg  
        JOIN student s ON sg.identification_code = s.identification_code  
        JOIN diploma d ON s.diploma_code = d.diploma_code  
        WHERE sg.identification_code = ? AND d.school_code = ?  
    ");
    $gpa_query->bind_param('ss', $identification_code, $school_code); // Bind parameters for student ID and school code
    $gpa_query->execute(); // Execute the GPA query
    $gpa_query->bind_result($gpa); // Bind the GPA result to the variable $gpa
    $gpa_query->fetch(); // Fetch the GPA result
    $gpa_query->close(); // Close the GPA prepared statement

    if (!$gpa) {
        header("Location: faculty_gpa.php?error=" . urlencode("No GPA data found"));
        exit();
    }

    // Update student_score table
    // Delete existing GPA record for the student
    $delete_query = $connect->prepare("DELETE FROM student_score WHERE identification_code = ?");
    $delete_query->bind_param('s', $identification_code);
    $delete_success = $delete_query->execute();
    $delete_query->close();

    if ($delete_success) {
        // Insert new GPA record
        $insert_query = $connect->prepare("
            INSERT INTO student_score (identification_code, semester_gpa) 
            VALUES (?, ?)
        ");
        $insert_query->bind_param('sd', $identification_code, $gpa);
        if ($insert_query->execute()) {
            // Regenerate CSRF token after form submission for security
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header("Location: faculty_gpa.php?success=2"); // Redirect on success
        } else {
            header("Location: faculty_gpa.php?error=" . urlencode("Database error on insert"));
        }
        $insert_query->close();
    } else {
        header("Location: faculty_gpa.php?error=" . urlencode("Database error on delete"));
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student GPA</title>
    <link rel="stylesheet" href=" ../styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href=" ../faculty_dashboard.php">Home</a>
            <a href=" ../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student GPA</h2>
            <p>View student's Grade Point Average. <a href="faculty_score.php">VIEW STUDENT SCORES</a></p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" class="success-message">Student grade created successfully.</div>';
            }            

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Student grade updated successfully.</div>';
            }

            // If ?success=3 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" class="success-message">Student grade deleted successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Student GPA Details</h3>
            <form method="post" action="faculty_gpa.php">
                <div class="form-group">
                    <label>Student Identification Code</label>
                    <select name="identification_code" required>
                        <option value="">Select Identification Code</option>
                        <?php
                        // Select the student identification code from the 'student' table
                        // Querying from the 'student' table with alias 's' for simplicity
                        // Join the 'diploma' table (alias 'd') to link student with their diploma details
                        // Filter the results to only include students from the specified school code (parameterized for security)
                        $students_query = $connect->prepare(" 
                            SELECT s.identification_code  
                            FROM student s               
                            JOIN diploma d ON s.diploma_code = d.diploma_code  
                            WHERE d.school_code = ?       
                        ");
                        $students_query->bind_param('s', $school_code);
                        $students_query->execute();
                        $result = $students_query->get_result();
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" 
                                 . htmlspecialchars($row['identification_code']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <button type="submit" name="view_button">View GPA</button>
            </form>
        </div>

        <div class="card">
            <h3>Student GPA Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button>
            <?php
            // Select the student identification code and their semester GPA from 'student_score'
            // Querying from the 'student_score' table with alias 'ss'
            // Join 'student' table to link scores with student details using identification_code
            // Join 'diploma' table to get the school code associated with the student
            // Filter results to only include students from the specified school code (parameterized for security)
            $records_query = $connect->prepare(" 
                SELECT ss.identification_code, ss.semester_gpa  
                FROM student_score ss                          
                JOIN student s ON ss.identification_code = s.identification_code  
                JOIN diploma d ON s.diploma_code = d.diploma_code                
                WHERE d.school_code = ?                       
            ");
            $records_query->bind_param('s', $school_code);
            $records_query->execute();
            $result = $records_query->get_result();

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>GPA</th></tr>';
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>" . htmlspecialchars($row['identification_code']) . "</td>
                    <td>" . number_format($row['semester_gpa'], 2) . "</td>
                </tr>";
            }
            echo "</table>";
            ?>
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
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php");
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
            window.location.href = " ../logout.php";
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