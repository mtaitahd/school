<?php
require_once 'php/db_connection.php';
require_once 'php/includes/lang.php';

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$active_nav = 'home';
$lang_page = 'index.php';
$page_title = 'Kona Ya Hisabati | Pre-Primary Mathematics Learning';
$page_description = 'Kona Ya Hisabati — interactive Pre-Primary mathematics for Tanzania. Teachers, parents, and learners access numeracy activities, lesson plans, and progress tracking.';
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
<body class="page-child">
    <?php include 'php/includes/header.php'; ?>
    <section class="hero-section" aria-labelledby="heroTitle">
        <div class="hero-background">
            <div class="hero-bg-slide slide-1" style="background-image: url('assets/images/1.jpeg');"></div>
            <div class="hero-bg-slide slide-2" style="background-image: url('assets/images/2.jpeg');"></div>
            <div class="hero-bg-slide slide-3" style="background-image: url('assets/images/3.jpeg');"></div>
            <div class="hero-bg-slide slide-4" style="background-image: url('assets/images/4.jpeg');"></div>
        </div>
        <div class="hero-overlay">
            <div class="container-child">
                <div class="text-center page-enter">
                    <p class="hero-badge" style="display:inline-block;background:rgba(255,215,0,0.9);color:#0b2d89;padding:6px 16px;border-radius:999px;font-weight:700;font-size:0.85rem;margin-bottom:16px;">
                        Pre-Primary • Tanzania
                    </p>
                    <h1 id="heroTitle" class="activity-title" style="color:#fff;font-weight:800;text-shadow:0 4px 20px rgba(0,0,0,0.5);font-size:clamp(1.75rem,5vw,2.75rem);">
                        <?php echo htmlspecialchars($t['hero_title']); ?>
                    </h1>
                    <p class="activity-instruction" style="color:#fff;font-weight:600;text-shadow:0 2px 12px rgba(0,0,0,0.5);max-width:640px;margin:0 auto 20px;font-size:clamp(1rem,2.5vw,1.2rem);">
                        <?php echo htmlspecialchars($t['hero_sub']); ?>
                    </p>
                    <div class="home-stats-bar">
                        <div class="home-stat"><strong>10+</strong><span><?php echo $current_lang === 'sw' ? 'Shughuli' : 'Activities'; ?></span></div>
                        <div class="home-stat"><strong>100%</strong><span><?php echo $current_lang === 'sw' ? 'Bila Malipo' : 'Free Access'; ?></span></div>
                        <div class="home-stat"><strong>SW/EN</strong><span><?php echo $current_lang === 'sw' ? 'Lugha Mbili' : 'Bilingual'; ?></span></div>
                    </div>

                    <div class="hero-welcome-audio mt-20">
                        <button type="button" class="audio-btn" onclick="playAudio('<?php echo htmlspecialchars($t['audio_welcome'], ENT_QUOTES); ?>')" aria-label="<?php echo htmlspecialchars($t['audio_welcome']); ?>">
                            <i class="fas fa-volume-up" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="hero-actions" style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                        <a href="learner/categories.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-yellow" style="text-decoration:none;min-height:52px;font-size:1.05rem;">
                            <i class="fas fa-play-circle" aria-hidden="true"></i>
                            <?php echo htmlspecialchars($t['btn_start']); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="feature-cards-section py-16">
        <div class="container-child">
            <div class="section-heading-center mb-30">
                <h2 style="font-size:clamp(1.5rem,3vw,2rem);color:var(--navbar-dark);"><?php echo $current_lang === 'sw' ? 'Jukwaa kwa Kila Mhusika' : 'Built for Every Role'; ?></h2>
                <div class="title-underline"></div>
            </div>
            <div class="row-child">
                <div class="col-child-3">
                    <div class="feature-card">
                        <div class="feature-card-icon"><i class="fas fa-child"></i></div>
                        <h3 class="feature-card-title"><?php echo $current_lang === 'sw' ? 'Wanafunzi' : 'Learners'; ?></h3>
                        <p class="feature-card-description"><?php echo $current_lang === 'sw' ? 'Shughuli za hisabati zenye michezo na sauti.' : 'Interactive numeracy with games, audio, and rewards.'; ?></p>
                        <a href="learner/login" class="feature-card-link"><?php echo $current_lang === 'sw' ? 'Ingia' : 'Learner Login'; ?> &rarr;</a>
                    </div>
                </div>
                
                <div class="col-child-3">
                    <div class="feature-card">
                        <div class="feature-card-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                        <h3 class="feature-card-title"><?php echo $current_lang === 'sw' ? 'Walimu' : 'Teachers'; ?></h3>
                        <p class="feature-card-description"><?php echo $current_lang === 'sw' ? 'Ongeza wanafunzi, panga masomo, fuatilia maendeleo.' : 'Add students, lesson plans, assignments, and progress.'; ?></p>
                        <a href="teacher/login" class="feature-card-link"><?php echo $current_lang === 'sw' ? 'Ingia Mwalimu' : 'Teacher Login'; ?> &rarr;</a>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="feature-card">
                        <div class="feature-card-icon"><i class="fas fa-users"></i></div>
                        <h3 class="feature-card-title"><?php echo $current_lang === 'sw' ? 'Wazazi' : 'Parents'; ?></h3>
                        <p class="feature-card-description"><?php echo $current_lang === 'sw' ? 'Unganisha mtoto kwa msimbo kutoka mwalimu.' : 'Link your child with a claim code from the teacher.'; ?></p>
                        <a href="parent/login" class="feature-card-link"><?php echo $current_lang === 'sw' ? 'Ingia Mzazi' : 'Parent Login'; ?> &rarr;</a>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="feature-card">
                        <div class="feature-card-icon"><i class="fas fa-book-open"></i></div>
                        <h3 class="feature-card-title"><?php echo $current_lang === 'sw' ? 'Mwongozo' : 'Parent Guide'; ?></h3>
                        <p class="feature-card-description"><?php echo $current_lang === 'sw' ? 'Vidokezo vya kusaidia mtoto nyumbani.' : 'Tips and home numeracy support resources.'; ?></p>
                        <a href="parent/guide.php?lang=<?php echo $current_lang; ?>" class="feature-card-link"><?php echo $current_lang === 'sw' ? 'Soma Zaidi' : 'Read Guide'; ?> &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="why-choose-section py-16" style="background:#fff;">
        <div class="container-child">
            <div class="section-heading-center">
                <h2 class="why-choose-title"><?php echo $current_lang === 'sw' ? 'Kwa Nini Kona Ya Hisabati?' : 'Why Kona Ya Hisabati?'; ?></h2>
                <div class="title-underline"></div>
            </div>
            <div class="row-child mt-30">
                <div class="col-child-3">
                    <div class="why-choose-item">
                        <div class="why-choose-icon"><i class="fas fa-shield-alt"></i></div>
                        <h4 class="why-choose-item-title"><?php echo $current_lang === 'sw' ? 'Salama kwa Watoto' : 'Child Safe'; ?></h4>
                        <p class="why-choose-item-description"><?php echo $current_lang === 'sw' ? 'Hakuna barua pepe kwa wanafunzi.' : 'No email required for learners.'; ?></p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="why-choose-item">
                        <div class="why-choose-icon"><i class="fas fa-hand-pointer"></i></div>
                        <h4 class="why-choose-item-title"><?php echo $current_lang === 'sw' ? 'Rahisi Kutumia' : 'Easy to Use'; ?></h4>
                        <p class="why-choose-item-description"><?php echo $current_lang === 'sw' ? 'Kiolesura kinachofaa watoto.' : 'Child-friendly, icon-based interface.'; ?></p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="why-choose-item">
                        <div class="why-choose-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h4 class="why-choose-item-title"><?php echo $current_lang === 'sw' ? 'Kila Kifaa' : 'Any Device'; ?></h4>
                        <p class="why-choose-item-description"><?php echo $current_lang === 'sw' ? 'Simu, kompyuta kibao, au PC.' : 'Phone, tablet, or computer.'; ?></p>
                    </div>
                </div>
                <div class="col-child-3">
                    <div class="why-choose-item">
                        <div class="why-choose-icon"><i class="fas fa-graduation-cap"></i></div>
                        <h4 class="why-choose-item-title"><?php echo $current_lang === 'sw' ? 'Mtaala wa Tanzania' : 'Tanzania Curriculum'; ?></h4>
                        <p class="why-choose-item-description"><?php echo $current_lang === 'sw' ? 'Pre-Primary hisabati.' : 'Aligned with Pre-Primary numeracy.'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'php/includes/footer.php'; ?>

    <div class="a11y-toolbar" role="group" aria-label="Accessibility options">
        <button type="button" class="a11y-btn" id="toggleContrast" title="High contrast" aria-label="Toggle high contrast"><i class="fas fa-adjust"></i></button>
        <button type="button" class="a11y-btn" id="toggleDyslexia" title="Dyslexia-friendly text" aria-label="Toggle dyslexia-friendly mode"><i class="fas fa-font"></i></button>
    </div>

    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
