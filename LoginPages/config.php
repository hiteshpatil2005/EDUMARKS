<?php
$host = "localhost:3307";
$user = "root";
$password = "";
$database = "student_marklist";

// Connect to MySQL
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create admins table
$conn->query("
    CREATE TABLE IF NOT EXISTS admins(
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL
    )
");

// Create classes table
$conn->query("
    CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(255) NOT NULL,
        year INT NOT NULL  -- Adding the year column
    )
");

// Create students table
$conn->query("
    CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        prn_no VARCHAR(255) UNIQUE NOT NULL,
        roll_no VARCHAR(255) UNIQUE NOT NULL,
        department VARCHAR(100) NOT NULL,
        class_id INT NOT NULL,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )
");

// Create semesters table
$conn->query("
    CREATE TABLE IF NOT EXISTS semesters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        semester_name VARCHAR(255) NOT NULL
    )
");

// Create subjects table
$conn->query("
    CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(255) NOT NULL,
        class_id INT NOT NULL,
        semester_id INT NOT NULL,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (semester_id) REFERENCES semesters(id)
    )
");

// Create marks table
$conn->query("
    CREATE TABLE IF NOT EXISTS marks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        semester_id INT NOT NULL,
        subject_id INT NOT NULL,
        marks_obtained DECIMAL(5,2) NOT NULL,
        grade_point DECIMAL(3,2) DEFAULT NULL,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (semester_id) REFERENCES semesters(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )
");

// Create results table
$conn->query("
    CREATE TABLE IF NOT EXISTS results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        semester_id INT NOT NULL,
        cgpa DECIMAL(4,2),
        sgpa DECIMAL(4,2),
        status VARCHAR(50) DEFAULT 'Pending',
        publish_status BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (student_id) REFERENCES students(id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (semester_id) REFERENCES semesters(id)
    )
");

?>
