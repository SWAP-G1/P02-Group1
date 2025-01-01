<?php
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial");
if ($connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
}

// Check if an ID is passed via GET and retrieve record data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare and execute the select query
    $query = $connect->prepare("SELECT identification_code, course_code, course_score, grade FROM semester_gpa_to_course_code WHERE GRADE_ID = ?");
    $query->bind_param('i', $id);
    $query->execute();
    $query->bind_result($identification_code, $course_code, $course_score, $grade);
    $query->fetch();
}

// Check if the form is submitted for an update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_button'])) {
    $id = $_POST['id'];
    $identification_code = $_POST['identification_code'];
    $course_code = $_POST['course_code'];
    $course_score = $_POST['course_score'];
    $grade = $_POST['grade'];

    // Prepare and execute the update query
    $update_query = $connect->prepare("UPDATE semester_gpa_to_course_code SET course_score = ?, grade = ? WHERE GRADE_ID = ?");
    $update_query->bind_param('dsssi', $course_score, $grade, $identification_code, $course_code, $id);
    if ($update_query->execute()) {
        echo "<script>
            alert('Student record updated successfully!');
            window.location.href = 'admin_edit.php';
        </script>";
        exit();
    } else {
        echo "<script>
            alert('Failed to update record.');
            window.location.href = 'admin_edit.php';
        </script>";
        exit();
    }
    $update_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
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
            <h2>Edit Score Record</h2>
            <p>Changes student's Course Score and Grade.</p>
        </div>

        <div class="card">
            <h3>Student Score Details</h3>
            <form method="post" action="admin_score.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <input type="text" name="identification_code" value="<?php echo htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8'); ?>" readonly />

                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <input type="text" name="course_code" value="<?php echo htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8'); ?>" readonly />

                </div>
                <div class="form-group">
                    <label class="label" for="course_score">Course Score</label>
                    <input type="text" name="course_score" value="<?php echo htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <div class="form-group">
                    <label class="label" for="grade">Grade</label>
                    <input type="text" name="grade" value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" />
                <button type="submit" name="update_button" value="Update Button" >Update Score</button>
            </form>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Student Management System. All rights reserved.</p>
    </footer>

</body>
</html>
