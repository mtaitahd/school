<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
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

require_once __DIR__ . '/../php/includes/lang.php';
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
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Dashboard</h1>
            <a href="users" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;text-decoration:none;">
                <i class="fas fa-user-plus me-2"></i>Manage Users
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-blue);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-blue);">Total Users</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-blue);"><?php echo $stats['users']; ?></div>
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
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-green);">Learners</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-green);"><?php echo $stats['learners']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-green);"><i class="fas fa-child text-white"></i></div>
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
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:#e6a800;">Teachers</div>
                                <div class="h3 mb-0 fw-bold" style="color:#e6a800;"><?php echo $stats['teachers']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:#e6a800;"><i class="fas fa-chalkboard-teacher text-white"></i></div>
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
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-purple);">Parents</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-purple);"><?php echo $stats['parents']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-purple);"><i class="fas fa-user-friends text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-orange);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-orange);">Modules</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-orange);"><?php echo $stats['modules']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-orange);"><i class="fas fa-cubes text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2" style="border-left:4px solid var(--primary-red);">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-red);">Activities</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-red);"><?php echo $stats['activities']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-red);"><i class="fas fa-tasks text-white"></i></div>
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
                                <div class="text-xs fw-bold text-uppercase mb-1" style="color:var(--primary-green);">Completed</div>
                                <div class="h3 mb-0 fw-bold" style="color:var(--primary-green);"><?php echo $stats['completed']; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="icon-circle" style="background:var(--primary-green);"><i class="fas fa-check-circle text-white"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>





