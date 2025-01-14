<html>
<body>  
<?php
session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database

// Initialize the error message
$error_message = "";

if (!$con) {
    $error_message = 'Could not connect: ' . mysqli_connect_errno();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token. Possible CSRF attack detected.');
    }
// Retrieve input from POST and validate using htmlspecialchars
    $student_name = htmlspecialchars($_POST["student_name"]);
    $phone_number = htmlspecialchars($_POST["phone_number"]);
    $student_id_code = strtoupper(htmlspecialchars($_POST["student_id_code"])); // Auto-capitalize
    $class_codes = [
        strtoupper(htmlspecialchars($_POST["class_code_1"])),
        strtoupper(htmlspecialchars($_POST["class_code_2"])),
        strtoupper(htmlspecialchars($_POST["class_code_3"])),
    ];
    $diploma_name = htmlspecialchars($_POST["diploma_name"]); // Auto-capitalize
    // Validate name: must contain only alphabets (both uppercase and lowercase) and spaces
    if (!$error_message && !preg_match('/^[a-zA-Z ]+$/', $student_name)) {
        $error_message = "Error: Student name must only contain alphabets and spaces.";
    }

    // Validate phone number: must be exactly 8 digits by using regex ^\d{8}$/
    if (!$error_message && !preg_match('/^\d{8}$/', $phone_number)) {
        $error_message = "Error: Phone number must be exactly 8 numbers.";
    }

// Automatically format email as student_id_code@student.xyz.sg using "." to concatenate
    $student_email = $student_id_code . "@student.xyz.sg";

// Validation: Ensure all fields are provided using empty()
    if (!$error_message && (empty($student_name) || empty($phone_number) || empty($student_id_code) || empty($diploma_code) ||
        empty($class_codes[0]) || empty($class_codes[1]) || empty($class_codes[2]))) {
        $error_message = "Error: All fields are required. Please ensure no field is left empty.";
    }

// Ensure all class codes are unique
    if (!$error_message && count($class_codes) !== count(array_unique($class_codes))) {
        $error_message = "Error: Class codes must not overlap. Ensure all class codes are unique.";
    }

// Ensure format and length are valid for student ID code
    $pattern_student_id = '/^\d{3}[A-Z]$/'; // 3 digits + 1 uppercase letter
    if (!$error_message && !preg_match($pattern_student_id, $student_id_code)) {
        $error_message = "Invalid ID code format for students. Ensure it is 3 digits followed by 1 uppercase letter.";
    }

// Validate class codes: 4 characters, first 2 are uppercase letters, last 2 are digits


// Validate diploma code: 3 to 4 uppercase letters
    $pattern_diploma_code = '/^[A-Z]{3,4}$/'; // 3 to 4 uppercase letters
    if (!$error_message && !preg_match($pattern_diploma_code, $diploma_code)) {
        $error_message = "Invalid diploma code format. Ensure it is 3 to 4 uppercase letters.";
    }

// Check if class codes exist
    if (!$error_message) {
        foreach ($class_codes as $class_code) {
            $stmt = $con->prepare("SELECT COUNT(*) FROM class WHERE class_code = ?");
            $stmt->bind_param('s', $class_code);
            $stmt->execute();
            $stmt->bind_result($class_exists);
            $stmt->fetch();
            $stmt->close();
            if ($class_exists == 0) {
                $error_message = "Error: Class $class_code does not exist.";
                break;
            }
        }
    }

// Check if diploma_code exists
    if (!$error_message) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
        $stmt->bind_param('s', $diploma_code);
        $stmt->execute();
        $stmt->bind_result($diploma_exists);
        $stmt->fetch();
        $stmt->close();
        if ($diploma_exists == 0) {
            $error_message = "Error: Diploma code does not exist.";
        }
    }

// Check if phone number already exists
    if (!$error_message) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ?");
        $stmt->bind_param('i', $phone_number);
        $stmt->execute();
        $stmt->bind_result($phone_exists);
        $stmt->fetch();
        $stmt->close();
        if ($phone_exists > 0) {
            $error_message = "Error: Phone number already exists.";
        }
    }

// Proceed only if there are no errors
    if (!$error_message) {
    // Begin transaction
        $con->begin_transaction();

        try {
        // Step 1: Insert into `user` table
            $stmt1 = $con->prepare("INSERT INTO `user` (identification_code, email, phone_number, full_name, role_id, password) VALUES (?, ?, ?, ?, ?, ?)");
            $role_id = 3; // Assuming role_id 3 is for students
            $password = password_hash("defaultPassword123" . $student_id_code, PASSWORD_DEFAULT); // Default hashed password: defaultPassword123 + ID code
            $stmt1->bind_param('ssisss', $student_id_code, $student_email, $phone_number, $student_name, $role_id, $password); 

            if (!$stmt1->execute()) {
                throw new Exception("Error inserting into user table: " . $stmt1->error);
            }

        // Step 2: Insert into `student` table for each class code
            foreach ($class_codes as $class_code) {
                $stmt2 = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
                $stmt2->bind_param('sss', $student_id_code, $class_code, $diploma_code);

                if (!$stmt2->execute()) {
                    throw new Exception("Error inserting into student table: " . $stmt2->error);
                }
            }

        // Commit transaction
            $con->commit();
            header("Location: faculty_create_stu_recordform.php");
            exit;
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
            $con->rollback();
        }
    }

// Close SQL connection
    $con->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if (!empty($error_message)): ?>
                <h2>Error</h2>
                <p style="color: red;"><?php echo $error_message; ?></p>
                <button onclick="window.history.back()">Back</button>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
