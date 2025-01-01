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
            echo "<center>Error: All fields must be filled out!</center><br>";
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
                echo "<center>Error: The combination of Identification Code '$identification_code' and Course Code '$course_code' already exists!</center><br>";
            } else {
                // If the combination doesn't exist, proceed with the insertion
                $query = $connect->prepare("INSERT INTO semester_gpa_to_course_code (GRADE_ID, identification_code, course_code, course_score, grade) VALUES (NULL, ?, ?, ?, ?)");
                $query->bind_param('ssds', $identification_code, $course_code, $course_score, $grade); // Bind the parameters
                if ($query->execute()) {
                    echo "<center>Record Inserted!</center><br>";
                }
            }
        }
    }
}



// Update record functionality for 'testing' database
if (isset($_POST["update_button"])) {
    $id=$_POST["id"];
    $identification_code = $_POST["identification_code"];
    $course_code = $_POST["course_code"];
    $course_score = $_POST["course_score"];
    $grade = $_POST["grade"];

    $query = $connect->prepare("UPDATE semester_gpa_to_course_code SET identification_code=?, course_code=?, course_score=?, grade=? WHERE GRADE_ID=?");
    $query->bind_param('ssdsi', $identification_code, $course_code, $course_score, $grade, $id); // Bind the parameters
    if ($query->execute()) 
    {
        echo "<center>Record Updated!</center><br>";
    }
}

// Delete record functionality for 'testing' database
if (isset($_POST["delete_button"])) {
    $id=$_POST["id"];
    $query = $connect->prepare("DELETE FROM semester_gpa_to_course_code WHERE GRADE_ID=?");
    $query->bind_param('i', $id);
    if ($query->execute()) {
        echo "<center>Record Deleted!</center><br>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>semgpa_course</title>
</head>
<h1 align="center">Student Scores</h1>
<body>
    <form method="post" action="admin_semgpa_course.php">
        <table align="center" border="0">
            <tr>
                <td>Identification Code:</td>
                <td>
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
                </td>
            </tr>
            <tr>
                <td>Course Code:</td>
                <td>
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
                </td>
            </tr>
            <tr>
                <td>Course Score:</td>
                <td><input type="text" name="course_score" value="<?php echo isset($_POST['course_score']) ? htmlspecialchars($_POST['course_score'], ENT_QUOTES, 'UTF-8') : ''; ?>" /></td>
            </tr>
            <tr>
                <td>Grade:</td>
                <td><input type="text" name="grade" value="<?php echo isset($_POST['grade']) ? htmlspecialchars($_POST['grade'], ENT_QUOTES, 'UTF-8') : ''; ?>" /></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align="right">
                    <input type="hidden" name="id" value="<?php echo isset($_GET['id']) ? htmlspecialchars($_GET['id'], ENT_QUOTES, 'UTF-8') : ''; ?>" />
                    <input type="hidden" name="insert" value="yes" />
                    <input type="submit" name="insert_button" value="Insert Record" />
                </td>
            </tr>
        </table>

<?php
// Read: Admin and Faculty can view all student score records
$query = $connect->prepare("SELECT * FROM semester_gpa_to_course_code");
$query->execute();
$query->bind_result($id, $identification_code, $course_code, $course_score, $grade);

echo "<table align='center' border='1'>";
echo "<tr>";
echo "<th>Identification Code</th>";
echo "<th>Course Code</th>";
echo "<th>Course Score</th>";
echo "<th>Grade</th>";
echo "<th>EDIT</th>";
echo "<th>DELETE</th>";
echo "</tr>";

while ($query->fetch()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td><a href='admin_edit.php?operation=edit&id=" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "&identification_code" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "&course_code=" . htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8') . "&course_score=" . htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8') . "&grade=" . htmlspecialchars($grade, ENT_QUOTES, 'UTF-8') . "'>edit</a></td>";
    echo "<td align='center'>";
    echo "<form method='post' action='admin_semgpa_course.php'>";
    echo "<input type='hidden' name='id' value='" . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "' />";
    echo "<input type='submit' name='delete_button' value='delete' class='button' />";
    echo "</form>";
    echo "</td>";
    echo "</tr>";
}
echo "</table>";
?>
    </form>
</body>
</html>
