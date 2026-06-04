<?php
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/lang.php';
require_once __DIR__ . '/php/includes/announcements-data.php';

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$active_nav = 'home';
$lang_page = 'index.php';
$page_title = 'Kona Ya Hisabati | Pre-Primary Mathematics Learning';
$page_description = 'Kona Ya Hisabati — interactive Pre-Primary mathematics for Tanzania. Teachers, parents, and learners access numeracy activities, lesson plans, and progress tracking.';

// Notes Board data — latest 3 published notes
$kyh_notes = $database->fetchAll("SELECT id, title, slug, featured_image, short_description, publish_date, created_at FROM notes WHERE status = 'published' ORDER BY COALESCE(publish_date, created_at) DESC LIMIT 3");

// Events Calendar data — upcoming published events
$kyh_events = $database->fetchAll("SELECT id, event_title, event_date, event_time, event_description FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
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

    <div class="kyh-ticker-wrap">
        <div class="kyh-ticker-inner">
            <div class="kyh-ticker-track">
                <?php foreach ($kyh_ticker_items as $ticker): ?>
                <span class="kyh-ticker-item">
                    <?php if ($ticker['url']): ?><a href="<?php echo htmlspecialchars($ticker['url']); ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;"><?php endif; ?>
                    <?php echo htmlspecialchars($ticker['message']); ?>
                    <?php if ($ticker['url']): ?></a><?php endif; ?>
                </span>
                <span class="kyh-ticker-spacer">&bull;</span>
                <?php endforeach; ?>
                <?php // duplicate for seamless loop ?>
                <?php foreach ($kyh_ticker_items as $ticker): ?>
                <span class="kyh-ticker-item">
                    <?php if ($ticker['url']): ?><a href="<?php echo htmlspecialchars($ticker['url']); ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;"><?php endif; ?>
                    <?php echo htmlspecialchars($ticker['message']); ?>
                    <?php if ($ticker['url']): ?></a><?php endif; ?>
                </span>
                <span class="kyh-ticker-spacer">&bull;</span>
                <?php endforeach; ?>
            </div>
            <div class="kyh-ticker-icon">
                <i class="fas fa-bullhorn"></i>
            </div>
        </div>
    </div>
    <section class="hero-section" aria-labelledby="heroTitle">
        <div class="hero-background">
            <?php foreach ($kyh_hero_slides as $si => $slide): ?>
            <div class="hero-bg-slide slide-<?php echo $si + 1; ?>" style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>');"></div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center page-enter">
                        <h1 id="heroTitle" class="text-white display-3 fw-black mb-3" style="text-shadow:2px 4px 10px rgba(0,0,0,0.85);white-space:nowrap;">
                            <?php echo htmlspecialchars($t['hero_title']); ?>
                        </h1>
                        <p class="text-white fw-semibold lead mb-4" style="text-shadow:0 2px 4px rgba(0,0,0,0.5);max-width:640px;margin-left:auto;margin-right:auto;">
                            <?php echo htmlspecialchars($t['hero_sub']); ?>
                        </p>
                        <div class="row justify-content-center g-4 mb-3">
                            <div class="col-4 col-md-3">
                                <div class="home-stat">
                                    <strong class="text-white display-5 fw-black">10+</strong>
                                    <span class="d-block fw-bold text-uppercase tracking-wider" style="color:#FFD43B;"><?php echo $current_lang === 'sw' ? 'Shughuli' : 'Activities'; ?></span>
                                </div>
                            </div>
                            <div class="col-4 col-md-3">
                                <div class="home-stat">
                                    <strong class="text-white display-5 fw-black">100%</strong>
                                    <span class="d-block fw-bold text-uppercase tracking-wider" style="color:#FFD43B;"><?php echo $current_lang === 'sw' ? 'Bila Malipo' : 'Free Access'; ?></span>
                                </div>
                            </div>
                            <div class="col-4 col-md-3">
                                <div class="home-stat">
                                    <strong class="text-white display-5 fw-black">SW/EN</strong>
                                    <span class="d-block fw-bold text-uppercase tracking-wider" style="color:#FFD43B;"><?php echo $current_lang === 'sw' ? 'Lugha Mbili' : 'Bilingual'; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="hero-actions" style="margin-top:28px;display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
                            <a href="learner/categories.php?lang=<?php echo $current_lang; ?>" class="btn-child btn-child-yellow" style="text-decoration:none;min-height:52px;font-size:1.05rem;border-radius:50px;">
                                <i class="fas fa-play-circle" aria-hidden="true"></i>
                                <?php echo htmlspecialchars($t['btn_start']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="slider-arrow slider-arrow-left" onclick="heroSlide(-1)" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="slider-arrow slider-arrow-right" onclick="heroSlide(1)" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>
    </section>

    <section class="kyh-notevents-section">
        <div class="container-child">
            <div class="kyh-notevents-row">
                <!-- Notes Board -->
                <div class="kyh-notevents-left">
                    <div class="kyh-notes-header">
                        <h3 class="kyh-section-title"><?php echo $current_lang === 'sw' ? 'Maelezo' : 'Notes Board'; ?></h3>
                    </div>
                    <div class="kyh-notes-list">
                        <?php foreach ($kyh_notes as $n): ?>
                        <div class="kyh-note-item">
                            <div class="kyh-note-img">
                                <a href="notes.php?id=<?php echo (int) $n['id']; ?>">
                                    <?php if ($n['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($n['featured_image']); ?>" alt="<?php echo htmlspecialchars($n['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                    <img src="assets/images/note-placeholder.jpg" alt="" loading="lazy">
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="kyh-note-body">
                                <a href="notes.php?id=<?php echo (int) $n['id']; ?>" style="text-decoration:none;color:inherit;">
                                    <h4 class="kyh-note-title"><?php echo htmlspecialchars($n['title']); ?></h4>
                                </a>
                                <div class="kyh-note-date">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('M j, Y', strtotime($n['publish_date'] ?: $n['created_at'])); ?>
                                </div>
                                <?php if ($n['short_description']): ?>
                                <p class="kyh-note-excerpt"><?php echo htmlspecialchars($n['short_description']); ?></p>
                                <?php endif; ?>
                                <a href="notes.php?id=<?php echo (int) $n['id']; ?>" class="kyh-note-link">
                                    <?php echo $current_lang === 'sw' ? 'Soma Zaidi' : 'Read More'; ?> <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="notes.php" class="kyh-board-card-btn">
                        <?php echo $current_lang === 'sw' ? 'Maelezo Yote' : 'All Notes'; ?> <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <!-- Events Calendar -->
                <div class="kyh-notevents-right">
                    <div class="kyh-events-header">
                        <h3 class="kyh-section-title"><?php echo $current_lang === 'sw' ? 'Matukio' : 'Events Calendar'; ?></h3>
                    </div>
                    <div class="kyh-events-calendar">
                        <div class="kyh-events-list">
                            <?php foreach ($kyh_events as $e): ?>
                            <?php $dt = new DateTime($e['event_date']); ?>
                            <div class="kyh-event-item">
                                <div class="kyh-event-date-box">
                                    <span class="kyh-event-day"><?php echo $dt->format('d'); ?></span>
                                    <span class="kyh-event-month"><?php echo $dt->format('M'); ?></span>
                                </div>
                                <div class="kyh-event-info">
                                    <h4 class="kyh-event-title"><?php echo htmlspecialchars($e['event_title']); ?></h4>
                                    <?php if ($e['event_time']): ?>
                                    <div class="kyh-event-time">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($e['event_time']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="events.php" class="kyh-board-card-btn">
                        <?php echo $current_lang === 'sw' ? 'Matukio Yote' : 'All Events'; ?> <i class="fas fa-arrow-right"></i>
                    </a>
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

    <?php $gov_count = !empty($kyh_governance) ? count($kyh_governance) : 0; ?>
    <section class="container text-center" style="padding-top:2rem;padding-bottom:1rem;">
        <h3 class="text-primary fw-bold mb-0" style="font-size:1.75rem;">
            <?php echo $current_lang === 'sw' ? 'Usimamizi na Walimu' : 'Management & Teachers'; ?>
        </h3>
        <div style="width:80px;height:3px;background-color:#007bff;margin:0.75rem auto 1.5rem auto;border-radius:2px;"></div>

        <div class="governance-carousel position-relative">
            <button type="button" class="governance-arrow governance-arrow-left" onclick="scrollGovernance(-1)" aria-label="Previous">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 flex-nowrap overflow-auto governance-scroll" id="governanceScroll">
            <?php
            $color_map = [
                'blue'   => '#007bff',
                'green'  => '#28a745',
                'red'    => '#dc3545',
                'yellow' => '#ffc107',
                'purple' => '#6f42c1',
            ];
            $tint_map = [
                'blue'   => 'rgba(0, 123, 255, 0.05)',
                'green'  => 'rgba(40, 167, 69, 0.05)',
                'red'    => 'rgba(220, 53, 69, 0.05)',
                'yellow' => 'rgba(255, 193, 7, 0.08)',
                'purple' => 'rgba(111, 66, 193, 0.05)',
            ];
            ?>
            <?php foreach ($kyh_governance as $g): ?>
            <?php
                $bc = $g['border_color'] ?? 'blue';
                $border_hex = $color_map[$bc] ?? '#007bff';
                $tint = $tint_map[$bc] ?? 'rgba(0, 123, 255, 0.05)';
            ?>
            <div class="col">
                <div class="card h-100 text-center leadership-card" style="border-bottom:4px solid <?php echo $border_hex; ?>;">
                    <div class="image-frame p-3">
                        <?php if ($g['image_path']): ?>
                            <img src="<?php echo htmlspecialchars($g['image_path']); ?>" alt="<?php echo htmlspecialchars($g['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="fallback-icon"><i class="fas fa-user"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <h5 class="fw-bold text-dark mt-3 px-2 mb-1"><?php echo htmlspecialchars($g['name']); ?></h5>
                        <div class="role-box p-2 mt-auto" style="background-color:<?php echo $tint; ?>;">
                            <p class="role-text"><?php echo htmlspecialchars($g['title']); ?></p>
                        </div>
                        <?php if ($g['profile_link']): ?>
                            <a href="<?php echo htmlspecialchars($g['profile_link']); ?>" class="d-block text-muted text-decoration-none small pb-3" target="_blank" rel="noopener">view profile</a>
                        <?php else: ?>
                            <span class="d-block text-muted small pb-3" style="cursor:default;">view profile</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
            <button type="button" class="governance-arrow governance-arrow-right" onclick="scrollGovernance(1)" aria-label="Next">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <?php if ($gov_count > 5): ?>
        <div class="text-center mt-4">
            <button type="button" class="kyh-board-card-btn" data-bs-toggle="modal" data-bs-target="#governanceAllModal">
                <?php echo $current_lang === 'sw' ? 'Wote' : 'View All'; ?> (<?php echo $gov_count; ?>) <i class="fas fa-arrow-right"></i>
            </button>
        </div>
        <?php endif; ?>
    </section>

    <!-- View All Governance Modal -->
    <div class="modal fade" id="governanceAllModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border-radius:0;">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><?php echo $current_lang === 'sw' ? 'Usimamizi na Walimu Wote' : 'All Management & Teachers'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($kyh_governance as $g): ?>
                    <?php
                        $bc = $g['border_color'] ?? 'blue';
                        $border_hex = $color_map[$bc] ?? '#007bff';
                        $tint = $tint_map[$bc] ?? 'rgba(0, 123, 255, 0.05)';
                    ?>
                        <div class="col text-center">
                            <div class="card h-100 leadership-card" style="border-bottom:4px solid <?php echo $border_hex; ?>;">
                                <div class="image-frame p-3">
                                    <?php if ($g['image_path']): ?>
                                        <img src="<?php echo htmlspecialchars($g['image_path']); ?>" alt="<?php echo htmlspecialchars($g['name']); ?>" loading="lazy">
                                    <?php else: ?>
                                        <div class="fallback-icon"><i class="fas fa-user"></i></div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body p-0">
                                    <h5 class="fw-bold text-dark mt-3 px-2 mb-1"><?php echo htmlspecialchars($g['name']); ?></h5>
                                    <div class="role-box p-2 mt-auto" style="background-color:<?php echo $tint; ?>;">
                                        <p class="role-text"><?php echo htmlspecialchars($g['title']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:0;"><?php echo $current_lang === 'sw' ? 'Funga' : 'Close'; ?></button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'php/includes/footer.php'; ?>

    <div class="a11y-toolbar" role="group" aria-label="Accessibility options">
        <button type="button" class="a11y-btn" id="toggleContrast" title="High contrast" aria-label="Toggle high contrast"><i class="fas fa-adjust"></i></button>
        <button type="button" class="a11y-btn" id="toggleDyslexia" title="Dyslexia-friendly text" aria-label="Toggle dyslexia-friendly mode"><i class="fas fa-font"></i></button>
        <button type="button" class="a11y-btn" id="cycleColor" title="Change color theme" aria-label="Cycle color theme"><i class="fas fa-palette"></i></button>
        <button type="button" class="a11y-btn" id="cycleFont" title="Change font" aria-label="Cycle font"><i class="fas fa-text-height"></i></button>
    </div>

    <script>
    // Dynamic hero slider — handles any number of slides with arrow navigation
    (function() {
        var slides = document.querySelectorAll('.hero-bg-slide');
        if (slides.length > 1) {
            // Disable CSS animations for dynamic slides
            var style = document.createElement('style');
            var css = '';
            for (var i = 1; i <= slides.length; i++) {
                css += '.hero-bg-slide.slide-' + i + ' { animation: none !important; }';
            }
            style.textContent = css;
            document.head.appendChild(style);

            // Show first, hide rest
            for (var i = 1; i < slides.length; i++) {
                slides[i].style.opacity = '0';
            }
            slides[0].style.opacity = '1';

            var current = 0;
            var interval;

            function goToSlide(index) {
                if (index < 0) index = slides.length - 1;
                if (index >= slides.length) index = 0;
                slides[current].style.opacity = '0';
                current = index;
                slides[current].style.opacity = '1';
            }

            function startInterval() {
                if (interval) clearInterval(interval);
                interval = setInterval(function() {
                    goToSlide(current + 1);
                }, 5000);
            }

            window.heroSlide = function(dir) {
                goToSlide(current + dir);
                startInterval();
            };

            startInterval();
        }
    })();
    </script>
    <audio id="audioPlayer" preload="auto"></audio>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    function kyhOpenModal(id) {
        var el = document.getElementById('kyhModal' + id);
        if (el) el.classList.add('active');
    }
    function kyhCloseModal(id) {
        var el = document.getElementById('kyhModal' + id);
        if (el) el.classList.remove('active');
    }
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('kyh-modal-overlay')) {
            e.target.classList.remove('active');
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.kyh-modal-overlay.active').forEach(function(m) {
                m.classList.remove('active');
            });
        }
    });

    function scrollGovernance(dir) {
        var el = document.getElementById('governanceScroll');
        if (el) {
            var scrollAmount = el.clientWidth * 0.8;
            el.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
        }
    }

    // Color & font cycler
    (function() {
        var colors = ['#007bff', '#28a745', '#dc3545', '#6f42c1', '#fd7e14'];
        var fonts = ['Poppins, sans-serif', 'Arial, sans-serif', 'Georgia, serif', '"Courier New", monospace', '"Times New Roman", serif'];
        var ci = 0, fi = 0;
        document.getElementById('cycleColor').addEventListener('click', function() {
            ci = (ci + 1) % colors.length;
            document.querySelector('.hero-section .display-3').style.color = colors[ci];
        });
        document.getElementById('cycleFont').addEventListener('click', function() {
            fi = (fi + 1) % fonts.length;
            document.querySelector('.hero-section .display-3').style.fontFamily = fonts[fi];
        });
    })();
    </script>
</body>
</html>
