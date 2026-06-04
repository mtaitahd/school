<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/includes/SubscriptionMiddleware.php';

ensure_schema_v2($database);

auth_require_role(['learner'], 'login.php');

// Check subscription access
SubscriptionMiddleware::requireAccess();

$learner_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'learner';
$sidebar_active = 'assigned';

$studentAssignmentId = (int) ($_GET['sa_id'] ?? 0);
if (!$studentAssignmentId) {
    header('Location: assigned.php');
    exit;
}

$sa = $database->fetchOne(
    "SELECT sa.*, a.title, a.description, a.due_date, a.duration_minutes, a.assignment_type,
            a.activity_id, act.activity_name, act.activity_type, m.module_name, m.module_color,
            u.first_name AS teacher_first
     FROM student_assignments sa
     JOIN assignments a ON sa.assignment_id = a.assignment_id
     LEFT JOIN activities act ON a.activity_id = act.activity_id
     LEFT JOIN modules m ON act.module_id = m.module_id
     LEFT JOIN users u ON a.teacher_id = u.user_id
     WHERE sa.student_assignment_id = ? AND sa.student_id = ?",
    [$studentAssignmentId, $learner_id]
);

if (!$sa) {
    header('Location: assigned.php');
    exit;
}

// If already completed, redirect to review or assigned
if (in_array($sa['status'], ['completed', 'auto_submitted', 'expired'], true)) {
    header('Location: assigned.php?lang=' . $current_lang);
    exit;
}

// Load questions
$questions = $database->fetchAll(
    "SELECT * FROM assignment_questions WHERE assignment_id = ? ORDER BY sort_order ASC, question_id ASC",
    [$sa['assignment_id']]
);

$totalQuestions = count($questions);

if ($totalQuestions === 0) {
    header('Location: activity.php?activity_id=' . (int) $sa['activity_id']);
    exit;
}

// Set started_at and status to in_progress
$database->execute(
    "UPDATE student_assignments SET status = 'in_progress', started_at = COALESCE(started_at, NOW())
     WHERE student_assignment_id = ?",
    [$studentAssignmentId]
);

// Load existing answers
$answers = $database->fetchAll(
    "SELECT question_id, given_answer, is_correct FROM assignment_answers
     WHERE student_assignment_id = ? AND student_id = ?",
    [$studentAssignmentId, $learner_id]
);
$answerMap = [];
foreach ($answers as $a) {
    $answerMap[(int) $a['question_id']] = $a;
}

// Determine started answers count
$answeredCount = count(array_filter($answers, fn($a) => !empty($a['given_answer'])));
$durationMinutes = (int) ($sa['duration_minutes'] ?? 30);

$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sa['title']); ?> - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/activities.css">
    <style>
        .quiz-container { max-width: 800px; margin: 0 auto; }
        .quiz-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
        .timer-bar { background: var(--primary-blue); color: #fff; padding: 0.75rem 1.25rem; border-radius: 12px; font-weight: 700; font-size: 1.25rem; display: inline-flex; align-items: center; gap: 0.5rem; }
        .timer-bar.warning { background: var(--primary-orange); animation: pulse 1s infinite; }
        .timer-bar.danger { background: var(--primary-red); animation: pulse 0.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .question-number { font-size: 0.85rem; color: var(--text-light); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .question-text { font-size: 1.35rem; font-weight: 700; color: var(--text-dark); margin: 1rem 0 1.5rem; line-height: 1.6; }
        .progress-track { display: flex; gap: 6px; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .progress-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; color: #999; background: #fff; transition: all 0.3s; }
        .progress-dot.answered { border-color: var(--primary-green); background: var(--primary-green); color: #fff; }
        .progress-dot.current { border-color: var(--primary-blue); background: var(--primary-blue); color: #fff; transform: scale(1.15); }
        .progress-dot.skipped { border-color: var(--primary-orange); background: var(--primary-orange); color: #fff; }
        .option-btn { display: block; width: 100%; padding: 1rem 1.25rem; margin-bottom: 0.75rem; border: 2px solid #e0e0e0; border-radius: 12px; background: #fff; font-size: 1.05rem; text-align: left; cursor: pointer; transition: all 0.2s; font-family: inherit; }
        .option-btn:hover { border-color: var(--primary-blue); background: #f0f7ff; }
        .option-btn:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2); }
        .option-btn.selected { border-color: var(--primary-blue); background: #e8f4fd; font-weight: 600; }
        .option-btn.correct { border-color: var(--primary-green); background: #e8f8e8; }
        .option-btn.incorrect { border-color: var(--primary-red); background: #fde8e8; }
        .option-btn:disabled { cursor: default; opacity: 0.85; }
        .result-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; margin-top: 1rem; }
        .result-badge.correct { background: #e8f8e8; color: #2d7a2d; }
        .result-badge.incorrect { background: #fde8e8; color: #c0392b; }
        .next-overlay { position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #eee; padding: 1rem; text-align: center; z-index: 100; display: none; box-shadow: 0 -4px 20px rgba(0,0,0,0.1); }
        body { padding-bottom: 80px; }
        .auto-submit-banner { position: fixed; top: 0; left: 0; right: 0; background: var(--primary-orange); color: #fff; text-align: center; padding: 0.75rem; font-weight: 600; z-index: 200; display: none; }
    </style>
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

<div class="quiz-container">
    <div class="quiz-header">
        <div>
            <h3 class="mb-1" style="font-weight:700;"><?php echo htmlspecialchars($sa['title']); ?></h3>
            <p class="text-muted mb-0" style="font-size:0.9rem;">
                <?php echo $current_lang === 'sw' ? 'Mwalimu' : 'Teacher' ?>: <?php echo htmlspecialchars($sa['teacher_first'] ?? 'N/A'); ?>
                <?php if ($sa['module_name']): ?> &middot; <?php echo htmlspecialchars($sa['module_name']); endif; ?>
            </p>
        </div>
        <div class="timer-bar" id="timerDisplay">
            <i class="fas fa-clock"></i>
            <span id="timerText">--:--</span>
        </div>
    </div>

    <div class="progress-track" id="progressTrack"></div>

    <div class="question-number" id="questionNumber"></div>
    <div class="question-text" id="questionText"></div>
    <div id="optionsContainer"></div>
    <div id="resultFeedback"></div>
</div>

<div class="auto-submit-banner" id="autoSubmitBanner">
    <i class="fas fa-hourglass-end me-2"></i>
    <?php echo $current_lang === 'sw' ? 'Muda umeisha. Inatuma kiotomatiki...' : 'Time expired. Auto-submitting...'; ?>
</div>

<div class="next-overlay" id="nextOverlay">
    <button class="btn-child btn-child-primary btn-child-large" id="nextBtn" style="display:none;">
        <?php echo $current_lang === 'sw' ? 'Inayofuata' : 'Next'; ?>
        <i class="fas fa-arrow-right ms-2"></i>
    </button>
    <button class="btn-child btn-child-green btn-child-large" id="submitBtn" style="display:none;">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo $current_lang === 'sw' ? 'Wasilisha' : 'Submit'; ?>
    </button>
</div>

<?php include '../php/includes/dashboard-end.php'; ?>

<script>
    const CONFIG = {
        studentAssignmentId: <?php echo $studentAssignmentId; ?>,
        totalQuestions: <?php echo $totalQuestions; ?>,
        durationMinutes: <?php echo $durationMinutes; ?>,
        csrfToken: <?php echo json_encode($csrfToken); ?>,
        lang: <?php echo json_encode($current_lang); ?>,
        questions: <?php echo json_encode(array_map(function($q) {
            $options = json_decode($q['options'], true) ?: [];
            if ($q['question_type'] === 'true_false') {
                $options = ['True', 'False'];
            }
            return [
                'question_id' => (int) $q['question_id'],
                'question_text' => $q['question_text'],
                'question_type' => $q['question_type'],
                'options' => $options,
                'points' => (int) $q['points'],
            ];
        }, $questions)); ?>,
        existingAnswers: <?php echo json_encode($answerMap); ?>,
        strings: {
            answered: <?php echo json_encode($current_lang === 'sw' ? 'Jibu' : 'Answered'); ?>,
            skipped: <?php echo json_encode($current_lang === 'sw' ? 'Rukia' : 'Skipped'); ?>,
            submitConfirm: <?php echo json_encode($current_lang === 'sw' ? 'Una uhakika unataka kuwasilisha? Maswali yaliyobaki yatawekwa kama yamerukiwa.' : 'Are you sure you want to submit? Unanswered questions will be skipped.'); ?>,
            submitting: <?php echo json_encode($current_lang === 'sw' ? 'Inawasilisha...' : 'Submitting...'); ?>,
            timeExpired: <?php echo json_encode($current_lang === 'sw' ? 'Muda umeisha! Maswali hayajajibiwa yamewekwa kama yamerukiwa.' : 'Time expired! Unanswered questions have been skipped.'); ?>,
            correct: <?php echo json_encode($current_lang === 'sw' ? 'Sahihi!' : 'Correct!'); ?>,
            incorrect: <?php echo json_encode($current_lang === 'sw' ? 'Sio sahihi' : 'Incorrect'); ?>,
            completedTitle: <?php echo json_encode($current_lang === 'sw' ? 'Umekamilisha!' : 'Completed!'); ?>,
            completedMsg: <?php echo json_encode($current_lang === 'sw' ? 'Shughuli yako imewasilishwa kwa mafanikio.' : 'Your assignment has been submitted successfully.'); ?>,
            viewResults: <?php echo json_encode($current_lang === 'sw' ? 'Angalia Matokeo' : 'View Results'); ?>,
        }
    };

    let currentIndex = 0;
    let timerInterval = null;
    let autoSaveInterval = null;
    let isSubmitting = false;
    let isTimerExpired = false;
    let answeredState = {};

    // Restore existing answers
    for (const [qId, ans] of Object.entries(CONFIG.existingAnswers)) {
        if (ans.given_answer) {
            answeredState[qId] = ans;
        }
    }

    function getCurrentQuestion() {
        return CONFIG.questions[currentIndex];
    }

    function renderProgress() {
        const track = document.getElementById('progressTrack');
        track.innerHTML = '';
        CONFIG.questions.forEach((q, i) => {
            const dot = document.createElement('div');
            dot.className = 'progress-dot';
            dot.textContent = i + 1;
            if (answeredState[q.question_id]) {
                dot.classList.add('answered');
            }
            if (i === currentIndex) {
                dot.classList.add('current');
            }
            dot.title = `Q${i + 1}`;
            dot.style.cursor = 'pointer';
            dot.onclick = () => goToQuestion(i);
            track.appendChild(dot);
        });
        updateProgressPercentage();
    }

    function updateProgressPercentage() {
        const answered = Object.keys(answeredState).length;
        const pct = CONFIG.totalQuestions > 0 ? Math.round((answered / CONFIG.totalQuestions) * 100) : 0;
        // Update the sidebar or any progress display
    }

    function renderQuestion() {
        const q = getCurrentQuestion();
        if (!q) return;

        document.getElementById('questionNumber').textContent =
            (CONFIG.lang === 'sw' ? 'Swali' : 'Question') + ' ' + (currentIndex + 1) + ' / ' + CONFIG.totalQuestions;
        document.getElementById('questionText').textContent = q.question_text;

        const container = document.getElementById('optionsContainer');
        container.innerHTML = '';
        document.getElementById('resultFeedback').innerHTML = '';

        const existing = answeredState[q.question_id];
        const isAlreadyAnswered = !!existing;

        if (q.options && q.options.length > 0) {
            q.options.forEach((opt) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'option-btn';
                btn.textContent = opt;

                if (isAlreadyAnswered) {
                    btn.disabled = true;
                    btn.classList.add(opt === existing.given_answer ? (existing.is_correct ? 'correct' : 'incorrect') : '');
                    if (opt === existing.given_answer) {
                        btn.classList.add('selected');
                    }
                }

                btn.onclick = () => selectAnswer(opt);
                container.appendChild(btn);
            });
        } else {
            const inputGroup = document.createElement('div');
            inputGroup.className = 'input-group mb-3';
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-lg';
            input.placeholder = CONFIG.lang === 'sw' ? 'Andika jibu lako...' : 'Type your answer...';
            input.disabled = isAlreadyAnswered;
            if (isAlreadyAnswered) {
                input.value = existing.given_answer;
            }
            const sendBtn = document.createElement('button');
            sendBtn.type = 'button';
            sendBtn.className = 'btn btn-primary btn-lg';
            sendBtn.textContent = CONFIG.lang === 'sw' ? 'Wasilisha' : 'Submit';
            sendBtn.disabled = isAlreadyAnswered;
            sendBtn.onclick = () => {
                const val = input.value.trim();
                if (val) selectAnswer(val);
            };
            input.onkeydown = (e) => {
                if (e.key === 'Enter' && input.value.trim()) selectAnswer(input.value.trim());
            };
            inputGroup.appendChild(input);
            inputGroup.appendChild(sendBtn);
            container.appendChild(inputGroup);
        }

        renderProgress();

        // Show overlay if all answered
        updateOverlay();
    }

    function selectAnswer(answer) {
        const q = getCurrentQuestion();
        if (!q) return;
        if (answeredState[q.question_id] || isSubmitting) return;

        isSubmitting = true;

        // Disable all buttons
        document.querySelectorAll('.option-btn').forEach(b => b.disabled = true);

        fetch('../api/save-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CONFIG.csrfToken
            },
            body: JSON.stringify({
                student_assignment_id: CONFIG.studentAssignmentId,
                question_id: q.question_id,
                answer: answer,
                action: 'answer'
            })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                if (data.final) return finishAssignment('already_submitted');
                showError(data.error || 'Error saving answer');
                return;
            }

            // Mark as answered
            answeredState[q.question_id] = {
                given_answer: answer,
                is_correct: data.is_correct ? 1 : 0
            };
            CONFIG.existingAnswers[q.question_id] = answeredState[q.question_id];

            // Show feedback
            const feedback = document.getElementById('resultFeedback');
            feedback.innerHTML = '';
            const badge = document.createElement('div');
            badge.className = 'result-badge ' + (data.is_correct ? 'correct' : 'incorrect');
            badge.innerHTML = (data.is_correct ? '&#10004; ' : '&#10008; ') +
                (data.is_correct ? CONFIG.strings.correct : CONFIG.strings.incorrect) +
                (data.is_correct ? '' : '');
            feedback.appendChild(badge);

            // Highlight correct/incorrect
            document.querySelectorAll('.option-btn').forEach(btn => {
                if (btn.textContent === answer) {
                    btn.classList.add(data.is_correct ? 'correct' : 'incorrect', 'selected');
                }
            });

            // If completed, submit
            if (data.completed) {
                setTimeout(() => finishAssignment('auto'), 800);
                return;
            }

            // Auto-advance after short delay
            setTimeout(() => {
                if (currentIndex < CONFIG.totalQuestions - 1) {
                    goToQuestion(currentIndex + 1);
                } else {
                    updateOverlay();
                }
                isSubmitting = false;
            }, 600);
        })
        .catch(err => {
            console.error('Save error:', err);
            isSubmitting = false;
            showError('Network error. Please try again.');
        });
    }

    function goToQuestion(index) {
        currentIndex = index;
        renderQuestion();
        updateOverlay();
    }

    function updateOverlay() {
        const answered = Object.keys(answeredState).length;
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const overlay = document.getElementById('nextOverlay');

        if (answered >= CONFIG.totalQuestions) {
            // All answered - show submit
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            overlay.style.display = 'block';
        } else if (currentIndex < CONFIG.totalQuestions - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'none';
            overlay.style.display = 'none';
        } else {
            // Last question, not all answered = show submit anyway
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
            overlay.style.display = 'block';
        }
    }

    function finishAssignment(type) {
        if (isSubmitting) return;
        isSubmitting = true;

        const submissionType = type === 'timeout' ? 'time_expired' : 'automatic';

        if (type === 'timeout') {
            document.getElementById('autoSubmitBanner').style.display = 'block';
        }

        fetch('../api/submit-assignment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': CONFIG.csrfToken
            },
            body: JSON.stringify({
                student_assignment_id: CONFIG.studentAssignmentId,
                submission_type: submissionType
            })
        })
        .then(r => r.json())
        .then(data => {
            if (timerInterval) clearInterval(timerInterval);
            if (autoSaveInterval) clearInterval(autoSaveInterval);
            showCompletionModal(data);
        })
        .catch(err => {
            console.error('Submit error:', err);
            isSubmitting = false;
            showError('Submit failed. Please refresh and try again.');
        });
    }

    function showCompletionModal(data) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
        const card = document.createElement('div');
        card.style.cssText = 'background:#fff;border-radius:24px;padding:2.5rem;max-width:450px;width:90%;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.2);';
        card.innerHTML = `
            <div style="font-size:4rem;margin-bottom:1rem;">&#127881;</div>
            <h3 style="font-weight:700;margin-bottom:0.5rem;">${CONFIG.strings.completedTitle}</h3>
            <p style="color:var(--text-light);margin-bottom:1.5rem;">${CONFIG.strings.completedMsg}</p>
            <div style="display:flex;justify-content:center;gap:2rem;margin-bottom:1.5rem;">
                <div><div style="font-size:1.5rem;font-weight:700;color:var(--primary-green);">${data.answered || 0}</div><small style="color:var(--text-light);">${CONFIG.lang === 'sw' ? 'Jibu' : 'Answered'}</small></div>
                <div><div style="font-size:1.5rem;font-weight:700;color:var(--primary-orange);">${data.skipped || 0}</div><small style="color:var(--text-light);">${CONFIG.lang === 'sw' ? 'Kuruka' : 'Skipped'}</small></div>
                <div><div style="font-size:1.5rem;font-weight:700;color:var(--primary-blue);">${data.total || 0}</div><small style="color:var(--text-light);">${CONFIG.lang === 'sw' ? 'Jumla' : 'Total'}</small></div>
            </div>
            <a href="assigned.php?lang=${CONFIG.lang}" class="btn-child btn-child-primary btn-child-large" style="text-decoration:none;display:inline-block;">
                <i class="fas fa-arrow-left me-2"></i>${CONFIG.strings.viewResults}
            </a>
        `;
        overlay.appendChild(card);
        overlay.onclick = (e) => { if (e.target === overlay) window.location.href = 'assigned.php?lang=' + CONFIG.lang; };
        document.body.appendChild(overlay);
    }

    function showError(msg) {
        const feedback = document.getElementById('resultFeedback');
        feedback.innerHTML = `<div class="alert alert-danger py-2 mt-2" style="border-radius:10px;font-size:0.9rem;">${msg}</div>`;
    }

    // Timer
    function startTimer() {
        let totalSeconds = CONFIG.durationMinutes * 60;
        const timerText = document.getElementById('timerText');
        const timerBar = document.getElementById('timerDisplay');

        function updateDisplay() {
            const mins = Math.floor(totalSeconds / 60);
            const secs = totalSeconds % 60;
            timerText.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');

            if (totalSeconds <= 60) {
                timerBar.className = 'timer-bar danger';
            } else if (totalSeconds <= 300) {
                timerBar.className = 'timer-bar warning';
            }

            if (totalSeconds <= 0) {
                clearInterval(timerInterval);
                clearInterval(autoSaveInterval);
                if (!isTimerExpired) {
                    isTimerExpired = true;
                    finishAssignment('timeout');
                }
            }
            totalSeconds--;
        }

        updateDisplay();
        timerInterval = setInterval(updateDisplay, 1000);
    }

    // Auto-save every 30s
    function startAutoSave() {
        autoSaveInterval = setInterval(() => {
            // Answers are already saved on each selection.
            // This auto-save is for heartbeat/time tracking.
            const answered = Object.keys(answeredState).length;
            if (answered >= 0 && !isSubmitting) {
                fetch('../api/save-answer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CONFIG.csrfToken
                    },
                    body: JSON.stringify({
                        student_assignment_id: CONFIG.studentAssignmentId,
                        question_id: 0,
                        answer: '',
                        action: 'heartbeat'
                    })
                }).catch(() => {});
            }
        }, 30000);
    }

    document.getElementById('submitBtn').onclick = () => {
        if (confirm(CONFIG.strings.submitConfirm)) {
            finishAssignment('manual');
        }
    };

    // Init
    renderQuestion();
    startTimer();
    startAutoSave();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
</body>
</html>
