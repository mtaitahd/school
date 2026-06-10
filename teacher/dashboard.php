<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}

// Fetch teacher's assigned learners (children whose parent_id is linked to this teacher)
// For now, we'll show all learners
$learners = $database->fetchAll("
    SELECT u.*, 
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars
    FROM users u 
    WHERE u.role = 'learner' 
    ORDER BY u.created_at DESC
");

// Fetch module statistics
$module_stats = $database->fetchAll("
    SELECT m.module_name, 
           COUNT(DISTINCT p.user_id) as active_learners,
           AVG(p.score) as avg_score
    FROM modules m
    LEFT JOIN activities a ON m.module_id = a.module_id
    LEFT JOIN progress p ON a.activity_id = p.activity_id
    WHERE m.is_active = 1
    GROUP BY m.module_id, m.module_name
");

// Fetch recent activity
$recent_activity = $database->fetchAll("
    SELECT p.*, u.first_name, u.last_name, a.activity_name, m.module_name
    FROM progress p
    JOIN users u ON p.user_id = u.user_id
    JOIN activities a ON p.activity_id = a.activity_id
    JOIN modules m ON a.module_id = m.module_id
    ORDER BY p.last_attempt_at DESC
    LIMIT 10
");

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'teacher';
$sidebar_active = 'dashboard';
$dashboard_page_title = 'Teacher Dashboard';
$lang_page = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Teacher Dashboard</h1>
            <div class="d-flex gap-2 flex-wrap">
                <a href="learners?add=1" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;text-decoration:none;">
                    <i class="fas fa-user-plus me-2"></i>Add Student
                </a>
                <a href="learners" class="btn btn-success" style="background:var(--primary-green);border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;text-decoration:none;">
                    <i class="fas fa-users me-2"></i>Manage Learners
                </a>
                <button type="button" class="btn btn-warning" style="background:#e6a800;border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;color:#fff;" data-bs-toggle="modal" data-bs-target="#createClassModal">
                    <i class="fas fa-plus-circle me-2"></i>Create Class
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'class_created'): ?>
            <div class="alert alert-success py-2 px-3 mb-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;max-width:700px;margin:0 auto;">
                Class created successfully.
            </div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'assignment_created'): ?>
            <div class="alert alert-success py-2 px-3 mb-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;max-width:700px;margin:0 auto;">
                <i class="fas fa-check-circle me-1"></i> Assignment created successfully and assigned to students.
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-blue);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-blue);">Total Learners</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-blue);"><?php echo count($learners); ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-blue);"><i class="fas fa-users text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-green);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-green);">Completed Activities</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-green);">
                                    <?php echo array_sum(array_column($learners, 'completed_activities')); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-green);"><i class="fas fa-check-circle text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid #e6a800;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#e6a800;">Total Stars Earned</div>
                                <div class="h3 mb-0 fw-bold" style="color:#e6a800;">
                                    <?php echo array_sum(array_column($learners, 'total_stars')); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:#e6a800;"><i class="fas fa-star text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-purple);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-purple);">Active Modules</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-purple);"><?php echo count($module_stats); ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-purple);"><i class="fas fa-tasks text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Class Modal -->
        <div class="modal fade" id="createClassModal" tabindex="-1" aria-labelledby="createClassModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createClassModalLabel">Create a New Class</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="create-class">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="class_name" class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Class Name</label>
                                <input type="text" class="form-control" id="class_name" name="class_name" placeholder="e.g. Standard One A" required style="border-radius:10px;">
                            </div>
                            <div class="mb-3">
                                <label for="grade_level" class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Grade Level</label>
                                <select class="form-control" id="grade_level" name="grade_level" required style="border-radius:10px;">
                                    <option value="">-- Select Grade Level --</option>
                                    <option value="Pre-Primary 1">Pre-Primary 1</option>
                                    <option value="Pre-Primary 2">Pre-Primary 2</option>
                                    <option value="Standard 1">Standard 1</option>
                                    <option value="Standard 2">Standard 2</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="academic_year" class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Academic Year</label>
                                <input type="number" class="form-control" id="academic_year" name="academic_year" value="2026" min="2024" max="2030" required style="border-radius:10px;">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                            <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                                <i class="fas fa-plus-circle me-2"></i>Create Class
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
