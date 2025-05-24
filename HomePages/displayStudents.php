<?php
include('db_connection.php');

// Get class ID from URL
$class_id = $_GET['class_id'];

// Fetch students based on selected class
$studentsQuery = "SELECT * FROM students WHERE class_id = '$class_id'";
$studentsResult = $conn->query($studentsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Students</title>
    <link rel="stylesheet" href="students.css">
</head>
<body>
    <div class="container">
        <h1>Students in Class <?= $class_id ?></h1>
        <div class="students-list">
            <?php while ($student = $studentsResult->fetch_assoc()): ?>
                <div class="student-card" onclick="window.location.href='student_details.php?id=<?= $student['id'] ?>'">
                    <p><?= $student['full_name'] ?> - Roll No: <?= $student['roll_no'] ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
