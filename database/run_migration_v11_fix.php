<?php
/**
 * Phase 6 — Migration Fix: Upsert + Validate NUM-03 Activities
 *
 * Safe to re-run (idempotent).
 * 1. Creates topic + lessons if missing (calls v11a)
 * 2. Upserts all 80 activities (calls v11b)
 * 3. Validates JSON, engine whitelist
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 6 Fix: NUM-03 Upsert + Validate ===\n\n";

/* 1. Ensure topic + lessons exist */
require __DIR__ . '/run_migration_v11a.php';
echo "\n";

/* 1b. Get correct module_id */
$mod = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Ten' LIMIT 1");
$module_id = $mod ? (int)$mod['module_id'] : 14;
echo "module_id = $module_id\n\n";

/* 2. Load activity definitions */
require __DIR__ . '/run_migration_v11b.php';

/* 3. Build lesson_code → lesson_id map */
$L = [];
$rows = $database->fetchAll("SELECT lesson_id, lesson_code FROM lessons WHERE lesson_code LIKE 'NUM-03-%'");
foreach ($rows as $r) { $L[$r['lesson_code']] = (int)$r['lesson_id']; }
echo "Lessons found: " . count($L) . "\n";
if (count($L) < 8) { echo "ERROR: expected 8 NUM-03 lessons, found " . count($L) . "\n"; exit(1); }

/* 4. Upsert activities */
$updated = 0;
$inserted = 0;
$errors = 0;
foreach ($acts as [$lesson_code, $step_type, $step_order, $name, $desc, $json]) {
    $lid = $L[$lesson_code] ?? null;
    if (!$lid) { echo "SKIP: unknown lesson $lesson_code\n"; $errors++; continue; }

    $data = json_decode($json, true);
    if (!$data || !isset($data['engine'])) { echo "SKIP: invalid JSON for $name\n"; $errors++; continue; }

    $diff = $data['difficulty'] <= 1 ? 'easy' : ($data['difficulty'] <= 2 ? 'medium' : 'hard');

    $existing = $database->fetchOne(
        "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? AND step_order = ?",
        [$lid, $step_type, $step_order]
    );

    if ($existing) {
        $database->execute(
            "UPDATE activities SET activity_name=?, activity_description=?, difficulty_level=?, activity_data=?, audio_instruction=? WHERE activity_id=?",
            [$name, $desc, $diff, $json, $data['instruction'], $existing['activity_id']]
        );
        $updated++;
    } else {
        $database->execute(
            "INSERT INTO activities (module_id,lesson_id,step_type,step_order,activity_name,activity_description,activity_type,difficulty_level,activity_data,audio_instruction) VALUES (?,?,?,?,?,?,'counting',?,?,?)",
            [$module_id, $lid, $step_type, $step_order, $name, $desc, $diff, $json, $data['instruction']]
        );
        $inserted++;
    }
}

echo "\nUpsert: $inserted inserted, $updated updated, $errors errors\n";

/* 5. Validate */
$check = $database->fetchAll("SELECT a.activity_id, a.activity_name, a.activity_data FROM activities a JOIN lessons l ON a.lesson_id = l.lesson_id WHERE l.lesson_code LIKE 'NUM-03-%' ORDER BY l.order_index, a.step_order");
echo "\nTotal NUM-03 activities in DB: " . count($check) . "\n";

$valid_engines = ['mango_counting','number_identification','number_sequencing','missing_numbers','match_quantity','dot_to_dot','math_game','pattern_counting'];
$json_ok = 0;
$json_err = 0;
$engine_ok = 0;
$engine_err = 0;

foreach ($check as $c) {
    $d = json_decode($c['activity_data'], true);
    if ($d && isset($d['engine']) && isset($d['instruction']) && isset($d['answer'])) { $json_ok++; } else { $json_err++; echo "  JSON ERROR: {$c['activity_name']}\n"; }
    if ($d && in_array($d['engine'], $valid_engines)) { $engine_ok++; } else { $engine_err++; echo "  ENGINE ERROR: {$c['activity_name']} engine=" . ($d['engine'] ?? '?') . "\n"; }
}

echo "\nValidation:\n";
echo "  JSON valid:    $json_ok / " . count($check) . ($json_err ? " ($json_err errors)" : " ✓") . "\n";
echo "  Engine valid:  $engine_ok / " . count($check) . ($engine_err ? " ($engine_err errors)" : " ✓") . "\n";

/* 6. Activities per lesson */
echo "\nActivities per lesson:\n";
$perLesson = $database->fetchAll("SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt FROM activities a JOIN lessons l ON a.lesson_id = l.lesson_id WHERE l.lesson_code LIKE 'NUM-03-%' GROUP BY l.lesson_code ORDER BY l.order_index");
foreach ($perLesson as $pl) {
    $mark = $pl['cnt'] === 10 ? '✓' : "✗ ({$pl['cnt']})";
    echo "  {$pl['lesson_code']}: {$pl['lesson_name']} — {$pl['cnt']} activities $mark\n";
}

echo "\nDone.\n";
