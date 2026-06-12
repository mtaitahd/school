<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/includes/SubscriptionMiddleware.php';
ensure_schema_v2($database);

auth_require_role(['learner'], 'login.php');

$learner_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'learner';
$sidebar_active = 'assigned';
$lang_page = 'assigned.php';

$trialInfo = SubscriptionMiddleware::getLearnerTrialInfo($learner_id);

$assignments = $database->fetchAll(
    "SELECT a.*, sa.status, sa.score, sa.student_assignment_id, sa.submitted_at,
            sa.total_questions, sa.answered_questions, sa.progress_percentage,
            sa.submission_type, sa.completed_at,
            act.activity_id, act.activity_name, act.activity_type, m.module_name, m.module_color,
            u.first_name AS teacher_first,
            (SELECT p.attempts FROM progress p WHERE p.user_id = ? AND p.activity_id = a.activity_id LIMIT 1) AS attempts,
            (SELECT p.completed FROM progress p WHERE p.user_id = ? AND p.activity_id = a.activity_id LIMIT 1) AS progress_completed
     FROM student_assignments sa
     JOIN assignments a ON sa.assignment_id = a.assignment_id
     LEFT JOIN users u ON a.teacher_id = u.user_id
     LEFT JOIN activities act ON a.activity_id = act.activity_id
     LEFT JOIN modules m ON act.module_id = m.module_id
     WHERE sa.student_id = ?
     ORDER BY sa.status ASC, a.due_date ASC, a.created_at DESC",
    [$learner_id, $learner_id, $learner_id]
);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Activities - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <div>
                <h1 class="activity-title mb-0"><?php echo htmlspecialchars($t['sb_learner_assigned'] ?? 'Assigned Activities'); ?></h1>
                <p class="activity-instruction mb-0"><?php echo $current_lang === 'sw' ? 'Shughuli zilizokupangiwa na mwalimu' : 'Activities your teacher assigned for you'; ?></p>
            </div>
            <?php if (count($assignments) > 0): ?>
                <div class="text-muted" style="font-size:0.9rem;">
                    <?php
                        $pendingCount = count(array_filter($assignments, fn($a) => $a['status'] === 'pending'));
                        $completedCount = count(array_filter($assignments, fn($a) => in_array($a['status'], ['completed','auto_submitted'], true)));
                    ?>
                    <span class="badge bg-warning me-1"><?php echo $pendingCount; ?> <?php echo $current_lang === 'sw' ? 'Zinazosubiri' : 'Pending'; ?></span>
                    <span class="badge bg-success"><?php echo $completedCount; ?> <?php echo $current_lang === 'sw' ? 'Zimekamilishwa' : 'Completed'; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$trialInfo['is_active']): ?>
            <div class="text-center py-5">
                <i class="fas fa-lock mb-3" style="font-size:4rem;color:#dc2626;"></i>
                <h4><?php echo $current_lang === 'sw' ? 'Huduma Imezuiwa' : 'Access Blocked'; ?></h4>
                <p class="text-muted mb-4"><?php echo $current_lang === 'sw' ? 'Tafadhali lipa 1,500 TZS ili kuendelea na masomo.' : 'Please pay 1,500 TZS to continue learning.'; ?></p>
                <a href="../payment" class="btn btn-danger btn-lg fw-bold px-5" style="border-radius:50px;">
                    <i class="fas fa-wallet me-2"></i> <?php echo $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now'; ?>
                </a>
            </div>
        <?php elseif (empty($assignments)): ?>
            <div class="dashboard-card text-center">
                <i class="fas fa-clipboard-check" style="font-size:4rem;color:var(--primary-green);"></i>
                <p class="mt-20 activity-instruction"><?php echo $current_lang === 'sw' ? 'Hakuna shughuli zilizopangwa bado. Anza kujifunza!' : 'No assignments yet. Start learning!'; ?></p>
                <a href="categories?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-20"><?php echo htmlspecialchars($t['nav_start'] ?? 'Start Learning'); ?></a>
            </div>
        <?php else: ?>
            <div class="row-child">
                <?php foreach ($assignments as $a):
                    $is_quiz = ($a['assignment_type'] ?? '') === 'quiz';
                    $has_questions = !empty($a['total_questions']) && $a['total_questions'] > 0;
                    $has_started = !empty($a['attempts']) && $a['attempts'] > 0;
                    $is_completed = in_array($a['status'], ['completed', 'auto_submitted', 'expired'], true);
                    $status_display = $is_completed ? $a['status'] : ($has_started ? 'in_progress' : ($a['status'] ?? 'pending'));
                    $status_label = $current_lang === 'sw'
                        ? ($status_display === 'completed' ? 'Imekamilika' : ($status_display === 'auto_submitted' ? 'Imewasilishwa Kiotomatiki' : ($status_display === 'expired' ? 'Muda Umeisha' : ($status_display === 'in_progress' ? 'Inaendelea' : 'Inasubiri'))))
                        : ($status_display === 'completed' ? 'Completed' : ($status_display === 'auto_submitted' ? 'Auto Submitted' : ($status_display === 'expired' ? 'Expired' : ($status_display === 'in_progress' ? 'In Progress' : 'Pending'))));
                    $status_bg = $status_display === 'completed' ? 'var(--primary-green)' : ($status_display === 'auto_submitted' ? '#17a2b8' : ($status_display === 'expired' ? 'var(--primary-red)' : ($status_display === 'in_progress' ? 'var(--primary-blue)' : 'var(--primary-yellow)')));
                ?>
                <div class="col-child-3 mb-20">
                    <article class="dashboard-card assigned-card" style="border-left:6px solid <?php echo htmlspecialchars($a['module_color'] ?? 'var(--primary-blue)'); ?>;">
                        <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                        <?php if ($a['activity_name']): ?>
                            <p style="color:var(--text-light);"><?php echo htmlspecialchars($a['activity_name']); ?></p>
                            <p><small><?php echo htmlspecialchars($a['module_name'] ?? ''); ?></small></p>
                        <?php else: ?>
                            <p style="color:var(--text-light);"><?php echo htmlspecialchars($a['description'] ?? ''); ?></p>
                        <?php endif; ?>
                        <p><small><?php echo $current_lang === 'sw' ? 'Mwalimu' : 'Teacher'; ?>: <?php echo htmlspecialchars($a['teacher_first'] ?? 'N/A'); ?></small></p>
                        <?php if ($a['due_date']): ?><p><small><?php echo $current_lang === 'sw' ? 'Tarehe' : 'Due'; ?>: <?php echo htmlspecialchars(date('M d, Y', strtotime($a['due_date']))); ?></small></p><?php endif; ?>
                        <span style="font-weight:600;"><?php echo $status_label; ?></span>

                        <?php if ($has_questions && !$is_completed): ?>
                            <p class="mt-10 mb-0"><small><?php echo $current_lang === 'sw' ? 'Maswali' : 'Questions'; ?>: <?php echo (int) ($a['answered_questions'] ?? 0); ?>/<?php echo (int) ($a['total_questions'] ?? 0); ?></small></p>
                            <?php if (!empty($a['progress_percentage'])): ?>
                                <div class="progress mt-2" style="height:6px;border-radius:3px;">
                                    <div class="progress-bar bg-info" style="width:<?php echo (int) $a['progress_percentage']; ?>%;" role="progressbar"></div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($is_completed): ?>
                            <?php if (!is_null($a['score'])): ?>
                                <p class="mt-10 mb-0"><small><?php echo $current_lang === 'sw' ? 'Alama' : 'Score'; ?>: <strong><?php echo (int) ($a['score'] ?? 0); ?>%</strong></small></p>
                            <?php endif; ?>
                            <?php if ($has_questions): ?>
                                <p class="mb-0"><small><?php echo $current_lang === 'sw' ? 'Jibu' : 'Answered'; ?>: <?php echo (int) ($a['answered_questions'] ?? 0); ?>/<?php echo (int) ($a['total_questions'] ?? 0); ?></small></p>
                            <?php endif; ?>
                            <a href="assigned?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-secondary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-eye me-1"></i><?php echo $current_lang === 'sw' ? 'Angalia Matokeo' : 'Review'; ?>
                            </a>
                        <?php elseif ($is_quiz && $has_questions): ?>
                            <a href="take-assignment?sa_id=<?php echo (int) $a['student_assignment_id']; ?>&lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-<?php echo $has_started ? 'play-circle' : 'play'; ?> me-1"></i>
                                <?php echo $current_lang === 'sw' ? ($has_started ? 'Endelea' : 'Anza') : ($has_started ? 'Continue' : 'Start'); ?>
                            </a>
                        <?php elseif ($a['activity_id']): ?>
                            <a href="activity?activity_id=<?php echo (int) $a['activity_id']; ?>&lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-<?php echo $has_started ? 'play-circle' : 'play'; ?> me-1"></i>
                                <?php echo $current_lang === 'sw' ? ($has_started ? 'Endelea' : 'Anza') : ($has_started ? 'Continue' : 'Start'); ?>
                            </a>
                        <?php else: ?>
                            <a href="categories?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-play me-1"></i><?php echo $current_lang === 'sw' ? 'Anza Kujifunza' : 'Start Learning'; ?>
                            </a>
                        <?php endif; ?>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>



