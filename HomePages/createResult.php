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
$message = '';
$selected_class = null;
$selected_student = null;
$selected_semester = null;
$subjects = [];
$marks = [];
$result_data = [];
$search_query = '';
$all_sgpas = [];
$initial_cgpa = 0;

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
    
    // Fetch subjects for this class and semester
    $subjects = $conn->query("
        SELECT s.* 
        FROM subjects s
        WHERE s.class_id = {$selected_class['id']} 
        AND s.semester_id = $semester_id
        ORDER BY s.subject_name ASC
    ");
    
    // Fetch all SGPAs for this student to calculate CGPA
    $sgpa_result = $conn->query("
        SELECT sgpa FROM results 
        WHERE student_id = {$selected_student['id']}
        AND semester_id != $semester_id");
    
    while ($row = $sgpa_result->fetch_assoc()) {
        $all_sgpas[] = $row['sgpa'];
    }
    
    // Check if result already exists
    $result_exists = $conn->query("
        SELECT * FROM results 
        WHERE student_id = {$selected_student['id']} 
        AND class_id = {$selected_class['id']} 
        AND semester_id = $semester_id
    ")->fetch_assoc();
    
    if ($result_exists) {
        $result_data = $result_exists;
        
        // If we're editing an existing result, exclude its current SGPA from the calculation
        $all_sgpas = array_filter($all_sgpas, function($sgpa) use ($result_exists) {
            return abs($sgpa - $result_exists['sgpa']) > 0.01;
        });
        
        // Fetch existing marks
        $marks_result = $conn->query("
            SELECT m.*, s.subject_name 
            FROM marks m
            JOIN subjects s ON m.subject_id = s.id
            WHERE m.student_id = {$selected_student['id']} 
            AND m.class_id = {$selected_class['id']} 
            AND m.semester_id = $semester_id
        ");
        
        while ($mark = $marks_result->fetch_assoc()) {
            $marks[$mark['subject_id']] = $mark;
        }
    }
    
    // Calculate initial CGPA
    if (!empty($all_sgpas)) {
        $initial_cgpa = array_sum($all_sgpas) / count($all_sgpas);
    }
    
    // If we're editing, use the existing CGPA
    if ($result_exists) {
        $initial_cgpa = $result_exists['cgpa'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_result'])) {
        $student_id = intval($_POST['student_id']);
        $class_id = intval($_POST['class_id']);
        $semester_id = intval($_POST['semester_id']);
        $sgpa = floatval($_POST['sgpa']);
        $cgpa = floatval($_POST['cgpa']);
        
        // Check if any subject has marks < 40
        $has_failed = false;
        foreach ($_POST['marks'] as $subject_id => $mark_data) {
            if (floatval($mark_data['marks_obtained']) < 40) {
                $has_failed = true;
                break;
            }
        }
        
        $status = $has_failed ? 'Fail' : 'Pass';
        
      
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Check if result already exists
            $existing_result = $conn->query("
                SELECT id FROM results 
                WHERE student_id = $student_id 
                AND class_id = $class_id 
                AND semester_id = $semester_id
            ")->fetch_assoc();
            
            if ($existing_result) {
                // Update existing result
                $conn->query("
                    UPDATE results SET 
                    sgpa = $sgpa,
                    cgpa = $cgpa,
                    status = '$status'
                    WHERE id = {$existing_result['id']}
                ");
            } else {
                // Insert new result
                $conn->query("
                    INSERT INTO results (student_id, class_id, semester_id, sgpa, cgpa, status)
                    VALUES ($student_id, $class_id, $semester_id, $sgpa, $cgpa, '$status')
                ");
            }
            
            // Process marks for each subject
            foreach ($_POST['marks'] as $subject_id => $mark_data) {
                $subject_id = intval($subject_id);
                $marks_obtained = floatval($mark_data['marks_obtained']);
                $grade_point = floatval($mark_data['grade_point']);
                
                // Check if mark already exists
                $existing_mark = $conn->query("
                    SELECT id FROM marks 
                    WHERE student_id = $student_id 
                    AND class_id = $class_id 
                    AND semester_id = $semester_id 
                    AND subject_id = $subject_id
                ")->fetch_assoc();
                
                if ($existing_mark) {
                    // Update existing mark
                    $conn->query("
                        UPDATE marks SET 
                        marks_obtained = $marks_obtained,
                        grade_point = $grade_point
                        WHERE id = {$existing_mark['id']}
                    ");
                } else {
                    // Insert new mark
                    $conn->query("
                        INSERT INTO marks (student_id, class_id, semester_id, subject_id, marks_obtained, grade_point)
                        VALUES ($student_id, $class_id, $semester_id, $subject_id, $marks_obtained, $grade_point)
                    ");
                }
            }
            
            // Commit transaction
            $conn->commit();
            $message = "Result saved successfully!";
            
            // Refresh the page to show updated data
            header("Location: createResult.php?class_id=$class_id&student_id=$student_id&semester_id=$semester_id");
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error saving result: " . $e->getMessage();
        }
    }
    
    // Handle result deletion
    if (isset($_POST['delete_result'])) {
        $student_id = intval($_POST['student_id']);
        $class_id = intval($_POST['class_id']);
        $semester_id = intval($_POST['semester_id']);
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Delete marks first
            $conn->query("
                DELETE FROM marks 
                WHERE student_id = $student_id 
                AND class_id = $class_id 
                AND semester_id = $semester_id
            ");
            
            // Then delete the result
            $conn->query("
                DELETE FROM results 
                WHERE student_id = $student_id 
                AND class_id = $class_id 
                AND semester_id = $semester_id
            ");
            
            // Commit transaction
            $conn->commit();
            $message = "Result deleted successfully!";
            
            // Redirect back to student page
            header("Location: createResult.php?class_id=$class_id&student_id=$student_id");
            exit;
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $message = "Error deleting result: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Result Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="createResult.css">
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
                         onclick="window.location.href='createResult.php?class_id=<?php echo $class['id']; ?>'">
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

                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

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
                                     onclick="window.location.href='createResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $student['id']; ?>'">
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
                </h1>
                <a href="createResult.php?class_id=<?php echo $selected_class['id']; ?>" class="btn btn-secondary">
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
                                </div>
                            <?php else: ?>
                                <div class="result-status">
                                    <span class="status-badge warning">No Result</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="semester-actions">
                            <?php if ($result_exists): ?>
                                <a href="createResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $selected_student['id']; ?>&semester_id=<?php echo $semester['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> View Result
                                </a>
                            <?php else: ?>
                                <a href="createResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $selected_student['id']; ?>&semester_id=<?php echo $semester['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Create Result
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($selected_semester): ?>
        <!-- Result form modal -->
        <div class="modal-overlay active">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>
                        <i class="fas fa-file-alt"></i> 
                        <?php echo isset($result_data['id']) ? 'Update Result' : 'Create Result'; ?> for 
                        <?php echo htmlspecialchars($selected_student['full_name']); ?> - 
                        <?php echo htmlspecialchars($selected_semester['semester_name']); ?>
                    </h2>
                    <a href="createResult.php?class_id=<?php echo $selected_class['id']; ?>&student_id=<?php echo $selected_student['id']; ?>" class="close-modal">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="student_id" value="<?php echo $selected_student['id']; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class['id']; ?>">
                    <input type="hidden" name="semester_id" value="<?php echo $selected_semester['id']; ?>">
                    
                    <!-- Add hidden inputs for existing SGPAs -->
                    <?php if (!empty($all_sgpas)): ?>
                        <?php foreach ($all_sgpas as $sgpa): ?>
                            <input type="hidden" name="existing_sgpas[]" value="<?php echo $sgpa; ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="form-scroll">
                        <!-- Marks table with fixed header -->
                        <div class="table-container">
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
                                            <td>
                                                <input type="number" class="form-control marks-obtained" 
                                                       name="marks[<?php echo $subject['id']; ?>][marks_obtained]" 
                                                       value="<?php echo isset($marks[$subject['id']]) ? htmlspecialchars($marks[$subject['id']]['marks_obtained']) : ''; ?>" 
                                                       min="0" max="100" step="0.01" required>
                                            </td>
                                            <td>
                                                <input type="number" class="form-control grade-point" 
                                                       name="marks[<?php echo $subject['id']; ?>][grade_point]" 
                                                       value="<?php echo isset($marks[$subject['id']]) ? htmlspecialchars($marks[$subject['id']]['grade_point']) : ''; ?>" 
                                                       min="0" max="10" step="0.01" required>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Result summary -->
                        <div class="result-summary">
                            <div class="form-group">
                                <label for="sgpa">SGPA</label>
                                <input type="number" class="form-control" name="sgpa" id="sgpa" 
                                       value="<?php echo isset($result_data['sgpa']) ? htmlspecialchars($result_data['sgpa']) : ''; ?>" 
                                       min="0" max="10" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="cgpa">CGPA</label>
                                <input type="number" class="form-control" name="cgpa" id="cgpa" 
                                       value="<?php echo isset($initial_cgpa) ? htmlspecialchars($initial_cgpa) : ''; ?>" 
                                       min="0" max="10" step="0.01" readonly>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="Pass" <?php echo (isset($result_data['status']) && $result_data['status'] == 'Pass') ? 'selected' : ''; ?>>Pass</option>
                                    <option value="Fail" <?php echo (isset($result_data['status']) && $result_data['status'] == 'Fail') ? 'selected' : ''; ?>>Fail</option>
                                    <option value="Pending" <?php echo (!isset($result_data['status']) || $result_data['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <?php if (isset($result_data['id'])): ?>
                            <button type="submit" name="delete_result" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this result? This action cannot be undone.');">
                                <i class="fas fa-trash"></i> Delete Result
                            </button>
                        <?php endif; ?>
                        <button type="submit" name="save_result" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo isset($result_data['id']) ? 'Update Result' : 'Save Result'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Auto-calculate grade points based on marks
        document.querySelectorAll('.marks-obtained').forEach(input => {
            input.addEventListener('input', function() {
                const marks = parseFloat(this.value) || 0;
                const row = this.closest('tr');
                const gradePointInput = row.querySelector('.grade-point');
                
                // Simple conversion from marks to grade points
                let gradePoint = 0;
                if (marks >= 90) gradePoint = 10;
                else if (marks >= 80) gradePoint = 9;
                else if (marks >= 70) gradePoint = 8;
                else if (marks >= 60) gradePoint = 7;
                else if (marks >= 50) gradePoint = 6;
                else if (marks >= 40) gradePoint = 5;
                else gradePoint = 0;
                
                gradePointInput.value = gradePoint.toFixed(2);
                
                // Recalculate SGPA and update status
                calculateSGPA();
                updateStatusBasedOnMarks();
            });
        });

        // Calculate SGPA based on grade points
        function calculateSGPA() {
            const gradePointInputs = document.querySelectorAll('.grade-point');
            let totalGradePoints = 0;
            let count = 0;
            
            gradePointInputs.forEach(input => {
                const gradePoint = parseFloat(input.value) || 0;
                totalGradePoints += gradePoint;
                count++;
            });
            
            const sgpa = count > 0 ? (totalGradePoints / count) : 0;
            document.getElementById('sgpa').value = sgpa.toFixed(2);
            
            // Calculate CGPA
            calculateCGPA();
        }

        // Calculate CGPA based on all SGPAs (existing + current)
        function calculateCGPA() {
            const existingSgpas = Array.from(document.querySelectorAll('input[name="existing_sgpas[]"]')).map(input => parseFloat(input.value));
            const currentSgpa = parseFloat(document.getElementById('sgpa').value) || 0;
            
            // Combine existing SGPAs with current SGPA
            const allSgpas = [...existingSgpas, currentSgpa];
            const cgpa = allSgpas.length > 0 ? (allSgpas.reduce((a, b) => a + b, 0) / allSgpas.length) : 0;
            
            document.getElementById('cgpa').value = cgpa.toFixed(2);
        }

        // Update status based on marks (Fail if any subject < 40)
        function updateStatusBasedOnMarks() {
            const marksInputs = document.querySelectorAll('.marks-obtained');
            const statusSelect = document.getElementById('status');
            let hasFailed = false;
            
            marksInputs.forEach(input => {
                const marks = parseFloat(input.value) || 0;
                if (marks < 40) {
                    hasFailed = true;
                }
            });
            
            // Update status
            if (hasFailed) {
                statusSelect.value = 'Fail';
            } else {
                statusSelect.value = 'Pass';
            }
        }

        // Initialize calculations when page loads
        document.addEventListener('DOMContentLoaded', function() {
            calculateSGPA();
            updateStatusBasedOnMarks();
        });
    </script>
</body>
</html>