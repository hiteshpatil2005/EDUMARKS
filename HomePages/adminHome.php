<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EDU MARKS - Admin Dashboard</title>
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"
    />
    <style>
      /* Base Styles */
      :root {
        --primary-color: #e67e22;
        --primary-light: #f5923e;
        --primary-dark: #c2410c;
        --text-color: #333;
        --light-gray: #f5f5f5;
        --white: #ffffff;
        --shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
      }

      body {
        font-family: "Roboto", sans-serif;
        background-color: var(--light-gray);
        color: var(--text-color);
        line-height: 1.6;
      }

      /* Loading Animation */
      .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        transition: opacity 0.5s ease;
      }

      .loader {
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--primary-color);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
      }

      @keyframes spin {
        0% {
          transform: rotate(0deg);
        }
        100% {
          transform: rotate(360deg);
        }
      }

      /* Header Styles */
      .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--white);
        padding: 15px 5%;
        box-shadow: var(--shadow);
        position: sticky;
        top: 0;
        z-index: 100;
      }

      .site-name {
        color: var(--primary-color);
        font-size: 2rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .admin-badge {
        background: var(--primary-color);
        color: white;
        font-size: 0.8rem;
        padding: 3px 10px;
        border-radius: 20px;
        margin-left: 10px;
      }

      .nav-buttons {
        display: flex;
        gap: 15px;
      }

      .nav-btn {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
        padding: 8px 20px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .nav-btn:hover {
        background: linear-gradient(
          135deg,
          var(--primary-color),
          var(--primary-light)
        );
        color: var(--white);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(243, 142, 47, 0.3);
      }

      .nav-btn.active {
        background: linear-gradient(
          135deg,
          var(--primary-color),
          var(--primary-light)
        );
        color: var(--white);
      }

      .logout-btn:hover {
        background: #e74c3c;
        border-color: #e74c3c;
      }

      /* Hero Section */
      .hero {
        background: linear-gradient(
          135deg,
          var(--primary-color),
          var(--primary-light)
        );
        padding:30px;
        color: white;
      }

      .hero-content {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
      }

      .hero-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 15px;
      }

      .hero-subtitle {
        font-size: 1.2rem;
        opacity: 0.9;
        margin-bottom: 25px;
      }

      .hero-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
      }

      .hero-btn {
        padding: 12px 25px;
        border-radius: 25px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s ease;
      }

      .hero-btn-primary {
        background: white;
        color: var(--primary-color);
      }

      .hero-btn-primary:hover {
        background: #fed7aa;
      }

      .hero-btn-outline {
        border: 2px solid white;
        color: white;
      }

      .hero-btn-outline:hover {
        background: rgba(255, 255, 255, 0.1);
      }

      /* Features Section */
      .features {
        padding: 60px 5%;
        /* max-width: 1500px; */
        margin: 0 auto;
      }

      .section-title {
        text-align: center;
        font-size: 2rem;
        color: var(--primary-dark);
        margin-bottom: 40px;
      }

      .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
      }

      .feature-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
      }

      .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
      }

      .feature-card-header {
        background: linear-gradient(
          135deg,
          var(--primary-color),
          var(--primary-light)
        );
        padding: 30px;
        display: flex;
        justify-content: center;
      }

      .feature-icon {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
      }

      .feature-card:hover .feature-icon {
        background: rgba(255, 255, 255, 0.3);
      }

      .feature-card-body {
        padding: 25px;
      }

      .feature-title {
        font-size: 1.3rem;
        color: var(--primary-dark);
        margin-bottom: 15px;
      }

      .feature-description {
        color: #666;
        margin-bottom: 20px;
      }

      .feature-link {
        color: var(--primary-color);
        font-weight: 500;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      /* How It Works Section */
      .how-it-works {
        padding: 60px 5%;
        background: white;
      }

      .timeline {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
      }

      .timeline-line {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        width: 2px;
        height: 100%;
        background: #fdba74;
      }

      .timeline-step {
        position: relative;
        margin-bottom: 60px;
      }

      .step-content {
        background: white;
        border-left: 4px solid var(--primary-color);
        padding: 20px;
        border-radius: 5px;
        box-shadow: var(--shadow);
        max-width: 350px;
      }

      .step-number {
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        background: var(--primary-color);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        border: 4px solid white;
      }

     

      /* Footer */
      .footer {
        background: #eb852c;
        color: white;
        padding: 40px 5%;
      }

      .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 30px;
        margin-bottom: 30px;
      }

      .footer-links h4 {
        font-size: 1.1rem;
        margin-bottom: 15px;
      }

      .footer-links ul {
        list-style: none;
      }

      .footer-links li {
        margin-bottom: 10px;
      }

      .footer-links a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
      }

      .footer-links a:hover {
        color: white;
      }

      .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.2);
      }

      .social-links {
        display: flex;
        gap: 15px;
      }

      .social-links a {
        color: rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
      }

      .social-links a:hover {
        color: white;
      }

      /* Responsive Design */
      @media (max-width: 768px) {
        .header {
          flex-direction: column;
          gap: 15px;
        }

        .nav-buttons {
          width: 100%;
          justify-content: center;
          flex-wrap: wrap;
        }

        .hero-content {
          text-align: center;
          flex-direction: column;
        }

        .hero-buttons {
          justify-content: center;
        }

        .timeline-line {
          left: 20px;
        }

        .step-content {
          margin-left: 50px;
          max-width: 100%;
        }

        .step-number {
          left: 20px;
        }
      }
    </style>
  </head>
  <body>
    <!-- Loading Animation -->
    <div class="loading-overlay">
      <div class="loader"></div>
    </div>

    <header class="header">
      <h1 class="site-name">
        EDU MARKS <span class="admin-badge">Admin</span>
      </h1>
      <div class="nav-buttons">
        <button class="nav-btn active"><i class="fas fa-home"></i> Home</button>
        <button class="nav-btn">
          <i class="fas fa-envelope"></i> Re-Evaluation
        </button>
        <form method="POST" action="../LoginPages/logoutAdmin.php" style="display: inline;">
  <button type="submit" class="nav-btn logout-btn">
    <i class="fas fa-sign-out-alt"></i> Logout
  </button>
</form>

      </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
      <div class="hero-content">
        <div>
          <h1 class="hero-title">Revolutionizing Educational Assessment</h1>
          <p class="hero-subtitle">
            The complete solution for managing student results, analyzing
            performance, and driving educational excellence.
          </p>
          <div class="hero-buttons">
            <a href="#features" class="hero-btn hero-btn-primary"
              >Explore Features</a
            >
            <a href="#how-it-works" class="hero-btn hero-btn-outline"
              >How It Works</a
            >
          </div>
        </div>
        <div>
          <img
            src="../Assets/Logo.png"
            alt="Hero Image"
            height="400"
            width="400"
            style="filter: brightness(0%) invert(1)"
          />
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
      <h2 class="section-title">Our Powerful Features</h2>
      <div class="cards-container">
        <!-- Feature 1 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-chalkboard-teacher fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">Create Class</h3>
            <p class="feature-description">
              Easily set up and organize classes with customizable parameters to
              match your institution's structure.
            </p>
            <a href="createClass.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-user-graduate fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">Add Students</h3>
            <p class="feature-description">
              Register and manage student profiles with comprehensive
              information and class assignments.
            </p>
            <a href="addStudents.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-book fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">Add Subjects</h3>
            <p class="feature-description">
              Create and customize subjects with detailed information, grading
              scales, and assessment criteria.
            </p>
            <a href="addSubjects.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 4 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-tasks fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">Create Result</h3>
            <p class="feature-description">
              Generate comprehensive result records with flexible grading
              options and automated calculations.
            </p>
            <a href="createResult.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 5 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-search fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">See Result</h3>
            <p class="feature-description">
              Access and view detailed results with intuitive interfaces for
              administrators, teachers, and students.
            </p>
            <a href="displayResult.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>

        <!-- Feature 6 -->
        <div class="feature-card">
          <div class="feature-card-header">
            <div class="feature-icon">
              <i class="fas fa-chart-bar fa-2x"></i>
            </div>
          </div>
          <div class="feature-card-body">
            <h3 class="feature-title">Result Analysis</h3>
            <p class="feature-description">
              Gain valuable insights with advanced analytics tools,
              visualizations, and performance tracking.
            </p>
            <a href="resultAnalysis.php" class="feature-link">
              Get Started <i class="fas fa-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works" id="how-it-works">
      <h2 class="section-title">How It Works</h2>
      <div class="timeline">
        <div class="timeline-line"></div>

        <!-- Step 1 -->
        <div class="timeline-step">
          <div class="step-number">1</div>
          <div class="step-content" style="margin-left: auto; margin-right: 0">
            <h3>Create Classes & Add Subjects</h3>
            <p>
              Set up your educational structure by creating classes and adding
              relevant subjects.
            </p>
          </div>
        </div>

        <!-- Step 2 -->
        <div class="timeline-step">
          <div class="step-number">2</div>
          <div class="step-content">
            <h3>Add Students</h3>
            <p>
              Register students with their details and assign them to
              appropriate classes.
            </p>
          </div>
        </div>

        <!-- Step 3 -->
        <div class="timeline-step">
          <div class="step-number">3</div>
          <div class="step-content" style="margin-left: auto; margin-right: 0">
            <h3>Create & Enter Results</h3>
            <p>
              Create examination records and enter student results efficiently.
            </p>
          </div>
        </div>

        <!-- Step 4 -->
        <div class="timeline-step">
          <div class="step-number">4</div>
          <div class="step-content">
            <h3>Analyze & Improve</h3>
            <p>
              Use powerful analytics tools to gain insights and improve
              educational outcomes.
            </p>
          </div>
        </div>
      </div>
    </section>

    
    <!-- Footer -->
    <footer class="footer">
      <div class="footer-content">
        <div class="footer-links">
          <h4>EDU MARKS</h4>
          <p>
            The complete solution for educational result management and
            analytics.
          </p>
        </div>
        <div class="footer-links">
          <h4>Quick Links</h4>
          <ul>
            <li><a href="#">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#how-it-works">How It Works</a></li>
            <li><a href="#">Contact</a></li>
          </ul>
        </div>
        <div class="footer-links">
          <h4>Resources</h4>
          <ul>
            <li><a href="#">Documentation</a></li>
            <li><a href="#">Tutorials</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Support</a></li>
          </ul>
        </div>
        <div class="footer-links">
          <h4>Contact Us</h4>
          <address>
            <p>123 Education Street</p>
            <p>Learning City, ED 12345</p>
            <p>info@edumarks.com</p>
            <p>+1 (555) 123-4567</p>
          </address>
        </div>
      </div>
      <div class="footer-bottom">
        <p>© 2025 EDU MARKS Result Management System</p>
        <div class="social-links">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
    </footer>

    <script>
      // Loading animation
      window.addEventListener("load", function () {
        document.querySelector(".loading-overlay").style.opacity = "0";
        setTimeout(() => {
          document.querySelector(".loading-overlay").style.display = "none";
        }, 500);
      });

      // Add active class to nav buttons on click
      const navButtons = document.querySelectorAll(".nav-btn");
      navButtons.forEach((button) => {
        button.addEventListener("click", function () {
          navButtons.forEach((btn) => btn.classList.remove("active"));
          this.classList.add("active");
        });
      });

      // Smooth scrolling for anchor links
      document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
          e.preventDefault();

          const targetId = this.getAttribute("href");
          if (targetId === "#") return;

          const targetElement = document.querySelector(targetId);
          if (targetElement) {
            targetElement.scrollIntoView({
              behavior: "smooth",
            });
          }
        });
      });
    </script>
  </body>
</html>