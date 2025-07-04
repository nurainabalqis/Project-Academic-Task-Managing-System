<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $pass = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email=? AND role=?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashedPassword, $db_role);

    if ($stmt->num_rows > 0 && $stmt->fetch() && password_verify($pass, $hashedPassword)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $db_role;
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid login credentials or role selection.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="system-title">
        <img src="img/logo.png" alt="Academic Task Management System" style="max-width: 500px; height: auto;">
    </div>

    <div class="login-container">
        <form method="POST" action="">
            <h1 style="color: #2f4156;"><b><i>LOG IN</i></b></h1>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <?php if (isset($_GET['success'])) echo "<p class='success'>Registration successful! Please login.</p>"; ?>

            <input type="email" name="email" required placeholder="Email">
            <input type="password" name="password" required placeholder="Password">
            
            <label for="role">Select Role:</label>
            <div class="center-select" align=center>
                <label for="role">Role:</label>
                <select name="role" id="role" required>
                    <option value="student">Student</option>
                    <option value="lecturer">Lecturer</option>
                    <option value="academic_coordinator">Academic Coordinator</option>
                </select>
            </div>


            <button type="submit">Login</button>
            <p>Don't have an account? <a href="register.php">Register</a></p>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
        </form>
    </div>
</body>
</html>
