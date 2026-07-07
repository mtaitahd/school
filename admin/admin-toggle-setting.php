<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/settings.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard');
    exit;
}

csrf_require();

$setting = $_POST['setting'] ?? '';
if ($setting === 'payment_enabled') {
    $current = is_payment_enabled();
    $newValue = $current ? '0' : '1';
    $existing = $database->fetchOne(
        "SELECT id FROM `settings` WHERE setting_key = 'payment_enabled'"
    );
    if ($existing) {
        $database->execute(
            "UPDATE `settings` SET setting_value = ? WHERE setting_key = 'payment_enabled'",
            [$newValue]
        );
    } else {
        $database->execute(
            "INSERT INTO `settings` (setting_key, setting_value) VALUES ('payment_enabled', ?)",
            [$newValue]
        );
    }
}

header('Location: settings');
exit;
