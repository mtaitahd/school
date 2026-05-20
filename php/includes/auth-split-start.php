<?php
/**
 * Split-screen auth layout — visual panel (left) + form panel (right).
 * Set $auth_base (e.g. '' or '../') and optional $auth_tagline before include.
 */
$auth_base = $auth_base ?? '';
$auth_tagline = $auth_tagline ?? 'Jifunze • Furahia • Fanikiwa';
$auth_image = $auth_base . 'assets/image/logo.png';
$auth_logo = $auth_base . 'assets/images/logo.png';
?>
<main class="auth-split-wrapper auth-fade-in" role="main">
    <div class="auth-split-card">
        <aside class="auth-split-visual">
            <div class="auth-split-visual-bg" style="background-image: url('<?php echo htmlspecialchars($auth_image); ?>');"></div>
            <div class="auth-split-visual-overlay"></div>
            <div class="auth-split-visual-content">
                <a href="<?php echo htmlspecialchars($auth_base); ?>index.php" class="auth-split-brand">
                    <img src="<?php echo htmlspecialchars($auth_logo); ?>" alt="" class="auth-split-brand-logo" width="48" height="48">
                    <span class="auth-split-brand-name">Kona Ya Hisabati</span>
                </a>
                <p class="auth-split-tagline"><?php echo htmlspecialchars($auth_tagline); ?></p>
                <div class="auth-split-visual-spacer"></div>
                <div class="auth-split-social">
                    <span class="auth-split-social-label">Connect with us</span>
                    <div class="auth-split-social-links">
                        <a href="<?php echo htmlspecialchars($auth_base); ?>index.php" class="auth-split-social-link" aria-label="Home"><i class="fas fa-home" aria-hidden="true"></i></a>
                        <a href="<?php echo htmlspecialchars($auth_base); ?>about.php" class="auth-split-social-link" aria-label="About"><i class="fas fa-info-circle" aria-hidden="true"></i></a>
                        <a href="<?php echo htmlspecialchars($auth_base); ?>contact.php" class="auth-split-social-link" aria-label="Help"><i class="fas fa-question-circle" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </aside>
        <div class="auth-split-form-panel">
