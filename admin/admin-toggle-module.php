<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard');
    exit;
}

csrf_require();

$module_id = (int) ($_POST['module_id'] ?? 0);
if ($module_id > 0) {
    $module = $database->fetchOne("SELECT is_active FROM modules WHERE module_id = ?", [$module_id]);
    if ($module) {
        $new_status = $module['is_active'] ? 0 : 1;
        $database->execute("UPDATE modules SET is_active = ? WHERE module_id = ?", [$new_status, $module_id]);
    }
}
header('Location: dashboard');
exit;



