<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
$error_message = "";

if (!$con) {
    $error_message = 'Could not connect: ' . mysqli_connect_errno();
}
// Generate CSRF token if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: testlogin.php");
    exit();
}
$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";
$class_query = "SELECT class_code FROM class";
$class_result = mysqli_query($con, $class_query);

// Fetch all class codes into an array
$class_codes = [];
if ($class_result && mysqli_num_rows($class_result) > 0) {
    while ($row = mysqli_fetch_assoc($class_result)) {
        $class_codes[] = $row['class_code'];
    }
}
$diploma_query = "SELECT diploma_code FROM diploma";
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
            <a href="admin_dashboard.php">Home</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student Profile Management</h2>
            <p>Add and view student profiles.</p>
        </div>

        <div class="card">
            <h3>Student Profile Form</h3>
            <form method="POST" action="admin_create_stu_record.php">
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
                    <input type="text" name="student_id_code" placeholder="Enter Student ID Code" maxlength="4" required>
                </div>
                <div class="form-group">
                <label class="label" for="diploma_code">Diploma Code</label>
                <select name="diploma_code" required>
                        <option value="" disabled selected>Select a Diploma Code</option>
                        <?php
                        if ($diploma_result && mysqli_num_rows($diploma_result) > 0) {
                            while ($row = mysqli_fetch_assoc($diploma_result)) {
                                echo "<option value='" . htmlspecialchars($row['diploma_code']) . "'>" . htmlspecialchars($row['diploma_code']) . "</option>";
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
                    foreach ($class_codes as $class_code) {
                        echo "<option value='" . htmlspecialchars($class_code) . "'>" . htmlspecialchars($class_code) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label class="label" for="class_code_2">Class Code 2</label>
                <select name="class_code_2" required>
                    <option value="" disabled selected>Select a Class Code</option>
                    <?php
                    foreach ($class_codes as $class_code) {
                        echo "<option value='" . htmlspecialchars($class_code) . "'>" . htmlspecialchars($class_code) . "</option>";
                    }
                    ?>
                </select>
            </div>

                <!-- Class Code 3 -->
                <div class="form-group">
            <label class="label" for="class_code_3">Class Code 3</label>
                <select name="class_code_3" required>
                    <option value="" disabled selected>Select a Class Code</option>
                    <?php
                    foreach ($class_codes as $class_code) {
                        echo "<option value='" . htmlspecialchars($class_code) . "'>" . htmlspecialchars($class_code) . "</option>";
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
            <?php

// Prepare the SQL query to fetch student details and their associated class codes
$stmt = $con->prepare("
    SELECT 
        u.identification_code,       -- Fetch the unique ID  from user table
        u.full_name,                 -- Fetch the full name from user table
        u.phone_number,              -- Fetch the phone number from user table
        s.class_code,                -- Fetch the class code from student table
        s.diploma_code               -- Fetch the diploma code from user table
    FROM 
        user u                       -- The 'user' table stores user information
    JOIN 
        student s ON u.identification_code = s.identification_code
        -- Join 'user' and 'student' tables to combine student details with their class records
");

// Execute the prepared query
$stmt->execute();

// Retrieve the results of the executed query
$result = $stmt->get_result();

// Initialize an empty array to organize student data
$students = [];

// Iterate through each row of the result set
while ($row = $result->fetch_assoc()) {
    $student_id = $row['identification_code']; // Get the unique ID for the current student

    // Check if this student is already in the array
    if (!isset($students[$student_id])) {
        // If not, initialize their record in the array to continue with processing
        $students[$student_id] = [
            'identification_code' => $row['identification_code'], // Store the student ID
            'full_name' => $row['full_name'],                     // Store the student's name
            'phone_number' => $row['phone_number'],               // Store the student's phone number
            'diploma_code' => $row['diploma_code'],               // Store the diploma code
            'class_code_1' => null,                               // Initialize the first class code as null
            'class_code_2' => null,                               // Initialize the second class code as null
            'class_code_3' => null,                               // Initialize the third class code as null
        ];
    }

    // Assign the class code to the first available slot
if (!$students[$student_id]['class_code_1']) {
        // Check if the first class code slot (class_code_1) for the student is empty.
        // If it is empty, assign the current class_code from the database row to this slot.
        $students[$student_id]['class_code_1'] = $row['class_code']; // Fill the first class code slot
    } elseif (!$students[$student_id]['class_code_2']) {
        // If the first slot is already filled, check if the second class code slot (class_code_2) is empty.
        // If it is empty, assign the current class_code from the database row to this slot.
        $students[$student_id]['class_code_2'] = $row['class_code']; // Fill the second class code slot
    } elseif (!$students[$student_id]['class_code_3']) {
        // If both the first and second slots are already filled, check if the third class code slot (class_code_3) is empty.
        // If it is empty, assign the current class_code from the database row to this slot.
        $students[$student_id]['class_code_3'] = $row['class_code']; // Fill the third class code slot
    }
}
// Start the HTML table to display student records
echo '<table border="1" bgcolor="white" align="center">';
echo '<tr>
        <th>Student ID</th>        
        <th>Name</th>             
        <th>Phone Number</th>    
        <th>Class Code 1</th>        
        <th>Class Code 2</th>        
        <th>Class Code 3</th>        
        <th>Diploma Code</th>        
        <th colspan="2">Operations</th> 
    </tr>';

// Loop through each student record in the organized array
$stu_id_pattern = '/^\d{3}[A-Z]$/';

foreach ($students as $student_id => $student) {
    if (preg_match($stu_id_pattern, $student['identification_code'])) { //ensures only the students with the matching id format is displayed and exclude admins and faculty
        echo '<tr>';
        echo '<td>' . $student['identification_code'] . '</td>';
        echo '<td>' . $student['full_name'] . '</td>';
        echo '<td>' . $student['phone_number'] . '</td>';
        echo '<td>' . $student['class_code_1'] . '</td>';
        echo '<td>' . $student['class_code_2'] . '</td>';
        echo '<td>' . $student['class_code_3'] . '</td>';
        echo '<td>' . $student['diploma_code'] . '</td>';
        echo '<td> <a href="admin_update_stu_recordform.php?student_id=' . $student['identification_code'] . '">Edit</a> </td>';
        echo '<td>
                <form action="admin_delete_stu_record.php" method="POST" style="display:inline;">
                    <input type="hidden" name="student_id" value="' . $student['identification_code'] . '">
                    <input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">
                    <button type="submit" onclick="return confirm(\'Are you sure you want to delete this student?\')">Delete</button>
                </form>
              </td>';
        echo '</tr>';
    }
}


// End the HTML table
echo '</table>';

// Close the database connection
$con->close();
?>

        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

</body>
</html>
