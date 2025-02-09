<html>
<body>
<?php
session_start();
//check request method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify if CSRF token matches
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
    // Initialize the error message
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }
    // Retrieve and sanitize inputs to prevent SQL and XSS scripting
    $upd_student_id_code = strtoupper(htmlspecialchars($_POST["upd_student_id_code"]));
    $upd_student_name = strtoupper(htmlspecialchars($_POST["upd_student_name"])); // Updated student name
    $upd_phone_number = htmlspecialchars($_POST["upd_phone_number"]); // Updated phone number
    $upd_diploma_code = strtoupper(htmlspecialchars($_POST["upd_diploma_code"])); // Updated diploma code
    $upd_class_codes = [
        strtoupper(htmlspecialchars($_POST["upd_class_code_1"] ?? '')),
        strtoupper(htmlspecialchars($_POST["upd_class_code_2"] ?? '')),
        strtoupper(htmlspecialchars($_POST["upd_class_code_3"] ?? '')),
    ];
    $student_id_code = strtoupper(htmlspecialchars($_GET["student_id"])); // Get the student ID from the query string
    if (empty($upd_student_name) || empty($upd_student_id_code) || empty($upd_phone_number) || empty($upd_diploma_code) ) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("All fields are required and at least one class must be assigned.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }
    // Validate phone number: must be exactly 8 numbers
    if (!preg_match('/^\d{8}$/', $upd_phone_number)) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Phone number must be exactly 8 numbers.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Validate student name: must contain only alphabets (lowercase/ uppsercase) and spaces
    if (!preg_match('/^[a-zA-Z ]+$/', $upd_student_name)) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Student name must only contain alphabets and spaces.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }
    // Validate student ID: must be "S" followed by 3 numerical digits 
    $pattern_student_id = '/^S\d{3}$/';
    if (!preg_match($pattern_student_id, $upd_student_id_code)) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Invalid Student ID format. It must start with letter 'S' followed by 3 numbers.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // create empty array to store class codes associated with student
    $existing_classes = [];
    //prepared statement to select all class codes from student table according to selected identification code
    $stmt = $con->prepare("SELECT class_code FROM student WHERE identification_code = ?");
    //binds the student_id to SQL query
    $stmt->bind_param('s', $student_id_code);
    $stmt->execute();
    //retrieve results
    $result = $stmt->get_result();
    // adds class code value to array from results
    while ($row = $result->fetch_assoc()) {
        $existing_classes[] = $row['class_code'];
    }
    // close prepared statement
    $stmt->close();

    // Validate class codes: 4 characters, first 2 are uppercase alphabets, last 2 are numerical digits
    // Check if class codes exist in the database, but skip validation for "NULL", loops through each class code
    foreach ($upd_class_codes as $class_code) {
        if (!empty($class_code)) {
            // Validate class code format
            if (!preg_match('/^[A-Z]{2}\d{2}$/', $class_code)) {
                header("Location: admin_update_stu_recordform.php?error=" . urlencode("Invalid class code format for Class Code. Each must be 2 uppercase letters followed by 2 digits.") . "&student_id=" . urlencode($student_id_code));
                exit();
            }
            //join the class and course table to fetch corresponding course status of the class
            $stmt = $con->prepare("SELECT co.status FROM class c 
                                JOIN course co ON c.course_code = co.course_code 
                                WHERE c.class_code = ?");
            $stmt->bind_param('s', $class_code);
            $stmt->execute();
            $stmt->bind_result($course_status);
            $stmt->fetch();
            $stmt->close();

            // Reject the class if its assigned course does not have a "No Status" status + not assigned to the student
            if ($course_status !== "No Status" && !in_array($class_code, $existing_classes)) {
                header("Location: admin_update_stu_recordform.php?error=" . urlencode("Class $class_code cannot be assigned because the course has a status assigned to it.") . "&student_id=" . urlencode($student_id_code));
                exit();
            }
        }
    }


    // Validate diploma code: must be 3-4 uppercase letters
    $pattern_diploma_code = '/^[A-Z]{3,4}$/';
    if (!preg_match($pattern_diploma_code, $upd_diploma_code)) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Invalid diploma code format. It must be 3-4 uppercase letters.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Check if diploma code exists in the database by counting the number of records that match given diploma code
    $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
    $stmt->bind_param('s', $upd_diploma_code);
    $stmt->execute();
    $stmt->bind_result($diploma_exists);
    $stmt->fetch();
    $stmt->close();
    //reject if diploma code does not exist in the database
    if ($diploma_exists == 0) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Diploma code does not exist.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Check if phone number already exists for another user with different identification code
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ? AND identification_code != ?");
    $stmt->bind_param('ss', $upd_phone_number, $student_id_code);
    $stmt->execute();
    //bind result to variable $phone exists
    $stmt->bind_result($phone_exists);
    $stmt->fetch();
    $stmt->close();

    if ($phone_exists > 0) {
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Phone number already exists.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }
    //non-null class codes removes empty class code  values using array_filter
    $non_null_class_codes = array_filter($upd_class_codes);
    if (count($non_null_class_codes) !== count(array_unique($non_null_class_codes))) {
        //removes duplicate lines using aray_unique
        // if the  count of class codes that are not null is different from the unique filtered array, it will reject it as array_unique will remove duplicates
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Ensure that all classes are unique.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Only check for duplicate if the updated student ID code is different from the original
    if ($upd_student_id_code !== $student_id_code) {
        $id_stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE identification_code = ?");
        $id_stmt->bind_param("s", $upd_student_id_code);
        $id_stmt->execute();
        $id_stmt->bind_result($id_exists);
        $id_stmt->fetch();
        $id_stmt->close();
        if ($id_exists > 0) {
            header("Location: admin_update_stu_recordform.php?error=" . urlencode("Student ID code already exists.") . "&student_id=" . urlencode($student_id_code));
            exit();
        }
    }
    // Proceed to update the record by starting db transaction
    $con->begin_transaction(); 

    $upd_email = $student_id_code . "@gmail.com"; // Generate email based on student ID by concatenating the ID and @gmail.com
    $query_user = $con->prepare("UPDATE user SET full_name=?, identification_code=?, phone_number=?, email=? WHERE identification_code=?");
    $query_user->bind_param('sssss', $upd_student_name, $upd_student_id_code, $upd_phone_number, $upd_email, $student_id_code);

    if (!$query_user->execute()) {
        // rollback if update fails
        $con->rollback();
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Error updating user table: " . $query_user->error));
        exit();
    }

    // Clear old class codes and reinsert updated ones using sql statement using updated student id as the student id is already updated
    $clear_classes_stmt = $con->prepare("DELETE FROM student WHERE identification_code = ?");
    $clear_classes_stmt->bind_param('s', $upd_student_id_code);

    if (!$clear_classes_stmt->execute()) {
        $con->rollback();
        header("Location: admin_update_stu_recordform.php?error=" . urlencode("Error clearing old class codes: " . $clear_classes_stmt->error) . "&student_id=" . urlencode($student_id_code));
        exit();
    }
    //hold all non-NULL class codes
    $valid_class_codes = [];
    //hold NULL class codes
    $null_class_codes = [];

    //for every updated class code
    foreach ($upd_class_codes as $class_code) {
        if (!empty($class_code)) {
            //add class code to valid_class_codes array
            $valid_class_codes[] = $class_code;
        } else {
            //add class code to null_class_codes array if class code is empty
            $null_class_codes[] = null;
        }
    }
    //for every non-NULL class code
    foreach ($valid_class_codes as $class_code) {
        //new record is inserted into student table using all updated data
        $stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $upd_student_id_code, $class_code, $upd_diploma_code);
        
        if (!$stmt->execute()) {
            $con->rollback();
            header("Location: admin_create_stu_recordform.php?error=" . urlencode("Error inserting into `student` table: " . $stmt->error));
            exit();
        }
        $stmt->close();
    }
    // Insert rows for NULL class codes (if any)
    foreach ($null_class_codes as $null_class_code) {
        $stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, NULL, ?)");
        $stmt->bind_param("ss", $upd_student_id_code, $upd_diploma_code);
        if (!$stmt->execute()) {
            $con->rollback();
            header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Error inserting into `student` table: " . $stmt->error));
            exit();
        }
        $stmt->close();
    }

    $con->commit(); // Commit the transaction
            // Regenerate CSRF token after form submission
            unset($_SESSION['csrf_token']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: admin_create_stu_recordform.php?success=2");
    exit();
}
?>