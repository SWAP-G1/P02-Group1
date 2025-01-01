<?php

// Connect to the database 'testing'
$connect = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial");

// View GPA functionality for 'testing' database
if (isset($_POST["view_button"])) {
    $identification_code = $_POST["identification_code"];

    // Input validation: Check if identification_code is selected
    if (empty($identification_code)) {
        echo "<script>
                    alert('Error: Please select an Identification Code to view the GPA!');
                    window.location.href = 'admin_gpa.php';
        </script>";
        exit();
    } else {
        // Query to calculate GPA based on the identification_code
        $query = $connect->prepare("SELECT identification_code, AVG(course_score) AS gpa FROM semester_gpa_to_course_code WHERE identification_code = ? GROUP BY identification_code");
        $query->bind_param('s', $identification_code);
        $query->execute();
        $query->bind_result($identification_code_result, $gpa);
        $query->fetch();
        $query->close();

        // Display the GPA in a JavaScript alert pop-up
        if (!empty($gpa)) {
            echo "<script>
                    alert('Identification Code: $identification_code_result\\nGPA: " . number_format($gpa, 2) . "');
                    window.location.href = 'admin_gpa.php';
                  </script>";

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
                    echo "<script>
                            alert('Student GPA updated successfully!');
                            window.location.href = 'admin_gpa.php';
                         </script>";
                    exit();
                } else {
                    echo "<script>
                            alert('Error updating GPA for Identification Code');
                            window.location.href = 'admin_gpa.php';
                         </script>";
                    exit();
                }
                $update_query->close();
            } else {
                // Insert new record
                $insert_query = $connect->prepare("INSERT INTO student_score (identification_code, semester_gpa) VALUES (?, ?)");
                $insert_query->bind_param('sd', $identification_code, $gpa);
                if ($insert_query->execute()) {
                    echo "<script>
                            alert('Student GPA inserted successfully!');
                            window.location.href = 'admin_gpa.php';
                         </script>";
                    exit();
                } else {
                    echo "<script>
                            alert('Error inserting GPA for Identification Code');
                            window.location.href = 'admin_gpa.php';
                         </script>";
                    exit();
                }
                $insert_query->close();
            }
        } else {
            echo "<script>
                        alert('No GPA data found for Identification Code \"$identification_code\".');
                        window.location.href = 'admin_gpa.php';
                  </script>";
            exit();
        }
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
            <h2>Student GPA</h2>
            <p>View student's Grade Point Average.</p>
        </div>

        <div class="card">
            <h3>Student GPA Details</h3>
            <form method="post" action="admin_gpa.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Student Identification Code</label>
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
                </div>
                <button type="submit" name="view_button" value="View GPA" >View GPA</button>
            </form>
        </div>

        <div class="card">
            <h3>Student GPA Records</h3>
            <?php
            $con = mysqli_connect("localhost", "root", "", "xyz polytechnic_danial"); // Connect to the database
            if (!$con) {
                die('Could not connect: ' . mysqli_connect_errno()); // Return error if connection fails
            }

            // Prepare the statement
            $query = $connect->prepare("SELECT identification_code, semester_gpa FROM student_score");
            $query->execute();
            $query->bind_result($identification_code, $gpa); 

            echo '<table border="1" bgcolor="white" align="center">';
            echo '<tr><th>Identification Code</th><th>GPA</th></tr>';

            // Extract the data row by row
            while ($query->fetch()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($identification_code, ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars(number_format($gpa, 2), ENT_QUOTES, 'UTF-8') . "</td>";
                echo "</tr>";
            }
            echo "</table>";

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
