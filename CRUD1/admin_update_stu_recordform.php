<?php
session_start();
session_regenerate_id(true);
// Define session timeout and and its variables
define('SESSION_TIMEOUT', 600); // 10 minutes
define('WARNING_TIME', 60); // Warning 1 minute before timeout
define('FINAL_WARNING_TIME', 3); // Final warning 3 seconds before logout

// Check session timeout and update activity timestamp
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) {
            return;
        }
    }
    $_SESSION['last_activity'] = time();
}

checkSessionTimeout();

$remaining_time = isset($_SESSION['last_activity']) ? SESSION_TIMEOUT - (time() - $_SESSION['last_activity']) : SESSION_TIMEOUT;

// Database connection
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno());
}

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check user role and session
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    header("Location: ../login.php");
    exit();
}
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch all class codes and associated course names and use JOIN to join the rows 
//joins course, diploma table via course code and diploma code
$class_query = "
    SELECT c.class_code, co.course_name, d.diploma_code
    FROM class c
    JOIN course co ON c.course_code = co.course_code
    JOIN diploma d ON co.diploma_code = d.diploma_code
";
//store results in class_result after execution
$class_result = mysqli_query($con, $class_query);
// Store class codes and course names
$class_codes = [];
//checks if class_result exists and there are more than 0 rows
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_codes[] = [
            //array will have class code linked to corresponding course anme and diploma code
            'class_code' => $row['class_code'],
            'course_name' => $row['course_name'],
            'diploma_code' => $row['diploma_code'] 
        ];
    }
}
// Fetch all diploma codes and names
$diploma_query = "SELECT diploma_code, diploma_name FROM diploma";
$diploma_result = mysqli_query($con, $diploma_query);

// Fetch student details based on the provided student ID
//fetch student ID from url
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';
if (!empty($student_id)) {
    //fetch all data related to student based on student id. LEFT JOIN will join the rows even if there is a class code that is empty
    $stmt = $con->prepare("
        SELECT u.full_name, u.phone_number, u.identification_code, s.diploma_code, s.class_code, co.course_name
        FROM user u
        LEFT JOIN student s ON u.identification_code = s.identification_code
        LEFT JOIN class c ON s.class_code = c.class_code
        LEFT JOIN course co ON c.course_code = co.course_code
        WHERE u.identification_code = ?
    ");
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    //create existing_classes array and hold class info associated with student
    $existing_classes = [];
    //set all the values to null initially to prevent any undefined variable error if no rows are returned
    $student_name = $phone_number = $diploma_code = $identification_code = null;

    while ($row = $result->fetch_assoc()) {
        $student_name = $row['full_name'];
        $phone_number = $row['phone_number'];
        $diploma_code = $row['diploma_code'];
        $identification_code = $row['identification_code'];
        $existing_classes[] = [
            // Store the student's class code and corresponding course name in the array
            'class_code' => $row['class_code'],
            'course_name' => $row['course_name']
        ];
    }

    if (empty($student_name)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Student record not found."));
        exit();
    }

    // If no classes are found, set an error message
    if (empty($existing_classes)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Student record not found."));
        exit();
    }
    
    // Validate student ID format "S" followed by 3 numerical digits
    $pattern_student_id = '/^S\d{3}$/';
    if (!preg_match($pattern_student_id, $student_id)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Invalid Student ID format. It must start with letter 'S' followed by 3 numbers."));
        exit();
    }
}

$con->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Student Record</title>
    <link rel="stylesheet" href="/SWAP/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="../logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="admin_create_stu_recordform.php">Back to Student Records</a>
            <a href="../logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Update Student Record</h2>
            <?php
            if (isset($_GET['error'])) {
                echo '<div id="message" class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" class="success-message">Student record updated successfully.</div>';
            }
            ?>
            <form method="POST" action="admin_update_stu_record.php?student_id=<?php echo htmlspecialchars($student_id); ?>">
                <div class="form-group">
                    <label class="label" for="student_name">Student Name</label>
                    <input type="text" name="upd_student_name" value="<?php echo htmlspecialchars($student_name); ?>" required>
                </div>
                <div class="form-group">
                    <label class="label" for="phone_number">Phone Number</label>
                    <input type="text" name="upd_phone_number" maxlength="8" value="<?php echo htmlspecialchars($phone_number); ?>" required>
                </div>
                <div class="form-group">
                    <label class="label" for="student_id_code">Student ID Code</label>
                    <p>Student Email Format: Student ID + @gmail.com</p>
                    <input type="text" name="upd_student_id_code" maxlength="4" value="<?php echo htmlspecialchars($student_id); ?>" required>
                </div>
                <div class="form-group">
                    <label class="label" for="diploma_code">Diploma Name</label>
                    <select name="upd_diploma_code" required>
                        <option value="" disabled>Select a Diploma Name</option>
                        <?php
                        //checks if diploma_result exists with at least one row
                        if ($diploma_result && mysqli_num_rows($diploma_result) > 0) {
                            while ($row = mysqli_fetch_assoc($diploma_result)) {
                                //iterate over each row in result set
                                //compares diploma_code from current diploma_code row with student's currently assigned diploma code
                                //if one of the rows match (then condition), the $selected variable to 'selected' to pre select the assigned diploma code 
                                $selected = ($row['diploma_code'] === $diploma_code) ? 'selected' : '';
                                //diploma code is set to the value using the option html
                                echo "<option value='" . htmlspecialchars($row['diploma_code']) . "' $selected>" . htmlspecialchars($row['diploma_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_1">Class 1</label>
                    <select name="upd_class_code_1">
                        <!-- if no class is assigned, No Class is set as the option --->
                        <option value="" <?php echo empty($existing_classes[0]['class_code']) ? 'selected' : ''; ?>>No Class</option>
                        <?php
                        //iterate through class codes array that contain class info fetched from db
                        foreach ($class_codes as $class) {
                            //check if there is entery in first row of student currently assigned to and if it matches student's assigned class
                            //if it matches, it will pre select it using selected html, if not, dont pre select it
                            $selected = (!empty($existing_classes[0]) && $class['class_code'] === $existing_classes[0]['class_code']) ? 'selected' : '';
                            //display the options of the fetches class codes in class_codes array
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" .
                                 htmlspecialchars($class['diploma_code']) . ")</option>";
                        }                        
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_2">Class 2</label>
                    <select name="upd_class_code_2">
                        <option value="" <?php echo empty($existing_classes[1]['class_code']) ? 'selected' : ''; ?>>No Class</option>
                        <?php
                        foreach ($class_codes as $class) {
                            $selected = (!empty($existing_classes[1]) && $class['class_code'] === $existing_classes[1]['class_code']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" .
                                 htmlspecialchars($class['diploma_code']) . ")</option>";
                        }                        
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="label" for="class_code_3">Class 3</label>
                    <select name="upd_class_code_3">
                        <option value="" <?php echo empty($existing_classes[2]['class_code']) ? 'selected' : ''; ?>>No Class</option>
                        <?php
                        foreach ($class_codes as $class) {
                            $selected = (!empty($existing_classes[2]) && $class['class_code'] === $existing_classes[2]['class_code']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($class['class_code']) . "' $selected>" .
                                 htmlspecialchars($class['class_code']) . ": " . htmlspecialchars($class['course_name']) . " (" .
                                 htmlspecialchars($class['diploma_code']) . ")</option>";
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
                showLogoutWarning("You will be logged out due to inactivity.", "../logout.php");
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
            window.location.href = "../logout.php";
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
