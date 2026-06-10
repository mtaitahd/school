          </div>
        </div>
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>copyright &copy; Kona Ya Hisabati</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<div class="a11y-toolbar" role="group" aria-label="Customizer options">
    <button type="button" class="a11y-btn" id="btnWhatsApp" title="Chat on WhatsApp" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></button>
    <button type="button" class="a11y-btn" id="btnFont" title="Change font" aria-label="Cycle font"><i class="fas fa-font"></i></button>
    <button type="button" class="a11y-btn" id="btnColor" title="Change color theme" aria-label="Cycle color theme"><i class="fas fa-palette"></i></button>
</div>

<?php include __DIR__ . '/whatsapp-support.php'; ?>
<?php $asset_base = ($base_path ?? '') . 'assets/'; ?>
<script src="<?php echo $asset_base; ?>vendor/jquery/jquery.min.js"></script>
<script src="<?php echo $asset_base; ?>vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="<?php echo $asset_base; ?>js/ruang-admin.min.js"></script>
<script>
// Fix ruang-admin sidebar toggle for Bootstrap 5 (no jQuery .collapse plugin)
if (typeof $.fn === 'object') {
    $.fn.collapse = $.fn.collapse || function() { return this; }
}
</script>
<script src="<?php echo ($base_path ?? '') . 'js/customizer.js'; ?>"></script>
