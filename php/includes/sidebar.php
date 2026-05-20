<?php
/**
 * Role-based dashboard sidebar. Set $dashboard_role and $sidebar_active before include.
 */
require_once __DIR__ . '/url_helpers.php';

$lang = $current_lang ?? 'en';
$role = $dashboard_role ?? auth_role();
$active = $sidebar_active ?? '';

$home_href = app_in_role_folder($role)
    ? '../index.php?lang=' . urlencode($lang)
    : app_site_url('index.php?lang=' . urlencode($lang));

$menus = [
    'teacher' => [
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => $t['sb_teacher_dashboard'] ?? 'Dashboard'],
        ['id' => 'classes', 'href' => 'manage-classes.php', 'icon' => 'fa-chalkboard-teacher', 'label' => $t['sb_teacher_classes'] ?? 'Classes'],
        ['id' => 'learners', 'href' => 'learners.php', 'icon' => 'fa-users', 'label' => $t['nav_teacher_learners'] ?? 'Learners'],
        ['id' => 'assign', 'href' => 'assign-activity.php', 'icon' => 'fa-tasks', 'label' => $t['sb_teacher_assign'] ?? 'Assign Activity'],
        ['id' => 'progress', 'href' => 'learners.php', 'icon' => 'fa-chart-line', 'label' => $t['sb_teacher_progress'] ?? 'Learner Progress'],
        ['id' => 'lesson-plans', 'href' => 'lesson-plans.php', 'icon' => 'fa-book-open', 'label' => $t['sb_teacher_lesson_plans'] ?? 'Lesson Plans'],
        ['id' => 'all-activities', 'href' => 'all-activities.php', 'icon' => 'fa-list', 'label' => $t['sb_teacher_all_activities'] ?? 'All Activities'],
        ['id' => 'activity-library', 'href' => 'activity-library.php', 'icon' => 'fa-th-large', 'label' => $t['sb_teacher_activities'] ?? 'Activity Summary'],
    ],
    'parent' => [
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => $t['sb_parent_dashboard'] ?? 'Dashboard'],
        ['id' => 'claim', 'href' => 'dashboard.php?claim=1', 'icon' => 'fa-key', 'label' => $t['sb_parent_claim'] ?? 'Claim Child'],
        ['id' => 'guide', 'href' => 'guide.php?lang=' . urlencode($lang), 'icon' => 'fa-book-open', 'label' => $t['nav_parent'] ?? 'Parent Guide'],
    ],
    'learner' => [
        ['id' => 'dashboard', 'href' => 'dashboard.php?lang=' . urlencode($lang), 'icon' => 'fa-star', 'label' => $t['sb_learner_home'] ?? 'My Corner'],
        ['id' => 'learn', 'href' => 'categories.php?lang=' . urlencode($lang), 'icon' => 'fa-play-circle', 'label' => $t['nav_learner'] ?? 'Start Learning'],
        ['id' => 'assigned', 'href' => 'assigned.php?lang=' . urlencode($lang), 'icon' => 'fa-clipboard-list', 'label' => $t['sb_learner_assigned'] ?? 'Assigned Activities'],
    ],
    'admin' => [
        ['id' => 'dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-tachometer-alt', 'label' => $t['sb_admin_dashboard'] ?? 'Dashboard'],
        ['id' => 'users', 'href' => 'users.php', 'icon' => 'fa-users-cog', 'label' => $t['sb_admin_users'] ?? 'Manage Users'],
        ['id' => 'modules', 'href' => 'modules.php', 'icon' => 'fa-cubes', 'label' => $t['sb_admin_modules'] ?? 'Modules'],
        ['id' => 'upload', 'href' => 'upload-content.php', 'icon' => 'fa-cloud-upload-alt', 'label' => $t['sb_admin_upload'] ?? 'Upload Content'],
    ],
];

$items = $menus[$role] ?? [];
$logout_href = app_in_role_folder($role)
    ? '../logout.php'
    : 'logout.php';
$logo_src = app_in_role_folder($role)
    ? '../assets/images/logo.png'
    : 'assets/images/logo.png';
$home_href = app_in_role_folder($role)
    ? '../index.php?lang=' . urlencode($lang)
    : 'index.php?lang=' . urlencode($lang);
?><aside class="dashboard-sidebar" id="dashboardSidebar" aria-label="Dashboard navigation">
    <div class="sidebar-logo">
        <a href="<?php echo htmlspecialchars($home_href); ?>" class="sidebar-logo-link">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Kona Ya Hisabati" width="60" height="60">
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul class="sidebar-menu">
            <?php foreach ($items as $item): ?>
            <li class="sidebar-item<?php echo $active === $item['id'] ? ' active' : ''; ?>">
                <a href="<?php echo htmlspecialchars($item['href']); ?>" class="sidebar-link">
                    <i class="fas <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($item['label']); ?></span>
                </a>
            </li>
            <?php endforeach; ?>
            <li class="sidebar-item">
                <a href="<?php echo htmlspecialchars($logout_href); ?>" class="sidebar-link">
                    <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
