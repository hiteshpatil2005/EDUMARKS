<?php
// db_connection.php - A separate file to connect to the database
$host = 'localhost:3307';
$username = 'root';
$password = '';
$dbname = 'student_marklist';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<?php
// Add Student Page
include('../LoginPages/config.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $prn_no = $_POST['prn_no'];
    $roll_no = $_POST['roll_no'];
    $department = $_POST['department'];
    $class_id = $_POST['class_id'];

    // Check if PRN number already exists in the database
    $checkQuery = "SELECT * FROM students WHERE prn_no = '$prn_no'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult->num_rows > 0) {
        // If PRN number already exists, display message
        echo "<script>alert('Error: PRN Number already exists.');</script>";
    } else {
        // Insert student details into the database if PRN doesn't exist
        $query = "INSERT INTO students (full_name, prn_no, roll_no, department, class_id) 
                  VALUES ('$full_name', '$prn_no', '$roll_no', '$department', '$class_id')";
        
        if ($conn->query($query) === TRUE) {
            echo "<script>
                    alert('Student added successfully!');
                    document.getElementById('studentForm').reset();
                  </script>";
        } else {
            echo "Error: " . $query . "<br>" . $conn->error;
        }
    }
}

// Fetch available classes for the dropdown
$classQuery = "SELECT * FROM classes";
$classResult = $conn->query($classQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="students.css">
</head>
<body>
    <div class="main-container">
        <!-- Left Section - Form -->
        <div class="form-section">
            <h1>Add New Student</h1>
            
            <form id="studentForm" method="POST" action="">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="prn_no">PRN Number</label>
                    <input type="text" id="prn_no" name="prn_no" required>
                </div>
                
                <div class="form-group">
                    <label for="roll_no">Roll Number</label>
                    <input type="text" id="roll_no" name="roll_no" required>
                </div>
                
                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" required>
                </div>
                
                <div class="form-group">
                    <label for="class_id">Select Class</label>
                    <select id="class_id" name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php while ($class = $classResult->fetch_assoc()): ?>
                            <option value="<?= $class['id'] ?>"><?= $class['class_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="submit-btn">Add Student</button>
            </form>
            
            <a href="adminHome.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <!-- Right Section - Classes -->
        <div class="classes-section">
            <div class="classes-header">
                <h2>Available Classes</h2>
                <div class="search-container">
                    <i class="fas fa-search"></i>
                    <input type="text" id="classSearch" placeholder="Search classes...">
                </div>
            </div>
            
            <div class="class-cards" id="classCards">
                <?php
                $classResult->data_seek(0); // Reset result pointer
                while ($class = $classResult->fetch_assoc()):
                ?>
                    <div class="class-card" onclick="window.location.href='view_students.php?class_id=<?= $class['id'] ?>'">
                        <h3><i class="fas fa-chalkboard-teacher"></i> <?= $class['class_name'] ?></h3>
                        <p>Click to view students in this class</p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('classSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.class-card');
            
            cards.forEach(card => {
                const className = card.querySelector('h3').textContent.toLowerCase();
                if (className.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Show success message if form was submitted successfully
        <?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($query) && $conn->query($query)): ?>
            const successDiv = document.createElement('div');
            successDiv.className = 'success-message';
            successDiv.textContent = 'Student added successfully!';
            document.querySelector('.form-section').insertBefore(successDiv, document.querySelector('#studentForm'));
            successDiv.style.display = 'block';
            
            // Hide after 3 seconds
            setTimeout(() => {
                successDiv.style.opacity = '0';
                setTimeout(() => successDiv.remove(), 500);
            }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>

<?php $conn->close(); ?>