<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$stats = [
    'users' => $database->fetchOne("SELECT COUNT(*) as count FROM users")['count'],
    'learners' => $database->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'learner'")['count'],
    'teachers' => $database->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'")['count'],
    'parents' => $database->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'parent'")['count'],
    'modules' => $database->fetchOne("SELECT COUNT(*) as count FROM modules WHERE is_active = 1")['count'],
    'activities' => $database->fetchOne("SELECT COUNT(*) as count FROM activities WHERE is_active = 1")['count'],
    'completed' => $database->fetchOne("SELECT COUNT(*) as count FROM progress WHERE completed = 1")['count']
];

$recent_users = $database->fetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
$all_users = $database->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
$modules = $database->fetchAll("SELECT * FROM modules ORDER BY order_index ASC");

require_once '../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'dashboard';
$dashboard_page_title = 'Admin Dashboard';
$lang_page = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            <button type="button" class="btn-child btn-child-primary" onclick="openModal('addUserModal')">
                <i class="fas fa-user-plus me-2"></i>Add User
            </button>
        </div>
        
        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-blue);"><i class="fas fa-users"></i></div>
                        <h3 class="dashboard-card-title">Total Users</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-blue); margin: 0;"><?php echo $stats['users']; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);"><i class="fas fa-child"></i></div>
                        <h3 class="dashboard-card-title">Learners</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-green); margin: 0;"><?php echo $stats['learners']; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-yellow);"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3 class="dashboard-card-title">Teachers</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-yellow); margin: 0;"><?php echo $stats['teachers']; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-purple);"><i class="fas fa-user-friends"></i></div>
                        <h3 class="dashboard-card-title">Parents</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-purple); margin: 0;"><?php echo $stats['parents']; ?></p>
                </div>
            </div>
        </div>

        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-orange);"><i class="fas fa-cubes"></i></div>
                        <h3 class="dashboard-card-title">Modules</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-orange); margin: 0;"><?php echo $stats['modules']; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-red);"><i class="fas fa-tasks"></i></div>
                        <h3 class="dashboard-card-title">Activities</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-red); margin: 0;"><?php echo $stats['activities']; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);"><i class="fas fa-check-circle"></i></div>
                        <h3 class="dashboard-card-title">Completed</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-green); margin: 0;"><?php echo $stats['completed']; ?></p>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>





