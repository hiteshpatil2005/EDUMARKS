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
$selected_semester = null;
$search_query = '';

// Fetch all classes
$classes = $conn->query("SELECT * FROM classes ORDER BY year DESC, class_name ASC");

// Handle class selection
if (isset($_GET['class_id'])) {
    $class_id = intval($_GET['class_id']);
    $selected_class = $conn->query("SELECT * FROM classes WHERE id = $class_id")->fetch_assoc();
    
    // Fetch semesters for this class
    $semesters = $conn->query("SELECT * FROM semesters ORDER BY id ASC");
}

// Handle semester selection
if (isset($_GET['semester_id'])) {
    $semester_id = intval($_GET['semester_id']);
    $selected_semester = $conn->query("SELECT * FROM semesters WHERE id = $semester_id")->fetch_assoc();
    
    // Get top 5 toppers by CGPA (overall performance)
    $top_toppers_cgpa = $conn->query("
        SELECT s.full_name, s.roll_no, AVG(r.sgpa) as cgpa
        FROM students s
        JOIN results r ON s.id = r.student_id
        WHERE s.class_id = {$selected_class['id']}
        GROUP BY s.id
        ORDER BY cgpa DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get top 5 toppers by SGPA (this semester only)
    $top_toppers_sgpa = $conn->query("
        SELECT s.full_name, s.roll_no, r.sgpa
        FROM students s
        JOIN results r ON s.id = r.student_id
        WHERE s.class_id = {$selected_class['id']}
        AND r.semester_id = $semester_id
        ORDER BY r.sgpa DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    
    // Get pass/fail statistics for this semester
    $pass_fail_stats = $conn->query("
        SELECT 
            COUNT(DISTINCT CASE WHEN r.status = 'Pass' THEN s.id END) as pass_count,
            COUNT(DISTINCT CASE WHEN r.status = 'Fail' THEN s.id END) as fail_count,
            COUNT(DISTINCT s.id) as total_students
        FROM students s
        LEFT JOIN results r ON s.id = r.student_id AND r.semester_id = $semester_id
        WHERE s.class_id = {$selected_class['id']}
    ")->fetch_assoc();
    
    // Get subject-wise pass percentage for this semester
    $subject_stats = $conn->query("
        SELECT 
            sub.id as subject_id,
            sub.subject_name,
            COUNT(CASE WHEN m.marks_obtained >= 40 THEN 1 END) as pass_count,
            COUNT(m.id) as total_count,
            (COUNT(CASE WHEN m.marks_obtained >= 40 THEN 1 END) * 100.0 / COUNT(m.id)) as pass_percentage
        FROM subjects sub
        JOIN marks m ON sub.id = m.subject_id
        JOIN students s ON m.student_id = s.id
        JOIN results r ON s.id = r.student_id AND r.semester_id = m.semester_id
        WHERE s.class_id = {$selected_class['id']}
        AND m.semester_id = $semester_id
        GROUP BY sub.id
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Result Analysis</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-orange: #e67e22;
            --secondary-orange: #FF9E1B;
            --dark-orange: #d35400;
            --light-orange: #FFB347;
            --pale-orange: #FFE8D6;
            --white: #FFFFFF;
            --dark-gray: #333333;
            --medium-gray: #777777;
            --light-gray: #F5F5F5;
            --lighter-gray: #F9F9F9;
            
            /* Vibrant chart colors */
            --chart-blue: #4285F4;
            --chart-green: #0F9D58;
            --chart-yellow: #F4B400;
            --chart-red: #DB4437;
            --chart-purple: #9C27B0;
            --chart-teal: #009688;
            --chart-cyan: #00BCD4;
            --chart-pink: #E91E63;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--pale-orange);
        }

        .header h1 {
            color: var(--primary-orange);
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Sidebar with class cards */
        .sidebar {
            background-color: var(--light-gray);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--pale-orange);
        }

        .sidebar-header h2 {
            color: var(--primary-orange);
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .class-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-orange);
        }

        .class-card:hover {
            transform: translateX(5px);
            box-shadow: 0 6px 15px rgba(230, 126, 34, 0.2);
        }

        .class-card.active {
            background-color: var(--pale-orange);
            border-left: 4px solid var(--dark-orange);
        }

        .class-card h3 {
            color: var(--primary-orange);
            margin-bottom: 5px;
        }

        .class-card p {
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        /* Semester container */
        .semester-container {
            margin-top: 30px;
        }

        .semester-container h2 {
            color: var(--primary-orange);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .semester-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .semester-card:hover {
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
        }

        .semester-info h3 {
            color: var(--dark-gray);
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* Analysis specific styles */
        .analysis-section {
            margin-top: 30px;
        }

        .analysis-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }

        .analysis-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .analysis-card h3 {
            color: var(--primary-orange);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        .chart-container {
            height: 300px;
            margin-top: 20px;
            position: relative;
        }

        .small-chart-container {
            height: 200px;
            width: 200px;
            margin: 0 auto;
        }

        /* Enhanced Toppers Table */
        .toppers-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .toppers-table thead {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
        }

        .toppers-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .toppers-table tbody tr {
            transition: all 0.3s ease;
        }

        .toppers-table tbody tr:nth-child(even) {
            background-color: var(--pale-orange);
        }

        .toppers-table tbody tr:hover {
            background-color: #FFD5B8;
            transform: translateX(5px);
        }

        .toppers-table td {
            padding: 15px;
            border-bottom: 1px solid #FFE8D6;
            position: relative;
        }

        .toppers-table td:first-child::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-orange);
        }

        .rank-badge {
            display: inline-block;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: white;
            text-align: center;
            line-height: 28px;
            font-weight: bold;
            margin-right: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .cgpa-badge {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        .sgpa-badge {
            background: linear-gradient(135deg, #2196F3, #64B5F6);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            min-width: 60px;
            text-align: center;
        }

        /* Enhanced Subject Cards */
        .subject-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .subject-card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 4px solid var(--chart-blue);
        }

        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }

        .subject-card h4 {
            color: var(--dark-gray);
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Different border colors for subject cards */
        .subject-card:nth-child(8n+1) { border-top-color: var(--chart-blue); }
        .subject-card:nth-child(8n+2) { border-top-color: var(--chart-green); }
        .subject-card:nth-child(8n+3) { border-top-color: var(--chart-yellow); }
        .subject-card:nth-child(8n+4) { border-top-color: var(--chart-red); }
        .subject-card:nth-child(8n+5) { border-top-color: var(--chart-purple); }
        .subject-card:nth-child(8n+6) { border-top-color: var(--chart-teal); }
        .subject-card:nth-child(8n+7) { border-top-color: var(--chart-cyan); }
        .subject-card:nth-child(8n+8) { border-top-color: var(--chart-pink); }

        .subject-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .value {
            font-weight: bold;
            color: var(--dark-gray);
            font-size: 1.1rem;
        }

        .stat-item .label {
            font-size: 0.8rem;
            color: var(--medium-gray);
            margin-top: 5px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-orange), var(--secondary-orange));
            color: var(--white);
            box-shadow: 0 4px 8px rgba(230, 126, 34, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-orange), var(--primary-orange));
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(230, 126, 34, 0.4);
        }

        .btn-secondary {
            background-color: var(--light-gray);
            color: var(--dark-gray);
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .semester-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .subject-cards {
                grid-template-columns: 1fr;
            }
            
            .analysis-card {
                padding: 20px;
            }
            
            .toppers-table th, 
            .toppers-table td {
                padding: 12px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chart-pie"></i> Result Analysis Dashboard</h1>
        </div>

        <div style="display: flex; justify-content: flex-end; padding: 10px;">
  <a href="adminHome.php" 
     style="display: inline-block; margin-top: 20px; margin-bottom: 20px; padding: 8px 24px; background-color: white; color: #FF6600; border: 3px solid #FF6600; border-radius: 15px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
    <i class="fas fa-arrow-left"></i> Back
  </a>
</div>

        
        <div class="sidebar">
            <h2><i class="fas fa-chalkboard"></i> Select Class</h2>
            <?php while ($class = $classes->fetch_assoc()): ?>
                <div class="class-card <?php echo ($selected_class && $selected_class['id'] == $class['id']) ? 'active' : ''; ?>" 
                     onclick="window.location.href='resultAnalysis.php?class_id=<?php echo $class['id']; ?>'">
                    <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                    <p>Year: <?php echo htmlspecialchars($class['year']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php if ($selected_class): ?>
            <div class="semester-container">
                <h2><i class="fas fa-calendar-alt"></i> Select Semester</h2>
                
                <?php while ($semester = $semesters->fetch_assoc()): ?>
                    <div class="semester-card">
                        <div class="semester-info">
                            <h3><?php echo htmlspecialchars($semester['semester_name']); ?></h3>
                        </div>
                        
                        <div class="semester-actions">
                            <a href="resultAnalysis.php?class_id=<?php echo $selected_class['id']; ?>&semester_id=<?php echo $semester['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-chart-line"></i> Analyze Performance
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($selected_semester): ?>
            <div class="analysis-section">
                <!-- Top 5 Toppers by CGPA -->
                <div class="analysis-card">
                    <h3><i class="fas fa-trophy"></i> Top 5 Toppers (Overall CGPA)</h3>
                    <table class="toppers-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Roll No</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_toppers_cgpa as $index => $topper): ?>
                                <tr>
                                    <td><span class="rank-badge"><?php echo $index + 1; ?></span></td>
                                    <td><?php echo htmlspecialchars($topper['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($topper['roll_no']); ?></td>
                                    <td><span class="cgpa-badge"><?php echo number_format($topper['cgpa'], 2); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Top 5 Toppers by SGPA (this semester) -->
                <div class="analysis-card">
                    <h3><i class="fas fa-medal"></i> Top 5 Toppers (Semester SGPA)</h3>
                    <table class="toppers-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Name</th>
                                <th>Roll No</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_toppers_sgpa as $index => $topper): ?>
                                <tr>
                                    <td><span class="rank-badge"><?php echo $index + 1; ?></span></td>
                                    <td><?php echo htmlspecialchars($topper['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($topper['roll_no']); ?></td>
                                    <td><span class="sgpa-badge"><?php echo number_format($topper['sgpa'], 2); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pass/Fail Analysis -->
                <div class="analysis-card">
                    <h3><i class="fas fa-chart-pie"></i> Semester Pass/Fail Analysis</h3>
                    <div class="chart-container">
                        <canvas id="passFailChart"></canvas>
                    </div>
                </div>
                
                <!-- Subject-wise Performance -->
                <div class="analysis-card">
                    <h3><i class="fas fa-book-open"></i> Subject-wise Performance</h3>
                    <div class="subject-cards">
                        <?php foreach ($subject_stats as $subject): ?>
                            <div class="subject-card">
                                <h4><?php echo htmlspecialchars($subject['subject_name']); ?></h4>
                                <div class="small-chart-container">
                                    <canvas id="subjectChart_<?php echo $subject['subject_id']; ?>"></canvas>
                                </div>
                                <div class="subject-stats">
                                    <div class="stat-item">
                                        <div class="value"><?php echo $subject['pass_count']; ?></div>
                                        <div class="label">Passed</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="value"><?php echo ($subject['total_count'] - $subject['pass_count']); ?></div>
                                        <div class="label">Failed</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="value"><?php echo number_format($subject['pass_percentage'], 1); ?>%</div>
                                        <div class="label">Pass %</div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <script>
                // Color palette for charts
                const chartColors = [
                    { bg: 'rgba(66, 133, 244, 0.8)', border: 'rgba(66, 133, 244, 1)' },    // Blue
                    { bg: 'rgba(15, 157, 88, 0.8)', border: 'rgba(15, 157, 88, 1)' },      // Green
                    { bg: 'rgba(244, 180, 0, 0.8)', border: 'rgba(244, 180, 0, 1)' },      // Yellow
                    { bg: 'rgba(219, 68, 55, 0.8)', border: 'rgba(219, 68, 55, 1)' },      // Red
                    { bg: 'rgba(156, 39, 176, 0.8)', border: 'rgba(156, 39, 176, 1)' },    // Purple
                    { bg: 'rgba(0, 150, 136, 0.8)', border: 'rgba(0, 150, 136, 1)' },     // Teal
                    { bg: 'rgba(0, 188, 212, 0.8)', border: 'rgba(0, 188, 212, 1)' },     // Cyan
                    { bg: 'rgba(233, 30, 99, 0.8)', border: 'rgba(233, 30, 99, 1)' }      // Pink
                ];

                // Pass/Fail Donut Chart
                const passFailCtx = document.getElementById('passFailChart').getContext('2d');
                const passFailChart = new Chart(passFailCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Pass', 'Fail'],
                        datasets: [{
                            data: [
                                <?php echo $pass_fail_stats['pass_count']; ?>,
                                <?php echo $pass_fail_stats['fail_count']; ?>
                            ],
                            backgroundColor: [
                                chartColors[0].bg,
                                chartColors[3].bg
                            ],
                            borderColor: [
                                chartColors[0].border,
                                chartColors[3].border
                            ],
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    font: {
                                        size: 14,
                                        family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                                    },
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0,0,0,0.85)',
                                titleFont: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                bodyFont: {
                                    size: 14
                                },
                                callbacks: {
                                    label: function(context) {
                                        const total = <?php echo $pass_fail_stats['total_students']; ?>;
                                        const value = context.raw;
                                        const percentage = Math.round((value / total) * 100);
                                        return `${context.label}: ${value} (${percentage}%)`;
                                    }
                                }
                            }
                        },
                        cutout: '70%',
                        animation: {
                            animateScale: true,
                            animateRotate: true
                        }
                    }
                });

                // Create donut charts for each subject
                <?php foreach ($subject_stats as $index => $subject): ?>
                    const ctx_<?php echo $subject['subject_id']; ?> = document.getElementById('subjectChart_<?php echo $subject['subject_id']; ?>').getContext('2d');
                    const colorIndex_<?php echo $subject['subject_id']; ?> = <?php echo $index % 8; ?>;
                    new Chart(ctx_<?php echo $subject['subject_id']; ?>, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pass', 'Fail'],
                            datasets: [{
                                data: [
                                    <?php echo $subject['pass_count']; ?>,
                                    <?php echo ($subject['total_count'] - $subject['pass_count']); ?>
                                ],
                                backgroundColor: [
                                    chartColors[colorIndex_<?php echo $subject['subject_id']; ?>].bg,
                                    'rgba(206, 206, 206, 0.8)'
                                ],
                                borderColor: [
                                    chartColors[colorIndex_<?php echo $subject['subject_id']; ?>].border,
                                    'rgba(206, 206, 206, 1)'
                                ],
                                borderWidth: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(0,0,0,0.85)',
                                    callbacks: {
                                        label: function(context) {
                                            const percentage = Math.round((context.raw / <?php echo $subject['total_count']; ?>) * 100);
                                            return `${context.label}: ${context.raw} (${percentage}%)`;
                                        }
                                    }
                                }
                            },
                            cutout: '75%',
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            }
                        }
                    });
                <?php endforeach; ?>
            </script>
        <?php endif; ?>
    </div>
</body>
</html>