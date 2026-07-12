<?php
/**
 * Fix: Remove duplicate activities
 *
 * Finds duplicate activities within each lesson (same step_type + step_order)
 * and across modules (same activity_name + lesson).
 * Keeps the one with the latest activity_id (most recent data).
 *
 * Safe to re-run (idempotent).
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Fix: Remove Duplicate Activities ===\n\n";

$deleted = 0;

/* 1. Remove duplicates within each lesson (same step_type + step_order) */
echo "Step 1: Duplicates within same lesson (same step_type + step_order)\n";
$dupes = $database->fetchAll("
    SELECT lesson_id, step_type, step_order, COUNT(*) as cnt,
           GROUP_CONCAT(activity_id ORDER BY activity_id DESC) as ids
    FROM activities
    GROUP BY lesson_id, step_type, step_order
    HAVING cnt > 1
");

foreach ($dupes as $d) {
    $ids = explode(',', $d['ids']);
    $keep = array_shift($ids); /* keep the latest (highest ID) */
    foreach ($ids as $delId) {
        $database->execute("DELETE FROM activities WHERE activity_id = ?", [(int)$delId]);
        $deleted++;
        echo "  Deleted activity_id=$delId (duplicate of $keep in lesson_id={$d['lesson_id']}, {$d['step_type']}={$d['step_order']})\n";
    }
}

/* 2. Remove duplicates across modules (same activity_name in same lesson) */
echo "\nStep 2: Duplicates with same name in same lesson\n";
$dupes2 = $database->fetchAll("
    SELECT lesson_id, activity_name, COUNT(*) as cnt,
           GROUP_CONCAT(activity_id ORDER BY activity_id DESC) as ids
    FROM activities
    GROUP BY lesson_id, activity_name
    HAVING cnt > 1
");

foreach ($dupes2 as $d) {
    $ids = explode(',', $d['ids']);
    $keep = array_shift($ids);
    foreach ($ids as $delId) {
        $database->execute("DELETE FROM activities WHERE activity_id = ?", [(int)$delId]);
        $deleted++;
        echo "  Deleted activity_id=$delId (duplicate of '$keep' — same name in lesson_id={$d['lesson_id']})\n";
    }
}

/* 3. Remove orphaned activities (lesson_id not in lessons table) */
echo "\nStep 3: Orphaned activities (lesson_id not in lessons table)\n";
$orphaned = $database->fetchAll("
    SELECT a.activity_id, a.lesson_id, a.activity_name
    FROM activities a
    LEFT JOIN lessons l ON a.lesson_id = l.lesson_id
    WHERE l.lesson_id IS NULL
");
foreach ($orphaned as $o) {
    $database->execute("DELETE FROM activities WHERE activity_id = ?", [(int)$o['activity_id']]);
    $deleted++;
    echo "  Deleted orphaned activity_id={$o['activity_id']} (lesson_id={$o['lesson_id']})\n";
}

/* 4. Remove activities still stuck on module 14 that have copies on correct modules */
echo "\nStep 4: Activities on module 14 that have copies on correct modules\n";
$moved = $database->fetchAll("
    SELECT a1.activity_id as old_id, a2.activity_id as new_id, a1.activity_name
    FROM activities a1
    JOIN activities a2 ON a1.lesson_id = a2.lesson_id
        AND a1.step_type = a2.step_type
        AND a1.step_order = a2.step_order
        AND a1.activity_id != a2.activity_id
    WHERE a1.module_id = 14
");
foreach ($moved as $m) {
    $database->execute("DELETE FROM activities WHERE activity_id = ?", [(int)$m['old_id']]);
    $deleted++;
    echo "  Deleted old module-14 activity_id={$m['old_id']} (kept new {$m['new_id']}: '{$m['activity_name']}')\n";
}

echo "\n=== Summary ===\n";
echo "Total deleted: $deleted\n\n";

/* 5. Final state per module */
echo "Final activity counts per module:\n";
$final = $database->fetchAll("
    SELECT m.module_id, m.module_name,
           (SELECT COUNT(*) FROM activities a WHERE a.module_id = m.module_id) as act_count
    FROM modules m WHERE m.is_active = 1 ORDER BY m.order_index
");
foreach ($final as $f) {
    echo "  ID={$f['module_id']} | {$f['module_name']} | {$f['act_count']} activities\n";
}

/* 6. Activities per lesson for NUM-02, NUM-03, NUM-04 */
echo "\nActivities per lesson (NUM-02/03/04):\n";
$perLesson = $database->fetchAll("
    SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
    FROM activities a
    JOIN lessons l ON a.lesson_id = l.lesson_id
    WHERE l.lesson_code LIKE 'NUM-0%'
    GROUP BY l.lesson_code
    ORDER BY l.lesson_code
");
foreach ($perLesson as $pl) {
    $mark = $pl['cnt'] === 10 ? '✓' : "✗ ({$pl['cnt']})";
    echo "  {$pl['lesson_code']}: {$pl['lesson_name']} — {$pl['cnt']} activities $mark\n";
}

echo "\nDone.\n";
