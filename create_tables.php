<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "academic_task_db";

// Connect directly to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected to database: $dbname<br>";

// Create 'users' table with role column
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'lecturer', 'academic_coordinator') NOT NULL DEFAULT 'student'
)";
if ($conn->query($sql_users) === TRUE) {
    echo "Users table created successfully.<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create 'tasks' table with assignment functionality
$sql_tasks = "CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    assigned_to INT NULL,
    assigned_by INT NULL,
    subject VARCHAR(100),
    type ENUM('Assignment', 'Exam', 'Study Session') DEFAULT 'Assignment',
    title VARCHAR(255),
    description TEXT,
    due_date DATETIME,  # Changed from DATE to DATETIME for more precision
    priority ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
    status ENUM('Not Started', 'In Progress', 'Completed') DEFAULT 'Not Started',
    file VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql_tasks) === TRUE) {
    echo "Tasks table created successfully.<br>";
} else {
    echo "Error creating tasks table: " . $conn->error . "<br>";
}

$conn->close();
?>