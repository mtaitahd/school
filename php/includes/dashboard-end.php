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

