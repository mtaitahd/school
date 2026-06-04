<?php
require_once '__DIR__ . '/../php/includes/session.php';
require_once '__DIR__ . '/../php/includes/security.php';
require_once '__DIR__ . '/../php/includes/csrf.php';
require_once '__DIR__ . '/../php/db_connection.php';
require_once '__DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard');
    exit;
}

csrf_require();

$user_id = (int) ($_POST['user_id'] ?? 0);
if ($user_id > 0 && $user_id !== auth_user_id()) {
    $user = $database->fetchOne("SELECT is_active FROM users WHERE user_id = ?", [$user_id]);
    if ($user) {
        $new_status = $user['is_active'] ? 0 : 1;
        $database->execute("UPDATE users SET is_active = ? WHERE user_id = ?", [$new_status, $user_id]);
    }
}
header('Location: dashboard');
exit;



