<?php
/**
 * MANUAL MIGRATION: Seed Smart Math Corner spec activities
 * Usage: php database/run_migration_spec_activities.php
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Smart Math Corner: Spec Activities Migration ===\n\n";

function sd($engine, $extra = []) {
    return json_encode(array_merge([
        'engine' => $engine, 'difficulty' => 1,
        'visual' => ['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function getOrCreateTopic($db, $moduleId, $name, $code) {
    $t = $db->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? AND topic_code = ? LIMIT 1", [$moduleId, $code]);
    if ($t) { echo "  topic: id={$t['topic_id']} (exists)\n"; return (int)$t['topic_id']; }
    // Find a valid strand_id
    $strand = $db->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM' LIMIT 1");
    if (!$strand) $strand = $db->fetchOne("SELECT strand_id FROM strands LIMIT 1");
    if (!$strand) { echo "  ERROR: no strands table rows!\n"; return 0; }
    $strandId = (int)$strand['strand_id'];
    $mx = (int)$db->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM topics WHERE module_id = ?", [$moduleId])['mx'];
    try {
        $db->execute("INSERT INTO topics (strand_id, module_id, topic_name, topic_code, order_index, is_active) VALUES (?, ?, ?, ?, ?, 1)", [$strandId, $moduleId, $name, $code, $mx + 1]);
    } catch (Exception $e) {
        echo "  ERROR creating topic: " . $e->getMessage() . "\n";
        return 0;
    }
    $t = $db->fetchOne("SELECT topic_id FROM topics WHERE module_id = ? AND topic_code = ? LIMIT 1", [$moduleId, $code]);
    if (!$t) { echo "  ERROR: topic not found after insert!\n"; return 0; }
    echo "  topic: id={$t['topic_id']} (created)\n";
    return (int)$t['topic_id'];
}

function getOrCreateLesson($db, $topicId, $code, $name, $obj, $crit) {
    $l = $db->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", [$code]);
    if ($l) { echo "  lesson: id={$l['lesson_id']} '$name' (exists)\n"; return (int)$l['lesson_id']; }
    $mx = (int)$db->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM lessons WHERE topic_id = ?", [$topicId])['mx'];
    try {
        $db->execute(
            "INSERT INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, order_index, is_active) VALUES (?, ?, ?, ?, ?, 20, ?, 1)",
            [$topicId, $code, $name, $obj, $crit, $mx + 1]
        );
    } catch (Exception $e) {
        echo "  ERROR creating lesson '$code': " . $e->getMessage() . "\n";
        return 0;
    }
    $l = $db->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", [$code]);
    if (!$l) { echo "  ERROR: lesson '$code' not found after insert!\n"; return 0; }
    echo "  lesson: id={$l['lesson_id']} '$name' (created)\n";
    return (int)$l['lesson_id'];
}

function addAct($db, $modId, $lessonId, $step, $idx, $name, $desc, $engine, $data, $audio) {
    $x = $db->fetchOne("SELECT activity_id FROM activities WHERE lesson_id = ? AND activity_name = ? LIMIT 1", [$lessonId, $name]);
    if ($x) { echo "    - exists: $name\n"; return; }
    try {
        $db->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, 1)",
            [$modId, $lessonId, $step, $idx, $idx, $name, $desc, $engine, $data, $audio]
        );
        echo "    + created: $name\n";
    } catch (Exception $e) {
        echo "    ERROR: $name: " . $e->getMessage() . "\n";
    }
}

// Step 1: Ensure modules
echo "Step 1: Modules...\n";
foreach (['Number Zero'=>['d'=>'Learn to recognise, trace, and find the number 0','i'=>'fa-circle','c'=>'#9B59B6'],'Number Ten'=>['d'=>'Learn to recognise, read, write, and count to 10','i'=>'fa-hands-helping','c'=>'#E67E22']] as $nm => $m) {
    $e = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = ?", [$nm]);
    if (!$e) {
        $mx = (int)$database->fetchOne("SELECT COALESCE(MAX(order_index),0) as mx FROM modules")['mx'];
        $database->execute("INSERT INTO modules (module_name,module_description,module_icon,module_color,audio_prompt,order_index,is_active) VALUES (?,?,?,?,?,?,1)", [$nm,$m['d'],$m['i'],$m['c'],"Touch here for $nm!",$mx+1]);
        echo "  + Created: $nm\n";
    } else { echo "  OK: $nm (id={$e['module_id']})\n"; }
}

$mod14 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name LIKE '%Recognising%Numbers%1-9%' AND module_name NOT LIKE '%Counting%'");
if (!$mod14) $mod14 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name LIKE '%Recognising%Counting%Numbers%'");
$modZero = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Zero'");
$modTen = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Ten'");
$M14 = (int)$mod14['module_id'];
$MZ = $modZero ? (int)$modZero['module_id'] : 0;
$MT = $modTen ? (int)$modTen['module_id'] : 0;
echo "  IDs: 1-9=$M14 Zero=$MZ Ten=$MT\n\n";

// Cleanup
echo "Step 2: Cleanup...\n";
$database->execute("DELETE FROM activities WHERE activity_name IN ('Count One Orange','Count Two Mangoes','Count Three Pencils','Count Four Apples','Count Five Cups','Count Six Chairs','Count Seven Plates','Count Eight Sticks','Count Nine Trees','Tap the Empty Plate','Drag Empty to Zero','Tap Number Zero','Tap Number Ten','Drag Ten into Box','Match Ten with Apples','Pop Balloon Ten')");
$database->execute("DELETE FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%'");
$database->execute("DELETE FROM topics WHERE topic_code LIKE 'NUM-SPEC-%'");
echo "  Cleaned.\n\n";

// Counting 1-5
echo "Step 3: Count Objects 1-5...\n";
$t1 = getOrCreateTopic($database, $M14, 'Counting Objects 1-9', 'NUM-SPEC-TOPIC-14');
$l1 = getOrCreateLesson($database, $t1, 'NUM-SPEC-L01', 'Count Objects and Read Numbers 1-5', 'Count objects and identify numbers 1 to 5.', 'Can count objects and match numbers 1 to 5.');
if ($l1) {
    $d = [
        ['warmup',0,'Count One Orange','Tap one orange then select number 1.','spec_count_objects',['object'=>'orange','count'=>1,'correct_number'=>1,'numbers'=>[1,2,3],'tap_audio'=>'Tap one orange.','success_audio'=>'One orange. Number one. Well done!'],'Tap one orange.'],
        ['warmup',1,'Count Two Mangoes','Tap two mangoes then select number 2.','spec_count_objects',['object'=>'mango','count'=>2,'correct_number'=>2,'numbers'=>[1,2,3],'tap_audio'=>'Tap two mangoes.','success_audio'=>'Two mangoes. Number two. Well done!'],'Tap two mangoes.'],
        ['warmup',2,'Count Three Pencils','Tap three pencils then select number 3.','spec_count_objects',['object'=>'pencil','count'=>3,'correct_number'=>3,'numbers'=>[1,2,3,4],'tap_audio'=>'Tap three pencils.','success_audio'=>'Three pencils. Number three. Well done!'],'Tap three pencils.'],
        ['warmup',3,'Count Four Apples','Tap four apples then select number 4.','spec_count_objects',['object'=>'apple','count'=>4,'correct_number'=>4,'numbers'=>[2,1,3,4,5],'tap_audio'=>'Tap four apples.','success_audio'=>'Four apples. Number four. Well done!'],'Tap four apples.'],
        ['warmup',4,'Count Five Cups','Tap five cups then select number 5.','spec_count_objects',['object'=>'cup','count'=>5,'correct_number'=>5,'numbers'=>[1,2,3,4,5],'tap_audio'=>'Tap five cups.','success_audio'=>'Five cups. Number five. Well done!'],'Tap five cups.'],
    ];
    foreach ($d as $a) addAct($database,$M14,$l1,$a[0],$a[1],$a[2],$a[3],$a[4],sd($a[4],$a[5]),$a[6]);
} else { echo "  SKIP: lesson not created\n"; }

// Counting 6-9
echo "\nStep 4: Count Objects 6-9...\n";
$l2 = getOrCreateLesson($database, $t1, 'NUM-SPEC-L02', 'Count Objects and Read Numbers 6-9', 'Count objects and identify numbers 6 to 9.', 'Can count objects and match numbers 6 to 9.');
if ($l2) {
    $d = [
        ['warmup',0,'Count Six Chairs','Tap six chairs then select number 6.','spec_count_objects',['object'=>'chair','count'=>6,'correct_number'=>6,'numbers'=>[1,2,3,4,5,6],'tap_audio'=>'Tap six chairs.','success_audio'=>'Six chairs. Number six. Well done!'],'Tap six chairs.'],
        ['warmup',1,'Count Seven Plates','Tap seven plates then select number 7.','spec_count_objects',['object'=>'plate','count'=>7,'correct_number'=>7,'numbers'=>[2,4,5,7,1],'tap_audio'=>'Tap seven plates.','success_audio'=>'Seven plates. Number seven. Well done!'],'Tap seven plates.'],
        ['warmup',2,'Count Eight Sticks','Tap eight sticks then select number 8.','spec_count_objects',['object'=>'stick','count'=>8,'correct_number'=>8,'numbers'=>[4,7,1,3,2,8],'tap_audio'=>'Tap eight sticks.','success_audio'=>'Eight sticks. Number eight. Well done!'],'Tap eight sticks.'],
        ['warmup',3,'Count Nine Trees','Tap nine trees then select number 9.','spec_count_objects',['object'=>'tree','count'=>9,'correct_number'=>9,'numbers'=>[1,2,3,4,5,6,8,9],'tap_audio'=>'Tap nine trees.','success_audio'=>'Nine trees. Number nine. Well done!'],'Tap nine trees.'],
    ];
    foreach ($d as $a) addAct($database,$M14,$l2,$a[0],$a[1],$a[2],$a[3],$a[4],sd($a[4],$a[5]),$a[6]);
} else { echo "  SKIP: lesson not created\n"; }

// Number 0
echo "\nStep 5: Recognising Number 0...\n";
if ($MZ) {
    $tZ = getOrCreateTopic($database, $MZ, 'Understanding Zero', 'NUM-SPEC-TOPIC-0');
    $lZ = getOrCreateLesson($database, $tZ, 'NUM-SPEC-L03', 'Recognising Number 0', 'Understand that zero means no objects.', 'Can identify that zero means nothing.');
    if ($lZ) {
        $d = [
            ['warmup',0,'Tap the Empty Plate','Three plates appear: 2 oranges, 1 orange, empty. Tap the empty plate.','spec_zero_plate',['difficulty'=>1,'object'=>'orange'],'Tap the plate with no oranges.'],
            ['we_do',1,'Drag Empty to Zero','Drag pictures with no objects into the Zero box.','spec_zero_drag',['difficulty'=>1],'Drag the pictures with no objects to the box labeled Zero.'],
            ['check',2,'Tap Number Zero','Find and tap the number 0 from 0, 2, 5, 7.','spec_zero_tap',['difficulty'=>1],'Tap number zero.'],
        ];
        foreach ($d as $a) addAct($database,$MZ,$lZ,$a[0],$a[1],$a[2],$a[3],$a[4],sd($a[4],$a[5]),$a[6]);
    } else { echo "  SKIP: lesson not created\n"; }
} else { echo "  SKIP: module not found\n"; }

// Number 10
echo "\nStep 6: Recognising Number 10...\n";
if ($MT) {
    $tT = getOrCreateTopic($database, $MT, 'Understanding Ten', 'NUM-SPEC-TOPIC-10');
    $lT = getOrCreateLesson($database, $tT, 'NUM-SPEC-L04', 'Recognising Number 10', 'Identify, drag, match, and pop number 10.', 'Can identify number 10 in different ways.');
    if ($lT) {
        $d = [
            ['warmup',0,'Tap Number Ten','Find and tap the number 10 from 7, 10, 4, 9.','spec_ten_tap',['difficulty'=>1],'Tap number ten.'],
            ['we_do',1,'Drag Ten into Box','A yellow box labeled 10 and numbers 6, 10, 8. Drag 10 into the yellow box.','spec_ten_drag',['difficulty'=>1],'Drag number ten into the yellow box.'],
            ['you_do',2,'Match Ten with Apples','Three groups: 8 apples, 10 apples, 6 apples. Drag number 10 to the group with 10 apples.','spec_ten_match',['difficulty'=>1,'object'=>'apple'],'Match number ten with the group that has ten apples.'],
            ['game',3,'Pop Balloon Ten','Balloons labeled 5, 10, 7, 9. Pop the balloon with 10.','spec_ten_balloon',['difficulty'=>1],'Pop the balloon with number ten.'],
        ];
        foreach ($d as $a) addAct($database,$MT,$lT,$a[0],$a[1],$a[2],$a[3],$a[4],sd($a[4],$a[5]),$a[6]);
    } else { echo "  SKIP: lesson not created\n"; }
} else { echo "  SKIP: module not found\n"; }

// Verify
echo "\n=== Verification ===\n";
$check = $database->fetchAll("
    SELECT l.lesson_code, l.lesson_name, COUNT(a.activity_id) as cnt
    FROM lessons l
    LEFT JOIN activities a ON a.lesson_id = l.lesson_id AND a.is_active = 1
    WHERE l.lesson_code LIKE 'NUM-SPEC-%'
    GROUP BY l.lesson_id ORDER BY l.lesson_code
");
if (empty($check)) {
    echo "  WARNING: No lessons found! Checking raw...\n";
    $raw = $database->fetchAll("SELECT lesson_id, lesson_code, lesson_name, topic_id FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%'");
    foreach ($raw as $r) echo "    raw: id={$r['lesson_id']} code={$r['lesson_code']} topic={$r['topic_id']}\n";
    $raw2 = $database->fetchAll("SELECT activity_id, activity_name, lesson_id FROM activities WHERE activity_name LIKE '%Orange%' OR activity_name LIKE '%Plate%' OR activity_name LIKE '%Ten%' LIMIT 5");
    foreach ($raw2 as $r) echo "    act: id={$r['activity_id']} lesson_id={$r['lesson_id']} name={$r['activity_name']}\n";
}
foreach ($check as $c) echo "  {$c['lesson_code']}: {$c['lesson_name']} — {$c['cnt']} activities\n";

echo "\nDone! Refresh your browser.\n";
