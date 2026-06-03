<?php
require_once 'php/db_connection.php';
require_once 'php/includes/lang.php';

$current_lang = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$base_path = '';
$page_title = 'Kona Ya Hisabati | Events';
$page_description = 'Upcoming events from Kona Ya Hisabati.';

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = null;

if ($event_id) {
    $event = $database->fetchOne("SELECT * FROM events WHERE id = ? AND status = 'published'", [$event_id]);
}

if (!$event) {
    $all_events = $database->fetchAll("SELECT * FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC");
} else {
    $page_title = htmlspecialchars($event['event_title']) . ' - Kona Ya Hisabati';
    $page_description = htmlspecialchars(mb_strimwidth(strip_tags($event['event_description'] ?: ''), 0, 160, '...'));
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang === 'sw' ? 'sw' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="page-child">
    <?php include 'php/includes/header.php'; ?>

    <main class="container-child" style="padding-top:32px;padding-bottom:48px;">
        <?php if ($event): ?>
            <div class="row">
                <div class="col-md-8">
                    <article>
                        <h1 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);font-size:clamp(1.5rem,3vw,2rem);margin:0 0 12px;">
                            <?php echo htmlspecialchars($event['event_title']); ?>
                        </h1>
                        <div style="display:flex;align-items:center;gap:16px;color:var(--text-light);font-size:0.9rem;margin-bottom:20px;flex-wrap:wrap;">
                            <span><i class="fas fa-calendar-alt" style="color:var(--primary-blue);margin-right:6px;"></i><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                            <?php if ($event['event_time']): ?>
                            <span><i class="fas fa-clock" style="color:var(--primary-blue);margin-right:6px;"></i><?php echo htmlspecialchars($event['event_time']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($event['event_description']): ?>
                        <div style="font-size:1rem;line-height:1.7;color:var(--text-dark);">
                            <?php echo nl2br(htmlspecialchars($event['event_description'])); ?>
                        </div>
                        <?php endif; ?>
                    </article>
                </div>
            </div>
        <?php else: ?>
            <h1 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);font-size:clamp(1.5rem,3vw,2rem);margin:0 0 24px;">
                <?php echo $current_lang === 'sw' ? 'Matukio' : 'Upcoming Events'; ?>
            </h1>
            <?php if (empty($all_events)): ?>
                <div style="text-align:center;padding:60px 20px;">
                    <i class="fas fa-calendar-day" style="font-size:3rem;color:var(--primary-blue);opacity:0.4;margin-bottom:16px;display:block;"></i>
                    <p style="color:var(--text-light);font-size:1.1rem;"><?php echo $current_lang === 'sw' ? 'Hakuna matukio yaliyopangwa kwa sasa.' : 'No upcoming events at this time.'; ?></p>
                    <a href="index.php" class="kyh-board-card-btn" style="display:inline-flex;"><?php echo $current_lang === 'sw' ? 'Rudi Nyumbani' : 'Back to Home'; ?></a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($all_events as $e): ?>
                    <?php $dt = new DateTime($e['event_date']); ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100" style="border:1px solid #e2e8f0;border-radius:0;">
                            <div class="card-body d-flex gap-3 p-4">
                                <div style="text-align:center;flex-shrink:0;width:60px;">
                                    <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:800;color:var(--primary-blue);line-height:1;"><?php echo $dt->format('d'); ?></div>
                                    <div style="font-family:'Poppins',sans-serif;font-size:0.85rem;font-weight:700;color:#000;text-transform:uppercase;"><?php echo $dt->format('M'); ?></div>
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <h5 style="font-family:'Poppins',sans-serif;font-size:0.95rem;font-weight:700;color:var(--navbar-dark);margin:0 0 4px;">
                                        <?php if ($e['event_description']): ?>
                                        <a href="events.php?id=<?php echo (int)$e['id']; ?>" style="text-decoration:none;color:inherit;"><?php echo htmlspecialchars($e['event_title']); ?></a>
                                        <?php else: ?>
                                        <?php echo htmlspecialchars($e['event_title']); ?>
                                        <?php endif; ?>
                                    </h5>
                                    <?php if ($e['event_time']): ?>
                                    <div style="font-size:0.78rem;color:var(--text-light);"><i class="fas fa-clock" style="color:var(--primary-blue);font-size:0.7rem;margin-right:4px;"></i><?php echo htmlspecialchars($e['event_time']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php include 'php/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/theme.js"></script>
</body>
</html>
