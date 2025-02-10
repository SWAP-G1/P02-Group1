<?php
// Start the session
session_start(); // Initialize session handling
session_regenerate_id(true); // Regenerate session ID to prevent session fixation attacks
define('SESSION_TIMEOUT', 600); // 600 seconds = 10 minutes session timeout
define('WARNING_TIME', 60); // 60 seconds (1 minute) warning before session ends
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout

// Function to check and handle session timeout
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) { // Check if last activity is set
        $inactive_time = time() - $_SESSION['last_activity']; // Calculate inactive time
        if ($inactive_time > SESSION_TIMEOUT) { // Check if session has timed out
            return; // Exit function if session timed out
        }
    }
    $_SESSION['last_activity'] = time(); // Update last activity time
}

checkSessionTimeout(); // Invoke session timeout check

$remaining_time = (isset($_SESSION['last_activity'])) 
    ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) // Calculate remaining session time
    : SESSION_TIMEOUT; // Default session timeout if no activity recorded

// Connect to the database
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$connect) {
    die('Could not connect: ' . mysqli_connect_errno()); // Terminate script if connection fails
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a random CSRF token
}

// Check if user session role is authorized
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
   header("Location:  ../login.php"); // Redirect to login if unauthorized
   exit(); // Terminate script
}

// Get faculty school code
$faculty_id = $_SESSION['session_identification_code'] ?? ''; // Retrieve faculty ID from session
$school_code = '';
if ($faculty_id) {
    $school_query = $connect->prepare("SELECT school_code FROM faculty WHERE faculty_identification_code = ?");
    $school_query->bind_param('s', $faculty_id); // Bind faculty ID to query
    $school_query->execute(); // Execute the query
    $school_query->bind_result($school_code); // Bind result to school_code
    $school_query->fetch(); // Fetch the result
    $school_query->close(); // Close the query
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : ""; // Retrieve full name from session

// Function to check CSRF token validity
function check_csrf_token($csrf_token) {
    if (!isset($_SESSION['csrf_token']) || $csrf_token !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.'); // Terminate if CSRF token is invalid
    }
}

// Function to assign grade based on course score
function assign_grade($course_score) {
    // Check if course_score is numeric
    if (!is_numeric($course_score)) {
        return 'X'; // Return 'X' if the score is not a number
    }

    // Grade assignment based on score range
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
        return 'X'; // Return 'X' for invalid score
    }
}

// Insert record with school validation
if (isset($_POST["insert_button"])) {
    if ($_POST["insert"] == "yes") {
        $csrf_token = $_POST["csrf_token"] ?? '';
        check_csrf_token($csrf_token); // Validate CSRF token

        $identification_code = $_POST["identification_code"];
        $course_code = $_POST["course_code"];
        $course_score_input = $_POST["course_score"];

        // Strict validation for course_score
        if (!is_numeric($course_score_input)) {
            header("Location: faculty_score.php?error=" . urlencode("Invalid input. Course score must be a number."));
            exit();
        }

        $course_score = (float)$course_score_input; // Cast to float after validation
        $grade = assign_grade($course_score); // Assign grade based on score

        // Continue with existing logic
        if ($grade == 'X') {
            header("Location: faculty_score.php?error=" . urlencode("Invalid score (0-4 only)"));
            exit();
        }

        // Validate student belongs to faculty's school
        // Prepare an SQL statement to count the number of students
// This query checks if a student with a specific identification code exists
// and belongs to the same school code as provided
// The JOIN operation links the 'student' table with the 'diploma' table based on the diploma_code
// The WHERE clause ensures the student identification and school code match the given criteria
        $student_check = $connect->prepare("SELECT COUNT(*) FROM student s JOIN diploma d ON s.diploma_code = d.diploma_code WHERE s.identification_code = ? AND d.school_code = ?");
        $student_check->bind_param('ss', $identification_code, $school_code);
        $student_check->execute();
        $student_check->bind_result($student_count);
        $student_check->fetch();
        $student_check->close();

        if ($student_count == 0) {
            header("Location: faculty_score.php?error=" . urlencode("Student not in your school"));
            exit();
        }

        // Validate course belongs to faculty's school
        // Prepare an SQL statement to count the number of courses
// This query checks if a course with a specific course code exists
// and belongs to the same school code as provided
// The JOIN operation links the 'course' table with the 'diploma' table based on the diploma_code
// The WHERE clause ensures the course code and school code match the given criteria
        $course_check = $connect->prepare("SELECT COUNT(*) FROM course c JOIN diploma d ON c.diploma_code = d.diploma_code WHERE c.course_code = ? AND d.school_code = ?");
        $course_check->bind_param('ss', $course_code, $school_code);
        $course_check->execute();
        $course_check->bind_result($course_count);
        $course_check->fetch();
        $course_check->close();

        if ($course_count == 0) {
            header("Location: faculty_score.php?error=" . urlencode("Course not in your school"));
            exit();
        }

        // Check for duplicate entry
        $check_query = $connect->prepare("SELECT COUNT(*) FROM semester_gpa_to_course_code WHERE identification_code = ? AND course_code = ?");
        $check_query->bind_param('ss', $identification_code, $course_code);
        $check_query->execute();
        $check_query->bind_result($count);
        $check_query->fetch();
        $check_query->close();

        if ($count > 0) {
            header("Location: faculty_score.php?error=" . urlencode("Duplicate entry"));
            exit();
        } else {
            $query = $connect->prepare("INSERT INTO semester_gpa_to_course_code (grade_id, identification_code, course_code, course_score, grade) VALUES (NULL, ?, ?, ?, ?)");
            $query->bind_param('ssds', $identification_code, $course_code, $course_score, $grade);
            if ($query->execute()) {
                // Regenerate CSRF token after form submission
                unset($_SESSION['csrf_token']);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate new CSRF token
                header("Location: faculty_score.php?success=1");
                exit();
            }
        }
    }
}

// Update record with school validation
if (isset($_POST["update_button"])) {
    $csrf_token = $_POST["csrf_token"] ?? '';
    check_csrf_token($csrf_token); // Validate CSRF token

    $id = $_POST["id"];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score = $_POST["course_score"];
    $grade = assign_grade($course_score); // Assign grade based on score

    $query = $connect->prepare("UPDATE semester_gpa_to_course_code SET identification_code=?, course_code=?, course_score=?, grade=? WHERE grade_id=?");
    $query->bind_param('ssdsi', $identification_code, $course_code, $course_score, $grade, $id);
    $query->execute(); // Execute the update query
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Specifies the character encoding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Ensures responsive design on all devices -->
    <title>Student Record</title> <!-- Sets the title of the web page -->
    <link rel="stylesheet" href=" ../styles.css"> <!-- Links an external CSS stylesheet -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet"> <!-- Imports Google Fonts -->
</head>
<body>

    <div class="navbar"> <!-- Navigation bar section -->
        <div class="navbar-brand"> <!-- Brand section of the navbar -->
            <img src=" ../logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> <!-- Displays the school logo -->
            <h1>XYZ Polytechnic Management</h1> <!-- Displays the heading -->
        </div>
        <nav> <!-- Navigation links -->
            <a href=" ../faculty_dashboard.php">Home</a> <!-- Link to the faculty dashboard -->
            <a href=" ../logout.php">Logout</a> <!-- Link to logout -->
            <a><?php echo htmlspecialchars($full_name); ?></a> <!-- Displays the full name securely -->
        </nav>
    </div>

    <div class="container"> <!-- Main container for content -->
        <div class="card"> <!-- Card layout for content -->
            <h2>Student Grading System</h2> <!-- Title for the grading system section -->
            <p>Add, update, and organize student score records. <a href="faculty_gpa.php">VIEW GPA</a></p> <!-- Description with link to GPA page -->
            <?php
            if (isset($_GET['success'])) { // Check if success message is set
                $messages = [
                    1 => 'Student grade created successfully.',
                    2 => 'Student grade updated successfully.',
                    3 => 'Student grade deleted successfully.'
                ];
                echo '<div id="message" class="success-message">' . $messages[$_GET['success']] . '</div>'; // Display success message
            }
            if (isset($_GET['error'])) { // Check if error message is set
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>'; // Display error message securely
            }
            ?>
        </div>

        <div class="card"> <!-- Form card for adding/updating student scores -->
            <h3>Student Score Details</h3>
            <form method="post" action="faculty_score.php"> <!-- Form submission to faculty_score.php -->
                <div class="form-group"> <!-- Form group for student identification code -->
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code" required> <!-- Dropdown for selecting student code -->
                        <option value="">Select Identification Code</option>
                        <?php
                        // Prepare SQL query to fetch student IDs
                        // Select the identification_code from the student table
                        // Specify the student table with alias 's'
                        // Join the diploma table with student table based on matching diploma_code
                        // Filter records where the school_code matches the provided parameter (placeholder '?')
                        $student_query = $connect->prepare(" 
                            SELECT DISTINCT s.identification_code  
                            FROM student s  
                            JOIN diploma d ON s.diploma_code = d.diploma_code  
                            WHERE d.school_code = ?  
                        ");
                        $student_query->bind_param('s', $school_code); // Bind parameters securely
                        $student_query->execute(); // Execute the query
                        $result = $student_query->get_result(); // Get the result set
                        while ($row = $result->fetch_assoc()) { // Loop through each student record
                            echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" . htmlspecialchars($row['identification_code']) . "</option>"; // Display student ID securely
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group"> <!-- Form group for course code -->
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code" required> <!-- Dropdown for selecting course code -->
                        <option value="">Select Course Code</option>
                        <?php
                        // Prepare SQL query to fetch course codes
                        // Select the course_code from the course table
                        // Specify the course table with alias 'c'
                        // Join the diploma table with the course table based on matching diploma_code
                        // Filter records where the school_code in the diploma table matches the provided parameter (placeholder '?')
                        $course_query = $connect->prepare("
                            SELECT c.course_code  
                            FROM course c  
                            JOIN diploma d ON c.diploma_code = d.diploma_code  
                            WHERE d.school_code = ?  
                        ");
                        $course_query->bind_param('s', $school_code); // Bind parameters securely
                        $course_query->execute(); // Execute the query
                        $result = $course_query->get_result(); // Get the result set
                        while ($row = $result->fetch_assoc()) { // Loop through each course record
                            echo "<option value='" . htmlspecialchars($row['course_code']) . "'>" . htmlspecialchars($row['course_code']) . "</option>"; // Display course code securely
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group"> <!-- Form group for course score -->
                    <label class="label" for="course_score">Course Score</label>
                    <td><input type="text" name="course_score" value="<?php echo isset($_POST['course_score']) ? htmlspecialchars($_POST['course_score'], ENT_QUOTES, 'UTF-8') : ''; ?>" required/> <!-- Input field for course score -->
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>"> <!-- Hidden CSRF token for security -->
                <input type="hidden" name="insert" value="yes"> <!-- Hidden field to indicate insert operation -->
                <button type="submit" name="insert_button">Insert Score</button> <!-- Submit button -->
            </form>
        </div>

        <div class="card"> <!-- Card to display student score records -->
            <h3>Student Score Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src=" ../scroll_up.png" alt="Scroll to top"></button> <!-- Button to scroll to top -->
            <?php
            // Prepare SQL query to fetch student score records
            // Select all columns from the semester_gpa_to_course_code table using the alias 'sg'
            // Specify the semester_gpa_to_course_code table with alias 'sg'
            // Join the student table with alias 's' based on matching identification_code
            // Join the diploma table with alias 'd' based on matching diploma_code from the student table
            // Filter records where the school_code in the diploma table matches the provided parameter (placeholder '?')
            $query = $connect->prepare(" 
                SELECT DISTINCT sg.*  
                FROM semester_gpa_to_course_code sg  
                JOIN student s ON sg.identification_code = s.identification_code  
                JOIN diploma d ON s.diploma_code = d.diploma_code  
                WHERE d.school_code = ?  
            ");
            $query->bind_param('s', $school_code); // Bind parameters securely
            $query->execute(); // Execute the query
            $result = $query->get_result(); // Get the result set

            echo '<table border="1" bgcolor="white" align="center">'; // Create table for displaying records
            echo '<tr><th>ID Code</th><th>Course</th><th>Score</th><th>Grade</th><th colspan="2">Operations</th></tr>'; // Table headers
            
            while ($row = $result->fetch_assoc()) { // Loop through each record
                echo '<tr>'; // Start new table row
                echo "<td>" . htmlspecialchars($row['identification_code']) . "</td>"; // Display ID code securely
                echo "<td>" . htmlspecialchars($row['course_code']) . "</td>"; // Display course code securely
                echo "<td>" . htmlspecialchars($row['course_score']) . "</td>"; // Display course score securely
                echo "<td>" . htmlspecialchars($row['grade']) . "</td>"; // Display grade securely
                echo "<td><a href='faculty_edit.php?operation=edit&id=" . $row['grade_id'] . "'>Edit</a></td>"; // Link to edit record
                echo '</tr>'; // End table row
            }
            echo '</table>'; // End table
            ?>
        </div>
    </div>

    <footer class="footer"> <!-- Footer section -->
        <p>&copy; 2024 XYZ Polytechnic. All rights reserved.</p> <!-- Copyright information -->
    </footer>

    <script>
        const remainingTime = <?php echo $remaining_time; ?>; // Remaining session time in seconds
        const warningTime = <?php echo WARNING_TIME; ?>; // Warning time before session timeout
        const finalWarningTime = <?php echo FINAL_WARNING_TIME; ?>; // Final warning time before session timeout

        function showLogoutWarning(message, redirectUrl = null) { // Function to show logout warning modal
            const modal = document.getElementById("logoutWarningModal");
            const modalMessage = document.getElementById("logoutWarningMessage");
            const modalButton = document.getElementById("logoutWarningButton");

            modalMessage.innerText = message;
            modal.style.display = "flex";

            modalButton.onclick = function () { // Hide modal and redirect if needed
                modal.style.display = "none";
                if (redirectUrl) {
                    window.location.href = redirectUrl;
                }
            };
        }

        if (remainingTime > warningTime) { // Show warning before session timeout
            setTimeout(() => {
                showLogoutWarning("You will be logged out in 1 minute due to inactivity. Please interact with the page to stay logged in.");
            }, (remainingTime - warningTime) * 1000);
        }

        if (remainingTime > finalWarningTime) { // Show final warning before logout
            setTimeout(() => {
                showLogoutWarning("You will be logged out due to inactivity.", " ../logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }

        setTimeout(function() { // Hide success/error message after 10 seconds
            const messageElement = document.getElementById('message');
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, 10000);

        setTimeout(() => { // Automatically log out when session expires
            window.location.href = " ../logout.php";
        }, remainingTime * 1000);

        function scroll_to_top() { // Function to scroll to the top of the page
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>
</body>
</html>
