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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Fix ruang-admin sidebar toggle for Bootstrap 5 (no jQuery .collapse plugin)
if (typeof $.fn === 'object') {
    $.fn.collapse = $.fn.collapse || function() { return this; }
}

// SweetAlert2 Bootstrap-style confirmation in dashboard corner
function confirmAction(title, text, okText) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: okText || 'Yes',
        cancelButtonText: 'Cancel',
        position: 'top-end',
        customClass: {
            popup: 'rounded-4 shadow-lg border-0',
            confirmButton: 'btn btn-success rounded-pill px-4 fw-bold',
            cancelButton: 'btn btn-secondary rounded-pill px-4 me-2'
        },
        buttonsStyling: false,
        reverseButtons: true,
        focusCancel: true
    }).then(function(r) { return r.isConfirmed; });
}

// Global click handler for data-confirm attributes (inline onclick confirm replacements)
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    var href = el.getAttribute('href');
    var action = el.getAttribute('data-confirm-action');
    confirmAction(el.getAttribute('data-confirm-title') || 'Confirm', el.getAttribute('data-confirm'), el.getAttribute('data-confirm-ok')).then(function(confirmed) {
        if (!confirmed) return;
        if (href) { window.location.href = href; return; }
        if (el.form) {
            if (typeof el.form.requestSubmit === 'function') { el.form.requestSubmit(el); return; }
            var h = document.createElement('input');
            h.type = 'hidden'; h.name = el.name; h.value = el.value;
            el.form.appendChild(h);
            el.form.submit();
        }
    });
});
</script>

