<?php
$base = $base_path ?? '';
$lang = $current_lang ?? 'en';
include __DIR__ . '/paths-script.php';

require_once __DIR__ . '/url_helpers.php';
if (!isset($nav_items)) {
    require_once __DIR__ . '/nav-items.php';
}
?>
<footer class="site-footer">
    <div class="container-child">
        <div class="row-child footer-grid">
            <div class="col-child-3 footer-col">
                <h3 class="footer-heading">Kona Ya Hisabati</h3>
                <p class="footer-text"><?php echo htmlspecialchars($t['footer_tagline'] ?? 'Interactive numeracy for Tanzanian early grade learners.'); ?></p>
            </div>
            <div class="col-child-3 footer-col">
                <h3 class="footer-heading">Quick Links</h3>
                <ul class="footer-links">
                    <?php foreach ($nav_items as $item): ?>
                    <li><a href="<?php echo htmlspecialchars($item['href']); ?>"><?php echo htmlspecialchars($item['label']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-child-3 footer-col">
                <h3 class="footer-heading">Information</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo app_web_path('about.php?lang=' . urlencode($lang)); ?>"><?php echo htmlspecialchars($t['footer_about'] ?? 'About'); ?></a></li>
                    <li><a href="<?php echo app_web_path('contact.php'); ?>"><?php echo htmlspecialchars($t['footer_contact'] ?? 'Contact'); ?></a></li>
                    <li><a href="<?php echo app_web_path('terms.php'); ?>"><?php echo htmlspecialchars($t['footer_terms'] ?? 'Terms'); ?></a></li>
                </ul>
            </div>
            <div class="col-child-3 footer-col">
                <h3 class="footer-heading">Contact</h3>
                <p class="footer-text"><i class="fas fa-envelope me-2" aria-hidden="true"></i>info@konahisabati.com</p>
                <p class="footer-text"><i class="fas fa-phone me-2" aria-hidden="true"></i>+255 XXX XXX XXX</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Kona Ya Hisabati. All rights reserved.</p>
        </div>
    </div>
</footer>
