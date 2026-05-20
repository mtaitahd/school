<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

// Fetch all activities with module information
$activities = $database->fetchAll("
    SELECT a.*, m.module_name 
    FROM activities a 
    JOIN modules m ON a.module_id = m.module_id 
    ORDER BY a.order_index ASC
");

// Calculate statistics
$total_activities = count($activities);
$active_activities = count(array_filter($activities, fn($a) => $a['is_active']));
$quiz_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'quiz'));
$game_count = count(array_filter($activities, fn($a) => $a['activity_type'] === 'game'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Activities - Kona Ya Hisabati</title>
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
    $sidebar_active = 'all-activities';
    $lang_page = 'all-activities.php';
    include '../php/includes/dashboard-start.php';
    ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            
            <div>
                <h1 class="activity-title mb-0">All Activities</h1>
                <p class="activity-instruction mb-0">View and manage all learning activities in the system</p>
            </div>
            <button type="button" class="btn-child btn-child-primary" onclick="window.location.href='activity-library.php'">
                <i class="fas fa-plus-circle me-2"></i>Create Activity
            </button>
        </div>

        <!-- All Activities Table -->
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-list"></i>
                </div>
                <h3 class="dashboard-card-title">Activity List</h3>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--background-light);">
                            <th style="padding: 15px; text-align: left;">Activity</th>
                            <th style="padding: 15px; text-align: left;">Module</th>
                            <th style="padding: 15px; text-align: left;">Type</th>
                            <th style="padding: 15px; text-align: left;">Difficulty</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Order</th>
                            <th style="padding: 15px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activities as $activity): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($activity['activity_name']); ?></strong>
                                <?php if (!empty($activity['activity_description'])): ?>
                                <br><small style="color: var(--text-light);"><?php echo htmlspecialchars($activity['activity_description']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;"><?php echo htmlspecialchars($activity['module_name']); ?></td>
                            <td style="padding: 15px;">
                                <span style="background: var(--primary-blue); color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.8rem;">
                                    <?php echo ucfirst($activity['activity_type']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <span style="background: var(--primary-yellow); color: var(--text-dark); padding: 5px 10px; border-radius: 10px; font-size: 0.8rem;">
                                    <?php echo ucfirst($activity['difficulty_level']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($activity['is_active']): ?>
                                    <span style="color: var(--primary-green);"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span style="color: var(--primary-red);"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;"><?php echo (int)$activity['order_index']; ?></td>
                            <td style="padding: 15px;">
                                <a href="activity-library.php?edit=<?php echo $activity['activity_id']; ?>" 
                                   class="btn-child btn-child-warning" style="min-height: 35px; min-width: 35px; font-size: 0.8rem; padding: 0 10px; display: inline-block; text-decoration: none;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="activity-library.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this activity?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="activity_id" value="<?php echo $activity['activity_id']; ?>">
                                    <button type="submit" class="btn-child btn-child-danger" style="min-height: 35px; min-width: 35px; font-size: 0.8rem; padding: 0 10px; margin-left: 5px;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
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
</body>
</html>
