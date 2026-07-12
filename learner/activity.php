<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/learner-session.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/SubscriptionMiddleware.php';

// Subscription enforcement for logged-in learners
if ($learner_logged_in) {
    SubscriptionMiddleware::requireAccess();
}

if (!empty($_SERVER['REQUEST_URI']) && preg_match('#/learner/learner/#', $_SERVER['REQUEST_URI'])) {
    $fixed = preg_replace('#/learner/learner/#', '/learner/', $_SERVER['REQUEST_URI']);
    header('Location: ' . $fixed, true, 301);
    exit;
}

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '../';
$active_nav = 'learning';

$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$lang_page = 'activity.php?activity_id=' . $activity_id;

if ($activity_id === 0) {
    header('Location: categories?lang=' . $current_lang);
    exit;
}

$activity = $database->fetchOne("
    SELECT a.*, m.module_name, m.module_color, m.module_icon 
    FROM activities a 
    JOIN modules m ON a.module_id = m.module_id 
    WHERE a.activity_id = ? AND a.is_active = 1
", [$activity_id]);

if (!$activity) {
    header('Location: categories?lang=' . $current_lang);
    exit;
}

$activity_data = json_decode($activity['activity_data'], true) ?: [];
$back_url = 'activities.php?module_id=' . (int)$activity['module_id'] . '&lang=' . $current_lang;
$home_url = '../index.php';

$engine = $activity_data['engine'] ?? $activity['activity_type'];
$isInteractiveEngine = in_array($engine, [
    'mango_counting', 'pattern_counting', 'number_identification', 'number_sequencing', 'number_tracing',
    'missing_numbers', 'match_quantity', 'dot_to_dot', 'identify_shapes',
    'shape_sorting', 'complete_pattern', 'drag_addition', 'visual_subtraction', 'number_line',
    'object_recognition', 'objects', 'sorting', 'math_game'
], true);

/* Phase 6: Lesson progress info */
$lesson_name = '';
$lesson_id = (int)($activity['lesson_id'] ?? 0);
$activity_position = 0;
$total_in_lesson = 0;
if ($lesson_id > 0) {
    $lesson_row = $database->fetchOne("SELECT lesson_name FROM lessons WHERE lesson_id = ?", [$lesson_id]);
    $lesson_name = $lesson_row['lesson_name'] ?? '';
    $total_in_lesson = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE lesson_id = ? AND is_active = 1", [$lesson_id])['cnt'];
    $activity_position = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE lesson_id = ? AND is_active = 1 AND order_index <= ?", [$lesson_id, $activity['order_index'] ?? 0])['cnt'];
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/activities.css">
</head>
<body class="page-child">
    <?php if (!$learner_logged_in): include '../php/includes/header.php'; endif; ?>

    <main class="container-child mt-30 page-enter">
        <div class="activity-container">
            <?php include '../php/includes/activity-topbar.php'; ?>

            <div class="activity-header">
                <h1 class="activity-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h1>
                <p class="activity-instruction"><?php echo htmlspecialchars($activity['activity_description']); ?></p>
            </div>

            <?php if ($lesson_name && $total_in_lesson > 0): ?>
            <div class="activity-progress-inline" role="status" aria-label="Activity progress">
                <div class="activity-progress-label">
                    <?php echo $current_lang === 'sw' ? 'Somoo' : 'Lesson'; ?>:
                    <strong><?php echo htmlspecialchars($lesson_name); ?></strong>
                    &mdash;
                    <?php echo $activity_position; ?> / <?php echo $total_in_lesson; ?>
                </div>
                <div class="activity-progress-track">
                    <div class="activity-progress-fill" style="width: <?php echo round(($activity_position / $total_in_lesson) * 100); ?>%"></div>
                </div>
            </div>
            <div class="activity-step-dots" aria-hidden="true">
                <?php for ($i = 1; $i <= $total_in_lesson; $i++): ?>
                <span class="activity-step-dot <?php echo $i < $activity_position ? 'done' : ($i === $activity_position ? 'active' : ''); ?>"></span>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php if (!$isInteractiveEngine): ?>
            <div class="progress-bar-child">
                <div class="progress-fill" style="width: 0%;" id="progressBar">0%</div>
            </div>
            <?php endif; ?>

            <div class="activity-display" id="activityDisplay"></div>

            <div class="answer-options" id="answerOptions"></div>

            <div class="text-center mt-30" id="scoreSection" <?php echo $isInteractiveEngine ? 'style="display:none"' : ''; ?>>
                <h3 style="font-size: 1.5rem; color: var(--primary-blue);">
                    <?php echo $current_lang === 'sw' ? 'Alama' : 'Score'; ?>:
                    <span id="scoreDisplay">0</span> / <span id="totalQuestions">5</span>
                </h3>
            </div>

            <div class="activity-footer-bar" id="nextActivityBar" style="display: none;">
                <button type="button" class="btn-child btn-child-green btn-child-large btn-bounce" id="nextActivityBtn">
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($t['activity_next']); ?>
                </button>
            </div>
        </div>
    </main>




    <div class="a11y-toolbar">
        <button type="button" class="a11y-btn" id="toggleContrast" title="High contrast"><i class="fas fa-adjust"></i></button>
        <button type="button" class="a11y-btn" id="toggleDyslexia" title="Dyslexia mode"><i class="fas fa-font"></i></button>
    </div>

    <audio id="audioPlayer" preload="auto"></audio>

    <script>
        window.ACTIVITY_CONFIG = {
            activityData: <?php echo json_encode($activity_data); ?>,
            activityType: <?php echo json_encode($activity['activity_type']); ?>,
            engine: <?php echo json_encode($engine); ?>,
            audioInstruction: <?php echo json_encode($activity['audio_instruction']); ?>,
            moduleId: <?php echo (int)$activity['module_id']; ?>,
            activityId: <?php echo (int)$activity_id; ?>,
            saveProgressUrl: '../api/save-progress.php',
            lang: <?php echo json_encode($current_lang); ?>,
            totalQuestions: 5,
            lessonName: <?php echo json_encode($lesson_name); ?>,
            activityPosition: <?php echo $activity_position; ?>,
            totalInLesson: <?php echo $total_in_lesson; ?>
        };
        function goBack() {
            window.location.href = 'activities?module_id=' + ACTIVITY_CONFIG.moduleId + '&lang=' + ACTIVITY_CONFIG.lang;
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/activities/core.js?v=20260712e"></script>
    <script src="../js/activities/engines.js?v=20260712e"></script>
    <script src="../js/activities/registry.js?v=20260712e"></script>
    <script src="../js/activity-runner.js?v=20260712e"></script>
</body>
</html>



