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
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['_csrf_token'] ?? '';
if (!csrf_verify($token)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'CSRF validation failed']);
    exit;
}

$studentId = (int) $_SESSION['user_id'];
$studentAssignmentId = (int) ($input['student_assignment_id'] ?? 0);
$submissionType = $input['submission_type'] ?? 'manual'; // manual | automatic | time_expired

if (!$studentAssignmentId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing student_assignment_id']);
    exit;
}

$sa = $database->fetchOne(
    "SELECT sa.*, a.activity_id
     FROM student_assignments sa
     JOIN assignments a ON sa.assignment_id = a.assignment_id
     WHERE sa.student_assignment_id = ? AND sa.student_id = ?",
    [$studentAssignmentId, $studentId]
);

if (!$sa) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Assignment not found']);
    exit;
}

if ($sa['status'] === 'completed' || $sa['status'] === 'auto_submitted') {
    echo json_encode(['ok' => true, 'message' => 'Already submitted', 'status' => $sa['status']]);
    exit;
}

// Mark unanswered questions as skipped
$database->execute(
    "INSERT IGNORE INTO assignment_answers (student_assignment_id, question_id, student_id, given_answer, is_correct, points_earned)
     SELECT ?, q.question_id, ?, '', 0, 0
     FROM assignment_questions q
     WHERE q.assignment_id = ?
       AND q.question_id NOT IN (
           SELECT aa.question_id FROM assignment_answers aa
           WHERE aa.student_assignment_id = ? AND aa.given_answer IS NOT NULL AND aa.given_answer != ''
       )",
    [$studentAssignmentId, $studentId, $sa['assignment_id'], $studentAssignmentId]
);

// Recalculate totals
$totals = $database->fetchOne(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN given_answer IS NOT NULL AND given_answer != '' THEN 1 ELSE 0 END) AS answered
     FROM assignment_answers
     WHERE student_assignment_id = ? AND student_id = ?",
    [$studentAssignmentId, $studentId]
);

$totalQ = (int) ($totals['total'] ?? 0);
$answered = (int) ($totals['answered'] ?? 0);
$skipped = $totalQ - $answered;
$progressPct = $totalQ > 0 ? round(($answered / $totalQ) * 100, 2) : 0;

$status = $submissionType === 'time_expired' ? 'expired' : 'completed';

$database->execute(
    "UPDATE student_assignments
     SET status = ?, total_questions = ?, answered_questions = ?, skipped_questions = ?,
         completed_questions = ?, progress_percentage = ?, submission_type = ?,
         completed_at = NOW()
     WHERE student_assignment_id = ?",
    [$status, $totalQ, $answered, $skipped, $answered, $progressPct, $submissionType, $studentAssignmentId]
);

// Update progress on linked activity
if ($sa['activity_id']) {
    $existing = $database->fetchOne(
        "SELECT progress_id FROM progress WHERE user_id = ? AND activity_id = ?",
        [$studentId, $sa['activity_id']]
    );
    if ($existing) {
        $database->execute(
            "UPDATE progress SET completed = 1, last_attempt_at = NOW() WHERE progress_id = ?",
            [$existing['progress_id']]
        );
    }
}

echo json_encode([
    'ok' => true,
    'status' => $status,
    'submission_type' => $submissionType,
    'answered' => $answered,
    'total' => $totalQ,
    'skipped' => $skipped,
    'progress_percentage' => $progressPct,
]);
