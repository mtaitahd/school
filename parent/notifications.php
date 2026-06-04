<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/subscription.php';

sec_send_headers();
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}

sub_require_access();

// Check if user is logged in as parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login.php');
    exit;
}

$parent_id = $_SESSION['user_id'];

// Handle mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_all_read') {
    csrf_require();
    $database->execute(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
        [$parent_id]
    );
    header('Location: notifications.php?success=all_marked');
    exit;
}

// Fetch all notifications
$notifications = $database->fetchAll("
    SELECT n.*, u.first_name, u.last_name 
    FROM notifications n
    JOIN users u ON n.related_user_id = u.user_id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
", [$parent_id]);

// Mark unread notifications as read when viewing
$database->execute(
    "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0",
    [$parent_id]
);

require_once __DIR__ . '/../php/includes/lang.php';
$current_lang = $_SESSION['lang'] ?? 'en';
$base_path = '../';
$dashboard_role = 'parent';
$sidebar_active = 'dashboard';
$lang_page = 'notifications.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Notifications</h1>
        <p class="activity-instruction">Stay updated on your child's progress and activities</p>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'all_marked'): ?>
        <div class="alert-child alert-child-success mb-30">
            <i class="fas fa-check-circle me-2"></i>All notifications marked as read!
        </div>
    <?php endif; ?>

    <!-- Notifications List -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                <i class="fas fa-bell"></i>
            </div>
            <h3 class="dashboard-card-title">All Notifications</h3>
            <?php if (!empty($notifications)): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn-child btn-child-secondary" style="padding: 8px 16px; font-size: 0.9rem;">
                        <i class="fas fa-check-double me-2"></i>Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="text-center py-30">
                <i class="fas fa-bell-slash" style="font-size: 48px; color: var(--text-light); margin-bottom: 15px;"></i>
                <p style="color: var(--text-light);">No notifications yet. You'll be notified when your child completes activities or receives assignments.</p>
            </div>
        <?php else: ?>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item" style="padding: 15px; border-bottom: 1px solid #eee; <?php echo $notif['is_read'] ? 'background: #f9f9f9;' : 'background: white;'; ?>">
                        <div style="display: flex; align-items: start; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: <?php echo $notif['is_read'] ? 'var(--text-light)' : 'var(--primary-blue)'; ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem; flex-shrink: 0;">
                                <i class="fas fa-<?php echo $notif['notification_type'] === 'assignment' ? 'tasks' : ($notif['notification_type'] === 'completion' ? 'check-circle' : ($notif['notification_type'] === 'badge' ? 'award' : 'bell')); ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                    <h4 style="margin: 0; color: var(--text-dark); font-size: 1rem;">
                                        <?php echo htmlspecialchars($notif['title']); ?>
                                    </h4>
                                    <span style="font-size: 0.75rem; color: var(--text-light); white-space: nowrap; margin-left: 10px;">
                                        <?php echo date('M d, H:i', strtotime($notif['created_at'])); ?>
                                    </span>
                                </div>
                                <?php if ($notif['related_user_id']): ?>
                                    <p style="margin: 5px 0; color: var(--text-light); font-size: 0.9rem;">
                                        <i class="fas fa-child me-1"></i><?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?>
                                    </p>
                                <?php endif; ?>
                                <p style="margin: 5px 0; color: var(--text-dark); font-size: 0.95rem;">
                                    <?php echo htmlspecialchars($notif['message']); ?>
                                </p>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn-child btn-child-primary" style="padding: 6px 12px; font-size: 0.85rem; margin-top: 10px; display: inline-block;">
                                        <i class="fas fa-external-link-alt me-1"></i>View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



