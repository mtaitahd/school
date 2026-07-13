<?php
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/lang.php';
require_once __DIR__ . '/php/includes/announcements-data.php';
require_once __DIR__ . '/php/includes/settings.php';

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$active_nav = 'home';
$lang_page = 'index.php';
$page_title = 'Kona Ya Hisabati | Pre-Primary Mathematics Learning';
$page_description = 'Kona Ya Hisabati - interactive Pre-Primary mathematics for Tanzania. Teachers, parents, and learners access numeracy activities, lesson plans, and progress tracking.';

// Notes Board data -- latest 3 published notes
$kyh_notes = $database->fetchAll("SELECT id, title, slug, featured_image, short_description, publish_date, created_at FROM notes WHERE status = 'published' ORDER BY COALESCE(publish_date, created_at) DESC LIMIT 3");

// Events Calendar data -- upcoming published events
$kyh_events = $database->fetchAll("SELECT id, event_title, event_date, event_time, event_description FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");

// Total registered students count
$total_students = $database->fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'learner'")['count'] ?? 0;

// Benefit cards
$benefit_cards = $database->fetchAll("SELECT * FROM benefit_cards WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="page-child">
    <div class="scroll-progress" id="scrollProgress"></div>
    <div class="custom-cursor" id="customCursor"></div>
    <?php include 'php/includes/header.php'; ?>

    <div class="kyh-ticker-wrap">
        <div class="kyh-ticker-inner">
            <div class="kyh-ticker-track">
                <?php foreach ($kyh_ticker_items as $ticker): ?>
                <span class="kyh-ticker-item">
                    <i class="fas fa-bullhorn" style="font-size:0.75rem;opacity:0.6;margin-right:4px;"></i>
                    <?php if ($ticker['url']): ?><a href="<?php echo htmlspecialchars($ticker['url']); ?>" target="_blank" rel="noopener" style="color:inherit;text-decoration:none;"><?php endif; ?>
                    <?php echo htmlspecialchars($ticker['message']); ?>
                    <?php if ($ticker['url']): ?></a><?php endif; ?>
                </span>
                <span class="kyh-ticker-spacer">&bull;</span>
                <?php endforeach; ?>
                <?php // duplicate for seamless loop ?>
                <?php foreach ($kyh_ticker_items as $ticker): ?>
                <span class="kyh-ticker-item">
                    <i class="fas fa-bullhorn" style="font-size:0.75rem;opacity:0.6;margin-right:4px;"></i>
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
    <section class="hero-section-home" aria-labelledby="heroTitle">
        <div class="hero-background">
            <?php foreach ($kyh_hero_slides as $si => $slide): ?>
            <div class="hero-bg-slide slide-<?php echo $si + 1; ?>" style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>');"></div>
            <?php endforeach; ?>
            <div class="hero-dark-overlay"></div>
        </div>
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
        <div class="hero-overlay">
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-8 text-center">
                        <div class="hero-content">
                            <h1 id="heroTitle" class="hero-title-home">
                                <?php echo htmlspecialchars($t['hero_title']); ?>
                            </h1>
                            <p class="hero-subtitle-home">
                                <?php echo htmlspecialchars($t['hero_sub']); ?>
                            </p>
                            <div class="hero-cta-group">
                                <button type="button" onclick="handleStartLearning()" class="hero-btn hero-btn-primary">
                                    <i class="fas fa-play-circle"></i>
                                    <?php echo htmlspecialchars($t['btn_start']); ?>
                                </button>
                                <a href="#benefits" class="hero-btn hero-btn-ghost">
                                    <i class="fas fa-arrow-down"></i>
                                    <?php echo $current_lang === 'sw' ? 'Jifunze Zaidi' : 'Learn More'; ?>
                                </a>
                            </div>
                            <div class="hero-stats">
                                <div class="hero-stat">
                                    <div class="hero-stat-number"><span class="counter-value" data-target="<?php echo $total_students; ?>">0</span>+</div>
                                    <div class="hero-stat-label"><?php echo $current_lang === 'sw' ? 'Wanafunzi' : 'Students'; ?></div>
                                </div>
                                <div class="hero-stat">
                                    <div class="hero-stat-number">480+</div>
                                    <div class="hero-stat-label"><?php echo $current_lang === 'sw' ? 'Shughuli' : 'Activities'; ?></div>
                                </div>
                                <div class="hero-stat">
                                    <div class="hero-stat-number">SW/<span class="highlight">EN</span></div>
                                    <div class="hero-stat-label"><?php echo $current_lang === 'sw' ? 'Lugha Mbili' : 'Bilingual'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="button" class="slider-arrow slider-arrow-left" onclick="heroSlide(-1)" aria-label="Previous slide"><i class="fas fa-chevron-left"></i></button>
        <button type="button" class="slider-arrow slider-arrow-right" onclick="heroSlide(1)" aria-label="Next slide"><i class="fas fa-chevron-right"></i></button>
    </section>

    <section class="notes-events-section">
        <div class="container-child">
            <div class="section-heading-home reveal">
                <div class="section-badge"><i class="fas fa-newspaper"></i> <?php echo $current_lang === 'sw' ? 'Habari' : 'Latest Updates'; ?></div>
                <h2 class="section-title-home"><?php echo $current_lang === 'sw' ? 'Maelezo na Matukio' : 'Notes & Events'; ?></h2>
                <p class="section-subtitle-home"><?php echo $current_lang === 'sw' ? 'Pata habari na matukio mapya kutoka Kona Ya Hisabati' : 'Stay updated with the latest from Kona Ya Hisabati'; ?></p>
            </div>
            <div class="row g-4">
                <!-- Notes Column -->
                <div class="col-lg-7 reveal-left">
                    <div class="row g-4">
                        <?php foreach ($kyh_notes as $n): ?>
                        <div class="col-md-6">
                            <a href="notes?id=<?php echo (int) $n['id']; ?>" class="note-card-home" style="text-decoration:none;color:inherit;display:block;">
                                <div class="note-card-img">
                                    <?php if ($n['featured_image']): ?>
                                    <img src="<?php echo htmlspecialchars($n['featured_image']); ?>" alt="<?php echo htmlspecialchars($n['title']); ?>" loading="lazy">
                                    <?php else: ?>
                                    <img src="assets/images/note-placeholder.jpg" alt="" loading="lazy">
                                    <?php endif; ?>
                                </div>
                                <div class="note-card-body">
                                    <div class="note-card-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($n['publish_date'] ?: $n['created_at'])); ?>
                                    </div>
                                    <h4 class="note-card-title"><?php echo htmlspecialchars($n['title']); ?></h4>
                                    <?php if ($n['short_description']): ?>
                                    <p class="note-card-excerpt"><?php echo htmlspecialchars($n['short_description']); ?></p>
                                    <?php endif; ?>
                                    <span class="note-card-link">
                                        <?php echo $current_lang === 'sw' ? 'Soma Zaidi' : 'Read More'; ?> <i class="fas fa-arrow-right"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="notes" class="hero-btn hero-btn-ghost" style="color:var(--home-dark);border-color:rgba(0,0,0,0.1);">
                            <?php echo $current_lang === 'sw' ? 'Maelezo Yote' : 'All Notes'; ?> <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <!-- Events Column -->
                <div class="col-lg-5 reveal-right">
                    <div class="leader-card-home" style="background:#fff;border:1px solid rgba(0,0,0,0.06);">
                        <div style="padding:24px 24px 12px;border-bottom:1px solid rgba(0,0,0,0.06);">
                            <h3 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;color:var(--home-dark);margin:0;display:flex;align-items:center;gap:10px;">
                                <i class="fas fa-calendar-alt" style="color:var(--home-accent);"></i>
                                <?php echo $current_lang === 'sw' ? 'Matukio Yajayo' : 'Upcoming Events'; ?>
                            </h3>
                        </div>
                        <div style="padding:12px 16px;">
                            <?php if (empty($kyh_events)): ?>
                            <p style="text-align:center;padding:40px 20px;color:#999;font-family:var(--font-body);font-size:0.9rem;">
                                <i class="fas fa-calendar-times" style="font-size:2rem;display:block;margin-bottom:12px;opacity:0.3;"></i>
                                <?php echo $current_lang === 'sw' ? 'Hakuna matukio yajayo' : 'No upcoming events'; ?>
                            </p>
                            <?php else: ?>
                            <?php foreach ($kyh_events as $e): ?>
                            <?php $dt = new DateTime($e['event_date']); ?>
                            <div class="event-item-home">
                                <div class="event-date-box">
                                    <span class="event-date-day"><?php echo $dt->format('d'); ?></span>
                                    <span class="event-date-month"><?php echo $dt->format('M'); ?></span>
                                </div>
                                <div>
                                    <h4 class="event-info-title"><?php echo htmlspecialchars($e['event_title']); ?></h4>
                                    <?php if ($e['event_time']): ?>
                                    <div class="event-info-time"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($e['event_time']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="padding:12px 24px 20px;text-align:center;">
                            <a href="events" class="note-card-link" style="font-size:0.85rem;">
                                <?php echo $current_lang === 'sw' ? 'Matukio Yote' : 'All Events'; ?> <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="benefits-section-home" id="benefits">
        <div class="container-child">
            <div class="section-heading-home reveal">
                <div class="section-badge"><i class="fas fa-star"></i> <?php echo $current_lang === 'sw' ? 'Kwa Nini Sisi?' : 'Why Us?'; ?></div>
                <h2 class="section-title-home"><?php echo $current_lang === 'sw' ? 'Kwa Nini Kona Ya Hisabati?' : 'Why Kona Ya Hisabati?'; ?></h2>
                <p class="section-subtitle-home"><?php echo $current_lang === 'sw' ? 'Tunatoa uzoefu bora wa kujifunza kwa watoto' : 'We provide the best learning experience for children'; ?></p>
            </div>
            <div class="row g-4 stagger-children">
                <?php if (empty($benefit_cards)): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="benefit-card-home">
                        <div class="benefit-icon-home" style="background:rgba(108,92,231,0.1);color:var(--home-accent);"><i class="fas fa-shield-alt"></i></div>
                        <h4 class="benefit-title-home"><?php echo $current_lang === 'sw' ? 'Salama kwa Watoto' : 'Child Safe'; ?></h4>
                        <p class="benefit-desc-home"><?php echo $current_lang === 'sw' ? 'Hakuna barua pepe kwa wanafunzi.' : 'No email required for learners.'; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="benefit-card-home">
                        <div class="benefit-icon-home" style="background:rgba(245,166,35,0.1);color:var(--home-gold);"><i class="fas fa-hand-pointer"></i></div>
                        <h4 class="benefit-title-home"><?php echo $current_lang === 'sw' ? 'Rahisi Kutumia' : 'Easy to Use'; ?></h4>
                        <p class="benefit-desc-home"><?php echo $current_lang === 'sw' ? 'Kiolesura kinachofaa watoto.' : 'Child-friendly, icon-based interface.'; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="benefit-card-home">
                        <div class="benefit-icon-home" style="background:rgba(0,206,209,0.1);color:#00ced1;"><i class="fas fa-mobile-alt"></i></div>
                        <h4 class="benefit-title-home"><?php echo $current_lang === 'sw' ? 'Kila Kifaa' : 'Any Device'; ?></h4>
                        <p class="benefit-desc-home"><?php echo $current_lang === 'sw' ? 'Simu, kompyuta kibao, au PC.' : 'Phone, tablet, or computer.'; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="benefit-card-home">
                        <div class="benefit-icon-home" style="background:rgba(80,200,120,0.1);color:#50C878;"><i class="fas fa-graduation-cap"></i></div>
                        <h4 class="benefit-title-home"><?php echo $current_lang === 'sw' ? 'Mtaala wa Tanzania' : 'Tanzania Curriculum'; ?></h4>
                        <p class="benefit-desc-home"><?php echo $current_lang === 'sw' ? 'Pre-Primary hisabati.' : 'Aligned with Pre-Primary numeracy.'; ?></p>
                    </div>
                </div>
                <?php else: ?>
                    <?php foreach ($benefit_cards as $card): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="benefit-card-home">
                            <div class="benefit-icon-home" style="background:rgba(108,92,231,0.1);color:var(--home-accent);">
                                <i class="fas <?php echo htmlspecialchars($card['icon']); ?>"></i>
                            </div>
                            <h4 class="benefit-title-home"><?php echo htmlspecialchars($current_lang === 'sw' ? $card['title_sw'] : $card['title_en']); ?></h4>
                            <p class="benefit-desc-home"><?php echo htmlspecialchars($current_lang === 'sw' ? $card['description_sw'] : $card['description_en']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php $gov_count = !empty($kyh_governance) ? count($kyh_governance) : 0; ?>
    <section class="governance-section-home">
        <div class="container-child">
            <div class="section-heading-home reveal">
                <div class="section-badge"><i class="fas fa-users"></i> <?php echo $current_lang === 'sw' ? 'Timu Yetu' : 'Our Team'; ?></div>
                <h2 class="section-title-home"><?php echo $current_lang === 'sw' ? 'Usimamizi na Walimu' : 'Management & Teachers'; ?></h2>
                <p class="section-subtitle-home"><?php echo $current_lang === 'sw' ? 'Wale ambao wanaendesha Kona Ya Hisabati' : 'The people behind Kona Ya Hisabati'; ?></p>
            </div>
            <div class="governance-carousel position-relative">
                <button type="button" class="governance-arrow governance-arrow-left" onclick="scrollGovernance(-1)" aria-label="Previous">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 g-4 flex-nowrap overflow-auto governance-scroll" id="governanceScroll">
                <?php foreach ($kyh_governance as $g): ?>
                <?php
                    $bc = $g['border_color'] ?? 'blue';
                    $border_hex = $color_map[$bc] ?? '#007bff';
                ?>
                <div class="col">
                    <div class="leader-card-home" style="border-bottom:3px solid <?php echo $border_hex; ?>;">
                        <div class="image-frame" style="overflow:hidden;">
                            <?php if ($g['image_path']): ?>
                                <img class="leader-avatar" src="<?php echo htmlspecialchars($g['image_path']); ?>" alt="<?php echo htmlspecialchars($g['name']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="fallback-icon" style="width:100%;aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.2);font-size:2.5rem;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="leader-info">
                            <h5 class="leader-name"><?php echo htmlspecialchars($g['name']); ?></h5>
                            <p class="leader-role"><?php echo htmlspecialchars($g['title']); ?></p>
                            <?php if ($g['profile_link']): ?>
                                <a href="<?php echo htmlspecialchars($g['profile_link']); ?>" target="_blank" rel="noopener" style="display:inline-flex;align-items:center;gap:4px;font-size:0.72rem;color:var(--home-gold-light);text-decoration:none;margin-top:8px;transition:color 0.3s;">
                                    <i class="fas fa-external-link-alt"></i> Profile
                                </a>
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
                <button type="button" class="hero-btn hero-btn-ghost" style="color:#fff;border-color:rgba(255,255,255,0.15);" data-bs-toggle="modal" data-bs-target="#governanceAllModal">
                    <?php echo $current_lang === 'sw' ? 'Wote' : 'View All'; ?> (<?php echo $gov_count; ?>) <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
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

    <button type="button" class="scroll-top-btn" id="scrollTopBtn" aria-label="Scroll to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <?php include 'php/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    /* ── Scroll Progress Bar ──────────────────────── */
    (function(){
        var bar = document.getElementById('scrollProgress');
        if (!bar) return;
        window.addEventListener('scroll', function(){
            var h = document.documentElement.scrollHeight - window.innerHeight;
            bar.style.width = h > 0 ? (window.scrollY / h * 100) + '%' : '0%';
        }, {passive:true});
    })();

    /* ── Custom Cursor ────────────────────────────── */
    (function(){
        var c = document.getElementById('customCursor');
        if (!c || 'ontouchstart' in window) return;
        document.addEventListener('mousemove', function(e){
            c.style.left = e.clientX + 'px';
            c.style.top = e.clientY + 'px';
        });
        document.querySelectorAll('a, button, .note-card-home, .benefit-card-home, .leader-card-home, .hero-btn, .event-item-home').forEach(function(el){
            el.addEventListener('mouseenter', function(){ c.classList.add('hover'); });
            el.addEventListener('mouseleave', function(){ c.classList.remove('hover'); });
        });
    })();

    /* ── Scroll Reveal (Intersection Observer) ────── */
    (function(){
        var targets = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children');
        if (!targets.length) return;
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    io.unobserve(entry.target);
                }
            });
        }, {threshold: 0.15, rootMargin: '0px 0px -40px 0px'});
        targets.forEach(function(t){ io.observe(t); });
    })();

    /* ── Counter Animation ────────────────────────── */
    (function(){
        var counters = document.querySelectorAll('.counter-value[data-target]');
        if (!counters.length) return;
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(entry){
                if (!entry.isIntersecting) return;
                var el = entry.target;
                var target = parseInt(el.getAttribute('data-target'), 10);
                var duration = 2000;
                var start = performance.now();
                function tick(now){
                    var elapsed = now - start;
                    var progress = Math.min(elapsed / duration, 1);
                    var eased = 1 - Math.pow(1 - progress, 3);
                    el.textContent = Math.floor(eased * target).toLocaleString();
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
                io.unobserve(el);
            });
        }, {threshold: 0.5});
        counters.forEach(function(c){ io.observe(c); });
    })();

    /* ── Scroll-to-top Button ─────────────────────── */
    (function(){
        var btn = document.getElementById('scrollTopBtn');
        if (!btn) return;
        window.addEventListener('scroll', function(){
            btn.classList.toggle('visible', window.scrollY > 400);
        }, {passive:true});
        btn.addEventListener('click', function(){
            window.scrollTo({top:0, behavior:'smooth'});
        });
    })();

    /* ── Navbar scroll effect ─────────────────────── */
    (function(){
        var nav = document.querySelector('.navbar-modern');
        if (!nav) return;
        nav.classList.add('navbar-home');
        window.addEventListener('scroll', function(){
            nav.classList.toggle('scrolled', window.scrollY > 60);
        }, {passive:true});
    })();

    /* ── Hero slider ──────────────────────────────── */
    (function() {
        var slides = document.querySelectorAll('.hero-bg-slide');
        if (slides.length > 1) {
            var style = document.createElement('style');
            var css = '';
            for (var i = 1; i <= slides.length; i++) {
                css += '.hero-bg-slide.slide-' + i + ' { animation: none !important; }';
            }
            style.textContent = css;
            document.head.appendChild(style);
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
                interval = setInterval(function() { goToSlide(current + 1); }, 5000);
            }
            window.heroSlide = function(dir) {
                goToSlide(current + dir);
                startInterval();
            };
            startInterval();
        }
    })();
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

    var startLearningRestricted = <?php echo is_start_learning_restricted() ? 'true' : 'false'; ?>;

    function handleStartLearning() {
        if (startLearningRestricted) {
            promptUsername();
        } else {
            window.location.href = 'learner/categories?lang=<?php echo $current_lang; ?>';
        }
    }

    function promptUsername() {
        Swal.fire({
            title: '<?php echo $current_lang === 'sw' ? 'Ingiza Jina Lako la Mtumiaji' : 'Enter Your Username'; ?>',
            text: '<?php echo $current_lang === 'sw' ? 'Weka jina lako la mtumiaji ulilopewa na mwalimu au mzazi wako' : 'Enter the username given by your teacher or parent'; ?>',
            input: 'text',
            inputPlaceholder: '<?php echo $current_lang === 'sw' ? 'Jina la mtumiaji' : 'Username'; ?>',
            showCancelButton: true,
            confirmButtonText: '<?php echo $current_lang === 'sw' ? 'Anza Kujifunza' : 'Start Learning'; ?>',
            cancelButtonText: '<?php echo $current_lang === 'sw' ? 'Ghairi' : 'Cancel'; ?>',
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            inputAttributes: { autocapitalize: 'off', autocomplete: 'off' },
            customClass: { popup: 'rounded-4', confirmButton: 'rounded-pill px-4 fw-bold', cancelButton: 'rounded-pill px-3' },
            buttonsStyling: true,
            preConfirm: function(username) {
                if (!username || !username.trim()) {
                    Swal.showValidationMessage('<?php echo $current_lang === 'sw' ? 'Tafadhali ingiza jina lako la mtumiaji' : 'Please enter your username'; ?>');
                    return false;
                }
                return fetch('api/check-learner.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'username=' + encodeURIComponent(username.trim())
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data.error) {
                        Swal.showValidationMessage(data.error);
                        return false;
                    }
                    return data;
                }).catch(function() {
                    Swal.showValidationMessage('<?php echo $current_lang === 'sw' ? 'Hitilafu ya mtandao. Tafadhali jaribu tena.' : 'Network error. Please try again.'; ?>');
                    return false;
                });
            },
            allowOutsideClick: function() { return !Swal.isLoading(); }
        }).then(function(result) {
            if (!result.value) return;
            var data = result.value;
            if (!data.exists) {
                Swal.fire({
                    icon: 'error',
                    title: '<?php echo $current_lang === 'sw' ? 'Jina Halijulikani' : 'Unknown Username'; ?>',
                    text: data.message || '<?php echo $current_lang === 'sw' ? 'Jina la mtumiaji halipo. Muulize mwalimu au mzazi wako.' : 'Username not found. Ask your teacher or parent.'; ?>',
                    confirmButtonColor: '#2563eb',
                    confirmButtonText: '<?php echo $current_lang === 'sw' ? 'Sawa' : 'OK'; ?>',
                    customClass: { popup: 'rounded-4', confirmButton: 'rounded-pill px-4 fw-bold' }
                });
            } else if (!data.can_access) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Malipo Yanahitajika',
                    text: data.message || 'Tafadhali mwambie mzazi wako alipe ada yako ili uweze kuendelea na masomo.',
                    confirmButtonColor: '#f59e0b',
                    confirmButtonText: '<?php echo $current_lang === 'sw' ? 'Sawa' : 'OK'; ?>',
                    customClass: { popup: 'rounded-4', confirmButton: 'rounded-pill px-4 fw-bold' }
                });
            } else if (data.redirect) {
                window.location.href = data.redirect;
            }
        });
    }

    </script>
    <script src="js/customizer.js"></script>
</body>
</html>
