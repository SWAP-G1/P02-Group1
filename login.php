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
            <img src="bwlogo-removebg.png" alt="XYZ Polytechnic Logo" class="school-logo"> 
            <h1>Polytechnic Management</h1>
        </div>
    </div>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <h2 class="text-center">Login</h2>
            <form method="POST" action="login_process.php">
                <div class="form-group">
                    <label class="label" for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                     <label class="label" for="password">Password <a href="#" class="login-links">Forgot Password?</a></label>
                     <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit">Login</button>
            </form>
            <p class="login-links">
                <a href="#">Create An Account</a>
            </p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 Polytechnic Management. All rights reserved.</p>
    </footer>

</body>
</html>
