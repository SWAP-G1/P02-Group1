<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile Management</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
    <script>
        // Function to fetch course details for a given class code
        async function fetchCourseDetails(inputId, courseCodeId, courseNameId) {
            const classCode = document.getElementById(inputId).value;
            if (!classCode) {
                alert("Please enter a class code.");
                return;
            }

            try {
                const response = await fetch(`fetch_course_details.php?class_code=${classCode}`);
                const data = await response.json();
                if (data.error) {
                    alert(data.error);
                    document.getElementById(courseCodeId).textContent = "N/A";
                    document.getElementById(courseNameId).textContent = "N/A";
                } else {
                    document.getElementById(courseCodeId).textContent = data.course_code;
                    document.getElementById(courseNameId).textContent = data.course_name;
                }
            } catch (err) {
                alert("Failed to fetch course details. Please try again.");
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">
            <img src="bwlogo-removebg.png" alt="XYZ Polytechnic Logo" class="school-logo">
            <h1>XYZ Polytechnic Management</h1>
        </div>
        <nav>
            <a href="#">Home</a>
            <a href="#">Logout</a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Student Profile Management</h2>
            <p>Add and view student profiles.</p>
        </div>

        <div class="card">
            <h3>Student Profile Form</h3>
            <form method="POST" action="create_stu_record.php">
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
                    <input type="text" name="diploma_code" placeholder="Enter Diploma Code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="class_code_1">Class Code 1</label>
                    <input type="text" id="class_code_1" name="class_code_1" placeholder="Enter Class Code 1" required>
                    <button type="button" onclick="fetchCourseDetails('class_code_1', 'course_code_1', 'course_name_1')">Search</button>
                    <p>Course Code: <span id="course_code_1">N/A</span></p>
                    <p>Course Name: <span id="course_name_1">N/A</span></p>
                </div>

                <!-- Class Code 2 -->
                <div class="form-group">
                    <label class="label" for="class_code_2">Class Code 2</label>
                    <input type="text" id="class_code_2" name="class_code_2" placeholder="Enter Class Code 2">
                    <button type="button" onclick="fetchCourseDetails('class_code_2', 'course_code_2', 'course_name_2')">Search</button>
                    <p>Course Code: <span id="course_code_2">N/A</span></p>
                    <p>Course Name: <span id="course_name_2">N/A</span></p>
                </div>

                <!-- Class Code 3 -->
                <div class="form-group">
                    <label class="label" for="class_code_3">Class Code 3</label>
                    <input type="text" id="class_code_3" name="class_code_3" placeholder="Enter Class Code 3">
                    <button type="button" onclick="fetchCourseDetails('class_code_3', 'course_code_3', 'course_name_3')">Search</button>
                    <p>Course Code: <span id="course_code_3">N/A</span></p>
                    <p>Course Name: <span id="course_name_3">N/A</span></p>
                </div>
                <button type="submit">Submit</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Records</h3>
            <?php
// Establish a connection to the MySQL database
$con = mysqli_connect("localhost", "admin", "admin", "xyz polytechnic");

// Check if the connection failed
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Display an error message and terminate script
}

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
foreach ($students as $student) {
    echo '<tr>';
    echo '<td>' . $student['identification_code'] . '</td>'; // Display the student ID
    echo '<td>' . $student['full_name'] . '</td>';           // Display the student's name
    echo '<td>' . $student['phone_number'] . '</td>';        // Display the student's phone number
    echo '<td>' . $student['class_code_1'] . '</td>';        // Display the first class code
    echo '<td>' . $student['class_code_2'] . '</td>';        // Display the second class code
    echo '<td>' . $student['class_code_3'] . '</td>';        // Display the third class code
    echo '<td>' . $student['diploma_code'] . '</td>';        // Display the diploma code
    echo '<td> <a href="update_stu_recordform.php?student_id=' . $student['identification_code'] . '">Edit</a> </td>'; // Edit link
    echo '<td> <a href="delete_stu_record.php?student_id=' . $student['identification_code'] . '">Delete</a> </td>'; // Delete link for selected student
    echo '</tr>';
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
