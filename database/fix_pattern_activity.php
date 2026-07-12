<?php
/**
 * Quick fix: Update the "Pattern: Count 1 to 5" activity data
 * to use pattern_objects instead of object:'butterfly'
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Fixing Pattern Count activity ===\n\n";

$act = $database->fetchOne(
    "SELECT activity_id, activity_data FROM activities WHERE module_id = 14 AND activity_name = ?",
    ['Pattern: Count 1 to 5']
);

if (!$act) {
    echo "Activity not found!\n";
    exit(1);
}

$data = json_decode($act['activity_data'], true);
echo "Current engine: " . ($data['engine'] ?? '?') . "\n";
echo "Current object: " . ($data['object'] ?? 'N/A') . "\n";

/* Fix: replace 'object' with 'pattern_objects' */
unset($data['object']);
$data['pattern_objects'] = ['fly', 'butterfly', 'bird', 'mosquito', 'bee'];

$newJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$database->query("UPDATE activities SET activity_data = ? WHERE activity_id = ?", [$newJson, $act['activity_id']]);

echo "\nUpdated!\n";
echo "New pattern_objects: " . json_encode($data['pattern_objects']) . "\n";
echo "Done.\n";
