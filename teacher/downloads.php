<?php
session_start();
require_once __DIR__ . '/../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloads - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once __DIR__ . '/../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'downloads';
    $lang_page = 'downloads.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Downloadable Resources</h1>
        <p class="activity-instruction">Access teaching materials and resources for your classroom</p>
    </div>

    <!-- Downloads Section -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                <i class="fas fa-download"></i>
            </div>
            <h3 class="dashboard-card-title">Teaching Resources</h3>
        </div>
        <div class="row-child">
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-file-pdf" style="font-size: 3rem; color: var(--primary-red); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Worksheets</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px;">Printable counting worksheets</p>
                    <button class="btn-child btn-child-primary" style="min-height: 40px; width: 100%; font-size: 0.9rem;">
                        <i class="fas fa-download me-2"></i>Download
                    </button>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-images" style="font-size: 3rem; color: var(--primary-green); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Flashcards</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px;">Number and shape flashcards</p>
                    <button class="btn-child btn-child-primary" style="min-height: 40px; width: 100%; font-size: 0.9rem;">
                        <i class="fas fa-download me-2"></i>Download
                    </button>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-chalkboard-teacher" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Lesson Guides</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px;">Teaching guide materials</p>
                    <button class="btn-child btn-child-primary" style="min-height: 40px; width: 100%; font-size: 0.9rem;">
                        <i class="fas fa-download me-2"></i>Download
                    </button>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-clipboard-list" style="font-size: 3rem; color: var(--primary-purple); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Assessment Forms</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem; margin-bottom: 15px;">Student assessment templates</p>
                    <button class="btn-child btn-child-primary" style="min-height: 40px; width: 100%; font-size: 0.9rem;">
                        <i class="fas fa-download me-2"></i>Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include '../php/includes/dashboard-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>



