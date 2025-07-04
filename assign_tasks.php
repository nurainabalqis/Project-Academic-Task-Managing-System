<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only lecturers can access
if ($_SESSION['role'] !== 'lecturer') {
    header("Location: dashboard.php");
    exit();
}

// Get all students
$students_result = $conn->query("SELECT id, name FROM users WHERE role='student'");
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

// Get courses by the lecturer
$lecturer_id = $_SESSION['user_id'];
$courses_result = $conn->query("SELECT id, course_code, course_name FROM courses WHERE lecturer_id = $lecturer_id");
$courses = [];
while ($row = $courses_result->fetch_assoc()) {
    $courses[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $subject = $_POST['subject'];
    $type = $_POST['type'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $priority = $_POST['priority'];
    $course_id = $_POST['course_id'];
    
    if (!empty($_POST['student_ids']) && is_array($_POST['student_ids'])) {
        /*$title = $_POST['title'];
        $subject = $_POST['subject'];
        $type = $_POST['type'];
        $description = $_POST['description'];
        $due_date = $_POST['due_date'];
        $priority = $_POST['priority'];*/
    
        $successCount = 0;
        $errorCount = 0;
    
        foreach ($_POST['student_ids'] as $student_id) {
            $stmt = $conn->prepare("INSERT INTO tasks 
                        (user_id, assigned_by, subject, type, title, description, due_date, priority, status, course_id) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Not Started', ?)");

            $stmt->bind_param("iissssssi", 
            $student_id, 
            $_SESSION['user_id'], 
            $subject, 
            $type, 
            $title, 
            $description, 
            $due_date, 
            $priority,
            $course_id
            );
    
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }
    
        if ($errorCount > 0) {
            $error = "Assigned to $successCount students, but failed for $errorCount.";
        } else {
            $success = "Task successfully assigned to $successCount students!";
        }
    } else {
        $error = "Please select at least one student.";
    }
    
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Assign Tasks</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const assignToAllCheckbox = document.getElementById('assign_to_all');
        const studentSelect = document.getElementById('student_id');
        
        assignToAllCheckbox.addEventListener('change', function() {
            studentSelect.disabled = this.checked;
            studentSelect.required = !this.checked;
        });
    });
    </script>

    <script>
        function toggleAllStudents(masterCheckbox) {
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
        }
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const courseSelect = document.getElementById("course_id");
        const studentList = document.getElementById("student-list");

        courseSelect.addEventListener("change", function() {
            const courseId = this.value;

            if (!courseId) {
                studentList.innerHTML = "<p>Please select a course to load students.</p>";
                return;
            }

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
                        html += `
                            <div style="display: inline-flex; align-items: center; gap: 5px; margin-bottom: 5px;">
                                <input type="checkbox" name="student_ids[]" value="${student.id}" id="student_${student.id}" checked>
                                <label for="student_${student.id}">${student.name}</label>
                            </div>
                        `;
                    });

                    studentList.innerHTML = html;
                })
                .catch(() => {
                    studentList.innerHTML = "<p>Error loading students.</p>";
                });
        });
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
        <h2>Assign Task to Student</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Task Title:</label>
                <input type="text" id="title" name="title" required>
            </div>
            
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
                <div id="student-list">
                    <p>Please select a course to load students...</p>
                </div>

            </div>

            <div class="form-group">
                <label for="course_id">Select Course:</label>
                <select name="course_id" id="course_id" required>
                    <option value="">-- Select a Course --</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>">
                            <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['course_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <a href=dashboard.php><button type="submit" class="btn-submit">Assign Task</button></a>
                <a href="dashboard.php" class="btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>