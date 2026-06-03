<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/includes/auth.php';

auth_require_role(['admin'], 'index');

$module_id = (int) ($_GET['module_id'] ?? 0);
if ($module_id > 0) {
    $module = $database->fetchOne("SELECT is_active FROM modules WHERE module_id = ?", [$module_id]);
    if ($module) {
        $new_status = $module['is_active'] ? 0 : 1;
        $database->execute("UPDATE modules SET is_active = ? WHERE module_id = ?", [$new_status, $module_id]);
    }
}
header('Location: dashboard');
exit;



