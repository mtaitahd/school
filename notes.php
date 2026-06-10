<?php
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/lang.php';

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$page_title = 'Kona Ya Hisabati | Notes';
$page_description = 'News and updates from Kona Ya Hisabati.';

$slug = trim($_GET['slug'] ?? '');
$note = null;

if ($slug !== '') {
    $note = $database->fetchOne("SELECT * FROM notes WHERE slug = ? AND status = 'published'", [$slug]);
}

$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$note && $note_id) {
    $note = $database->fetchOne("SELECT * FROM notes WHERE id = ? AND status = 'published'", [$note_id]);
}

if (!$note) {
    http_response_code(404);
    $not_found = true;
} else {
    $page_title = htmlspecialchars($note['title']) . ' - Kona Ya Hisabati';
    $page_description = htmlspecialchars(mb_strimwidth(strip_tags($note['short_description'] ?: $note['full_content'] ?: ''), 0, 160, '...'));
}

$recent_notes = $database->fetchAll("SELECT id, title, slug, featured_image, short_description, publish_date, created_at FROM notes WHERE status = 'published'" . ($note ? " AND id != " . (int)$note['id'] : "") . " ORDER BY publish_date DESC, created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang === 'sw' ? 'sw' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php if ($note): ?>
    <meta property="og:title" content="<?php echo htmlspecialchars($note['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <?php if ($note['featured_image']): ?>
    <meta property="og:image" content="<?php echo 'https://' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '/' . htmlspecialchars($note['featured_image']); ?>">
    <?php endif; ?>
    <meta property="og:type" content="article">
    <link rel="canonical" href="<?php echo 'https://' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . '/notes/' . htmlspecialchars($note['slug']); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-child">
    <?php include 'php/includes/header.php'; ?>

    <main class="container-child" style="padding-top:32px;padding-bottom:48px;">
        <?php if (isset($not_found) && $not_found): ?>
            <div style="text-align:center;padding:80px 20px;">
                <i class="fas fa-file-alt" style="font-size:3rem;color:var(--primary-blue);opacity:0.4;margin-bottom:16px;display:block;"></i>
                <h1 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);"><?php echo $current_lang === 'sw' ? 'Hatujakuta' : 'Note Not Found'; ?></h1>
                <p style="color:var(--text-light);max-width:400px;margin:12px auto 24px;">
                    <?php echo $current_lang === 'sw' ? 'Hatukuweza kupata taarifa uliyotafuta.' : 'The note you are looking for could not be found.'; ?>
                </p>
                <a href="index" class="kyh-board-card-btn" style="display:inline-flex;"><?php echo $current_lang === 'sw' ? 'Rudi Nyumbani' : 'Back to Home'; ?></a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-8">
                    <article>
                        <?php if ($note['featured_image']): ?>
                        <div style="margin-bottom:24px;border-radius:var(--border-radius-lg);overflow:hidden;">
                            <img src="<?php echo htmlspecialchars($note['featured_image']); ?>" alt="<?php echo htmlspecialchars($note['title']); ?>" style="width:100%;max-height:400px;object-fit:cover;display:block;">
                        </div>
                        <?php endif; ?>
                        <h1 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);font-size:clamp(1.5rem,3vw,2rem);margin:0 0 12px;">
                            <?php echo htmlspecialchars($note['title']); ?>
                        </h1>
                        <div style="display:flex;align-items:center;gap:16px;color:var(--text-light);font-size:0.9rem;margin-bottom:20px;flex-wrap:wrap;">
                            <span><i class="fas fa-calendar-alt" style="color:var(--primary-blue);margin-right:6px;"></i><?php echo date('F j, Y', strtotime($note['publish_date'] ?: $note['created_at'])); ?></span>
                        </div>
                        <?php if ($note['short_description']): ?>
                        <p style="font-size:1.05rem;color:var(--text-dark);line-height:1.7;margin-bottom:20px;font-weight:500;">
                            <?php echo nl2br(htmlspecialchars($note['short_description'])); ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($note['full_content']): ?>
                        <div style="font-size:1rem;color:var(--text-dark);line-height:1.8;">
                            <?php echo nl2br(htmlspecialchars($note['full_content'])); ?>
                        </div>
                        <?php endif; ?>
                    </article>
                </div>
                <div class="col-md-4">
                    <div style="background:#fff;border-radius:var(--border-radius-lg);padding:24px;box-shadow:var(--shadow-soft);">
                        <h3 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);font-size:1.1rem;margin:0 0 16px;padding-bottom:12px;border-bottom:2px solid var(--primary-yellow);">
                            <?php echo $current_lang === 'sw' ? 'Taarifa za Hivi Punde' : 'Recent Notes'; ?>
                        </h3>
                        <?php if (empty($recent_notes)): ?>
                            <p style="color:var(--text-light);font-size:0.9rem;margin:0;"><?php echo $current_lang === 'sw' ? 'Hakuna taarifa nyingine.' : 'No other notes.'; ?></p>
                        <?php else: ?>
                            <ul style="list-style:none;padding:0;margin:0;">
                                <?php foreach ($recent_notes as $r): ?>
                                <li style="padding:10px 0;border-bottom:1px solid #f0f0f0;">
                                    <a href="notes/<?php echo htmlspecialchars($r['slug']); ?>" style="color:var(--navbar-dark);text-decoration:none;font-weight:600;font-size:0.9rem;display:block;line-height:1.4;">
                                        <?php echo htmlspecialchars($r['title']); ?>
                                    </a>
                                    <span style="font-size:0.78rem;color:var(--text-light);margin-top:4px;display:block;">
                                        <i class="fas fa-calendar-alt" style="color:var(--primary-blue);margin-right:4px;"></i>
                                        <?php echo date('M j, Y', strtotime($r['publish_date'] ?: $r['created_at'])); ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include 'php/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
