<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only students can add tasks for themselves
if ($_SESSION['role'] !== 'student') {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $subject = $_POST['subject'];
    $type = $_POST['type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $file_name = '';
    
    // Handle file upload
    if (!empty($_FILES['file']['name'])) {
        $targetDir = "assets/uploads/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $file_name = basename($_FILES["file"]["name"]);
        $targetFilePath = $targetDir . uniqid() . "_" . $file_name;
        
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFilePath)) {
            $file_name = $targetFilePath;
        } else {
            $_SESSION['error'] = "File upload failed";
        }
    }

    $stmt = $conn->prepare("INSERT INTO tasks 
        (user_id, subject, type, title, description, due_date, priority, status, file, assigned_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)");
    
    $stmt->bind_param("issssssss", 
        $user_id, 
        $subject, 
        $type, 
        $title, 
        $description, 
        $due_date, 
        $priority, 
        $status, 
        $file_name
    );


    if ($stmt->execute()) {
        $_SESSION['success'] = "Task added successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error adding task: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js"></script>
</head>
<body>
    <div class="container">
        <a href="dashboard.php">
            <button class="btn-back">← Back to Dashboard</button>
        </a>
        <h2>Add New Task</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form name="taskForm" method="POST" onsubmit="return validateTaskForm();" enctype="multipart/form-data">
            <div class="form-group">
                <label for="subject">Subject/Course:</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="type">Task Type:</label>
                <select id="type" name="type" required>
                    <option value="Assignment">Assignment</option>
                    <option value="Exam">Exam</option>
                    <option value="Study Session">Study Session</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date:</label>
                <input type="datetime-local" id="due_date" name="due_date" required>
            </div>
            
            <div class="form-group">
                <label for="priority">Priority:</label>
                <select id="priority" name="priority" required>
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Not Started">Not Started</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="file">Attachment:</label>
                <input type="file" id="file" name="file">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn-submit">Create Task</button>
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>