<?php
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/lang.php';

$slug = trim($_GET['slug'] ?? '');
$id = trim($_GET['id'] ?? '');
$not_found = false;
$announcement = null;

if ($slug !== '') {
    $stmt = $database->query(
        "SELECT * FROM announcements WHERE slug = ? AND status = 'published' LIMIT 1",
        [$slug]
    );
    $announcement = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
} elseif ($id !== '') {
    $stmt = $database->query(
        "SELECT * FROM announcements WHERE announcement_id = ? AND status = 'published' LIMIT 1",
        [(int)$id]
    );
    $announcement = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
}

if (!$announcement) {
    $not_found = true;
    $page_title = 'Announcement Not Found | Kona Ya Hisabati';
    $page_description = 'The requested announcement could not be found.';
    $canonical_path = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/announcement.php';
} else {
    $page_title = htmlspecialchars($announcement['title']) . ' | Kona Ya Hisabati';
    $page_description = htmlspecialchars($announcement['short_description'] ?: mb_strimwidth(strip_tags($announcement['content']), 0, 160, '...'));

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $slug_clean = rawurlencode($announcement['slug']);
    $canonical_path = $protocol . '://' . $host . '/announcement/' . $slug_clean;

    // Read time estimate
    $word_count = str_word_count(strip_tags($announcement['content']));
    $read_time = max(1, ceil($word_count / 200));

    // Recent 5 announcements
    $recent_stmt = $database->query(
        "SELECT announcement_id, title, slug, short_description, created_at FROM announcements WHERE status = 'published' AND announcement_id != ? ORDER BY created_at DESC LIMIT 5",
        [$announcement['announcement_id']]
    );
    $recent_announcements = $recent_stmt ? $recent_stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

$current_lang = $_GET['lang'] ?? 'en';
$base_path = '';
$active_nav = 'home';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang === 'sw' ? 'sw' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php include 'php/includes/seo-head.php'; ?>
<?php if (!$not_found && $announcement && !empty($announcement['image'])): ?>
    <meta property="og:image" content="<?php echo htmlspecialchars($protocol . '://' . $host . '/' . ltrim($announcement['image'], '/')); ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($protocol . '://' . $host . '/' . ltrim($announcement['image'], '/')); ?>">
<?php endif; ?>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .announcement-detail-section {
            padding: 48px 0 64px;
        }
        .announcement-detail-main {
            flex: 0 0 68%;
            max-width: 68%;
            padding: 15px;
        }
        .announcement-detail-sidebar {
            flex: 0 0 32%;
            max-width: 32%;
            padding: 15px;
        }
        .announcement-hero-img {
            width: 100%;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-soft);
            margin-bottom: 24px;
            object-fit: cover;
            max-height: 420px;
        }
        .announcement-title {
            font-family: 'Poppins', sans-serif;
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 800;
            color: var(--navbar-dark);
            margin: 0 0 16px;
            line-height: 1.3;
        }
        .announcement-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 24px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #eee;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        .announcement-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .announcement-meta-item i {
            color: var(--primary-blue);
            width: 16px;
            text-align: center;
        }
        .announcement-content {
            font-size: 1rem;
            line-height: 1.8;
            color: var(--text-dark);
        }
        .announcement-content p {
            margin-bottom: 16px;
        }
        .announcement-back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 32px;
            font-weight: 600;
            color: var(--primary-blue);
            text-decoration: none;
            transition: gap 0.2s;
        }
        .announcement-back-link:hover {
            gap: 12px;
            color: var(--primary-blue-dark);
        }
        .announcement-not-found {
            text-align: center;
            padding: 80px 20px;
        }
        .announcement-not-found i {
            font-size: 3.5rem;
            color: var(--primary-blue);
            opacity: 0.3;
            margin-bottom: 16px;
        }
        .announcement-not-found h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            color: var(--navbar-dark);
            margin: 0 0 10px;
        }
        .announcement-not-found p {
            color: var(--text-light);
            margin-bottom: 24px;
        }

        /* Sidebar styles */
        .sidebar-widget {
            background: var(--background-white);
            border-radius: var(--border-radius-lg);
            padding: 24px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 24px;
        }
        .sidebar-widget-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--navbar-dark);
            margin: 0 0 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .sidebar-announcement-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .sidebar-announcement-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .sidebar-announcement-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .sidebar-announcement-item:first-child {
            padding-top: 0;
        }
        .sidebar-announcement-link {
            text-decoration: none;
            display: block;
        }
        .sidebar-announcement-link:hover .sidebar-announcement-title {
            color: var(--primary-blue);
        }
        .sidebar-announcement-date {
            font-size: 0.78rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 4px;
        }
        .sidebar-announcement-date i {
            font-size: 0.7rem;
            color: var(--primary-blue);
        }
        .sidebar-announcement-title {
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dark);
            transition: color 0.2s;
            line-height: 1.4;
        }
        .sidebar-announcement-excerpt {
            font-size: 0.82rem;
            color: var(--text-light);
            margin: 4px 0 0;
            line-height: 1.4;
        }
        .sidebar-empty {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        @media (max-width: 992px) {
            .announcement-detail-main,
            .announcement-detail-sidebar {
                flex: 0 0 100%;
                max-width: 100%;
            }
            .announcement-detail-section {
                padding: 32px 0 48px;
            }
        }
    </style>
</head>
<body class="page-child">
    <?php include 'php/includes/header.php'; ?>

    <section class="announcement-detail-section">
        <div class="container-child">
            <?php if ($not_found): ?>
            <div class="announcement-not-found">
                <i class="fas fa-newspaper"></i>
                <h2><?php echo $current_lang === 'sw' ? 'Tangazo Halijapatikana' : 'Announcement Not Found'; ?></h2>
                <p><?php echo $current_lang === 'sw' ? 'Samahani, tangazo ulililotafuta halipo au limefutwa.' : 'Sorry, the announcement you are looking for does not exist or has been removed.'; ?></p>
                <a href="index<?php echo $current_lang !== 'en' ? '?lang=' . urlencode($current_lang) : ''; ?>" class="kyh-board-card-btn">
                    <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'sw' ? 'Rudi Nyumbani' : 'Back to Home'; ?>
                </a>
            </div>
            <?php else: ?>
            <div class="row-child">
                <div class="announcement-detail-main">
                    <?php if (!empty($announcement['image']) && file_exists($announcement['image'])): ?>
                    <img src="<?php echo htmlspecialchars($announcement['image']); ?>"
                         alt="<?php echo htmlspecialchars($announcement['title']); ?>"
                         class="announcement-hero-img"
                         loading="eager">
                    <?php endif; ?>

                    <h1 class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></h1>

                    <div class="announcement-meta">
                        <span class="announcement-meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo htmlspecialchars(date('F j, Y', strtotime($announcement['created_at']))); ?>
                        </span>
                        <span class="announcement-meta-item">
                            <i class="fas fa-clock"></i>
                            <?php echo $read_time; ?> <?php echo $current_lang === 'sw' ? 'dakika soma' : 'min read'; ?>
                        </span>
                        <?php if (!empty($announcement['event_date'])): ?>
                        <span class="announcement-meta-item">
                            <i class="fas fa-calendar-check"></i>
                            <?php echo $current_lang === 'sw' ? 'Tarehe ya tukio' : 'Event date' ; ?>: <?php echo htmlspecialchars(date('F j, Y', strtotime($announcement['event_date']))); ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="announcement-content">
                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                    </div>

                    <a href="index<?php echo $current_lang !== 'en' ? '?lang=' . urlencode($current_lang) : ''; ?>" class="announcement-back-link">
                        <i class="fas fa-arrow-left"></i> <?php echo $current_lang === 'sw' ? 'Rudi kwenye Ukurasa wa Mwanzo' : 'Back to Home Page'; ?>
                    </a>
                </div>

                <aside class="announcement-detail-sidebar">
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget-title">
                            <i class="fas fa-clock" style="color:var(--primary-blue);margin-right:6px;"></i>
                            <?php echo $current_lang === 'sw' ? 'Matangazo ya Hivi Punde' : 'Recent Announcements'; ?>
                        </h3>
                        <?php if (empty($recent_announcements)): ?>
                            <p class="sidebar-empty"><?php echo $current_lang === 'sw' ? 'Hakuna matangazo ya hivi punde.' : 'No recent announcements.'; ?></p>
                        <?php else: ?>
                        <ul class="sidebar-announcement-list">
                            <?php foreach ($recent_announcements as $recent): ?>
                            <li class="sidebar-announcement-item">
                                <a href="/announcement/<?php echo rawurlencode($recent['slug']); ?>" class="sidebar-announcement-link">
                                    <span class="sidebar-announcement-date">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo htmlspecialchars(date('M j, Y', strtotime($recent['created_at']))); ?>
                                    </span>
                                    <span class="sidebar-announcement-title"><?php echo htmlspecialchars($recent['title']); ?></span>
                                    <?php if (!empty($recent['short_description'])): ?>
                                    <p class="sidebar-announcement-excerpt"><?php echo htmlspecialchars(mb_strimwidth($recent['short_description'], 0, 100, '...')); ?></p>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'php/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
