<?php
/**
 * Role-based main navigation items. Sets $nav_items array.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/url_helpers.php';

$logged_role = auth_role();
$active = $active_nav ?? 'home';
$lang = $current_lang ?? 'en';

$home_href = ($logged_role && app_in_role_folder($logged_role))
    ? '../index.php?lang=' . urlencode($lang)
    : app_web_path('index.php?lang=' . urlencode($lang));

$nav_items = [];

switch ($logged_role) {
    case 'learner':
        $nav_items = [
            ['id' => 'home', 'href' => $home_href, 'icon' => 'fa-home', 'label' => $t['nav_home'] ?? 'Home'],
            ['id' => 'learner_dashboard', 'href' => app_learner_url('dashboard.php', ['lang' => $lang]), 'icon' => 'fa-star', 'label' => $t['sb_learner_home'] ?? 'My Corner'],
            ['id' => 'assigned', 'href' => app_learner_url('assigned.php', ['lang' => $lang]), 'icon' => 'fa-clipboard-list', 'label' => $t['sb_learner_assigned'] ?? 'Assigned Activities'],
        ];
        break;

    case 'teacher':
        $nav_items = [
            ['id' => 'home', 'href' => $home_href, 'icon' => 'fa-home', 'label' => $t['nav_home'] ?? 'Home'],
            ['id' => 'teacher_dashboard', 'href' => app_web_path('teacher/dashboard.php'), 'icon' => 'fa-tachometer-alt', 'label' => $t['nav_teacher_dashboard'] ?? 'Dashboard'],
            ['id' => 'teacher_learners', 'href' => app_web_path('teacher/learners.php'), 'icon' => 'fa-users', 'label' => $t['nav_teacher_learners'] ?? 'Learners'],
            ['id' => 'teacher_assign', 'href' => app_web_path('teacher/assign-activity.php'), 'icon' => 'fa-tasks', 'label' => $t['sb_teacher_assign'] ?? 'Assign Activity'],
            ['id' => 'teacher_lesson', 'href' => app_web_path('teacher/lesson-plans.php'), 'icon' => 'fa-book-open', 'label' => $t['nav_teacher_lesson'] ?? 'Lesson Plans'],
            ['id' => 'teacher_library', 'href' => app_web_path('teacher/activity-library.php'), 'icon' => 'fa-th-large', 'label' => $t['nav_teacher_library'] ?? 'Activity Library'],
        ];
        break;

    case 'parent':
        $nav_items = [
            ['id' => 'home', 'href' => $home_href, 'icon' => 'fa-home', 'label' => $t['nav_home'] ?? 'Home'],
            ['id' => 'parent_dashboard', 'href' => app_web_path('parent/dashboard.php'), 'icon' => 'fa-tachometer-alt', 'label' => $t['nav_parent_dashboard'] ?? 'Parent Dashboard'],
            ['id' => 'parent', 'href' => app_web_path('parent/guide.php?lang=' . urlencode($lang)), 'icon' => 'fa-book-open', 'label' => $t['nav_parent'] ?? 'Parent Guide'],
            ['id' => 'topup', 'href' => app_web_path('topup.php'), 'icon' => 'fa-wallet', 'label' => 'Topup'],
        ];
        break;

    case 'admin':
        $nav_items = [
            ['id' => 'home', 'href' => $home_href, 'icon' => 'fa-home', 'label' => $t['nav_home'] ?? 'Home'],
            ['id' => 'admin_dashboard', 'href' => app_web_path('admin/dashboard.php'), 'icon' => 'fa-tachometer-alt', 'label' => $t['sb_admin_dashboard'] ?? 'Admin Dashboard'],
            ['id' => 'admin_upload', 'href' => app_web_path('admin/upload-content.php'), 'icon' => 'fa-cloud-upload-alt', 'label' => $t['sb_admin_upload'] ?? 'Upload Content'],
        ];
        break;

    default:
        $nav_items = [
            ['id' => 'home', 'href' => app_web_path('index.php?lang=' . urlencode($lang)), 'icon' => 'fa-home', 'label' => $t['nav_home'] ?? 'Home'],
        ];
        break;
}
