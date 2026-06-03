<?php
$base = $base_path ?? '';
$lang = $current_lang ?? 'en';
$role_label = ucfirst($dashboard_role ?? auth_role());
$dashboard_page_title = $dashboard_page_title ?? '';
$display_name = auth_display_name() ?: strtoupper($dashboard_role ?? auth_role());

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
?><nav class="navbar navbar-expand navbar-light bg-navbar topbar mb-4 static-top">
    <button id="sidebarToggleTop" class="btn btn-link rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <?php if ($dashboard_page_title !== ''): ?>
    <div class="d-sm-flex align-items-center justify-content-between mb-0" style="flex:1;">
        <h4 class="mb-0 text-white" style="font-family:'Poppins',sans-serif;font-weight:700;"><?php echo htmlspecialchars($dashboard_page_title); ?></h4>
    </div>
    <?php else: ?>
    <a href="<?php echo $base; ?>index?lang=<?php echo $lang; ?>" class="d-flex align-items-center gap-2 text-white text-decoration-none" style="flex:1;">
        <span style="font-family:'Poppins',sans-serif;font-weight:700;font-size:1.1rem;">Kona Ya Hisabati</span>
    </a>
    <?php endif; ?>

    <ul class="navbar-nav ml-auto">
        <?php if (auth_role() === 'parent'): ?>
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-bell fa-fw"></i>
                <?php if ($notification_count > 0): ?>
                <span class="badge badge-danger badge-counter"><?php echo $notification_count > 9 ? '9+' : $notification_count; ?></span>
                <?php endif; ?>
            </a>
            <div class="dropdown-list dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="alertsDropdown">
                <h6 class="dropdown-header">Notifications</h6>
                <?php if (empty($notifications)): ?>
                    <a class="dropdown-item d-flex align-items-center" href="#"><span>No new notifications</span></a>
                <?php else: ?>
                    <?php foreach ($notifications as $notif): ?>
                    <a class="dropdown-item d-flex align-items-center" href="#" onclick="markAsRead(<?php echo (int) $notif['notification_id']; ?>)">
                        <div class="mr-3"><div class="icon-circle bg-primary"><i class="fas fa-child text-white"></i></div></div>
                        <div>
                            <div class="small text-gray-500"><?php echo htmlspecialchars($notif['first_name'] . ' ' . $notif['last_name']); ?></div>
                            <span class="font-weight-bold"><?php echo htmlspecialchars($notif['message']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <a class="dropdown-item text-center small text-gray-500" href="<?php echo $base; ?>parent/notifications">View All Notifications</a>
                <?php endif; ?>
            </div>
        </li>
        <?php endif; ?>

        <div class="topbar-divider d-none d-sm-block"></div>

        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-white small"><?php echo htmlspecialchars($display_name); ?></span>
                <div class="img-profile rounded-circle" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.2);">
                    <i class="fas fa-user text-white" style="font-size:0.85rem;"></i>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#manageAccountModal">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i> Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="<?php echo $base; ?>logout">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>

<!-- Manage Account Modal -->
<div class="modal fade" id="manageAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);"><i class="fas fa-user-cog me-2" style="color:var(--primary-blue);"></i>Manage Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="manageAccountForm" action="<?php echo $base; ?>update-profile" method="POST" enctype="multipart/form-data">
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Profile Picture</label>
                        <div class="profile-upload-wrapper d-flex align-items-center gap-3">
                            <div class="profile-preview rounded-circle overflow-hidden" style="width:80px;height:80px;display:flex;align-items:center;justify-content:center;background:var(--background-light);font-size:2rem;color:var(--text-light);">
                                <?php if (!empty($_SESSION['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($base . $_SESSION['profile_image']); ?>" alt="Profile Preview" style="width:100%;height:100%;object-fit:cover;">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                            </div>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*" onchange="previewProfile(this)" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($_SESSION['last_name'] ?? ''); ?>" required style="border-radius:10px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" style="border-radius:10px;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;"><i class="fas fa-save me-2"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (auth_role() === 'parent'): ?>
<script>
document.getElementById('alertsDropdown')?.addEventListener('click', function(e) {
    e.preventDefault();
    var dd = this.nextElementSibling;
    if (dd) dd.classList.toggle('show');
});
function markAsRead(notificationId) {
    fetch('<?php echo $base; ?>parent/mark-notification-read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + notificationId
    }).then(function(r) { return r.json(); }).then(function() { location.reload(); });
}
</script>
<?php endif; ?>

<script>
function previewProfile(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.querySelector('.profile-preview');
            if (preview) preview.innerHTML = '<img src="' + e.target.result + '" alt="Profile Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
