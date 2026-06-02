<?php
$base = $base_path ?? '';
$lang = $current_lang ?? 'en';
$logo_src = $base . 'assets/images/logo.png';
$role_label = ucfirst($dashboard_role ?? auth_role());
$dashboard_page_title = $dashboard_page_title ?? '';

$notification_count = 0;
$notifications = [];
if (isset($database) && auth_role() === 'parent') {
    $parent_id = auth_user_id();
    $notifications = $database->fetchAll("
        SELECT n.*, u.first_name, u.last_name
        FROM notifications n
        JOIN users u ON n.related_user_id = u.user_id
        WHERE n.user_id = ? AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT 10
    ", [$parent_id]);
    $notification_count = count($notifications);
}
?><header class="dashboard-topbar" role="banner">
    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="dashboardSidebar">
        <i class="fas fa-bars sidebar-toggle-icon-menu" aria-hidden="true"></i>
        <i class="fas fa-chevron-left sidebar-toggle-icon-collapse" aria-hidden="true"></i>
    </button>

    <div class="dashboard-topbar-leading">
        <?php if ($dashboard_page_title !== ''): ?>
            <h1 class="dashboard-page-title"><?php echo htmlspecialchars($dashboard_page_title); ?></h1>
        <?php else: ?>
            <a href="<?php echo $base; ?>index?lang=<?php echo $lang; ?>" class="dashboard-topbar-brand">
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="" class="navbar-logo" width="40" height="40">
                <span class="brand-main">Kona Ya Hisabati</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="dashboard-topbar-meta">
        <?php if (auth_role() === 'parent'): ?>
            <div class="notification-wrapper dashboard-notification-wrap">
                <button type="button" class="notification-btn dashboard-notification-btn" id="notificationBtn" aria-label="Notifications">
                    <i class="fas fa-bell" aria-hidden="true"></i>
                    <?php if ($notification_count > 0): ?>
                        <span class="notification-badge dashboard-notification-badge"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </button>
                <div id="notificationDropdown" class="notification-dropdown dashboard-notification-dropdown" style="display: none;">
                    <?php if (empty($notifications)): ?>
                        <div class="dashboard-notification-empty">
                            <i class="fas fa-bell-slash" aria-hidden="true"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): ?>
                            <div class="notification-item" role="button" tabindex="0" onclick="markAsRead(<?php echo (int) $notif['notification_id']; ?>)">
                                <div class="dashboard-notification-item-inner">
                                    <div class="dashboard-notification-avatar"><i class="fas fa-child" aria-hidden="true"></i></div>
                                    <div>
                                        <p class="dashboard-notification-name"><?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></p>
                                        <p class="dashboard-notification-msg"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <p class="dashboard-notification-time"><?php echo date('M d, H:i', strtotime($notif['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="dashboard-notification-footer">
                            <a href="<?php echo $base; ?>parent/notifications">View All Notifications</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="dashboard-user-info">
            <div class="dashboard-welcome">
                <span class="dashboard-welcome-text">Welcome,</span>
                <span class="dashboard-user-name"><?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?></span>
            </div>
            <div class="dashboard-user-role"><?php echo htmlspecialchars($role_label); ?></div>
        </div>
        <div class="dashboard-user-profile" onclick="openModal('manageAccountModal')">
            <div class="dashboard-profile-image">
                <?php if (!empty($_SESSION['profile_image'])): ?>
                    <img src="<?php echo htmlspecialchars($base . $_SESSION['profile_image']); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Manage Account Modal -->
<div id="manageAccountModal" class="kona-modal-overlay" aria-hidden="true">
    <div class="kona-modal" role="dialog">
        <div class="kona-modal-header"><h3>Manage Account</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
        <form id="manageAccountForm" action="<?php echo $base; ?>update-profile" method="POST" enctype="multipart/form-data">
            <div class="kona-modal-body">
                <div class="form-group-child">
                    <label class="form-label-child">Profile Picture</label>
                    <div class="profile-upload-wrapper">
                        <div class="profile-preview">
                            <?php if (!empty($_SESSION['profile_image'])): ?>
                                <img src="<?php echo htmlspecialchars($base . $_SESSION['profile_image']); ?>" alt="Profile Preview">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="profile_picture" class="form-control-child" accept="image/*" onchange="previewProfile(this)">
                    </div>
                </div>
                <div class="form-group-child">
                    <label class="form-label-child">First Name</label>
                    <input type="text" name="first_name" class="form-control-child" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group-child">
                    <label class="form-label-child">Last Name</label>
                    <input type="text" name="last_name" class="form-control-child" value="<?php echo htmlspecialchars($_SESSION['last_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group-child">
                    <label class="form-label-child">Email</label>
                    <input type="email" name="email" class="form-control-child" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>">
                </div>
            </div>
            <div class="kona-modal-footer">
                <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn-child btn-child-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php if (auth_role() === 'parent'): ?>
<script>
document.getElementById('notificationBtn')?.addEventListener('click', function() {
    const dropdown = document.getElementById('notificationDropdown');
    if (!dropdown) return;
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.notification-wrapper')) {
        const dropdown = document.getElementById('notificationDropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
});

function markAsRead(notificationId) {
    fetch('<?php echo $base; ?>parent/mark-notification-read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + notificationId
    }).then(() => location.reload());
}
</script>
<?php endif; ?>

<script>
function previewProfile(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.profile-preview');
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

