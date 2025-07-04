<?php
include 'includes/db.php';

if (!isset($_GET['course_id'])) {
    echo json_encode([]);
    exit;
}

$course_id = intval($_GET['course_id']);

$query = $conn->prepare("SELECT users.id, users.name 
                         FROM users 
                         INNER JOIN course_enrollments ON users.id = course_enrollments.student_id 
                         WHERE course_enrollments.course_id = ? AND users.role = 'student'");
$query->bind_param("i", $course_id);
$query->execute();
$result = $query->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode($students);
?>
