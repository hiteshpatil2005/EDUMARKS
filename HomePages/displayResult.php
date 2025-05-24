<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../LoginPages/config.php");
    exit;
}

// Database connection
$host = "localhost:3307";
$user = "root";
$password = "";
$database = "student_marklist";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$selected_class = null;
$selected_student = null;
$selected_semester = null;
$search_query = '';

// Fetch all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY year DESC, class_name ASC");

// Handle class selection
if (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
    $selected_class = $conn->query("SELECT * FROM classes WHERE id = $class_id")->fetch_assoc();
    
    // Fetch students in this class
    $search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $search_condition = $search_query ? "AND full_name LIKE '%$search_query%'" : '';
    
    $students = $conn->query("
        SELECT * FROM students 
        WHERE class_id = $class_id 
        $search_condition
        ORDER BY roll_no ASC
    ");
}

// Handle student selection
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);
    $selected_student = $conn->query("SELECT * FROM students WHERE id = $student_id")->fetch_assoc();
    
    // Fetch semesters for this class
    $semesters = $conn->query("SELECT * FROM semesters ORDER BY id ASC");
}

// Handle semester selection
if (isset($_GET['semester_id'])) {
    $semester_id = intval($_GET['semester_id']);
    $selected_semester = $conn->query("SELECT * FROM semesters WHERE id = $semester_id")->fetch_assoc();
    
    // Check if result exists for this semester
    $result_data = $conn->query("
        SELECT * FROM results 
        WHERE student_id = {$selected_student['id']} 
        AND class_id = {$selected_class['id']} 
        AND semester_id = $semester_id
    ")->fetch_assoc();
    
    if ($result_data) {
        // Fetch subjects and marks for this semester
        $subjects = $conn->query("
            SELECT s.*, m.marks_obtained, m.grade_point
            FROM subjects s
            LEFT JOIN marks m ON s.id = m.subject_id 
                AND m.student_id = {$selected_student['id']} 
                AND m.class_id = {$selected_class['id']} 
                AND m.semester_id = $semester_id
            WHERE s.class_id = {$selected_class['id']} 
            AND s.semester_id = $semester_id
            ORDER BY s.subject_name ASC
        ");
    }
}

// Calculate CGPA for the student
$cgpa = 0;
if (isset($selected_student)) {
    $cgpa_result = $conn->query("
        SELECT AVG(sgpa) AS cgpa 
        FROM results 
        WHERE student_id = {$selected_student['id']}
        AND class_id = {$selected_class['id']}
    ");
    if ($cgpa_result && $cgpa_result->num_rows > 0) {
        $cgpa_data = $cgpa_result->fetch_assoc();
        $cgpa = round($cgpa_data['cgpa'], 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student Results</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="displayResult.css">
</head>
<body>
    <?php if (!isset($_GET['student_id'])): ?>
        <!-- Class selection page -->
        <div class="container">
            <div class="sidebar">
                <div class="sidebar-header">
                    <h2><i class="fas fa-chalkboard"></i> Classes</h2>
                </div>
                
                <?php while ($class = $classes->fetch_assoc()): ?>
                    <div class="class-card <?php echo ($selected_class && $selected_class['id'] == $class['id']) ? 'active' : ''; ?>" 
                         onclick="window.location.href='displayResult.php?class_id=<?php echo $class['id']; ?>'">
                        <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                        <p>Year: <?php echo htmlspecialchars($class['year']); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="main-content">
                <div class="header">
                    <h1>
                        <i class="fas fa-graduation-cap"></i> 
                        <?php echo $selected_class ? "Students - " . htmlspecialchars($selected_class['class_name']) : "Select a Class"; ?>
                    </h1>
                    <?php if ($selected_class): ?>
                        <a href="adminHome.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($selected_class): ?>
                    <div class="search-box">
                        <form method="GET" action="">
                            <input type="hidden" name="class_id" value="<?php echo $selected_class['id']; ?>">
                            <input type="text" name="search" placeholder="Search students..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit"><i class="fas fa-search"></i></button>
                        </form>
                    </div>

                    <div class="student-list">
                        <?php if ($students->num_rows > 0): ?>
                            <?php while ($student = $students->fetch_assoc()): ?>
                                <div class="student-card" 
                                     onclick="window.location.href='displayResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $student['id']; ?>'">
                                    <div class="student-info">
                                        <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                                        <p>Roll No: <?php echo htmlspecialchars($student['roll_no']); ?></p>
                                        <p>PRN: <?php echo htmlspecialchars($student['prn_no']); ?></p>
                                    </div>
                                    <div class="student-arrow">
                                        <i class="fas fa-chevron-right"></i>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-results">
                                <i class="fas fa-user-graduate"></i>
                                <p>No students found in this class.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Student semester selection page -->
        <div class="container">
            <div class="header">
                <h1>
                    <i class="fas fa-user-graduate"></i> 
                    <?php echo htmlspecialchars($selected_student['full_name']); ?>
                    <span class="student-cgpa">CGPA: <?php echo $cgpa; ?></span>
                </h1>
                <a href="displayResult.php?class_id=<?php echo $selected_class['id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <div class="semester-container">
                <h2><i class="fas fa-calendar-alt"></i> Select Semester</h2>
                
                <?php while ($semester = $semesters->fetch_assoc()): ?>
                    <?php 
                    // Check if result exists for this semester
                    $result_exists = $conn->query("
                        SELECT * FROM results 
                        WHERE student_id = {$selected_student['id']} 
                        AND class_id = {$selected_class['id']} 
                        AND semester_id = {$semester['id']}
                    ")->fetch_assoc();
                    ?>
                    
                    <div class="semester-card">
                        <div class="semester-info">
                            <h3><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                            <?php if ($result_exists): ?>
                                <div class="result-status">
                                    <span class="status-badge success">Result Available</span>
                                    <span>SGPA: <?php echo htmlspecialchars($result_exists['sgpa']); ?></span>
                                    <span>Status: <?php echo htmlspecialchars($result_exists['status']); ?></span>
                                </div>
                            <?php else: ?>
                                <div class="result-status">
                                    <span class="status-badge warning">No Result</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="semester-actions">
                            <?php if ($result_exists): ?>
                                <a href="displayResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $selected_student['id']; ?>&semester_id=<?php echo $semester['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Result
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($selected_semester) && isset($result_data)): ?>
        <!-- Result display modal -->
        <div class="modal-overlay active">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>
                        <i class="fas fa-file-alt"></i> 
                        Result for <?php echo htmlspecialchars($selected_student['full_name']); ?> - 
                        <?php echo htmlspecialchars($selected_semester['semester_name']); ?>
                    </h2>
                    <a href="displayResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $selected_student['id']; ?>" class="close-modal">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <div class="result-details">
                    <div class="student-info-card">
                        <div>
                            <h3><?php echo htmlspecialchars($selected_student['full_name']); ?></h3>
                            <p>Roll No: <?php echo htmlspecialchars($selected_student['roll_no']); ?></p>
                            <p>PRN: <?php echo htmlspecialchars($selected_student['prn_no']); ?></p>
                        </div>
                        <div>
                            <p>Class: <?php echo htmlspecialchars($selected_class['class_name']); ?></p>
                            <p>Year: <?php echo htmlspecialchars($selected_class['year']); ?></p>
                        </div>
                    </div>
                    
                    <div class="performance-summary">
                        <div class="performance-card">
                            <h4>SGPA</h4>
                            <p><?php echo htmlspecialchars($result_data['sgpa']); ?></p>
                        </div>
                        <div class="performance-card">
                            <h4>CGPA</h4>
                            <p><?php echo $cgpa; ?></p>
                        </div>
                        <div class="performance-card">
                            <h4>Status</h4>
                            <p class="status-<?php echo strtolower($result_data['status']); ?>">
                                <?php echo htmlspecialchars($result_data['status']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="marks-table-container">
                        <h3>Subject-wise Marks</h3>
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Grade Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($subject = $subjects->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($subject['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['marks_obtained']); ?></td>
                                        <td><?php echo htmlspecialchars($subject['grade_point']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print"></i> Print Result
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>