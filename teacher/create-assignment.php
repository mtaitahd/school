<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_id = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assignment_type = trim($_POST['assignment_type']);
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $points = !empty($_POST['points']) ? (int)$_POST['points'] : null;
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (!empty($title) && !empty($assignment_type)) {
        // Insert new assignment into database
        $assignment_id = $database->insert(
            "INSERT INTO assignments (teacher_id, class_id, title, description, assignment_type, due_date, points, is_published, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [$teacher_id, $class_id, $title, $description, $assignment_type, $due_date, $points, $is_published]
        );
        
        if ($assignment_id) {
            // Link assignment to students in the selected class
            if ($class_id) {
                $students = $database->fetchAll(
                    "SELECT student_id FROM class_enrollments WHERE class_id = ?",
                    [$class_id]
                );
                foreach ($students as $student) {
                    $database->insert(
                        "INSERT INTO student_assignments (assignment_id, student_id) VALUES (?, ?)",
                        [$assignment_id, $student['student_id']]
                    );
                }
            }
            header('Location: dashboard.php?success=assignment_created');
            exit;
        } else {
            $error = "Failed to create assignment. Please try again.";
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}

// Fetch teacher's classes
$classes = $database->fetchAll("
    SELECT class_id, class_name, grade_level 
    FROM classes 
    WHERE teacher_id = ? AND is_active = 1
    ORDER BY class_name ASC
", [$teacher_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once '../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'assignments';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Create Assignment</h1>
        <p class="activity-instruction">Create a new assignment for your students</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-card" style="max-width: 800px; margin: 0 auto;">
        <form method="POST" action="">
            <div class="mb-20">
                <label for="title" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                    <i class="fas fa-heading" style="margin-right: 8px;"></i>Assignment Title *
                </label>
                <input type="text" 
                       class="form-control" 
                       id="title" 
                       name="title" 
                       placeholder="e.g. Counting Practice 1-10"
                       required
                       style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
            </div>

            <div class="mb-20">
                <label for="class_id" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                    <i class="fas fa-users" style="margin-right: 8px;"></i>Assign to Class (Optional)
                </label>
                <select class="form-select" 
                        id="class_id" 
                        name="class_id" 
                        style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">-- All Classes / Individual Assignment --</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['class_id']; ?>">
                            <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['grade_level']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-20">
                <label for="assignment_type" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                    <i class="fas fa-tag" style="margin-right: 8px;"></i>Assignment Type *
                </label>
                <select class="form-select" 
                        id="assignment_type" 
                        name="assignment_type" 
                        required
                        style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                    <option value="">-- Select Type --</option>
                    <option value="homework">Homework</option>
                    <option value="task">Task</option>
                    <option value="material">Material</option>
                    <option value="quiz">Quiz</option>
                </select>
            </div>

            <div class="mb-20">
                <label for="description" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                    <i class="fas fa-align-left" style="margin-right: 8px;"></i>Description
                </label>
                <textarea class="form-control" 
                          id="description" 
                          name="description" 
                          rows="4"
                          placeholder="Provide detailed instructions for this assignment..."
                          style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;"></textarea>
            </div>

            <div class="row-child mb-20">
                <div class="col-child-2">
                    <label for="due_date" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-calendar" style="margin-right: 8px;"></i>Due Date
                    </label>
                    <input type="datetime-local" 
                           class="form-control" 
                           id="due_date" 
                           name="due_date" 
                           style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                </div>
                <div class="col-child-2">
                    <label for="points" class="form-label" style="font-weight: 600; color: var(--text-dark);">
                        <i class="fas fa-star" style="margin-right: 8px;"></i>Points
                    </label>
                    <input type="number" 
                           class="form-control" 
                           id="points" 
                           name="points" 
                           placeholder="e.g. 100"
                           min="0"
                           style="padding: 12px; border: 2px solid var(--border-color); border-radius: 8px;">
                </div>
            </div>

            <div class="mb-20">
                <label class="form-label" style="font-weight: 600; color: var(--text-dark);">
                    <input type="checkbox" name="is_published" id="is_published">
                    <i class="fas fa-paper-plane" style="margin-right: 8px;"></i>Publish Immediately
                </label>
                <small class="text-muted d-block mt-5">If unchecked, the assignment will be saved as a draft.</small>
            </div>

            <div class="d-flex gap-10 mt-30">
                <button type="button" 
                        class="btn-child btn-child-secondary" 
                        onclick="window.location.href='dashboard.php'"
                        style="flex: 1; padding: 12px 24px; font-size: 16px;">
                    <i class="fas fa-times" style="margin-right: 8px;"></i>Cancel
                </button>
                <button type="submit" 
                        class="btn-child btn-child-primary" 
                        style="flex: 1; padding: 12px 24px; font-size: 16px;">
                    <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>Create Assignment
                </button>
            </div>
        </form>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



