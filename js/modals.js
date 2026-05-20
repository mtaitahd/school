/**
 * Kona modal helpers
 */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('is-open');
        el.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('is-open');
        el.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function (e) {
    const overlay = e.target.closest('.kona-modal-overlay');
    if (overlay && e.target === overlay) {
        closeModal(overlay.id);
    }
    if (e.target.closest('[data-modal-close]')) {
        const modal = e.target.closest('.kona-modal-overlay');
        if (modal) closeModal(modal.id);
    }
});

document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.kona-modal-overlay.is-open').forEach(function (m) {
            closeModal(m.id);
        });
    }
});
