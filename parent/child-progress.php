<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in (teacher or parent)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

if ($child_id === 0) {
    header('Location: ../index.php');
    exit;
}

// Fetch child details
$child = $database->fetchOne("SELECT * FROM users WHERE user_id = ? AND role = 'learner'", [$child_id]);

if (!$child) {
    header('Location: ../index.php');
    exit;
}

if ($_SESSION['role'] === 'parent') {
    $parent_id = (int) $_SESSION['user_id'];
    $linked = $database->fetchOne(
        "SELECT 1 FROM parent_student_links WHERE parent_id = ? AND student_id = ? AND is_active = 1
         UNION SELECT 1 FROM users WHERE user_id = ? AND parent_id = ? LIMIT 1",
        [$parent_id, $child_id, $child_id, $parent_id]
    );
    if (!$linked) {
        header('Location: dashboard.php');
        exit;
    }
}

// Fetch child's progress
$progress_data = $database->fetchAll("
    SELECT p.*, a.activity_name, a.activity_type, m.module_name, m.module_color
    FROM progress p
    JOIN activities a ON p.activity_id = a.activity_id
    JOIN modules m ON a.module_id = m.module_id
    WHERE p.user_id = ?
    ORDER BY p.last_attempt_at DESC
", [$child_id]);

// Fetch child's badges
$badges = $database->fetchAll("
    SELECT b.* 
    FROM badges b
    JOIN user_badges ub ON b.badge_id = ub.badge_id
    WHERE ub.user_id = ?
", [$child_id]);

// Calculate statistics
$total_activities = count($progress_data);
$completed_activities = count(array_filter($progress_data, fn($p) => $p['completed'] == 1));
$total_stars = array_sum(array_column($progress_data, 'stars_earned'));
$avg_score = $total_activities > 0 ? array_sum(array_column($progress_data, 'score')) / $total_activities : 0;

// Module-wise progress
$module_progress = [];
foreach ($progress_data as $p) {
    $module_name = $p['module_name'];
    if (!isset($module_progress[$module_name])) {
        $module_progress[$module_name] = [
            'color' => $p['module_color'],
            'total' => 0,
            'completed' => 0,
            'avg_score' => 0,
            'scores' => []
        ];
    }
    $module_progress[$module_name]['total']++;
    if ($p['completed']) {
        $module_progress[$module_name]['completed']++;
    }
    $module_progress[$module_name]['scores'][] = $p['score'];
}

foreach ($module_progress as &$module) {
    $module['avg_score'] = count($module['scores']) > 0 ? array_sum($module['scores']) / count($module['scores']) : 0;
}

$assignments = $database->fetchAll(
    "SELECT a.title, a.description, a.due_date, a.assignment_type, sa.status, act.activity_id, act.activity_name, m.module_name, m.module_color
     FROM student_assignments sa
     JOIN assignments a ON sa.assignment_id = a.assignment_id
     LEFT JOIN activities act ON a.activity_id = act.activity_id
     LEFT JOIN modules m ON act.module_id = m.module_id
     WHERE sa.student_id = ?
     ORDER BY a.due_date DESC, a.created_at DESC",
    [$child_id]
);

$activity_assignments = $database->fetchAll("
    SELECT aa.*, act.activity_name, m.module_name
    FROM activity_assignments aa
    JOIN activities act ON aa.activity_id = act.activity_id
    JOIN modules m ON act.module_id = m.module_id
    WHERE aa.learner_id = ?
    ORDER BY aa.assigned_at DESC
", [$child_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Child Progress - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar-modern">
        <div class="container-modern">
            <div class="navbar-content">
                <!-- Left Side - Logo -->
                <div class="navbar-brand-modern">
                    <img src="../assets/images/logo.png" alt="Kona Ya Hisabati Logo" class="navbar-logo">
                    <div class="navbar-brand-text">
                        <span class="brand-main">Kona Ya Hisabati</span>
                        <span class="brand-subtitle">Jifunze • Furahia • Fanikiwa</span>
                    </div>
                </div>

                <!-- Center Menu -->
                <ul class="navbar-menu">
                    <li class="navbar-item">
                        <a href="../index.php" class="navbar-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="navbar-item">
                        <a href="dashboard.php" class="navbar-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="navbar-item active">
                        <a href="#" class="navbar-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Progress</span>
                        </a>
                    </li>
                </ul>

                <!-- Right Side -->
                <div class="navbar-right">
                    <span style="color: white; font-weight: 600; margin-right: 15px;">
                        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                    </span>
                    <a href="../logout.php" class="teacher-login-btn" style="background: var(--primary-red);">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>

                    <!-- Mobile Hamburger -->
                    <button class="hamburger-btn">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-child mt-30">
        <!-- Child Header -->
        <div class="dashboard-card mb-30">
            <div class="text-center">
                <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--primary-blue); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 3rem; margin-bottom: 20px;">
                    <i class="fas fa-child"></i>
                </div>
                <h1 class="activity-title"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?></h1>
                <p class="activity-instruction">Learning Progress Report</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3 class="dashboard-card-title">Activities</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-blue); margin: 0;">
                        <?php echo $completed_activities; ?>/<?php echo $total_activities; ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="dashboard-card-title">Stars</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-green); margin: 0;">
                        <?php echo $total_stars; ?>
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="dashboard-card-title">Avg Score</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-yellow); margin: 0;">
                        <?php echo round($avg_score, 1); ?>%
                    </p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-purple);">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h3 class="dashboard-card-title">Badges</h3>
                    </div>
                    <p style="font-size: 2.5rem; font-weight: 700; color: var(--primary-purple); margin: 0;">
                        <?php echo count($badges); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Badges Section -->
        <?php if (!empty($badges)): ?>
            <div class="dashboard-card mb-30">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                        <i class="fas fa-medal"></i>
                    </div>
                    <h3 class="dashboard-card-title">Earned Badges</h3>
                </div>
                <div class="badge-container">
                    <?php foreach ($badges as $badge): ?>
                        <div class="badge" style="background: <?php echo $badge['badge_color']; ?>;" title="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                            <i class="fas <?php echo $badge['badge_icon']; ?>"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Teacher Assignments -->
        <?php if (!empty($assignments)): ?>
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-orange);"><i class="fas fa-clipboard-list"></i></div>
                <h3 class="dashboard-card-title">Assigned by Teacher</h3>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead><tr style="background:var(--background-light);">
                        <th style="padding:10px;text-align:left;">Assignment</th>
                        <th style="padding:10px;text-align:left;">Activity</th>
                        <th style="padding:10px;text-align:left;">Module</th>
                        <th style="padding:10px;text-align:left;">Status</th>
                        <th style="padding:10px;text-align:left;">Due</th>
                        <th style="padding:10px;text-align:left;">Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($assignments as $as): ?>
                    <tr style="border-bottom:1px solid #eee;">
                        <td style="padding:10px;"><?php echo htmlspecialchars($as['title']); ?></td>
                        <td style="padding:10px;"><?php echo $as['activity_name'] ? htmlspecialchars($as['activity_name']) : '—'; ?></td>
                        <td style="padding:10px;"><?php echo $as['module_name'] ? htmlspecialchars($as['module_name']) : '—'; ?></td>
                        <td style="padding:10px;">
                            <span style="background:var(--primary-blue);color:#fff;padding:4px 10px;border-radius:12px;font-size:0.8rem;"><?php echo htmlspecialchars(ucfirst($as['status'])); ?></span>
                        </td>
                        <td style="padding:10px;"><?php echo $as['due_date'] ? date('M d, Y', strtotime($as['due_date'])) : '—'; ?></td>
                        <td style="padding:10px;">
                            <?php if ($as['activity_id']): ?>
                                <a href="activity-preview.php?activity_id=<?php echo (int) $as['activity_id']; ?>&child_id=<?php echo $child_id; ?>" 
                                   class="btn-child btn-child-primary" style="min-height:35px;min-width:35px;font-size:0.85rem;padding:8px 12px;display:inline-block;text-decoration:none;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Assignments (direct activity assignments to the learner) -->
        <?php if (!empty($activity_assignments)): ?>
            <div class="dashboard-card mb-30">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h3 class="dashboard-card-title">Activity Assignments</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--background-light);">
                                <th style="padding: 12px; text-align: left;">Activity</th>
                                <th style="padding: 12px; text-align: left;">Module</th>
                                <th style="padding: 12px; text-align: left;">Assigned At</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Due</th>
                                <th style="padding: 12px; text-align: left;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activity_assignments as $aa): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($aa['activity_name']); ?></td>
                                    <td style="padding: 12px;"><?php echo htmlspecialchars($aa['module_name'] ?? '—'); ?></td>
                                    <td style="padding: 12px;"><?php echo date('M d, Y H:i', strtotime($aa['assigned_at'])); ?></td>
                                    <td style="padding: 12px;"><span style="background: var(--primary-blue); color:#fff; padding:4px 10px; border-radius:10px; font-size:0.8rem;"><?php echo htmlspecialchars(ucfirst($aa['status'])); ?></span></td>
                                    <td style="padding: 12px;"><?php echo !empty($aa['due_date']) ? date('M d, Y', strtotime($aa['due_date'])) : '—'; ?></td>
                                    <td style="padding: 12px;">
                                        <a href="activity-preview.php?activity_id=<?php echo (int) $aa['activity_id']; ?>&child_id=<?php echo $child_id; ?>" 
                                           class="btn-child btn-child-primary" style="min-height:35px;min-width:35px;font-size:0.85rem;padding:8px 12px;display:inline-block;text-decoration:none;">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Module Progress -->
        <?php if (!empty($module_progress)): ?>
            <div class="dashboard-card mb-30">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-green);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="dashboard-card-title">Progress by Module</h3>
                </div>
                <div class="row-child">
                    <?php foreach ($module_progress as $module_name => $data): ?>
                        <div class="col-child-3">
                            <div class="dashboard-card">
                                <h4 style="color: <?php echo $data['color']; ?>; margin-bottom: 15px;"><?php echo htmlspecialchars($module_name); ?></h4>
                                <div class="progress-bar-child" style="height: 20px;">
                                    <div class="progress-fill" style="width: <?php echo ($data['completed'] / $data['total']) * 100; ?>%; background: <?php echo $data['color']; ?>;">
                                        <?php echo $data['completed']; ?>/<?php echo $data['total']; ?>
                                    </div>
                                </div>
                                <p style="margin-top: 10px; color: var(--text-light); font-size: 0.9rem;">
                                    Avg Score: <?php echo round($data['avg_score'], 1); ?>%
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Detailed Progress -->
        <?php if (!empty($progress_data)): ?>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="dashboard-card-title">Activity History</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--background-light);">
                                <th style="padding: 15px; text-align: left;">Activity</th>
                                <th style="padding: 15px; text-align: left;">Module</th>
                                <th style="padding: 15px; text-align: left;">Score</th>
                                <th style="padding: 15px; text-align: left;">Stars</th>
                                <th style="padding: 15px; text-align: left;">Status</th>
                                <th style="padding: 15px; text-align: left;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress_data as $progress): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 15px;"><?php echo htmlspecialchars($progress['activity_name']); ?></td>
                                    <td style="padding: 15px;">
                                        <span style="background: <?php echo $progress['module_color']; ?>; color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.8rem;">
                                            <?php echo htmlspecialchars($progress['module_name']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px;">
                                        <span style="background: var(--primary-blue); color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.9rem;">
                                            <?php echo $progress['score']; ?>%
                                        </span>
                                    </td>
                                    <td style="padding: 15px;">
                                        <span style="background: var(--primary-yellow); color: var(--text-dark); padding: 5px 10px; border-radius: 10px; font-size: 0.9rem;">
                                            <i class="fas fa-star me-1"></i><?php echo $progress['stars_earned']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px;">
                                        <?php if ($progress['completed']): ?>
                                            <span style="color: var(--primary-green);"><i class="fas fa-check-circle"></i> Completed</span>
                                        <?php else: ?>
                                            <span style="color: var(--primary-orange);"><i class="fas fa-clock"></i> In Progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px;">
                                        <?php echo date('M d, Y', strtotime($progress['last_attempt_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="dashboard-card">
                <div class="text-center">
                    <p class="activity-instruction">No activity data available yet.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>



