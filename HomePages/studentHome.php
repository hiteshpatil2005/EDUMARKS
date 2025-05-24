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

$has_results_query = "SELECT COUNT(*) as count FROM results WHERE student_id = ?";
$stmt = $conn->prepare($has_results_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$has_results = $stmt->get_result()->fetch_assoc()['count'] > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EDU MARKS - Student Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #e67e22;
            --primary-light: #f5923e;
            --primary-dark: #E05A2C;
            --secondary-color: #FF9E1B;
            --accent-color: #FFB347;
            --background-color: #ffffff;
            --text-color: #2B2D42;
            --light-text: #6C757D;
            --card-bg: #ffffff;
            --card-shadow: 0 4px 20px rgba(255, 107, 53, 0.08);
            --border-radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9fafb;
            color: var(--secondary-color);
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
            margin-bottom: 3rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 107, 53, 0.1);
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
            color: var(--primary-color);
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
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
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }
        
        .user-name {
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .logout-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.2);
        }
        
        .logout-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .welcome-message {
            margin-bottom: 3rem;
        }
        
        .welcome-message h1 {
            font-size: 2.5rem;
            color: #c2410c;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .welcome-message p {
            color: var(--primary-light);
            font-size: 1.1rem;
        }
        
        .student-info {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.75rem;
            margin-bottom: 2.5rem;
            transition: transform 0.3s ease;
        }
        
        .student-info:hover {
            transform: translateY(-5px);
        }
        
        .student-info h2 {
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .student-info h2 i {
            color: var(--primary-color);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
        }
        
        .info-item {
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 3px solid var(--secondary-color);
        }
        
        .info-item:hover {
            background-color: rgba(255, 107, 53, 0.08);
            border-color: rgba(255, 107, 53, 0.2);
        }
        
        .info-label {
            font-weight: 500;
            color: var(--light-text);
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1.05rem;
        }
        
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 107, 53, 0.1);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 28px rgba(255, 107, 53, 0.12);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            width: fit-content;
        }
        
        .card:hover .card-icon {
            transform: scale(1.1) translateY(-5px);
        }
        
        .card h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #c2410c;
            font-weight: 600;
        }
        
        .card p {
            margin-bottom: 1.75rem;
            color: var(--light-text);
            line-height: 1.7;
        }
        
        .card-btn {
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
            width: 100%;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.2);
        }
        
        .card-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 107, 53, 0.3);
        }
        
        .card-btn i {
            transition: transform 0.3s ease;
        }
        
        .card-btn:hover i {
            transform: translateX(3px);
        }
        
        .pulse {
            position: relative;
            overflow: hidden;
        }
        
        .pulse::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 107, 53, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }
        
        .pulse:hover::after {
            animation: pulse-animation 1.5s ease-out infinite;
        }
        
        @keyframes pulse-animation {
            0% {
                transform: scale(0.1, 0.1) translate(-50%, -50%);
                opacity: 0;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                transform: scale(20, 20) translate(-50%, -50%);
                opacity: 0;
            }
        }
        
        .card-content {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .card-text {
            flex-grow: 1;
        }
        
        .badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background-color: var(--secondary-color);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .welcome-message h1 {
                font-size: 2rem;
            }
            
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .user-profile {
                gap: 1rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
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
                <div class="user-info">
                    <div class="user-avatar"><?php echo substr($student['full_name'], 0, 1); ?></div>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
                </div>
                <form action="../logout.php" method="post">
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </header>
        
        <section class="welcome-message">
            <h1>Welcome <?php echo htmlspecialchars($_SESSION['student_name']); ?>!</h1>
            <p>Your personalized academic dashboard with EDU MARKS</p>
        </section>
        
        <section class="student-info">
            <h2><i class="fas fa-user-graduate"></i> Student Information</h2>
            <div class="info-grid">
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
                <div class="info-item">
                    <div class="info-label">Department</div>
                    <div class="info-value"><?php echo htmlspecialchars($student['department']); ?></div>
                </div>
            </div>
        </section>
        
        <section class="cards-container">
            <div class="card <?php echo $has_results ? 'pulse' : ''; ?>">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-poll"></i>
                    </div>
                    <?php if($has_results): ?>
                       
                    <?php endif; ?>
                    <div class="card-text">
                        <h2>Exam Results</h2>
                        <p>View your exam results and grade reports with detailed breakdowns of your performance across all subjects and semesters.</p>
                    </div>
                    <a href="results.php" class="card-btn">
                        View Results <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="card <?php echo $has_results ? 'pulse' : ''; ?>">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="card-text">
                        <h2>Performance Analytics</h2>
                        <p>Interactive charts and visualizations showing your academic progress, strengths, and areas for improvement.</p>
                    </div>
                    <a href="performance.php" class="card-btn">
                        View Analytics <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            
            <div class="card <?php echo $has_results ? 'pulse' : ''; ?>">
                <div class="card-content">
                    <div class="card-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="card-text">
                        <h2>Re-Evaluation</h2>
                        <p>Submit requests for re-evaluation of your answer scripts and track the status of your applications.</p>
                    </div>
                    <a href="reevaluation.php" class="card-btn">
                        Apply Now <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </section>
    </div>
</body>
</html>