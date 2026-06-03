<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Tips - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body">
    <?php
    require_once '../php/includes/lang.php';
    $base_path = '../';
    $dashboard_role = 'teacher';
    $sidebar_active = 'tips';
    $lang_page = 'tips.php';
    include '../php/includes/dashboard-start.php';
    ?>

    <div class="text-center mb-30">
        <h1 class="activity-title">Classroom Tips & UDL Strategies</h1>
        <p class="activity-instruction">Effective teaching strategies for inclusive mathematics education</p>
    </div>

    <!-- Classroom Tips Section -->
    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-purple);">
                <i class="fas fa-lightbulb"></i>
            </div>
            <h3 class="dashboard-card-title">Teaching Strategies</h3>
        </div>
        <div class="row-child">
            <div class="col-child-2">
                <div class="dashboard-card">
                    <h4 style="color: var(--primary-purple); margin-bottom: 15px;">Differentiation Tips</h4>
                    <ul style="line-height: 2;">
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Use multiple representations (visual, auditory, kinesthetic)</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Provide varied difficulty levels</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Allow flexible grouping</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Use scaffolded instruction</li>
                    </ul>
                </div>
            </div>
            <div class="col-child-2">
                <div class="dashboard-card">
                    <h4 style="color: var(--primary-purple); margin-bottom: 15px;">Remedial Strategies</h4>
                    <ul style="line-height: 2;">
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Identify specific learning gaps</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Use manipulatives and concrete objects</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Provide extra practice opportunities</li>
                        <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Use peer tutoring when appropriate</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-card mb-30">
        <div class="dashboard-card-header">
            <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                <i class="fas fa-brain"></i>
            </div>
            <h3 class="dashboard-card-title">Universal Design for Learning (UDL)</h3>
        </div>
        <div class="row-child">
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-eye" style="font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Multiple Means of Representation</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Present information in different ways</p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-pencil-alt" style="font-size: 2.5rem; color: var(--primary-green); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Multiple Means of Action</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Allow different ways to express learning</p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card text-center">
                    <i class="fas fa-heart" style="font-size: 2.5rem; color: var(--primary-red); margin-bottom: 15px;"></i>
                    <h4 style="margin-bottom: 10px;">Multiple Means of Engagement</h4>
                    <p style="color: var(--text-light); font-size: 0.9rem;">Provide options for engagement</p>
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



