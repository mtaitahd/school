<?php
require_once __DIR__ . '/url_helpers.php';

$lang = $current_lang ?? 'en';
$role = $dashboard_role ?? auth_role();
$active = $sidebar_active ?? '';

$items = [];
$subtitle = '';

$in_role_folder = app_in_role_folder($role);
$role_prefix = $in_role_folder ? '' : $role . '/';

switch ($role) {
    case 'teacher':
        $subtitle = 'TEACHER';
        $items = [
            ['id' => 'dashboard', 'href' => $role_prefix . 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'classes', 'href' => $role_prefix . 'manage-classes', 'icon' => 'fa-chalkboard-teacher', 'label' => 'Classes'],
            ['id' => 'learners', 'href' => $role_prefix . 'learners', 'icon' => 'fa-users', 'label' => 'Learners'],
            ['id' => 'import', 'href' => $role_prefix . 'import-students', 'icon' => 'fa-file-import', 'label' => 'Import Students'],
            ['id' => 'assign', 'href' => $role_prefix . 'assign-activity', 'icon' => 'fa-tasks', 'label' => 'Assign Activity'],
            ['id' => 'progress', 'href' => $role_prefix . 'learners', 'icon' => 'fa-chart-line', 'label' => 'Learner Progress'],
            ['id' => 'lesson-plans', 'href' => $role_prefix . 'lesson-plans', 'icon' => 'fa-book-open', 'label' => 'Lesson Plans'],
            ['id' => 'all-activities', 'href' => $role_prefix . 'all-activities', 'icon' => 'fa-list', 'label' => 'All Activities'],
            ['id' => 'activity-library', 'href' => $role_prefix . 'activity-library', 'icon' => 'fa-th-large', 'label' => 'Activity Summary'],
        ];
        break;
    case 'parent':
        $subtitle = 'PARENT';
        $items = [
            ['id' => 'dashboard', 'href' => $role_prefix . 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'claim', 'href' => $role_prefix . 'dashboard?claim=1', 'icon' => 'fa-key', 'label' => 'Claim Child'],
            ['id' => 'topup', 'href' => $in_role_folder ? '../payment' : 'payment', 'icon' => 'fa-wallet', 'label' => 'Payment'],
            ['id' => 'guide', 'href' => $role_prefix . 'guide?lang=' . urlencode($lang), 'icon' => 'fa-book-open', 'label' => 'Parent Guide'],
        ];
        break;
    case 'learner':
        $subtitle = 'LEARNER';
        $items = [
            ['id' => 'dashboard', 'href' => $role_prefix . 'dashboard?lang=' . urlencode($lang), 'icon' => 'fa-star', 'label' => 'My Corner'],
            ['id' => 'learn', 'href' => $role_prefix . 'categories?lang=' . urlencode($lang), 'icon' => 'fa-play-circle', 'label' => 'Start Learning'],
            ['id' => 'assigned', 'href' => $role_prefix . 'assigned?lang=' . urlencode($lang), 'icon' => 'fa-clipboard-list', 'label' => 'Assigned Activities'],
        ];
        break;
    case 'admin':
        $subtitle = 'ADMIN';
        $items = [
            ['id' => 'dashboard', 'href' => $role_prefix . 'dashboard', 'icon' => 'fa-tachometer-alt', 'label' => 'Dashboard'],
            ['id' => 'announcements', 'href' => $role_prefix . 'announcements', 'icon' => 'fa-bullhorn', 'label' => 'Announcements'],
            ['id' => 'announcement-ticker', 'href' => $role_prefix . 'announcement-ticker', 'icon' => 'fa-scroll', 'label' => 'Ticker Messages'],
            ['id' => 'hero-slides', 'href' => $role_prefix . 'hero-slides', 'icon' => 'fa-images', 'label' => 'Hero Slides'],
            ['id' => 'notes', 'href' => $role_prefix . 'notes', 'icon' => 'fa-sticky-note', 'label' => 'Notes Board'],
            ['id' => 'events', 'href' => $role_prefix . 'events', 'icon' => 'fa-calendar-alt', 'label' => 'Events Calendar'],
            ['id' => 'governance', 'href' => $role_prefix . 'governance', 'icon' => 'fa-users-cog', 'label' => 'Governance'],
            ['id' => 'users', 'label' => 'Manage Users', 'icon' => 'fa-users-cog', 'children' => [
                ['id' => 'all-users', 'href' => $role_prefix . 'users', 'label' => 'All Users'],
                ['id' => 'parents', 'href' => $role_prefix . 'parents', 'label' => 'Parents'],
                ['id' => 'learners', 'href' => $role_prefix . 'learners', 'label' => 'Learners'],
                ['id' => 'teachers', 'href' => $role_prefix . 'teachers', 'label' => 'Teachers'],
                ['id' => 'link-children', 'href' => $role_prefix . 'link-children', 'label' => 'Link Children'],
            ]],
            ['id' => 'modules', 'href' => $role_prefix . 'modules', 'icon' => 'fa-cubes', 'label' => 'Modules'],
            ['id' => 'payments', 'href' => $role_prefix . 'payments', 'icon' => 'fa-credit-card', 'label' => 'Payments'],
            ['id' => 'upload', 'href' => $role_prefix . 'upload-content', 'icon' => 'fa-cloud-upload-alt', 'label' => 'Upload Content'],
            ['id' => 'logs', 'href' => $role_prefix . 'logs', 'icon' => 'fa-clipboard-list', 'label' => 'Error Logs'],
        ];
        break;
}

$logout_href = app_in_role_folder($role) ? '../logout' : 'logout';
$logo_src = app_in_role_folder($role) ? '../assets/images/logo.png' : 'assets/images/logo.png';
$home_href = app_in_role_folder($role) ? '../index?lang=' . urlencode($lang) : 'index?lang=' . urlencode($lang);
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
        <?php if (isset($item['children'])): ?>
        <li class="nav-item">
            <a class="nav-link<?php echo in_array($active, array_column($item['children'], 'id')) ? '' : ' collapsed'; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $item['id']; ?>">
                <i class="fas fa-fw <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
            <div id="collapse<?php echo $item['id']; ?>" class="collapse<?php echo in_array($active, array_column($item['children'], 'id')) ? ' show' : ''; ?>" data-bs-parent="#accordionSidebar">
                <div class="collapse-inner">
                    <?php foreach ($item['children'] as $child): ?>
                    <a class="collapse-item<?php echo $active === $child['id'] ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($child['href']); ?>"><?php echo htmlspecialchars($child['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </li>
        <?php else: ?>
        <li class="nav-item<?php echo $active === $item['id'] ? ' active' : ''; ?>">
            <a class="nav-link" href="<?php echo htmlspecialchars($item['href']); ?>">
                <i class="fas fa-fw <?php echo htmlspecialchars($item['icon']); ?>"></i>
                <span><?php echo htmlspecialchars($item['label']); ?></span>
            </a>
        </li>
        <?php endif; ?>
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
