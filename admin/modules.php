<?php
require_once '../php/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once '../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$modules = $database->fetchAll("SELECT * FROM modules ORDER BY order_index ASC");

require_once '../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'modules';
$dashboard_page_title = 'Modules';
$lang_page = 'modules.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modules - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Module Management</h1>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Modules</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Module Name</th>
                                <th>Icon</th>
                                <th>Activities</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($modules as $module): ?>
                            <?php $activity_count = $database->fetchOne("SELECT COUNT(*) as count FROM activities WHERE module_id = ?", [$module['module_id']])['count']; ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($module['module_name']); ?></td>
                                <td><i class="fas <?php echo $module['module_icon']; ?>"></i></td>
                                <td><?php echo $activity_count; ?></td>
                                <td>
                                    <?php if ($module['is_active']): ?>
                                        <span class="text-success fw-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick="editModule(<?php echo $module['module_id']; ?>)"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="toggleModule(<?php echo $module['module_id']; ?>)"><i class="fas fa-power-off"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function toggleModule(moduleId) {
            if (confirm('Toggle module status?')) window.location.href = 'admin-toggle-module.php?module_id=' + moduleId;
        }
        function editModule(moduleId) {
            alert('Edit module functionality coming soon');
        }
    </script>
</body>
</html>
