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

// Handle delete request
if (isset($_POST['delete_student'])) {
    $student_id = intval($_POST['student_id']);
    $deleteQuery = "DELETE FROM students WHERE id = $student_id";
    if ($conn->query($deleteQuery)) {
        echo "<script>alert('Student deleted successfully'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Error deleting student');</script>";
    }
}

// Handle update request
if (isset($_POST['update_student'])) {
    $student_id = intval($_POST['student_id']);
    $roll_no = $conn->real_escape_string($_POST['roll_no']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $prn_no = $conn->real_escape_string($_POST['prn_no']);
    $department = $conn->real_escape_string($_POST['department']);
    
    $updateQuery = "UPDATE students SET 
                    roll_no = '$roll_no',
                    full_name = '$full_name',
                    prn_no = '$prn_no',
                    department = '$department'
                    WHERE id = $student_id";
    
    if ($conn->query($updateQuery)) {
        echo "<script>alert('Student updated successfully'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Error updating student');</script>";
    }
}

// Fetch the class_id from the URL parameter
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Fetch class name for display
$classQuery = "SELECT * FROM classes WHERE id = $class_id";
$classResult = $conn->query($classQuery);
$class = $classResult->fetch_assoc();

// Fetch students for the selected class, ordered by roll number
$studentQuery = "SELECT * FROM students WHERE class_id = $class_id ORDER BY CAST(roll_no AS UNSIGNED) ASC";
$studentResult = $conn->query($studentQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students - <?= htmlspecialchars($class['class_name']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-orange: #FF6B35;
            --secondary-orange: #FF9E1B;
            --dark-orange: #E04E00;
            --light-orange: #FFB347;
            --pale-orange: #FFE8D6;
            --white: #FFFFFF;
            --dark-gray: #333333;
            --medium-gray: #777777;
            --light-gray: #F5F5F5;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--white);
            color: var(--dark-gray);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 90%;
            margin: 0 auto;
            padding: 30px;
            background-color: var(--white);
            border-radius: 15px;
            
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        h1 {
            color: var(--primary-orange);
            position: relative;
            padding-bottom: 10px;
        }

        .search-container {
            position: relative;
            width: 600px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--secondary-orange);
            border-radius: 30px;
            font-size: 16px;
            transition: var(--transition);
            background-color: var(--light-gray);
        }

        .search-container input:focus {
            outline: none;
            border-color: var(--light-orange);
            box-shadow: 0 0 0 3px rgba(255, 179, 71, 0.2);
        }

        .search-container i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--medium-gray);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 25px 0;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        thead tr {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: var(--white);
            text-align: left;
        }

        th, td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--pale-orange);
        }

        th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        tbody tr {
            transition: var(--transition);
        }

        tbody tr:nth-child(even) {
            background-color: var(--pale-orange);
        }

        tbody tr:hover {
            background-color: rgba(255, 107, 53, 0.1);
            transform: translateX(5px);
        }

        .no-students {
            text-align: center;
            padding: 30px;
            color: var(--medium-gray);
            font-size: 18px;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-orange), var(--primary-orange));
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-secondary {
            background-color: var(--white);
            color: var(--primary-orange);
            border: 2px solid var(--primary-orange);
        }

        .btn-secondary:hover {
            background-color: var(--pale-orange);
            transform: translateY(-3px);
        }

        .btn-sm {
            padding: 8px 12px;
            font-size: 14px;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
            border: none;
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800, #d39e00);
        }

        .btn-info {
            background: linear-gradient(135deg, #f38e2f,rgb(247, 187, 84));
            color: white;
            border: none;
        }

        .btn-info:hover {
            background: linear-gradient(135deg,rgb(247, 187, 84), #f38e2f);
        }

        .action-cell {
            display: flex;
            gap: 8px;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            width: 50%;
            max-width: 600px;
            animation: modalopen 0.3s;
        }

        @keyframes modalopen {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--pale-orange);
        }

        .modal-header h2 {
            color: var(--primary-orange);
        }

        .close {
            color: var(--medium-gray);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
        }

        .close:hover {
            color: var(--dark-orange);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: var(--transition);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--light-orange);
            box-shadow: 0 0 0 3px rgba(255, 179, 71, 0.2);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--pale-orange);
        }

        .confirmation-message {
            font-size: 18px;
            margin-bottom: 30px;
            text-align: center;
        }

        .student-info {
            background-color: var(--pale-orange);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .student-info p {
            margin: 8px 0;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .search-container {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }

            .modal-content {
                width: 90%;
                margin: 20% auto;
            }

            .action-cell {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-users"></i> 
                Students in <?= htmlspecialchars($class['class_name']) ?>
            </h1>
            
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="studentSearch" placeholder="Search students by name or PRN...">
            </div>
        </div>

        <?php if ($studentResult->num_rows > 0): ?>
            <table id="studentsTable">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Full Name</th>
                        <th>PRN Number</th>
                        <th>Department</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $studentResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['roll_no']) ?></td>
                            <td><?= htmlspecialchars($student['full_name']) ?></td>
                            <td><?= htmlspecialchars($student['prn_no']) ?></td>
                            <td><?= htmlspecialchars($student['department']) ?></td>
                            <td class="action-cell">
                                <button class="btn btn-info btn-sm" onclick="openEditModal(
                                    <?= $student['id'] ?>, 
                                    '<?= htmlspecialchars($student['roll_no'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['full_name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['prn_no'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['department'], ENT_QUOTES) ?>'
                                )">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="openDeleteModal(
                                    <?= $student['id'] ?>, 
                                    '<?= htmlspecialchars($student['roll_no'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['full_name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['prn_no'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($student['department'], ENT_QUOTES) ?>'
                                )">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-students">
                <i class="fas fa-user-graduate" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>No students found for this class.</p>
            </div>
        <?php endif; ?>

        <div class="action-buttons">
            <a href="addStudents.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Student
            </a>
            <a href="addStudents.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Classes
            </a>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit Student</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editForm" method="POST">
                <input type="hidden" name="student_id" id="editStudentId">
                <input type="hidden" name="update_student" value="1">
                
                <div class="form-group">
                    <label for="editRollNo">Roll Number</label>
                    <input type="text" id="editRollNo" name="roll_no" required>
                </div>
                
                <div class="form-group">
                    <label for="editFullName">Full Name</label>
                    <input type="text" id="editFullName" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="editPrnNo">PRN Number</label>
                    <input type="text" id="editPrnNo" name="prn_no" required>
                </div>
                
                <div class="form-group">
                    <label for="editDepartment">Department</label>
                    <input type="text" id="editDepartment" name="department" required>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h2>
                <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            </div>
            
            <div class="confirmation-message">
                <p>Are you sure you want to delete this student?</p>
            </div>
            
            <div class="student-info">
                <p><strong>Roll No:</strong> <span id="deleteRollNo"></span></p>
                <p><strong>Name:</strong> <span id="deleteFullName"></span></p>
                <p><strong>PRN:</strong> <span id="deletePrnNo"></span></p>
                <p><strong>Department:</strong> <span id="deleteDepartment"></span></p>
            </div>
            
            <form id="deleteForm" method="POST">
                <input type="hidden" name="student_id" id="deleteStudentId">
                <input type="hidden" name="delete_student" value="1">
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Student</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const name = row.cells[1].textContent.toLowerCase();
                const prn = row.cells[2].textContent.toLowerCase();
                
                if (name.includes(searchTerm) || prn.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Highlight row on hover with animation
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', () => {
                row.style.transition = 'transform 0.3s ease, background 0.3s ease';
            });
        });

        // Modal functions
        function openEditModal(id, rollNo, fullName, prnNo, department) {
            document.getElementById('editStudentId').value = id;
            document.getElementById('editRollNo').value = rollNo;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editPrnNo').value = prnNo;
            document.getElementById('editDepartment').value = department;
            
            document.getElementById('editModal').style.display = 'block';
        }

        function openDeleteModal(id, rollNo, fullName, prnNo, department) {
            document.getElementById('deleteStudentId').value = id;
            document.getElementById('deleteRollNo').textContent = rollNo;
            document.getElementById('deleteFullName').textContent = fullName;
            document.getElementById('deletePrnNo').textContent = prnNo;
            document.getElementById('deleteDepartment').textContent = department;
            
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>