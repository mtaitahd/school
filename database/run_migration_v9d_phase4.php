<?php
/**
 * Phase 4 Migration: Add audio + visual config to all 80 activity_data records.
 * 
 * Safe to re-run (idempotent).
 * - Adds "audio" and "visual" keys if missing
 * - Does NOT alter engine, content, answer, or any curriculum fields
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 4 Migration: Audio + Visual Config ===\n\n";

$acts = $database->fetchAll("SELECT activity_id, activity_data FROM activities WHERE module_id = 14 ORDER BY activity_id");
echo "Found " . count($acts) . " activities\n\n";

$updated = 0;
$errors = 0;

foreach ($acts as $act) {
    $data = json_decode($act['activity_data'], true);
    if (!$data) {
        echo "SKIP #{$act['activity_id']}: invalid JSON\n";
        $errors++;
        continue;
    }

    $changed = false;

    /* Add audio config if missing */
    if (!isset($data['audio'])) {
        $data['audio'] = [
            'instruction' => $data['instruction'] ?? '',
            'number_name' => '',
            'enabled' => false
        ];
        $changed = true;
    }

    /* Add visual config if missing */
    if (!isset($data['visual'])) {
        $data['visual'] = [
            'theme' => 'numbers',
            'background' => 'light',
            'show_progress' => true,
            'large_numbers' => true,
            'large_objects' => true,
            'animation' => 'fade'
        ];
        $changed = true;
    }

    if ($changed) {
        $newJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $database->query("UPDATE activities SET activity_data = ? WHERE activity_id = ?", [$newJson, $act['activity_id']]);
        $updated++;
    }
}

echo "\n=== Results ===\n";
echo "Updated: $updated\n";
echo "Errors:  $errors\n";
echo "Total:   " . count($acts) . "\n";

/* Validate all have audio + visual */
$check = $database->fetchAll("SELECT activity_id, activity_data FROM activities WHERE module_id = 14");
$missing_audio = 0;
$missing_visual = 0;
foreach ($check as $c) {
    $d = json_decode($c['activity_data'], true);
    if (!$d || !isset($d['audio'])) $missing_audio++;
    if (!$d || !isset($d['visual'])) $missing_visual++;
}
echo "\nValidation: " . $missing_audio . " missing audio, " . $missing_visual . " missing visual\n";

if ($missing_audio === 0 && $missing_visual === 0) {
    echo "All 80 activities have audio + visual config.\n";
}

echo "\nDone.\n";
