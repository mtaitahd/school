<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
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

$studentId = (int) $_SESSION['user_id'];
$studentAssignmentId = (int) ($_GET['student_assignment_id'] ?? 0);

if (!$studentAssignmentId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing student_assignment_id']);
    exit;
}

$sa = $database->fetchOne(
    "SELECT sa.*, a.title, a.due_date, a.duration_minutes
     FROM student_assignments sa
     JOIN assignments a ON sa.assignment_id = a.assignment_id
     WHERE sa.student_assignment_id = ? AND sa.student_id = ?",
    [$studentAssignmentId, $studentId]
);

if (!$sa) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Not found']);
    exit;
}

// Get current answer count
$answeredCount = $database->fetchOne(
    "SELECT COUNT(*) AS cnt FROM assignment_answers
     WHERE student_assignment_id = ? AND student_id = ? AND given_answer IS NOT NULL AND given_answer != ''",
    [$studentAssignmentId, $studentId]
);

$totalQ = $database->fetchOne(
    "SELECT COUNT(*) AS cnt FROM assignment_questions WHERE assignment_id = ?",
    [$sa['assignment_id']]
);

echo json_encode([
    'ok' => true,
    'status' => $sa['status'],
    'answered' => (int) ($answeredCount['cnt'] ?? 0),
    'total' => (int) ($totalQ['cnt'] ?? 0),
    'progress_percentage' => (float) ($sa['progress_percentage'] ?? 0),
    'score' => $sa['score'],
    'started_at' => $sa['started_at'],
    'completed_at' => $sa['completed_at'],
    'due_date' => $sa['due_date'],
    'duration_minutes' => $sa['duration_minutes'],
    'submission_type' => $sa['submission_type'],
]);
