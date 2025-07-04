<?php
include 'includes/auth.php';
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

$enrolledCoursesResult = $conn->query("SELECT c.course_code, c.course_name 
    FROM courses c
    INNER JOIN course_enrollments ce ON c.id = ce.course_id
    WHERE ce.student_id = $user_id");

$enrolledCourses = [];
while ($row = $enrolledCoursesResult->fetch_assoc()) {
    $enrolledCourses[] = $row;
}


// Get tasks: Owned tasks + Assigned tasks
$sql = "SELECT t.*, u1.name AS assigned_by_name, u2.name AS assigned_to_name 
        FROM tasks t
        LEFT JOIN users u1 ON t.assigned_by = u1.id
        LEFT JOIN users u2 ON t.assigned_to = u2.id
        WHERE t.user_id = ? OR t.assigned_to = ? OR t.assigned_by = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Count tasks
$total = $result->num_rows;
$completed = $conn->query("SELECT COUNT(*) FROM tasks 
                          WHERE (user_id = $user_id OR assigned_to = $user_id)
                          AND status='Completed'")->fetch_row()[0];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="js/script.js" defer></script>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Progress bar styles */
        #progressContainer {
            width: 100%;
            background-color: #f0f0f0;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        #progressBar {
            height: 20px;
            border-radius: 5px;
            transition: width 0.5s;
        }
        .progress-red { background-color: #ff4444; }
        .progress-yellow { background-color: #ffbb33; }
        .progress-green { background-color: #00C851; }
        #progressLabel {
            display: block;
            text-align: center;
            padding: 5px;
            color: #333;
        }
        
        /* Status indicator styles */
        .status-indicator {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            color: white;
        }
        .status-not-started { background-color: #ff4444; }
        .status-in-progress { background-color: #ffbb33; }
        .status-completed { background-color: #00C851; }
        
        /* Button styles */
        .edit-btn {
            background-color: #4285f4;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
        }
        .edit-btn:hover {
            background-color: #3367d6;
        }
        .delete-btn {
            background-color: #db4437;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
        }
        .delete-btn:hover {
            background-color: #c1351d;
        }
        
        /* Dark mode styles */
        .dark-mode { background-color: #121212; color: #ffffff; }
        .dark-mode header { background-color: #1e1e1e; }
        .dark-mode table { background-color: #1e1e1e; color: #ffffff; }
        .dark-mode table th { background-color: #2d2d2d; }
        .dark-mode table tr:nth-child(even) { background-color: #2d2d2d; }
    </style>
</head>
<body onload="updateProgressBar()">
<header>
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center;">
        <h1 style="margin: 0; display: flex; align-items: center; font-weight: bold; font-style: italic; color: #2f4156;">
            Hello, <?= $_SESSION['name'] ?>!
            <span class="role-badge <?= $_SESSION['role'] ?>-badge" style="margin-left: 10px;">
                <?= ucfirst($_SESSION['role']) ?>
            </span>
        </h1>
        </div>
        <div>
    <?php if ($_SESSION['role'] === 'student'): ?>
            <a href="add_task.php">
            <button type="submit"> + Add Task </button>
             </a>
         <?php endif; ?>
         <a href="logout.php">
         <button type="submit"> Logout </button>
          </a>
        </div>
    </div>
</header>
<?php if ($_SESSION['role'] === 'student' && !empty($enrolledCourses)): ?>
    <div class="container mt-4">
        <h4 class="mb-3">My Courses</h4>
        <div class="row">
            <?php foreach ($enrolledCourses as $course): ?>
                <div class="col-md-4 mb-4">
                    <div class="card text-white shadow" style="background-color: <?= sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>;">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($course['course_code']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($course['course_name']) ?></p>
                            <div class="progress mt-2">
                                <div class="progress-bar bg-light" role="progressbar" style="width: <?= rand(70, 95) ?>%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">
                                    <?= rand(70, 95) ?>% complete
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

    <!-- Role-specific features -->
    <?php if ($_SESSION['role'] === 'lecturer'): ?>
        <div class="lecturer-features">
            <h3>Lecturer Tools</h3>
            <a href="assign_tasks.php">
                <button class="btn-role">Assign Tasks to Students</button>
            </a>
            <a href="manage_courses.php">
                <button class="btn-role">Manage Courses</button>
            </a>
        </div>
    <?php elseif ($_SESSION['role'] === 'academic_coordinator'): ?>
        <div class="coordinator-features">
            <h3>Coordinator Tools</h3>
            <a href="manage_users.php">
                <button class="btn-role">Manage User</button>
            </a>
            <a href="reports.php">
                <button class="btn-role">View Users</button>
            </a>
        </div>
    <?php endif; ?>

    <section>
        <?php if ($_SESSION['role'] !== 'academic_coordinator'): ?>
        <div id="progressContainer">
            <div id="progressBar"></div>
            <span id="progressLabel"></span>
        </div>
        <?php endif; ?>

        <?php if ($_SESSION['role'] !== 'academic_coordinator'): ?>
        <input type="text" id="searchInput" onkeyup="filterTasks()" placeholder="Search tasks...">
            <select id="filterType" onchange="filterTasks()">
                <option value="all">All</option>
                <option value="Assignment">Assignment</option>
                <option value="Exam">Exam</option>
                <option value="Study Session">Study Session</option>
            </select>
        <?php endif; ?>

        <?php if ($_SESSION['role'] !== 'academic_coordinator'): ?>
        <div id="taskList">
            <table>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Subject</th>
                    <th>Due</th>
                    <th>Status</th>
                    <?php if ($_SESSION['role'] === 'lecturer'): ?>
                        <th>Assigned To</th>
                    <?php endif; ?>
                    <th>Assigned By</th>
                    <th>Actions</th>
                </tr>
                <?php while($row = $result->fetch_assoc()): 
                    if ($_SESSION['role'] === 'student') {
                        $assigned_text = is_null($row['assigned_by']) ? 'Self' : 'Lecturer';
                    } else {
                        $assigned_text = $row['assigned_by_name'] ?? '-';
                    }
                    $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                ?>

                <tr class="task-row" 
                    data-title="<?= strtolower($row['title']) ?>" 
                    data-type="<?= $row['type'] ?>" 
                    data-due="<?= $row['due_date'] ?>" 
                    data-status="<?= $row['status'] ?>">
                    <td><?= $row['title'] ?></td>
                    <td><?= $row['type'] ?></td>
                    <td><?= $row['subject'] ?></td>
                    <td><?= date('M d, Y', strtotime($row['due_date'])) ?></td>
                    <td>
                        <span class="status-indicator status-<?= $statusClass ?>">
                            <?= $row['status'] ?>
                        </span>
                    </td>
                    <?php if ($_SESSION['role'] === 'lecturer'): ?>
                        <td>
                        <?php
                        if (!empty($row['course_id'])) {
                            $course_id = (int)$row['course_id'];
                            $studentQuery = "SELECT u.name 
                                            FROM course_enrollments ce
                                            JOIN users u ON ce.student_id = u.id
                                            WHERE ce.course_id = $course_id AND u.role = 'student'";
                            $studentResult = $conn->query($studentQuery);
                            $studentNames = [];
                            while ($student = $studentResult->fetch_assoc()) {
                                $studentNames[] = $student['name'];
                            }
                            echo htmlspecialchars(implode(', ', $studentNames));
                        } else {
                            echo $row['assigned_to_name'] ?? '—';
                        }
                        ?>
                        </td>
                    <?php endif; ?>
                    <td><?= $assigned_text ?></td>
                    <td>
                        <?php if ($_SESSION['role'] !== 'academic_coordinator'): ?>
                            <a href="edit_task.php?id=<?= $row['id'] ?>" class="edit-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_task.php?id=<?= $row['id'] ?>" class="delete-btn" onclick="return confirm('Delete this task?')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <div style="display:none;">
        <span id="totalTasks"><?= $total ?></span>
        <span id="completedTasks"><?= $completed ?></span>
    </div>
</body>
</html>