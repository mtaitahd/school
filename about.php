<?php
require_once 'php/includes/security.php';
sec_send_headers();
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once 'php/db_connection.php';
require_once 'php/includes/lang.php';
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$active_nav = 'about';
$lang_page = 'about.php';
$page_title = 'About - Kona Ya Hisabati';
$page_description = 'Learn about Kona Ya Hisabati — Pre-Primary mathematics learning for Tanzania.';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang === 'sw' ? 'sw' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <?php include 'php/includes/seo-head.php'; ?>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Minimal Top Bar -->
    <div class="about-topbar">
        <div class="about-topbar-content">
            <a href="index.php?lang=<?php echo $current_lang; ?>" class="about-back-btn" aria-label="Back to Home">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="about-logo">
                <div class="about-logo-icon">
                    <i class="fas fa-shapes"></i>
                </div>
                <div class="about-logo-text">
                    <span class="about-logo-main">Kona Ya Hisabati</span>
                    <span class="about-logo-sub">Jifunze • Furahia • Fanikiwa</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="about-hero-bg">
            <div class="about-hero-shape shape-1"></div>
            <div class="about-hero-shape shape-2"></div>
            <div class="about-hero-shape shape-3"></div>
        </div>
        <div class="container-child">
            <div class="about-hero-content">
                <div class="about-hero-left">
                    <span class="about-hero-label">ABOUT US</span>
                    <h1 class="about-hero-title">We Build Fun Mathematics Learning Experiences</h1>
                    <p class="about-hero-description">
                        Kona Ya Hisabati helps Tanzanian early learners discover the joy of mathematics through interactive, child-friendly digital activities designed for Pre-Primary education.
                    </p>
                </div>
                <div class="about-hero-right">
                    <div class="about-hero-visual">
                        <div class="about-hero-image">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="about-stat-card stat-1">
                            <div class="about-stat-number">10+</div>
                            <div class="about-stat-label">Activities</div>
                        </div>
                        <div class="about-stat-card stat-2">
                            <div class="about-stat-number">100%</div>
                            <div class="about-stat-label">Free Access</div>
                        </div>
                        <div class="about-stat-card stat-3">
                            <div class="about-stat-number">SW/EN</div>
                            <div class="about-stat-label">Bilingual</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mission Section -->
    <section class="about-mission" id="mission">
        <div class="container-child">
            <div class="about-section-header">
                <span class="about-section-label">OUR MISSION</span>
                <h2 class="about-section-title">Empowering Young Mathematicians</h2>
                <div class="about-section-underline"></div>
            </div>
            <div class="about-mission-content">
                <div class="about-mission-card glass-card">
                    <div class="about-mission-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <p class="about-mission-text">
                        Kona Ya Hisabati is a web-based interactive mathematics learning platform designed to digitize the traditional Mathematics Learning Corner used in Tanzanian early grade classrooms. Our platform aims to improve numeracy learning for Pre-Primary learners through child-friendly, interactive digital activities.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Goals Section -->
    <section class="about-goals">
        <div class="container-child">
            <div class="about-section-header">
                <span class="about-section-label">OUR GOALS</span>
                <h2 class="about-section-title">What We Strive For</h2>
                <div class="about-section-underline"></div>
            </div>
            <div class="about-goals-grid">
                <div class="about-goal-card">
                    <div class="about-goal-icon-wrapper" style="background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="about-goal-title">Improve Numeracy Skills</h3>
                    <p class="about-goal-description">Interactive practice builds strong mathematical foundations</p>
                </div>
                <div class="about-goal-card">
                    <div class="about-goal-icon-wrapper" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="about-goal-title">Curriculum-Aligned Learning</h3>
                    <p class="about-goal-description">Continuous access to Tanzania curriculum resources</p>
                </div>
                <div class="about-goal-card">
                    <div class="about-goal-icon-wrapper" style="background: linear-gradient(135deg, var(--primary-yellow), var(--primary-yellow-dark));">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="about-goal-title">School-Home Collaboration</h3>
                    <p class="about-goal-description">Strengthen connections between classroom and home</p>
                </div>
                <div class="about-goal-card">
                    <div class="about-goal-icon-wrapper" style="background: linear-gradient(135deg, var(--primary-purple), var(--primary-purple-dark));">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="about-goal-title">Confidence in Mathematics</h3>
                    <p class="about-goal-description">Boost interest, engagement, and self-confidence</p>
                </div>
                <div class="about-goal-card">
                    <div class="about-goal-icon-wrapper" style="background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));">
                        <i class="fas fa-universal-access"></i>
                    </div>
                    <h3 class="about-goal-title">Inclusive Learning</h3>
                    <p class="about-goal-description">Accessible education following UDL principles</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Target Users Section -->
    <section class="about-users">
        <div class="container-child">
            <div class="about-section-header">
                <span class="about-section-label">TARGET USERS</span>
                <h2 class="about-section-title">Built for Everyone</h2>
                <div class="about-section-underline"></div>
            </div>
            <div class="about-users-grid">
                <div class="about-user-card user-children">
                    <div class="about-user-gradient"></div>
                    <div class="about-user-icon">
                        <i class="fas fa-child"></i>
                    </div>
                    <h3 class="about-user-title">Children</h3>
                    <p class="about-user-description">Pre-Primary & Standard One-Two learners with limited reading ability</p>
                </div>
                <div class="about-user-card user-teachers">
                    <div class="about-user-gradient"></div>
                    <div class="about-user-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 class="about-user-title">Teachers</h3>
                    <p class="about-user-description">Need structured lesson-aligned content and classroom resources</p>
                </div>
                <div class="about-user-card user-parents">
                    <div class="about-user-gradient"></div>
                    <div class="about-user-icon">
                        <i class="fas fa-user-friends"></i>
                    </div>
                    <h3 class="about-user-title">Parents</h3>
                    <p class="about-user-description">Require simple navigation and home-based practice activities</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Learning Principles Section -->
    <section class="about-principles">
        <div class="container-child">
            <div class="about-section-header">
                <span class="about-section-label">LEARNING PRINCIPLES</span>
                <h2 class="about-section-title">Our Educational Approach</h2>
                <div class="about-section-underline"></div>
            </div>
            <div class="about-principles-grid">
                <div class="about-principle-card glass-card">
                    <div class="about-principle-icon" style="background: linear-gradient(135deg, var(--primary-blue), var(--primary-blue-dark));">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3 class="about-principle-title">Early Childhood Education</h3>
                    <p class="about-principle-description">More visuals, less text; color-rich, playful interactions designed for young learners.</p>
                </div>
                <div class="about-principle-card glass-card">
                    <div class="about-principle-icon" style="background: linear-gradient(135deg, var(--primary-green), var(--primary-green-dark));">
                        <i class="fas fa-universal-access"></i>
                    </div>
                    <h3 class="about-principle-title">Universal Design for Learning</h3>
                    <p class="about-principle-description">Multiple means of engagement, representation, and expression for all learners.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="about-team">
        <div class="container-child">
            <div class="about-section-header">
                <span class="about-section-label">OUR TEAM</span>
                <h2 class="about-section-title">Meet the Founders</h2>
                <div class="about-section-underline"></div>
            </div>
            <div class="about-team-grid">
                <div class="about-team-card">
                    <div class="about-team-avatar" style="background: linear-gradient(145deg, var(--primary-blue), var(--primary-blue-dark));">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="about-team-name">Saumu Mahanda</h3>
                    <p class="about-team-role" style="color: var(--primary-blue);">Owner & Founder</p>
                </div>
                <div class="about-team-card">
                    <div class="about-team-avatar" style="background: linear-gradient(145deg, var(--primary-green), var(--primary-green-dark));">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="about-team-name">Happiness Mhina</h3>
                    <p class="about-team-role" style="color: var(--primary-green);">Owner & Founder</p>
                </div>
                <div class="about-team-card">
                    <div class="about-team-avatar" style="background: linear-gradient(145deg, var(--primary-yellow), var(--primary-yellow-dark));">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="about-team-name">Francis Shayo</h3>
                    <p class="about-team-role" style="color: var(--primary-yellow);">Program Manager</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'php/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
