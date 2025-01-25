<?php
session_start(); // Start the session

// Connect to the database
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Include PHPMailer
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $form_email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : "";
    $form_password = isset($_POST['password']) ? $_POST['password'] : "";

    // Check for empty fields
    if (empty($form_email) || empty($form_password)) {
        header("Location: hamizanlogin.php?error=" . urlencode("Email and Password fields cannot be empty. Please try again."));
        exit();
    }

    // Use a prepared statement to fetch the user
    $stmt = $con->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $form_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Verify the password (stored using password_hash)
        if (password_verify($form_password, $row['password'])) {
            // Set session variables
            $_SESSION['session_identification_code'] = $row['identification_code'];
            $_SESSION['session_full_name'] = $row['full_name'];
            $_SESSION['session_role'] = $row['role_id'];

            // Check if the user is a student and if it's their first login
            if ($row['role_id'] == 3 && $row['login_tracker'] == 0) {
                $token = bin2hex(random_bytes(50)); // Generate token
                
                // Save token to password_reset table
                $stmt = $con->prepare("INSERT INTO password_reset (email, token) VALUES (?, ?)");
                $stmt->bind_param("ss", $form_email, $token);
                $stmt->execute();
                
                // Send password reset email
                $reset_link = "http://localhost/PROJECT/reset_password.php?token=$token";
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'xyzpolytechnicadm@gmail.com';
                    $mail->Password = 'pges vjob hgjl lfzb';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom('xyzpolytechnicadm@gmail.com', 'XYZ Polytechnic');
                    $mail->addAddress($form_email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Reset Your Password';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4;'>
                            <table align='center' width='600' style='margin: 20px auto; background-color: #fff; border: 1px solid #ddd; border-radius: 8px; overflow: hidden;'>
                                <!-- Header -->
                                <tr>
                                    <td style='background-color: #113f84; text-align: center; padding: 20px;'></td>
                                </tr>
                                
                                <!-- Body -->
                                <tr>
                                    <td style='padding: 30px; text-align: center; color: #333;'>
                                        <h1 style='font-size: 24px; color: #333; margin: 0 0 20px;'>Password Reset Info</h1>
                                        <p style='font-size: 16px; line-height: 1.5; margin: 0 0 20px;'>
                                            Dear User,<br>We received a request to reset your password.
                                        </p>
                                        <a href='http://localhost/PROJECT/reset_password.php?token=$token'
                                           style='display: inline-block; padding: 12px 20px; background-color: #113f84; color: #fff; text-decoration: none; font-size: 16px; border-radius: 5px; margin: 20px 0;'>
                                            Reset your password
                                        </a>
                                        <p style='font-size: 14px; color: #666; margin: 20px 0 0;'>
                                            This link will expire in 24 hours. If you did not request a password reset, please ignore this message.
                                        </p>
                                    </td>
                                </tr>
                                ";

                    $mail->send();
                    header("Location: hamizanlogin.php?success=1");
                    exit();
                } catch (Exception $e) {
                    header("Location: password_reset_request.php?error=" . urlencode("Failed to send email. Please contact support.</h2>"));
                    exit();
                }
            }

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
                header("Location: hamizanlogin.php?error=" . urlencode("Invalid role detected. Contact administrator."));
                exit();
            }
        } else {
            // Incorrect password
            header("Location: hamizanlogin.php?error=" . urlencode("Incorrect Email/Password. Please try again."));
            exit();
        }
    } else {
        // No user found, changed to incorrect email/password.
        header("Location: hamizanlogin.php?error=" . urlencode("Incorrect Email/Password. Please try again."));
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
            <form method="POST" action="hamizanlogin.php">
                <div class="form-group">
                    <label class="label" for="email">Email</label>
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                     <label class="label" for="password">Password</label>
                     <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <?php
                    // If ?success=1 is set in the URL, display a success message
                    if (isset($_GET['success']) && $_GET['success'] == 1) {
                        echo '<div id="message" style="color: green; font-weight: bold;">A password reset link has been sent to your email address. Please check your inbox to proceed.</div>';
                    }
                                    
                    // Check if an error parameter was passed
                    if (isset($_GET['error'])) {
                        echo '<div id="message" style="color: red; font-weight: bold;">' . htmlspecialchars($_GET['error']) . '</div>';
                    }                    
                    ?>
                </div>
                <button type="submit">Login</button>
            </form>
            <p class="text-center mt-2">
                <a href="password_reset_request.php">Forgot Password?</a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 XYZ Polytechnic Management. All rights reserved.</p>
    </footer>
    
    <script>
        setTimeout(function() {
        const messageElement = document.getElementById('message');
        if (messageElement) {
            messageElement.style.display = 'none';
        }
        }, 10000);
    
    </script>

</body>
</html>
