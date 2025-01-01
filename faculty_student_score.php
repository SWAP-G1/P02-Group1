<?php

// Connect to the database 'testing'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial");

// View GPA functionality for 'testing' database
if (isset($_POST["view_button"])) {
    $identification_code = $_POST["identification_code"];

    // Input validation: Check if identification_code is selected
    if (empty($identification_code)) {
        echo "<center>Error: Please select an Identification Code to view the GPA!</center><br>";
    } else {
        // Query to calculate GPA based on the identification_code
        $query = $connect->prepare("SELECT identification_code, AVG(course_score) AS gpa FROM semester_gpa_to_course_code WHERE identification_code = ? GROUP BY identification_code");
        $query->bind_param('s', $identification_code);
        $query->execute();
        $query->bind_result($identification_code_result, $gpa);
        $query->fetch();
        $query->close();

        // Display the GPA
        if (!empty($gpa)) {
            echo "<div style='text-align: center; margin-top: 20px;'>";
            echo "<h2>Identification Code: $identification_code_result</h2>";
            echo "<h2>GPA: " . number_format($gpa, 2) . "</h2>";
            echo "</div>";

            // Check for existing record and update or insert accordingly
            $check_query = $connect->prepare("SELECT COUNT(*) FROM student_score WHERE identification_code = ?");
            $check_query->bind_param('s', $identification_code);
            $check_query->execute();
            $check_query->bind_result($exists);
            $check_query->fetch();
            $check_query->close();

            if ($exists > 0) {
                // Update existing record
                $update_query = $connect->prepare("UPDATE student_score SET semester_gpa = ? WHERE identification_code = ?");
                $update_query->bind_param('ds', $gpa, $identification_code);
                if ($update_query->execute()) {
                    echo "<center>Student GPA updated successfully!</center><br>";
                } else {
                    echo "<center>Error updating GPA for Identification Code '$identification_code'.</center><br>";
                }
                $update_query->close();
            } else {
                // Insert new record
                $insert_query = $connect->prepare("INSERT INTO student_score (identification_code, semester_gpa) VALUES (?, ?)");
                $insert_query->bind_param('sd', $identification_code, $gpa);
                if ($insert_query->execute()) {
                    echo "<center>Student GPA added successfully!</center><br>";
                } else {
                    echo "<center>Error inserting GPA for Identification Code '$identification_code'.</center><br>";
                }
                $insert_query->close();
            }
        } else {
            echo "<center>No GPA data found for Identification Code '$identification_code'.</center><br>";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View GPA</title>
</head>
<h1 align="center">Student GPA</h1>
<body>
    <form method="post" action="faculty_student_score.php">
        <table align="center" border="0">
            <tr>
                <td>Identification Code:</td>
                <td>
                    <select name="identification_code">
                        <option value="">Select Identification Code</option>
                        <?php
                        // Fetch unique identification codes from the database
                        $result = $connect->query("SELECT DISTINCT identification_code FROM semester_gpa_to_course_code");
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
                <td>&nbsp;</td>
                <td align="right">
                    <input type="submit" name="view_button" value="View GPA" />
                </td>
            </tr>
        </table>

<?php
// Display all GPA data from the database
$query = $connect->prepare("SELECT identification_code, semester_gpa FROM student_score");
$query->execute();
$query->bind_result($identification_code, $gpa);

echo "<table align='center' border='1'>";
echo "<tr>";
echo "<th>Identification Code</th>";
echo "<th>GPA</th>";
echo "</tr>";

while ($query->fetch()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "</td>";
    echo "<td>" . htmlspecialchars(number_format($gpa, 2), ENT_QUOTES, 'UTF-8') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
    </form>
</body>
</html>
