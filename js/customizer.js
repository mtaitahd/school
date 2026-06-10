(function() {
    var fonts = ['Poppins, sans-serif', 'Roboto, sans-serif', '"Open Sans", sans-serif', '"Playfair Display", serif', 'Montserrat, sans-serif'];
    var themes = [
        { name: 'Default', bodyBg: '', textColor: '', cardBg: '' },
        { name: 'Dark', bodyBg: '#1a1a2e', textColor: '#e0e0e0', cardBg: '#16213e' },
        { name: 'Nature', bodyBg: '#e8f5e9', textColor: '#1b5e20', cardBg: '#c8e6c9' },
        { name: 'Warm', bodyBg: '#fff3e0', textColor: '#4e342e', cardBg: '#ffe0b2' }
    ];
    var fi = parseInt(localStorage.getItem('kyh_font_index') || '0');
    var ti = parseInt(localStorage.getItem('kyh_theme_index') || '0');

    function applyFont(index) {
        var f = fonts[index] || fonts[0];
        document.documentElement.style.setProperty('--kyh-font-family', f);
        document.body.style.fontFamily = f;
        localStorage.setItem('kyh_font_index', index);
    }

    function applyTheme(index) {
        var t = themes[index] || themes[0];
        if (t.bodyBg) {
            document.body.style.backgroundColor = t.bodyBg;
            document.body.style.color = t.textColor;
            document.documentElement.style.setProperty('--kyh-text-color', t.textColor);
            document.documentElement.style.setProperty('--kyh-bg-color', t.bodyBg);
        } else {
            document.body.style.backgroundColor = '';
            document.body.style.color = '';
            document.documentElement.style.setProperty('--kyh-text-color', '');
            document.documentElement.style.setProperty('--kyh-bg-color', '');
        }
        localStorage.setItem('kyh_theme_index', index);
    }

    applyFont(fi);
    applyTheme(ti);

    var btnWA = document.getElementById('btnWhatsApp');
    var btnFont = document.getElementById('btnFont');
    var btnColor = document.getElementById('btnColor');

    if (btnWA) {
        btnWA.addEventListener('click', function() {
            window.open('https://wa.me/255616591639?text=Hi%20Kona%20Ya%20Hisabati', '_blank');
        });
    }
    if (btnFont) {
        btnFont.addEventListener('click', function() {
            fi = (fi + 1) % fonts.length;
            applyFont(fi);
        });
    }
    if (btnColor) {
        btnColor.addEventListener('click', function() {
            ti = (ti + 1) % themes.length;
            applyTheme(ti);
        });
    }
})();
