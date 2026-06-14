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

// Bootstrap toast notification in top-right corner
function showToast(message, type) {
    type = type || 'danger';
    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    var colors = { danger: 'text-bg-danger', success: 'text-bg-success', warning: 'text-bg-warning', info: 'text-bg-info' };
    var cls = colors[type] || 'text-bg-danger';
    var id = 't-' + Date.now();
    container.insertAdjacentHTML('beforeend',
        '<div id="' + id + '" class="toast align-items-center ' + cls + ' border-0" role="alert">' +
          '<div class="d-flex">' +
            '<div class="toast-body fw-semibold">' + message + '</div>' +
            '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
          '</div>' +
        '</div>');
    var el = document.getElementById(id);
    var t = new bootstrap.Toast(el, { autohide: true, delay: 5000 });
    t.show();
    el.addEventListener('hidden.bs.toast', function() { el.remove(); });
}

// Native confirm fallback
function confirmAction(title, text, okText) {
    return Promise.resolve(confirm(text || title));
}

// Global click handler for data-confirm attributes (inline onclick confirm replacements)
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    var href = el.getAttribute('href');
    var msg = el.getAttribute('data-confirm');
    if (!confirm(msg)) return;
    if (href) { window.location.href = href; return; }
    if (el.form) {
        if (typeof el.form.requestSubmit === 'function') { el.form.requestSubmit(el); return; }
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = el.name; h.value = el.value;
        el.form.appendChild(h);
        el.form.submit();
    }
});
</script>

