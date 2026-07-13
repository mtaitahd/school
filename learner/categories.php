<?php
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/learner-session.php';
require_once __DIR__ . '/../php/includes/SubscriptionMiddleware.php';
require_once __DIR__ . '/../php/includes/settings.php';
require_once __DIR__ . '/../php/includes/migrate.php';
ensure_schema_v4_number_groups($database);

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

$need_payment = false;
if ($learner_logged_in) {
    $trialInfo = SubscriptionMiddleware::getLearnerTrialInfo((int) $_SESSION['user_id']);
    $need_payment = is_payment_enabled() && !$trialInfo['is_active'];
}

$modules = $database->fetchAll("SELECT * FROM modules WHERE is_active = 1 AND module_name NOT IN ('Counting','Shapes','Addition','Subtraction','Matching','Games') ORDER BY order_index ASC");

$category_labels = [
    'Counting' => ['en' => 'Counting & Number Recognition', 'sw' => 'Kuhesabu na Kutambua Namba'],
    'Shapes' => ['en' => 'Shapes & Patterns', 'sw' => 'Maumbo na Mifumo'],
    'Addition' => ['en' => 'Addition', 'sw' => 'Kuongeza'],
    'Subtraction' => ['en' => 'Subtraction', 'sw' => 'Kutoa'],
    'Matching' => ['en' => 'Matching Games', 'sw' => 'Michezo ya Kulinganisha'],
    'Games' => ['en' => 'Math Games', 'sw' => 'Michezo ya Hisabati'],
    'Recognising Numbers 1-9' => ['en' => 'Recognise, trace, and find numbers 1 to 9', 'sw' => 'Tambua, fuata, na upate namba 1 hadi 9'],
    'Counting Numbers 1-9' => ['en' => 'Count objects, match groups, and play counting games', 'sw' => 'Hesabu vitu, linganisha makundi, naucheza michezo ya kuhesabu'],
    'Recognising and Counting Numbers 1-9' => ['en' => 'Learn to recognise, count, and write numbers from 1 to 9', 'sw' => 'Jifunze kutambua, kuhesabu, na kuandika namba 1 hadi 9'],
    'Number Zero' => ['en' => 'Learn to recognise, trace, and find the number 0', 'sw' => 'Jifunze kutambua, kufuata, na kupata namba 0'],
    'Number Ten' => ['en' => 'Learn to recognise, read, write, and count to 10', 'sw' => 'Jifunze kutambua, kusoma, kuandika, na kuhesabu hadi 10'],
    'Numbers 11–20' => ['en' => 'Count objects and learn numbers 11 to 20', 'sw' => 'Hesabu vitu na jifunze namba 11 hadi 20'],
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

    <style>
        .modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;}
    </style>

    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php include '../php/includes/paths-script.php'; ?>
    <script src="../js/main.js"></script>

    <?php if ($need_payment): ?>
    <div class="modal-overlay" id="paymentModal" style="display:none;">
        <div class="modal-card" style="background:#fff;border-radius:16px;padding:40px;max-width:400px;margin:100px auto;text-align:center;position:relative;">
            <i class="fas fa-lock" style="font-size:4rem;color:#dc2626;margin-bottom:16px;"></i>
            <h3 style="margin-bottom:12px;"><?php echo $current_lang === 'sw' ? 'Malipo Yanahitajika' : 'Payment Required'; ?></h3>
            <p style="color:#666;margin-bottom:24px;"><?php echo $current_lang === 'sw' ? 'Tafadhali mwambie mzazi wako alipe ada yako ili uweze kuendelea na masomo.' : 'Please ask your parent to pay for your subscription to continue learning.'; ?></p>
            <button type="button" class="btn btn-danger btn-lg fw-bold px-5" style="border-radius:50px;" onclick="window.location.href='../payment'">
                <i class="fas fa-wallet me-2"></i> <?php echo $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now'; ?>
            </button>
            <button type="button" class="btn btn-link d-block mt-3" style="color:#999;" onclick="document.getElementById('paymentModal').style.display='none'"><?php echo $current_lang === 'sw' ? 'Funga' : 'Close'; ?></button>
        </div>
    </div>
    <script>
    window.selectModule = function() {
        document.getElementById('paymentModal').style.display = 'flex';
    };
    </script>
    <?php endif; ?>
</body>
</html>



