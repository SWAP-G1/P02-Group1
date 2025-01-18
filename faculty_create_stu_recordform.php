<?php
session_start();
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

// Check if the user is logged in and has the correct role (faculty role: 1)
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    header("Location: testlogin.php");
    exit();
}

// Fetch faculty's full name from the session for display purposes
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Query to fetch all class codes and their associated course names
$class_query = "
    SELECT c.class_code, co.course_name
    FROM class c
    JOIN course co ON c.course_code = co.course_code
";

$class_result = mysqli_query($con, $class_query);

// Organize class codes and course names into an array
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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Management</title>
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
            <a href="faculty_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
    <div class="container">
        <div class="card">
            <h2>Student Profile Management</h2>
            <p>Add and view student profiles.</p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<p style="color: green; font-weight: bold;">Student record created successfully.</p>';
            }

            // If ?success=1 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<p style="color: green; font-weight: bold;">Student record updated successfully.</p>';
            }

            // If ?success=1 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<p style="color: green; font-weight: bold;">Student record deleted successfully.</p>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<p style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</p>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Create Student Profile Form</h3>
            <form method="POST" action="faculty_create_stu_record.php">
                <div class="form-group">
                    <label class="label" for="student_name">Student Name</label>
                    <input type="text" name="student_name" placeholder="Enter Student Name" required>
                </div>
                <div class="form-group">
                    <label class="label" for="phone_number">Phone Number</label>
                    <input type="text" name="phone_number" placeholder="Enter Phone Number" maxlength="8" required>
                </div>
                <div class="form-group">
                    <label class="label" for="student_id_code">Student ID Code</label>
                    <p>Student Email Format: Student ID + @student.xyz.sg</p>
                    <input type="text" name="student_id_code" placeholder="Enter Student ID Code" maxlength="4" required>
                </div>
                <div class="form-group">
                    <label class="label" for="diploma_code">Diploma Name</label>
                    <select name="diploma_code" required>
                        <option value="" disabled selected>Select a Diploma Name</option>
                        <?php
                        if ($diploma_result && mysqli_num_rows($diploma_result) > 0) {
                            while ($row = mysqli_fetch_assoc($diploma_result)) {
                                echo "<option value='" . htmlspecialchars($row['diploma_code']) . "'>" . htmlspecialchars($row['diploma_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_code_1">Class Code 1</label>
                    <select name="class_code_1" required>
                        <option value="" disabled selected>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                 "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_2">Class Code 2</label>
                    <select name="class_code_2" required>
                        <option value="" disabled selected>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                 "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_3">Class Code 3</label>
                    <select name="class_code_3" required>
                        <option value="" disabled selected>Select a Class Code</option>
                        <?php
                        foreach ($class_codes as $class) {
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "'>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) .
                                 "</option>";
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src="scrollup.png" alt="Scroll to top"></button>

            <?php

            // Query to fetch student details along with class codes and course names
            $stmt = $con->prepare("
                SELECT 
                    u.identification_code,
                    u.full_name,
                    u.phone_number,
                    s.class_code,
                    co.course_name,
                    d.diploma_code,
                    d.diploma_name
                FROM 
                    user u
                JOIN 
                    student s ON u.identification_code = s.identification_code
                JOIN
                    diploma d ON s.diploma_code = d.diploma_code
                JOIN
                    class c ON s.class_code = c.class_code
                JOIN
                    course co ON c.course_code = co.course_code
            ");

            // Execute the prepared query
            $stmt->execute();

            // Retrieve the results of the executed query
            $result = $stmt->get_result();

            // Organize student data into an array for display
            $students = [];
            while ($row = $result->fetch_assoc()) {
                $student_id = $row['identification_code'];
                if (!isset($students[$student_id])) {
                    $students[$student_id] = [
                        'identification_code' => $row['identification_code'],
                        'full_name' => $row['full_name'],
                        'phone_number' => $row['phone_number'],
                        'diploma_code' => $row['diploma_code'],
                        'diploma_name' => $row['diploma_name'],
                        'class_code_1' => null,
                        'class_code_2' => null,
                        'class_code_3' => null,
                    ];
                }

                // Assign class codes and course names to slots
                if (is_null($students[$student_id]['class_code_1'])) {
                    $students[$student_id]['class_code_1'] = $row['class_code'] . ": " . $row['course_name'];
                } elseif (is_null($students[$student_id]['class_code_2'])) {
                    $students[$student_id]['class_code_2'] = $row['class_code'] . ": " . $row['course_name'];
                } elseif (is_null($students[$student_id]['class_code_3'])) {
                    $students[$student_id]['class_code_3'] = $row['class_code'] . ": " . $row['course_name'];
                }
            }

            // Start HTML table to display student records
            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr>
                    <th>Student ID</th>        
                    <th>Name</th>             
                    <th>Phone Number</th>    
                    <th>Class Code 1</th>        
                    <th>Class Code 2</th>        
                    <th>Class Code 3</th>        
                    <th>Diploma Name</th>        
                    <th colspan="2">Operations</th> 
                </tr>';

            // Display each student record in the table
            foreach ($students as $student) {
                if (preg_match('/^\d{3}[A-Z]$/', $student['identification_code'])) {
                    echo '<tr>';
                    echo '<td>' . $student['identification_code'] . '</td>';
                    echo '<td>' . $student['full_name'] . '</td>';
                    echo '<td>' . $student['phone_number'] . '</td>';
                    echo '<td>' . ($student['class_code_1'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($student['class_code_2'] ?? 'N/A') . '</td>';
                    echo '<td>' . ($student['class_code_3'] ?? 'N/A') . '</td>';
                    echo '<td>' . $student['diploma_name'] . '</td>';
                    echo '<td> <a href="faculty_update_stu_recordform.php?student_id=' . $student['identification_code'] . '">Edit</a> </td>';
                    echo '</tr>';
                }
            }

            // Close the HTML table
            echo '</table>';

            // Close the database connection
            $con->close();
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
                showLogoutWarning("You will be logged out due to inactivity.", "logout.php");
            }, (remainingTime - finalWarningTime) * 1000);
        }

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
