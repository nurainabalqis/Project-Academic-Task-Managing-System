<?php
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $role  = $_POST['role'];
    $pass  = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $pass, $role);

    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit();
    } else {
        $error = "Registration failed. Email may already be in use.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <script src="js/script.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="system-title">
        <img src="img/logo.png" alt="Academic Task Management System" style="max-width: 500px; height: auto;">
    </div>
    <div class="login-container">
        <form name="registerForm" method="POST" onsubmit="return validateRegisterForm();">
            <h1 style="color: #2f4156;"><b><i>REGISTER</i></b></h1>

            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

            <input type="text" name="name" required placeholder="Full Name">
            <input type="email" name="email" required placeholder="Email">
            <input type="password" name="password" required placeholder="Password">
            <input type="password" name="confirm_password" required placeholder="Confirm Password">

            <label>Select Role:</label>
            <select name="role" required align= center>
                <option value="student">Student</option>
                <option value="lecturer">Lecturer</option>
                <option value="academic_coordinator">Academic Coordinator</option>
            </select>

            <button type="submit">Register</button>
            <p>Already have an account? <a href="index.php">Login</a></p>
        </form>
    </div>
</body>
</html>
