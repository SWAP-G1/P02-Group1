<?php
session_start(); // Start the session

$con = mysqli_connect("localhost","root","","xyz polytechnic"); // Connect to database
if (!$con){
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Generate CSRF token 
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


// Check if the user is logged in and has the correct role
if (!isset($_SESSION['session_role']) || $_SESSION['session_role'] != 1) {
    // Redirect to login page if the user is not logged in or not an admin
    header("Location: testlogin.php");
    exit();
}

// Check if course_code is set and not empty
if (!isset($_GET["course_code"]) || empty($_GET["course_code"])) {
    // Redirect to another page or show an error
    header("Location: admin_course_create_form.php"); // Redirect to admin_course_create_form.php
    exit(); // Stop further execution
} 

$full_name = isset($_SESSION['session_full_name']) ? $_SESSION['session_full_name'] : "";

// Catch the submitted course_code to fetch data
$edit_coursecode = htmlspecialchars($_GET["course_code"]);

// Prepare the statement
$stmt = $con->prepare("SELECT * FROM course WHERE course_code = ?");
$stmt->bind_param('s', $edit_coursecode);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Course Details</title>
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
            <h2>Course Management</h2>
            <p>Update course records.</p>
        </div>

        <div class="card">
            <h3>Update Course Details</h3>
            <form method="POST" action="admin_course_update.php">
                <?php
                // Fetch the data (assuming one row per course_code)
                $row = $result->fetch_assoc();
                ?>

                <input type="hidden" name="original_coursecode" value="<?php echo $row['course_code']; ?>">
                <input type="hidden" name="original_coursename" value="<?php echo $row['course_name']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="upd_coursecode" value="<?php echo $row['course_code']; ?>" placeholder="Enter course code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Name</label>
                    <input type="text" name="upd_coursename" value="<?php echo $row['course_name']; ?>" placeholder="Enter Course Name" required>
                </div>
                <div class="form-group">
                    <label class="label" for="school_code">Diploma Code</label>
                    <input type="text" name="upd_diplomacode" value="<?php echo $row['diploma_code']; ?>" placeholder="Enter Diploma Code" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_start_date">Course Start Date</label>
                    <input type="date" name="upd_coursestartdate" value="<?php echo $row['course_start_date']; ?>" placeholder="Enter Course Start Date" required>
                </div>
                <div class="form-group">
                    <label class="label" for="course_end_date">Course End Date</label>
                    <input type="date" name="upd_courseenddate" value="<?php echo $row['course_end_date']; ?>" placeholder="Enter Course End Date" required>
                </div>
                <div class="form-group">
                    <label class="label" for="status">Status</label>
                    <select name="upd_status" required>
                        <option value="" disabled <?php echo ($row['status'] === '') ? 'selected' : ''; ?>>Select Status</option>
                        <option value="To start" <?php echo ($row['status'] === 'To start') ? 'selected' : ''; ?>>To start</option>
                        <option value="In-progress" <?php echo ($row['status'] === 'In-progress') ? 'selected' : ''; ?>>In-progress</option>
                        <option value="Ended" <?php echo ($row['status'] === 'Ended') ? 'selected' : ''; ?>>Ended</option>
                    </select>
                </div>
                <button type="submit">Update Course</button>
            </form>
        </div>
    </div>
</body>
</html>
