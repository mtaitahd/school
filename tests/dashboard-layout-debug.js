document.addEventListener('DOMContentLoaded', function () {
    var topbar = document.querySelector('.dashboard-topbar');
    var shell = document.querySelector('.dashboard-shell');
    var pre = document.createElement('pre');
    pre.id = 'layout-debug';
    pre.style.cssText = 'position:fixed;bottom:8px;left:8px;background:#fff;padding:8px;z-index:99999;font:12px monospace;border:1px solid #000';
    if (topbar) {
        var r = topbar.getBoundingClientRect();
        pre.textContent = 'topbar.top=' + r.top + ' shell.top=' + (shell ? shell.getBoundingClientRect().top : '?');
    }
    document.body.appendChild(pre);
});
