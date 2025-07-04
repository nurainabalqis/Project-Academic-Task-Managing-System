<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only lecturers can access
if ($_SESSION['role'] !== 'lecturer') {
    header("Location: dashboard.php");
    exit();
}

// Validate course_id
if (!isset($_GET['course_id']) {
    $_SESSION['error'] = "Course ID not specified";
    header("Location: manage_courses.php");
    exit();
}

$course_id = intval($_GET['course_id']);
$lecturer_id = $_SESSION['user_id'];

// Verify lecturer owns this course
$course_check = $conn->query("SELECT id FROM courses WHERE id = $course_id AND lecturer_id = $lecturer_id");
if ($course_check->num_rows == 0) {
    $_SESSION['error'] = "Course not found or access denied";
    header("Location: manage_courses.php");
    exit();
}

// Get course details
$course = $conn->query("SELECT course_code, course_name FROM courses WHERE id = $course_id")->fetch_assoc();

// Get all students enrolled in this course
$enrolled_students = $conn->query("
    SELECT u.id, u.name 
    FROM course_enrollments ce
    JOIN users u ON ce.student_id = u.id
    WHERE ce.course_id = $course_id
");

// Get all tasks for this course
$tasks = $conn->query("
    SELECT t.id, t.title, t.due_date, t.status, t.user_id, u.name as student_name
    FROM tasks t
    JOIN users u ON t.user_id = u.id
    WHERE t.assigned_by = $lecturer_id
    AND t.subject = '{$course['course_name']}'
    ORDER BY t.due_date
");

// Calculate completion statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_tasks,
        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN status = 'Not Started' THEN 1 ELSE 0 END) as not_started_tasks
    FROM tasks
    WHERE assigned_by = $lecturer_id
    AND subject = '{$course['course_name']}'
")->fetch_assoc();

// Calculate completion percentage
$completion_percentage = $stats['total_tasks'] > 0 
    ? round(($stats['completed_tasks'] / $stats['total_tasks']) * 100, 2) 
    : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Progress - <?= htmlspecialchars($course['course_code']) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .progress-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .progress-bar {
            height: 20px;
            background-color: #e0e0e0;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #4CAF50;
            width: <?= $completion_percentage ?>%;
            transition: width 0.5s;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin-top: 0;
            color: #333;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
        }
        .completed { color: #4CAF50; }
        .in-progress { color: #FFC107; }
        .not-started { color: #F44336; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed {
            background-color: #4CAF50;
            color: white;
        }
        .status-in-progress {
            background-color: #FFC107;
            color: black;
        }
        .status-not-started {
            background-color: #F44336;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="manage_courses.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Courses</a>
        <h2>Progress Tracking - <?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['course_name']) ?></h2>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Progress Overview -->
        <div class="progress-container">
            <h3>Overall Completion</h3>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <p><?= $completion_percentage ?>% of tasks completed</p>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Tasks</h3>
                    <div class="stat-value"><?= $stats['total_tasks'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Completed</h3>
                    <div class="stat-value completed"><?= $stats['completed_tasks'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>In Progress</h3>
                    <div class="stat-value in-progress"><?= $stats['in_progress_tasks'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Not Started</h3>
                    <div class="stat-value not-started"><?= $stats['not_started_tasks'] ?></div>
                </div>
            </div>
        </div>

        <!-- Student Progress Table -->
        <h3>Student Progress</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Task</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($task = $tasks->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($task['student_name']) ?></td>
                    <td><?= htmlspecialchars($task['title']) ?></td>
                    <td><?= date('M d, Y', strtotime($task['due_date'])) ?></td>
                    <td>
                        <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $task['status'])) ?>">
                            <?= $task['status'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="edit_task.php?id=<?= $task['id'] ?>" class="btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($tasks->num_rows == 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No tasks assigned for this course yet.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Student Completion Rates -->
        <h3>Student Completion Rates</h3>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Completed</th>
                    <th>In Progress</th>
                    <th>Not Started</th>
                    <th>Completion Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $enrolled_students->fetch_assoc()): 
                    $student_stats = $conn->query("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN status = 'Not Started' THEN 1 ELSE 0 END) as not_started
                        FROM tasks
                        WHERE user_id = {$student['id']}
                        AND subject = '{$course['course_name']}'
                    ")->fetch_assoc();
                    
                    $student_completion = $student_stats['total'] > 0 
                        ? round(($student_stats['completed'] / $student_stats['total']) * 100, 2) 
                        : 0;
                ?>
                <tr>
                    <td><?= htmlspecialchars($student['name']) ?></td>
                    <td><?= $student_stats['completed'] ?></td>
                    <td><?= $student_stats['in_progress'] ?></td>
                    <td><?= $student_stats['not_started'] ?></td>
                    <td>
                        <div class="progress-bar" style="height: 10px; margin: 5px 0;">
                            <div class="progress-fill" style="width: <?= $student_completion ?>%;"></div>
                        </div>
                        <?= $student_completion ?>%
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>