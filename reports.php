<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only coordinators can access
if ($_SESSION['role'] !== 'academic_coordinator') {
    header("Location: dashboard.php");
    exit();
}

// Get course statistics
$course_stats = $conn->query("
    SELECT 
        c.id,
        c.course_code,
        c.course_name,
        COUNT(DISTINCT ce.student_id) as student_count,
        COUNT(DISTINCT CASE WHEN t.user_id = ce.student_id THEN t.id END) as task_count,
        COUNT(DISTINCT CASE WHEN t.user_id = ce.student_id AND t.status = 'Completed' THEN t.id END) as completed_tasks,
        COUNT(DISTINCT CASE WHEN t.user_id = ce.student_id AND t.status = 'In Progress' THEN t.id END) as in_progress_tasks,
        COUNT(DISTINCT CASE WHEN t.user_id = ce.student_id AND t.status = 'Not Started' THEN t.id END) as not_started_tasks
    FROM courses c
    LEFT JOIN course_enrollments ce ON c.id = ce.course_id
    LEFT JOIN tasks t ON t.subject = c.course_name AND t.user_id = ce.student_id
    GROUP BY c.id
");


// Get lecturer performance
$lecturer_stats = $conn->query("
    SELECT 
        u.id,
        u.name,
        COUNT(DISTINCT c.id) as course_count,
        COUNT(DISTINCT t.id) as task_assigned,
        AVG(t.status = 'Completed') as avg_completion_rate
    FROM users u
    LEFT JOIN courses c ON u.id = c.lecturer_id
    LEFT JOIN tasks t ON t.assigned_by = u.id
    WHERE u.role = 'lecturer'
    GROUP BY u.id
");

// Get system-wide statistics
$system_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.role = 'student' THEN u.id END) as total_students,
        COUNT(DISTINCT CASE WHEN u.role = 'lecturer' THEN u.id END) as total_lecturers,
        COUNT(DISTINCT c.id) as total_courses,
        COUNT(DISTINCT t.id) as total_tasks,
        AVG(t.status = 'Completed') as system_completion_rate
    FROM users u
    LEFT JOIN courses c ON 1=1
    LEFT JOIN tasks t ON 1=1
")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Reports</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .report-section {
            margin-bottom: 40px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
            text-align: center;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #2f4156;
            font-size: 16px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin: 10px 0;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background-color: #2f4156;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .progress-cell {
            display: flex;
            align-items: center;
        }
        .progress-bar {
            flex-grow: 1;
            height: 10px;
            background-color: #e0e0e0;
            border-radius: 5px;
            margin: 0 10px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background-color: #4CAF50;
        }
        .high-rate { color: #4CAF50; }
        .medium-rate { color: #FFC107; }
        .low-rate { color: #F44336; }
        .export-buttons {
            margin: 20px 0;
        }
        .export-buttons button {
            margin-right: 10px;
            padding: 8px 15px;
            background-color: #2f4156;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .export-buttons button:hover {
            background-color: #1a2a3a;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        <h1>Academic Reports</h1>
        
        <!-- System Overview -->
        <div class="report-section">
            <h2><i class="fas fa-chart-line"></i> System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?= $system_stats['total_users'] ?></div>
                    <div>Students: <?= $system_stats['total_students'] ?></div>
                    <div>Lecturers: <?= $system_stats['total_lecturers'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Courses</h3>
                    <div class="stat-value"><?= $system_stats['total_courses'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Tasks</h3>
                    <div class="stat-value"><?= $system_stats['total_tasks'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>System Completion Rate</h3>
                    <div class="stat-value <?= $system_stats['system_completion_rate'] > 0.7 ? 'high-rate' : 
                                          ($system_stats['system_completion_rate'] > 0.4 ? 'medium-rate' : 'low-rate') ?>">
                        <?= round($system_stats['system_completion_rate'] * 100, 2) ?>%
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $system_stats['system_completion_rate'] * 100 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Performance -->
        <div class="report-section">
            <h2><i class="fas fa-book"></i> Course Performance</h2>
            <div class="export-buttons">
                <button onclick="exportToCSV('course-data', 'course-performance')">
                    <i class="fas fa-file-csv"></i> Export to CSV
                </button>
                <button onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
            <div class="table-container">
                <table id="course-data">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Students</th>
                            <th>Total Tasks</th>
                            <th>Completed</th>
                            <th>In Progress</th>
                            <th>Not Started</th>
                            <th>Completion Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while($course = $course_stats->fetch_assoc()):
                        $completed = (int) $course['completed_tasks'];
                        $total = (int) $course['task_count'];
                        $completion_rate = ($total > 0) ? ($completed / $total) : 0;
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($course['course_code']) ?></td>
                            <td><?= htmlspecialchars($course['course_name']) ?></td>
                            <td><?= $course['student_count'] ?></td>
                            <td><?= $course['task_count'] ?></td>
                            <td><?= $course['completed_tasks'] ?></td>
                            <td><?= $course['in_progress_tasks'] ?></td>
                            <td><?= $course['not_started_tasks'] ?></td>
                            <td class="progress-cell">
                            <span class="<?= $completion_rate > 0.7 ? 'high-rate' : 
                                        ($completion_rate > 0.4 ? 'medium-rate' : 'low-rate') ?>">
                                <?= round($completion_rate * 100, 2) ?>%
                            </span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?= $completion_rate * 100 ?>%"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Lecturer Performance -->
        <div class="report-section">
            <h2><i class="fas fa-chalkboard-teacher"></i> Lecturer Performance</h2>
            <div class="table-container">
                <table id="lecturer-data">
                    <thead>
                        <tr>
                            <th>Lecturer</th>
                            <th>Courses</th>
                            <th>Tasks Assigned</th>
                            <th>Avg. Completion</th>
                            <th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($lecturer = $lecturer_stats->fetch_assoc()): 
                            $performance = $lecturer['avg_completion_rate'] > 0.7 ? 'Excellent' :
                                          ($lecturer['avg_completion_rate'] > 0.4 ? 'Good' : 'Needs Improvement');
                            $performance_class = $lecturer['avg_completion_rate'] > 0.7 ? 'high-rate' :
                                              ($lecturer['avg_completion_rate'] > 0.4 ? 'medium-rate' : 'low-rate');
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($lecturer['name']) ?></td>
                            <td><?= $lecturer['course_count'] ?></td>
                            <td><?= $lecturer['task_assigned'] ?></td>
                            <td><?= round($lecturer['avg_completion_rate'] * 100, 2) ?>%</td>
                            <td class="<?= $performance_class ?>"><?= $performance ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Data Export Section -->
        <div class="report-section">
            <h2><i class="fas fa-download"></i> Data Export</h2>
            <div class="export-buttons">
                <button onclick="exportToCSV('course-data', 'course-performance')">
                    <i class="fas fa-file-csv"></i> Export Course Data
                </button>
                <button onclick="exportToCSV('lecturer-data', 'lecturer-performance')">
                    <i class="fas fa-file-csv"></i> Export Lecturer Data
                </button>
                <button onclick="window.print()">
                    <i class="fas fa-print"></i> Print All Reports
                </button>
            </div>
        </div>
    </div>

    <script>
    function exportToCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll('tr');
        let csv = [];
        
        for (const row of rows) {
            const rowData = [];
            const cols = row.querySelectorAll('td, th');
            
            for (const col of cols) {
                // Get text content, remove any commas
                let text = col.textContent.trim().replace(/,/g, ';');
                
                // For progress cells, get the percentage value
                if (col.classList.contains('progress-cell')) {
                    const percentSpan = col.querySelector('span');
                    if (percentSpan) text = percentSpan.textContent.trim();
                }
                
                rowData.push(text);
            }
            
            csv.push(rowData.join(','));
        }
        
        // Download CSV file
        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename + '.csv');
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
</body>
</html>