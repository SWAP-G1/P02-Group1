<?php
session_start();
// Validate CSRF token
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }

    // Connect to the database
    $con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
    if (!$con) {
        die('Could not connect: ' . mysqli_connect_errno());
    }
    // Retrieve form data and sanitize inputs
    $student_name = htmlspecialchars(trim($_POST["student_name"]));
    $phone_number = htmlspecialchars(trim($_POST["phone_number"]));
    $student_id_code = strtoupper(htmlspecialchars(trim($_POST["student_id_code"])));
    $class_codes = [
        strtoupper(htmlspecialchars(trim($_POST["class_code_1"]))),
        strtoupper(htmlspecialchars(trim($_POST["class_code_2"]))),
        strtoupper(htmlspecialchars(trim($_POST["class_code_3"])))
    ];
    $diploma_code = htmlspecialchars(trim($_POST["diploma_code"]));

    // Validation
    if (empty($student_name) || empty($phone_number) || empty($student_id_code) || empty($diploma_code) ||
        empty($class_codes[0]) || empty($class_codes[1]) || empty($class_codes[2])) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("All fields are required."));
        exit();
    }

    if (!preg_match('/^[a-zA-Z ]+$/', $student_name)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Student name must contain only alphabets and spaces."));
        exit();
    }

    if (!preg_match('/^\d{8}$/', $phone_number)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Phone number must be exactly 8 numbers."));
        exit();
    }

    if (!preg_match('/^\d{3}[A-Z]$/', $student_id_code)) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Invalid student ID format. It must be 3 digits followed by 1 uppercase letter."));
        exit();
    }

    if (count($class_codes) !== count(array_unique($class_codes))) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Class codes must be unique."));
        exit();
    }

    // Check for existing phone number
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ?");
    $stmt->bind_param("s", $phone_number);
    $stmt->execute();
    $stmt->bind_result($phone_exists);
    $stmt->fetch();
    $stmt->close();

    if ($phone_exists > 0) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Phone number already exists."));
        exit();
    }

    // Check for existing student ID
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE identification_code = ?");
    $stmt->bind_param("s", $student_id_code);
    $stmt->execute();
    $stmt->bind_result($id_exists);
    $stmt->fetch();
    $stmt->close();

    if ($id_exists > 0) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Student ID code already exists."));
        exit();
    }

    // Check if all class codes exist in the `class` table
    foreach ($class_codes as $class_code) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM class WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $stmt->bind_result($class_exists);
        $stmt->fetch();
        $stmt->close();

        if ($class_exists == 0) {
            header("Location: admin_create_stu_recordform.php?error=" . urlencode("Class $class_code does not exist."));
            exit();
        }
    }

    // Check if diploma code exists
    $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
    $stmt->bind_param("s", $diploma_code);
    $stmt->execute();
    $stmt->bind_result($diploma_exists);
    $stmt->fetch();
    $stmt->close();

    if ($diploma_exists == 0) {
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Diploma code does not exist."));
        exit();
    }

    // Insert data into the database
    $con->begin_transaction();

    $success = true;

    // Step 1: Insert into `user` table
    $stmt = $con->prepare("INSERT INTO `user` (identification_code, email, phone_number, full_name, role_id, password) VALUES (?, ?, ?, ?, ?, ?)");
    $role_id = 3;
    $default_password = password_hash("xyzpassword123!" . $student_id_code, PASSWORD_DEFAULT);
    $email = $student_id_code . "@gmail.com";
    $stmt->bind_param("ssissi", $student_id_code, $email, $phone_number, $student_name, $role_id, $default_password);

    if (!$stmt->execute()) {
        $success = false;
        $con->rollback();
        header("Location: admin_create_stu_recordform.php?error=" . urlencode("Error inserting into `user` table: " . $stmt->error));
        exit();
    }
    $stmt->close();

    // Step 2: Insert into `student` table
    foreach ($class_codes as $class_code) {
        $stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $student_id_code, $class_code, $diploma_code);

        if (!$stmt->execute()) {
            $success = false;
            $con->rollback();
            header("Location: admin_create_stu_recordform.php?error=" . urlencode("Error inserting into `student` table: " . $stmt->error));
            exit();
        }
        $stmt->close();
    }

    if ($success) {
        $con->commit();
        header("Location: admin_create_stu_recordform.php?success=1");
        exit();
    }
}
?>
