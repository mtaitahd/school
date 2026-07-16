/**
 * Kona Ya Hisabati — Activity engines (Nursery Edition)
 */
const ActivityEngines = {

    /* ----- Counting with objects (1–10, multi-object, difficulty) ----- */
    mango_counting(config) {
        ActivityCore.hideMultiRoundUI();
        const isPattern = Array.isArray(config.pattern_objects) && config.pattern_objects.length > 0;

        if (isPattern) {
            return ActivityEngines.pattern_counting(config);
        }

        const obj = config.object || 'mango';
        const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '🥭';
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const total = config.count || ActivityCore.randomInt(min, max);
        const useMixed = config.mixed_objects === true || config.mixed_objects === 'true';
        const objectName = obj.charAt(0).toUpperCase() + obj.slice(1);
        const prompt = useMixed
            ? ('Count only the ' + ActivityCore.pluralize(obj, 2) + '! Tap each ' + obj + '!')
            : (config.instruction || ('Tap each ' + obj + ' as you count!'));
        let tapped = 0;

        function reset() {
            tapped = 0;
            runIntro();
        }

        function runIntro() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt(prompt, emoji));
            const grid = document.createElement('div');
            grid.className = 'object-count-grid';
            grid.setAttribute('role', 'group');

            /* Build items array — targets + optional distractors */
            var items = [];
            for (var t = 0; t < total; t++) {
                items.push({ emoji: emoji, obj: obj, isTarget: true });
            }
            if (useMixed) {
                var distractorCount = Math.min(5, Math.max(2, Math.ceil(total * 0.5)));
                var distractors = ActivityCore.getDistractorObjects(obj, distractorCount);
                for (var d = 0; d < distractors.length; d++) {
                    items.push({ emoji: distractors[d].emoji, obj: distractors[d].obj, isTarget: false });
                }
                items = ActivityCore.shuffle(items);
            }

            for (var i = 0; i < items.length; i++) {
                (function (idx) {
                    var item = items[idx];
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'object-count-item';
                    btn.setAttribute('aria-label', item.obj + ' ' + (idx + 1));
                    btn.innerHTML = '<span class="count-label"></span><span class="count-emoji">' + item.emoji + '</span>';
                    var label = btn.querySelector('.count-label');
                    btn.onclick = function () {
                        if (btn.classList.contains('tapped')) return;
                        if (!item.isTarget) {
                            btn.classList.add('wrong-tap');
                            ActivityCore.say("That is not a " + obj + "! Count only " + ActivityCore.pluralize(obj, 2) + ".");
                            setTimeout(function () { btn.classList.remove('wrong-tap'); }, 600);
                            return;
                        }
                        tapped++;
                        btn.classList.add('tapped');
                        label.textContent = tapped;
                        ActivityCore.sayNumber(tapped);
                        if (tapped >= total) {
                            setTimeout(showAnswerPhase, 800);
                        }
                    };
                    grid.appendChild(btn);
                })(i);
            }
            display.appendChild(grid);

            var audioMsg = useMixed
                ? "Count only the " + ActivityCore.pluralize(obj, 2) + ". Tap each one as I say the number."
                : "Let's count the " + ActivityCore.pluralize(obj, 2) + ". Tap each one as I say the number.";
            ActivityCore.bindTopbarAudio(function () {
                ActivityCore.say(audioMsg);
            });
            ActivityCore.say(audioMsg);
        }

        function showAnswerPhase() {
            const options = ActivityCore.getOptions();
            options.innerHTML = '';
            ActivityCore.getDisplay().querySelector('.activity-prompt').textContent =
                'How many ' + ActivityCore.pluralize(obj, 2) + ' did you count?';
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

    /* ----- Pattern Counting (mixed objects, 1→N rows) ----- */
    /*  Used when config.pattern_objects is set, e.g.:
     *  ['fly','butterfly','bird','mosquito','bee']
     *  Shows rows: 1 fly, 2 butterflies, 3 birds... child counts each row. */
    pattern_counting(config) {
        ActivityCore.hideMultiRoundUI();
        const objects = config.pattern_objects;
        const totalRows = objects.length;
        let currentRow = 0;

        function showRow() {
            if (currentRow >= totalRows) {
                showFinalAnswer();
                return;
            }
            const count = currentRow + 1;
            const obj = objects[currentRow];
            const emoji = ActivityCore.OBJECT_EMOJIS[obj] || '❓';
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Count the ' + ActivityCore.pluralize(obj, count) + '!', emoji));
            const grid = document.createElement('div');
            grid.className = 'object-count-grid';
            let tapped = 0;

            for (let i = 0; i < count; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'object-count-item';
                btn.innerHTML = '<span class="count-label"></span><span class="count-emoji">' + emoji + '</span>';
                const label = btn.querySelector('.count-label');
                btn.onclick = () => {
                    if (btn.classList.contains('tapped')) return;
                    tapped++;
                    btn.classList.add('tapped');
                    label.textContent = tapped;
                    ActivityCore.sayNumber(tapped);
                    if (tapped >= count) {
                        ActivityCore.sayEncouragement(() => {
                            currentRow++;
                            setTimeout(showRow, 1200);
                        });
                    }
                };
                grid.appendChild(btn);
            }
            display.appendChild(grid);
            ActivityCore.say('Row ' + count + ': Count the ' + ActivityCore.pluralize(obj, count) + '!');
        }

        function showFinalAnswer() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt(config.instruction || 'How many rows did you count?', '🔢'));
            const total = totalRows;
            const choices = ActivityCore.buildMCOptions(total, Math.max(1, total - 2), total + 2);
            ActivityCore.renderMC(choices, (selected, btn) => {
                if (selected === total) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.sayNumber(total, () => {
                        ActivityCore.sayEncouragement(() => {
                            setTimeout(() => ActivityCore.finishActivity(), 1500);
                        });
                    });
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Try again. Count the rows!');
                }
            });
            ActivityCore.say('You counted ' + total + ' rows! Choose the number.');
        }

        showRow();
    },

    /* ----- Number identification (nursery 1–9, large buttons, audio) ----- */
    /*  Modes:
     *  - default: number tiles (find the number)
     *  - shape:   object emojis (find the object shaped like the number)
     *  - coloring/trace: canvas outline + marker brush to trace the number
     */
    number_identification(config) {
        ActivityCore.hideMultiRoundUI();
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const nurseryMin = Math.max(0, min);
        const nurseryMax = Math.min(Math.max(max, nurseryMin), 20);
        const range = nurseryMax - nurseryMin + 1;
        const poolSize = Math.min(config.poolSize || Math.min(6, range), range);
        const fixedTarget = (config.target_number != null && config.target_number !== undefined) ? config.target_number : (config.answer != null && typeof config.answer === 'number' ? config.answer : null);
        const isShape = !!config.shape_object;
        const isTrace = config.mode === 'trace' || config.interaction === 'coloring';
        const ROUNDS = 3;
        let roundsDone = 0;

        const SHAPE_MAP = {
            0: { emoji: '🍊', name: 'orange' },
            1: { emoji: '✏️', name: 'pencil' },
            2: { emoji: '🦆', name: 'duck' },
            3: { emoji: '🦋', name: 'butterfly' },
            4: { emoji: '🪑', name: 'chair' },
            5: { emoji: '🪝', name: 'hook' },
            6: { emoji: '🥄', name: 'spoon' },
            7: { emoji: '🌾', name: 'hoe' },
            8: { emoji: '🐌', name: 'snail' },
            9: { emoji: '👁️', name: 'eye' }
        };
        const ALL_SHAPE_EMOJIS = Object.values(SHAPE_MAP);

        function finishOrNext() {
            roundsDone++;
            if (roundsDone >= ROUNDS) {
                ActivityCore.finishActivity();
            } else {
                setTimeout(round, 1500);
            }
        }

        function round() {
            const target = fixedTarget != null ? fixedTarget : ActivityCore.randomInt(nurseryMin, nurseryMax);

            if (isTrace) {
                roundTrace(target);
                return;
            }
            if (isShape) {
                roundShape(target);
                return;
            }

            /* default: number tiles */
            const pool = new Set([target]);
            while (pool.size < poolSize) {
                pool.add(ActivityCore.randomInt(nurseryMin, nurseryMax));
            }
            const numbers = ActivityCore.shuffle([...pool]);
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            const prompt = config.instruction || ('Find number ' + target);
            display.appendChild(ActivityCore.renderPrompt(prompt, '🔢'));
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
                            ActivityCore.sayEncouragement(finishOrNext);
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
                ActivityCore.say(prompt);
            });
            ActivityCore.say(prompt);
        }

        function roundShape(target) {
            var correctShape;
            if (config.shape_object) {
                var byName = ALL_SHAPE_EMOJIS.find(function (s) { return s.name === config.shape_object; });
                if (byName) {
                    correctShape = byName;
                } else {
                    var sEmoji = ActivityCore.OBJECT_EMOJIS[config.shape_object] || '❓';
                    correctShape = { emoji: sEmoji, name: config.shape_object };
                }
            } else {
                correctShape = SHAPE_MAP[target] || ALL_SHAPE_EMOJIS[0];
            }
            var distractors = ALL_SHAPE_EMOJIS.filter(function (s) { return s.name !== correctShape.name; });
            if (distractors.length < 2) {
                distractors = ActivityCore.getDistractorObjects(correctShape.name, 3);
            }
            const pick = ActivityCore.shuffle(distractors).slice(0, 2);
            const options_list = ActivityCore.shuffle([correctShape, ...pick]);

            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            const prompt = config.instruction || ('Find the object shaped like number ' + target + '!');
            display.appendChild(ActivityCore.renderPrompt(prompt, correctShape.emoji));

            /* show the number large as reference */
            const numRef = document.createElement('div');
            numRef.style.cssText = 'font-size:6rem;font-weight:800;color:var(--primary-blue);margin:0.3rem 0;opacity:0.35;';
            numRef.textContent = target;
            display.appendChild(numRef);

            const grid = document.createElement('div');
            grid.className = 'number-tiles number-tiles-large';

            options_list.forEach((shape) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'number-tile';
                btn.innerHTML = '<span style="font-size:3rem">' + shape.emoji + '</span><br><span style="font-size:0.9rem">' + shape.name + '</span>';
                btn.onclick = () => {
                    if (shape.name === correctShape.name) {
                        btn.classList.add('correct');
                        ActivityCore.celebrate();
                        ActivityCore.say('Yes! Number ' + target + ' looks like a ' + correctShape.name + '!', finishOrNext);
                    } else {
                        btn.classList.add('incorrect');
                        ActivityCore.say('That is a ' + shape.name + '. Try again!');
                        setTimeout(() => btn.classList.remove('incorrect'), 600);
                    }
                };
                grid.appendChild(btn);
            });
            display.appendChild(grid);

            ActivityCore.bindTopbarAudio(() => {
                ActivityCore.say(prompt);
            });
            ActivityCore.say(prompt);
        }

        function roundTrace(target) {
            const TRACES_NEEDED = 1;
            let tracesDone = 0;

            function runOneTrace() {
                const { display, options } = ActivityCore.clearStage();
                display.className = 'activity-display activity-stage';
                options.innerHTML = '';

                const traceLabel = 'Trace ' + (tracesDone + 1) + ' of ' + TRACES_NEEDED;
                const prompt = config.instruction || ('Trace the number ' + target + '!');
                display.appendChild(ActivityCore.renderPrompt(prompt, '✏️'));

                /* trace counter */
                const counter = document.createElement('div');
                counter.style.cssText = 'text-align:center;font-size:1rem;font-weight:700;color:var(--primary-blue,#4A90E2);margin-bottom:4px;';
                counter.textContent = traceLabel;
                display.appendChild(counter);

                const canvas = document.createElement('canvas');
                canvas.width = 320;
                canvas.height = 320;
                canvas.style.cssText = 'width:100%;max-width:320px;height:auto;border:3px dashed var(--primary-blue,#4A90E2);border-radius:16px;touch-action:none;cursor:crosshair;display:block;margin:0.5rem auto;background:#fff;';
                display.appendChild(canvas);

                const ctx = canvas.getContext('2d');

                /* draw faint outline of the number */
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = 'bold 240px sans-serif';
                ctx.strokeStyle = '#ddd';
                ctx.lineWidth = 4;
                ctx.strokeText(String(target), 160, 165);

                /* thick outline for tracing guide */
                ctx.strokeStyle = '#ccc';
                ctx.lineWidth = 8;
                ctx.setLineDash([12, 8]);
                ctx.strokeText(String(target), 160, 165);
                ctx.setLineDash([]);

                /* --- build reference: stroke outline of the number --- */
                const refCanvas = document.createElement('canvas');
                refCanvas.width = 320;
                refCanvas.height = 320;
                const refCtx = refCanvas.getContext('2d');
                refCtx.textAlign = 'center';
                refCtx.textBaseline = 'middle';
                refCtx.font = 'bold 240px sans-serif';
                refCtx.strokeStyle = '#000';
                refCtx.lineWidth = 20;
                refCtx.strokeText(String(target), 160, 165);
                const refData = refCtx.getImageData(0, 0, 320, 320).data;

                function isOnNumberStroke(px, py) {
                    const radius = 15;
                    for (let dy = -radius; dy <= radius; dy += 2) {
                        for (let dx = -radius; dx <= radius; dx += 2) {
                            const sx = Math.round(px + dx);
                            const sy = Math.round(py + dy);
                            if (sx >= 0 && sx < 320 && sy >= 0 && sy < 320) {
                                const i = (sy * 320 + sx) * 4;
                                if (refData[i + 3] > 128) return true;
                            }
                        }
                    }
                    return false;
                }

                let painting = false;
                let lastX = 0, lastY = 0;
                let totalPixels = 0;
                let onNumberPixels = 0;
                let finished = false;
                const childPoints = [];

                ctx.strokeStyle = '#4A90E2';
                ctx.lineWidth = 18;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.globalCompositeOperation = 'source-over';

                /* hint text */
                const hint = document.createElement('p');
                hint.style.cssText = 'text-align:center;color:#888;font-size:0.9rem;margin-top:0.3rem;min-height:1.4em;';
                hint.textContent = 'Draw over the number with your finger!';
                display.appendChild(hint);

                /* progress bar */
                const progressWrap = document.createElement('div');
                progressWrap.style.cssText = 'width:80%;max-width:280px;height:10px;background:#e0e0e0;border-radius:5px;margin:6px auto;overflow:hidden;';
                const progressBar = document.createElement('div');
                progressBar.style.cssText = 'width:0%;height:100%;background:linear-gradient(90deg,#4A90E2,#27ae60);border-radius:5px;transition:width 0.3s;';
                progressWrap.appendChild(progressBar);
                display.appendChild(progressWrap);

                /* Done button — hidden until enough drawing */
                const doneBtn = document.createElement('button');
                doneBtn.type = 'button';
                doneBtn.className = 'number-tile';
                doneBtn.textContent = '✅ Done';
                doneBtn.style.cssText = 'font-size:1.3rem;padding:14px 36px;margin:8px auto;display:none;background:var(--primary-green,#27ae60);color:#fff;border:none;border-radius:16px;cursor:pointer;min-height:52px;';
                display.appendChild(doneBtn);

                function getPos(e) {
                    const rect = canvas.getBoundingClientRect();
                    const scaleX = canvas.width / rect.width;
                    const scaleY = canvas.height / rect.height;
                    const touch = e.touches ? e.touches[0] : e;
                    return [(touch.clientX - rect.left) * scaleX, (touch.clientY - rect.top) * scaleY];
                }

                function startPaint(e) {
                    e.preventDefault();
                    if (finished) return;
                    painting = true;
                    const [x, y] = getPos(e);
                    lastX = x;
                    lastY = y;
                }

                function paint(e) {
                    if (!painting || finished) return;
                    e.preventDefault();
                    const [x, y] = getPos(e);
                    ctx.beginPath();
                    ctx.moveTo(lastX, lastY);
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    totalPixels++;
                    lastX = x;
                    lastY = y;

                    /* sample every 3rd point for validation */
                    if (totalPixels % 3 === 0) {
                        childPoints.push({ x: Math.round(x), y: Math.round(y) });
                        if (isOnNumberStroke(x, y)) {
                            onNumberPixels++;
                        }
                    }

                    /* show Done only after substantial drawing: 30+ strokes and 20+ points */
                    if (totalPixels >= 30 && childPoints.length >= 20) {
                        doneBtn.style.display = 'block';
                    }

                    /* live feedback */
                    const accuracy = childPoints.length > 0 ? onNumberPixels / childPoints.length : 0;
                    if (totalPixels < 30) {
                        hint.textContent = 'Good! Keep tracing on the line!';
                    } else if (accuracy >= 0.5) {
                        hint.textContent = 'Great! You are following the number!';
                        hint.style.color = '#27ae60';
                    } else {
                        hint.textContent = 'Try to stay on the dotted line!';
                        hint.style.color = '#e67e22';
                    }

                    /* update progress */
                    const pct = Math.min(100, Math.round((totalPixels / 80) * 100));
                    progressBar.style.width = pct + '%';
                }

                function endPaint() { painting = false; }

                function finishOneTrace() {
                    if (finished) return;
                    if (totalPixels < 30 || childPoints.length < 20) {
                        hint.textContent = 'Draw more on the number first!';
                        hint.style.color = '#e67e22';
                        return;
                    }

                    finished = true;
                    painting = false;
                    canvas.removeEventListener('pointerdown', startPaint);
                    canvas.removeEventListener('pointermove', paint);
                    canvas.removeEventListener('pointerup', endPaint);
                    canvas.removeEventListener('touchstart', startPaint);
                    canvas.removeEventListener('touchmove', paint);
                    canvas.removeEventListener('touchend', endPaint);

                    /* calculate accuracy */
                    const accuracy = childPoints.length > 0 ? onNumberPixels / childPoints.length : 0;
                    doneBtn.style.display = 'none';
                    progressBar.style.width = '100%';

                    if (accuracy >= 0.5) {
                        /* good trace */
                        tracesDone++;
                        canvas.style.borderColor = '#27ae60';
                        canvas.style.borderStyle = 'solid';
                        progressBar.style.background = '#27ae60';

                        if (tracesDone >= TRACES_NEEDED) {
                            /* all traces done — move to next activity */
                            hint.textContent = 'Excellent! You traced ' + target + ' perfectly! 🌟';
                            hint.style.color = '#27ae60';
                            ActivityCore.celebrate();
                            ActivityCore.sayNumber(target, () => {
                                ActivityCore.sayEncouragement(function() {
                                    ActivityCore.finishActivity();
                                });
                            });
                        } else {
                            /* more traces needed */
                            hint.textContent = 'Great tracing! Trace it again! (' + tracesDone + '/' + TRACES_NEEDED + ')';
                            hint.style.color = '#27ae60';
                            ActivityCore.sayNumber(target, () => {
                                setTimeout(runOneTrace, 1500);
                            });
                        }
                    } else {
                        /* poor trace — retry */
                        canvas.style.borderColor = '#e67e22';
                        canvas.style.borderStyle = 'solid';
                        hint.textContent = 'Hmm, stay on the dotted line! Try again.';
                        hint.style.color = '#e67e22';
                        progressBar.style.background = '#e67e22';

                        setTimeout(() => {
                            /* reset for retry */
                            ctx.clearRect(0, 0, 320, 320);
                            /* redraw the guide */
                            ctx.textAlign = 'center';
                            ctx.textBaseline = 'middle';
                            ctx.font = 'bold 240px sans-serif';
                            ctx.strokeStyle = '#ddd';
                            ctx.lineWidth = 4;
                            ctx.strokeText(String(target), 160, 165);
                            ctx.strokeStyle = '#ccc';
                            ctx.lineWidth = 8;
                            ctx.setLineDash([12, 8]);
                            ctx.strokeText(String(target), 160, 165);
                            ctx.setLineDash([]);
                            ctx.strokeStyle = '#4A90E2';
                            ctx.lineWidth = 18;
                            ctx.lineCap = 'round';
                            ctx.lineJoin = 'round';

                            canvas.style.borderColor = 'var(--primary-blue,#4A90E2)';
                            canvas.style.borderStyle = 'dashed';
                            hint.textContent = 'Draw over the number with your finger!';
                            hint.style.color = '#888';
                            progressBar.style.width = '0%';
                            progressBar.style.background = 'linear-gradient(90deg,#4A90E2,#27ae60)';
                            finished = false;
                            totalPixels = 0;
                            onNumberPixels = 0;
                            childPoints.length = 0;
                            doneBtn.style.display = 'none';
                            canvas.addEventListener('pointerdown', startPaint);
                            canvas.addEventListener('pointermove', paint);
                            canvas.addEventListener('pointerup', endPaint);
                            canvas.addEventListener('touchstart', startPaint, { passive: false });
                            canvas.addEventListener('touchmove', paint, { passive: false });
                            canvas.addEventListener('touchend', endPaint);
                        }, 2500);
                    }
                }

                doneBtn.onclick = finishOneTrace;

                canvas.addEventListener('pointerdown', startPaint);
                canvas.addEventListener('pointermove', paint);
                canvas.addEventListener('pointerup', endPaint);
                canvas.addEventListener('touchstart', startPaint, { passive: false });
                canvas.addEventListener('touchmove', paint, { passive: false });
                canvas.addEventListener('touchend', endPaint);

                ActivityCore.bindTopbarAudio(() => {
                    ActivityCore.say(prompt);
                });
                ActivityCore.say(prompt);
            }

            runOneTrace();
        }

        round();
    },

    /* ----- Number sequencing (supports 1–20 via config.min/max) ----- */
    number_sequencing(config) {
        ActivityCore.hideMultiRoundUI();
        const { min, max } = ActivityCore.getDifficultyRange(config);
        const seqMin = config.min ?? min ?? 1;
        const seqMax = Math.min(max, 20);
        const slots = [];
        const pool = [];

        function init() {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt(config.instruction || ('Put numbers in order from ' + seqMin + ' to ' + seqMax), '🔢'));

            const ws = document.createElement('div');
            ws.className = 'sequence-workspace';

            const slotsRow = document.createElement('div');
            slotsRow.className = 'sequence-slots';
            for (let i = seqMin; i <= seqMax; i++) {
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
            const nums = ActivityCore.shuffle([...Array(seqMax - seqMin + 1)].map((_, i) => i + seqMin));
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
                        ActivityCore.say('Good job! You arranged the numbers correctly!', () => {
                            setTimeout(() => ActivityCore.finishActivity(), 1500);
                        });
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
        const ROUNDS = 3;
        let roundsDone = 0;

        function round() {
            const range = max - min;
            const startPos = Math.floor(Math.random() * Math.max(1, range - 4)) + min;
            const missing = startPos + 1 + Math.floor(Math.random() * Math.min(3, range - 2));
            const seq = [startPos, startPos + 1, missing + 1, missing + 2];
            const correct = missing;
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';

            display.appendChild(ActivityCore.renderPrompt(config.instruction || 'What number is missing?', '❓'));
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
                        ActivityCore.sayEncouragement(() => {
                            roundsDone++;
                            if (roundsDone >= ROUNDS) {
                                ActivityCore.finishActivity();
                            } else {
                                setTimeout(round, 2000);
                            }
                        });
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
        const target = (config.target != null && config.target !== undefined) ? config.target : ActivityCore.randomInt(Math.max(0, min), Math.min(max, 10));
        const ROUNDS = 3;
        let roundsDone = 0;

        function round() {
            var counts;
            if (target === 0) {
                counts = ActivityCore.shuffle([0, 1, 2]);
            } else {
                counts = ActivityCore.shuffle(
                    [target, target - 1, target + 1].filter((c) => c >= 0)
                );
            }
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';

            display.appendChild(ActivityCore.renderPrompt('Find the group with ' + target + ' ' + ActivityCore.pluralize(obj, target), emoji));
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
                if (count === 0) {
                    const empty = document.createElement('span');
                    empty.textContent = '∅';
                    empty.style.cssText = 'font-size:2rem;color:#ccc;';
                    empty.setAttribute('aria-label', 'empty');
                    row.appendChild(empty);
                }
                g.appendChild(row);
                g.onclick = () => {
                    if (count === target) {
                        g.classList.add('selected-correct');
                        ActivityCore.celebrate();
                        ActivityCore.sayEncouragement(() => {
                            roundsDone++;
                            if (roundsDone >= ROUNDS) {
                                ActivityCore.finishActivity();
                            } else {
                                setTimeout(round, 1500);
                            }
                        });
                    } else {
                        g.classList.add('selected-wrong');
                        ActivityCore.say('Almost! Let us count together.');
                        setTimeout(() => {
                            g.classList.remove('selected-wrong');
                            [...groups.children].forEach((el) => {
                                const emojiCount = el.querySelectorAll('.objects-row span[aria-label!="empty"]').length;
                                const isEmpty = el.querySelector('.objects-row span[aria-label="empty"]');
                                const groupCount = isEmpty ? 0 : emojiCount;
                                if (groupCount === target) {
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
                ActivityCore.say('Can you find the group that has ' + target + ' ' + ActivityCore.pluralize(obj, target) + '?');
            });
            ActivityCore.say('Can you find the group that has ' + target + ' ' + ActivityCore.pluralize(obj, target) + '?');
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

        display.appendChild(ActivityCore.renderPrompt('Move all ' + ActivityCore.pluralize(obj, total) + ' into the basket', emoji));
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
            display.querySelector('.activity-prompt').textContent = 'How many ' + ActivityCore.pluralize(obj, 2) + ' are in the basket?';
            const choices = ActivityCore.buildMCOptions(total, 1, total + 3);
            ActivityCore.renderMC(choices, (sel, btn) => {
                if (sel === total) {
                    btn.classList.add('correct');
                    ActivityCore.celebrate();
                    ActivityCore.sayNumber(total, () => ActivityCore.sayEncouragement());
                } else {
                    btn.classList.add('incorrect');
                    ActivityCore.say('Count again. How many ' + ActivityCore.pluralize(obj, 2) + '?');
                }
            });
            ActivityCore.say('How many ' + ActivityCore.pluralize(obj, 2) + ' are in the basket?');
        }

        ActivityCore.say('Let us add the ' + ActivityCore.pluralize(obj, total) + '. Move them into the basket!');
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

        display.appendChild(ActivityCore.renderPrompt('Tap ' + remove + ' ' + ActivityCore.pluralize(obj, remove) + ' to take away', emoji));
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
                'How many ' + ActivityCore.pluralize(obj, 2) + ' are left?';
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
            ActivityCore.say('How many ' + ActivityCore.pluralize(obj, 2) + ' are left?');
        }

        ActivityCore.bindTopbarAudio(() => {
            ActivityCore.say('We have ' + start + ' ' + ActivityCore.pluralize(obj, start) + '. Tap ' + remove + ' to take them away.');
        });
        ActivityCore.say('We have ' + start + ' ' + ActivityCore.pluralize(obj, start) + '. Tap ' + remove + ' to take them away.');
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

        if (config.skip_finish) {
            const { display, options } = ActivityCore.clearStage();
            display.className = 'activity-display activity-stage';
            options.innerHTML = '';
            display.innerHTML = '<div class="finish-screen text-center">' +
                '<div class="finish-trophy">🏆</div>' +
                '<div class="finish-stars">⭐⭐⭐</div>' +
                '<h2 class="finish-title">Great Work!</h2>' +
                '<p class="finish-subtitle">You are doing amazing!</p></div>';
            ActivityCore.celebrate();
            ActivityCore.say('Great work! You are doing amazing!');
            const bar = document.getElementById('nextActivityBar');
            if (bar) {
                bar.style.display = 'flex';
                const btn = document.getElementById('nextActivityBtn');
                if (btn) {
                    const actCfg = window.ACTIVITY_CONFIG || {};
                    const hasNext = actCfg.nextActivityId > 0;
                    btn.innerHTML = hasNext
                        ? '<i class="fas fa-arrow-right me-2"></i>Next'
                        : '<i class="fas fa-check-circle me-2"></i>Done';
                    btn.onclick = function () {
                        if (typeof goBack === 'function') goBack();
                    };
                }
            }
            return;
        }

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

            showRoundHeader(display, 'Count the ' + ActivityCore.pluralize(obj, total) + '!');

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

            ActivityCore.say('Count the ' + ActivityCore.pluralize(obj, total) + '. Tap each one.');
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

            showRoundHeader(display, 'Add the ' + ActivityCore.pluralize(obj, total) + '!');
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

            ActivityCore.say('Add the ' + ActivityCore.pluralize(obj, total) + '. Move them into the basket.');
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

            showRoundHeader(display, 'Take away ' + ActivityCore.pluralize(obj, start) + '!');
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

            ActivityCore.say('Take away ' + remove + ' ' + ActivityCore.pluralize(obj, remove) + '. Tap them.');
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
