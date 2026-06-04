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
$questionId = (int) ($input['question_id'] ?? 0);
$givenAnswer = trim($input['answer'] ?? '');
$action = $input['action'] ?? 'answer'; // 'answer' | 'skip' | 'timeout'

if (!$studentAssignmentId || !$questionId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing parameters']);
    exit;
}

// Verify this assignment belongs to this student and is in progress
$sa = $database->fetchOne(
    "SELECT sa.*, a.activity_id, a.assignment_type
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

if ($sa['status'] === 'completed' || $sa['status'] === 'auto_submitted' || $sa['status'] === 'expired') {
    echo json_encode(['ok' => false, 'error' => 'Assignment already submitted', 'final' => true]);
    exit;
}

// Get question info
$question = $database->fetchOne(
    "SELECT * FROM assignment_questions WHERE question_id = ? AND assignment_id = ?",
    [$questionId, $sa['assignment_id']]
);

if (!$question) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Question not found']);
    exit;
}

// Determine correctness
$isCorrect = 0;
if (strtolower(trim($givenAnswer)) === strtolower(trim($question['correct_answer']))) {
    $isCorrect = 1;
} elseif ($question['question_type'] === 'multiple_choice' || $question['question_type'] === 'true_false') {
    $isCorrect = strcasecmp(trim($givenAnswer), trim($question['correct_answer'])) === 0 ? 1 : 0;
}

$pointsEarned = $isCorrect ? (int) $question['points'] : 0;

// Upsert answer
$existingAnswer = $database->fetchOne(
    "SELECT answer_id FROM assignment_answers
     WHERE student_assignment_id = ? AND question_id = ? AND student_id = ?",
    [$studentAssignmentId, $questionId, $studentId]
);

if ($existingAnswer) {
    $database->execute(
        "UPDATE assignment_answers
         SET given_answer = ?, is_correct = ?, points_earned = ?, answered_at = NOW()
         WHERE answer_id = ?",
        [$givenAnswer, $isCorrect, $pointsEarned, $existingAnswer['answer_id']]
    );
} else {
    $database->insert(
        "INSERT INTO assignment_answers (student_assignment_id, question_id, student_id, given_answer, is_correct, points_earned, answered_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())",
        [$studentAssignmentId, $questionId, $studentId, $givenAnswer, $isCorrect, $pointsEarned]
    );
}

// Recalculate totals
$totals = $database->fetchOne(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN given_answer IS NOT NULL AND given_answer != '' THEN 1 ELSE 0 END) AS answered,
        SUM(CASE WHEN given_answer IS NULL OR given_answer = '' THEN 1 ELSE 0 END) AS skipped
     FROM assignment_answers
     WHERE student_assignment_id = ? AND student_id = ?",
    [$studentAssignmentId, $studentId]
);

// Also count total questions from assignment_questions
$totalQuestions = $database->fetchOne(
    "SELECT COUNT(*) AS cnt FROM assignment_questions WHERE assignment_id = ?",
    [$sa['assignment_id']]
);
$totalQ = (int) ($totalQuestions['cnt'] ?? 0);

$answered = (int) ($totals['answered'] ?? 0);
$skipped = (int) ($totals['skipped'] ?? 0);
$progressPct = $totalQ > 0 ? round(($answered / $totalQ) * 100, 2) : 0;

// Update student_assignments tracking
$database->execute(
    "UPDATE student_assignments
     SET total_questions = ?, answered_questions = ?, skipped_questions = ?,
         completed_questions = ?, progress_percentage = ?,
         status = IF(? >= ?, 'completed', 'in_progress')
     WHERE student_assignment_id = ?",
    [$totalQ, $answered, $skipped, $answered, $progressPct, $answered, $totalQ, $studentAssignmentId]
);

// If just started, set started_at
$database->execute(
    "UPDATE student_assignments
     SET started_at = COALESCE(started_at, NOW())
     WHERE student_assignment_id = ? AND started_at IS NULL",
    [$studentAssignmentId]
);

// Auto-submit if all answered
$isComplete = $answered >= $totalQ;

if ($isComplete) {
    $database->execute(
        "UPDATE student_assignments
         SET status = 'completed', completed_at = NOW(), submission_type = 'automatic'
         WHERE student_assignment_id = ?",
        [$studentAssignmentId]
    );

    // If linked to an activity, also save progress
    if ($sa['activity_id']) {
        $scorePct = $totalQ > 0 ? round(($answered > 0 ? 1 : 0) * 100) : 0;
        $existingProgress = $database->fetchOne(
            "SELECT progress_id FROM progress WHERE user_id = ? AND activity_id = ?",
            [$studentId, $sa['activity_id']]
        );
        if ($existingProgress) {
            $database->execute(
                "UPDATE progress SET score = GREATEST(score, ?), attempts = attempts + 1, completed = 1, completed_at = NOW(), last_attempt_at = NOW() WHERE progress_id = ?",
                [$scorePct, $existingProgress['progress_id']]
            );
        } else {
            $database->insert(
                "INSERT INTO progress (user_id, activity_id, score, attempts, completed, stars_earned, completed_at) VALUES (?, ?, ?, 1, 1, 0, NOW())",
                [$studentId, $sa['activity_id'], $scorePct]
            );
        }
    }
}

// Return current state
echo json_encode([
    'ok' => true,
    'is_correct' => (bool) $isCorrect,
    'points_earned' => $pointsEarned,
    'answered' => $answered,
    'total' => $totalQ,
    'progress_percentage' => $progressPct,
    'completed' => $isComplete,
    'action' => $action,
]);
