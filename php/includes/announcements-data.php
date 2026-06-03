<?php
// Legacy urgent announcement (top bar) — single most recent urgent
$kyh_urgent = $database->fetchOne(
    "SELECT * FROM announcements WHERE is_urgent = 1 AND status = 'published' ORDER BY created_at DESC LIMIT 1"
);

// Legacy standard announcements (notice board) — published, non-urgent
$kyh_standard = $database->fetchAll(
    "SELECT * FROM announcements WHERE is_urgent = 0 AND status = 'published' ORDER BY created_at DESC"
);

// Ticker items for scrolling ticker bar
$kyh_ticker_items = $database->fetchAll(
    "SELECT * FROM announcement_ticker WHERE is_active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE()) ORDER BY sort_order ASC, ticker_id ASC"
);

// Hero slides for dynamic hero slider
$kyh_hero_slides = $database->fetchAll(
    "SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY sort_order ASC, slide_id ASC"
);

// Governance / Leadership data
$kyh_governance = $database->fetchAll(
    "SELECT * FROM governance ORDER BY sort_order ASC, id ASC"
);

// Latest published announcements (paginated, for homepage "Latest Announcements")
$kyh_latest_page = max(1, intval($_GET['announce_page'] ?? 1));
$kyh_latest_per = 6;
$kyh_latest_offset = ($kyh_latest_page - 1) * $kyh_latest_per;
$kyh_latest_total = $database->fetchOne(
    "SELECT COUNT(*) AS cnt FROM announcements WHERE status = 'published'"
);
$kyh_latest_total = $kyh_latest_total ? intval($kyh_latest_total['cnt']) : 0;
$kyh_latest_total_pages = max(1, (int)ceil($kyh_latest_total / $kyh_latest_per));
$kyh_latest = $database->fetchAll(
    "SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT ? OFFSET ?",
    [$kyh_latest_per, $kyh_latest_offset]
);
