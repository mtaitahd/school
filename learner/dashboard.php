<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/auth.php';

auth_require_role(['learner'], 'login.php');

$learner_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'learner';
$sidebar_active = 'dashboard';
$dashboard_page_title = $current_lang === 'sw' ? 'Dashibodi Yangu' : 'My Dashboard';
$lang_page = 'dashboard.php';

$stats = $database->fetchOne(
    "SELECT
        (SELECT COUNT(*) FROM progress WHERE user_id = ? AND completed = 1) AS completed,
        (SELECT COALESCE(SUM(stars_earned), 0) FROM progress WHERE user_id = ?) AS stars,
        (SELECT COUNT(*) FROM student_assignments WHERE student_id = ? AND status NOT IN ('completed', 'graded')) AS pending_assignments",
    [$learner_id, $learner_id, $learner_id]
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

        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-green);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-check text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) $stats['completed']; ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Zimekamilika' : 'Completed'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-yellow);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-star text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) $stats['stars']; ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Nyota' : 'Stars'; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="card h-100 py-2 text-center">
                    <div class="card-body">
                        <div class="icon-circle mb-3" style="background:var(--primary-blue);width:56px;height:56px;font-size:1.5rem;margin:0 auto;"><i class="fas fa-clipboard-list text-white"></i></div>
                        <p style="font-size:2rem;font-weight:700;margin:0;color:var(--text-dark);"><?php echo (int) $stats['pending_assignments']; ?></p>
                        <p class="text-muted mb-0"><?php echo $current_lang === 'sw' ? 'Zilizopangwa' : 'Assigned'; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-3 col-md-6">
                <a href="categories.php?lang=<?php echo $current_lang; ?>" class="card h-100 py-2 text-center text-decoration-none" style="display:block;">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                        <i class="fas fa-play-circle mb-3" style="font-size:3rem;color:var(--primary-green);"></i>
                        <h5 class="fw-bold mb-0" style="color:var(--text-dark);"><?php echo htmlspecialchars($t['nav_start'] ?? 'Start Learning'); ?></h5>
                    </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6">
                <a href="assigned.php?lang=<?php echo $current_lang; ?>" class="card h-100 py-2 text-center text-decoration-none" style="display:block;">
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
                <a href="assigned.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-secondary" style="margin-left:auto; min-height:35px; font-size:0.85rem; padding:6px 14px; text-decoration:none;">
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
                        <span class="badge" style="background:<?php echo $status_bg; ?>;padding:4px 10px;border-radius:12px;"><?php echo $status_label; ?></span>
                        <?php if ($is_completed): ?>
                            <?php if (!is_null($a['score'])): ?>
                                <div style="margin-top:8px;"><small><?php echo $current_lang === 'sw' ? 'Alama' : 'Score'; ?>: <strong><?php echo (int)$a['score']; ?>%</strong></small></div>
                            <?php endif; ?>
                            <span class="btn-child btn-child-secondary mt-10" style="display:inline-block; cursor:default; opacity:0.9; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-check me-1"></i><?php echo $current_lang === 'sw' ? 'Umefanya' : 'Done'; ?>
                            </span>
                        <?php elseif ($a['activity_id']): ?>
                            <a href="activity.php?activity_id=<?php echo (int) $a['activity_id']; ?>&lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
                                <i class="fas fa-<?php echo $has_started ? 'play-circle' : 'play'; ?> me-1"></i>
                                <?php echo $current_lang === 'sw' ? ($has_started ? 'Endelea' : 'Anza') : ($has_started ? 'Continue' : 'Start'); ?>
                            </a>
                        <?php else: ?>
                            <a href="categories.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block; text-decoration:none; font-size:0.85rem; padding:6px 14px;">
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

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../php/includes/paths-script.php'; ?>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
