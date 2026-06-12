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
                <div class="footer-social">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="https://wa.me/255655879005" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                </div>
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
                    <li><a href="<?php echo app_web_path('about?lang=' . urlencode($lang)); ?>"><?php echo htmlspecialchars($t['footer_about'] ?? 'About'); ?></a></li>
                    <li><a href="<?php echo app_web_path('contact'); ?>"><?php echo htmlspecialchars($t['footer_contact'] ?? 'Contact'); ?></a></li>
                    <li><a href="<?php echo app_web_path('terms'); ?>"><?php echo htmlspecialchars($t['footer_terms'] ?? 'Terms'); ?></a></li>
                </ul>
            </div>
            <div class="col-child-3 footer-col">
                <h3 class="footer-heading">Contact</h3>
                <p class="footer-text"><i class="fas fa-envelope" style="width:18px;"></i> info@konahisabati.com</p>
                <p class="footer-text"><i class="fas fa-phone" style="width:18px;"></i> +255 XXX XXX XXX</p>
                <p class="footer-text"><i class="fas fa-map-marker-alt" style="width:18px;"></i> Tanzania</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Kona Ya Hisabati. All rights reserved.</p>
        </div>
    </div>
</footer>
<div class="a11y-toolbar" role="group" aria-label="Customizer options">
    <span class="whatsapp-label">Need Support?</span>
    <button type="button" class="a11y-btn" id="btnWhatsApp" title="Chat on WhatsApp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></button>
    <button type="button" class="a11y-btn" id="btnFont" title="Change font" aria-label="Cycle font"><i class="fas fa-font"></i></button>
    <button type="button" class="a11y-btn" id="btnColor" title="Change color theme" aria-label="Cycle color theme"><i class="fas fa-palette"></i></button>
</div>

<script src="<?php echo ($base_path ?? '') . 'js/customizer.js'; ?>"></script>
