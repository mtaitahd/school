/**
 * Kona Ya Hisabati — Activity engines
 */
const ActivityEngines = {

    /* ----- Mango Counting 1–10 ----- */
    mango_counting(config) {
        ActivityCore.hideMultiRoundUI();
        const total = config.count || 10;
        const correct = total;
        let tapped = 0;
        const words = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'];

        function reset() {
            tapped = 0;
            runIntro();
        }

        function runIntro() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Tap each mango as you count!'));
            const grid = document.createElement('div');
            grid.className = 'mango-grid';
            grid.setAttribute('role', 'group');

            for (let i = 0; i < total; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mango-item';
                btn.setAttribute('aria-label', 'Mango ' + (i + 1));
                btn.innerHTML = '<span class="count-label"></span><span class="mango-emoji">🥭</span>';
                const label = btn.querySelector('.count-label');
                btn.onclick = () => {
                    if (btn.classList.contains('tapped')) return;
                    tapped++;
                    btn.classList.add('tapped');
                    label.textContent = tapped;
                    ActivityCore.say(words[tapped] || String(tapped));
                    if (tapped >= total) {
                        setTimeout(showAnswerPhase, 800);
                    }
                };
                grid.appendChild(btn);
            }
            display.appendChild(grid);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say("Let's count the mangoes. Tap each one as I say the number.");
            });
            ActivityCore.say("Let's count the mangoes. Tap each one as I say the number.");
        }

        function showAnswerPhase() {
            const options = ActivityCore.getOptions();
            options.innerHTML = '';
            ActivityCore.getDisplay().querySelector('.activity-prompt').textContent =
                'How many mangoes did you count?';
            const choices = ActivityCore.buildMCOptions(correct, Math.max(1, correct - 2), correct + 2);
            ActivityCore.renderMC(choices, (selected, btn) => {
                if (selected === correct) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.say('Good job! You counted correctly!', () => {
                        ActivityCore.showMiniGame(() => {});
                    });
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Good try. Let us count together again.', reset);
                }
            });
            ActivityCore.say('Now choose the number we counted.');
        }

        runIntro();
    },

    /* ----- Number identification 0–20 ----- */
    number_identification(config) {
        ActivityCore.hideMultiRoundUI();
        const min = config.min ?? 0;
        const max = config.max ?? 20;
        const poolSize = config.poolSize || 6;

        function round() {
            const target = Math.floor(Math.random() * (max - min + 1)) + min;
            const pool = new Set([target]);
            while (pool.size < poolSize) {
                pool.add(Math.floor(Math.random() * (max - min + 1)) + min);
            }
            const numbers = ActivityCore.shuffle([...pool]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Find number ' + target));
            const tiles = document.createElement('div');
            tiles.className = 'number-tiles';

            numbers.forEach((n) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'number-tile';
                btn.textContent = n;
                btn.onclick = () => {
                    if (n === target) {
                        btn.classList.add('correct');
                        ActivityCore.celebrate();
                        ActivityCore.say('Well done! That is number ' + target + '!', () => setTimeout(round, 1500));
                    } else {
                        btn.classList.add('incorrect');
                        ActivityCore.say('Oops! That is not number ' + target + '. Let us try again.');
                        setTimeout(() => {
                            numbers.forEach((num) => {
                                const t = [...tiles.children].find((c) => +c.textContent === num);
                                if (t && num === target) t.classList.add('hint-flash');
                            });
                        }, 400);
                        setTimeout(() => btn.classList.remove('incorrect'), 600);
                    }
                };
                tiles.appendChild(btn);
            });
            display.appendChild(tiles);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say('Can you find number ' + target + '? Tap number ' + target + '.');
            });
            ActivityCore.say('Can you find number ' + target + '? Tap number ' + target + '.');
        }
        round();
    },

    /* ----- Number sequencing 1–10 ----- */
    number_sequencing(config) {
        ActivityCore.hideMultiRoundUI();
        const max = config.max || 10;
        const slots = [];
        const pool = [];

        function init() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Drag numbers into order from 1 to ' + max));

            const ws = document.createElement('div');
            ws.className = 'sequence-workspace';

            const slotsRow = document.createElement('div');
            slotsRow.className = 'sequence-slots';
            for (let i = 1; i <= max; i++) {
                const slot = document.createElement('div');
                slot.className = 'sequence-slot';
                slot.dataset.index = i;
                slot.textContent = i;
                slots.push(slot);
                bindSlot(slot);
                slotsRow.appendChild(slot);
            }

            const poolRow = document.createElement('div');
            poolRow.className = 'sequence-pool';
            const nums = ActivityCore.shuffle([...Array(max)].map((_, i) => i + 1));
            nums.forEach((n) => {
                const tile = document.createElement('div');
                tile.className = 'draggable-tile';
                tile.textContent = n;
                tile.dataset.value = n;
                setupDrag(tile);
                pool.push(tile);
                poolRow.appendChild(tile);
            });

            ws.appendChild(slotsRow);
            ws.appendChild(poolRow);
            display.appendChild(ws);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say('Let us put the numbers in order. What comes first? Drag number one to the first box.');
            });
            ActivityCore.say('Let us put the numbers in order. Drag number one to the first box.');
        }

        let selectedTile = null;

        function setupDrag(tile) {
            tile.onclick = () => {
                document.querySelectorAll('.draggable-tile').forEach((t) => { t.style.outline = ''; });
                selectedTile = tile;
                tile.style.outline = '4px solid var(--primary-orange)';
            };
        }

        function bindSlot(slot) {
            slot.onclick = () => {
                if (!selectedTile || slot.classList.contains('filled')) return;
                const expected = +slot.dataset.index;
                const val = +selectedTile.dataset.value;
                if (val === expected) {
                    slot.classList.add('filled');
                    slot.textContent = '';
                    slot.appendChild(selectedTile);
                    selectedTile.style.outline = '';
                    selectedTile.onclick = null;
                    selectedTile = null;
                    if (document.querySelectorAll('.sequence-slot.filled').length >= max) {
                        ActivityCore.celebrate();
                        ActivityCore.say('Good job! You have arranged the numbers correctly!');
                    }
                } else {
                    ActivityCore.say('Let us look again, something is out of order.');
                }
            };
        }

        init();
    },

    /* ----- Missing numbers on a line ----- */
    missing_numbers(config) {
        ActivityCore.hideMultiRoundUI();
        const min = config.min ?? 0;
        const max = config.max ?? 20;

        function round() {
            const start = Math.floor(Math.random() * (max - min - 4)) + min;
            const missing = start + 1 + Math.floor(Math.random() * 3);
            const seq = [start, start + 1, missing + 1, missing + 2];
            const correct = missing;
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';

            display.appendChild(ActivityCore.renderPrompt('What number is missing?'));
            const row = document.createElement('div');
            row.className = 'number-line-row';
            [seq[0], seq[1], null, seq[2], seq[3]].forEach((n, i) => {
                const span = document.createElement('span');
                span.className = 'number-line-item' + (n === null ? ' missing' : '');
                span.textContent = n === null ? '?' : n;
                row.appendChild(span);
            });
            display.appendChild(row);

            const choices = ActivityCore.buildMCOptions(correct, min, max);
            ActivityCore.renderMC(choices, (sel, btn) => {
                if (sel === correct) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.say('Well done! That is the correct number.', () => setTimeout(round, 2000));
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Try again. Think about what comes after ' + seq[1] + '.');
                }
            });

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say('What number comes after ' + seq[1] + ' and before ' + seq[2] + '?');
            });
            ActivityCore.say('What number comes after ' + seq[1] + ' and before ' + seq[2] + '?');
        }
        round();
    },

    /* ----- Match number to quantity ----- */
    match_quantity(config) {
        ActivityCore.hideMultiRoundUI();
        const target = config.target || Math.floor(Math.random() * 8) + 2;
        const counts = ActivityCore.shuffle([target, target - 1, target + 1].filter((c) => c > 0 && c <= 10));

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        options.innerHTML = '';

        display.appendChild(ActivityCore.renderPrompt('Find the group with ' + target + ' apples'));
        const badge = document.createElement('div');
        badge.className = 'target-number-badge';
        badge.textContent = target;
        display.appendChild(badge);

        const groups = document.createElement('div');
        groups.className = 'quantity-groups';

        counts.forEach((count) => {
            const g = document.createElement('button');
            g.type = 'button';
            g.className = 'quantity-group';
            const row = document.createElement('div');
            row.className = 'objects-row';
            for (let i = 0; i < count; i++) {
                const s = document.createElement('span');
                s.textContent = '🍎';
                row.appendChild(s);
            }
            g.appendChild(row);
            g.onclick = () => {
                if (count === target) {
                    g.classList.add('selected-correct');
                    ActivityCore.celebrate();
                    ActivityCore.say('Great job! You matched it correctly!');
                } else {
                    g.classList.add('selected-wrong');
                    ActivityCore.say('Almost! Let us count together and try again.');
                    setTimeout(() => {
                        g.classList.remove('selected-wrong');
                        [...groups.children].forEach((el) => {
                            if (+el.querySelectorAll('span').length === target) {
                                el.classList.add('selected-correct');
                            }
                        });
                    }, 1200);
                }
            };
            groups.appendChild(g);
        });
        display.appendChild(groups);

        ActivityCore.bindTopbarAudio(() => {
            ActivityCore.say('Can you find the group that has ' + target + ' apples? Tap the correct group.');
        });
        ActivityCore.say('Can you find the group that has ' + target + ' apples? Tap the correct group.');
    },

    /* ----- Identify 2D shapes ----- */
    identify_shapes(config) {
        ActivityCore.hideMultiRoundUI();
        const targets = config.shapes || ['circle', 'square', 'triangle', 'rectangle'];
        const icons = { circle: '⭕', square: '⬜', triangle: '🔺', rectangle: '▬' };
        let correctCount = 0;

        function round() {
            const target = targets[Math.floor(Math.random() * targets.length)];
            const pool = ActivityCore.shuffle([...targets]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '<p class="text-center activity-prompt">Tap the ' + target + '</p>';

            display.appendChild(ActivityCore.renderPrompt('Find the ' + target));
            const grid = document.createElement('div');
            grid.className = 'shapes-grid';
            pool.forEach((s) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'shape-btn';
                btn.innerHTML = icons[s] || '⭕';
                btn.onclick = () => {
                    if (s === target) {
                        btn.classList.add('correct');
                        ActivityCore.say('Well done!');
                        correctCount++;
                        if (correctCount >= 2) {
                            ActivityCore.celebrate();
                            ActivityCore.say('You know your shapes! Shapes song time!');
                        } else setTimeout(round, 1200);
                    } else {
                        btn.classList.add('wrong');
                        ActivityCore.say('Try again.');
                        setTimeout(() => btn.classList.remove('wrong'), 500);
                    }
                };
                grid.appendChild(btn);
            });
            display.appendChild(grid);
            ActivityCore.say('Can you find the ' + target + '? Tap on the shape that looks like a ' + target + '.');
        }
        round();
    },

    /* ----- Complete pattern ----- */
    complete_pattern(config) {
        ActivityCore.hideMultiRoundUI();
        const patterns = [
            { seq: ['⭕', '⬜', '⭕', '⬜', null], answer: '⭕' },
            { seq: ['🔺', '🔺', '⬜', '🔺', '🔺', null], answer: '⬜' }
        ];
        const { seq: p, answer } = patterns[Math.floor(Math.random() * patterns.length)];
        const choices = ActivityCore.shuffle(['⭕', '⬜', '🔺']);

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        display.appendChild(ActivityCore.renderPrompt('What comes next in the pattern?'));

        const row = document.createElement('div');
        row.className = 'pattern-row';
        p.forEach((item) => {
            const cell = document.createElement('span');
            if (item === null) {
                cell.className = 'pattern-slot';
                cell.textContent = '?';
            } else {
                cell.textContent = item;
                cell.style.fontSize = '3rem';
            }
            row.appendChild(cell);
        });
        display.appendChild(row);

        const opts = ActivityCore.shuffle(['⭕', '⬜', '🔺']);
        ActivityCore.renderMC(opts, (sel, btn) => {
            if (sel === answer) {
                btn.classList.add('correct');
                row.querySelector('.pattern-slot').textContent = answer;
                ActivityCore.celebrate();
                ActivityCore.say('Correct!');
            } else {
                btn.classList.add('incorrect');
                ActivityCore.say('This is ' + sel + '. Try again.');
            }
        });
        ActivityCore.say('Look at the pattern. What comes next?');
    },

    /* ----- Drag-and-drop addition ----- */
    drag_addition(config) {
        ActivityCore.hideMultiRoundUI();
        const a = config.a ?? Math.floor(Math.random() * 4) + 1;
        const b = config.b ?? Math.floor(Math.random() * 4) + 1;
        const total = a + b;
        let inBasket = 0;

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        options.innerHTML = '';

        display.appendChild(ActivityCore.renderPrompt('Drag all apples into the basket'));
        const eq = document.createElement('div');
        eq.className = 'addition-eq';
        eq.textContent = a + ' + ' + b + ' = ?';
        display.appendChild(eq);

        const layout = document.createElement('div');
        layout.className = 'addition-layout';

        const left = document.createElement('div');
        left.className = 'addition-group';
        const right = document.createElement('div');
        right.className = 'addition-group';
        const basket = document.createElement('div');
        basket.className = 'addition-basket';
        basket.innerHTML = '<span class="addition-basket-label">Basket — drop apples here</span>';

        function makeApples(container, n) {
            for (let i = 0; i < n; i++) {
                const f = document.createElement('span');
                f.className = 'draggable-fruit';
                f.textContent = '🍎';
                f.style.cursor = 'pointer';
                f.onclick = () => {
                    if (f.classList.contains('in-basket')) return;
                    f.classList.add('in-basket');
                    basket.appendChild(f);
                    inBasket++;
                    ActivityCore.say(String(inBasket));
                    if (inBasket >= total) showAnswer();
                };
                container.appendChild(f);
            }
        }
        makeApples(left, a);
        makeApples(right, b);

        layout.appendChild(left);
        layout.appendChild(document.createTextNode('+'));
        layout.appendChild(right);
        layout.appendChild(basket);
        display.appendChild(layout);

        function showAnswer() {
            display.querySelector('.activity-prompt').textContent = 'How many apples are in the basket?';
            const choices = ActivityCore.buildMCOptions(total, 1, total + 3);
            ActivityCore.renderMC(choices, (sel, btn) => {
                if (sel === total) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.say('Great job!');
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Let us try again.');
                }
            });
            ActivityCore.say('How many apples are in the basket now? Tap the correct number.');
        }

        ActivityCore.say('Let us add the apples. Drag all the apples into the basket to find the total.');
    },

    /* ----- Legacy simple counting ----- */
    counting(config) {
        if (config.object === 'mango' || config.engine === 'mango_counting') {
            return ActivityEngines.mango_counting(config);
        }
        window._legacyCounting?.(config);
    }
};
