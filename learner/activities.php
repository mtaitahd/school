<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/learner-session.php';

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
$use_learner_dashboard = $learner_logged_in;
if ($use_learner_dashboard) {
    require_once __DIR__ . '/../php/includes/auth.php';
    $dashboard_role = 'learner';
    $sidebar_active = 'learn';
}

if ($module_id === 0) {
    header('Location: categories.php?lang=' . $current_lang);
    exit;
}

$module = $database->fetchOne("SELECT * FROM modules WHERE module_id = ? AND is_active = 1", [$module_id]);
if (!$module) {
    header('Location: categories.php?lang=' . urlencode($current_lang) . '&error=module');
    exit;
}

$activities = $database->fetchAll(
    "SELECT * FROM activities WHERE module_id = ? AND is_active = 1 ORDER BY order_index ASC",
    [$module_id]
);
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
<body class="<?php echo $use_learner_dashboard ? 'dashboard-body' : 'page-child'; ?>">
<?php if ($use_learner_dashboard): ?>
    <?php include '../php/includes/dashboard-start.php'; ?>
<?php else: ?>
    <?php include '../php/includes/header.php'; ?>
    <main class="container-child mt-30 page-enter">
        <?php
        $back_url = 'categories.php?lang=' . $current_lang;
        $home_url = '../index.php';
        include '../php/includes/activity-topbar.php';
        ?>
<?php endif; ?>
        <?php if ($use_learner_dashboard): ?>
        <p class="mb-20">
            <a href="categories.php?lang=<?php echo urlencode($current_lang); ?>" class="btn-child btn-child-yellow">
                <i class="fas fa-arrow-left me-2"></i><?php echo $t['activity_back'] ?? 'Back'; ?>
            </a>
        </p>
        <?php endif; ?>

        <div class="section-heading">
            <div class="module-card-icon-wrap" style="display:inline-flex; border: 4px solid <?php echo htmlspecialchars($module['module_color']); ?>; margin-bottom: 16px;">
                <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?> module-card-icon" style="color: <?php echo htmlspecialchars($module['module_color']); ?>;" aria-hidden="true"></i>
            </div>
            <h1><?php echo htmlspecialchars($module['module_name']); ?></h1>
            <p><?php echo htmlspecialchars($module['module_description']); ?></p>
        </div>

        <div class="row-child">
            <?php if (empty($activities)): ?>
                <div class="col-child-1 text-center">
                    <p class="activity-instruction"><?php echo $current_lang === 'sw' ? 'Hakuna shughuli bado.' : 'No activities available yet for this module.'; ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($activities as $activity): ?>
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
            <?php endif; ?>
        </div>
<?php if ($use_learner_dashboard): ?>
    <?php include '../php/includes/dashboard-end.php'; ?>
<?php else: ?>
    </main>
    <?php include '../php/includes/footer.php'; ?>
<?php endif; ?>

    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../php/includes/paths-script.php'; ?>
    <script src="../js/main.js"></script>
    <?php if ($use_learner_dashboard): ?><script src="../js/dashboard.js"></script><?php endif; ?>
</body>
</html>



