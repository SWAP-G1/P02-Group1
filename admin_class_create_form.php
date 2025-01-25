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

// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: hamizanlogin.php");
    exit();
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Fetch course codes for the dropdown
$course_query = "SELECT course_code FROM course";
$course_result = mysqli_query($con, $course_query);

// Fetch faculty for the dropdown (role_id = 2)
$faculty_query = "SELECT identification_code, full_name FROM user WHERE role_id = 2";
$faculty_result = mysqli_query($con, $faculty_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Management</title>
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
            <a href="admin_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Add, update, and organize class records.</p>
            <?php
            // If ?success=1 is set in the URL, display a success message
            if (isset($_GET['success']) && $_GET['success'] == 1) {
                echo '<div id="message" style="color: green; font-weight: bold;">Class created successfully.</div>';
            }            

            // If ?success=2 is set in the URL, display an update success message
            if (isset($_GET['success']) && $_GET['success'] == 2) {
                echo '<div id="message" style="color: green; font-weight: bold;">Class updated successfully.</div>';
            }

            // If ?success=3 is set in the URL, display a delete message
            if (isset($_GET['success']) && $_GET['success'] == 3) {
                echo '<div id="message" style="color: green; font-weight: bold;">Class deleted successfully.</div>';
            }

            // Check if an error parameter was passed
            if (isset($_GET['error'])) {
                echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
        </div>

        <div class="card">
            <h3>Class Details</h3>
            <form method="POST" action="admin_class_create.php">
                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="class_code" placeholder="Enter Class code" required >
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code" required>
                        <option value="" disabled selected>Select a Course Code</option>
                        <?php
                        if ($course_result && mysqli_num_rows($course_result) > 0) {
                            while ($row = mysqli_fetch_assoc($course_result)) {
                                // course_code is printed twice: once for the 'value' attribute and once for the visible text.
                                echo "<option value='" . htmlspecialchars($row['course_code']) . "'>" . htmlspecialchars($row['course_code']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="class_type" required>
                        <option value="" disabled selected>Select a Class Type</option>
                        <option value="Semester">Semester</option>
                        <option value="Term">Term</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="faculty_identification_code">Assigned Faculty</label>
                    <select name="faculty_identification_code" required>
                        <option value="" disabled selected>Select a Faculty</option>
                        <?php
                        if ($faculty_result && mysqli_num_rows($faculty_result) > 0) {
                            while ($row = mysqli_fetch_assoc($faculty_result)) {
                                // In this case, identification_code is for the 'value' attribute and full_name is for the visible text in the dropdown.
                                echo "<option value='" . htmlspecialchars($row['identification_code']) . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Class Records</h3>
            <button id="scrollToTop" class="button" onclick="scroll_to_top()"><img src="scrollup.png" alt="Scroll to top"></button>
            <?php
            // Fetch faculty mapping
            $faculty_map_query = "SELECT identification_code, full_name FROM user WHERE role_id = 2";
            $faculty_map_result = mysqli_query($con, $faculty_map_query);

            // Initialize an empty array to store a mapping of faculty identification codes to their full names.
            $faculty_map = [];
            if ($faculty_map_result && mysqli_num_rows($faculty_map_result) > 0) {
                while ($row = mysqli_fetch_assoc($faculty_map_result)) {
                    // Adds the faculty identification code as the key and full name as the value in the mapping array.
                    $faculty_map[$row['identification_code']] = $row['full_name'];
                }
            }

            // Fetch class records
            $class_query = "SELECT * FROM class";
            $class_result = mysqli_query($con, $class_query);

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Class Code</th><th>Course Code</th><th>Class Type</th><th>Assigned Faculty</th><th colspan="2">Operations</th></tr>';

            // Loop through each row from the result set of the class table and is fetched as an array.
            while ($class_row = mysqli_fetch_assoc($class_result)) {
                // Checks if the faculty_identification_code in the current class row exists in the $faculty_map array
                // If it exists, retrieve the corresponding faculty name
                $faculty_name = isset($faculty_map[$class_row['faculty_identification_code']])
                    ? $faculty_map[$class_row['faculty_identification_code']]
                    : "Unknown Faculty";

                echo '<tr>';
                echo '<td>' . htmlspecialchars($class_row['class_code']) . '</td>';
                echo '<td>' . htmlspecialchars($class_row['course_code']) . '</td>';
                echo '<td>' . htmlspecialchars($class_row['class_type']) . '</td>';
                echo '<td>' . htmlspecialchars($faculty_name) . '</td>';
                echo '<td> <a href="admin_class_update_form.php?class_code=' . htmlspecialchars($class_row['class_code']) . '">Edit</a> </td>';
                echo '<td> <a href="admin_class_delete.php?class_code=' . htmlspecialchars($class_row['class_code']) . '&csrf_token=' . $_SESSION['csrf_token'] . '">Delete</a> </td>';
                echo '</tr>';
            }
            echo '</table>';
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
