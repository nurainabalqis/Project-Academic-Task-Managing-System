<?php
// includes/db.php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "academic_task_db";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    // If database doesn't exist, show installation link
    if ($conn->connect_errno == 1049) {
        die("Database not found. Please run the <a href='install.php'>installer</a> first.");
    }
    die("Connection failed: " . $conn->connect_error);
}
?>