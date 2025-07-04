<?php
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: reset_password.php?email=" . urlencode($email));
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <form method="POST">
        <h2>Forgot Password</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <input type="email" name="email" required placeholder="Enter your email">
        <button type="submit">Next</button>
    </form>
</body>
</html>
