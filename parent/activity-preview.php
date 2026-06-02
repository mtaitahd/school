<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: login.php');
    exit;
}

$parent_id = $_SESSION['user_id'];
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
$child_id = isset($_GET['child_id']) ? intval($_GET['child_id']) : 0;

if ($activity_id === 0 || $child_id === 0) {
    header('Location: dashboard.php');
    exit;
}

// Verify parent is linked to this child
$linked = $database->fetchOne(
    "SELECT 1 FROM parent_student_links WHERE parent_id = ? AND student_id = ? AND is_active = 1
     UNION SELECT 1 FROM users WHERE user_id = ? AND parent_id = ? LIMIT 1",
    [$parent_id, $child_id, $child_id, $parent_id]
);

if (!$linked) {
    header('Location: dashboard.php');
    exit;
}

// Fetch activity details
$activity = $database->fetchOne("
    SELECT a.*, m.module_name, m.module_color, m.module_icon 
    FROM activities a 
    JOIN modules m ON a.module_id = m.module_id 
    WHERE a.activity_id = ? AND a.is_active = 1
", [$activity_id]);

if (!$activity) {
    header('Location: dashboard.php');
    exit;
}

// Fetch child details
$child = $database->fetchOne("SELECT * FROM users WHERE user_id = ?", [$child_id]);

$activity_data = json_decode($activity['activity_data'], true) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Preview - Kona Ya Hisabati</title>
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
                        <a href="../index" class="navbar-link">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="navbar-item">
                        <a href="dashboard" class="navbar-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="navbar-item active">
                        <a href="#" class="navbar-link">
                            <i class="fas fa-eye"></i>
                            <span>Preview</span>
                        </a>
                    </li>
                </ul>

                <!-- Right Side -->
                <div class="navbar-right">
                    <span style="color: white; font-weight: 600; margin-right: 15px;">
                        <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>
                    </span>
                    <a href="../logout" class="teacher-login-btn" style="background: var(--primary-red);">
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
        <!-- Header -->
        <div class="dashboard-card mb-30">
            <div class="text-center">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: <?php echo $activity['module_color']; ?>; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 2rem; margin-bottom: 20px;">
                    <i class="fas <?php echo $activity['module_icon']; ?>"></i>
                </div>
                <h1 class="activity-title"><?php echo htmlspecialchars($activity['activity_name']); ?></h1>
                <p class="activity-instruction">
                    <span style="background: <?php echo $activity['module_color']; ?>; color: white; padding: 5px 15px; border-radius: 15px;">
                        <?php echo htmlspecialchars($activity['module_name']); ?>
                    </span>
                </p>
                <p class="activity-instruction mt-20">
                    <i class="fas fa-child me-2"></i>
                    For: <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                </p>
                <div class="alert-child alert-child-info mt-20">
                    <i class="fas fa-info-circle me-2"></i>
                    This is a preview only. Your child needs to log in to complete this activity.
                </div>
            </div>
        </div>

        <!-- Activity Description -->
        <?php if (!empty($activity['description'])): ?>
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-info"></i>
                </div>
                <h3 class="dashboard-card-title">Activity Description</h3>
            </div>
            <p style="padding: 15px;"><?php echo htmlspecialchars($activity['description']); ?></p>
        </div>
        <?php endif; ?>

        <!-- Activity Instructions -->
        <?php if (!empty($activity_data['instructions'])): ?>
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-list-ol"></i>
                </div>
                <h3 class="dashboard-card-title">Instructions</h3>
            </div>
            <div style="padding: 15px;">
                <?php 
                $instructions = $activity_data['instructions'];
                if (is_array($instructions)) {
                    echo '<ol style="margin: 0; padding-left: 20px;">';
                    foreach ($instructions as $instruction) {
                        echo '<li style="margin: 10px 0;">' . htmlspecialchars($instruction) . '</li>';
                    }
                    echo '</ol>';
                } else {
                    echo '<p>' . htmlspecialchars($instructions) . '</p>';
                }
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Activity Type Info -->
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="dashboard-card-title">Activity Details</h3>
            </div>
            <div style="padding: 15px;">
                <p><strong>Type:</strong> <?php echo htmlspecialchars(ucfirst($activity['activity_type'])); ?></p>
                <p><strong>Difficulty:</strong> <?php echo htmlspecialchars(ucfirst($activity['difficulty_level'] ?? 'Medium')); ?></p>
                <?php if (!empty($activity['estimated_time'])): ?>
                <p><strong>Estimated Time:</strong> <?php echo htmlspecialchars($activity['estimated_time']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Back Button -->
        <div class="text-center mb-30">
            <a href="child-progress.php?child_id=<?php echo $child_id; ?>" class="btn-child btn-child-secondary btn-child-large">
                <i class="fas fa-arrow-left me-2"></i>Back to Progress Report
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
</body>
</html>
