<?php
/**
 * Insert sample ticker messages
 * Visit: https://smartmathconner.co.tz/database/insert_sample_tickers.php
 */

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== INSERT SAMPLE TICKER MESSAGES ===\n\n";

$messages = [
    ['message' => 'Karibu Kwa Ya Hisabati! Jifunze Hisabati kwa furaha na uweze. / Welcome to Kona Ya Hisabati! Learn Mathematics with joy and succeed.', 'sort_order' => 1],
    ['message' => 'Wanafunzi waliojiunga wiki hii: ' . ($database->fetchOne("SELECT COUNT(*) as c FROM users WHERE role='learner' AND YEARWEEK(created_at,1) = YEARWEEK(CURDATE(),1)")['c'] ?? 0) . ' mpya. / New students this week. Karibu ndugu!', 'sort_order' => 2],
    ['message' => 'Shughuli mpya zimeongezwa! Angalia masomo mapya ya Nambari 1 hadi 10. / New activities added! Check out Numbers 1-10 lessons.', 'sort_order' => 3],
    ['message' => 'Pigia simu: +255 655 879 005 kwa msaada. / Call us for support. Tuko hapa kukusaidia!', 'sort_order' => 4],
];

$inserted = 0;
foreach ($messages as $msg) {
    $exists = $database->fetchOne(
        "SELECT ticker_id FROM announcement_ticker WHERE message = ?",
        [$msg['message']]
    );
    if ($exists) {
        echo "  SKIP: '{$msg['message']}' (already exists)\n";
        continue;
    }
    $database->execute(
        "INSERT INTO announcement_ticker (message, is_active, sort_order) VALUES (?, 1, ?)",
        [$msg['message'], $msg['sort_order']]
    );
    $inserted++;
    echo "  INSERTED: '{$msg['message']}'\n";
}

echo "\nInserted: $inserted\n";

$all = $database->fetchAll("SELECT ticker_id, LEFT(message, 80) as msg, is_active FROM announcement_ticker ORDER BY sort_order");
echo "\nAll tickers:\n";
foreach ($all as $t) {
    $status = $t['is_active'] ? 'ACTIVE' : 'inactive';
    echo "  [{$t['ticker_id']}] ({$status}) {$t['msg']}...\n";
}

echo "\n=== DONE ===\n";
echo "</pre>";
