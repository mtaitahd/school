<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Create new class
        if ($_POST['action'] === 'create') {
            $class_name = trim($_POST['class_name']);
            $class_description = trim($_POST['class_description']);
            $grade_level = trim($_POST['grade_level']);
            $academic_year = trim($_POST['academic_year']);
            
            $sql = "INSERT INTO classes (teacher_id, class_name, class_description, grade_level, academic_year) 
                    VALUES (?, ?, ?, ?, ?)";
            $params = [$teacher_id, $class_name, $class_description, $grade_level, $academic_year];
            
            if ($database->insert($sql, $params)) {
                $success = "Class created successfully!";
            } else {
                $error = "Failed to create class.";
            }
        }
        
        // Update class
        if ($_POST['action'] === 'update') {
            $class_id = intval($_POST['class_id']);
            $class_name = trim($_POST['class_name']);
            $class_description = trim($_POST['class_description']);
            $grade_level = trim($_POST['grade_level']);
            $academic_year = trim($_POST['academic_year']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $sql = "UPDATE classes SET class_name = ?, class_description = ?, grade_level = ?, 
                    academic_year = ?, is_active = ? WHERE class_id = ? AND teacher_id = ?";
            $params = [$class_name, $class_description, $grade_level, $academic_year, $is_active, $class_id, $teacher_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Class updated successfully!";
            } else {
                $error = "Failed to update class.";
            }
        }
        
        // Delete class
        if ($_POST['action'] === 'delete') {
            $class_id = intval($_POST['class_id']);
            
            $sql = "DELETE FROM classes WHERE class_id = ? AND teacher_id = ?";
            $params = [$class_id, $teacher_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Class deleted successfully!";
            } else {
                $error = "Failed to delete class.";
            }
        }
    }
}

// Fetch all classes for this teacher
$classes = $database->fetchAll("
    SELECT c.*, 
           (SELECT COUNT(*) FROM class_enrollments ce WHERE ce.class_id = c.class_id) as student_count
    FROM classes c
    WHERE c.teacher_id = ?
    ORDER BY c.created_at DESC
", [$teacher_id]);

// Fetch class details if editing
$editing_class = null;
if (isset($_GET['edit'])) {
    $class_id = intval($_GET['edit']);
    $editing_class = $database->fetchOne("
        SELECT * FROM classes WHERE class_id = ? AND teacher_id = ?
    ", [$class_id, $teacher_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Kona Ya Hisabati</title>
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
    $sidebar_active = 'classes';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Manage Classes</h1>
        <p class="activity-instruction">Create and manage your classes</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert-child alert-child-success mb-30">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert-child alert-child-error mb-30">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Create/Edit Class Form -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                <i class="fas fa-<?php echo $editing_class ? 'edit' : 'plus'; ?>"></i>
            </div>
            <h3 class="dashboard-card-title"><?php echo $editing_class ? 'Edit Class' : 'Create New Class'; ?></h3>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $editing_class ? 'update' : 'create'; ?>">
            <?php if ($editing_class): ?>
                <input type="hidden" name="class_id" value="<?php echo $editing_class['class_id']; ?>">
            <?php endif; ?>
            
            <div class="form-group-child">
                <label class="form-label-child">Class Name</label>
                <input type="text" class="form-control-child" name="class_name" 
                       value="<?php echo $editing_class ? htmlspecialchars($editing_class['class_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group-child">
                <label class="form-label-child">Description</label>
                <textarea class="form-control-child" name="class_description" rows="3"><?php echo $editing_class ? htmlspecialchars($editing_class['class_description']) : ''; ?></textarea>
            </div>
            
            <div class="row-child">
                <div class="col-child-2">
                    <div class="form-group-child">
                        <label class="form-label-child">Grade Level</label>
                        <input type="text" class="form-control-child" name="grade_level" 
                               value="<?php echo $editing_class ? htmlspecialchars($editing_class['grade_level']) : ''; ?>">
                    </div>
                </div>
                <div class="col-child-2">
                    <div class="form-group-child">
                        <label class="form-label-child">Academic Year</label>
                        <input type="text" class="form-control-child" name="academic_year" 
                               value="<?php echo $editing_class ? htmlspecialchars($editing_class['academic_year']) : date('Y'); ?>">
                    </div>
                </div>
            </div>
            
            <?php if ($editing_class): ?>
                <div class="form-group-child">
                    <label class="form-label-child">
                        <input type="checkbox" name="is_active" <?php echo $editing_class['is_active'] ? 'checked' : ''; ?>>
                        Active Class
                    </label>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-30">
                <button type="submit" class="btn-child btn-child-primary btn-child-large">
                    <i class="fas fa-<?php echo $editing_class ? 'save' : 'plus-circle'; ?> me-2"></i>
                    <?php echo $editing_class ? 'Update Class' : 'Create Class'; ?>
                </button>
                <?php if ($editing_class): ?>
                    <a href="manage-classes" class="btn-child btn-child-secondary btn-child-large" style="margin-left: 10px;">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Classes List -->
    <?php if (!empty($classes)): ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-list"></i>
                </div>
                <h3 class="dashboard-card-title">Your Classes</h3>
            </div>
            <div style="max-height: 500px; overflow-y: auto;">
                <?php foreach ($classes as $class): ?>
                    <div style="padding: 20px; border-bottom: 1px solid #eee; <?php echo !$class['is_active'] ? 'opacity: 0.6;' : ''; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <h4 style="margin: 0 0 10px 0;">
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                    <?php if (!$class['is_active']): ?>
                                        <span style="background: var(--primary-red); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">Inactive</span>
                                    <?php endif; ?>
                                </h4>
                                <p style="margin: 5px 0; color: var(--text-light);">
                                    <?php echo htmlspecialchars($class['class_description']); ?>
                                </p>
                                <p style="margin: 5px 0; color: var(--text-light); font-size: 0.9rem;">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    <?php echo htmlspecialchars($class['grade_level'] ?: 'Not set'); ?>
                                    <span style="margin: 0 10px;">|</span>
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo htmlspecialchars($class['academic_year'] ?: 'Not set'); ?>
                                    <span style="margin: 0 10px;">|</span>
                                    <i class="fas fa-users me-2"></i>
                                    <?php echo $class['student_count']; ?> Students
                                </p>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="view-class.php?class_id=<?php echo $class['class_id']; ?>" 
                                   class="btn-child btn-child-info" style="min-height: 40px; min-width: 40px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="manage-classes.php?edit=<?php echo $class['class_id']; ?>" 
                                   class="btn-child btn-child-warning" style="min-height: 40px; min-width: 40px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this class?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                    <button type="submit" class="btn-child btn-child-danger" style="min-height: 40px; min-width: 40px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="dashboard-card text-center">
            <i class="fas fa-chalkboard-teacher" style="font-size: 4rem; color: var(--text-light); margin-bottom: 20px;"></i>
            <h3>No Classes Yet</h3>
            <p style="color: var(--text-light);">Create your first class to get started!</p>
        </div>
    <?php endif; ?>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



