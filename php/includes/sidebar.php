<?php
require_once __DIR__ . '/url_helpers.php';

$lang = $current_lang ?? 'en';
$role = $dashboard_role ?? auth_role();
$active = $sidebar_active ?? '';

$items = [];
$subtitle = '';

switch ($role) {
    case 'teacher':
        $subtitle = 'TEACHER';
        $items = [
            ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'classes', 'href' => 'manage-classes.php', 'icon' => 'fa-chalkboard-teacher', 'label' => 'Classes'],
            ['id' => 'learners', 'href' => 'learners.php', 'icon' => 'fa-users', 'label' => 'Learners'],
            ['id' => 'assign', 'href' => 'assign-activity.php', 'icon' => 'fa-tasks', 'label' => 'Assign Activity'],
            ['id' => 'progress', 'href' => 'learners.php', 'icon' => 'fa-chart-line', 'label' => 'Learner Progress'],
            ['id' => 'lesson-plans', 'href' => 'lesson-plans.php', 'icon' => 'fa-book-open', 'label' => 'Lesson Plans'],
            ['id' => 'all-activities', 'href' => 'all-activities.php', 'icon' => 'fa-list', 'label' => 'All Activities'],
            ['id' => 'activity-library', 'href' => 'activity-library.php', 'icon' => 'fa-th-large', 'label' => 'Activity Summary'],
        ];
        break;
    case 'parent':
        $subtitle = 'PARENT';
        $items = [
            ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'claim', 'href' => 'dashboard.php?claim=1', 'icon' => 'fa-key', 'label' => 'Claim Child'],
            ['id' => 'topup', 'href' => app_in_role_folder($role) ? '../topup.php' : 'topup.php', 'icon' => 'fa-wallet', 'label' => 'Topup'],
            ['id' => 'guide', 'href' => 'guide.php?lang=' . urlencode($lang), 'icon' => 'fa-book-open', 'label' => 'Parent Guide'],
        ];
        break;
    case 'learner':
        $subtitle = 'LEARNER';
        $items = [
            ['id' => 'dashboard', 'href' => 'dashboard.php?lang=' . urlencode($lang), 'icon' => 'fa-star', 'label' => 'My Corner'],
            ['id' => 'learn', 'href' => 'categories.php?lang=' . urlencode($lang), 'icon' => 'fa-play-circle', 'label' => 'Start Learning'],
            ['id' => 'assigned', 'href' => 'assigned.php?lang=' . urlencode($lang), 'icon' => 'fa-clipboard-list', 'label' => 'Assigned Activities'],
        ];
        break;
    case 'admin':
        $subtitle = 'ADMIN';
        $items = [
            ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'announcements', 'href' => 'announcements.php', 'icon' => 'fa-bullhorn', 'label' => 'Announcements'],
            ['id' => 'announcement-ticker', 'href' => 'announcement-ticker.php', 'icon' => 'fa-scroll', 'label' => 'Ticker Messages'],
            ['id' => 'hero-slides', 'href' => 'hero-slides.php', 'icon' => 'fa-images', 'label' => 'Hero Slides'],
            ['id' => 'notes', 'href' => 'notes.php', 'icon' => 'fa-sticky-note', 'label' => 'Notes Board'],
            ['id' => 'events', 'href' => 'events.php', 'icon' => 'fa-calendar-alt', 'label' => 'Events Calendar'],
            ['id' => 'governance', 'href' => 'governance.php', 'icon' => 'fa-users-cog', 'label' => 'Governance'],
            ['id' => 'users', 'href' => 'users.php', 'icon' => 'fa-users-cog', 'label' => 'Manage Users'],
            ['id' => 'modules', 'href' => 'modules.php', 'icon' => 'fa-cubes', 'label' => 'Modules'],
            ['id' => 'payments', 'href' => 'payments.php', 'icon' => 'fa-credit-card', 'label' => 'Payments'],
            ['id' => 'upload', 'href' => 'upload-content.php', 'icon' => 'fa-cloud-upload-alt', 'label' => 'Upload Content'],
            ['id' => 'logs', 'href' => 'logs.php', 'icon' => 'fa-clipboard-list', 'label' => 'Error Logs'],
        ];
        break;
}

$logout_href = app_in_role_folder($role) ? '../logout.php' : 'logout.php';
$logo_src = app_in_role_folder($role) ? '../assets/images/logo.png' : 'assets/images/logo.png';
$home_href = app_in_role_folder($role) ? '../index.php?lang=' . urlencode($lang) : 'index.php?lang=' . urlencode($lang);
$role_label = ucfirst($role);
?><ul class="navbar-nav sidebar sidebar-light accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo htmlspecialchars($home_href); ?>">
        <div class="sidebar-brand-icon">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Kona Ya Hisabati" width="36" height="36" style="border-radius:50%;object-fit:contain;">
        </div>
        <div class="sidebar-brand-text mx-3"><?php echo htmlspecialchars($subtitle); ?></div>
    </a>
    <hr class="sidebar-divider my-0">
    <hr class="sidebar-divider">
    <div class="sidebar-heading"><?php echo htmlspecialchars($role_label); ?> Menu</div>
    <?php foreach ($items as $item): ?>
    <li class="nav-item<?php echo $active === $item['id'] ? ' active' : ''; ?>">
        <a class="nav-link" href="<?php echo htmlspecialchars($item['href']); ?>">
            <i class="fas fa-fw <?php echo htmlspecialchars($item['icon']); ?>"></i>
            <span><?php echo htmlspecialchars($item['label']); ?></span>
        </a>
    </li>
    <?php endforeach; ?>
    <hr class="sidebar-divider">
    <li class="nav-item">
        <a class="nav-link" href="<?php echo htmlspecialchars($logout_href); ?>">
            <i class="fas fa-fw fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </li>
    <hr class="sidebar-divider">
    <div class="version" id="version-ruangadmin">Kona Ya Hisabati v1.0</div>
</ul>
