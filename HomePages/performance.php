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

// Get all semesters with results for this student
$semesters_query = "SELECT DISTINCT sem.id, sem.semester_name
                   FROM semesters sem
                   JOIN results r ON sem.id = r.semester_id
                   WHERE r.student_id = ?
                   ORDER BY sem.semester_name";
$stmt = $conn->prepare($semesters_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$semesters = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Prepare data for charts
$chart_data = [
    'semesters' => [],
    'sgpa' => [],
    'cgpa' => [],
    'subjects' => [],
    'marks' => [],
    'subject_marks' => [] // For selected semester's subject marks
];

// Get all results data for trend charts
$all_results_query = "SELECT r.*, sem.semester_name
                     FROM results r
                     JOIN semesters sem ON r.semester_id = sem.id
                     WHERE r.student_id = ?
                     ORDER BY sem.semester_name";
$stmt = $conn->prepare($all_results_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$all_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($all_results as $result) {
    $chart_data['semesters'][] = $result['semester_name'];
    $chart_data['sgpa'][] = $result['sgpa'];
    $chart_data['cgpa'][] = $result['cgpa'];
}

// Get data for selected semester
if ($selected_semester) {
    // Get result for selected semester
    $result_query = "SELECT r.*, sem.semester_name
                    FROM results r
                    JOIN semesters sem ON r.semester_id = sem.id
                    WHERE r.student_id = ? 
                    AND r.semester_id = ?";
    $stmt = $conn->prepare($result_query);
    $stmt->bind_param("ii", $student_id, $selected_semester);
    $stmt->execute();
    $result_data = $stmt->get_result()->fetch_assoc();
    
    // Get marks for selected semester
    $marks_query = "SELECT sub.subject_name, m.marks_obtained
                   FROM marks m
                   JOIN subjects sub ON m.subject_id = sub.id
                   WHERE m.student_id = ?
                   AND m.semester_id = ?
                   ORDER BY sub.subject_name";
    $stmt = $conn->prepare($marks_query);
    $stmt->bind_param("ii", $student_id, $selected_semester);
    $stmt->execute();
    $marks_result = $stmt->get_result();
    
    while ($mark = $marks_result->fetch_assoc()) {
        $chart_data['subject_marks']['labels'][] = $mark['subject_name'];
        $chart_data['subject_marks']['data'][] = $mark['marks_obtained'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU MARKS - Performance Analysis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .chart-container {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .chart-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chart-header h3 {
            font-size: 1.25rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chart-header h3 i {
            color: var(--primary-color);
        }
        
        .chart-wrapper {
            position: relative;
            height: 400px;
            width: 100%;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
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
        
        .student-info {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.75rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
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
        
        .semester-title {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .semester-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .chart-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 300px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-graduation-cap logo-icon"></i>
                EDU MARKS
            </div>
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                <div class="user-avatar"><?php echo substr($student['full_name'], 0, 1); ?></div>
            </div>
        </header>
        
        <div class="page-header">
            <a href="studentHome.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h1>Performance Analysis</h1>
        </div>
        
        <p>Select a semester to view detailed performance analysis</p>
        
        <div class="semester-grid">
            <?php foreach ($semesters as $semester): ?>
                <a href="performance.php?semester=<?php echo $semester['id']; ?>" class="semester-card <?php echo ($selected_semester == $semester['id']) ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <h3><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                    <p>View Analysis</p>
                </a>
            <?php endforeach; ?>
        </div>
        
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
        
        <?php if (count($all_results) > 0): ?>
            <!-- Overall Performance Charts (Always Visible) -->
            <div class="chart-grid">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-line"></i> SGPA Trend</h3>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="sgpaChart"></canvas>
                    </div>
                </div>
                
                <div class="chart-container">
                    <div class="chart-header">
                        <h3><i class="fas fa-chart-bar"></i> CGPA Progress</h3>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="cgpaChart"></canvas>
                    </div>
                </div>
            </div>
            
            <?php if ($selected_semester): ?>
                <!-- Semester-specific charts (only shown when semester is selected) -->
                <?php if (isset($result_data)): ?>
                    <div class="semester-title">
                        <i class="fas fa-calendar-alt"></i> <?php echo htmlspecialchars($result_data['semester_name']); ?> Performance Analysis
                    </div>
                    
                    <div class="chart-grid">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3><i class="fas fa-book"></i> Subject-wise Marks</h3>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="subjectMarksChart"></canvas>
                            </div>
                        </div>
                        
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3><i class="fas fa-chart-pie"></i> Marks Distribution</h3>
                            </div>
                            <div class="chart-wrapper">
                                <canvas id="marksDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3><i class="fas fa-star"></i> Semester Summary</h3>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="semesterSummaryChart"></canvas>
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
                <div class="no-results">
                    <i class="fas fa-hand-pointer"></i>
                    <h3>Select a Semester</h3>
                    <p>Please select a semester from above to view detailed performance analysis.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-info-circle"></i>
                <h3>No Results Found</h3>
                <p>You don't have any published results yet to analyze your performance.</p>
            </div>
        <?php endif; ?>
        
        <script>
            // Prepare data from PHP for JavaScript
            const chartData = {
                semesters: <?php echo json_encode($chart_data['semesters']); ?>,
                sgpa: <?php echo json_encode($chart_data['sgpa']); ?>,
                cgpa: <?php echo json_encode($chart_data['cgpa']); ?>,
                subjectMarks: <?php echo isset($chart_data['subject_marks']) ? json_encode($chart_data['subject_marks']) : 'null'; ?>,
                semesterData: <?php echo isset($result_data) ? json_encode($result_data) : 'null'; ?>
            };
            
            // SGPA Trend Chart (Line Chart)
            const sgpaCtx = document.getElementById('sgpaChart').getContext('2d');
            const sgpaChart = new Chart(sgpaCtx, {
                type: 'line',
                data: {
                    labels: chartData.semesters,
                    datasets: [{
                        label: 'SGPA',
                        data: chartData.sgpa,
                        backgroundColor: 'rgba(255, 107, 53, 0.1)',
                        borderColor: 'rgba(255, 107, 53, 1)',
                        borderWidth: 3,
                        tension: 0.3,
                        pointBackgroundColor: 'rgba(255, 107, 53, 1)',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 0,
                            max: 10,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `SGPA: ${context.parsed.y.toFixed(2)}`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            // CGPA Progress Chart (Bar Chart)
            const cgpaCtx = document.getElementById('cgpaChart').getContext('2d');
            const cgpaChart = new Chart(cgpaCtx, {
                type: 'bar',
                data: {
                    labels: chartData.semesters,
                    datasets: [{
                        label: 'CGPA',
                        data: chartData.cgpa,
                        backgroundColor: 'rgba(27, 137, 255, 0.7)',
                        borderColor: 'rgb(24, 157, 239)',
                        borderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 0,
                            max: 10,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `CGPA: ${context.parsed.y.toFixed(2)}`;
                                }
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
            
            <?php if ($selected_semester && isset($result_data)): ?>
                // Subject Marks Chart (Bar Chart) - Only for selected semester
                const subjectMarksCtx = document.getElementById('subjectMarksChart').getContext('2d');
                const subjectMarksChart = new Chart(subjectMarksCtx, {
                    type: 'bar',
                    data: {
                        labels: chartData.subjectMarks.labels,
                        datasets: [{
                            label: 'Marks Obtained',
                            data: chartData.subjectMarks.data,
                            backgroundColor: 'rgba(251, 145, 32, 0.7)',
                            borderColor: 'rgba(255, 107, 53, 1)',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    stepSize: 10
                                }
                            }
                        },
                        plugins: {
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `Marks: ${context.parsed.y}`;
                                    }
                                }
                            },
                            legend: {
                                display: false
                            }
                        }
                    }
                });
                
                // Marks Distribution Chart (Doughnut Chart)
                const marksDistributionCtx = document.getElementById('marksDistributionChart').getContext('2d');
                const marksDistributionChart = new Chart(marksDistributionCtx, {
                    type: 'doughnut',
                    data: {
                        labels: chartData.subjectMarks.labels,
                        datasets: [{
                            data: chartData.subjectMarks.data,
                            backgroundColor: [
                                'rgba(120, 255, 53, 0.7)',
                                'rgba(255, 38, 0, 0.66)',
                                'rgba(255, 247, 8, 0.7)',
                                'rgba(255, 140, 90, 0.7)',
                                'rgba(224, 90, 44, 0.7)',
                                'rgba(255, 99, 132, 0.7)',
                                'rgba(54, 162, 235, 0.7)',
                                'rgba(75, 192, 192, 0.7)'
                            ],
                            borderColor: [
                                'rgb(72, 255, 35)',
                                'rgba(255, 38, 0, 0.66)',
                                'rgba(255, 222, 8, 0.7)',
                                'rgba(255, 140, 90, 1)',
                                'rgba(224, 90, 44, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(75, 192, 192, 1)'
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        return `${label}: ${value}`;
                                    }
                                }
                            }
                        }
                    }
                });
                
                // Semester Summary Chart (Polar Area Chart)
                const semesterSummaryCtx = document.getElementById('semesterSummaryChart').getContext('2d');
                const semesterSummaryChart = new Chart(semesterSummaryCtx, {
                    type: 'polarArea',
                    data: {
                        labels: ['SGPA', 'CGPA', 'Total Marks', 'Percentage'],
                        datasets: [{
                            data: [
                                parseFloat(chartData.semesterData.sgpa),
                                parseFloat(chartData.semesterData.cgpa),
                                parseFloat(chartData.semesterData.total_marks),
                                parseFloat(chartData.semesterData.percentage)
                            ],
                            backgroundColor: [
                                'rgba(255, 208, 53, 0.7)',
                                'rgba(27, 236, 255, 0.7)',
                                'rgba(255, 71, 206, 0.7)',
                                'rgba(255, 140, 90, 0.7)'
                            ],
                            borderColor: [
                                'rgb(253, 176, 24)',
                                'rgb(27, 175, 255)',
                                'rgb(243, 28, 146)',
                                'rgba(255, 140, 90, 1)'
                            ],
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            r: {
                                pointLabels: {
                                    display: true,
                                    centerPointLabels: true,
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'right',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.raw || 0;
                                        return `${label}: ${value.toFixed(2)}`;
                                    }
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
            
            // Resize charts when window resizes
            window.addEventListener('resize', function() {
                sgpaChart.resize();
                cgpaChart.resize();
                <?php if ($selected_semester && isset($result_data)): ?>
                    subjectMarksChart.resize();
                    marksDistributionChart.resize();
                    semesterSummaryChart.resize();
                <?php endif; ?>
            });
        </script>
    </div>
</body>
</html>