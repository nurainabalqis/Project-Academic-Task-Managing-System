<?php
include 'includes/auth.php';
include 'includes/db.php';

// Check if user is allowed (e.g., academic coordinator only)
if ($_SESSION['role'] !== 'academic_coordinator') {
    header("Location: dashboard.php");
    exit();
}

// Get user ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$user_id = (int)$_GET['id'];

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $name, $email, $role, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "User updated successfully!";
        header("Location: manage_users.php");
        exit();
    } else {
        $error = "Error updating user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>Edit User</h2>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label>Name:</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="form-group" style="text-align: center;">
            <label>Role:</label>
            <select name="role" required style="width: 50%; text-align: center; margin: 0 auto;">
                <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                <option value="lecturer" <?= $user['role'] === 'lecturer' ? 'selected' : '' ?>>Lecturer</option>
                <option value="academic_coordinator" <?= $user['role'] === 'academic_coordinator' ? 'selected' : '' ?>>Academic Coordinator</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit">Update</button>
            <a href="manage_users.php"><button type="button">Cancel</button></a>
        </div>
    </form>
</div>
</body>
</html>
