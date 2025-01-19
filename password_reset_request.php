<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php'; // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$con = mysqli_connect("localhost", "root", "", "xyz polytechnic");
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);

    // Check if the email exists
    $stmt = $con->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(50)); // Generate token

        // Save token to password_reset table
        $stmt = $con->prepare("INSERT INTO password_reset (email, token) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $token);
        $stmt->execute();

        // Send reset email
        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'xyzpolytechnicadm@gmail.com';
            $mail->Password = 'pges vjob hgjl lfzb'; // App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email content
            $mail->setFrom('xyzpolytechnicadm@gmail.com', 'Your Website');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click http://localhost/swapmain/reset_password.php?token=$token to reset your password.";

            $mail->send();
            echo "Password reset email has been sent!";
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1 class="text-center">Password Reset Request</h1>
        <div class="card">
            <form method="POST" action="password_reset_request.php">
                <div class="form-group">
                    <label for="email">Enter your email address:</label>
                    <input type="text" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <button type="submit" class="button">Send Password Reset Email</button>
            </form>
        </div>
    </div>
</body>
</html>