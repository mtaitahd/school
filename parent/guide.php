<?php
require_once '../php/includes/session.php';
require_once '../php/includes/security.php';
sec_send_headers();
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once '../php/includes/lang.php';
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '../';
$active_nav = 'parent';
$lang_page = 'guide.php';
$logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'parent';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Guide - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="page-child"><?php include '../php/includes/header.php'; ?>
    <?php if (false): ?><nav class="navbar-modern">
        <div class="container-modern">
            <div class="navbar-content">
                <!-- Left Side - Logo -->
                <div class="navbar-brand-modern">
                    <img src="../assets/images/logo.png" alt="Kona Ya Hisabati Logo" class="navbar-logo">
                    <div class="navbar-brand-text">
                        <span class="brand-main">Kona Ya Hisabati</span>
                        <span class="brand-subtitle">Jifunze � Furahia � Fanikiwa</span>
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
                        <a href="guide" class="navbar-link">
                            <i class="fas fa-book"></i>
                            <span>Guide</span>
                        </a>
                    </li>
                </ul>

                <!-- Right Side -->
                <div class="navbar-right">
                    <span style="color: white; font-weight: 600; margin-right: 15px;">
                        <?php echo htmlspecialchars($_SESSION['first_name'] ?? ''); ?>
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
    </nav><?php endif; ?>

    <main class="container-child mt-30 page-enter">
        <div class="parent-guide-hero">
            <i class="fas fa-user-friends" aria-hidden="true"></i>
            <h1 class="activity-title mt-20"><?php echo $current_lang === 'sw' ? 'Mwongozo wa Mzazi' : 'Parent Guide'; ?></h1>
            <p class="activity-instruction" style="font-size: 1.35rem;"><?php echo $current_lang === 'sw' ? 'Msaidie mtoto wako kujifunza hisabati nyumbani' : "Support your child's numeracy learning at home"; ?></p>
        </div>

        <!-- Guide Sections -->
        <div class="row-child mb-30">
            <div class="col-child-3">
                <div class="dashboard-card" style="cursor: pointer;" onclick="scrollToSection('home-numeracy')">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3 class="dashboard-card-title">Home Numeracy</h3>
                    </div>
                    <p style="color: var(--text-light);">Simple daily math activities you can do at home</p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card" style="cursor: pointer;" onclick="scrollToSection('videos')">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-green);">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="dashboard-card-title">Video Tutorials</h3>
                    </div>
                    <p style="color: var(--text-light);">Watch and learn how to support your child</p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card" style="cursor: pointer;" onclick="scrollToSection('tips')">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="dashboard-card-title">Support Tips</h3>
                    </div>
                    <p style="color: var(--text-light);">Step-by-step guidance for parents</p>
                </div>
            </div>
            <div class="col-child-3">
                <div class="dashboard-card" style="cursor: pointer;" onclick="scrollToSection('activities')">
                    <div class="dashboard-card-header">
                        <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                            <i class="fas fa-puzzle-piece"></i>
                        </div>
                        <h3 class="dashboard-card-title">Practice Activities</h3>
                    </div>
                    <p style="color: var(--text-light);">Activities that need no devices</p>
                </div>
            </div>
        </div>

        <!-- Home Numeracy Section -->
        <div class="dashboard-card mb-30" id="home-numeracy">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);">
                    <i class="fas fa-home"></i>
                </div>
                <h3 class="dashboard-card-title">Home Numeracy Guides</h3>
            </div>
            <div class="row-child">
                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h4 style="color: var(--primary-blue); margin-bottom: 15px;">Counting at Home</h4>
                        <ul style="line-height: 2;">
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Count objects around the house (toys, spoons, chairs)</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Count steps while walking</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Count items during shopping</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Count fingers and toes together</li>
                        </ul>
                    </div>
                </div>
                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h4 style="color: var(--primary-blue); margin-bottom: 15px;">Number Recognition</h4>
                        <ul style="line-height: 2;">
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Point out numbers on clocks, phones, calendars</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Read house numbers while walking</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Identify numbers on food packages</li>
                            <li><i class="fas fa-check-circle me-2" style="color: var(--primary-green);"></i>Play number matching games</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Video Tutorials Section -->
        <div class="dashboard-card mb-30" id="videos">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-green);">
                    <i class="fas fa-video"></i>
                </div>
                <h3 class="dashboard-card-title">Video Tutorials</h3>
            </div>
            <div class="row-child">
                <div class="col-child-3">
                    <div class="dashboard-card text-center">
                        <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-play-circle" style="font-size: 4rem; color: var(--primary-green);"></i>
                        </div>
                        <h4 style="margin-bottom: 10px;">Introduction to Counting</h4>
                        <p style="color: var(--text-light); font-size: 0.9rem;">Learn basic counting techniques</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card text-center">
                        <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-play-circle" style="font-size: 4rem; color: var(--primary-green);"></i>
                        </div>
                        <h4 style="margin-bottom: 10px;">Shape Recognition</h4>
                        <p style="color: var(--text-light); font-size: 0.9rem;">Teaching shapes at home</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card text-center">
                        <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-play-circle" style="font-size: 4rem; color: var(--primary-green);"></i>
                        </div>
                        <h4 style="margin-bottom: 10px;">Simple Addition</h4>
                        <p style="color: var(--text-light); font-size: 0.9rem;">Visual addition methods</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card text-center">
                        <div style="width: 100%; height: 150px; background: var(--background-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                            <i class="fas fa-play-circle" style="font-size: 4rem; color: var(--primary-green);"></i>
                        </div>
                        <h4 style="margin-bottom: 10px;">Daily Math Routines</h4>
                        <p style="color: var(--text-light); font-size: 0.9rem;">Integrating math into daily life</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Tips Section -->
        <div class="dashboard-card mb-30" id="tips">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-yellow);">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="dashboard-card-title">Step-by-Step Support Tips</h3>
            </div>
            <div class="row-child">
                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h4 style="color: var(--primary-yellow); margin-bottom: 15px;">Getting Started</h4>
                        <ol style="line-height: 2;">
                            <li>Create a dedicated learning space at home</li>
                            <li>Set aside 15-20 minutes daily for math practice</li>
                            <li>Start with simple counting activities</li>
                            <li>Use everyday objects for learning</li>
                            <li>Be patient and encourage effort over perfection</li>
                        </ol>
                    </div>
                </div>
                <div class="col-child-2">
                    <div class="dashboard-card">
                        <h4 style="color: var(--primary-yellow); margin-bottom: 15px;">Making Learning Fun</h4>
                        <ol style="line-height: 2;">
                            <li>Use songs and rhymes for counting</li>
                            <li>Turn learning into games</li>
                            <li>Use colorful materials</li>
                            <li>Celebrate small achievements</li>
                            <li>Connect math to real-life situations</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Practice Activities Section -->
        <div class="dashboard-card mb-30" id="activities">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-orange);">
                    <i class="fas fa-puzzle-piece"></i>
                </div>
                <h3 class="dashboard-card-title">No-Device Practice Activities</h3>
            </div>
            <div class="row-child">
                <div class="col-child-3">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon" style="background: var(--primary-blue);"><i class="fas fa-sort-numeric-up"></i></div>
                            <h4 style="margin: 0;">Number Hunt</h4>
                        </div>
                        <p style="margin-top: 15px;">Find numbers around the house and neighborhood. Count how many you can find!</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon" style="background: var(--primary-green);"><i class="fas fa-shapes"></i></div>
                            <h4 style="margin: 0;">Shape Search</h4>
                        </div>
                        <p style="margin-top: 15px;">Look for circles, squares, and triangles in your home. Draw what you find!</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon" style="background: var(--primary-yellow);"><i class="fas fa-cookie-bite"></i></div>
                            <h4 style="margin: 0;">Snack Math</h4>
                        </div>
                        <p style="margin-top: 15px;">Count snacks before eating. Practice addition by adding two groups together.</p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="dashboard-card">
                        <div class="dashboard-card-header">
                            <div class="dashboard-card-icon" style="background: var(--primary-purple);"><i class="fas fa-ruler"></i></div>
                            <h4 style="margin: 0;">Size Sorting</h4>
                        </div>
                        <p style="margin-top: 15px;">Sort toys or objects by size (big/small, tall/short). Arrange them in order.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login to Dashboard -->
        <div class="text-center mt-30">
            <p class="activity-instruction mb-20">Want to track your child's progress?</p>
            <a href="login" class="btn-child btn-child-primary btn-child-large">
                <i class="fas fa-sign-in-alt me-2"></i>Login to Parent Dashboard
            </a>
        </div>
    </main>

    <?php include '../php/includes/footer.php'; ?>

    <div class="a11y-toolbar">
        <button type="button" class="a11y-btn" id="toggleContrast"><i class="fas fa-adjust"></i></button>
        <button type="button" class="a11y-btn" id="toggleDyslexia"><i class="fas fa-font"></i></button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script>
        function scrollToSection(sectionId) {
            document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>



