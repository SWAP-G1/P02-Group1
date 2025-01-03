<?php
session_start(); // Start the session

$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 2) {
    // Redirect to login page if the user is not logged in or not a faculty
    header("Location: testlogin.php");
    exit();
}

// Check if class_code is set and not empty
if (!isset($_GET["class_code"]) || empty($_GET["class_code"])) {
    // Redirect to another page or show an error
    header("Location: faculty_class_create_form.php"); // Redirect to faculty_class_create_form.php
    exit(); // Stop further execution
}

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Catch the submitted class_code to fetch data
$edit_classcode = htmlspecialchars($_GET["class_code"]);

// Prepare the statement
$stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");
$stmt->bind_param('s', $edit_classcode);
$stmt->execute();
$result = $stmt->get_result();
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
            <a href="#">Home</a>
            <a href="logout.php">Logout</a>
            <a><?php echo htmlspecialchars($full_name); ?></a>
        </nav>
    </div>

    <div class="container">
        <div class="card">
            <h2>Class Management</h2>
            <p>Add, update, and organize class records.</p>
        </div>

        <div class="card">
            <h3>Update Class Details</h3>
            <form method="POST" action="faculty_class_update.php">
                <?php
                // Fetch the data (assuming one row per class_code)
                $row = $result->fetch_assoc();
                ?>

                <input type="hidden" name="original_classcode" value="<?php echo $row['class_code']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label" for="class_code">Class Code</label>
                    <input type="text" name="upd_classcode" value="<?php echo $row['class_code']; ?>" placeholder="Enter class code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="upd_coursecode" value="<?php echo $row['course_code']; ?>" placeholder="Enter Course Code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="class_type">Class Type</label>
                    <select name="upd_classtype" required>
                        <option value="" disabled <?php echo ($row['class_type'] === '') ? 'selected' : ''; ?>>Select Class Type</option>
                        <option value="Semester" <?php echo ($row['class_type'] === 'Semester') ? 'selected' : ''; ?>>Semester</option>
                        <option value="Term" <?php echo ($row['class_type'] === 'Term') ? 'selected' : ''; ?>>Term</option>
                    </select>
                </div>
                <button type="submit">Update Class</button>
            </form>
        </div>
    </div>

</body>
</html>
