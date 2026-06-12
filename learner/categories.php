<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/learner-session.php';

if (!empty($_SERVER['REQUEST_URI']) && preg_match('#/learner/learner/#', $_SERVER['REQUEST_URI'])) {
    $fixed = preg_replace('#/learner/learner/#', '/learner/', $_SERVER['REQUEST_URI']);
    header('Location: ' . $fixed, true, 301);
    exit;
}

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '../';
$active_nav = 'learning';
$lang_page = 'categories.php';
$use_learner_dashboard = false; // no sidebar nav in learning flow

$modules = $database->fetchAll("SELECT * FROM modules WHERE is_active = 1 ORDER BY order_index ASC");

$category_labels = [
    'Counting' => ['en' => 'Counting & Number Recognition', 'sw' => 'Kuhesabu na Kutambua Namba'],
    'Shapes' => ['en' => 'Shapes & Patterns', 'sw' => 'Maumbo na Mifumo'],
    'Addition' => ['en' => 'Addition', 'sw' => 'Kuongeza'],
    'Subtraction' => ['en' => 'Subtraction', 'sw' => 'Kutoa'],
    'Matching' => ['en' => 'Matching Games', 'sw' => 'Michezo ya Kulinganisha'],
    'Games' => ['en' => 'Math Games', 'sw' => 'Michezo ya Hisabati'],
];
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Categories - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-child">
    <div class="activity-topbar">
        <a href="../index.php?lang=<?php echo urlencode($current_lang); ?>" class="topbar-btn topbar-home">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
    </div>
    <main class="container-child mt-30 page-enter">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'module'): ?>
            <div class="alert-child alert-child-error mb-20">
                <?php echo $current_lang === 'sw'
                    ? 'Moduli haikupatikana. Chagua moduli nyingine.'
                    : 'That module was not found. Please choose another module.'; ?>
            </div>
        <?php endif; ?>
        <div class="section-heading">
            <h1><?php echo htmlspecialchars($t['categories_title']); ?></h1>
            <p><?php echo htmlspecialchars($t['categories_sub']); ?></p>
            <button type="button" class="audio-btn mt-20" onclick="playAudio('<?php echo htmlspecialchars($t['categories_sub'], ENT_QUOTES); ?>')" aria-label="Listen to instructions">
                <i class="fas fa-volume-up" aria-hidden="true"></i>
            </button>
        </div>

        <div class="row-child categories-grid">
            <?php foreach ($modules as $module):
                $name = $module['module_name'];
                $subtitle = $category_labels[$name][$current_lang === 'sw' ? 'sw' : 'en'] ?? $module['module_description'];
            ?>
            <div class="col-child-3">
                <article class="module-card"
                     tabindex="0"
                     role="button"
                     data-module-id="<?php echo (int)$module['module_id']; ?>"
                     data-audio-prompt="<?php echo htmlspecialchars($module['audio_prompt']); ?>"
                     style="border-color: <?php echo htmlspecialchars($module['module_color']); ?>;"
                     onclick="selectModule(<?php echo (int)$module['module_id']; ?>)"
                     onkeydown="if(event.key==='Enter')selectModule(<?php echo (int)$module['module_id']; ?>)">
                    <div class="module-card-icon-wrap" style="border: 4px solid <?php echo htmlspecialchars($module['module_color']); ?>;">
                        <i class="fas <?php echo htmlspecialchars($module['module_icon']); ?> module-card-icon" style="color: <?php echo htmlspecialchars($module['module_color']); ?>;" aria-hidden="true"></i>
                    </div>
                    <h3 class="module-card-title"><?php echo htmlspecialchars($name); ?></h3>
                    <p class="module-card-subtitle"><?php echo htmlspecialchars($subtitle); ?></p>
                    <button type="button" class="audio-btn" onclick="event.stopPropagation(); playAudio('<?php echo htmlspecialchars($module['audio_prompt'], ENT_QUOTES); ?>')" aria-label="Listen">
                        <i class="fas fa-volume-up" aria-hidden="true"></i>
                    </button>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <div class="a11y-toolbar">
        <button type="button" class="a11y-btn" id="toggleContrast" title="High contrast"><i class="fas fa-adjust"></i></button>
        <button type="button" class="a11y-btn" id="toggleDyslexia" title="Dyslexia mode"><i class="fas fa-font"></i></button>
    </div>

    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../php/includes/paths-script.php'; ?>
    <script src="../js/main.js"></script>
</body>
</html>



