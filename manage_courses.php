<?php
include 'includes/auth.php';
include 'includes/db.php';

// Only lecturers can access
if ($_SESSION['role'] !== 'lecturer') {
    header("Location: dashboard.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new course
    if (isset($_POST['create_course'])) {
        $course_code = $_POST['course_code'];
        $course_name = $_POST['course_name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("INSERT INTO courses (lecturer_id, course_code, course_name, description) 
                               VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $lecturer_id, $course_code, $course_name, $description);
        
        if ($stmt->execute()) {
            $course_id = $stmt->insert_id;
            $_SESSION['success'] = "Course created successfully!";
            
            // Enroll selected students
            if (!empty($_POST['students']) && is_array($_POST['students'])) {
                $enrolled = 0;
                foreach ($_POST['students'] as $student_id) {
                    $enroll_stmt = $conn->prepare("INSERT INTO course_enrollments (course_id, student_id) 
                                                 VALUES (?, ?)");
                    $enroll_stmt->bind_param("ii", $course_id, $student_id);
                    if ($enroll_stmt->execute()) {
                        $enrolled++;
                    }
                }
                if ($enrolled > 0) {
                    $_SESSION['success'] .= " $enrolled students enrolled.";
                }
            }
        } else {
            $_SESSION['error'] = "Error creating course: " . $conn->error;
        }
    }
    
    // Update existing course
    if (isset($_POST['update_course'])) {
        $course_id = $_POST['course_id'];
        $course_code = $_POST['course_code'];
        $course_name = $_POST['course_name'];
        $description = $_POST['description'];
        
        $stmt = $conn->prepare("UPDATE courses SET course_code=?, course_name=?, description=? 
                               WHERE id=? AND lecturer_id=?");
        $stmt->bind_param("sssii", $course_code, $course_name, $description, $course_id, $lecturer_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Course updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating course: " . $conn->error;
        }
    }
    
    // Delete course
    if (isset($_POST['delete_course'])) {
        $course_id = $_POST['course_id'];
        if ($conn->query("DELETE FROM courses WHERE id=$course_id AND lecturer_id=$lecturer_id")) {
            $_SESSION['success'] = "Course deleted successfully!";
        } else {
            $_SESSION['error'] = "Error deleting course: " . $conn->error;
        }
    }
    
    // Enroll/Unenroll student
    if (isset($_POST['enroll_student'])) {
        $course_id = $_POST['course_id'];
        $student_id = $_POST['student_id'];
        $action = $_POST['action'];
        
        if ($action === 'enroll') {
            $stmt = $conn->prepare("INSERT INTO course_enrollments (course_id, student_id) 
                                   VALUES (?, ?)");
            $stmt->bind_param("ii", $course_id, $student_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Student enrolled successfully!";
            } else {
                $_SESSION['error'] = "Error enrolling student: " . $conn->error;
            }
        } else {
            if ($conn->query("DELETE FROM course_enrollments 
                            WHERE course_id=$course_id AND student_id=$student_id")) {
                $_SESSION['success'] = "Student unenrolled successfully!";
            } else {
                $_SESSION['error'] = "Error unenrolling student: " . $conn->error;
            }
        }
    }
    
    header("Location: manage_courses.php");
    exit();
}

// Get lecturer's courses
$courses = $conn->query("SELECT * FROM courses WHERE lecturer_id = $lecturer_id");

// Get all students (store in array to reuse)
$students_result = $conn->query("SELECT id, name FROM users WHERE role='student'");
$all_students = [];
while ($student = $students_result->fetch_assoc()) {
    $all_students[] = $student;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Courses</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Header and Navigation */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .btn-back {
            background: #2f4156;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
        }
        
        /* Alerts */
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Course Cards */
        .course-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .course-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Course Header */
        .course-header {
            background: #2f4156;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .course-header h4 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        /* Action Buttons */
        .course-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-manage, .btn-delete, .btn-update {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .btn-manage {
            background: #4285f4;
            color: white;
        }
        
        .btn-delete {
            background: #db4437;
            color: white;
        }
        
        .btn-update {
            background: #34a853;
            color: white;
        }
        
        /* Forms */
        .course-form {
            padding: 20px;
            background: #f8f9fa;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        /* Student Management */
        .student-management {
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .student-list {
            margin: 15px 0;
            padding: 0;
            list-style: none;
        }
        
        .student-list li {
            padding: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn-unenroll, .btn-enroll {
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .btn-unenroll {
            background: #ff4444;
            color: white;
            border: none;
        }
        
        .btn-enroll {
            background: #4285f4;
            color: white;
            border: none;
        }
        
        /* Checkboxes for student selection */
        .student-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Empty State */
        .empty-state {
            padding: 20px;
            text-align: center;
            color: #666;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .course-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .course-actions {
                width: 100%;
                justify-content: flex-end;
            }
            
            .student-checkboxes {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="action-buttons">
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <h2>Course Management</h2>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Create New Course Form -->
        <div class="course-card">
            <div class="course-header">
                <h4>Create New Course</h4>
            </div>
            <form method="POST" class="course-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Course Code</label>
                        <input type="text" name="course_code" required placeholder="e.g., CS101">
                    </div>
                    <div class="form-group">
                        <label>Course Name</label>
                        <input type="text" name="course_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Enroll Students</label>
                    <div class="student-checkboxes">
                        <?php foreach ($all_students as $student): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="students[]" value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" name="create_course" class="btn-update">
                    <i class="fas fa-plus-circle"></i> Create Course
                </button>
            </form>
        </div>

        <!-- Existing Courses -->
        <h3>Your Courses</h3>
        
        <?php if ($courses->num_rows > 0): ?>
            <?php while ($course = $courses->fetch_assoc()): 
                $enrolled = $conn->query("SELECT u.id, u.name 
                                         FROM course_enrollments ce
                                         JOIN users u ON ce.student_id = u.id
                                         WHERE ce.course_id = {$course['id']}");
            ?>
            <div class="course-card">
                <div class="course-header">
                    <h4><?= htmlspecialchars($course['course_code']) ?>: <?= htmlspecialchars($course['course_name']) ?></h4>
                    <div class="course-actions">
                        <button onclick="toggleEnrollmentForm(<?= $course['id'] ?>)" class="btn-manage">
                            <i class="fas fa-users"></i> Students
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                            <button type="submit" name="delete_course" class="btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                
                <form method="POST" class="course-form">
                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Course Code</label>
                            <input type="text" name="course_code" value="<?= htmlspecialchars($course['course_code']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Course Name</label>
                            <input type="text" name="course_name" value="<?= htmlspecialchars($course['course_name']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3"><?= htmlspecialchars($course['description']) ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_course" class="btn-update">
                        <i class="fas fa-save"></i> Update Course
                    </button>
                </form>
                
                <!-- Student Enrollment Section -->
                <div id="enroll-form-<?= $course['id'] ?>" class="student-management" style="display:none;">
                    <h5>Enrolled Students</h5>
                    <?php if ($enrolled->num_rows > 0): ?>
                        <ul class="student-list">
                            <?php while ($student = $enrolled->fetch_assoc()): ?>
                            <li>
                                <span><?= htmlspecialchars($student['name']) ?></span>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                    <input type="hidden" name="action" value="unenroll">
                                    <button type="submit" name="enroll_student" class="btn-unenroll">
                                        <i class="fas fa-user-minus"></i> Remove
                                    </button>
                                </form>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p>No students enrolled yet.</p>
                    <?php endif; ?>
                    
                    <h5>Enroll New Student</h5>
                    <form method="POST" class="enroll-form">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($all_students as $student): 
                                        $is_enrolled = $conn->query("SELECT * FROM course_enrollments 
                                                                   WHERE course_id={$course['id']} 
                                                                   AND student_id={$student['id']}")->num_rows > 0;
                                        if (!$is_enrolled):
                                    ?>
                                        <option value="<?= $student['id'] ?>">
                                            <?= htmlspecialchars($student['name']) ?>
                                        </option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <input type="hidden" name="action" value="enroll">
                                <button type="submit" name="enroll_student" class="btn-enroll">
                                    <i class="fas fa-user-plus"></i> Enroll
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="course-card">
                <div class="empty-state">
                    <p>You haven't created any courses yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
    // Improved toggle function with animation
    function toggleEnrollmentForm(courseId) {
        const form = document.getElementById(`enroll-form-${courseId}`);
        const btn = document.querySelector(`button[onclick="toggleEnrollmentForm(${courseId})"]`);
        
        if (form.style.display === 'none' || !form.style.display) {
            form.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-users-slash"></i> Hide Students';
            btn.classList.add('active');
            // Smooth scroll to the form
            form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            form.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-users"></i> Students';
            btn.classList.remove('active');
        }
    }

    // Add confirmation for delete actions
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    });
    </script>
</body>
</html>