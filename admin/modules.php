<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
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
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            <h1 class="activity-title mb-0">Module Management</h1>
        </div>
        
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);"><i class="fas fa-cubes"></i></div>
                <h3 class="dashboard-card-title">Modules</h3>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--background-light);">
                            <th style="padding: 15px; text-align: left;">Module Name</th>
                            <th style="padding: 15px; text-align: left;">Icon</th>
                            <th style="padding: 15px; text-align: left;">Activities</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $module): ?>
                        <?php $activity_count = $database->fetchOne("SELECT COUNT(*) as count FROM activities WHERE module_id = ?", [$module['module_id']])['count']; ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;"><?php echo htmlspecialchars($module['module_name']); ?></td>
                            <td style="padding: 15px;"><i class="fas <?php echo $module['module_icon']; ?>"></i></td>
                            <td style="padding: 15px;"><?php echo $activity_count; ?></td>
                            <td style="padding: 15px;">
                                <?php if ($module['is_active']): ?>
                                    <span style="color: var(--primary-green);"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span style="color: var(--primary-red);"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <button class="btn-child btn-child-primary" style="min-height: 35px; min-width: 35px; font-size: 0.8rem;" onclick="editModule(<?php echo $module['module_id']; ?>)"><i class="fas fa-edit"></i></button>
                                <button class="btn-child btn-child-yellow" style="min-height: 35px; min-width: 35px; font-size: 0.8rem;" onclick="toggleModule(<?php echo $module['module_id']; ?>)"><i class="fas fa-power-off"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
