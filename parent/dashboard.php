<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login.php');
    exit;
}

$parent_id = $_SESSION['user_id'];

// Children linked via claim code (parent_student_links) or legacy parent_id
$children = $database->fetchAll("
    SELECT DISTINCT u.*,
           (SELECT COUNT(*) FROM progress p WHERE p.user_id = u.user_id AND p.completed = 1) as completed_activities,
           (SELECT SUM(p.stars_earned) FROM progress p WHERE p.user_id = u.user_id) as total_stars
    FROM users u
    LEFT JOIN parent_student_links psl ON psl.student_id = u.user_id AND psl.parent_id = ? AND psl.is_active = 1
    WHERE u.role = 'learner' AND (u.parent_id = ? OR psl.link_id IS NOT NULL)
    ORDER BY u.created_at DESC
", [$parent_id, $parent_id]);

// Fetch badges earned by children
$badges = [];
foreach ($children as $child) {
    $child_badges = $database->fetchAll("
        SELECT b.* 
        FROM badges b
        JOIN user_badges ub ON b.badge_id = ub.badge_id
        WHERE ub.user_id = ?
    ", [$child['user_id']]);
    $badges[$child['user_id']] = $child_badges;
}

$child_ids = array_column($children, 'user_id');
$recent_activity = [];
if (!empty($child_ids)) {
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $recent_activity = $database->fetchAll("
        SELECT p.*, u.first_name, u.last_name, a.activity_name, m.module_name
        FROM progress p
        JOIN users u ON p.user_id = u.user_id
        JOIN activities a ON p.activity_id = a.activity_id
        JOIN modules m ON a.module_id = m.module_id
        WHERE u.user_id IN ($placeholders)
        ORDER BY p.last_attempt_at DESC
        LIMIT 10
    ", $child_ids);
}

// Fetch assignments for all children
$assignments = [];
if (!empty($child_ids)) {
    $placeholders = implode(',', array_fill(0, count($child_ids), '?'));
    $assignments = $database->fetchAll("
        SELECT sa.*, a.title, a.description, a.due_date, a.assignment_type, u.first_name, u.last_name,
               act.activity_id, act.activity_name, m.module_name, m.module_color
        FROM student_assignments sa
        JOIN assignments a ON sa.assignment_id = a.assignment_id
        JOIN users u ON sa.student_id = u.user_id
        LEFT JOIN activities act ON a.activity_id = act.activity_id
        LEFT JOIN modules m ON act.module_id = m.module_id
        WHERE sa.student_id IN ($placeholders)
        ORDER BY a.due_date ASC, a.created_at DESC
    ", $child_ids);
}

require_once '../php/includes/lang.php';
$current_lang = $_SESSION['lang'] ?? 'en';
$base_path = '../';
$active_nav = 'parent_dashboard';
$lang_page = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
<?php
$dashboard_role = 'parent';
$sidebar_active = 'dashboard';
$dashboard_page_title = 'Parent Dashboard';
include '../php/includes/dashboard-start.php';
?>

        <!-- Claim Child Button -->
        <?php if (empty($children)): ?>
            <div class="text-center mb-30">
                <div class="alert-child alert-child-info">
                    <i class="fas fa-info-circle me-2"></i>
                    No children linked to your account yet. Use the claim code provided by the teacher to link your child!
                </div>
                <button class="btn-child btn-child-primary btn-child-large" onclick="showClaimChildModal()">
                    <i class="fas fa-key me-2"></i>Claim Child
                </button>
            </div>
        <?php endif; ?>

        <!-- Children Cards -->
        <?php if (!empty($children)): ?>
            <div class="row-child mb-30">
                <?php foreach ($children as $child): ?>
                    <div class="col-child-3">
                        <div class="dashboard-card">
                            <div class="text-center mb-20">
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-blue); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                    <i class="fas fa-child"></i>
                                </div>
                                <h3 style="font-size: 1.5rem; margin-top: 15px;">
                                    <?php echo htmlspecialchars($child['first_name']); ?>
                                </h3>
                            </div>
                            
                            <div style="text-align: center; margin-bottom: 20px;">
                                <p style="margin: 10px 0; font-size: 1.1rem;">
                                    <i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>
                                    Activities: <?php echo $child['completed_activities']; ?>
                                </p>
                                <p style="margin: 10px 0; font-size: 1.1rem;">
                                    <i class="fas fa-star me-2" style="color: var(--primary-yellow);"></i>
                                    Stars: <?php echo $child['total_stars']; ?>
                                </p>
                            </div>

                            <!-- Badges -->
                            <?php if (isset($badges[$child['user_id']]) && !empty($badges[$child['user_id']])): ?>
                                <div class="badge-container" style="margin-bottom: 20px;">
                                    <?php foreach ($badges[$child['user_id']] as $badge): ?>
                                        <div class="badge" style="background: <?php echo $badge['badge_color']; ?>;" title="<?php echo htmlspecialchars($badge['badge_name']); ?>">
                                            <i class="fas <?php echo $badge['badge_icon']; ?>"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <button class="btn-child btn-child-primary" style="min-height: 40px; min-width: 40px; font-size: 0.9rem;" onclick="viewChildProgress(<?php echo $child['user_id']; ?>)">
                                <i class="fas fa-chart-line"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Claim Another Child Card -->
                <div class="col-child-3">
                    <div class="dashboard-card" style="display: flex; align-items: center; justify-content: center; min-height: 300px; cursor: pointer; border: 3px dashed var(--primary-blue);" onclick="showClaimChildModal()">
                        <div class="text-center">
                            <i class="fas fa-key" style="font-size: 4rem; color: var(--primary-blue);"></i>
                            <h3 style="margin-top: 20px; color: var(--primary-blue);">Claim Another Child</h3>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <?php if (!empty($recent_activity)): ?>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="dashboard-card-title">Recent Activity</h3>
                </div>
                <div>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div style="padding: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 15px;">
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: var(--primary-green); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                <i class="fas fa-child"></i>
                            </div>
                            <div style="flex: 1;">
                                <p style="margin: 0; font-weight: 600;">
                                    <?php echo htmlspecialchars($activity['first_name']); ?>
                                </p>
                                <p style="margin: 5px 0 0 0; color: var(--text-light); font-size: 0.9rem;">
                                    Completed: <?php echo htmlspecialchars($activity['activity_name']); ?> (<?php echo htmlspecialchars($activity['module_name']); ?>)
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <span style="background: var(--primary-green); color: white; padding: 5px 10px; border-radius: 10px; font-size: 0.9rem;">
                                    <?php echo $activity['score']; ?>%
                                </span>
                                <p style="margin: 5px 0 0 0; color: var(--text-light); font-size: 0.8rem;">
                                    <?php echo date('M d, H:i', strtotime($activity['last_attempt_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Teacher Assignments -->
        <?php if (!empty($assignments)): ?>
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h3 class="dashboard-card-title">Teacher Assignments</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: var(--background-light);">
                                <th style="padding: 12px; text-align: left;">Child</th>
                                <th style="padding: 12px; text-align: left;">Assignment</th>
                                <th style="padding: 12px; text-align: left;">Type</th>
                                <th style="padding: 12px; text-align: left;">Status</th>
                                <th style="padding: 12px; text-align: left;">Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 12px;">
                                        <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                        <?php if ($assignment['activity_name']): ?>
                                            <small style="color: var(--text-light);"><?php echo htmlspecialchars($assignment['activity_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: var(--primary-purple); color: white; padding: 4px 10px; border-radius: 10px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars(ucfirst($assignment['assignment_type'])); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <span style="background: 
                                            <?php 
                                                echo match($assignment['status']) {
                                                    'completed' => 'var(--primary-green)',
                                                    'in_progress' => 'var(--primary-blue)',
                                                    'overdue' => 'var(--primary-red)',
                                                    default => 'var(--primary-yellow)'
                                                }; 
                                            ?>; 
                                            color: white; padding: 4px 10px; border-radius: 10px; font-size: 0.85rem;">
                                            <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $assignment['status']))); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <?php echo $assignment['due_date'] ? date('M d, Y', strtotime($assignment['due_date'])) : '—'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
<?php include '../php/includes/dashboard-end.php'; ?>
<!-- Claim Child Modal -->
    <div id="claimChildModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div class="activity-container" style="max-width: 500px; position: relative;">
            <button onclick="hideClaimChildModal()" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                <i class="fas fa-times"></i>
            </button>
            <h2 class="activity-title text-center">Claim Child</h2>
            <p class="activity-instruction text-center">Enter the claim code provided by the teacher</p>
            <form method="POST" action="claim-child">
                <div class="form-group-child">
                    <label class="form-label-child">Claim Code</label>
                    <input type="text" class="form-control-child" name="claim_code" required
                           placeholder="KH-XXXXXX" maxlength="9"
                           style="text-transform: uppercase; letter-spacing: 2px; font-size: 1.2rem; text-align: center;">
                    <small style="color: var(--text-light);">Format: KH-XXXXXX (e.g., KH-7F92K1)</small>
                </div>
                <div class="text-center mt-30">
                    <button type="submit" class="btn-child btn-child-primary btn-child-large">
                        <i class="fas fa-key me-2"></i>Claim Child
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script src="../js/modals.js"></script>
    <script>
        <?php if (!empty($_GET['claim'])): ?>
        document.addEventListener('DOMContentLoaded', function(){ showClaimChildModal(); });
        <?php endif; ?>
        function showClaimChildModal() {
            document.getElementById('claimChildModal').style.display = 'flex';
            document.getElementById('claimChildModal').classList.add('is-open');
        }

        function hideClaimChildModal() {
            document.getElementById('claimChildModal').style.display = 'none';
        }

        function viewChildProgress(childId) {
            window.location.href = 'child-progress.php?child_id=' + childId;
        }
    </script>
</body>
</html>
