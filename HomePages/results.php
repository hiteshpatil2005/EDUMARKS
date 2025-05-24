<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header('Location: ../LoginPages/studentLogin.php');
    exit();
}

$host = "localhost:3307";
$user = "root";
$password = "";
$database = "student_marklist";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_SESSION['student_id'];
$student_query = "SELECT s.*, c.class_name 
                 FROM students s 
                 JOIN classes c ON s.class_id = c.id 
                 WHERE s.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();

$selected_semester = isset($_GET['semester']) ? intval($_GET['semester']) : null;

$semesters_query = "SELECT DISTINCT sem.* 
                   FROM semesters sem
                   JOIN subjects sub ON sem.id = sub.semester_id
                   WHERE sub.class_id = ?
                   ORDER BY sem.semester_name";
$stmt = $conn->prepare($semesters_query);
$stmt->bind_param("i", $student['class_id']);
$stmt->execute();
$semesters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$result_data = null;
$marks_data = null;
if ($selected_semester) {
    $result_query = "SELECT r.*, sem.semester_name
                    FROM results r
                    JOIN semesters sem ON r.semester_id = sem.id
                    WHERE r.student_id = ? 
                    AND r.semester_id = ?";
    $stmt = $conn->prepare($result_query);
    $stmt->bind_param("ii", $student_id, $selected_semester);
    $stmt->execute();
    $result_data = $stmt->get_result()->fetch_assoc();

    $marks_query = "SELECT sub.subject_name, m.marks_obtained, m.grade_point
                   FROM marks m
                   JOIN subjects sub ON m.subject_id = sub.id
                   WHERE m.student_id = ?
                   AND m.semester_id = ?
                   ORDER BY sub.subject_name";
    $stmt = $conn->prepare($marks_query);
    $stmt->bind_param("ii", $student_id, $selected_semester);
    $stmt->execute();
    $marks_data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU MARKS - Exam Results</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --primary-light: #FF8C5A;
            --primary-dark: #E05A2C;
            --secondary-color: #FF9E1B;
            --accent-color: #FFB347;
            --background-color: #ffffff;
            --text-color: #2B2D42;
            --light-text: #6C757D;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --border-radius: 10px;
            --border-color: rgba(0, 0, 0, 0.08);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .logo-icon {
            color: var(--primary-dark);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            color: var(--primary-color);
            gap: 1.5rem;
        }
        
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.2);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background-color: rgba(255, 107, 53, 0.1);
            transform: translateX(-3px);
        }
        
        .semester-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-bottom: 3rem;
        }
        
        .semester-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            text-align: center;
            border: 2px solid var(--secondary-color);
            cursor: pointer;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }
        
        .semester-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .semester-card.active {
            border-color: var(--primary-color);
            background-color: rgba(255, 107, 53, 0.03);
        }
        
        .semester-card.active::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-color), var(--accent-color));
        }
        
        .semester-card i {
            font-size: 2.25rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .semester-card h3 {
            font-size: 1.25rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .semester-card p {
            color: var(--light-text);
            font-size: 0.9rem;
        }
        
        .result-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            margin-bottom: 3rem;
            border: 1px solid var(--border-color);
            overflow: hidden;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background-color: rgba(255, 107, 53, 0.03);
            border-bottom: 1px solid var(--border-color);
        }
        
        .result-header h2 {
            color: var(--text-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .result-header h2 i {
            color: var(--primary-color);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            background-color: #4CAF50;
            color: white;
        }
        
        .status-badge.pending {
            background-color: #FFC107;
            color: var(--text-color);
        }
        
        .result-content {
            padding: 2rem;
        }
        
        .student-info {
            margin-bottom: 2.5rem;
        }
        
        .student-info h3 {
            font-size: 1.3rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-color);
        }
        
        .student-info h3 i {
            color: var(--primary-color);
        }
        
        .student-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            margin-top: 1.25rem;
        }
        
        .info-item {
            padding: 1rem;
            border-radius: 8px;
            background-color: rgba(255, 107, 53, 0.02);
            border: 1px solid var(--border-color);
        }
        
        .info-label {
            font-weight: 500;
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--text-color);
            font-size: 1.05rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: rgba(255, 107, 53, 0.05);
            color: var(--primary-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background-color: rgba(255, 107, 53, 0.02);
        }
        
        .summary-card {
            background-color: rgba(255, 107, 53, 0.03);
            border-radius: var(--border-radius);
            padding: 1.75rem;
            margin-top: 2rem;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        
        .summary-item {
            text-align: center;
            padding: 1rem;
        }
        
        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .summary-label {
            color: var(--light-text);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            justify-content: flex-end;
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.15);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.25);
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
        }
        
        .action-btn.secondary {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .action-btn.secondary:hover {
            background: rgba(255, 107, 53, 0.05);
        }
        
        .action-btn i {
            transition: transform 0.3s ease;
        }
        
        .action-btn:hover i {
            transform: translateX(3px);
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--light-text);
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }
        
        .no-results i {
            font-size: 3.5rem;
            color: var(--light-text);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .no-results p {
            max-width: 500px;
            margin: 0 auto;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
                font-size: 12pt;
            }
            
            .container {
                padding: 0;
                max-width: 100%;
            }
            
            .no-print {
                display: none !important;
            }
            
            .result-container {
                box-shadow: none;
                border: none;
                page-break-after: avoid;
            }
            
            table {
                page-break-inside: avoid;
            }
            
            .action-buttons {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .semester-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .student-info-grid, .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .result-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="no-print">
            <div class="logo">
                <i class="fas fa-graduation-cap logo-icon"></i>
                EDU MARKS
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                <div class="user-avatar"><?php echo substr($student['full_name'], 0, 1); ?></div>
            </div>
        </header>
        
        <div class="page-header no-print">
            <a href="studentHome.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Exam Results</h1>
        </div>
        
        <p class="no-print">Select a semester to view your results</p>
        
        <div class="semester-grid no-print">
            <?php foreach ($semesters as $semester): ?>
                <a href="results.php?semester=<?php echo $semester['id']; ?>" class="semester-card <?php echo ($selected_semester == $semester['id']) ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <h3><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                    <p>View Results</p>
                </a>
            <?php endforeach; ?>
        </div>
        
        <?php if ($selected_semester): ?>
            <?php if ($result_data): ?>
                <div class="result-container" id="result-to-print">
                    <!-- Print Header (only visible when printing) -->
                    <div class="print-header" style="display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #eee;">
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div style="font-size: 1.5rem; font-weight: 700; color: #FF6B35;">EDU MARKS</div>
                            </div>
                            <div style="font-size: 0.9rem; color: #666;"><?php echo date('F j, Y'); ?></div>
                        </div>
                        <h1 style="margin-bottom: 1.5rem; color: #2B2D42;">Semester <?php echo htmlspecialchars($result_data['semester_name']); ?> Results</h1>
                    </div>
                    
                    <div class="result-header">
                        <h2>
                            <i class="fas fa-poll"></i> 
                             <?php echo htmlspecialchars($result_data['semester_name']); ?> Results
                        </h2>
                        <span class="status-badge <?php echo ($result_data['status'] == 'Pending') ? 'pending' : ''; ?>">
                            <?php echo htmlspecialchars($result_data['status']); ?>
                        </span>
                    </div>
                    
                    <div class="result-content">
                        <div class="student-info">
                            <h3><i class="fas fa-user-graduate"></i> Student Information</h3>
                            <div class="student-info-grid">
                                <div class="info-item">
                                    <div class="info-label">Full Name</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">PRN Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['prn_no']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Roll Number</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['roll_no']); ?></div>
                                </div>
                                <div class="info-item">
                                    <div class="info-label">Class</div>
                                    <div class="info-value"><?php echo htmlspecialchars($student['class_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <h3 style="margin-top: 2.5rem; margin-bottom: 1rem; font-size: 1.3rem; color: var(--text-color);">
                            <i class="fas fa-book" style="color: var(--primary-color);"></i> Subject-wise Marks
                        </h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Marks Obtained</th>
                                    <th>Grade Point</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marks_data as $mark): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($mark['marks_obtained']); ?></td>
                                        <td><?php echo htmlspecialchars($mark['grade_point']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="summary-card">
                            <h3 style="margin-bottom: 1.5rem; font-size: 1.3rem; color: var(--text-color);">
                                <i class="fas fa-chart-pie" style="color: var(--primary-color);"></i> Result Summary
                            </h3>
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <div class="summary-value"><?php echo htmlspecialchars($result_data['sgpa']); ?></div>
                                    <div class="summary-label">SGPA</div>
                                </div>
                                <div class="summary-item">
                                    <div class="summary-value"><?php echo htmlspecialchars($result_data['cgpa']); ?></div>
                                    <div class="summary-label">CGPA</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="action-buttons no-print">
                            <button onclick="window.print()" class="action-btn">
                                <i class="fas fa-print"></i> Print Result
                            </button>
                            <button onclick="downloadResult()" class="action-btn secondary">
                                <i class="fas fa-download"></i> Download Result
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-results">
                    <i class="fas fa-info-circle"></i>
                    <h3>No Results Found</h3>
                    <p>Your results for this semester have not been published yet.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results no-print">
                <i class="fas fa-hand-pointer"></i>
                <h3>Select a Semester</h3>
                <p>Please select a semester from above to view your results.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function downloadResult() {
            // Create a temporary element with the result content
            const resultContent = document.getElementById('result-to-print').innerHTML;
            
            // Create a print header with logo and website name
            const printHeader = `
                <div style="margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="font-size: 24px; font-weight: 700; color: #FF6B35;">EDU MARKS</div>
                        </div>
                        <div style="font-size: 14px; color: #666;">${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</div>
                    </div>
                    <h1 style="margin: 0; font-size: 22px; color: #2B2D42;">Semester <?php echo $selected_semester ? htmlspecialchars($result_data['semester_name']) : ''; ?> Result</h1>
                </div>
            `;
            
            // Create a footer with copyright information
            const printFooter = `
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center;">
                    <p>© ${new Date().getFullYear()} EDU MARKS - Student Portal. All rights reserved.</p>
                </div>
            `;
            
            // CSS styles for the downloaded document
            const styles = `
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
                    
                    body { 
                        font-family: 'Poppins', sans-serif; 
                        margin: 0; 
                        padding: 30px; 
                        color: #333;
                        line-height: 1.6;
                    }
                    
                    .result-header, .no-print, .action-buttons {
                        display: none !important;
                    }
                    
                    h1, h2, h3 {
                        color: #2B2D42;
                        margin-bottom: 15px;
                    }
                    
                    h1 { font-size: 22px; }
                    h2 { font-size: 18px; }
                    h3 { font-size: 16px; }
                    
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                        font-size: 14px;
                    }
                    
                    th, td {
                        padding: 12px 15px;
                        text-align: left;
                        border-bottom: 1px solid #eee;
                    }
                    
                    th {
                        background-color: #f5f5f5;
                        color: #FF6B35;
                        font-weight: 600;
                    }
                    
                    .student-info-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 15px;
                        margin: 20px 0;
                    }
                    
                    .info-item {
                        padding: 12px;
                        border: 1px solid #eee;
                        border-radius: 6px;
                    }
                    
                    .info-label {
                        font-size: 12px;
                        color: #666;
                        margin-bottom: 5px;
                    }
                    
                    .info-value {
                        font-weight: 600;
                    }
                    
                    .summary-card {
                        background-color: #f9f9f9;
                        border-radius: 8px;
                        padding: 20px;
                        margin: 30px 0;
                    }
                    
                    .summary-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 20px;
                    }
                    
                    .summary-item {
                        text-align: center;
                    }
                    
                    .summary-value {
                        font-size: 24px;
                        font-weight: 700;
                        color: #FF6B35;
                    }
                    
                    .summary-label {
                        font-size: 12px;
                        color: #666;
                        text-transform: uppercase;
                    }
                    
                    .fa {
                        display: none !important;
                    }
                </style>
            `;
            
            // Combine all elements
            const htmlContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>EDU MARKS - Semester <?php echo $selected_semester ? htmlspecialchars($result_data['semester_name']) : ''; ?> Result</title>
                    ${styles}
                </head>
                <body>
                    ${printHeader}
                    ${resultContent}
                    ${printFooter}
                </body>
                </html>
            `;
            
            // Create a blob with the content
            const blob = new Blob([htmlContent], { type: 'text/html' });
            const url = URL.createObjectURL(blob);
            
            // Create a download link and trigger it
            const a = document.createElement('a');
            a.href = url;
            a.download = 'EDU_MARKS_<?php echo $student['prn_no']; ?>_Sem_<?php echo $selected_semester ?? ''; ?>_Result.html';
            document.body.appendChild(a);
            a.click();
            
            // Clean up
            setTimeout(() => {
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }, 100);
        }
    </script>
</body>
</html>