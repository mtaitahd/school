/**
 * Kona Ya Hisabati — Activity engines (Nursery Edition)
 */
const ActivityEngines = {

    /* ----- Counting with objects (1–10, multi-object, difficulty) ----- */
    mango_counting(config) {
        ActivityCore.hideMultiRoundUI();
        const obj = config.object || 'mango';
        const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🥭';
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const total = config.count || ActivityCore.randomInt(min, max);
        const objectName = obj.charAt(0).toUpperCase() + obj.slice(1);
        let tapped = 0;

        function reset() {
            tapped = 0;
            runIntro();
        }

        function runIntro() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Tap each ' + obj + ' as you count!', emoji));
            const grid = document.createElement('div');
            grid.className = 'object-count-grid';
            grid.setAttribute('role', 'group');

            for (let i = 0; i < total; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'object-count-item';
                btn.setAttribute('aria-label', obj + ' ' + (i + 1));
                btn.innerHTML = '<span class="count-label"></span><span class="count-emoji">' + emoji + '</span>';
                const label = btn.querySelector('.count-label');
                btn.onclick = () => {
                    if (btn.classList.contains('tapped')) return;
                    tapped++;
                    btn.classList.add('tapped');
                    label.textContent = tapped;
                    ActivityCore.sayNumber(tapped);
                    if (tapped >= total) {
                        setTimeout(showAnswerPhase, 800);
                    }
                };
                grid.appendChild(btn);
            }
            display.appendChild(grid);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say("Let's count the " + obj + "s. Tap each one as I say the number.");
            });
            ActivityCore.say("Let's count the " + obj + "s. Tap each one as I say the number.");
        }

        function showAnswerPhase() {
            const options = ActivityCore.getOptions();
            options.innerHTML = '';
            ActivityCore.getDisplay().querySelector('.activity-prompt').textContent =
                'How many ' + obj + 's did you count?';
            const choices = ActivityCore.buildMCOptions(total, Math.max(1, total - 2), total + 2);
            ActivityCore.renderMC(choices, (selected, btn) => {
                if (selected === total) {
                    btn.classList.add('correct');
                    ActivityCore.finishActivity();
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Good try. Let us count together again.', reset);
                }
            });
            ActivityCore.say('Now choose the number we counted.');
        }

        runIntro();
    },

    /* ----- Number identification (nursery 1–9, large buttons, audio) ----- */
    number_identification(config) {
        ActivityCore.hideMultiRoundUI();
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const nurseryMin = Math.max(1, min);
        const nurseryMax = Math.min(Math.max(max, 5), 20);
        const poolSize = config.poolSize || Math.min(6, nurseryMax - nurseryMin + 1);

        function round() {
            const target = ActivityCore.randomInt(nurseryMin, nurseryMax);
            const pool = new Set([target]);
            while (pool.size < poolSize) {
                pool.add(ActivityCore.randomInt(nurseryMin, nurseryMax));
            }
            const numbers = ActivityCore.shuffle([...pool]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Find number ' + target, '🔢'));
            const tiles = document.createElement('div');
            tiles.className = 'number-tiles number-tiles-large';

            numbers.forEach((n) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'number-tile';
                btn.textContent = n;
                btn.onclick = () => {
                    if (n === target) {
                        btn.classList.add('correct');
                        ActivityCore.celebrate();
                        ActivityCore.sayNumber(target, () => {
                            ActivityCore.sayEncouragement(() => setTimeout(round, 1500));
                        });
                    } else {
                        btn.classList.add('incorrect');
                        ActivityCore.say('Oops! That is not ' + target + '. Try again.');
                        setTimeout(() => {
                            const t = [...tiles.children].find((c) => +c.textContent === target);
                            if (t) t.classList.add('hint-flash');
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
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const seqMax = Math.min(max, 10);
        const slots = [];
        const pool = [];

        function init() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Put numbers in order from 1 to ' + seqMax, '🔢'));

            const ws = document.createElement('div');
            ws.className = 'sequence-workspace';

            const slotsRow = document.createElement('div');
            slotsRow.className = 'sequence-slots';
            for (let i = 1; i <= seqMax; i++) {
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
            const nums = ActivityCore.shuffle([...Array(seqMax)].map((_, i) => i + 1));
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
                ActivityCore.say('Let us put the numbers in order. Drag number one to the first box.');
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
                    if (document.querySelectorAll('.sequence-slot.filled').length >= seqMax) {
                        ActivityCore.celebrate();
                        ActivityCore.say('Good job! You arranged the numbers correctly!');
                    } else {
                        ActivityCore.sayNumber(expected);
                    }
                } else {
                    ActivityCore.sayNumber(val, () => {
                        ActivityCore.say('Try a different spot. We need number ' + expected + '.');
                    });
                }
            };
        }

        init();
    },

    /* ----- Missing numbers (basic 1–10, advanced 10–20) ----- */
    missing_numbers(config) {
        ActivityCore.hideMultiRoundUI();
        const { min, max } = ActivityCore.getDifficultyRange(config);

        function round() {
            const range = max - min;
            const startPos = Math.floor(Math.random() * Math.max(1, range - 4)) + min;
            const missing = startPos + 1 + Math.floor(Math.random() * Math.min(3, range - 2));
            const seq = [startPos, startPos + 1, missing + 1, missing + 2];
            const correct = missing;
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';

            display.appendChild(ActivityCore.renderPrompt('What number is missing?', '❓'));
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
                    row.querySelector('.number-line-item.missing').textContent = correct;
                    row.querySelector('.number-line-item.missing').classList.remove('missing');
                    ActivityCore.celebrate();
                    ActivityCore.sayNumber(correct, () => {
                        ActivityCore.sayEncouragement(() => setTimeout(round, 2000));
                    });
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Try again. What comes after ' + seq[1] + '?');
                }
            });

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say('What number comes after ' + seq[1] + ' and before ' + seq[2] + '?');
            });
            ActivityCore.say('What number comes after ' + seq[1] + ' and before ' + seq[2] + '?');
        }
        round();
    },

    /* ----- Match number to quantity (multi-object) ----- */
    match_quantity(config) {
        ActivityCore.hideMultiRoundUI();
        const obj = config.object || 'apple';
        const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const target = config.target || ActivityCore.randomInt(Math.max(2, min), Math.min(max, 10));

        function round() {
            const counts = ActivityCore.shuffle(
                [target, target - 1, target + 1].filter((c) => c > 0 && c <= 10)
            );
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Find the group with ' + target + ' ' + obj + 's', emoji));
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
                    s.textContent = emoji;
                    row.appendChild(s);
                }
                g.appendChild(row);
                g.onclick = () => {
                    if (count === target) {
                        g.classList.add('selected-correct');
                        ActivityCore.celebrate();
                        ActivityCore.sayEncouragement();
                    } else {
                        g.classList.add('selected-wrong');
                        ActivityCore.say('Almost! Let us count together.');
                        setTimeout(() => {
                            g.classList.remove('selected-wrong');
                            [...groups.children].forEach((el) => {
                                if (+el.querySelectorAll('.objects-row span').length === target) {
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
                ActivityCore.say('Can you find the group that has ' + target + ' ' + obj + 's?');
            });
            ActivityCore.say('Can you find the group that has ' + target + ' ' + obj + 's?');
        }
        round();
    },

    /* ----- Identify 2D shapes + sort by size ----- */
    identify_shapes(config) {
        ActivityCore.hideMultiRoundUI();
        const targets = config.shapes || ['circle', 'square', 'triangle', 'rectangle'];
        const sortBySize = config.sort_by_size || false;
        let correctCount = 0;
        const roundCount = sortBySize ? 3 : 2;

        function round() {
            if (sortBySize) return runSortBySize();
            const target = targets[Math.floor(Math.random() * targets.length)];
            const pool = ActivityCore.shuffle([...targets]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Find the ' + target, ActivityCore.SHAPE_ICONS[target]));
            const grid = document.createElement('div');
            grid.className = 'shapes-grid';
            pool.forEach((s) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'shape-btn';
                btn.innerHTML = ActivityCore.SHAPE_ICONS[s] || '⭕';
                btn.onclick = () => {
                    if (s === target) {
                        btn.classList.add('correct');
                        ActivityCore.say('That is a ' + target + '!');
                        correctCount++;
                        if (correctCount >= roundCount) {
                            ActivityCore.celebrate();
                            ActivityCore.say('You know your shapes!');
                        } else setTimeout(round, 1200);
                    } else {
                        btn.classList.add('wrong');
                        ActivityCore.say('That is a ' + s + '. Try again.');
                        setTimeout(() => btn.classList.remove('wrong'), 500);
                    }
                };
                grid.appendChild(btn);
            });
            display.appendChild(grid);
            ActivityCore.say('Can you find the ' + target + '? Tap on the ' + target + '.');
        }

        function runSortBySize() {
            const shapes = ['circle', 'square', 'triangle', 'rectangle'];
            const sizes = ['Small', 'Medium', 'Large'];
            const sizeEmojis = { circle: ['⭕', '🔵', '🟣'], square: ['⬜', '🟨', '🟧'], triangle: ['🔺', '🔻', '🔼'], rectangle: ['▬', '🟦', '🟩'] };

            const shape = ActivityCore.pickRandom(shapes);
            const emojis = ActivityCore.shuffle(sizeEmojis[shape]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Sort the ' + shape + 's by size!', ActivityCore.SHAPE_ICONS[shape]));

            const bins = document.createElement('div');
            bins.className = 'shape-sort-bins';
            sizes.forEach((size) => {
                const bin = document.createElement('div');
                bin.className = 'sort-bin';
                bin.dataset.size = size.toLowerCase();
                bin.innerHTML = '<span class="sort-bin-label">' + size + '</span>';
                bins.appendChild(bin);
            });
            display.appendChild(bins);

            const pool = document.createElement('div');
            pool.className = 'shape-sort-pool';
            emojis.forEach((e, i) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'sort-item';
                item.textContent = e;
                item.dataset.size = sizes[i].toLowerCase();
                item.onclick = () => {
                    if (item.classList.contains('sorted')) return;
                    item.classList.add('sorted');
                    const targetBin = bins.querySelector('[data-size="' + item.dataset.size + '"]');
                    if (targetBin) {
                        const clone = item.cloneNode(true);
                        clone.onclick = null;
                        clone.classList.remove('sorted');
                        targetBin.appendChild(clone);
                        item.style.visibility = 'hidden';
                        ActivityCore.say(item.dataset.size);
                    }
                    if (bins.querySelectorAll('.sort-item').length >= 3) {
                        ActivityCore.celebrate();
                        ActivityCore.say('Great sorting! You sorted by size!');
                    }
                };
                pool.appendChild(item);
            });
            display.appendChild(pool);

            ActivityCore.say('Sort each ' + shape + ' by its size. Small, Medium, or Large.');
        }

        round();
    },

    /* ----- Complete pattern ----- */
    complete_pattern(config) {
        ActivityCore.hideMultiRoundUI();
        const patterns = [
            { seq: ['⭕', '⬜', '⭕', '⬜', null], answer: '⭕', label: 'circle' },
            { seq: ['🔺', '🔺', '⬜', '🔺', '🔺', null], answer: '⬜', label: 'square' },
            { seq: ['⭐', '🔺', '⭐', '🔺', null], answer: '⭐', label: 'star' },
            { seq: ['❤️', '⭕', '❤️', '⭕', null], answer: '❤️', label: 'heart' }
        ];
        const { seq: p, answer, label } = patterns[Math.floor(Math.random() * patterns.length)];

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        display.appendChild(ActivityCore.renderPrompt('What comes next in the pattern?', '🧩'));

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

        const opts = ActivityCore.shuffle(['⭕', '⬜', '🔺', '⭐', '❤️']);
        ActivityCore.renderMC(opts, (sel, btn) => {
            if (sel === answer) {
                btn.classList.add('correct');
                row.querySelector('.pattern-slot').textContent = answer;
                ActivityCore.celebrate();
                ActivityCore.say('Correct! ' + label + ' comes next!');
            } else {
                btn.classList.add('incorrect');
                ActivityCore.say('Try again. Look at the pattern carefully.');
            }
        });
        ActivityCore.say('Look at the pattern. What comes next?');
    },

    /* ----- Drag-and-drop addition (1–10) ----- */
    drag_addition(config) {
        ActivityCore.hideMultiRoundUI();
        const obj = config.object || 'apple';
        const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const a = config.a ?? ActivityCore.randomInt(min, Math.min(max, 5));
        const b = config.b ?? ActivityCore.randomInt(min, Math.min(max, 5));
        const total = a + b;
        let inBasket = 0;

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        options.innerHTML = '';

        display.appendChild(ActivityCore.renderPrompt('Move all ' + obj + 's into the basket', emoji));
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
        basket.innerHTML = '<span class="addition-basket-label">' + emoji + ' Basket</span>';

        function makeItems(container, n) {
            for (let i = 0; i < n; i++) {
                const f = document.createElement('span');
                f.className = 'draggable-fruit';
                f.textContent = emoji;
                f.style.cursor = 'pointer';
                f.onclick = () => {
                    if (f.classList.contains('in-basket')) return;
                    f.classList.add('in-basket');
                    f.style.transform = 'scale(0)';
                    setTimeout(() => {
                        basket.appendChild(f);
                        f.style.transform = 'scale(1)';
                    }, 200);
                    inBasket++;
                    ActivityCore.sayNumber(inBasket);
                    if (inBasket >= total) showAnswer();
                };
                container.appendChild(f);
            }
        }
        makeItems(left, a);
        makeItems(right, b);

        layout.appendChild(left);
        layout.appendChild(document.createTextNode('+'));
        layout.appendChild(right);
        layout.appendChild(basket);
        display.appendChild(layout);

        function showAnswer() {
            display.querySelector('.activity-prompt').textContent = 'How many ' + obj + 's are in the basket?';
            const choices = ActivityCore.buildMCOptions(total, 1, total + 3);
            ActivityCore.renderMC(choices, (sel, btn) => {
                if (sel === total) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.sayNumber(total, () => ActivityCore.sayEncouragement());
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Count again. How many ' + obj + 's?');
                }
            });
            ActivityCore.say('How many ' + obj + 's are in the basket?');
        }

        ActivityCore.say('Let us add the ' + obj + 's. Move them into the basket!');
    },

    /* ----- Visual subtraction (objects disappear, count remaining) ----- */
    visual_subtraction(config) {
        ActivityCore.hideMultiRoundUI();
        const obj = config.object || 'apple';
        const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const start = config.start ?? ActivityCore.randomInt(Math.max(3, min + 1), Math.min(max, 10));
        const remove = config.remove ?? ActivityCore.randomInt(1, start - 1);
        const answer = start - remove;
        let removed = 0;

        const { display, options } = ActivityCore.clearStage();
        display.className = 'activity-display activity-stage';
        options.innerHTML = '';

        display.appendChild(ActivityCore.renderPrompt('Tap ' + remove + ' ' + obj + 's to take away', emoji));
        const eq = document.createElement('div');
        eq.className = 'addition-eq';
        eq.textContent = start + ' - ' + remove + ' = ?';
        display.appendChild(eq);

        const grid = document.createElement('div');
        grid.className = 'subtraction-grid';

        for (let i = 0; i < start; i++) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'subtraction-item';
            item.textContent = emoji;
            item.onclick = () => {
                if (item.classList.contains('removed') || removed >= remove) return;
                removed++;
                item.classList.add('removed');
                item.textContent = '💨';
                ActivityCore.sayNumber(removed);
                if (removed >= remove) {
                    setTimeout(showAnswer, 800);
                }
            };
            grid.appendChild(item);
        }
        display.appendChild(grid);

        function showAnswer() {
            const remaining = start - remove;
            display.querySelector('.activity-prompt').textContent =
                'How many ' + obj + 's are left?';
            const choices = ActivityCore.buildMCOptions(answer, 0, start);
            ActivityCore.renderMC(choices, (sel, btn) => {
                if (sel === answer) {
                    btn.classList.add('correct');
                    grid.querySelectorAll('.subtraction-item:not(.removed)').forEach((el) => {
                        el.classList.add('highlight-remaining');
                    });
                    ActivityCore.celebrate();
                    ActivityCore.sayNumber(answer, () => ActivityCore.sayEncouragement());
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Count what is left. Try again.');
                }
            });
            ActivityCore.say('How many ' + obj + 's are left?');
        }

        ActivityCore.bindTopbarAudio(() => {
            ActivityCore.say('We have ' + start + ' ' + obj + 's. Tap ' + remove + ' to take them away.');
        });
        ActivityCore.say('We have ' + start + ' ' + obj + 's. Tap ' + remove + ' to take them away.');
    },

    /* ----- Object recognition (identify object by name/emoji) ----- */
    object_recognition(config) {
        ActivityCore.hideMultiRoundUI();
        const objects = config.objects || ['apple', 'ball', 'car', 'cat', 'dog', 'fish', 'star', 'tree', 'flower', 'cake'];

        function round() {
            const target = ActivityCore.pickRandom(objects);
            const emoji = ActivityCore.OBJECT_EMOJIS[target] || '❓';
            const pool = ActivityCore.shuffle([target, ...ActivityCore.shuffle(objects).slice(0, 3)].filter((v, i, a) => a.indexOf(v) === i));

            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            const emojiDisplay = document.createElement('div');
            emojiDisplay.className = 'recognition-emoji';
            emojiDisplay.textContent = emoji;
            emojiDisplay.style.fontSize = '6rem';
            display.appendChild(emojiDisplay);

            display.appendChild(ActivityCore.renderPrompt('What is this?', emoji));

            const choices = document.createElement('div');
            choices.className = 'recognition-choices';
            pool.forEach((name) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'recognition-btn';
                btn.innerHTML = ActivityCore.OBJECT_EMOJIS[name]
                    ? `<span class="recog-emoji">${ActivityCore.OBJECT_EMOJIS[name]}</span><span class="recog-name">${name}</span>`
                    : name;
                btn.onclick = () => {
                    if (name === target) {
                        btn.classList.add('correct');
                        ActivityCore.celebrate();
                        ActivityCore.say('That is a ' + target + '!', () => setTimeout(round, 2000));
                    } else {
                        btn.classList.add('incorrect');
                        ActivityCore.say('That is a ' + name + '. Try again.');
                        setTimeout(() => btn.classList.remove('incorrect'), 500);
                    }
                };
                choices.appendChild(btn);
            });
            display.appendChild(choices);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say('What is this? Tap the correct name.');
            });
            ActivityCore.say('What is this? Tap the correct name.');
        }
        round();
    },

    /* ----- Math Game with Level 1 (1-9) and Level 2 (10-20) ----- */
    math_game(config) {
        ActivityCore.hideMultiRoundUI();
        const ROUNDS_PER_LEVEL = 5;
        const LEVELS = [
            { name: 'Level 1', min: 1, max: 9, emoji: '🌟' },
            { name: 'Level 2', min: 10, max: 20, emoji: '⭐' }
        ];
        let currentLevel = 0;
        let currentRound = 0;
        let correctCount = 0;

        function showLevelSelect() {
            currentLevel = 0;
            currentRound = 0;
            correctCount = 0;
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            const title = document.createElement('h2');
            title.className = 'math-game-title';
            title.textContent = '🎮 Math Game';
            display.appendChild(title);

            const subtitle = document.createElement('p');
            subtitle.className = 'math-game-subtitle';
            subtitle.textContent = 'Choose your level!';
            display.appendChild(subtitle);

            const levelsDiv = document.createElement('div');
            levelsDiv.className = 'math-game-levels';
            LEVELS.forEach((level, idx) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'math-game-level-btn';
                btn.innerHTML = '<span class="level-emoji">' + level.emoji + '</span>' +
                    '<span class="level-name">' + level.name + '</span>' +
                    '<span class="level-range">Numbers ' + level.min + ' to ' + level.max + '</span>';
                btn.onclick = function () {
                    currentLevel = idx;
                    currentRound = 0;
                    correctCount = 0;
                    startRound();
                };
                levelsDiv.appendChild(btn);
            });
            display.appendChild(levelsDiv);

            ActivityCore.bindTopbarAudio(function () {
                ActivityCore.say('Welcome to Math Game! Choose a level to start.');
            });
            ActivityCore.say('Welcome to Math Game! Choose a level to start.');
        }

        function startRound() {
            const level = LEVELS[currentLevel];
            const type = ActivityCore.pickRandom(['count', 'add', 'subtract']);
            if (type === 'count') startCountingRound(level);
            else if (type === 'add') startAdditionRound(level);
            else startSubtractionRound(level);
        }

        function startCountingRound(level) {
            const obj = ActivityCore.pickRandom(['apple', 'star', 'ball', 'car', 'fish', 'duck', 'flower', 'candy']);
            const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
            const total = ActivityCore.randomInt(level.min, Math.min(level.max, 12));
            let tapped = 0;

            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            showRoundHeader(display, 'Count the ' + obj + 's!');

            const grid = document.createElement('div');
            grid.className = 'object-count-grid';
            for (let i = 0; i < total; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'object-count-item';
                btn.innerHTML = '<span class="count-label"></span><span class="count-emoji">' + emoji + '</span>';
                const label = btn.querySelector('.count-label');
                btn.onclick = function () {
                    if (btn.classList.contains('tapped')) return;
                    tapped++;
                    btn.classList.add('tapped');
                    label.textContent = tapped;
                    ActivityCore.sayNumber(tapped);
                    if (tapped >= total) {
                        setTimeout(function () { showAnswer(total, level); }, 800);
                    }
                };
                grid.appendChild(btn);
            }
            display.appendChild(grid);

            ActivityCore.say('Count the ' + obj + 's. Tap each one.');
        }

        function startAdditionRound(level) {
            const obj = ActivityCore.pickRandom(['apple', 'star', 'ball', 'car', 'fish', 'duck', 'flower', 'candy']);
            const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
            const maxA = Math.min(level.max, 10);
            const a = ActivityCore.randomInt(Math.max(1, level.min), maxA);
            const b = ActivityCore.randomInt(1, Math.min(level.max - a, 10));
            const total = a + b;
            let inBasket = 0;

            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            showRoundHeader(display, 'Add the ' + obj + 's!');
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
            basket.innerHTML = '<span class="addition-basket-label">' + emoji + ' Basket</span>';

            function makeItems(container, n) {
                for (let i = 0; i < n; i++) {
                    const f = document.createElement('span');
                    f.className = 'draggable-fruit';
                    f.textContent = emoji;
                    f.style.cursor = 'pointer';
                    f.onclick = function () {
                        if (f.classList.contains('in-basket')) return;
                        f.classList.add('in-basket');
                        f.style.transform = 'scale(0)';
                        setTimeout(function () {
                            basket.appendChild(f);
                            f.style.transform = 'scale(1)';
                        }, 200);
                        inBasket++;
                        ActivityCore.sayNumber(inBasket);
                        if (inBasket >= total) {
                            setTimeout(function () { showAnswer(total, level); }, 800);
                        }
                    };
                    container.appendChild(f);
                }
            }
            makeItems(left, a);
            makeItems(right, b);

            layout.appendChild(left);
            layout.appendChild(document.createTextNode('+'));
            layout.appendChild(right);
            layout.appendChild(basket);
            display.appendChild(layout);

            ActivityCore.say('Add the ' + obj + 's. Move them into the basket.');
        }

        function startSubtractionRound(level) {
            const obj = ActivityCore.pickRandom(['apple', 'star', 'ball', 'car', 'fish', 'duck', 'flower', 'candy']);
            const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🍎';
            const start = ActivityCore.randomInt(Math.max(3, level.min + 1), Math.min(level.max, 15));
            const remove = ActivityCore.randomInt(1, start - 1);
            const answer = start - remove;
            let removed = 0;

            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            showRoundHeader(display, 'Take away ' + obj + 's!');
            const eq = document.createElement('div');
            eq.className = 'addition-eq';
            eq.textContent = start + ' - ' + remove + ' = ?';
            display.appendChild(eq);

            const grid = document.createElement('div');
            grid.className = 'subtraction-grid';
            for (let i = 0; i < start; i++) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'subtraction-item';
                item.textContent = emoji;
                item.onclick = function () {
                    if (item.classList.contains('removed') || removed >= remove) return;
                    removed++;
                    item.classList.add('removed');
                    item.textContent = '💨';
                    ActivityCore.sayNumber(removed);
                    if (removed >= remove) {
                        setTimeout(function () { showAnswer(answer, level); }, 800);
                    }
                };
                grid.appendChild(item);
            }
            display.appendChild(grid);

            ActivityCore.say('Take away ' + remove + ' ' + obj + 's. Tap them.');
        }

        function showRoundHeader(display, text) {
            const header = document.createElement('div');
            header.className = 'math-game-round-header';
            header.innerHTML = '<span class="round-badge">' + LEVELS[currentLevel].emoji + ' ' + LEVELS[currentLevel].name +
                '</span><span class="round-progress">Round ' + (currentRound + 1) + ' of ' + ROUNDS_PER_LEVEL + '</span>';
            display.appendChild(header);
            display.appendChild(ActivityCore.renderPrompt(text));
        }

        function showAnswer(correct, level) {
            const options = ActivityCore.getOptions();
            options.innerHTML = '';
            const promptEl = ActivityCore.getDisplay().querySelector('.activity-prompt');
            if (promptEl) promptEl.textContent = 'What is the answer?';

            const poolMin = Math.max(0, correct - 3);
            const poolMax = correct + 3;
            const choices = ActivityCore.buildMCOptions(correct, poolMin, poolMax);
            ActivityCore.renderMC(choices, function (selected, btn) {
                if (selected === correct) {
                    btn.classList.add('correct');
                    correctCount++;
                    ActivityCore.say('Correct!');
                    setTimeout(nextRound, 1200);
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Try again.');
                }
            });
            ActivityCore.say('Choose the correct answer.');
        }

        function nextRound() {
            currentRound++;
            if (currentRound >= ROUNDS_PER_LEVEL) {
                showLevelComplete();
            } else {
                startRound();
            }
        }

        function showLevelComplete() {
            const level = LEVELS[currentLevel];
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.innerHTML = '<div class="finish-screen text-center">' +
                '<div class="finish-trophy">' + (correctCount >= ROUNDS_PER_LEVEL ? '🏆' : '🎉') + '</div>' +
                '<h2 class="finish-title">' + level.name + ' Complete!</h2>' +
                '<p class="finish-subtitle">You got ' + correctCount + ' out of ' + ROUNDS_PER_LEVEL + ' correct!</p></div>';

            ActivityCore.celebrate();
            ActivityCore.say('Great job! You completed ' + level.name + '!');

            if (currentLevel < LEVELS.length - 1) {
                setTimeout(function () {
                    currentLevel++;
                    currentRound = 0;
                    correctCount = 0;
                    startRound();
                }, 3000);
            } else {
                setTimeout(showLevelSelect, 3500);
            }
        }

        showLevelSelect();
    },

    /* ----- Legacy simple counting ----- */
    counting(config) {
        if (config.object === 'mango' || config.engine === 'mango_counting') {
            return ActivityEngines.mango_counting(config);
        }
        window._legacyCounting?.(config);
    }
};
