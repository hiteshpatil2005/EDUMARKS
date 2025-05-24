<?php
session_start();

// Database connection
require 'config.php'; // Your database configuration file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prn_no = mysqli_real_escape_string($conn, $_POST['prn_no']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);

    // Check if student exists
    $query = "SELECT * FROM students WHERE prn_no = '$prn_no' AND full_name = '$full_name'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Set session variables
        $_SESSION['student_id'] = $row['id'];
        $_SESSION['prn_no'] = $row['prn_no'];
        $_SESSION['student_name'] = $row['full_name'];

        header('Location: ../HomePages/studentHome.php'); // Redirect to student dashboard
        exit();
    } else {
        $error_message = "Invalid PRN number or name.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="adminLogin.css"> <!-- Using the same CSS -->
</head>
<body>

    <div class="left">
        <img src="../Assets/EDU Mark logo with bg.png" alt="EDU MARK Logo">
        <h2>EDU MARKS</h2>
        <p>Your Gateway to Academic Excellence</p>
    </div>

    <div class="right">
        <div class="container">
            <h1>Student Login</h1>
            <form action="studentLogin.php" method="POST">
                <input type="text" name="prn_no" placeholder="Enter PRN Number" required>
                <input type="text" name="full_name" placeholder="Enter Full Name" required>
                <button type="submit">Login</button>
            </form>
            <?php
                if (isset($error_message)) {
                    echo "<p class='error-message'>$error_message</p>";
                }
            ?>
            <!-- Signup link removed -->
        </div>
    </div>

</body>
</html>
