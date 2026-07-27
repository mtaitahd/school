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
        candy: '🍬', cookie: '🍪', duck: '🦆',
        elephant: '🐘', frog: '🐸', grapes: '🍇', hat: '🎩',
        icecream: '🍦', juice: '🧃', kite: '🪁', lion: '🦁',
        monkey: '🐵', num: '🔢', orange: '🍊', penguin: '🐧',
        queen: '👑', robot: '🤖', sun: '☀️', truck: '🛻',
        umbrella: '☂️', van: '🚐', watermelon: '🍉', xylophone: '🔔',
        yarn: '🧶', zebra: '🦓',
        pencil: '✏️', ruler: '📏', eraser: '🧽', desk: '📚',
        chair: '🪑', table: '🍽️', board: '📋', mushroom: '🍄',
        butterfly: '🦋', rabbit: '🐇', goat: '🐐', chicken: '🐔',
        mosquito: '🦟', bee: '🐝', fly: '🪰', stick: '🥢',
        cow: '🐄', pumpkin: '🎃', bell: '🔔', guitar: '🎸',
        whistle: '📯', papaya: '🍈', glass: '🥛', plate: '🍽️',
        eggplant: '🍆', cabbage: '🥬', boat: '⛵', eraser: '🧽',
        leaf: '🍃', log: '🪵', coconut: '🥥'
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

    NUMBER_WORDS_SW: [
        '', 'Moja', 'Mbili', 'Tatu', 'Nne', 'Tano',
        'Sita', 'Saba', 'Nane', 'Tisa', 'Kumi',
        'Kumi na Moja', 'Kumi na Mbili', 'Kumi na Tatu', 'Kumi na Nne', 'Kumi na Tano',
        'Kumi na Sita', 'Kumi na Saba', 'Kumi na Nane', 'Kumi na Tisa', 'Ishirini'
    ],

    ENCOURAGEMENTS: [
        'Great job!', 'Well done!', 'Awesome!', 'You are so smart!',
        'Fantastic!', 'Amazing!', 'Super!', 'Wonderful!',
        'Excellent!', 'Brilliant!', 'Good work!', 'Keep it up!',
        'You did it!', 'Great counting!', 'Fantastic work!', 'Star!'
    ],

    ENCOURAGEMENTS_SW: [
        'Kazi nzuri!', 'Umefanya vizuri!', 'Wa ajabu!', 'Wewe ni mwerevu!',
        'Stahamabili!', 'Ajabu!', 'Super!', 'Wonderful!',
        'Bora!', 'Bright!', 'Kazi njema!', 'Endelea hivyo!',
        'Umeifanya!', 'Hesabu nzuri!', 'Kazi nzuri sana!', 'Nyota!'
    ],

    FINISH_MESSAGES: [
        'Excellent!', 'You did it!', 'Great counting!', 'Fantastic!',
        'Wonderful!', 'Amazing!', 'Super star!', 'Well done!'
    ],

    FINISH_MESSAGES_SW: [
        'Bora!', 'Umeifanya!', 'Hesabu nzuri!', 'Stahamabili!',
        'Wonderful!', 'Ajabu!', 'NyotaSuper!', 'Umefanya vizuri!'
    ],

    shuffle(arr) {
        const a = [...arr];
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    },

    getLang() {
        return (window.ACTIVITY_CONFIG && window.ACTIVITY_CONFIG.lang)
            || new URLSearchParams(window.location.search).get('lang')
            || 'en';
    },

    t(key, params) {
        var entry = this.LANG[key];
        if (!entry) return key;
        var text = (this.getLang() === 'sw' && entry.sw) ? entry.sw : entry.en;
        if (params) {
            Object.keys(params).forEach(function (k) {
                text = text.replace(new RegExp('\\{' + k + '\\}', 'g'), params[k]);
            });
        }
        return text;
    },

    sayLang(enText, swText) {
        this.say(this.getLang() === 'sw' && swText ? swText : enText);
    },

    LANG: {
        tap_each:          { en: 'Tap each {obj} as you count!', sw: 'Gusa kila {obj} unapohesabu!' },
        count_only:        { en: 'Count only the {obj}! Tap each {obj}!', sw: 'Hesabu {obj} tu! Gusa kila {obj}!' },
        lets_count:        { en: "Let's count the {obj}. Tap each one as I say the number.", sw: 'Hebu tuhesabu {obj}. Gusa kila moja ninaposema nambari.' },
        lets_count_together: { en: "Let's count the {obj} with me!", sw: 'Hebu tuhesabu {obj} pamoja!' },
        how_many:          { en: 'How many {obj} did you count?', sw: 'Ulihesabu {obj} ngapi?' },
        now_choose:        { en: 'Now choose the number we counted.', sw: 'Sasa chagua nambari tuliyohesabu.' },
        good_try:          { en: 'Good try. Let us count together again.', sw: 'Jaribu tena. Hebu tuhesabu pamoja.' },
        count_obj:         { en: 'Count the {obj}!', sw: 'Hesabu {obj}!' },
        row_count:         { en: 'Row {n}: Count the {obj}!', sw: 'Safu {n}: Hesabu {obj}!' },
        how_many_rows:     { en: 'How many rows did you count?', sw: 'Ulihesabu safu ngapi?' },
        choose_number:     { en: 'You counted {n} rows! Choose the number.', sw: 'Ulihesabu safu {n}! Chagua nambari.' },
        try_again_rows:    { en: 'Try again. Count the rows!', sw: 'Jaribu tena. Hesabu safu!' },
        find_number:       { en: 'Find number {n}', sw: 'Tafuta nambari {n}' },
        find_all_number:   { en: 'Find ALL the number {n}s! Tap each one.', sw: 'Tafuta nambari {n} ZOTE! Gusa kila moja.' },
        oops_not:          { en: 'Oops! That is not {n}. Try again.', sw: 'Sio {n}! Jaribu tena.' },
        find_object_shaped: { en: 'Find the object shaped like number {n}!', sw: 'Tafuta kitu kilicho naumbo kama nambari {n}!' },
        yes_looks_like:    { en: 'Yes! Number {n} looks like a {obj}!', sw: 'Ndiyo! Nambari {n} inaonekana kama {obj}!' },
        that_is:           { en: 'That is a {obj}. Try again!', sw: 'Hiyo ni {obj}. Jaribu tena!' },
        trace_number:      { en: 'Trace the number {n}!', sw: 'Fuatilia nambari {n}!' },
        put_in_order:      { en: 'Put numbers in order from {min} to {max}', sw: 'Weka nambari kwa mpaka from {min} hadi {max}' },
        lets_order:        { en: 'Let us put the numbers in order. Drag number one to the first box.', sw: 'Hebu tuweke nambari kwa mpaka. Buruta nambari moja kwenye sanduku la kwanza.' },
        try_different:     { en: 'Try a different spot. We need number {n}.', sw: 'Jaribu sehemu nyingine. Tunahitaji nambari {n}.' },
        what_missing:      { en: 'What number is missing?', sw: 'Nambari ipo imetoweka?' },
        what_after:        { en: 'What number comes after {n1} and before {n2}?', sw: 'Nambari ipo inafuata {n1} kabla ya {n2}?' },
        try_after:         { en: 'Try again. What comes after {n}?', sw: 'Jaribu tena. Inafuata {n} ni ipi?' },
        find_group:        { en: 'Find the group with {n} {obj}!', sw: 'Tafuta kundi lenye {obj} {n}!' },
        that_has:          { en: 'Can you find the group that has {n} {obj}?', sw: 'Unaweza kutafuta kundi linalojumuisha {obj} {n}?' },
        group_has:         { en: 'That group has {n} {obj}. Find the group with {t} {obj}!', sw: 'Kundi hilo lina {n} {obj}. Tafuta kundi lenye {t} {obj}!' },
        find_the:          { en: 'Find the {obj}', sw: 'Tafuta {obj}' },
        can_find:          { en: 'Can you find the {obj}? Tap on the {obj}.', sw: 'Unaweza kutafuta {obj}? Gusa juu ya {obj}.' },
        that_is_the:       { en: 'That is a {obj}!', sw: 'Hiyo ni {obj}!' },
        what_is_this:      { en: 'What is this?', sw: 'Hii ni nini?' },
        tap_correct_name:  { en: 'What is this? Tap the correct name.', sw: 'Hii ni nini? Jina sahihi.' },
        move_all:          { en: 'Move all {obj} into the basket', sw: 'Hamisha {obj} yote kwenye kikapu' },
        how_many_in_basket: { en: 'How many {obj} are in the basket?', sw: 'Kuna {obj} ngapi kwenye kikapu?' },
        count_again:       { en: 'Count again. How many {obj}?', sw: 'Hesabu tena. {obj} ngapi?' },
        lets_add:          { en: 'Let us add the {obj}. Move them into the basket!', sw: 'Hebu tuongeze {obj}. Hamisha kwenye kikapu!' },
        tap_take_away:     { en: 'Tap {n} {obj} to take away', sw: 'Gusa {obj} {n} kuchukua' },
        how_many_left:     { en: 'How many {obj} are left?', sw: 'Kumebaki {obj} ngapi?' },
        count_left:        { en: 'Count what is left. Try again.', sw: 'Hesabu kilichobaki. Jaribu tena.' },
        lets_subtract:     { en: 'We have {n} {obj}. Tap {r} to take them away.', sw: 'Tuna {obj} {n}. Gusa {obj} {r} kuchukua.' },
        sort_by_size:      { en: 'Sort the {obj}s by size!', sw: 'Panga {obj} kwa ukubwa!' },
        sort_each:         { en: 'Sort each {obj} by its size. Small, Medium, or Large.', sw: 'Panga kila {obj} kwa ukubwa wake. Ndogo, Wastani, au Kubwa.' },
        great_sorting:     { en: 'Great sorting! You sorted by size!', sw: 'Upangaji mzuri! Umepanga kwa ukubwa!' },
        what_next_pattern: { en: 'What comes next in the pattern?', sw: 'Nini kinachofuata katika muundo?' },
        correct_next:      { en: 'Correct! {n} comes next!', sw: 'Sahihi! {n} inafuata!' },
        try_pattern:       { en: 'Try again. Look at the pattern carefully.', sw: 'Jaribu tena. Angalia muundo kwa makini.' },
        look_pattern:      { en: 'Look at the pattern. What comes next?', sw: 'Angalia muundo. Nini kinachofuata?' },
        pop_balloon:       { en: 'Pop the balloon with number {n}!', sw: 'Rarua baluni yenye nambari {n}!' },
        tap_number:        { en: 'Tap number {n}.', sw: 'Gusa nambari {n}.' },
        tap_number_zero:   { en: 'Tap number zero.', sw: 'Gusa nambari sifuri.' },
        tap_number_ten:    { en: 'Tap number ten.', sw: 'Gusa nambari kumi.' },
        drag_into_box:     { en: 'Drag number ten into the yellow box.', sw: 'Buruta nambari kumi kwenye sanduku la manjano.' },
        match_ten_apples:  { en: 'Match number ten with the group that has ten apples.', sw: 'Linganisha nambari kumi na kundi lenye apuli kumi.' },
        first_tap:         { en: 'First tap the number 10, then tap the group with ten apples.', sw: 'Kwanza gusa nambari 10, kisha gusa kundi lenye apuli kumi.' },
        drag_empty_zero:   { en: 'Drag the pictures with no objects to the box labeled Zero.', sw: 'Buruta picha zenye vitu visivyokuwa na vitu kwenye sanduku la Sifuri.' },
        tap_empty_plate:   { en: 'Tap the plate with no oranges.', sw: 'Gusa sahani bila chungwa.' },
        choose_level:      { en: 'Choose your level!', sw: 'Chagua kiwango chako!' },
        welcome_game:      { en: 'Welcome to Math Game! Choose a level to start.', sw: 'Karibu kwenye Mchezo wa Hisabati! Chagua kiwango kuanza.' },
        correct:           { en: 'Correct!', sw: 'Sahihi!' },
        try_again:         { en: 'Try again.', sw: 'Jaribu tena.' },
        count_obj_with_me: { en: 'Count the {obj} with me!', sw: 'Hesabu {obj} pamoja nami!' },
        tap_each_one:      { en: 'Count the {obj}. Tap each one.', sw: 'Hesabu {obj}. Gusa kila moja.' },
        choose_answer:     { en: 'Choose the correct answer.', sw: 'Jibu la sahihi.' },
        what_answer:       { en: 'What is the answer?', sw: 'Jibu ni lipi?' },
        level_complete:    { en: '{n} Complete!', sw: '{n} Imekamilika!' },
        you_got:           { en: 'You got {c} out of {t} correct!', sw: 'Umepata {c} kati ya {t} sahihi!' },
        great_job:         { en: 'Great job! You completed {n}!', sw: 'Kazi nzuri! Umekamilisha {n}!' },
        good_job:          { en: 'Good job!', sw: 'Kazi nzuri!' },
        well_done:         { en: 'Well done!', sw: 'Umefanya vizuri!' },
        excellent:         { en: 'Excellent!', sw: 'Bora!' },
        amazing_work:      { en: 'Amazing work!', sw: 'Kazi ya ajabu!' },
        amazing_counting:  { en: 'Amazing counting!', sw: 'Hesabu ya ajabu!' },
        great_counting:    { en: 'Great counting!', sw: 'Hesabu nzuri!' },
        you_counted:       { en: 'You counted {n}!', sw: 'Ulihesabu {n}!' },
        empty_means_zero:  { en: 'There are no oranges. No oranges means zero.', sw: 'Hakuna chungwa. Kutokuwa na chungwa ni sifuri.' },
        this_has_objects:  { en: 'This has objects. Find the empty ones.', sw: 'Hii ina vitu. Tafuta zisizo na chochote.' },
        keep_tracing:      { en: 'Good! Keep tracing on the line!', sw: 'Vizuri! Endelea kufuatilia kwenye mstari!' },
        great_tracing:     { en: 'Great! You are following the number!', sw: 'Nzuri! Unafuata nambari!' },
        stay_on_line:      { en: 'Try to stay on the dotted line!', sw: 'Jaribu kubaki kwenye mstari wa alama!' },
        trace_finger:      { en: 'Draw over the number with your finger!', sw: 'Chora juu ya nambari kwa kidole chako!' },
        excellent_trace:   { en: 'Excellent! You traced {n} perfectly!', sw: 'Bora! Umefuatilia {n} kikamilifu!' },
        trace_again:       { en: 'Great tracing! Trace it again! ({d}/{t})', sw: 'Fuatilia nzuri! Fuatilia tena! ({d}/{t})' },
        stay_dotted:       { en: 'Hmm, stay on the dotted line! Try again.', sw: 'Jaribu kubaki kwenye mstari wa alama! Jaribu tena.' },
        draw_more:         { en: 'Draw more on the number first!', sw: 'Chora zaidi kwenye nambari kwanza!' },
        find_all_numbers:  { en: 'Find all the numbers in this fun game!', sw: 'Tafuta nambari zote katika mchezo huu!' },
        which_object:      { en: 'Which object looks like number {n}? Tap the right one!', sw: 'Kitu gani kinaonekana kama nambari {n}? Gusa kile sahihi!' },
        ready_next:        { en: 'Great job! Ready for number {n}!', sw: 'Kazi nzuri! Tayari kwa nambari {n}!' },
        next_number:       { en: 'Great counting! Ready for the next number!', sw: 'Hesabu nzuri! Tayari kwa nambari inayofuata!' },
        find_falling:      { en: 'Find the falling number {n}! Tap it!', sw: 'Tafuta nambari {n} inayodondoka! Gusa!' },
        score_message:     { en: 'You scored {s} out of {t}!', sw: 'Umepata {s} kati ya {t}!' },
        pop_balloon_swing: { en: 'Swing to pop the balloon with number {n}!', sw: 'Tetemeka kuarusha baluni yenye nambari {n}!' },
        drag_correct:      { en: 'Drag the correct answer here', sw: 'Buruta jibu sahihi hapa' },
        type_answer:       { en: 'Type your answer here', sw: 'Andika jibu lako hapa' },
        tap_match:         { en: 'Tap the matching pair', sw: 'Gusa jozi linalofanana' },
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

    pluralize(word, count) {
        return count === 1 ? word : word + 's';
    },

    getDistractorObjects(targetObj, count) {
        const allObjects = Object.keys(this.OBJECT_EMOJIS);
        const others = allObjects.filter(function (o) { return o !== targetObj; });
        const picked = this.shuffle(others).slice(0, count);
        return picked.map(function (o) {
            return { obj: o, emoji: ActivityCore.OBJECT_EMOJIS[o] };
        });
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
        var words = this.getLang() === 'sw' ? this.NUMBER_WORDS_SW : this.NUMBER_WORDS;
        var word = words[num];
        this.say(word ?? String(num), then);
    },

    sayEncouragement(then) {
        var pool = this.getLang() === 'sw' ? this.ENCOURAGEMENTS_SW : this.ENCOURAGEMENTS;
        this.say(this.pickRandom(pool), then);
    },

    renderPrompt(text, emoji) {
        const p = document.createElement('p');
        p.className = 'activity-prompt';
        p.textContent = emoji ? emoji + ' ' + text : text;
        /* sync instruction bar with actual engine prompt */
        const bar = document.getElementById('activityInstructionBar');
        if (bar) {
            const txt = bar.querySelector('.instruction-text');
            if (txt) txt.textContent = text;
            const ico = bar.querySelector('.instruction-icon');
            if (ico && emoji) ico.textContent = emoji;
        }
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

    finishActivity() {
        const { display, options } = this.clearStage();
        display.className = 'activity-display activity-stage';
        const finishMsg = this.pickRandom(this.FINISH_MESSAGES);
        const emojiRow = ['⭐', '🌟', '✨', '🏆', '🎉'];
        const starsHtml = '⭐⭐⭐';

        const cfg = window.ACTIVITY_CONFIG || {};
        const categoriesUrl = 'categories?lang=' + (cfg.lang || 'en');
        display.innerHTML = '<div class="finish-screen text-center">' +
            '<div class="finish-trophy">🏆</div>' +
            '<div class="finish-stars">' + starsHtml + '</div>' +
            '<h2 class="finish-title">' + finishMsg + '</h2>' +
            '<div class="finish-emoji-row">' +
            emojiRow.map(function(e) { return '<span>' + e + '</span>'; }).join('') +
            '</div>' +
            '<p class="finish-subtitle">You did a great job!</p>' +
            '<div class="finish-actions">' +
            '<a href="' + categoriesUrl + '" class="btn-child btn-child-primary" style="margin-top:12px;text-decoration:none;"><i class="fas fa-home me-2"></i>Home</a>' +
            '</div></div>';
        options.innerHTML = '';

        const bar = document.getElementById('nextActivityBar');
        if (bar) {
            bar.style.display = 'flex';
            const btn = document.getElementById('nextActivityBtn');
            if (btn) {
                const hasNext = cfg.nextActivityId > 0;
                if (hasNext) {
                    btn.innerHTML = '<i class="fas fa-arrow-right me-2"></i>Next';
                } else {
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i>Done';
                }
                btn.onclick = function () {
                    if (typeof goBack === 'function') goBack();
                };
            }
        }

        this.celebrate();
        this.say(finishMsg);

        /* Auto-advance to next activity after audio plays */
        const hasNext = cfg.nextActivityId > 0;
        if (hasNext) {
            setTimeout(function () {
                if (typeof goBack === 'function') goBack();
            }, 3000);
        }

        if (cfg.activityId && cfg.saveProgressUrl) {
            fetch(cfg.saveProgressUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    activity_id: cfg.activityId,
                    score: 100,
                    completed: 1,
                    stars: 3
                })
            }).catch(function () {});
        }
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
