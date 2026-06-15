<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();
header('Content-Type: application/json');

if (auth_role() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$parents = $database->fetchAll("
    SELECT user_id, username, first_name, last_name, phone, email
    FROM users
    WHERE role = 'parent' AND is_active = 1
        AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ? OR phone LIKE ?)
    ORDER BY first_name ASC
    LIMIT 20
", ["%$q%", "%$q%", "%$q%", "%$q%"]);

echo json_encode($parents);
