<?php
session_start();

// Database connection
require 'config.php'; // Your database configuration file (replace with actual file path if needed)

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Check if user exists
    $query = "SELECT * FROM admins WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            header('Location: ../HomePages/adminHome.php'); // Redirect to the dashboard
            exit();
        } else {
            $error_message = "Incorrect Password.";
        }
    } else {
        $error_message = "No user found with this username.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="adminLogin.css">
</head>
<body>

    <div class="left">
        <img src="../Assets/EDU Mark logo with bg.png" alt="EDU MARK Logo">
        <h2>EDU MARKS</h2>
        <p>Your Gateway to Academic Excellence</p>
    </div>

    <div class="right">
        <div class="container">
            <h1>Admin Login</h1>
            <form action="adminLogin.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <?php
                if (isset($error_message)) {
                    echo "<p class='error-message'>$error_message</p>";
                }
            ?>
            <p>Don't have an account?  <a href="adminSignUp.php">Sign up</a></p>
        </div>
    </div>

</body>
</html>
