<?php
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic"); // Connect to the database
if (!$con) {
    die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
}

// Catch the submitted class_code to fetch data
$edit_classcode = htmlspecialchars($_GET["class_code"]);


// Prepare the statement
$stmt = $con->prepare("SELECT * FROM class WHERE class_code = ?");

// Bind the parameter
$stmt->bind_param('s', $edit_classcode);

// Execute the statement
$stmt->execute();

// Get the result set
$result = $stmt->get_result();
?>

<html>
<head>
    <title>Update Class Details</title>
    <style>
        th {
            text-align: left;
        }
    </style>
</head>
<body>
    <form action="class_update.php" method="POST">
        <table border="0" bgcolor="white" align="center">
            <?php
            // Fetch the data (assuming one row per class_code)
            $row = $result->fetch_assoc();
            ?>
            <!-- Add a hidden field to store the original class code -->
            <input type="hidden" name="original_classcode" value="<?php echo $row['class_code']; ?>">

            <tr>
                <td>Class Code</td>
                <td><input type="text" name="upd_classcode" value="<?php echo $row['class_code']; ?>" required></td>
            </tr>
            <tr>
                <td>Course Code</td>
                <td><input type="text" name="upd_coursecode" value="<?php echo $row['course_code']; ?>" required></td>
            </tr>
            <tr>
                <td>Class Type</td>
                <td>
                    <select name="upd_classtype" required>
                        <option value="" disabled <?php echo ($row['class_type'] === '') ? 'selected' : ''; ?>>Select Class Type</option>
                        <option value="Semester" <?php echo ($row['class_type'] === 'Semester') ? 'selected' : ''; ?>>Semester</option>
                        <option value="Term" <?php echo ($row['class_type'] === 'Term') ? 'selected' : ''; ?>>Term</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan="2" align="right"><input type="submit" value="Update Class"></td>
            </tr>
        </table>
    </form>
</body>
</html>

<?php
$con->close();
?>
