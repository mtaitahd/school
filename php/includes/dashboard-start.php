<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/migrate.php';

if (empty($dashboard_role)) {
    $dashboard_role = auth_role();
}

$allowed = ['teacher', 'parent', 'learner', 'admin'];
if (!in_array($dashboard_role, $allowed, true)) {
    header('Location: ' . ($base_path ?? '') . 'index');
    exit;
}

if (auth_role() !== $dashboard_role) {
    $login_map = [
        'teacher' => ($base_path ?? '') . 'teacher/login',
        'parent' => ($base_path ?? '') . 'parent/login',
        'learner' => ($base_path ?? '') . 'learner/login',
        'admin' => ($base_path ?? '') . 'admin/index',
    ];
    header('Location: ' . ($login_map[$dashboard_role] ?? 'index'));
    exit;
}

if (isset($database)) {
    ensure_schema_v2($database);
}

$layout = 'dashboard';
$base = $base_path ?? '';
$asset_base = $base . 'assets/';
?><div id="wrapper">
<link rel="stylesheet" href="<?php echo $asset_base; ?>css/ruang-admin.min.css">
<link rel="stylesheet" href="<?php echo $base; ?>css/style.css">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include __DIR__ . '/dashboard-topbar.php'; ?>
            <div class="container-fluid" id="container-wrapper">
