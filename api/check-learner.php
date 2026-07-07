<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/settings.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
if (empty($username)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username is required']);
    exit;
}

// Look up learner
$learner = $database->fetchOne(
    "SELECT user_id, first_name, last_name, username, parent_id, subscription_status FROM users WHERE LOWER(username) = LOWER(?) AND role = 'learner' AND is_active = 1",
    [$username]
);

if (!$learner) {
    echo json_encode([
        'exists' => false,
        'message' => 'Jina la mtumiaji halipo. Muulize mwalimu au mzazi wako.'
    ]);
    exit;
}

// If start learning restriction is OFF, allow access without subscription check
if (!is_start_learning_restricted()) {
    echo json_encode([
        'exists' => true,
        'can_access' => true,
        'redirect' => 'learner/categories?lang=' . ($_SESSION['lang'] ?? 'en'),
        'message' => ''
    ]);
    exit;
}

// Find parent linked to this learner
$parentId = $learner['parent_id'];
if (!$parentId) {
    // Try parent_student_links
    $link = $database->fetchOne(
        "SELECT parent_id FROM parent_student_links WHERE student_id = ? AND is_active = 1 LIMIT 1",
        [(int) $learner['user_id']]
    );
    if ($link) {
        $parentId = (int) $link['parent_id'];
    }
}

if (!$parentId) {
    echo json_encode([
        'exists' => true,
        'can_access' => false,
        'message' => 'Tafadhali mwambie mzazi wako alipe ada ya mtoto wako ili uweze kuendelea na masomo.'
    ]);
    exit;
}

// Check parent subscription
require_once __DIR__ . '/../php/includes/subscription.php';
$subStatus = sub_get_status($parentId);

if ($subStatus['is_active']) {
    echo json_encode([
        'exists' => true,
        'can_access' => true,
        'redirect' => 'learner/categories?lang=' . ($_SESSION['lang'] ?? 'en'),
        'message' => ''
    ]);
} else {
    echo json_encode([
        'exists' => true,
        'can_access' => false,
        'message' => 'Tafadhali mwambie mzazi wako alipe ada ya mtoto wako ili uweze kuendelea na masomo.'
    ]);
}
