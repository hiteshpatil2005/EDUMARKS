<?php
session_start(); // Start session to destroy it
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

// Redirect to login page or homepage
header("Location: adminLogin.php"); // Change to your login/home page
exit();
?>
