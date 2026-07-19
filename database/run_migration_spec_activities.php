<?php
/**
 * MANUAL MIGRATION: Seed all Smart Math Corner spec activities
 *
 * Creates:
 *   1. Count Objects and Read Numbers 1-5 (5 activities)
 *   2. Count Objects and Read Numbers 6-9 (4 activities)
 *   3. Recognising Number 0 (3 activities)
 *   4. Recognising Number 10 (4 activities)
 *
 * Usage: php database/run_migration_spec_activities.php
 * Safe to run multiple times (skips existing records).
 */

require_once __DIR__ . '/../php/db_connection.php';

echo "=== Smart Math Corner: Spec Activities Migration ===\n\n";

function specData($engine, $extra = []) {
    return json_encode(array_merge([
        'engine' => $engine,
        'difficulty' => 1,
        'visual' => ['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

// --- Step 1: Ensure Number Zero and Number Ten modules exist ---
echo "Step 1: Ensuring modules exist...\n";

$modules = [
    'Number Zero' => ['desc'=>'Learn to recognise, trace, and find the number 0','icon'=>'fa-circle','color'=>'#9B59B6','audio'=>'Touch here for Number Zero!'],
    'Number Ten'  => ['desc'=>'Learn to recognise, read, write, and count to 10','icon'=>'fa-hands-helping','color'=>'#E67E22','audio'=>'Touch here for Number Ten!'],
];

foreach ($modules as $name => $m) {
    $exists = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = ?", [$name]);
    if (!$exists) {
        $maxOrder = (int)$database->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM modules")['mx'];
        $database->execute(
            "INSERT INTO modules (module_name, module_description, module_icon, module_color, audio_prompt, order_index, is_active)
             VALUES (?, ?, ?, ?, ?, ?, 1)",
            [$name, $m['desc'], $m['icon'], $m['color'], $m['audio'], $maxOrder + 1]
        );
        echo "  + Created module: $name\n";
    } else {
        echo "  OK module: $name (id={$exists['module_id']})\n";
    }
}

// --- Step 2: Get module IDs ---
$mod14 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name LIKE '%Recognising%Numbers%1-9%' AND module_name NOT LIKE '%Counting%'");
$modZero = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Zero'");
$modTen = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Ten'");

if (!$mod14) {
    $mod14 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name LIKE '%Recognising%Counting%Numbers%'");
}
if (!$mod14) {
    echo "ERROR: Cannot find module for Numbers 1-9. Aborting.\n";
    exit(1);
}

$mod14Id = (int)$mod14['module_id'];
$modZeroId = $modZero ? (int)$modZero['module_id'] : 0;
$modTenId = $modTen ? (int)$modTen['module_id'] : 0;

echo "\nModule IDs: 1-9=$mod14Id, Zero=$modZeroId, Ten=$modTenId\n\n";

// --- Cleanup: fully reset spec activities and lessons ---
echo "Cleaning old spec data...\n";
$database->execute("DELETE FROM activities WHERE activity_type IN ('spec_count_objects','spec_zero_plate','spec_zero_drag','spec_zero_tap','spec_ten_tap','spec_ten_drag','spec_ten_match','spec_ten_balloon')");
$database->execute("DELETE FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%'");
$database->execute("DELETE FROM topics WHERE topic_code LIKE 'NUM-SPEC-%'");
echo "  Done.\n\n";

// --- Helper: ensure topic ---
function ensureTopic($database, $moduleId, $topicName, $topicCode) {
    $t = $database->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? AND topic_code = ? LIMIT 1", [$moduleId, $topicCode]);
    if ($t) return (int)$t['topic_id'];
    try {
        $maxOrder = (int)$database->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM topics WHERE module_id = ?", [$moduleId])['mx'];
        $database->execute(
            "INSERT IGNORE INTO topics (module_id, topic_name, topic_code, order_index, is_active) VALUES (?, ?, ?, ?, 1)",
            [$moduleId, $topicName, $topicCode, $maxOrder + 1]
        );
    } catch (Exception $e) {
        echo "  WARN: topic insert: " . $e->getMessage() . "\n";
    }
    $t = $database->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? AND topic_code = ? LIMIT 1", [$moduleId, $topicCode]);
    return $t ? (int)$t['topic_id'] : 0;
}

// --- Helper: create lesson ---
function ensureLesson($database, $moduleId, $topicId, $code, $name, $desc) {
    $l = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", [$code]);
    if ($l) return (int)$l['lesson_id'];
    try {
        $maxOrder = (int)$database->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM lessons WHERE topic_id = ?", [$topicId])['mx'];
        $database->execute(
            "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, order_index, is_active)
             VALUES (?, ?, ?, ?, ?, 20, ?, 1)",
            [$topicId, $code, $name, $desc, $desc, $maxOrder + 1]
        );
    } catch (Exception $e) {
        echo "  WARN: lesson insert: " . $e->getMessage() . "\n";
    }
    $l = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", [$code]);
    return $l ? (int)$l['lesson_id'] : 0;
}

// --- Helper: insert activity ---
function insertActivity($database, $moduleId, $lessonId, $stepType, $idx, $name, $desc, $engine, $data, $audio) {
    $exists = $database->fetchOne("SELECT activity_id FROM activities WHERE lesson_id = ? AND activity_name = ? LIMIT 1", [$lessonId, $name]);
    if ($exists) {
        echo "  - exists: $name\n";
        return;
    }
    try {
        $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 1)",
            [$moduleId, $lessonId, $stepType, $idx, $idx, $name, $desc, $engine, $data, $audio]
        );
        echo "  + created: $name\n";
    } catch (Exception $e) {
        echo "  ERROR: $name: " . $e->getMessage() . "\n";
    }
}

// =============================================
// LESSON 1: Count Objects and Read Numbers 1-5
// =============================================
echo "Step 2: Count Objects 1-5...\n";
$topic14 = ensureTopic($database, $mod14Id, 'Counting Objects 1-9', 'NUM-SPEC-TOPIC-14');
$l1 = ensureLesson($database, $mod14Id, $topic14, 'NUM-SPEC-L01', 'Count Objects and Read Numbers 1-5', 'Count objects and identify numbers 1 to 5.');

$acts1to5 = [
    ['warmup', 'Count One Orange', 'Tap one orange then select number 1.', 'spec_count_objects',
     ['object'=>'orange','count'=>1,'correct_number'=>1,'numbers'=>[1,2,3],'tap_audio'=>'Tap one orange.','success_audio'=>'One orange. Number one. Well done!'],
     'Tap one orange.'],
    ['warmup', 'Count Two Mangoes', 'Tap two mangoes then select number 2.', 'spec_count_objects',
     ['object'=>'mango','count'=>2,'correct_number'=>2,'numbers'=>[1,2,3],'tap_audio'=>'Tap two mangoes.','success_audio'=>'Two mangoes. Number two. Well done!'],
     'Tap two mangoes.'],
    ['warmup', 'Count Three Pencils', 'Tap three pencils then select number 3.', 'spec_count_objects',
     ['object'=>'pencil','count'=>3,'correct_number'=>3,'numbers'=>[1,2,3,4],'tap_audio'=>'Tap three pencils.','success_audio'=>'Three pencils. Number three. Well done!'],
     'Tap three pencils.'],
    ['warmup', 'Count Four Apples', 'Tap four apples then select number 4.', 'spec_count_objects',
     ['object'=>'apple','count'=>4,'correct_number'=>4,'numbers'=>[2,1,3,4,5],'tap_audio'=>'Tap four apples.','success_audio'=>'Four apples. Number four. Well done!'],
     'Tap four apples.'],
    ['warmup', 'Count Five Cups', 'Tap five cups then select number 5.', 'spec_count_objects',
     ['object'=>'cup','count'=>5,'correct_number'=>5,'numbers'=>[1,2,3,4,5],'tap_audio'=>'Tap five cups.','success_audio'=>'Five cups. Number five. Well done!'],
     'Tap five cups.'],
];
foreach ($acts1to5 as $i => $a) {
    insertActivity($database, $mod14Id, $l1, $a[0], $i, $a[1], $a[2], $a[3], specData($a[3], $a[4]), $a[5]);
}

// =============================================
// LESSON 2: Count Objects and Read Numbers 6-9
// =============================================
echo "\nStep 3: Count Objects 6-9...\n";
$l2 = ensureLesson($database, $mod14Id, $topic14, 'NUM-SPEC-L02', 'Count Objects and Read Numbers 6-9', 'Count objects and identify numbers 6 to 9.');

$acts6to9 = [
    ['warmup', 'Count Six Chairs', 'Tap six chairs then select number 6.', 'spec_count_objects',
     ['object'=>'chair','count'=>6,'correct_number'=>6,'numbers'=>[1,2,3,4,5,6],'tap_audio'=>'Tap six chairs.','success_audio'=>'Six chairs. Number six. Well done!'],
     'Tap six chairs.'],
    ['warmup', 'Count Seven Plates', 'Tap seven plates then select number 7.', 'spec_count_objects',
     ['object'=>'plate','count'=>7,'correct_number'=>7,'numbers'=>[2,4,5,7,1],'tap_audio'=>'Tap seven plates.','success_audio'=>'Seven plates. Number seven. Well done!'],
     'Tap seven plates.'],
    ['warmup', 'Count Eight Sticks', 'Tap eight sticks then select number 8.', 'spec_count_objects',
     ['object'=>'stick','count'=>8,'correct_number'=>8,'numbers'=>[4,7,1,3,2,8],'tap_audio'=>'Tap eight sticks.','success_audio'=>'Eight sticks. Number eight. Well done!'],
     'Tap eight sticks.'],
    ['warmup', 'Count Nine Trees', 'Tap nine trees then select number 9.', 'spec_count_objects',
     ['object'=>'tree','count'=>9,'correct_number'=>9,'numbers'=>[1,2,3,4,5,6,8,9],'tap_audio'=>'Tap nine trees.','success_audio'=>'Nine trees. Number nine. Well done!'],
     'Tap nine trees.'],
];
foreach ($acts6to9 as $i => $a) {
    insertActivity($database, $mod14Id, $l2, $a[0], $i, $a[1], $a[2], $a[3], specData($a[3], $a[4]), $a[5]);
}

// =============================================
// LESSON 3: Recognising Number 0
// =============================================
if ($modZeroId) {
    echo "\nStep 4: Recognising Number 0...\n";
    $topicZero = ensureTopic($database, $modZeroId, 'Understanding Zero', 'NUM-SPEC-TOPIC-0');
    $l3 = ensureLesson($database, $modZeroId, $topicZero, 'NUM-SPEC-L03', 'Recognising Number 0', 'Understand that zero means no objects.');

    $zeroActs = [
        ['warmup', 'Tap the Empty Plate', 'Three plates appear: 2 oranges, 1 orange, empty. Tap the empty plate.', 'spec_zero_plate',
         ['difficulty'=>1,'object'=>'orange'], 'Tap the plate with no oranges.'],
        ['we_do', 'Drag Empty to Zero', 'Drag pictures with no objects into the Zero box.', 'spec_zero_drag',
         ['difficulty'=>1], 'Drag the pictures with no objects to the box labeled Zero.'],
        ['check', 'Tap Number Zero', 'Find and tap the number 0 from 0, 2, 5, 7.', 'spec_zero_tap',
         ['difficulty'=>1], 'Tap number zero.'],
    ];
    foreach ($zeroActs as $i => $a) {
        insertActivity($database, $modZeroId, $l3, $a[0], $i, $a[1], $a[2], $a[3], specData($a[3], $a[4]), $a[5]);
    }
} else {
    echo "\nStep 4: SKIP (Number Zero module not found)\n";
}

// =============================================
// LESSON 4: Recognising Number 10
// =============================================
if ($modTenId) {
    echo "\nStep 5: Recognising Number 10...\n";
    $topicTen = ensureTopic($database, $modTenId, 'Understanding Ten', 'NUM-SPEC-TOPIC-10');
    $l4 = ensureLesson($database, $modTenId, $topicTen, 'NUM-SPEC-L04', 'Recognising Number 10', 'Identify, drag, match, and pop number 10.');

    $tenActs = [
        ['warmup', 'Tap Number Ten', 'Find and tap the number 10 from 7, 10, 4, 9.', 'spec_ten_tap',
         ['difficulty'=>1], 'Tap number ten.'],
        ['we_do', 'Drag Ten into Box', 'A yellow box labeled 10 and numbers 6, 10, 8. Drag 10 into the yellow box.', 'spec_ten_drag',
         ['difficulty'=>1], 'Drag number ten into the yellow box.'],
        ['you_do', 'Match Ten with Apples', 'Three groups: 8 apples, 10 apples, 6 apples. Drag number 10 to the group with 10 apples.', 'spec_ten_match',
         ['difficulty'=>1,'object'=>'apple'], 'Match number ten with the group that has ten apples.'],
        ['game', 'Pop Balloon Ten', 'Balloons labeled 5, 10, 7, 9. Pop the balloon with 10.', 'spec_ten_balloon',
         ['difficulty'=>1], 'Pop the balloon with number ten.'],
    ];
    foreach ($tenActs as $i => $a) {
        insertActivity($database, $modTenId, $l4, $a[0], $i, $a[1], $a[2], $a[3], specData($a[3], $a[4]), $a[5]);
    }
} else {
    echo "\nStep 5: SKIP (Number Ten module not found)\n";
}

// --- Verify ---
echo "\n=== Verification ===\n";
$lessons = $database->fetchAll("
    SELECT l.lesson_code, l.lesson_name, m.module_name, COUNT(a.activity_id) as act_count
    FROM lessons l
    JOIN modules m ON l.module_id = m.module_id
    LEFT JOIN activities a ON a.lesson_id = l.lesson_id AND a.is_active = 1
    WHERE l.lesson_code LIKE 'NUM-SPEC-%'
    GROUP BY l.lesson_id
    ORDER BY l.lesson_code
");
foreach ($lessons as $l) {
    echo "  {$l['lesson_code']}: {$l['lesson_name']} ({$l['module_name']}) — {$l['act_count']} activities\n";
}

echo "\nDone! Refresh your browser.\n";
