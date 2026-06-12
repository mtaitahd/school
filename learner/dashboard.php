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
$sidebar_active = 'dashboard';
$dashboard_page_title = $current_lang === 'sw' ? 'Dashibodi Yangu' : 'My Dashboard';
$lang_page = 'dashboard.php';

$trialInfo = SubscriptionMiddleware::getLearnerTrialInfo($learner_id);

$stats = $database->fetchOne(
    "SELECT
        (SELECT COUNT(*) FROM progress WHERE user_id = ? AND completed = 1) AS completed,
        (SELECT COALESCE(SUM(stars_earned), 0) FROM progress WHERE user_id = ?) AS stars,
        (SELECT COUNT(*) FROM student_assignments WHERE student_id = ? AND status = 'pending') AS pending_assignments,
        (SELECT COUNT(*) FROM student_assignments WHERE student_id = ? AND status IN ('completed','auto_submitted')) AS completed_assignments,
        (SELECT COUNT(*) FROM student_assignments WHERE student_id = ? AND status = 'in_progress') AS in_progress_assignments",
    [$learner_id, $learner_id, $learner_id, $learner_id, $learner_id]
);

// Average score from completed assignments
$avgScore = $database->fetchOne(
    "SELECT COALESCE(ROUND(AVG(score), 0), 0) AS avg_score
     FROM student_assignments
     WHERE student_id = ? AND status IN ('completed','auto_submitted') AND score IS NOT NULL",
    [$learner_id]
);

$recent_assignments = $database->fetchAll(
    "SELECT a.*, sa.status, sa.score, sa.student_assignment_id, sa.submitted_at,
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
     ORDER BY sa.status ASC, a.due_date ASC, a.created_at DESC
     LIMIT 5",
    [$learner_id, $learner_id, $learner_id]
);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learner Dashboard - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <!-- Trial / Subscription Banner -->
        <?php if ($trialInfo['status'] === 'trial'): ?>
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-clock me-2"></i>
                    <strong><?php echo $current_lang === 'sw' ? 'Majaribio ya Bure' : 'Free Trial'; ?></strong> :
                    <?php if ($trialInfo['days_remaining'] > 0): ?>
                        <?php echo $current_lang === 'sw' ? 'Umesalia siku' : 'You have'; ?> <strong><?php echo $trialInfo['days_remaining']; ?></strong> <?php echo $current_lang === 'sw' ? 'siku za majaribio' : 'trial days remaining'; ?>.
                    <?php else: ?>
                        <?php echo $current_lang === 'sw' ? 'Muda wa majaribio umeisha. Tafadhali lipa ili kuendelea.' : 'Trial period has ended. Please subscribe to continue.'; ?>
                    <?php endif; ?>
                </div>
                <a href="../payment" class="btn btn-warning btn-sm fw-bold px-4" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?php echo $current_lang === 'sw' ? 'Lipa Sasa' : 'Subscribe Now'; ?> : 1,500 TZS
                </a>
            </div>
        <?php elseif ($trialInfo['status'] === 'active' && $trialInfo['days_remaining'] <= 7): ?>
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong><?php echo $current_lang === 'sw' ? 'Uanachama Unakaribia Kuisha' : 'Subscription Expiring'; ?></strong> :
                    <?php echo $current_lang === 'sw' ? 'Siku zilizobaki' : 'Days remaining'; ?>: <strong><?php echo $trialInfo['days_remaining']; ?></strong>
                </div>
                <a href="../payment" class="btn btn-outline-warning btn-sm fw-bold px-3" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?php echo $current_lang === 'sw' ? 'Jaza Salio' : 'Topup'; ?>
                </a>
            </div>
        <?php elseif (!$trialInfo['is_active']): ?>
            <div class="alert alert-danger d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 mb-4" style="border-radius:10px;border:none;">
                <div>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong><?php echo $current_lang === 'sw' ? 'Huduma Imezuiwa' : 'Access Blocked'; ?></strong> :
                    <?php echo $current_lang === 'sw' ? 'Tafadhali lipa 1,500 TZS ili kuendelea.' : 'Please pay 1,500 TZS to continue.'; ?>
                </div>
                <a href="../payment" class="btn btn-danger btn-sm fw-bold px-4" style="border-radius:50px;">
                    <i class="fas fa-wallet me-1"></i> <?php echo $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now'; ?>
                </a>
            </div>
        <?php elseif ($trialInfo['is_active']): ?><!-- intentionally empty --><?php endif; ?>

        <?php if (!$trialInfo['is_active']): ?>
            <div class="text-center py-5">
                <i class="fas fa-lock mb-3" style="font-size:4rem;color:#dc2626;"></i>
                <h4><?php echo $current_lang === 'sw' ? 'Huduma Imezuiwa' : 'Access Blocked'; ?></h4>
                <p class="text-muted mb-4"><?php echo $current_lang === 'sw' ? 'Tafadhali lipa 1,500 TZS ili kuendelea na masomo.' : 'Please pay 1,500 TZS to continue learning.'; ?></p>
                <a href="../payment" class="btn btn-danger btn-lg fw-bold px-5" style="border-radius:50px;">
                    <i class="fas fa-wallet me-2"></i> <?php echo $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now'; ?>
                </a>
            </div>
        <?php else: ?>

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-green);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-check text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) ($stats['completed_assignments'] ?? 0); ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Zimekamilishwa' : 'Completed'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-yellow);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-star text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) ($stats['stars'] ?? 0); ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Nyota' : 'Stars'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-blue);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-clipboard-list text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) ($stats['pending_assignments'] ?? 0); ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Zinazosubiri' : 'Pending'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-orange);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-chart-line text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) ($avgScore['avg_score'] ?? 0); ?>%</p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Wastani wa Alama' : 'Avg Score'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <a href="categories?lang=<?php echo $current_lang; ?>" class="card h-100 py-2 text-center text-decoration-none" style="display:block;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-play-circle mb-3" style="font-size:3rem;color:var(--primary-green);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--text-dark);"><?php echo htmlspecialchars($t['nav_start'] ?? 'Start Learning'); ?></h5>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="assigned?lang=<?php echo $current_lang; ?>" class="card h-100 py-2 text-center text-decoration-none" style="display:block;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-tasks mb-3" style="font-size:3rem;color:var(--primary-blue);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--text-dark);"><?php echo htmlspecialchars($t['sb_learner_assigned'] ?? 'Assigned Activities'); ?></h5>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Assignments -->
        <?php if (!empty($recent_assignments)): ?>
        <div class="dashboard-card mt-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="dashboard-card-title"><?php echo $current_lang === 'sw' ? 'Shughuli Zilizopangwa Hivi Karibuni' : 'Recent Assignments'; ?></h3>
                <a href="assigned?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-secondary" style="margin-left:auto; min-height:35px; font-size:0.85rem; padding:6px 14px; text-decoration:none;">
                    <?php echo $current_lang === 'sw' ? 'Zote' : 'View All'; ?> <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="row-child">
                <?php foreach ($recent_assignments as $a):
                    $has_started = !empty($a['attempts']) && $a['attempts'] > 0;
                    $is_completed = ($a['progress_completed'] ?? 0) == 1 || $a['status'] === 'completed' || $a['status'] === 'graded';
                    $status_display = $is_completed ? 'completed' : ($has_started ? 'in_progress' : ($a['status'] ?? 'pending'));
                ?>
                <div class="col-child-3 mb-20">
                    <article class="dashboard-card assigned-card" style="border-left:6px solid <?php echo htmlspecialchars($a['module_color'] ?? 'var(--primary-blue)'); ?>; height:100%;">
                        <h3 style="font-size:1.1rem;"><?php echo htmlspecialchars($a['title']); ?></h3>
                        <?php if ($a['activity_name']): ?>
                            <p style="color:var(--text-light); margin-bottom:4px; font-size:0.9rem;"><?php echo htmlspecialchars($a['activity_name']); ?></p>
                            <p style="margin-bottom:4px;"><small><?php echo htmlspecialchars($a['module_name'] ?? ''); ?></small></p>
                        <?php endif; ?>
                        <?php if ($a['due_date']): ?>
                            <p style="margin-bottom:4px;"><small><?php echo $current_lang === 'sw' ? 'Tarehe' : 'Due'; ?>: <?php echo htmlspecialchars(date('M d, Y', strtotime($a['due_date']))); ?></small></p>
                        <?php endif; ?>
                        <p style="margin-bottom:8px;"><small><?php echo $current_lang === 'sw' ? 'Mwalimu' : 'Teacher'; ?>: <?php echo htmlspecialchars($a['teacher_first'] ?? 'N/A'); ?></small></p>
                        <?php
                            $status_label = $current_lang === 'sw'
                                ? ($status_display === 'completed' ? 'Imekamilika' : ($status_display === 'in_progress' ? 'Inaendelea' : 'Inasubiri'))
                                : ($status_display === 'completed' ? 'Completed' : ($status_display === 'in_progress' ? 'In Progress' : 'Pending'));
                            $status_bg = $status_display === 'completed' ? 'var(--primary-green)' : ($status_display === 'in_progress' ? 'var(--primary-blue)' : 'var(--primary-yellow)');
                        ?>
                        <span style="font-weight:600;"><?php echo $status_label; ?></span>
                        <?php if ($is_completed): ?>
                            <?php if (!is_null($a['score'])): ?>
                                <div style="margin-top:8px;"><small><?php echo $current_lang === 'sw' ? 'Alama' : 'Score'; ?>: <strong><?php echo (int)$a['score']; ?>%</strong></small></div>
                            <?php endif; ?>
                            <span class="btn-child btn-child-secondary mt-10" style="display:inline-block; cursor:default; opacity:0.9; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-check me-1"></i><?php echo $current_lang === 'sw' ? 'Umefanya' : 'Done'; ?>
                            </span>
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
        </div>
        <?php else: ?>
        <div class="dashboard-card mt-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h3 class="dashboard-card-title"><?php echo $current_lang === 'sw' ? 'Shughuli Zilizopangwa' : 'Assignments'; ?></h3>
            </div>
            <div class="text-center py-4">
                <i class="fas fa-check-circle" style="font-size:4rem;color:var(--primary-green);"></i>
                <p class="activity-instruction mt-20"><?php echo $current_lang === 'sw' ? 'Hakuna shughuli zilizopangwa kwa sasa' : 'No assignments at the moment'; ?></p>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../php/includes/paths-script.php'; ?>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
