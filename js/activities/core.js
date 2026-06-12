/**
 * Shared activity utilities
 */
const ActivityCore = {
    OBJECT_EMOJIS: {
        apple: '🍎', mango: '🥭', ball: '⚽', car: '🚗',
        cup: '🥤', fruit: '🍇', animal: '🐱', toy: '🧸',
        star: '⭐', fish: '🐟', dog: '🐶', cat: '🐱',
        bird: '🐦', bunny: '🐰', flower: '🌸', tree: '🌳',
        balloon: '🎈', bike: '🚲', book: '📚', cake: '🎂',
        candy: '🍬', car: '🚗', cookie: '🍪', duck: '🦆',
        elephant: '🐘', frog: '🐸', grapes: '🍇', hat: '🎩',
        icecream: '🍦', juice: '🧃', kite: '🪁', lion: '🦁',
        monkey: '🐵', num: '🔢', orange: '🍊', penguin: '🐧',
        queen: '👑', robot: '🤖', sun: '☀️', truck: '🛻',
        umbrella: '☂️', van: '🚐', watermelon: '🍉', xylophone: '🔔',
        yarn: '🧶', zebra: '🦓'
    },

    SHAPE_ICONS: {
        circle: '⭕', square: '⬜', triangle: '🔺',
        rectangle: '▬', diamond: '🔷', star: '⭐',
        heart: '❤️', oval: '🥚', crescent: '🌙'
    },

    NUMBER_WORDS: [
        '', 'One', 'Two', 'Three', 'Four', 'Five',
        'Six', 'Seven', 'Eight', 'Nine', 'Ten',
        'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen',
        'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen', 'Twenty'
    ],

    ENCOURAGEMENTS: [
        'Great job!', 'Well done!', 'Awesome!', 'You are so smart!',
        'Fantastic!', 'Amazing!', 'Super!', 'Wonderful!',
        'Excellent!', 'Brilliant!', 'Good work!', 'Keep it up!'
    ],

    shuffle(arr) {
        const a = [...arr];
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    },

    getDifficultyRange(config) {
        const level = config.difficulty || 'easy';
        if (level === 'easy') return { min: config.min ?? 1, max: config.max ?? 5 };
        if (level === 'medium') return { min: config.min ?? 1, max: config.max ?? 10 };
        return { min: config.min ?? 10, max: config.max ?? 20 };
    },

    randomInt(min, max) {
        return Math.floor(Math.random() * (max - min + 1)) + min;
    },

    pickRandom(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    },

    buildMCOptions(correct, poolMin, poolMax, count = 3) {
        const opts = [correct];
        let tries = 0;
        while (opts.length < count && tries < 50) {
            const n = Math.floor(Math.random() * (poolMax - poolMin + 1)) + poolMin;
            if (!opts.includes(n) && n >= 0) opts.push(n);
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

    sayNumber(num, then) {
        const word = this.NUMBER_WORDS[num];
        this.say(word ?? String(num), then);
    },

    sayEncouragement(then) {
        this.say(this.pickRandom(this.ENCOURAGEMENTS), then);
    },

    renderPrompt(text, emoji) {
        const p = document.createElement('p');
        p.className = 'activity-prompt';
        p.textContent = emoji ? emoji + ' ' + text : text;
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

    renderEmojiMC(choices, onSelect) {
        const options = this.getOptions();
        options.innerHTML = '';
        choices.forEach(({ label, emoji }) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'answer-btn answer-btn-emoji';
            btn.innerHTML = emoji ? `<span class="answer-emoji">${emoji}</span><span class="answer-label">${label}</span>` : label;
            btn.onclick = () => onSelect(label, btn);
            options.appendChild(btn);
        });
    },

    celebrate() {
        if (typeof showStarAnimation === 'function') showStarAnimation();
        const layer = document.createElement('div');
        layer.className = 'confetti-layer';
        const colors = ['#FFD700', '#4A90E2', '#50C878', '#FF8C00', '#FF6B6B', '#FF69B4', '#9B59B6'];
        const emojis = ['⭐', '🌟', '✨', '🎉', '🎊', '💫', '🏆'];
        for (let i = 0; i < 50; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            piece.style.left = Math.random() * 100 + '%';
            piece.style.animationDelay = Math.random() * 1.2 + 's';
            piece.style.animationDuration = (2 + Math.random() * 2) + 's';
            if (i > 30) {
                piece.textContent = emojis[i % emojis.length];
                piece.style.fontSize = '1.5rem';
                piece.style.background = 'none';
            } else {
                piece.style.background = colors[i % colors.length];
            }
            layer.appendChild(piece);
        }
        document.body.appendChild(layer);
        setTimeout(() => layer.remove(), 3500);
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
