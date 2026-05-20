<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/includes/lang.php';
require_once '../php/includes/auth.php';

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
        (SELECT COUNT(*) FROM student_assignments WHERE student_id = ? AND status != 'completed') AS pending_assignments",
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
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-card-icon" style="background:var(--primary-green);margin:0 auto 12px;"><i class="fas fa-check"></i></div>
                    <p style="font-size:2rem;font-weight:700;margin:0;"><?php echo (int) $stats['completed']; ?></p>
                    <p><?php echo $current_lang === 'sw' ? 'Zimekamilika' : 'Completed'; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-card-icon" style="background:var(--primary-yellow);margin:0 auto 12px;"><i class="fas fa-star"></i></div>
                    <p style="font-size:2rem;font-weight:700;margin:0;"><?php echo (int) $stats['stars']; ?></p>
                    <p><?php echo $current_lang === 'sw' ? 'Nyota' : 'Stars'; ?></p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <div class="dashboard-card-icon" style="background:var(--primary-blue);margin:0 auto 12px;"><i class="fas fa-clipboard-list"></i></div>
                    <p style="font-size:2rem;font-weight:700;margin:0;"><?php echo (int) $stats['pending_assignments']; ?></p>
                    <p><?php echo $current_lang === 'sw' ? 'Zilizopangwa' : 'Assigned'; ?></p>
                </div>
            </div>
        </div>

        <div class="row-child">
            <div class="col-child-3">
                <a href="categories.php?lang=<?php echo $current_lang; ?>" class="dashboard-card parent-card" style="display:block;text-decoration:none;text-align:center;">
                    <i class="fas fa-play-circle" style="font-size:3rem;color:var(--primary-green);"></i>
                    <h3 class="mt-20"><?php echo htmlspecialchars($t['nav_start'] ?? 'Start Learning'); ?></h3>
                </a>
            </div>
            <div class="col-child-3">
                <a href="assigned.php?lang=<?php echo $current_lang; ?>" class="dashboard-card parent-card" style="display:block;text-decoration:none;text-align:center;">
                    <i class="fas fa-tasks" style="font-size:3rem;color:var(--primary-blue);"></i>
                    <h3 class="mt-20"><?php echo htmlspecialchars($t['sb_learner_assigned'] ?? 'Assigned Activities'); ?></h3>
                </a>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../php/includes/paths-script.php'; ?>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
