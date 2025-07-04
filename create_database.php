<?php
$servername = "localhost";
$username = "root";
$password = "";

// Connect to MySQL server (no DB yet)
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database
$sql = "CREATE DATABASE IF NOT EXISTS academic_task_db";
if ($conn->query($sql) === TRUE) {
    echo "Database 'academic_task_db' created successfully or already exists.";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->close();
?>