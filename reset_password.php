<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = htmlspecialchars($_POST['token']);
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Verify token
    $stmt = $con->prepare("SELECT * FROM password_reset WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];

        // Update password
        $stmt = $con->prepare("UPDATE user SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $new_password, $email);
        $stmt->execute();

        // Delete token
        $stmt = $con->prepare("DELETE FROM password_reset WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Update the login_tracker in the database
        $update_stmt = $con->prepare("UPDATE user SET login_tracker = 1 WHERE email = ?");
        $update_stmt->bind_param("s", $email);
        $update_stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Reset Your Password</h1>
        <div class="card">
            <?php
            if (isset($_GET['token'])) {
                $token = htmlspecialchars($_GET['token']);
                echo '<form method="POST" action="reset_password.php">
                        <input type="hidden" name="token" value="' . $token . '">
                        <div class="form-group">
                            <label for="password">Enter your new password:</label>
                            <input type="password" id="password" name="password" placeholder="New password" required>
                        </div>
                        <button type="submit" class="button">Reset Password</button>
                      </form>';
            } else if ($result->num_rows > 0) {
                echo '<p>Password has been reset!</p>';
            } else {
                echo '<p>Invalid token.<p>';
            }
            ?>
        </div>
    </div>
</body>
</html>