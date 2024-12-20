<html>
<body>
<?php
// Connect to the database
$con = mysqli_connect("localhost", "admin", "admin", "xyz polytechnic");

if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Retrieve and sanitize inputs
$upd_student_name = htmlspecialchars($_POST["upd_student_name"]); // Updated student name
$upd_phone_number = htmlspecialchars($_POST["upd_phone_number"]); // Updated phone number
$upd_diploma_code = strtoupper(htmlspecialchars($_POST["upd_diploma_code"])); // Updated diploma code
$upd_class_codes = [
    strtoupper(htmlspecialchars($_POST["upd_class_code_1"] ?? '')),
    strtoupper(htmlspecialchars($_POST["upd_class_code_2"] ?? '')),
    strtoupper(htmlspecialchars($_POST["upd_class_code_3"] ?? '')),
];
$upd_student_id_code = strtoupper(htmlspecialchars($_GET["student_id"])); // Get the student ID from the query string

// Initialize error message
$error_message = "";

// Validate phone number: must be exactly 8 digits
if (!$error_message && !preg_match('/^\d{8}$/', $upd_phone_number)) {
    $error_message = "Error: Phone number must be exactly 8 digits.";
}

// Validate student name: must contain only alphabets and spaces
if (!$error_message && !preg_match('/^[a-zA-Z ]+$/', $upd_student_name)) {
    $error_message = "Error: Student name must only contain alphabets and spaces.";
}

// Validate student ID: must be 3 digits followed by an uppercase letter
$pattern_student_id = '/^\d{3}[A-Z]$/';
if (!$error_message && !preg_match($pattern_student_id, $upd_student_id_code)) {
    $error_message = "Error: Invalid student ID format. It must be 3 digits followed by an uppercase letter.";
}

// Validate class codes: 4 characters, first 2 are uppercase letters, last 2 are digits
$pattern_class_code = '/^[A-Z]{2}\d{2}$/';
if (!$error_message) {
    foreach ($upd_class_codes as $class_code) {
        if ($class_code && !preg_match($pattern_class_code, $class_code)) {
            $error_message = "Error: Invalid class code format. Each must be 2 uppercase letters followed by 2 digits.";
            break;
        }
    }
}

// Validate diploma code: must be 3-4 uppercase letters
$pattern_diploma_code = '/^[A-Z]{3,4}$/';
if (!$error_message && !preg_match($pattern_diploma_code, $upd_diploma_code)) {
    $error_message = "Error: Invalid diploma code format. It must be 3-4 uppercase letters.";
}

// Ensure all class codes are unique
if (!$error_message && count(array_filter($upd_class_codes)) !== count(array_unique(array_filter($upd_class_codes)))) {
    $error_message = "Error: Class codes must not overlap. Ensure all class codes are unique.";
}

// Check if class codes exist in the database
if (!$error_message) {
    foreach ($upd_class_codes as $class_code) {
        if ($class_code) {
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
}

// Check if diploma code exists in the database
if (!$error_message) {
    $stmt = $con->prepare("SELECT COUNT(*) FROM diploma WHERE diploma_code = ?");
    $stmt->bind_param('s', $upd_diploma_code);
    $stmt->execute();
    $stmt->bind_result($diploma_exists);
    $stmt->fetch();
    $stmt->close();

    if ($diploma_exists == 0) {
        $error_message = "Error: Diploma code does not exist.";
    }
}

// Check if phone number already exists for another user
if (!$error_message) {
    $stmt = $con->prepare("SELECT COUNT(*) FROM user WHERE phone_number = ? AND identification_code != ?");
    $stmt->bind_param('is', $upd_phone_number, $upd_student_id_code);
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
    $con->begin_transaction(); // Start a transaction

    try {
        // Update the `user` table
        $upd_email = $upd_student_id_code . "@student.xyz.sg"; // Generate email based on student ID
        $query_user = $con->prepare("UPDATE user SET full_name=?, phone_number=?, email=? WHERE identification_code=?");
        $query_user->bind_param('siss', $upd_student_name, $upd_phone_number, $upd_email, $upd_student_id_code);

        if (!$query_user->execute()) {
            throw new Exception("Error updating user table: " . $query_user->error);
        }

        // Clear old class codes from the `student` table
        $clear_classes_stmt = $con->prepare("DELETE FROM student WHERE identification_code = ?");
        $clear_classes_stmt->bind_param('s', $upd_student_id_code);
        if (!$clear_classes_stmt->execute()) {
            throw new Exception("Error clearing old class codes: " . $clear_classes_stmt->error);
        }

        // Reinsert updated class codes into the `student` table
        foreach ($upd_class_codes as $class_code) {
            if ($class_code) {
                $insert_class_stmt = $con->prepare("INSERT INTO student (identification_code, class_code, diploma_code) VALUES (?, ?, ?)");
                $insert_class_stmt->bind_param('sss', $upd_student_id_code, $class_code, $upd_diploma_code);

                if (!$insert_class_stmt->execute()) {
                    throw new Exception("Error inserting updated class codes: " . $insert_class_stmt->error);
                }
            }
        }

        $con->commit(); // Commit the transaction
        header("Location: create_stu_recordform.php"); // Redirect to the profile view page
        exit;
    } catch (Exception $e) {
        $error_message = $e->getMessage(); // Capture the exception message
        $con->rollback(); // Roll back the transaction
    }
}

// Close the database connection
$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
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
