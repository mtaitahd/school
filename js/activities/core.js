/**
 * Shared activity utilities
 */
const ActivityCore = {
    shuffle(arr) {
        const a = [...arr];
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    },

    buildMCOptions(correct, poolMin, poolMax, count = 3) {
        const opts = [correct];
        let tries = 0;
        while (opts.length < count && tries < 50) {
            const n = Math.floor(Math.random() * (poolMax - poolMin + 1)) + poolMin;
            if (!opts.includes(n)) opts.push(n);
            tries++;
        }
        return this.shuffle(opts);
    },

    getDisplay() {
        return document.getElementById('activityDisplay');
    },

    getOptions() {
        return document.getElementById('answerOptions');
    },

    clearStage() {
        const d = this.getDisplay();
        const o = this.getOptions();
        if (d) d.innerHTML = '';
        if (o) o.innerHTML = '';
        return { display: d, options: o };
    },

    say(text, then) {
        if (typeof playAudio === 'function' && text) playAudio(text);
        if (then) setTimeout(then, text && text.length > 50 ? 4000 : 2500);
    },

    renderPrompt(text) {
        const p = document.createElement('p');
        p.className = 'activity-prompt';
        p.textContent = text;
        return p;
    },

    renderMC(choices, onSelect) {
        const options = this.getOptions();
        options.innerHTML = '';
        choices.forEach((n) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'answer-btn';
            btn.textContent = n;
            btn.onclick = () => onSelect(n, btn);
            options.appendChild(btn);
        });
    },

    celebrate() {
        if (typeof showStarAnimation === 'function') showStarAnimation();
        const layer = document.createElement('div');
        layer.className = 'confetti-layer';
        const colors = ['#FFD700', '#4A90E2', '#50C878', '#FF8C00', '#FF6B6B'];
        for (let i = 0; i < 40; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.background = colors[i % colors.length];
            piece.style.animationDelay = Math.random() * 0.8 + 's';
            layer.appendChild(piece);
        }
        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 3000);
    },

    bindTopbarAudio(fn) {
        const btn = document.getElementById('topbarAudioBtn');
        if (btn) btn.onclick = fn;
    },

    hideMultiRoundUI() {
        document.querySelectorAll('.progress-bar-child').forEach((el) => { el.style.display = 'none'; });
        const scoreBlock = document.querySelector('.activity-container .text-center.mt-30');
        if (scoreBlock) scoreBlock.style.display = 'none';
    },

    showMiniGame(onDone) {
        const overlay = document.createElement('div');
        overlay.className = 'mini-game-overlay';
        const card = document.createElement('div');
        card.className = 'mini-game-card';
        card.innerHTML = '<h3>Counting Star!</h3><p class="activity-prompt">Tap 1, 2, 3, 4, 5 in order!</p>';
        const tiles = document.createElement('div');
        tiles.className = 'number-tiles';
        let next = 1;
        for (let i = 1; i <= 5; i++) {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'number-tile';
            b.textContent = i;
            b.onclick = () => {
                if (i === next) {
                    b.classList.add('correct');
                    next++;
                    if (next > 5) setTimeout(() => { overlay.remove(); if (onDone) onDone(); }, 600);
                }
            };
            tiles.appendChild(b);
        }
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-child btn-child-primary mt-20';
        close.textContent = 'Done';
        close.onclick = () => { overlay.remove(); if (onDone) onDone(); };
        card.appendChild(tiles);
        card.appendChild(close);
        overlay.appendChild(card);
        document.body.appendChild(overlay);
    }
};
