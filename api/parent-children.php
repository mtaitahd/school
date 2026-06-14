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

$parentId = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
if ($parentId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parent ID']);
    exit;
}

$children = $database->fetchAll("
    SELECT DISTINCT u.user_id, u.username, u.first_name, u.last_name, u.is_active
    FROM users u
    LEFT JOIN parent_student_links psl ON psl.student_id = u.user_id AND psl.is_active = 1
    WHERE u.role = 'learner' AND (u.parent_id = ? OR psl.parent_id = ?)
    ORDER BY u.first_name ASC
", [$parentId, $parentId]);

echo json_encode($children);
