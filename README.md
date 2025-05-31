# EDUMARKS - A Student Marklist Management System

EDU MARKS offers a seamless experience for administrators and students alike, enabling easy class and result management, personalized performance tracking, and secure student access. With real-time performance analytics and graph-based insights, EDU MARKS empowers data-driven academic decisions.

## ✨ Key Features

🏫 Effortless Class Management: Create and manage multiple classes with ease.

👨‍🎓 Secure Student Data Handling: Add, update, and maintain student records safely.

📝 Dynamic Result Management: Admins can create, update, or delete results.

📊 Class-Wise Performance Analytics: Gain insights through data-driven analysis.

🔐 Student Login System: Students can log in to view personalized academic results.

📈 Performance Graphs: Track individual progress with visual analytics using Chart.js.

🔁 Re-Evaluation Requests: Students can request re-evaluation of results.

✔️ Admin Review Panel: Admins can accept or reject re-evaluation requests.

## 🧱 Tech Stack

![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)
![PhpMyAdmin](https://img.shields.io/badge/PhpMyAdmin-%23FF8000.svg?style=for-the-badge&logo=phpmyadmin&logoColor=white)
![HTML5](https://img.shields.io/badge/html5-%23E34F26.svg?style=for-the-badge&logo=html5&logoColor=white)
![CSS](https://img.shields.io/badge/css-%2300C4CC.svg?style=for-the-badge&logo=css&logoColor=white)
![JavaScript](https://img.shields.io/badge/javascript-%23323330.svg?style=for-the-badge&logo=javascript&logoColor=%23F7DF1E)
![MySQL](https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white)
![Visual Studio Code](https://img.shields.io/badge/Visual%20Studio%20Code-0078d7.svg?style=for-the-badge&logo=visual-studio-code&logoColor=white)
![GitHub](https://img.shields.io/badge/github-%23121011.svg?style=for-the-badge&logo=github&logoColor=white)
![Git](https://img.shields.io/badge/git-%23F05033.svg?style=for-the-badge&logo=git&logoColor=white)

## 🧱 System Architecture 

```mermaid
graph TD

    1499["User<br>External Actor"]
    subgraph 1490["External Systems"]
        1498["Application Database<br>MySQL"]
    end
    subgraph 1491["EduMarks Web Application<br>PHP, MySQL, JS, CSS"]
        1492["Application Start Page<br>PHP"]
        1493["Authentication Module<br>PHP"]
        1494["Database Configuration<br>PHP"]
        1495["Admin Dashboard<br>PHP"]
        1496["Student Dashboard<br>PHP"]
        1497["Academic Management Services<br>PHP, JS Code Directory"]
        %% Edges at this level (grouped by source)
        1492["Application Start Page<br>PHP"] -->|navigates to| 1493["Authentication Module<br>PHP"]
        1495["Admin Dashboard<br>PHP"] -->|initiates logout via| 1493["Authentication Module<br>PHP"]
        1495["Admin Dashboard<br>PHP"] -->|manages data via| 1497["Academic Management Services<br>PHP, JS Code Directory"]
        1493["Authentication Module<br>PHP"] -->|uses| 1494["Database Configuration<br>PHP"]
        1493["Authentication Module<br>PHP"] -->|grants Admin access to| 1495["Admin Dashboard<br>PHP"]
        1493["Authentication Module<br>PHP"] -->|grants Student access to| 1496["Student Dashboard<br>PHP"]
        1497["Academic Management Services<br>PHP, JS Code Directory"] -->|uses| 1494["Database Configuration<br>PHP"]
        1496["Student Dashboard<br>PHP"] -->|views data via| 1497["Academic Management Services<br>PHP, JS Code Directory"]
    end
    %% Edges at this level (grouped by source)
    1499["User<br>External Actor"] -->|interacts with| 1492["Application Start Page<br>PHP"]
    1494["Database Configuration<br>PHP"] -->|connects to| 1498["Application Database<br>MySQL"]

```
