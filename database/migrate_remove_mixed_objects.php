<?php
/**
 * Migration: Remove mixed_objects from counting activities
 *
 * Sets mixed_objects = false on all mango_counting activities
 * so only the target object (e.g. pencils) is shown — no distractors.
 *
 * Visit: https://smartmathconner.co.tz/database/migrate_remove_mixed_objects.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== Remove Mixed Objects from Counting Activities ===\n\n";

$updated = 0;

$activities = $database->fetchAll(
    "SELECT a.activity_id, a.activity_data, a.activity_name, l.lesson_code
     FROM activities a
     LEFT JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE a.activity_type = 'counting' AND a.is_active = 1"
);

foreach ($activities as $ac) {
    $data = json_decode($ac['activity_data'], true) ?: [];
    if (($data['engine'] ?? '') !== 'mango_counting') continue;
    if (($data['mode'] ?? '') !== 'count') continue;

    if (($data['mixed_objects'] ?? true) !== false) {
        $data['mixed_objects'] = false;
        $database->execute(
            "UPDATE activities SET activity_data = ? WHERE activity_id = ?",
            [json_encode($data), $ac['activity_id']]
        );
        $updated++;
        echo "  Fixed: {$ac['lesson_code']} — {$ac['activity_name']}\n";
    }
}

echo "\nTotal updated: $updated activities\n";
echo "\n=== MIGRATION COMPLETE ===\n";
echo "</pre>";
