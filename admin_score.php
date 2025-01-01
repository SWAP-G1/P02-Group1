<?php

// Connect to the database 'testing'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial");

// Insert record functionality for 'testing' database
if (isset($_POST["insert_button"])) {
    if ($_POST["insert"] == "yes") {
        $identification_code = $_POST["identification_code"];
        $course_code = $_POST["course_code"];
        $course_score = $_POST["course_score"];
        $grade = $_POST["grade"];

        // Input validation: Check if all inputs are filled
        if (empty($identification_code) || empty($course_code) || empty($course_score) || empty($grade)) {
            echo "<script>
                alert('Error: All fields must be filled out!');
                window.location.href = 'admin_score.php';
            </script>";
            exit();
        } else {
            // Check if the combination of identification_code and course_code already exists
            $check_query = $connect->prepare("SELECT COUNT(*) FROM semester_gpa_to_course_code WHERE identification_code = ? AND course_code = ?");
            $check_query->bind_param('ss', $identification_code, $course_code);
            $check_query->execute();
            $check_query->bind_result($count);
            $check_query->fetch();
            $check_query->close();

            if ($count > 0) {
                // If the combination exists, display an error message
                echo "<script>
                    alert('Error: Identification Code \"$identification_code\" and Course Code \"$course_code\" already exist!');
                    window.location.href = 'admin_score.php';
                </script>";
                exit();
            } else {
                // If the combination doesn't exist, proceed with the insertion
                $query = $connect->prepare("INSERT INTO semester_gpa_to_course_code (GRADE_ID, identification_code, course_code, course_score, grade) VALUES (NULL, ?, ?, ?, ?)");
                $query->bind_param('ssds', $identification_code, $course_code, $course_score, $grade); // Bind the parameters
                if ($query->execute()) {
                    echo "<script>
                        alert('Record Inserted Successfully!');
                        window.location.href = 'admin_score.php';
                    </script>";
                    exit();
                }
            }
        }
    }
}

// Update record functionality 
if (isset($_POST["update_button"])) {
    $id=$_POST["id"];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score = $_POST["course_score"];
    $grade = $_POST["grade"];

    $query = $connect->prepare("UPDATE semester_gpa_to_course_code SET identification_code=?, course_code=?, course_score=?, grade=? WHERE GRADE_ID=?");
    $query->bind_param('ssdsi', $identification_code, $course_code, $course_score, $grade, $id); // Bind the parameters
    $query->execute();
}

// Delete record functionality
if (isset($_POST["delete_button"])) {
    $id=$_POST["id"];
    $query = $connect->prepare("DELETE FROM semester_gpa_to_course_code WHERE GRADE_ID=?");
    $query->bind_param('i', $id);
    if ($query->execute()) {
        echo "<script>
            alert('Student record deleted successfully!');
            window.location.href = 'admin_score.php';
        </script>";
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record</title>
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
            <h2>Student Grading System</h2>
            <p>Add, update, and organize student score records.</p>
        </div>

        <div class="card">
            <h3>Student Score Details</h3>
            <form method="post" action="admin_score.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
                    <select name="identification_code">
                        <option value="">Select Identification Code</option>
                        <?php
                        // Fetch unique identification codes from the database
                        $result = $connect->query("SELECT DISTINCT identification_code FROM user");
                        while ($row = $result->fetch_assoc()) {
                            // Check if the current value is selected
                            $selected = isset($_POST['identification_code']) && $_POST['identification_code'] === $row['identification_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['identification_code'], ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars($row['identification_code'], ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_code">Course Code</label>
                    <select name="course_code">
                        <option value="">Select Course Code</option>
                        <?php
                        // Fetch unique course codes from the database
                        $result = $connect->query("SELECT DISTINCT course_code FROM course");
                        while ($row = $result->fetch_assoc()) {
                            // Check if the current value is selected
                            $selected = isset($_POST['course_code']) && $_POST['course_code'] === $row['course_code'] ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($row['course_code'], ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars($row['course_code'], ENT_QUOTES, 'UTF-8') . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="label" for="course_score">Course Score</label>
                    <td><input type="text" name="course_score" value="<?php echo isset($_POST['course_score']) ? htmlspecialchars($_POST['course_score'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                </div>
                <div class="form-group">
                    <label class="label" for="grade">Grade</label>
                    <input type="text" name="grade" value="<?php echo isset($_POST['grade']) ? htmlspecialchars($_POST['grade'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                </div>
                <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                <input type="hidden" name="insert" value="yes" />
                <button type="submit" name="insert_button" value="Insert Score" >Insert Score</button>
            </form>
        </div>

        <div class="card">
            <h3>Student Score Records</h3>
            <?php
            $con = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial"); // Connect to the database
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
            }

            // Prepare the statement
            $query = $connect->prepare("SELECT * FROM semester_gpa_to_course_code");
            $query->execute();
            $query->bind_result($id, $identification_code, $course_code, $course_score, $grade);


            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>Course Code</th><th>Course Score</th><th>Grade</th><th colspan="2">Operations</th></tr>';

            // Extract the data row by row
            while ($query->fetch()) {
                echo '<tr>';
                echo "<td>" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td><a href='admin_edit.php?operation=edit&id=" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "&identification_code" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "&course_code=" . htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8') . "&course_score=" . htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8') . "&grade=" . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . "'>Edit</a></td>";
                echo "<td align='center'>";
                echo "<form method='post' action='admin_score.php'>";
                echo "<input type='hidden' name='id' value='" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "' />";
                echo "<input type='submit' name='delete_button' value='Delete' class='button' />";
                echo "</form>";
                echo '</tr>';
            }

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
