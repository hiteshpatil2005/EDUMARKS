<?php
include('../LoginPages/config.php');

// Handle add semester POST
if (isset($_POST['add_semester'])) {
    $semester_name = $_POST['semester_name'];
    $stmt = $conn->prepare("INSERT INTO semesters (semester_name) VALUES (?)");
    $stmt->bind_param("s", $semester_name);
    $stmt->execute();
    $stmt->close();

    // Prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle add subjects POST
if (isset($_POST['add_subjects'])) {
    $class_id = $_POST['class_id'];
    $semester_id = $_POST['semester_id'];
    $subject_names = $_POST['subject_names'];

    $stmt = $conn->prepare("INSERT INTO subjects (subject_name, class_id, semester_id) VALUES (?, ?, ?)");
    foreach ($subject_names as $subject) {
        if (!empty(trim($subject))) {
            $stmt->bind_param("sii", $subject, $class_id, $semester_id);
            $stmt->execute();
        }
    }
    $stmt->close();

    // Prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle delete subject POST
if (isset($_POST['delete_subject'])) {
    $subject_name = $_POST['subject_name'];
    $class_id = $_POST['class_id'];
    $semester_id = $_POST['semester_id'];
    
    $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_name = ? AND class_id = ? AND semester_id = ?");
    $stmt->bind_param("sii", $subject_name, $class_id, $semester_id);
    $stmt->execute();
    $stmt->close();
    
    // Prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch existing classes and semesters
$classes_result = $conn->query("SELECT * FROM classes ORDER BY class_name");
$semesters_result = $conn->query("SELECT * FROM semesters ORDER BY semester_name");
$classes = $classes_result->fetch_all(MYSQLI_ASSOC);
$semesters = $semesters_result->fetch_all(MYSQLI_ASSOC);

// Fetch subjects grouped by class and semester
$class_subjects = [];
$class_result = $conn->query("SELECT * FROM classes ORDER BY class_name");
while ($class = $class_result->fetch_assoc()) {
    $class_id = $class['id'];
    $subject_result = $conn->query("
        SELECT s.subject_name, sem.semester_name, sem.id as semester_id 
        FROM subjects s
        JOIN semesters sem ON s.semester_id = sem.id
        WHERE s.class_id = $class_id
        ORDER BY sem.semester_name, s.subject_name
    ");
    
    $subjects_by_semester = [];
    while ($subject = $subject_result->fetch_assoc()) {
        $semester_id = $subject['semester_id'];
        if (!isset($subjects_by_semester[$semester_id])) {
            $subjects_by_semester[$semester_id] = [
                'semester_name' => $subject['semester_name'],
                'subjects' => []
            ];
        }
        $subjects_by_semester[$semester_id]['subjects'][] = $subject['subject_name'];
    }
    
    $class_subjects[$class['id']] = [
        'class_name' => $class['class_name'],
        'semesters' => $subjects_by_semester
    ];
}

// Get unique semesters for each class
$class_semesters = [];
foreach ($class_subjects as $class_id => $data) {
    $class_semesters[$class_id] = array_keys($data['semesters']);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Subjects</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="addSubjects.css">
</head>
<body style="background-color: white;">
    <div class="container">
        <h2><i class="fas fa-book-open"></i> Subject Management</h2>
        
        <div class="action-buttons">
            <button class="btn" onclick="openModal('semesterModal')">
                <i class="fas fa-plus"></i> Add Semester
            </button>
            
            <button class="btn" onclick="openModal('subjectModal')">
                <i class="fas fa-plus"></i> Add Subjects
            </button>
        </div>
        
        <!-- Add Semester Modal -->
        <div id="semesterModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-calendar-alt"></i> Add New Semester</h3>
                    <button class="close-btn" onclick="closeModal('semesterModal')">&times;</button>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="semester_name">Semester Name</label>
                        <input type="text" id="semester_name" name="semester_name" placeholder="e.g. Semester 1, Fall 2023" required>
                    </div>
                    <button type="submit" name="add_semester" class="btn">Add Semester</button>
                </form>
            </div>
        </div>
        
        <!-- Add Subjects Modal -->
        <div id="subjectModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title"><i class="fas fa-book"></i> Add New Subjects</h3>
                    <button class="close-btn" onclick="closeModal('subjectModal')">&times;</button>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label for="class_id">Select Class</label>
                        <select name="class_id" id="class_id" required>
                            <option value="">-- Select Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="semester_id">Select Semester</label>
                        <select name="semester_id" id="semester_id" required>
                            <option value="">-- Select Semester --</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>"><?= htmlspecialchars($sem['semester_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>How many subjects do you want to add?</label>
                        <input type="number" id="subjectCount" min="1" value="1" class="form-control">
                        <button type="button" class="btn btn-outline" onclick="generateSubjectInputs()" style="margin-top: 10px;">
                            <i class="fas fa-magic"></i> Generate Fields
                        </button>
                    </div>
                    
                    <div id="subjectInputs" class="subject-inputs"></div>
                    
                    <button type="submit" name="add_subjects" class="btn">
                        <i class="fas fa-save"></i> Save Subjects
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Subjects by Class -->
        <h3><i class="fas fa-list-ul"></i> Subjects by Class</h3>
        
        <?php if (empty($class_subjects)): ?>
            <div class="card no-subjects">
                <p>No subjects have been added yet. Add some subjects to get started!</p>
            </div>
        <?php else: ?>
            <div class="classes-container">
                <?php foreach ($class_subjects as $class_id => $class_data): ?>
                    <div class="card class-card" onclick="openClassModal(<?= $class_id ?>)">
                        <h4 class="class-name">
                            <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($class_data['class_name']) ?>
                            <span class="subject-count">
                                <?= array_reduce($class_data['semesters'], function($carry, $semester) {
                                    return $carry + count($semester['subjects']);
                                }, 0) ?> subjects
                            </span>
                        </h4>
                        <p>
                            <?= count($class_data['semesters']) ?> semesters
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Class Subjects Modal -->
        <div id="classModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title" id="classModalTitle"></h3>
                    <button class="close-btn" onclick="closeModal('classModal')">&times;</button>
                </div>
                
                <div id="semesterFilter" class="semester-filter">
                    <!-- Semester buttons will be added here by JavaScript -->
                </div>
                
                <ul class="subject-list" id="classSubjectsList">
                    <!-- Subjects will be added here by JavaScript -->
                </ul>
            </div>
        </div>
        <div style="display: flex; justify-content: flex-end; padding: 10px;">
            <a href="adminHome.php" 
                style="display: inline-block; margin-top: 20px; margin-bottom: 20px; padding: 8px 24px; background-color: white; color: #FF6600; border: 3px solid #FF6600; border-radius: 15px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    <script>
        const classData = <?= json_encode($class_subjects) ?>;
    </script>
    <script src="addSubjects.js"></script>
</body>
</html>