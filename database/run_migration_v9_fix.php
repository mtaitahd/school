<?php
/**
 * Diagnostic + Fix: Check lessons and activities state,
 * then insert/upsert all 80 activities with rich activity_data.
 * Run: php database/run_migration_v9_fix.php
 */
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/db_connection.php';
require __DIR__ . '/run_migration_v9b.php'; // Only loads the function + $acts

$db = new Database();

echo "=== Diagnostic ===\n";

// Check lessons
$lessons = $db->fetchAll("SELECT lesson_id, lesson_code, lesson_name FROM lessons ORDER BY lesson_code");
echo "Lessons found: " . count($lessons) . "\n";
foreach ($lessons as $l) {
  echo "  {$l['lesson_code']} (id={$l['lesson_id']}): {$l['lesson_name']}\n";
}

// If no lessons, create them
if (count($lessons) === 0) {
  echo "\nNo lessons found. Creating structure from a.php...\n";
  require __DIR__ . '/run_migration_v9a.php';
  $lessons = $db->fetchAll("SELECT lesson_id, lesson_code FROM lessons ORDER BY lesson_code");
  echo "After a.php: " . count($lessons) . " lessons\n";
}

// Build $L map
$L = [];
foreach ($lessons as $l) {
  $L[$l['lesson_code']] = $l['lesson_id'];
}

echo "\nLesson ID map:\n";
foreach ($L as $k => $v) echo "  $k => $v\n";

if (count($L) < 8) {
  echo "\nERROR: Only " . count($L) . " lessons found. Need 8.\n";
  exit(1);
}

echo "\n=== Phase 2: Activity Data ===\n";
// $acts is already loaded from run_migration_v9b.php

echo "Total activity definitions loaded: " . count($acts) . "\n";

$cnt = 0;
foreach ($acts as $a) {
  [$code, $st, $so, $name, $desc, $djson] = $a;
  $lid = $L[$code] ?? null;
  if (!$lid) { echo "ERROR: Missing lesson for $code\n"; continue; }
  
  $data = json_decode($djson, true);
  $diff = $data['difficulty'] ?? 'easy';
  
  $existing = $db->fetchOne("SELECT activity_id FROM activities WHERE lesson_id=? AND step_type=? AND step_order=?", [$lid, $st, $so]);
  if ($existing) {
    $db->execute("UPDATE activities SET activity_name=?, activity_description=?, activity_data=?, audio_instruction=?, difficulty_level=? WHERE activity_id=?",
      [$name, $desc, $djson, $desc, $diff, $existing['activity_id']]);
    $cnt++;
  } else {
    $db->execute("INSERT INTO activities (module_id,lesson_id,step_type,step_order,activity_name,activity_description,activity_type,difficulty_level,activity_data,audio_instruction) VALUES (14,?,?,?,?,?,'counting',?,?,?)",
      [$lid, $st, $so, $name, $desc, $diff, $djson, $desc]);
    $cnt++;
  }
}
echo "Processed $cnt activities.\n";

echo "\n=== Validation ===\n";
foreach (['NUM-01-L01','NUM-01-L02','NUM-01-L03','NUM-01-L04','NUM-01-L05','NUM-01-L06','NUM-01-L07','NUM-01-L08'] as $c) {
  $r = $db->fetchOne("SELECT COUNT(*) AS cnt FROM activities WHERE lesson_id=?", [$L[$c]]);
  echo "  $c: {$r['cnt']} activities\n";
}
$t = $db->fetchOne("SELECT COUNT(*) AS cnt FROM activities WHERE module_id=14 AND lesson_id IS NOT NULL");
echo "  Total: {$t['cnt']}\n";

$bad = 0;
foreach ($db->fetchAll("SELECT activity_id, activity_data FROM activities WHERE module_id=14 AND lesson_id IS NOT NULL") as $r) {
  $d = json_decode($r['activity_data'], true);
  if (!$d) { echo "  INVALID JSON: {$r['activity_id']}\n"; $bad++; continue; }
  $missing = array_diff(['instruction','objective','content','answer','feedback'], array_keys($d));
  if ($missing) { echo "  Missing ({$r['activity_id']}): " . implode(',',$missing) . "\n"; $bad++; }
}
echo ($bad ? "  FAIL: $bad errors\n" : "  ✓ All have valid JSON with required fields\n");

echo "\n✓ Fix complete.\n";
