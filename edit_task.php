<?php
include 'includes/auth.php';
include 'includes/db.php';

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Check task ownership
$task = $conn->query("SELECT * FROM tasks WHERE id=$id 
                     AND (user_id=$user_id OR assigned_to=$user_id OR assigned_by=$user_id)")->fetch_assoc();

if (!$task) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $type = $_POST['type'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $status = $_POST['status'];
    $assigned_to = !empty($_POST['student_ids']) ? $_POST['student_ids'][0] : null;


    $stmt = $conn->prepare("UPDATE tasks SET 
                          subject=?, type=?, title=?, description=?, 
                          due_date=?, priority=?, status=?, assigned_to=?
                          WHERE id=?");
    $stmt->bind_param("sssssssii", 
        $subject, $type, $title, $description, 
        $due_date, $priority, $status, $assigned_to, $id
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Task updated successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating task: " . $conn->error;
    }
}

// Get students for assignment
$students = [];
if ($_SESSION['role'] !== 'student') {
    $result = $conn->query("SELECT id, name FROM users WHERE role='student'");
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Task</title>
    <script src="js/script.js"></script>
    <link rel="stylesheet" href="css/style.css">

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const courseSelect = document.getElementById("course_id");
            const studentList = document.getElementById("student-list");
            const selectedStudents = <?= json_encode([$task['assigned_to']]) ?>;

            function loadStudents(courseId) {
                fetch("get_students_by_course.php?course_id=" + courseId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length === 0) {
                            studentList.innerHTML = "<p>No students found for this course.</p>";
                            return;
                        }

                        let html = `<div style="margin-bottom: 10px;">
                                        <input type="checkbox" id="select_all" onclick="toggleAllStudents(this)">
                                        <label for="select_all">Select All Students</label>
                                    </div>`;

                        data.forEach(student => {
                            const checked = selectedStudents.includes(student.id) ? "checked" : "";
                            html += `
                                <div style="display: inline-flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                                    <input type="checkbox" name="student_ids[]" value="${student.id}" id="student_${student.id}" ${checked}>
                                    <label for="student_${student.id}">${student.name}</label>
                                </div>
                            `;
                        });

                        studentList.innerHTML = html;
                    })
                    .catch(() => {
                        studentList.innerHTML = "<p>Error loading students.</p>";
                    });
            }

            courseSelect.addEventListener("change", function() {
                if (this.value) {
                    loadStudents(this.value);
                } else {
                    studentList.innerHTML = "<p>Please select a course to load students...</p>";
                }
            });

            if (courseSelect.value) {
                loadStudents(courseSelect.value); // auto load on edit
            }
        });

        function toggleAllStudents(masterCheckbox) {
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
        }
        </script>

</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        <h2>Edit Task</h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form name="taskForm" method="POST" onsubmit="return validateTaskForm();" enctype="multipart/form-data">
            <div class="form-group">
                <label for="subject">Subject/Course:</label>
                <input type="text" id="subject" name="subject" value="<?= htmlspecialchars($task['subject']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="type">Task Type:</label>
                <select id="type" name="type" required>
                    <option value="Assignment" <?= $task['type'] == 'Assignment' ? 'selected' : '' ?>>Assignment</option>
                    <option value="Exam" <?= $task['type'] == 'Exam' ? 'selected' : '' ?>>Exam</option>
                    <option value="Study Session" <?= $task['type'] == 'Study Session' ? 'selected' : '' ?>>Study Session</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($task['title']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($task['description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="due_date">Due Date:</label>
                <input type="datetime-local" id="due_date" name="due_date" 
                       value="<?= date('Y-m-d\TH:i', strtotime($task['due_date'])) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="priority">Priority:</label>
                <select id="priority" name="priority" required>
                    <option value="Low" <?= $task['priority'] == 'Low' ? 'selected' : '' ?>>Low</option>
                    <option value="Medium" <?= $task['priority'] == 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="High" <?= $task['priority'] == 'High' ? 'selected' : '' ?>>High</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="Not Started" <?= $task['status'] == 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                    <option value="In Progress" <?= $task['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="Completed" <?= $task['status'] == 'Completed' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>
            
            <?php if ($_SESSION['role'] !== 'student'): ?>
                <div class="form-group">
                    <label for="course_id">Select Course:</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Select a Course --</option>
                        <?php
                        $courses_result = $conn->query("SELECT id, course_code, course_name FROM courses WHERE lecturer_id = $user_id");
                        while ($course = $courses_result->fetch_assoc()):
                        ?>
                            <option value="<?= $course['id'] ?>" <?= $task['course_id'] == $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><strong>Assign to Student(s):</strong></label>
                    <div id="student-list">
                        <p>Please select a course to load students...</p>
                    </div>
                </div>
            <?php endif; ?>

            
            <div class="form-group">
                <button type="submit" class="btn-submit">Update Task</button>
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>