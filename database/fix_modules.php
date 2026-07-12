<?php
/**
 * Fix: Remove duplicate modules and re-run module assignment
 *
 * Safe to re-run.
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Fix: Cleanup Duplicate Modules ===\n\n";

/* 1. Remove duplicate empty modules (keep the ones with activities) */
$dupes = $database->fetchAll("SELECT module_id, module_name FROM modules WHERE module_name IN ('Number Zero','Number Ten','Numbers 11–20') ORDER BY module_id");
$seen = [];
$toDelete = [];
foreach ($dupes as $d) {
    $name = $d['module_name'];
    if (isset($seen[$name])) {
        /* check if this one has activities */
        $cnt = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = ?", [$d['module_id']])['cnt'];
        if ($cnt === 0) {
            $toDelete[] = $d['module_id'];
            echo "  Will delete duplicate: module_id={$d['module_id']} ({$name}, 0 activities)\n";
        }
    } else {
        $seen[$name] = $d['module_id'];
    }
}

/* Also check if the "kept" ones have activities, and if not, swap */
foreach ($seen as $name => $keptId) {
    $cnt = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = ?", [$keptId])['cnt'];
    if ($cnt === 0) {
        /* find the other one with activities */
        foreach ($dupes as $d) {
            if ($d['module_name'] === $name && $d['module_id'] !== $keptId) {
                $otherCnt = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = ?", [$d['module_id']])['cnt'];
                if ($otherCnt > 0) {
                    /* swap: move activities to the kept one, delete the other */
                    $database->execute("UPDATE activities SET module_id = ? WHERE module_id = ?", [$keptId, $d['module_id']]);
                    $database->execute("UPDATE topics SET module_id = ? WHERE module_id = ?", [$keptId, $d['module_id']]);
                    echo "  Swapped $otherCnt activities from module_id={$d['module_id']} → {$keptId}\n";
                    $toDelete[] = $d['module_id'];
                }
            }
        }
    }
}

/* Delete empty duplicates */
foreach ($toDelete as $delId) {
    $database->execute("DELETE FROM modules WHERE module_id = ?", [$delId]);
    echo "  Deleted module_id=$delId\n";
}

/* 2. Re-assign any orphaned activities */
/* Find NUM-02, NUM-03, NUM-04 lessons that are still on module 14 */
$orphanTopics = [
    'NUM-02' => null,
    'NUM-03' => null,
    'NUM-04' => null,
];

foreach (array_keys($orphanTopics) as $tc) {
    $mod = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = ? LIMIT 1", [
        $tc === 'NUM-02' ? 'Number Zero' : ($tc === 'NUM-03' ? 'Number Ten' : 'Numbers 11–20')
    ]);
    if ($mod) $orphanTopics[$tc] = (int)$mod['module_id'];
}

foreach ($orphanTopics as $tc => $newModId) {
    if (!$newModId) continue;
    $lessons = $database->fetchAll("SELECT lesson_id FROM lessons WHERE lesson_code LIKE ?", [$tc . '-%']);
    $ids = array_column($lessons, 'lesson_id');
    if (empty($ids)) continue;
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $database->execute("UPDATE activities SET module_id = ? WHERE lesson_id IN ($ph) AND module_id = 14", array_merge([$newModId], $ids));
    $cnt = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = ? AND lesson_id IN ($ph)", array_merge([$newModId], $ids))['cnt'];
    echo "  Re-assigned $cnt activities for $tc → module_id=$newModId\n";
}

/* 3. Final state */
echo "\n=== Final Module State ===\n";
$all = $database->fetchAll("
    SELECT m.module_id, m.module_name, m.module_icon, m.module_color, m.order_index,
           (SELECT COUNT(*) FROM activities a WHERE a.module_id = m.module_id) as act_count
    FROM modules m WHERE m.is_active = 1 ORDER BY m.order_index
");
foreach ($all as $a) {
    echo "  ID={$a['module_id']} | {$a['module_name']} | {$a['act_count']} activities | idx={$a['order_index']}\n";
}

echo "\nDone.\n";
