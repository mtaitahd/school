<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/lang.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/migrate.php';
ensure_schema_v2($database);

auth_require_role(['learner'], 'login.php');

$learner_id = auth_user_id();
$base_path = '../';
$dashboard_role = 'learner';
$sidebar_active = 'assigned';
$lang_page = 'assigned.php';

$assignments = $database->fetchAll(
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

        <h1 class="activity-title"><?php echo htmlspecialchars($t['sb_learner_assigned'] ?? 'Assigned Activities'); ?></h1>
        <p class="activity-instruction mb-30"><?php echo $current_lang === 'sw' ? 'Shughuli zilizokupangiwa na mwalimu' : 'Activities your teacher assigned for you'; ?></p>

        <?php if (empty($assignments)): ?>
            <div class="dashboard-card text-center">
                <i class="fas fa-clipboard-check" style="font-size:4rem;color:var(--primary-green);"></i>
                <p class="mt-20 activity-instruction"><?php echo $current_lang === 'sw' ? 'Hakuna shughuli zilizopangwa bado. Anza kujifunza!' : 'No assignments yet. Start learning!'; ?></p>
                <a href="categories.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-20"><?php echo htmlspecialchars($t['nav_start'] ?? 'Start Learning'); ?></a>
            </div>
        <?php else: ?>
            <div class="row-child">
                <?php foreach ($assignments as $a):
                    $has_started = !empty($a['attempts']) && $a['attempts'] > 0;
                    $is_completed = ($a['progress_completed'] ?? 0) == 1 || $a['status'] === 'completed' || $a['status'] === 'graded';
                    $status_display = $is_completed ? 'completed' : ($has_started ? 'in_progress' : ($a['status'] ?? 'pending'));
                    $status_label = $current_lang === 'sw'
                        ? ($status_display === 'completed' ? 'Imekamilika' : ($status_display === 'in_progress' ? 'Inaendelea' : 'Inasubiri'))
                        : ($status_display === 'completed' ? 'Completed' : ($status_display === 'in_progress' ? 'In Progress' : 'Pending'));
                    $status_bg = $status_display === 'completed' ? 'var(--primary-green)' : ($status_display === 'in_progress' ? 'var(--primary-blue)' : 'var(--primary-yellow)');
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
                        <span class="badge" style="background:<?php echo $status_bg; ?>;padding:4px 10px;border-radius:12px;"><?php echo $status_label; ?></span>
                        <?php if ($is_completed): ?>
                            <?php if (!is_null($a['score'])): ?>
                                <p class="mt-10 mb-0"><small><?php echo $current_lang === 'sw' ? 'Alama' : 'Score'; ?>: <strong><?php echo (int) ($a['score'] ?? 0); ?>%</strong></small></p>
                            <?php endif; ?>
                            <span class="btn-child btn-child-secondary mt-10" style="display:inline-block; cursor:default; opacity:0.9;"><?php echo $current_lang === 'sw' ? 'Imefanyika' : 'Done'; ?></span>
                        <?php else: ?>
                            <?php if ($a['activity_id']): ?>
                                <a href="activity.php?activity_id=<?php echo (int) $a['activity_id']; ?>&lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block;">
                                    <i class="fas fa-<?php echo $has_started ? 'play-circle' : 'play'; ?> me-2"></i>
                                    <?php echo $current_lang === 'sw' ? ($has_started ? 'Endelea' : 'Anza') : ($has_started ? 'Continue' : 'Start'); ?>
                                </a>
                            <?php else: ?>
                                <a href="categories.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-primary mt-10" style="display:inline-block;">
                                    <i class="fas fa-play me-2"></i><?php echo $current_lang === 'sw' ? 'Anza Kujifunza' : 'Start Learning'; ?>
                                </a>
                            <?php endif; ?>
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



