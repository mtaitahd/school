<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    if (isset($_POST['action'])) {
        // Create new module
        if ($_POST['action'] === 'create') {
            $module_name = trim($_POST['module_name']);
            $module_description = trim($_POST['module_description']);
            $module_icon = trim($_POST['module_icon']);
            $module_color = trim($_POST['module_color']);
            $audio_prompt = trim($_POST['audio_prompt']);
            $order_index = intval($_POST['order_index']);
            
            $sql = "INSERT INTO modules (module_name, module_description, module_icon, module_color, audio_prompt, order_index) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$module_name, $module_description, $module_icon, $module_color, $audio_prompt, $order_index];
            
            if ($database->insert($sql, $params)) {
                $success = "Module created successfully!";
            } else {
                $error = "Failed to create module.";
            }
        }
        
        // Update module
        if ($_POST['action'] === 'update') {
            $module_id = intval($_POST['module_id']);
            $module_name = trim($_POST['module_name']);
            $module_description = trim($_POST['module_description']);
            $module_icon = trim($_POST['module_icon']);
            $module_color = trim($_POST['module_color']);
            $audio_prompt = trim($_POST['audio_prompt']);
            $order_index = intval($_POST['order_index']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $sql = "UPDATE modules SET module_name = ?, module_description = ?, module_icon = ?, 
                    module_color = ?, audio_prompt = ?, order_index = ?, is_active = ? WHERE module_id = ?";
            $params = [$module_name, $module_description, $module_icon, $module_color, $audio_prompt, $order_index, $is_active, $module_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Module updated successfully!";
            } else {
                $error = "Failed to update module.";
            }
        }
        
        // Delete module
        if ($_POST['action'] === 'delete') {
            $module_id = intval($_POST['module_id']);
            
            $sql = "DELETE FROM modules WHERE module_id = ?";
            $params = [$module_id];
            
            if ($database->execute($sql, $params)) {
                $success = "Module deleted successfully!";
            } else {
                $error = "Failed to delete module.";
            }
        }
    }
}

// Fetch all modules
$modules = $database->fetchAll("SELECT * FROM modules ORDER BY order_index ASC");

// Fetch module details if editing
$editing_module = null;
if (isset($_GET['edit'])) {
    $module_id = intval($_GET['edit']);
    $editing_module = $database->fetchOne("
        SELECT * FROM modules WHERE module_id = ?
    ", [$module_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Modules - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once __DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'modules';
    $lang_page = 'dashboard.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Manage Modules</h1>
        <p class="activity-instruction">Create and manage learning modules</p>
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

    <!-- Create/Edit Module Form -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                <i class="fas fa-<?php echo $editing_module ? 'edit' : 'plus'; ?>"></i>
            </div>
            <h3 class="dashboard-card-title"><?php echo $editing_module ? 'Edit Module' : 'Create New Module'; ?></h3>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?php echo $editing_module ? 'update' : 'create'; ?>">
            <?php if ($editing_module): ?>
                <input type="hidden" name="module_id" value="<?php echo $editing_module['module_id']; ?>">
            <?php endif; ?>
            
            <div class="form-group-child">
                <label class="form-label-child">Module Name</label>
                <input type="text" class="form-control-child" name="module_name" 
                       value="<?php echo $editing_module ? htmlspecialchars($editing_module['module_name']) : ''; ?>" required>
            </div>
            
            <div class="form-group-child">
                <label class="form-label-child">Description</label>
                <textarea class="form-control-child" name="module_description" rows="3"><?php echo $editing_module ? htmlspecialchars($editing_module['module_description']) : ''; ?></textarea>
            </div>
            
            <div class="row-child">
                <div class="col-child-2">
                    <div class="form-group-child">
                        <label class="form-label-child">Module Icon (FontAwesome class)</label>
                        <input type="text" class="form-control-child" name="module_icon" 
                               value="<?php echo $editing_module ? htmlspecialchars($editing_module['module_icon']) : 'fa-book'; ?>" 
                               placeholder="e.g. fa-calculator, fa-shapes">
                    </div>
                </div>
                <div class="col-child-2">
                    <div class="form-group-child">
                        <label class="form-label-child">Module Color</label>
                        <input type="color" class="form-control-child" name="module_color" 
                               value="<?php echo $editing_module ? htmlspecialchars($editing_module['module_color']) : '#4A90E2'; ?>" 
                               style="height: 45px;">
                    </div>
                </div>
            </div>
            
            <div class="form-group-child">
                <label class="form-label-child">Audio Prompt (optional)</label>
                <input type="text" class="form-control-child" name="audio_prompt" 
                       value="<?php echo $editing_module ? htmlspecialchars($editing_module['audio_prompt']) : ''; ?>" 
                       placeholder="Audio file path or URL">
            </div>
            
            <div class="form-group-child">
                <label class="form-label-child">Order Index</label>
                <input type="number" class="form-control-child" name="order_index" 
                       value="<?php echo $editing_module ? htmlspecialchars($editing_module['order_index']) : '0'; ?>" min="0">
            </div>
            
            <?php if ($editing_module): ?>
                <div class="form-group-child">
                    <label class="form-label-child">
                        <input type="checkbox" name="is_active" <?php echo $editing_module['is_active'] ? 'checked' : ''; ?>>
                        Active Module
                    </label>
                </div>
            <?php endif; ?>
            
            <div class="text-center mt-30">
                <button type="submit" class="btn-child btn-child-primary btn-child-large">
                    <i class="fas fa-<?php echo $editing_module ? 'save' : 'plus-circle'; ?> me-2"></i>
                    <?php echo $editing_module ? 'Update Module' : 'Create Module'; ?>
                </button>
                <?php if ($editing_module): ?>
                    <a href="manage-modules" class="btn-child btn-child-secondary btn-child-large" style="margin-left: 10px;">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Modules List -->
    <?php if (!empty($modules)): ?>
        <div class="dashboard-card">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-list"></i>
                </div>
                <h3 class="dashboard-card-title">All Modules</h3>
            </div>
            <div style="max-height: 500px; overflow-y: auto;">
                <?php foreach ($modules as $module): ?>
                    <div style="padding: 20px; border-bottom: 1px solid #eee; <?php echo !$module['is_active'] ? 'opacity: 0.6;' : ''; ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div style="width: 50px; height: 50px; border-radius: 10px; background: <?php echo htmlspecialchars($module['module_color']); ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                                    <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?>"></i>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 5px 0;">
                                        <?php echo htmlspecialchars($module['module_name']); ?>
                                        <?php if (!$module['is_active']): ?>
                                            <span style="background: var(--primary-red); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem;">Inactive</span>
                                        <?php endif; ?>
                                    </h4>
                                    <p style="margin: 0; color: var(--text-light); font-size: 0.9rem;">
                                        <?php echo htmlspecialchars(substr($module['module_description'], 0, 100)) . (strlen($module['module_description']) > 100 ? '...' : ''); ?>
                                    </p>
                                    <p style="margin: 5px 0 0 0; color: var(--text-light); font-size: 0.85rem;">
                                        <i class="fas fa-sort-numeric-up me-1"></i>Order: <?php echo $module['order_index']; ?>
                                        <?php if ($module['audio_prompt']): ?>
                                            <span style="margin: 0 10px;">|</span>
                                            <i class="fas fa-volume-up me-1"></i>Audio: Yes
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <a href="manage-modules.php?edit=<?php echo $module['module_id']; ?>" 
                                   class="btn-child btn-child-warning" style="min-height: 40px; min-width: 40px;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this module? This will also delete all activities in this module.');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="module_id" value="<?php echo $module['module_id']; ?>">
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
            <i class="fas fa-cubes" style="font-size: 4rem; color: var(--text-light); margin-bottom: 20px;"></i>
            <h3>No Modules Yet</h3>
            <p style="color: var(--text-light);">Create your first module to get started!</p>
        </div>
    <?php endif; ?>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



