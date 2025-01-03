<?php
session_start(); // Start the session

// Connect to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $form_identification_code = isset($_POST['identification_code']) ? htmlspecialchars($_POST['identification_code']) : "";
    $form_password = isset($_POST['password']) ? $_POST['password'] : "";

    // Check for empty fields
    if (empty($form_identification_code) || empty($form_password)) {
        echo "<h2>Identification Code and Password fields cannot be empty. Please try again</h2>";
        header("Refresh: 2; URL=testlogin.php");
        exit();
    }

    // Use a prepared statement to fetch the user
    $stmt = $con->prepare("SELECT * FROM user WHERE identification_code = ?");
    $stmt->bind_param("s", $form_identification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {

        // Verify the password (stored using password_hash)
        if (password_verify($form_password, $row['password'])) {
            // Set session variables
            $_SESSION['session_identification_code'] = $row['identification_code'];
            $_SESSION['session_full_name'] = $row['full_name'];
            $_SESSION['session_role'] = $row['role_id'];

            // Role-based redirection
            if ($row['role_id'] == 1) { // Admin
                header("Location: admin_dashboard.php");
                exit();
            } elseif ($row['role_id'] == 2) { // Faculty
                header("Location: faculty_dashboard.php");
                exit();
            } elseif ($row['role_id'] == 3) { // Student
                header("Location: stu_dashboard.php");
                exit();
            } else {
                echo "<h2>Invalid role detected. Contact administrator.</h2>";
                exit();
            }
        } else {
            // Incorrect password
            echo "<h2>Incorrect Password. Please try again.</h2>";
            header("Refresh: 2; URL=testlogin.php");
            exit();
        }
    } else {
        // No user found
        echo "<h2>No such user exists. Please try again.</h2>";
        header("Refresh: 2; URL=testlogin.php");
        exit();
    }

    $stmt->close();
}

// Close the database connection
mysqli_close($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Nunito+Sans:wght@400&family=Poppins:wght@500&display=swap" rel="stylesheet">
</head>
<body>

    <div class="navbar">
        <div class="navbar-brand">
            <img src="logo.png" alt="XYZ Polytechnic Logo" class="school-logo"> 
            <h1>XYZ Polytechnic Management</h1>
        </div>
    </div>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h2 class="text-center">Login</h2>
            <form method="POST" action="testlogin.php">
                <div class="form-group">
                    <label class="label" for="identification_code">Username</label>
                    <input type="text" id="identification_code" name="identification_code" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                     <label class="label" for="password">Password</label>
                     <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p class="text-center mt-2">
                <a href="#">Forgot Password?</a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Management. All rights reserved.</p>
    </footer>

</body>
</html>