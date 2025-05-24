<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../LoginPages/adminLogin.php");
    exit;
}

// Include the database configuration
include('../LoginPages/config.php');

// Initialize error/success message
$message = '';

// Handle form submission for creating class
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_class'])) {
    $class_name = isset($_POST['class_name']) ? mysqli_real_escape_string($conn, $_POST['class_name']) : '';
    $year = isset($_POST['year']) ? mysqli_real_escape_string($conn, $_POST['year']) : '';

    if (empty($class_name) || empty($year)) {
        $message = "Both class name and year are required.";
    } else {
        $checkSql = "SELECT * FROM classes WHERE class_name = '$class_name' AND year = '$year'";
        $checkResult = $conn->query($checkSql);

        if ($checkResult->num_rows > 0) {
            $message = "Class already exists.";
        } else {
            $sql = "INSERT INTO classes (class_name, year) VALUES ('$class_name', '$year')";
            if ($conn->query($sql) === TRUE) {
                $message = "New class created successfully!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } else {
                $message = "Error: " . $conn->error;
            }
        }
    }
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_class'])) {
    $class_id = intval($_POST['class_id']);
    $class_name = mysqli_real_escape_string($conn, $_POST['class_name']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);

    $updateSql = "UPDATE classes SET class_name = '$class_name', year = '$year' WHERE id = $class_id";
    if ($conn->query($updateSql)) {
        $message = "Class updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Error updating class: " . $conn->error;
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $deleteSql = "DELETE FROM classes WHERE id = $delete_id";
    if ($conn->query($deleteSql)) {
        $message = "Class deleted successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = "Error deleting class: " . $conn->error;
    }
}

// Handle search
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$whereClause = $search ? "WHERE class_name LIKE '%$search%' OR year LIKE '%$search%'" : "";

// Fetch existing classes with IDs
$sql = "SELECT id, class_name, year FROM classes $whereClause ORDER BY year DESC, class_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-orange: #e67e22;
            --secondary-orange: #f5923e;
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
        }

        header {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow);
            position: relative;
            z-index: 10;
        }

        header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .card {
            background-color: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 30px;
            margin-bottom: 30px;
            transition: var(--transition);
            border-top: 4px solid var(--primary-orange);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 107, 53, 0.2);
        }

        .card-title {
            color: var(--primary-orange);
            margin-bottom: 20px;
            font-size: 1.5rem;
            position: relative;
            padding-bottom: 10px;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-orange), var(--light-orange));
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

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--pale-orange);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--light-orange);
            box-shadow: 0 0 0 3px rgba(255, 179, 71, 0.2);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: var(--white);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-orange), var(--primary-orange));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(255, 107, 53, 0.3);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        .btn-warning {
            background: linear-gradient(135deg, #FFC107, #FFA000);
            color: var(--dark-gray);
        }

        .btn-warning:hover {
            background: linear-gradient(135deg, #FFA000, #FF8F00);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #F44336, #D32F2F);
            color: var(--white);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #D32F2F, #B71C1C);
            transform: translateY(-2px);
        }

        .search-container {
            position: relative;
            margin-bottom: 30px;
        }

        .search-container input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid var(--secondary-orange);
            border-radius: 30px;
            font-size: 16px;
            transition: var(--transition);
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

        .table-responsive {
            overflow-x: auto;
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
        }

        .action-cell {
            display: flex;
            gap: 8px;
        }

        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
        }

        .success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border-left: 4px solid #4CAF50;
        }

        .error {
            background-color: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #F44336;
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

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
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

        @media (max-width: 576px) {
            header h1 {
                font-size: 1.5rem;
            }
            
            header p {
                font-size: 1rem;
            }
            
            .card {
                padding: 20px;
            }
        }

        .back-btn {
    display: inline-block;
    margin-top: 20px;
    margin-bottom: 20px;
    padding: 8px 24px;
    background-color: var(--white);
    color: var(--secondary-orange);
    border: 3px solid var(--secondary-orange); /* Added border */
    border-radius: 15px;
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.btn-container {
  display: flex;
  justify-content: flex-end; /* aligns content to right */
  padding: 10px; /* optional spacing */
}

.back-btn:hover {
    color: var(--white);
    background-color: var(--secondary-orange);
    transform: translateY(-2px);
}
    </style>
</head>
<body>
    <header>
        <h1>Admin Panel - Class Management</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
    </header>
    <div class="container">
        <!-- Display message -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search classes by name or year..." 
                   value="<?php echo htmlspecialchars($search); ?>"
                   onkeyup="if(event.keyCode === 13) { searchClasses(); }">
            <button class="btn btn-primary" onclick="searchClasses()" style="position: absolute; right: 0; top: 0; height: 100%; border-radius: 0 30px 30px 0;">
                Search
            </button>
        </div>

        <!-- Create Class Form -->
        <div class="card">
            <div class="btn-container">
               <a href="adminHome.php" class="back-btn">
                 <i class="fas fa-arrow-left"></i> Back
               </a>
            </div>
            <h2 class="card-title">
                <i class="fas fa-plus-circle"></i> Create New Class
            </h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="class_name">Class Name</label>
                    <input type="text" class="form-control" name="class_name" id="class_name" required>
                </div>
                
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" class="form-control" name="year" id="year" required>
                </div>
                
                <button type="submit" name="create_class" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create Class
                </button>
            </form>
        </div>

        <!-- Classes Table -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-list"></i> Existing Classes
            </h2>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['year']); ?></td>
                                    <td class="action-cell">
                                        <button class="btn btn-warning btn-sm" onclick="openEditModal(
                                            <?php echo $row['id']; ?>,
                                            '<?php echo htmlspecialchars($row['class_name'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($row['year'], ENT_QUOTES); ?>'
                                        )">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="openDeleteModal(
                                            <?php echo $row['id']; ?>,
                                            '<?php echo htmlspecialchars($row['class_name'], ENT_QUOTES); ?>',
                                            '<?php echo htmlspecialchars($row['year'], ENT_QUOTES); ?>'
                                        )">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No classes found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Class Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Class</h2>
                <span class="close" onclick="closeModal('editModal')">&times;</span>
            </div>
            <form id="editForm" method="POST" action="">
                <input type="hidden" name="class_id" id="editClassId">
                <input type="hidden" name="update_class" value="1">
                
                <div class="form-group">
                    <label for="editClassName">Class Name</label>
                    <input type="text" class="form-control" name="class_name" id="editClassName" required>
                </div>
                
                <div class="form-group">
                    <label for="editYear">Year</label>
                    <input type="number" class="form-control" name="year" id="editYear" required>
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
                <p>Are you sure you want to delete this class?</p>
            </div>
            
            <div class="student-info">
                <p><strong>Class Name:</strong> <span id="deleteClassName"></span></p>
                <p><strong>Year:</strong> <span id="deleteYear"></span></p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Cancel</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Delete Class</a>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        function searchClasses() {
            const searchTerm = document.getElementById('searchInput').value;
            window.location.href = '<?php echo $_SERVER['PHP_SELF']; ?>?search=' + encodeURIComponent(searchTerm);
        }

        // Open edit modal
        function openEditModal(id, className, year) {
            document.getElementById('editClassId').value = id;
            document.getElementById('editClassName').value = className;
            document.getElementById('editYear').value = year;
            document.getElementById('editModal').style.display = 'block';
        }

        // Open delete modal
        function openDeleteModal(id, className, year) {
            document.getElementById('deleteClassName').textContent = className;
            document.getElementById('deleteYear').textContent = year;
            
            // Set the delete link
            const deleteUrl = '<?php echo $_SERVER['PHP_SELF']; ?>?delete_id=' + id;
            document.getElementById('confirmDeleteBtn').href = deleteUrl;
            
            document.getElementById('deleteModal').style.display = 'block';
        }

        // Close modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                document.getElementById('editModal').style.display = 'none';
                document.getElementById('deleteModal').style.display = 'none';
            }
        }
    </script>
</body>
</html>