/**
 * Auth split-screen UI: password toggle, submit loading state, fade-in.
 * Does not alter form submission or validation.
 */
(function () {
    'use strict';

    document.querySelectorAll('.auth-password-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-target');
            var input = targetId ? document.getElementById(targetId) : null;
            if (!input) return;

            var isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
            btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');

            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            }
        });
    });

    document.querySelectorAll('.auth-split-form-panel form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.classList.add('is-loading');
                submitBtn.setAttribute('aria-busy', 'true');
            }
        });
    });
})();
