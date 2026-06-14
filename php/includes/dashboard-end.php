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

// SweetAlert2 confirmation dialog (centered, Bootstrap-style)
function confirmAction(title, text, okText) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: okText || 'Yes',
        cancelButtonText: 'Cancel',
        position: 'center',
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

// Global click handler for data-confirm attributes
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-confirm]');
    if (!el) return;
    e.preventDefault();
    var href = el.getAttribute('href');
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

