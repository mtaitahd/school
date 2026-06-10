/**
 * Activity page bootstrap — uses engine registry or legacy multi-question mode
 */
(function () {
    const cfg = window.ACTIVITY_CONFIG || {};
    const engine = (cfg.activityData && cfg.activityData.engine) || cfg.activityType;

    document.addEventListener('DOMContentLoaded', function () {
        const usedEngine = runActivity(cfg);

        if (usedEngine) {
            window.playInstruction = function () {
                if (cfg.audioInstruction && typeof playAudio === 'function') {
                    playAudio(cfg.audioInstruction);
                }
            };
            if (cfg.audioInstruction) {
                setTimeout(() => playInstruction(), 400);
            }
            return;
        }

        // Legacy quiz mode (5 questions)
        initLegacyQuiz(cfg);
    });

    function initLegacyQuiz(cfg) {
        let currentQuestion = 0;
        let score = 0;
        const totalQuestions = cfg.totalQuestions || 5;
        let correctAnswer = 0;
        const activityData = cfg.activityData || {};
        const activityType = cfg.activityType;

        document.getElementById('totalQuestions').textContent = totalQuestions;

        window.playInstruction = function () {
            if (cfg.audioInstruction) playAudio(cfg.audioInstruction);
        };

        window.goBack = function () {
            window.location.href = 'activities?module_id=' + cfg.moduleId + '&lang=' + (cfg.lang || 'en');
        };

        loadQuestion();
        playInstruction();

        function loadQuestion() {
            const display = document.getElementById('activityDisplay');
            const options = document.getElementById('answerOptions');
            display.innerHTML = '';
            options.innerHTML = '';

            if (activityType === 'counting') loadCounting(display, options);
            else if (activityType === 'shapes') loadShapes(display, options);
            else if (activityType === 'addition') loadAddition(display, options);
            else if (activityType === 'subtraction') loadSubtraction(display, options);
        }

        function loadCounting(display, options) {
            const min = activityData.min || 1;
            const max = activityData.max || 10;
            const object = activityData.object || 'apple';
            correctAnswer = Math.floor(Math.random() * (max - min + 1)) + min;
            const container = document.createElement('div');
            container.className = 'activity-objects';
            const emoji = { apple: '🍎', star: '⭐', ball: '⚽', mango: '🥭' }[object] || '🍎';
            for (let i = 0; i < correctAnswer; i++) {
                const obj = document.createElement('span');
                obj.className = 'activity-object';
                obj.textContent = emoji;
                container.appendChild(obj);
            }
            display.appendChild(container);
            generateAnswerOptions(options, correctAnswer, min, max);
        }

        function loadShapes(display, options) {
            const shape = activityData.shape || 'circle';
            const shapes = ['circle', 'square', 'triangle', 'star'];
            const icons = { circle: '⭕', square: '⬜', triangle: '🔺', star: '⭐' };
            correctAnswer = shapes.indexOf(shape);
            const container = document.createElement('div');
            container.className = 'activity-objects';
            shapes.forEach((s, index) => {
                const div = document.createElement('div');
                div.className = 'activity-object';
                div.style.fontSize = '5rem';
                div.style.cursor = 'pointer';
                div.textContent = icons[s];
                div.onclick = function () { validateShape(index, div); };
                container.appendChild(div);
            });
            display.appendChild(container);
            options.innerHTML = '<p class="text-center activity-prompt">Click on the correct shape!</p>';
        }

        function loadAddition(display, options) {
            const min = activityData.min || 1;
            const max = activityData.max || 5;
            const n1 = Math.floor(Math.random() * (max - min + 1)) + min;
            const n2 = Math.floor(Math.random() * (max - min + 1)) + min;
            correctAnswer = n1 + n2;
            display.innerHTML = '<div style="font-size:4rem;font-weight:700;color:var(--primary-blue)">' + n1 + ' + ' + n2 + ' = ?</div>';
            generateAnswerOptions(options, correctAnswer, min, max * 2);
        }

        function loadSubtraction(display, options) {
            const min = activityData.min || 1;
            const max = activityData.max || 5;
            const n1 = Math.floor(Math.random() * (max - min + 1)) + min;
            const n2 = Math.floor(Math.random() * n1) + 1;
            correctAnswer = n1 - n2;
            display.innerHTML = '<div style="font-size:4rem;font-weight:700;color:var(--primary-blue)">' + n1 + ' - ' + n2 + ' = ?</div>';
            generateAnswerOptions(options, correctAnswer, 0, max);
        }

        function generateAnswerOptions(options, correct, min, max) {
            const answers = [correct];
            while (answers.length < 3) {
                const w = Math.floor(Math.random() * (max - min + 1)) + min;
                if (!answers.includes(w) && w >= 0) answers.push(w);
            }
            ActivityCore.shuffle(answers).forEach((answer) => {
                const btn = document.createElement('button');
                btn.className = 'answer-btn';
                btn.textContent = answer;
                btn.onclick = function () { validateAnswer(answer, correct, btn); };
                options.appendChild(btn);
            });
        }

        function validateShape(index, el) {
            if (index === correctAnswer) {
                el.style.transform = 'scale(1.3)';
                showStarAnimation();
                score++;
                document.getElementById('scoreDisplay').textContent = score;
                setTimeout(nextQuestion, 1500);
            } else {
                showErrorFeedback();
            }
        }

        function validateAnswer(selected, correct, button) {
            if (selected === correct) {
                button.classList.add('correct');
                showStarAnimation();
                score++;
                document.getElementById('scoreDisplay').textContent = score;
                setTimeout(nextQuestion, 1500);
            } else {
                button.classList.add('incorrect');
                showErrorFeedback();
                setTimeout(() => button.classList.remove('incorrect'), 500);
            }
        }

        function nextQuestion() {
            currentQuestion++;
            const pct = (currentQuestion / totalQuestions) * 100;
            const bar = document.getElementById('progressBar');
            bar.style.width = pct + '%';
            bar.textContent = Math.round(pct) + '%';
            if (currentQuestion >= totalQuestions) showCompletion();
            else {
                document.getElementById('nextActivityBar').style.display = 'flex';
                document.getElementById('nextActivityBtn').onclick = function () {
                    document.getElementById('nextActivityBar').style.display = 'none';
                    loadQuestion();
                };
            }
        }

        function saveProgressToServer(completed) {
            const cfg = window.ACTIVITY_CONFIG || {};
            if (!cfg.activityId) return;
            const pct = totalQuestions > 0 ? Math.round((score / totalQuestions) * 100) : 0;
            const stars = score >= totalQuestions ? 3 : (score >= totalQuestions * 0.6 ? 2 : 1);
            fetch(cfg.saveProgressUrl || '../api/save-progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    activity_id: cfg.activityId,
                    score: pct,
                    completed: completed ? 1 : 0,
                    stars: stars
                })
            }).catch(function () {});
        }

        function showCompletion() {
            saveProgressToServer(true);
            document.getElementById('activityDisplay').innerHTML =
                '<div style="text-align:center"><i class="fas fa-trophy" style="font-size:6rem;color:var(--primary-yellow)"></i>' +
                '<h2>Great Job!</h2><p>You scored ' + score + ' / ' + totalQuestions + '</p></div>';
            document.getElementById('answerOptions').innerHTML = '';
            document.getElementById('nextActivityBar').style.display = 'flex';
            document.getElementById('nextActivityBtn').innerHTML = '<i class="fas fa-redo me-2"></i>Try Again';
            document.getElementById('nextActivityBtn').onclick = function () { location.reload(); };
            playAudio('Congratulations! You completed the activity!');
        }
    }
})();
