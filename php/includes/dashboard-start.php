<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/migrate.php';

if (empty($dashboard_role)) {
    $dashboard_role = auth_role();
}

$allowed = ['teacher', 'parent', 'learner', 'admin'];
if (!in_array($dashboard_role, $allowed, true)) {
    header('Location: ' . ($base_path ?? '') . 'index.php');
    exit;
}

if (auth_role() !== $dashboard_role) {
    $login_map = [
        'teacher' => ($base_path ?? '') . 'teacher/login.php',
        'parent' => ($base_path ?? '') . 'parent/login.php',
        'learner' => ($base_path ?? '') . 'learner/login.php',
        'admin' => ($base_path ?? '') . 'admin/index.php',
    ];
    header('Location: ' . ($login_map[$dashboard_role] ?? 'index.php'));
    exit;
}

if (isset($database)) {
    ensure_schema_v2($database);
}

$layout = 'dashboard';
?><div class="dashboard-shell" id="dashboardShell"><?php include __DIR__ . '/sidebar.php'; ?><div class="dashboard-right"><?php include __DIR__ . '/dashboard-topbar.php'; ?><main class="dashboard-main">
