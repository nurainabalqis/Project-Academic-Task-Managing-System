<?php
// install.php
$host = "localhost";
$user = "root";
$pass = "";

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS academic_task_db";
if ($conn->query($sql)) {  // Fixed: Added parentheses around condition
    echo "Database created successfully!<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select database
$conn->select_db("academic_task_db");

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer', 'academic_coordinator') DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql) or die("Error creating users table: " . $conn->error);

// Create tasks table
$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_by INT NULL,
    assigned_to INT NULL,
    subject VARCHAR(50) NOT NULL,
    type ENUM('Assignment', 'Exam', 'Study Session') NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    file VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
)";
$conn->query($sql) or die("Error creating tasks table: " . $conn->error);

// Create courses table
$sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql) or die("Error creating courses table: " . $conn->error);

// Create course_enrollments table
$sql = "CREATE TABLE IF NOT EXISTS course_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    student_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (course_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql) or die("Error creating enrollments table: " . $conn->error);

// Create sample data
$sql = "INSERT IGNORE INTO users (name, email, password, role) VALUES
    ('John Student', 'student@uni.edu', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'student'),
    ('Dr. Smith', 'lecturer@uni.edu', '" . password_hash('lecturer123', PASSWORD_DEFAULT) . "', 'lecturer'),
    ('Admin Coordinator', 'coordinator@uni.edu', '" . password_hash('coordinator123', PASSWORD_DEFAULT) . "', 'academic_coordinator')";
$conn->query($sql);

$sql = "INSERT IGNORE INTO tasks (user_id, subject, type, title, due_date) VALUES
    (1, 'Mathematics', 'Assignment', 'Calculus Homework', NOW() + INTERVAL 7 DAY),
    (1, 'Physics', 'Exam', 'Midterm Exam', NOW() + INTERVAL 14 DAY),
    (2, 'Computer Science', 'Study Session', 'Algorithms Review', NOW() + INTERVAL 3 DAY)";
$conn->query($sql);

// Add sample courses if not exist
$sql = "INSERT IGNORE INTO courses (lecturer_id, course_code, course_name, description) VALUES
    (2, 'CS101', 'Introduction to Programming', 'Basic programming concepts and techniques'),
    (2, 'MATH201', 'Linear Algebra', 'Vector spaces and linear transformations')";
$conn->query($sql);

// Add sample enrollments
$sql = "INSERT IGNORE INTO course_enrollments (course_id, student_id) VALUES
    (1, 1), (1, 3), (2, 1)";
$conn->query($sql);

// Create uploads directory
if (!is_dir('assets/uploads')) {
    mkdir('assets/uploads', 0777, true);
}

echo "<h2>Setup Complete!</h2>";
echo "<p>Database and tables created successfully with sample data.</p>";
echo "<p><a href='index.php'>Go to Login Page</a></p>";
?>