<html>
<body>
<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database

// Initialize the error message
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }
    // Retrieve and sanitize inputs
    // Retrieve and sanitize inputs
    $upd_student_name = htmlspecialchars($_POST["upd_student_name"]); // Updated student name
    $upd_phone_number = htmlspecialchars($_POST["upd_phone_number"]); // Updated phone number
    $upd_diploma_code = strtoupper(htmlspecialchars($_POST["upd_diploma_code"])); // Updated diploma code
    $upd_class_codes = [
        strtoupper(htmlspecialchars($_POST["upd_class_code_1"] ?? '')),
        strtoupper(htmlspecialchars($_POST["upd_class_code_2"] ?? '')),
        strtoupper(htmlspecialchars($_POST["upd_class_code_3"] ?? '')),
    ];
    $student_id_code = strtoupper(htmlspecialchars($_GET["student_id"])); // Get the student ID from the query string

    // Validate phone number: must be exactly 8 numbers
    if (!preg_match('/^\d{8}$/', $upd_phone_number)) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Phone number must be exactly 8 numbers.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Validate student name: must contain only alphabets and spaces
    if (!preg_match('/^[a-zA-Z ]+$/', $upd_student_name)) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Student name must only contain alphabets and spaces.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Validate student ID: must be 3 digits followed by an uppercase letter
    $pattern_student_id = '/^\d{3}[A-Z]$/';
    if (!preg_match($pattern_student_id, $student_id_code)) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Invalid student ID format. It must be 3 digits followed by an uppercase letter.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Validate class codes: 4 characters, first 2 are uppercase letters, last 2 are digits
    $pattern_class_code = '/^[A-Z]{2}\d{2}$/';
    foreach ($upd_class_codes as $class_code) {
        if ($class_code && !preg_match($pattern_class_code, $class_code)) {
            header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Invalid class code format. Each must be 2 uppercase letters followed by 2 digits.") . "&student_id=" . urlencode($student_id_code));
            exit();
        }
    }

    // Validate diploma code: must be 3-4 uppercase letters
    $pattern_diploma_code = '/^[A-Z]{3,4}$/';
    if (!preg_match($pattern_diploma_code, $upd_diploma_code)) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Invalid diploma code format. It must be 3-4 uppercase letters.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Ensure all class codes are unique
    if (count(array_filter($upd_class_codes)) !== count(array_unique(array_filter($upd_class_codes)))) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Class codes must not overlap. Ensure all class codes are unique.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Check if class codes exist in the database
    foreach ($upd_class_codes as $class_code) {
        if ($class_code) {
            $stmt = $con->prepare("SELECT COUNT(*) FROM class WHERE class_code = ?");
            $stmt->bind_param('s', $class_code);
            $stmt->execute();
            $stmt->bind_result($class_exists);
            $stmt->fetch();
            $stmt->close();

            if ($class_exists == 0) {
                header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Class $class_code does not exist.") . "&student_id=" . urlencode($student_id_code));
                exit();
            }
        }
    }

    // Check if diploma code exists in the database
    $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
    $stmt->bind_param('s', $upd_diploma_code);
    $stmt->execute();
    $stmt->bind_result($diploma_exists);
    $stmt->fetch();
    $stmt->close();

    if ($diploma_exists == 0) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Diploma code does not exist.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Check if phone number already exists for another user
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ? AND identification_code != ?");
    $stmt->bind_param('ss', $upd_phone_number, $student_id_code);
    $stmt->execute();
    $stmt->bind_result($phone_exists);
    $stmt->fetch();
    $stmt->close();

    if ($phone_exists > 0) {
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Phone number already exists.") . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    // Proceed to update the record
    $con->begin_transaction(); // Start a transaction

    $upd_email = $student_id_code . "@student.xyz.sg"; // Generate email based on student ID
    $query_user = $con->prepare("UPDATE user SET full_name=?, phone_number=?, email=? WHERE identification_code=?");
    $query_user->bind_param('ssss', $upd_student_name, $upd_phone_number, $upd_email, $student_id_code);

    if (!$query_user->execute()) {
        $con->rollback();
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Error updating user table: " . $query_user->error));
        exit();
    }

    // Clear old class codes and reinsert updated ones
    $clear_classes_stmt = $con->prepare("DELETE FROM student WHERE identification_code = ?");
    $clear_classes_stmt->bind_param('s', $student_id_code);

    if (!$clear_classes_stmt->execute()) {
        $con->rollback();
        header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Error clearing old class codes: " . $clear_classes_stmt->error) . "&student_id=" . urlencode($student_id_code));
        exit();
    }

    foreach ($upd_class_codes as $class_code) {
        if ($class_code) {
            $insert_class_stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
            $insert_class_stmt->bind_param('sss', $student_id_code, $class_code, $upd_diploma_code);

            if (!$insert_class_stmt->execute()) {
                $con->rollback();
                header("Location: faculty_update_stu_recordform.php?error=" . urlencode("Error inserting updated class codes: " . $insert_class_stmt->error));
                exit();
            }
        }
    }

    $con->commit(); // Commit the transaction
    header("Location: faculty_create_stu_recordform.php?success=2");
    exit();
}
?>
