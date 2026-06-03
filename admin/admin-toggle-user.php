<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/includes/auth.php';

auth_require_role(['admin'], 'index');

$user_id = (int) ($_GET['user_id'] ?? 0);
if ($user_id > 0 && $user_id !== auth_user_id()) {
    $user = $database->fetchOne("SELECT is_active FROM users WHERE user_id = ?", [$user_id]);
    if ($user) {
        $new_status = $user['is_active'] ? 0 : 1;
        $database->execute("UPDATE users SET is_active = ? WHERE user_id = ?", [$new_status, $user_id]);
    }
}
header('Location: dashboard');
exit;



