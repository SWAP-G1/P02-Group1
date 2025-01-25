<?php
session_start(); // Start the session

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

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Generate CSRF token 
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: testlogin.php");
    exit();
}

// Check if class_code is set and not empty
if (!isset($_GET["class_code"]) || empty($_GET["class_code"])) {
    // Redirect to another page or show an error
    header("Location: admin_class_create_form.php"); // Redirect to class_create_form.php
    exit(); // Stop further execution
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch course codes for the dropdown
$course_query = "SELECT course_code FROM course";
$course_codes_result = mysqli_query($con, $course_query);

// Fetch faculty for the dropdown (role_id = 2)
$faculty_query = "SELECT identification_code, full_name FROM user WHERE role_id = 2";
$faculty_result = mysqli_query($con, $faculty_query);

// Catch the submitted class_code to fetch data
$edit_classcode = htmlspecialchars($_GET["class_code"]);

// Prepare the statement
$stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
$stmt->bind_param('s', $edit_classcode);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the data (assuming one row per class_code)
$class_row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Class</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1> XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="admin_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Update class records.</p>
            <?php
            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" style="color: green; font-weight: bold;">Class updated successfully.</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Update Class Details</h3>
            <form method="POST" action="admin_class_update.php">
                <input type="hidden" name="original_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="upd_classcode" value="<?php echo htmlspecialchars($class_row['class_code']); ?>" placeholder="Enter class code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="upd_coursecode" required>
                        <option value="" disabled>Select a Course Code</option>
                        <?php
                        if ($course_codes_result && mysqli_num_rows($course_codes_result) > 0) {
                            while ($course_row = mysqli_fetch_assoc($course_codes_result)) {
                                // If the current course_code matches the class's course_code, assign 'selected' to $selected.
                                // Otherwise, an empty string is assigned $selected.
                                $selected = ($course_row['course_code'] === $class_row['course_code']) ? 'selected' : '';
                                // The 'value' attribute and visible text are both set to the course_code.
                                echo "<option value='" . htmlspecialchars($course_row['course_code']) . "' $selected>" . htmlspecialchars($course_row['course_code']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="upd_classtype" required>
                        <option value="" disabled>Select Class Type</option>
                        <option value="Semester" <?php echo ($class_row['class_type'] === 'Semester') ? 'selected' : ''; ?>>Semester</option>
                        <option value="Term" <?php echo ($class_row['class_type'] === 'Term') ? 'selected' : ''; ?>>Term</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="faculty_identification_code">Assigned Faculty</label>
                    <select name="upd_facultycode" required>
                        <option value="" disabled>Select a Faculty</option>
                        <?php
                        if ($faculty_result && mysqli_num_rows($faculty_result) > 0) {
                            while ($faculty_row = mysqli_fetch_assoc($faculty_result)) {
                                // Adds the faculty identification code as the key and full name as the value in selected.
                                $selected = ($faculty_row['identification_code'] === $class_row['faculty_identification_code']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($faculty_row['identification_code']) . "' $selected>" . htmlspecialchars($faculty_row['full_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <button type="submit">Update Class</button>
            </form>
        </div>
    </div>
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
        setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
        }, 10000);

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
