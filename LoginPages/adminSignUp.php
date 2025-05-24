<?php
session_start();
require 'config.php'; // Your database configuration file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    
    if ($password != $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Hash the password before storing
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert the new admin into the database
        $query = "INSERT INTO admins (username, password) VALUES ('$username', '$hashed_password')";
        
        if (mysqli_query($conn, $query)) {
            $success_message = "Account created successfully! <a href='adminLogin.php'>Login</a>";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign Up</title>
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
            <h1>Admin Sign Up</h1>
            <form action="adminSignUp.php" method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Sign Up</button>
            </form>
            <?php
                if (isset($error_message)) {
                    echo "<p class='error-message'>$error_message</p>";
                }
                if (isset($success_message)) {
                    echo "<p class='success-message'>$success_message</p>";
                }
            ?>
            <p>Already have an account?  <a href="adminLogin.php">Login</a></p>
        </div>
    </div>

</body>
</html>
