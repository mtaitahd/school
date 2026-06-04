<?php
require_once '__DIR__ . '/../php/includes/session.php';
require_once '__DIR__ . '/../php/includes/security.php';
require_once '__DIR__ . '/../php/includes/csrf.php';
require_once '__DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
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
    require_once '__DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'classes';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h1 class="h3 mb-1 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Manage Classes</h1>
            <p class="text-muted mb-0" style="font-size:0.9rem;">Create and manage your classes</p>
        </div>
        <button class="btn btn-primary" style="background:var(--primary-blue);border:none;font-weight:600;padding:10px 24px;" onclick="openCreateModal()">
            <i class="fas fa-plus-circle me-2"></i>Create Class
        </button>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success py-2 px-3" style="border:none;border-radius:0;font-size:0.9rem;">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger py-2 px-3" style="border:none;border-radius:0;font-size:0.9rem;">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Create/Edit Class Modal -->
    <div class="modal fade" id="classModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header">
                    <h5 class="modal-title" id="classModalLabel">Create New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" id="classFormAction" value="create">
                    <input type="hidden" name="class_id" id="classFormId" value="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="class_name" class="form-label fw-semibold" style="font-size:0.85rem;">Class Name</label>
                            <input type="text" class="form-control" id="class_name" name="class_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="class_description" class="form-label fw-semibold" style="font-size:0.85rem;">Description</label>
                            <textarea class="form-control" id="class_description" name="class_description" rows="3"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="grade_level" class="form-label fw-semibold" style="font-size:0.85rem;">Grade Level</label>
                                <input type="text" class="form-control" id="grade_level" name="grade_level" placeholder="e.g. Standard 1">
                            </div>
                            <div class="col-md-6">
                                <label for="academic_year" class="form-label fw-semibold" style="font-size:0.85rem;">Academic Year</label>
                                <input type="text" class="form-control" id="academic_year" name="academic_year" value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                        <div class="mt-3 form-check" id="isActiveWrapper" style="display:none;">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label class="form-check-label fw-semibold" for="is_active" style="font-size:0.85rem;">Active Class</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;font-weight:600;" id="classSubmitBtn">
                            <i class="fas fa-plus-circle me-2" id="classSubmitIcon"></i><span id="classSubmitText">Create Class</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                                        <span style="background: var(--primary-red); color: white; padding: 2px 8px; font-size: 0.8rem;">Inactive</span>
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
                                <a href="javascript:void(0)" class="btn-child btn-child-warning" style="min-height: 40px; min-width: 40px;"
                                   onclick="openEditModal(<?php echo $class['class_id']; ?>, '<?php echo htmlspecialchars(addslashes($class['class_name'])); ?>', '<?php echo htmlspecialchars(addslashes($class['class_description'])); ?>', '<?php echo htmlspecialchars(addslashes($class['grade_level'])); ?>', '<?php echo htmlspecialchars(addslashes($class['academic_year'])); ?>', <?php echo $class['is_active'] ? 'true' : 'false'; ?>)">
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
    <script>
        function openCreateModal() {
            document.getElementById('classModalLabel').textContent = 'Create New Class';
            document.getElementById('classFormAction').value = 'create';
            document.getElementById('classFormId').value = '';
            document.getElementById('class_name').value = '';
            document.getElementById('class_description').value = '';
            document.getElementById('grade_level').value = '';
            document.getElementById('academic_year').value = '<?php echo date('Y'); ?>';
            document.getElementById('isActiveWrapper').style.display = 'none';
            document.getElementById('classSubmitIcon').className = 'fas fa-plus-circle me-2';
            document.getElementById('classSubmitText').textContent = 'Create Class';
            new bootstrap.Modal(document.getElementById('classModal')).show();
        }

        function openEditModal(id, name, description, grade, year, active) {
            document.getElementById('classModalLabel').textContent = 'Edit Class';
            document.getElementById('classFormAction').value = 'update';
            document.getElementById('classFormId').value = id;
            document.getElementById('class_name').value = name;
            document.getElementById('class_description').value = description;
            document.getElementById('grade_level').value = grade;
            document.getElementById('academic_year').value = year;
            document.getElementById('isActiveWrapper').style.display = 'block';
            document.getElementById('is_active').checked = active;
            document.getElementById('classSubmitIcon').className = 'fas fa-save me-2';
            document.getElementById('classSubmitText').textContent = 'Update Class';
            new bootstrap.Modal(document.getElementById('classModal')).show();
        }
    </script>
</body>
</html>



