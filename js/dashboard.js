/**
 * Dashboard layout: desktop collapse + mobile off-canvas sidebar
 */
(function () {
    const shell = document.getElementById('dashboardShell') || document.querySelector('.dashboard-shell');
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('dashboardSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (!shell || !toggle || !sidebar) return;

    const MQ = 992;
    const STORAGE_KEY = 'kona-dashboard-sidebar-collapsed';

    function isMobile() {
        return window.innerWidth <= MQ;
    }

    function isMobileOpen() {
        return sidebar.classList.contains('open');
    }

    function setMobileOpen(open) {
        sidebar.classList.toggle('open', open);
        shell.classList.toggle('sidebar-open', open);
        document.body.classList.toggle('dashboard-sidebar-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (backdrop) {
            backdrop.setAttribute('aria-hidden', open ? 'false' : 'true');
        }
    }

    function setDesktopCollapsed(collapsed) {
        shell.classList.toggle('sidebar-collapsed', collapsed);
        try {
            localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
        } catch (e) { /* ignore */ }
    }

    function loadDesktopState() {
        try {
            if (localStorage.getItem(STORAGE_KEY) === '1') {
                setDesktopCollapsed(true);
            }
        } catch (e) { /* ignore */ }
    }

    function closeMobile() {
        if (isMobileOpen()) setMobileOpen(false);
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (isMobile()) {
            setMobileOpen(!isMobileOpen());
        } else {
            closeMobile();
            setDesktopCollapsed(!shell.classList.contains('sidebar-collapsed'));
        }
    });

    backdrop?.addEventListener('click', closeMobile);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMobile();
    });

    document.addEventListener('click', function (e) {
        if (isMobile() && isMobileOpen() && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
            closeMobile();
        }
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            closeMobile();
            loadDesktopState();
        } else {
            shell.classList.remove('sidebar-collapsed');
        }
    });

    if (!isMobile()) {
        loadDesktopState();
    }

    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-controls', 'dashboardSidebar');
})();
