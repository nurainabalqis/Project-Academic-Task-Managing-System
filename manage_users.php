<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only coordinators can access
if ($_SESSION['role'] !== 'academic_coordinator') {
    header("Location: dashboard.php");
    exit();
}

// Get all users
$users = $conn->query("SELECT * FROM users");

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM users WHERE id=$id");
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
    <a href="dashboard.php" class="btn-back"> ← Back to Dashboard</a>
    </div>
    <h2>User Management</h2>
    
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Actions</th>
        </tr>
        <?php while ($user = $users->fetch_assoc()): ?>
        <tr>
            <td><?= $user['id'] ?></td>
            <td><?= $user['name'] ?></td>
            <td><?= $user['email'] ?></td>
            <td><?= ucfirst($user['role']) ?></td>
            <td>
                <a href="edit_user.php?id=<?= $user['id'] ?>">Edit</a> | 
                <a href="manage_users.php?delete=<?= $user['id'] ?>" 
                   onclick="return confirm('Delete this user?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>