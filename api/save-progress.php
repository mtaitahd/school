<?php
session_start();
header('Content-Type: application/json');
require_once '../php/db_connection.php';
require_once '../php/includes/migrate.php';

ensure_schema_v2($database);

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'learner') {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in as learner']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
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

    // Also mark any related student_assignments (assignment entries that reference this activity) as completed
    $database->execute(
        "UPDATE student_assignments sa
         JOIN assignments a ON sa.assignment_id = a.assignment_id
         SET sa.status = 'completed'
         WHERE sa.student_id = ? AND a.activity_id = ? AND sa.status != 'completed'",
        [$user_id, $activity_id]
    );
}

echo json_encode(['ok' => true, 'score' => $score, 'stars' => $stars, 'completed' => (bool) $completed]);



