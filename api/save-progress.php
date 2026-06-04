<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/migrate.php';

header('Content-Type: application/json');
sec_send_headers();

ensure_schema_v2($database);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'learner') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in as learner']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// For JSON requests, verify CSRF via X-CSRF-Token header
if (empty($input['_csrf_token']) && empty($_POST['_csrf_token'])) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_verify($token)) {
        http_response_code(419);
        echo json_encode(['ok' => false, 'error' => 'CSRF token validation failed.']);
        exit;
    }
} elseif (!csrf_verify($input['_csrf_token'] ?? $_POST['_csrf_token'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'CSRF token validation failed.']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$activity_id = (int) ($input['activity_id'] ?? 0);
$score = min(100, max(0, (int) ($input['score'] ?? 0)));
$completed = !empty($input['completed']) ? 1 : 0;
$stars = min(3, max(0, (int) ($input['stars'] ?? 0)));

if ($activity_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid activity']);
    exit;
}

$existing = $database->fetchOne(
    "SELECT progress_id, attempts FROM progress WHERE user_id = ? AND activity_id = ?",
    [$user_id, $activity_id]
);

if ($existing) {
    $database->execute(
        "UPDATE progress SET score = GREATEST(score, ?), attempts = attempts + 1, completed = GREATEST(completed, ?),
         stars_earned = GREATEST(stars_earned, ?), completed_at = IF(? = 1 AND completed_at IS NULL, NOW(), completed_at),
         last_attempt_at = NOW() WHERE progress_id = ?",
        [$score, $completed, $stars, $completed, $existing['progress_id']]
    );
} else {
    $database->insert(
        "INSERT INTO progress (user_id, activity_id, score, attempts, completed, stars_earned, completed_at)
         VALUES (?, ?, ?, 1, ?, ?, IF(? = 1, NOW(), NULL))",
        [$user_id, $activity_id, $score, $completed, $stars, $completed]
    );
}

if ($completed) {
    $database->execute(
        "UPDATE activity_assignments SET status = 'completed', completed_at = NOW()
         WHERE learner_id = ? AND activity_id = ? AND status != 'completed'",
        [$user_id, $activity_id]
    );

    $database->execute(
        "UPDATE student_assignments sa
         JOIN assignments a ON sa.assignment_id = a.assignment_id
         SET sa.status = 'completed', sa.score = GREATEST(COALESCE(sa.score, 0), ?), sa.submitted_at = NOW()
         WHERE sa.student_id = ? AND a.activity_id = ? AND sa.status != 'completed'",
        [$score, $user_id, $activity_id]
    );
} else {
    $database->execute(
        "UPDATE student_assignments sa
         JOIN assignments a ON sa.assignment_id = a.assignment_id
         SET sa.status = 'in_progress'
         WHERE sa.student_id = ? AND a.activity_id = ? AND sa.status = 'pending'",
        [$user_id, $activity_id]
    );
}

echo json_encode(['ok' => true, 'score' => $score, 'stars' => $stars, 'completed' => (bool) $completed]);
