<?php
session_start();
require_once '__DIR__ . '/../php/db_connection.php';
require_once '__DIR__ . '/../php/includes/lang.php';
require_once '__DIR__ . '/../php/includes/auth.php';

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

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../php/includes/paths-script.php'; ?>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
</body>
</html>
