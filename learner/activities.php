<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/learner-session.php';
require_once __DIR__ . '/../php/includes/migrate.php';

// Fix mistaken double /learner/learner/ URLs (404 Not Found)
if (!empty($_SERVER['REQUEST_URI']) && preg_match('#/learner/learner/#', $_SERVER['REQUEST_URI'])) {
    $fixed = preg_replace('#/learner/learner/#', '/learner/', $_SERVER['REQUEST_URI']);
    header('Location: ' . $fixed, true, 301);
    exit;
}

$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '../';
$active_nav = 'learning';
$lang_page = 'activities.php' . ($module_id ? '?module_id=' . $module_id : '');
$use_learner_dashboard = false; // no sidebar nav in learning flow

if ($module_id === 0) {
    header('Location: categories?lang=' . $current_lang);
    exit;
}

ensure_schema_v4_number_groups($database);

$module = $database->fetchOne("SELECT * FROM modules WHERE module_id = ? AND is_active = 1", [$module_id]);
if (!$module) {
    header('Location: categories?lang=' . urlencode($current_lang) . '&error=module');
    exit;
}

// Check if this module is a sub-category container (has child modules but no own lessons)
$sub_modules = [];
$lessons = $database->fetchAll(
    "SELECT l.* FROM lessons l
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.module_id = ? AND l.is_active = 1
     ORDER BY l.order_index ASC",
    [$module_id]
);

if (empty($lessons)) {
    // No lessons — check if this module has child modules (sub-categories)
    // A module is a sub-category container if its topics have child modules
    // or if it has topics with no lessons but other modules share similar topics
    $has_own_activities = $database->fetchOne(
        "SELECT COUNT(*) as cnt FROM activities WHERE module_id = ? AND is_active = 1",
        [$module_id]
    );
    if ((int)($has_own_activities['cnt'] ?? 0) === 0) {
        // No activities either — this is likely a container module
        // Find child modules: modules that have topics linked to this module's strand
        $parentTopics = $database->fetchAll(
            "SELECT t.strand_id FROM topics t WHERE t.module_id = ?",
            [$module_id]
        );
        if (!empty($parentTopics)) {
            $strandId = (int)$parentTopics[0]['strand_id'];
            $sub_modules = $database->fetchAll(
                "SELECT DISTINCT m.module_id, m.module_name, m.module_description, m.module_icon, m.module_color, m.audio_prompt,
                        COUNT(DISTINCT l.lesson_id) as lesson_count,
                        COUNT(DISTINCT a.activity_id) as activity_count
                 FROM modules m
                 JOIN topics t ON t.module_id = m.module_id
                 LEFT JOIN lessons l ON l.topic_id = t.topic_id AND l.is_active = 1
                 LEFT JOIN activities a ON a.lesson_id = l.lesson_id AND a.is_active = 1
                 WHERE t.strand_id = ? AND m.module_id != ? AND m.is_active = 1
                 GROUP BY m.module_id
                 ORDER BY m.order_index ASC",
                [$strandId, $module_id]
            );
        }
    }
}

$activities_by_lesson = [];
$activities_flat = [];

if (!empty($lessons)) {
    // New hierarchy: group activities by lesson
    foreach ($lessons as $lesson) {
        $lesson_activities = $database->fetchAll(
            "SELECT * FROM activities WHERE lesson_id = ? AND is_active = 1 ORDER BY order_index ASC",
            [$lesson['lesson_id']]
        );
        $activities_by_lesson[$lesson['lesson_id']] = [
            'lesson' => $lesson,
            'activities' => $lesson_activities,
        ];
    }
} elseif (empty($sub_modules)) {
    // Legacy: fetch activities directly under module
    $activities_flat = $database->fetchAll(
        "SELECT * FROM activities WHERE module_id = ? AND is_active = 1 ORDER BY order_index ASC",
        [$module_id]
    );
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/activities.css">
</head>
<body class="page-child">
    <main class="container-child mt-30 page-enter">
        <?php
        $back_url = 'categories.php?lang=' . $current_lang;
        $home_url = '../index.php';
        include '../php/includes/activity-topbar.php';
        ?>

        <div class="section-heading">
            <div class="module-card-icon-wrap" style="display:inline-flex; border: 4px solid <?php echo htmlspecialchars($module['module_color']); ?>; margin-bottom: 16px;">
                <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?> module-card-icon" style="color: <?php echo htmlspecialchars($module['module_color']); ?>;" aria-hidden="true"></i>
            </div>
            <h1><?php echo htmlspecialchars($module['module_name']); ?></h1>
            <p style="font-size: 1.1rem; color: var(--text-light); max-width: 600px; margin: 0 auto;"><?php echo htmlspecialchars($module['module_description']); ?></p>
        </div>

        <div class="row-child">
            <?php if (!empty($sub_modules)): ?>
                <?php foreach ($sub_modules as $sub): ?>
                <div class="col-child-2">
                    <article class="module-card" tabindex="0" role="button"
                         onclick="selectSubModule(<?php echo (int)$sub['module_id']; ?>)"
                         onkeydown="if(event.key==='Enter')selectSubModule(<?php echo (int)$sub['module_id']; ?>)"
                         style="border-color: <?php echo htmlspecialchars($sub['module_color']); ?>;">
                        <div class="module-card-icon-wrap" style="border: 4px solid <?php echo htmlspecialchars($sub['module_color']); ?>;">
                            <i class="fas <?php echo htmlspecialchars($sub['module_icon']); ?> module-card-icon" style="color: <?php echo htmlspecialchars($sub['module_color']); ?>;" aria-hidden="true"></i>
                        </div>
                        <h3 class="module-card-title"><?php echo htmlspecialchars($sub['module_name']); ?></h3>
                        <p class="module-card-subtitle"><?php echo htmlspecialchars($sub['module_description']); ?></p>
                        <p style="font-size: 0.8rem; color: var(--text-light); margin-top: 8px;">
                            <i class="fas fa-book" aria-hidden="true"></i> <?php echo (int)$sub['lesson_count']; ?> lessons
                            &middot;
                            <i class="fas fa-play-circle" aria-hidden="true"></i> <?php echo (int)$sub['activity_count']; ?> activities
                        </p>
                        <button type="button" class="audio-btn" onclick="event.stopPropagation(); playAudio('<?php echo htmlspecialchars($sub['audio_prompt'], ENT_QUOTES); ?>')" aria-label="Listen">
                            <i class="fas fa-volume-up" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <?php endforeach; ?>
            <?php elseif (!empty($activities_by_lesson)): ?>
                <?php foreach ($activities_by_lesson as $entry): ?>
                    <?php $lesson = $entry['lesson']; $lesson_activities = $entry['activities'];
                    $is_number_lesson = preg_match('/NUM-N(\d)/', $lesson['lesson_code'] ?? '', $nm);
                    $number_val = $is_number_lesson ? (int)$nm[1] : 0;
                    ?>
                    <div class="col-child-1">
                        <div class="lesson-section" style="margin-bottom: 32px;">
                            <?php if ($is_number_lesson): ?>
                            <div class="lesson-header" style="display:flex; align-items:center; gap:16px; padding:16px 20px; margin-bottom:16px; background:linear-gradient(135deg, <?php echo htmlspecialchars($module['module_color']); ?>15, <?php echo htmlspecialchars($module['module_color']); ?>08); border-radius:16px; border:2px solid <?php echo htmlspecialchars($module['module_color']); ?>30;">
                                <div style="width:64px; height:64px; border-radius:50%; background:<?php echo htmlspecialchars($module['module_color']); ?>; color:#fff; display:flex; align-items:center; justify-content:center; font-size:2rem; font-weight:900; flex-shrink:0;">
                                    <?php echo $number_val; ?>
                                </div>
                                <div style="flex:1;">
                                    <h3 style="font-size:1.3rem; font-weight:700; color:var(--text-dark); margin-bottom:4px;">
                                        <?php echo htmlspecialchars($lesson['lesson_name']); ?>
                                    </h3>
                                    <p style="font-size:0.85rem; color:var(--text-light); margin-bottom:0;">
                                        <i class="fas fa-list" aria-hidden="true"></i> <?php echo count($lesson_activities); ?> activities
                                        &middot;
                                        <i class="fas fa-clock" aria-hidden="true"></i> <?php echo (int)$lesson['estimated_minutes']; ?> min
                                    </p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="lesson-header" style="border-left: 5px solid <?php echo htmlspecialchars($module['module_color']); ?>; padding: 12px 16px; margin-bottom: 16px; background: rgba(255,255,255,0.7); border-radius: 0 12px 12px 0;">
                                <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                                    <?php echo htmlspecialchars($lesson['lesson_name']); ?>
                                </h3>
                                <p style="font-size: 0.9rem; color: var(--text-light); margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($lesson['learning_objective']); ?>
                                </p>
                                <p style="font-size: 0.8rem; color: var(--text-light); margin-bottom: 0;">
                                    <i class="fas fa-clock" aria-hidden="true"></i> <?php echo (int)$lesson['estimated_minutes']; ?> min
                                    &middot;
                                    <i class="fas fa-list" aria-hidden="true"></i> <?php echo count($lesson_activities); ?> activities
                                </p>
                            </div>
                            <?php endif; ?>
                            <div class="row-child" style="margin-top: 8px;">
                                <?php foreach ($lesson_activities as $idx => $activity): ?>
                                <div class="col-child-2">
                                    <article class="module-card activity-card-full" tabindex="0" role="button"
                                         onclick="selectActivity(<?php echo (int)$activity['activity_id']; ?>)"
                                         onkeydown="if(event.key==='Enter')selectActivity(<?php echo (int)$activity['activity_id']; ?>)">
                                        <div style="font-size:0.85rem; font-weight:700; color: var(--text-light); margin-bottom:4px;">
                                            <?php echo $idx + 1; ?> / <?php echo count($lesson_activities); ?>
                                        </div>
                                        <div class="activity-card-icon" style="color: <?php echo htmlspecialchars($module['module_color']); ?>;">
                                            <i class="fas fa-play-circle" aria-hidden="true"></i>
                                        </div>
                                        <h3 class="activity-card-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                                        <p class="activity-card-description"><?php echo htmlspecialchars($activity['activity_description']); ?></p>
                                        <button type="button" class="audio-btn" onclick="event.stopPropagation(); playAudio('<?php echo htmlspecialchars($activity['audio_instruction'], ENT_QUOTES); ?>')" aria-label="Listen to instruction">
                                            <i class="fas fa-volume-up" aria-hidden="true"></i>
                                        </button>
                                    </article>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php elseif (!empty($activities_flat)): ?>
                <?php foreach ($activities_flat as $activity): ?>
                <div class="col-child-3">
                    <article class="module-card" tabindex="0" role="button"
                         onclick="selectActivity(<?php echo (int)$activity['activity_id']; ?>)"
                         onkeydown="if(event.key==='Enter')selectActivity(<?php echo (int)$activity['activity_id']; ?>)">
                        <div class="activity-card-icon" style="color: <?php echo htmlspecialchars($module['module_color']); ?>;">
                            <i class="fas fa-play-circle" aria-hidden="true"></i>
                        </div>
                        <h3 class="activity-card-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h3>
                        <p class="activity-card-description"><?php echo htmlspecialchars($activity['activity_description']); ?></p>
                        <button type="button" class="audio-btn" onclick="event.stopPropagation(); playAudio('<?php echo htmlspecialchars($activity['audio_instruction'], ENT_QUOTES); ?>')">
                            <i class="fas fa-volume-up" aria-hidden="true"></i>
                        </button>
                    </article>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-child-1 text-center">
                    <p class="activity-instruction"><?php echo $current_lang === 'sw' ? 'Hakuna shughuli bado.' : 'No activities available yet for this module.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../php/includes/paths-script.php'; ?>
    <script src="../js/main.js"></script>
    <script>
    function selectSubModule(moduleId) {
        window.location.href = 'activities.php?module_id=' + moduleId + '&lang=<?php echo urlencode($current_lang); ?>';
    }
    </script>
</body>
</html>



