<?php
require_once __DIR__ . '/security.php';
sec_send_headers();

$base = $base_path ?? '';
$active = $active_nav ?? 'home';
$lang = $current_lang ?? 'en';
$lang_page = $lang_page ?? basename($_SERVER['SCRIPT_NAME']);
$logo_src = $base . 'assets/images/logo.png';

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}

if (($layout ?? '') === 'dashboard') {
    return;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/url_helpers.php';
require_once __DIR__ . '/nav-items.php';

$logged_role = auth_role();
$logged_name = auth_display_name();
$home_href = $nav_items[0]['href'] ?? app_web_path('index.php?lang=' . urlencode($lang));
$logout_href = ($logged_role && app_in_role_folder($logged_role)) ? '../logout.php' : app_web_path('logout.php');
$is_guest = ($logged_role === '');
?>
<nav class="navbar-modern" role="navigation" aria-label="Main navigation">
    <div class="container-modern">
        <div class="navbar-content">
            <a href="<?php echo htmlspecialchars($home_href); ?>" class="navbar-brand-modern" aria-label="Kona Ya Hisabati home">
                <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Kona Ya Hisabati" class="navbar-logo" width="48" height="48">
                <div class="navbar-brand-text">
                    <span class="brand-main">Kona Ya Hisabati</span>
                    <span class="brand-subtitle"><?php echo htmlspecialchars($t['brand_sub'] ?? 'Jifunze • Furahia • Fanikiwa'); ?></span>
                </div>
            </a>

            <ul class="navbar-menu" id="navbarMenu">
                <?php foreach ($nav_items as $item): ?>
                <li class="navbar-item<?php echo $active === $item['id'] ? ' active' : ''; ?>">
                    <a href="<?php echo htmlspecialchars($item['href']); ?>" class="navbar-link">
                        <i class="fas <?php echo htmlspecialchars($item['icon']); ?>" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($item['label']); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>

                <?php if ($is_guest): ?>
                <li class="navbar-item navbar-login-group-mobile" style="display:none;">
                    <a href="<?php echo app_web_path('learner/login.php'); ?>" class="nav-login-pill learner" style="width:100%;justify-content:center;margin-bottom:8px;">
                        <i class="fas fa-child"></i> <?php echo htmlspecialchars($t['nav_learner_login'] ?? 'Learner'); ?>
                    </a>
                    <a href="<?php echo app_web_path('teacher/login.php'); ?>" class="nav-login-pill teacher" style="width:100%;justify-content:center;margin-bottom:8px;">
                        <i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($t['nav_teacher_login'] ?? 'Teacher'); ?>
                    </a>
                    <a href="<?php echo app_web_path('parent/login.php'); ?>" class="nav-login-pill parent" style="width:100%;justify-content:center;">
                        <i class="fas fa-user-friends"></i> <?php echo htmlspecialchars($t['nav_parent_login'] ?? 'Parent'); ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <div class="navbar-right">
                <?php if ($logged_name !== '' && $logged_role !== ''): ?>
                    <span class="navbar-user-name"><?php echo htmlspecialchars($logged_name); ?></span>
                    <a href="<?php echo htmlspecialchars($logout_href); ?>" class="teacher-login-btn navbar-logout-btn">
                        <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                        <span><?php echo htmlspecialchars($t['nav_logout'] ?? 'Logout'); ?></span>
                    </a>
                <?php elseif ($is_guest): ?>
                    <div class="navbar-login-group">
                        <a href="<?php echo app_web_path('learner/login.php'); ?>" class="nav-login-pill learner" title="<?php echo htmlspecialchars($t['nav_learner_login'] ?? 'Learner Login'); ?>">
                            <i class="fas fa-child" aria-hidden="true"></i>
                            <span class="nav-pill-label"><?php echo htmlspecialchars($t['nav_learner_login'] ?? 'Learner'); ?></span>
                        </a>
                        <a href="<?php echo app_web_path('teacher/login.php'); ?>" class="nav-login-pill teacher" title="<?php echo htmlspecialchars($t['nav_teacher_login'] ?? 'Teacher Login'); ?>">
                            <i class="fas fa-chalkboard-teacher" aria-hidden="true"></i>
                            <span class="nav-pill-label"><?php echo htmlspecialchars($t['nav_teacher_login'] ?? 'Teacher'); ?></span>
                        </a>
                        <a href="<?php echo app_web_path('parent/login.php'); ?>" class="nav-login-pill parent" title="<?php echo htmlspecialchars($t['nav_parent_login'] ?? 'Parent Login'); ?>">
                            <i class="fas fa-user-friends" aria-hidden="true"></i>
                            <span class="nav-pill-label"><?php echo htmlspecialchars($t['nav_parent_login'] ?? 'Parent'); ?></span>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="language-dropdown">
                    <button type="button" class="language-btn" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-globe" aria-hidden="true"></i>
                        <span><?php echo strtoupper($lang); ?></span>
                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="language-menu" role="menu">
                        <a href="<?php echo ui_lang_url($lang_page, 'en'); ?>" class="language-option" role="menuitem">English</a>
                        <a href="<?php echo ui_lang_url($lang_page, 'sw'); ?>" class="language-option" role="menuitem">Kiswahili</a>
                    </div>
                </div>

                <button type="button" class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu" aria-expanded="false">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </div>
</nav>
