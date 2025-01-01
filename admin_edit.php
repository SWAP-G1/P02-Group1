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
        echo "<center>Record Updated!</center><br>";
    } else {
        echo "<center>Failed to update record.</center><br>";
    }
    $update_query->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Record</title>
</head>
<body>
    <h1 align="center">Edit Record</h1>
    <form method="post" action="admin_semgpa_course.php">
        <table align="center" border="0">
            <tr>
                <td>Identification Code:</td>
                <td><input type="text" name="identification_code" value="<?php echo htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /></td>
            </tr>
            <tr>
                <td>Course Code:</td>
                <td><input type="text" name="course_code" value="<?php echo htmlspecialchars($course_code, ENT_QUOTES, 'UTF-8'); ?>" readonly /></td>
            </tr>
            <tr>
                <td>Course Score:</td>
                <td><input type="text" name="course_score" value="<?php echo htmlspecialchars($course_score, ENT_QUOTES, 'UTF-8'); ?>" /></td>
            </tr>
            <tr>
                <td>Grade:</td>
                <td><input type="text" name="grade" value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>" /></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align="right">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>" />
                    <input type="submit" name="update_button" value="Update Record" />
                </td>
            </tr>
        </table>
    </form>
</body>
</html>
