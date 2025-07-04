<?php
include 'includes/auth.php';
include 'includes/db.php';

$id = intval($_GET['id']); // Always sanitize GET inputs
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role === 'academic_coordinator') {
    $conn->query("DELETE FROM tasks WHERE id = $id");
} elseif ($role === 'lecturer') {
    // Lecturer can only delete tasks they assigned
    $conn->query("DELETE FROM tasks WHERE id = $id AND assigned_by = $user_id");
} else {
    // Student can delete tasks assigned to them or created by them
    $conn->query("DELETE FROM tasks WHERE id = $id AND user_id = $user_id");
}

header("Location: dashboard.php");
exit();
?>
