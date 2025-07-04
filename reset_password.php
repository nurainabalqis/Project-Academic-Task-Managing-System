<?php
include 'includes/db.php';

$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pass = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($new_pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email);
        if ($stmt->execute()) {
            $success = "Password reset successful. You can now <a href='index.php'>log in</a>.";
        } else {
            $error = "Failed to reset password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <form method="POST">
        <h2>Reset Password for <?= htmlspecialchars($email) ?></h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <input type="password" name="password" required placeholder="New Password">
        <input type="password" name="confirm" required placeholder="Confirm New Password">
        <button type="submit">Reset Password</button>
    </form>
</body>
</html>
