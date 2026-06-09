<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/subscription.php';

sec_require_rate_limit();
header('Content-Type: application/json');

if (auth_role() !== 'teacher') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if ($studentId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid student ID']);
    exit;
}

$parent = $database->fetchOne("
    SELECT u.parent_id FROM users u WHERE u.user_id = ? AND u.role = 'learner'
", [$studentId]);

if (!$parent || !$parent['parent_id']) {
    $link = $database->fetchOne("
        SELECT parent_id FROM parent_student_links WHERE student_id = ? LIMIT 1
    ", [$studentId]);
    $parentId = $link ? (int)$link['parent_id'] : 0;
} else {
    $parentId = (int)$parent['parent_id'];
}

if (!$parentId) {
    echo json_encode([
        'active' => false,
        'status' => 'no_parent',
        'message' => 'This student has not been linked to a parent account.'
    ]);
    exit;
}

$status = sub_get_status($parentId);

echo json_encode([
    'active' => !empty($status['is_active']),
    'status' => $status['status'] ?? 'unknown',
    'days_remaining' => $status['days_remaining'] ?? 0,
    'message' => $status['is_active']
        ? ''
        : 'Parent subscription has expired. Please ask the parent to renew.',
]);
