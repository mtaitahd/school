<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
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

require_once '../php/includes/lang.php';
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
<div class="text-center mb-30">
            <div class="mt-20" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="learners.php?add=1" class="btn-child btn-child-primary" style="text-decoration: none;">
                    <i class="fas fa-user-plus me-2"></i>Add Student
                </a>
                <a href="learners.php" class="btn-child btn-child-secondary btn-child-large" style="text-decoration: none;">
                    <i class="fas fa-users" style="margin-right: 8px;"></i>Manage Learners
                </a>
                <a href="create-class.php" class="btn-child btn-child-info btn-child-large" style="text-decoration: none;">
                    <i class="fas fa-plus-circle" style="margin-right: 8px;"></i>Create Class
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="dashboard-card-title">Total Learners</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-blue); margin: 0;">
                        <?php echo count($learners); ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="dashboard-card-title">Completed Activities</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-green); margin: 0;">
                        <?php 
                        $total_completed = array_sum(array_column($learners, 'completed_activities'));
                        echo $total_completed;
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="dashboard-card-title">Total Stars Earned</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-yellow); margin: 0;">
                        <?php 
                        $total_stars = array_sum(array_column($learners, 'total_stars'));
                        echo $total_stars;
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-purple);">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="dashboard-card-title">Active Modules</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-purple); margin: 0;">
                        <?php echo count($module_stats); ?>
                    </p>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
