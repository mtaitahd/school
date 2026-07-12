<?php
/**
 * PHASE 10 FIX — Add missing NUM-02 Lessons L05-L08 + 40 Activities
 *
 * NUM-02 originally only had 4 lessons (L01-L04). This adds:
 *   L05: Writing Number 0
 *   L06: Zero in Context
 *   L07: Zero Game
 *   L08: Zero Assessment
 *
 * Visit: https://smartmathconner.co.tz/database/fix_num02_add_lessons.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== FIX: Add NUM-02 L05-L08 + 40 Activities ===\n\n";

/* ----------------------------------------------------------------
   STEP 0: Helper
   ---------------------------------------------------------------- */
function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
    return json_encode(array_merge([
        'engine' => $engine,
        'instruction' => $instruction,
        'objective' => $objective,
        'content' => $content,
        'choices' => $choices,
        'answer' => $answer,
        'feedback' => $feedback,
        'difficulty' => $difficulty,
        'estimated_time' => $time,
        'audio' => ['instruction' => $instruction, 'number_name' => 'zero', 'enabled' => false],
        'visual' => ['theme' => 'numbers', 'background' => 'light', 'show_progress' => true, 'large_numbers' => true, 'large_objects' => true, 'animation' => 'fade']
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ----------------------------------------------------------------
   STEP 1: Add 4 Lessons
   ---------------------------------------------------------------- */
echo "--- STEP 1: Add L05-L08 ---\n";

$topic = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-02'");
if (!$topic) { die("ERROR: NUM-02 not found\n"); }
$topic_id = (int)$topic['topic_id'];

$newLessons = [
    ['NUM-02-L05', 'Writing Number 0',
     'By the end of this lesson, the child can write the number 0 independently with correct circular motion.',
     'Can write 0 without tracing guides, maintaining smooth circular form.',
     15, '["NUM-02-L04"]', 5],
    ['NUM-02-L06', 'Zero in Context',
     'By the end of this lesson, the child can identify real-world examples of zero — empty plates, empty cups, no objects.',
     'Can match empty containers and groups to the number 0.',
     15, '["NUM-02-L05"]', 6],
    ['NUM-02-L07', 'Zero Game',
     'By the end of this lesson, the child can find and identify 0 quickly through game-based activities.',
     'Can find 0 with at least 80% accuracy in a game format.',
     20, '["NUM-02-L06"]', 7],
    ['NUM-02-L08', 'Zero Assessment',
     'By the end of this lesson, the child can demonstrate mastery of number 0 through recognition, writing, and matching.',
     'Scores at least 80% on the zero assessment covering recognition, shape, writing, and context.',
     20, '["NUM-02-L07"]', 8],
];

foreach ($newLessons as [$code, $name, $obj, $crit, $mins, $prereq, $order]) {
    $database->execute(
        "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, prerequisite_lesson_ids, order_index, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
        [$topic_id, $code, $name, $obj, $crit, $mins, $prereq, $order]
    );
    echo "  Lesson $code: $name\n";
}
echo "\n";

/* ----------------------------------------------------------------
   STEP 2: Get lesson IDs
   ---------------------------------------------------------------- */
$L = [];
$rows = $database->fetchAll("SELECT lesson_id, lesson_code FROM lessons WHERE lesson_code LIKE 'NUM-02-%' ORDER BY order_index");
foreach ($rows as $r) { $L[$r['lesson_code']] = (int)$r['lesson_id']; }
echo "Lesson IDs: " . json_encode($L) . "\n\n";

/* ----------------------------------------------------------------
   STEP 3: Build 40 Activities (L05-L08)
   ---------------------------------------------------------------- */
echo "--- STEP 2: Activities (40 total) ---\n";

$acts = [];

/* ================================================================
   L05: Writing Number 0
   Focus: Independent writing, no tracing guides
   ================================================================ */
$c = 'NUM-02-L05';
$acts[] = [$c, 'intro', 0, 'Writing Number 0', 'Time to write 0 on your own!',
    act_json('number_identification', ['min'=>0,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>0,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Colour number 0!', 'Review 0 shape before writing.', 'Colour the number 0 — remember, it is a circle!',
    [0,1,2], 0, 'Colour the circle!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Trace to Warm Up', 'Trace number 0 to warm up your hand!',
    act_json('number_identification', ['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>1,'step_type'=>'warmup'],
    'Trace number 0!', 'Warm up tracing motion.', 'Trace number 0 — smooth circle!',
    [0], 0, 'Good tracing!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Write 0', 'Watch as I write number 0 without help!',
    act_json('number_identification', ['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me write 0!', 'Model independent writing.', 'I can write 0 all by myself. Watch!',
    [0], 0, 'Beautiful zero!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'Write Together', 'Let us write number 0 together!',
    act_json('number_identification', ['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>2,'step_type'=>'we_do'],
    'Write 0 together!', 'Guided independent writing.', 'Write number 0 — one smooth circle!',
    [0], 0, 'Well done!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Write 0 Yourself!', 'Write number 0 without any help!',
    act_json('number_identification', ['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>2,'step_type'=>'you_do'],
    'Write number 0!', 'Independent writing practice.', 'Show me you can write 0 all alone!',
    [0], 0, 'Amazing writing!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Find Number 0', 'Find the number 0!',
    act_json('number_identification', ['min'=>0,'max'=>4,'poolSize'=>4,'target_number'=>0,'difficulty'=>2,'step_type'=>'check'],
    'Find number 0!', 'Verify 0 recognition.', 'Which number is 0?',
    [0,1,2,3], 0, 'Yes! 0!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Writing Game', 'Play a writing game with number 0!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Reinforce through play.', 'Find number 0 in the game!',
    [], '', 'Great game!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Writing Check', 'Show me you can write 0!',
    act_json('number_identification', ['min'=>0,'max'=>4,'poolSize'=>4,'mode'=>'trace','target_number'=>0,'difficulty'=>2,'step_type'=>'assessment'],
    'Write number 0!', 'Assess writing ability.', 'Write number 0 all by yourself!',
    [0], 0, 'Beautiful zero!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You can write number 0!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You wrote number 0!',
    [], '', 'Amazing!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Ready for Context', 'See zero in real life!',
    act_json('mango_counting', ['min'=>0,'max'=>0,'object'=>'star','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the stars!', 'Preview next lesson.', 'There are zero stars!',
    [0], 0, 'Zero stars!', 'easy', 2)];

/* ================================================================
   L06: Zero in Context
   Focus: Real-world zero — empty containers, no objects
   ================================================================ */
$c = 'NUM-02-L06';
$acts[] = [$c, 'intro', 0, 'Empty Means Zero', 'An empty plate means zero food!',
    act_json('match_quantity', ['min'=>0,'max'=>3,'object'=>'apple','target'=>0,'difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Find the empty plate!', 'Connect zero to real life.', 'Which plate has zero apples?',
    [], 0, 'No apples — zero!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Objects', 'Count how many you see!',
    act_json('mango_counting', ['min'=>0,'max'=>3,'object'=>'apple','difficulty'=>1,'step_type'=>'warmup'],
    'Count the apples!', 'Review counting to 3.', 'Count all the apples!',
    [0,1,2,3], 0, 'Zero apples!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Find Empty', 'Watch as I find the empty cup!',
    act_json('match_quantity', ['min'=>0,'max'=>3,'object'=>'cup','target'=>0,'difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me find empty!', 'Model finding zero context.', 'Which cup has zero juice?',
    [], 0, 'Empty cup — zero!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'Find Empty Together', 'Let us find the empty group together!',
    act_json('match_quantity', ['min'=>0,'max'=>3,'object'=>'ball','target'=>0,'difficulty'=>1,'step_type'=>'we_do'],
    'Find the empty group!', 'Guided zero context.', 'Which group has zero balls?',
    [], 0, 'No balls — zero!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Find Zero Groups!', 'Find all the groups with zero objects!',
    act_json('match_quantity', ['min'=>0,'max'=>4,'object'=>'fish','target'=>0,'difficulty'=>2,'step_type'=>'you_do'],
    'Find zero groups!', 'Independent zero context.', 'Which group has zero fish?',
    [], 0, 'No fish — zero!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Zero or Not?', 'Is it zero or not?',
    act_json('number_identification', ['min'=>0,'max'=>4,'poolSize'=>4,'target_number'=>0,'difficulty'=>2,'step_type'=>'check'],
    'Find number 0!', 'Verify zero understanding.', 'Find the number zero!',
    [0,1,2,3], 0, 'Yes! Zero!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Zero Game', 'Play the zero game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Reinforce zero in context.', 'Find zero in the game!',
    [], '', 'Found it!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Zero Context Check', 'Show what you know about zero!',
    act_json('match_quantity', ['min'=>0,'max'=>4,'object'=>'flower','target'=>0,'difficulty'=>2,'step_type'=>'assessment'],
    'Find the empty group!', 'Assess zero in context.', 'Which group has zero flowers?',
    [], 0, 'No flowers — zero!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Well Done!', 'You understand zero!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You know what zero means!',
    [], '', 'Fantastic!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Game Time!', 'Get ready for the zero game!',
    act_json('mango_counting', ['min'=>0,'max'=>0,'object'=>'star','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count stars!', 'Preview game lesson.', 'There are zero stars!',
    [0], 0, 'Zero!', 'easy', 2)];

/* ================================================================
   L07: Zero Game
   Focus: Fast zero recognition through games
   ================================================================ */
$c = 'NUM-02-L07';
$acts[] = [$c, 'intro', 0, 'Zero Challenge', 'Time for a zero challenge!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Get ready!', 'Introduce game lesson.', 'Let us play a zero game!',
    [], '', 'Ready!', 'medium', 2)];

$acts[] = [$c, 'warmup', 1, 'Quick Count', 'Count quickly!',
    act_json('mango_counting', ['min'=>0,'max'=>3,'object'=>'star','difficulty'=>1,'step_type'=>'warmup'],
    'Count the stars!', 'Quick counting warmup.', 'Count the stars fast!',
    [0,1,2,3], 0, 'Zero stars!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Play', 'Watch me find zero fast!',
    act_json('number_identification', ['min'=>0,'max'=>4,'poolSize'=>4,'target_number'=>0,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me find zero!', 'Model fast zero finding.', 'I find zero really fast. Watch!',
    [0,1,2,3], 0, 'Found it!', 'medium', 3)];

$acts[] = [$c, 'we_do', 3, 'Find Zero Fast!', 'Find zero together, fast!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>4,'target_number'=>0,'difficulty'=>2,'step_type'=>'we_do'],
    'Find zero!', 'Guided fast finding.', 'Quick! Find zero!',
    [0,1,2,3,4], 0, 'Found zero!', 'medium', 3)];

$acts[] = [$c, 'you_do', 4, 'Speed Round!', 'Find zero as fast as you can!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>2,'step_type'=>'you_do'],
    'Find zero fast!', 'Independent speed finding.', 'Speed round! Find zero!',
    [0,1,2,3,4], 0, 'Speedy zero!', 'medium', 3)];

$acts[] = [$c, 'check', 5, 'Quick Zero', 'Find zero quickly!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>2,'step_type'=>'check'],
    'Find zero!', 'Quick check.', 'Which number is zero?',
    [0,1,2,3,4], 0, 'Zero!', 'medium', 2)];

$acts[] = [$c, 'game', 6, 'Zero Game', 'Play the zero game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Game reinforcement.', 'Find all the zeros!',
    [], '', 'Zero champion!', 'medium', 5)];

$acts[] = [$c, 'assessment', 7, 'Zero Speed Test', 'Show how fast you can find zero!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>3,'step_type'=>'assessment'],
    'Speed test!', 'Assess speed finding.', 'Find zero really fast!',
    [0,1,2,3,4], 0, 'Lightning fast!', 'medium', 3)];

$acts[] = [$c, 'reward', 8, 'Champion!', 'You are a zero champion!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'Zero champion!',
    [], '', 'Champion!', 'medium', 2)];

$acts[] = [$c, 'next_steps', 9, 'Assessment Time', 'Get ready for the final test!',
    act_json('mango_counting', ['min'=>0,'max'=>0,'object'=>'star','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count!', 'Preview assessment.', 'Zero stars for the test!',
    [0], 0, 'Zero!', 'easy', 2)];

/* ================================================================
   L08: Zero Assessment
   Focus: Comprehensive assessment
   ================================================================ */
$c = 'NUM-02-L08';
$acts[] = [$c, 'intro', 0, 'Assessment Time', 'Show what you know about zero!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Find zero!', 'Prepare for assessment.', 'Find number zero to start!',
    [0,1,2,3,4], 0, 'Let us begin!', 'medium', 2)];

$acts[] = [$c, 'warmup', 1, 'Count to Warm Up', 'Count all the objects!',
    act_json('mango_counting', ['min'=>0,'max'=>3,'object'=>'star','difficulty'=>1,'step_type'=>'warmup'],
    'Count stars!', 'Warm up counting.', 'Count every star!',
    [0,1,2,3], 0, 'Counted!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Demo Question', 'Watch me answer a zero question!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>4,'target_number'=>0,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me!', 'Model assessment question.', 'Which number is zero? Watch me find it!',
    [0,1,2,3], 0, 'Zero!', 'medium', 3)];

$acts[] = [$c, 'we_do', 3, 'Practice Round', 'Let us practice together!',
    act_json('match_quantity', ['min'=>0,'max'=>4,'object'=>'flower','target'=>0,'difficulty'=>2,'step_type'=>'we_do'],
    'Find zero flowers!', 'Guided assessment practice.', 'Which group has zero flowers?',
    [], 0, 'No flowers — zero!', 'medium', 3)];

$acts[] = [$c, 'you_do', 4, 'Your Assessment!', 'Show what you know!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>2,'step_type'=>'you_do'],
    'Find zero!', 'Independent assessment.', 'Find number zero!',
    [0,1,2,3,4], 0, 'Zero!', 'medium', 3)];

$acts[] = [$c, 'check', 5, 'Final Check', 'One more check!',
    act_json('number_identification', ['min'=>0,'max'=>5,'poolSize'=>5,'target_number'=>0,'difficulty'=>2,'step_type'=>'check'],
    'Find zero!', 'Final verification.', 'Which number is zero?',
    [0,1,2,3,4], 0, 'Zero!', 'medium', 2)];

$acts[] = [$c, 'game', 6, 'Assessment Game', 'Play the assessment game!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'game'],
    'Play the game!', 'Game-based assessment.', 'Find zeros in the game!',
    [], '', 'Great work!', 'medium', 5)];

$acts[] = [$c, 'assessment', 7, 'Final Assessment', 'Show everything about zero!',
    act_json('match_quantity', ['min'=>0,'max'=>5,'object'=>'apple','target'=>0,'difficulty'=>3,'step_type'=>'assessment'],
    'Find zero!', 'Comprehensive assessment.', 'Which group has zero apples?',
    [], 0, 'No apples — zero!', 'medium', 3)];

$acts[] = [$c, 'reward', 8, 'Congratulations!', 'You completed Number Zero!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Congratulations!', 'Celebrate completion.', 'You mastered number zero!',
    [], '', 'Amazing!', 'medium', 2)];

$acts[] = [$c, 'next_steps', 9, 'What Is Next?', 'Count for the next chapter!',
    act_json('mango_counting', ['min'=>1,'max'=>10,'object'=>'star','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the stars!', 'Prepare for next topic.', 'Count all the stars!',
    [1,2,3,4,5,6,7,8,9,10], 10, 'Ten stars! Ready for more!', 'easy', 2)];

/* ----------------------------------------------------------------
   STEP 4: Insert into database
   ---------------------------------------------------------------- */
echo "--- STEP 3: Database Insert ---\n";

$inserted = 0;
$errors = 0;

foreach ($acts as [$lesson_code, $step_type, $step_order, $name, $desc, $json]) {
    if (!isset($L[$lesson_code])) {
        echo "ERROR: Lesson $lesson_code not found!\n";
        $errors++;
        continue;
    }
    $lid = $L[$lesson_code];

    $data = json_decode($json, true);
    if (!$data || !isset($data['engine'])) {
        echo "ERROR: Invalid JSON for $name\n";
        $errors++;
        continue;
    }

    $diff = $data['difficulty'] <= 1 ? 'easy' : ($data['difficulty'] <= 2 ? 'medium' : 'hard');
    $instruction_text = $data['instruction'] ?? '';

    $existing = $database->fetchOne(
        "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? AND step_order = ?",
        [$lid, $step_type, $step_order]
    );

    if ($existing) {
        $database->execute(
            "UPDATE activities SET activity_name=?, activity_description=?, difficulty_level=?, activity_data=?, audio_instruction=? WHERE activity_id=?",
            [$name, $desc, $diff, $json, $instruction_text, $existing['activity_id']]
        );
        echo "  UPDATE: $lesson_code $step_type $step_order — $name\n";
    } else {
        $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction)
             VALUES ((SELECT module_id FROM topics WHERE topic_code = 'NUM-02'), ?, ?, ?, ?, ?, 'numbers', ?, ?, ?)",
            [$lid, $step_type, $step_order, $name, $desc, $diff, $json, $instruction_text]
        );
        $inserted++;
        echo "  INSERT: $lesson_code $step_type $step_order — $name\n";
    }
}

echo "\nResults: $inserted inserted, $errors errors\n\n";

/* ----------------------------------------------------------------
   STEP 5: Verification
   ---------------------------------------------------------------- */
echo "=== VERIFICATION ===\n";

$topicRow = $database->fetchOne("SELECT * FROM topics WHERE topic_code = 'NUM-02'");
echo $topicRow ? "✓ Topic NUM-02 exists\n" : "✗ Topic NUM-02 NOT found\n";

$lessonsCount = $database->fetchOne("SELECT COUNT(*) as cnt FROM lessons WHERE lesson_code LIKE 'NUM-02-%'");
echo ($lessonsCount && $lessonsCount['cnt'] === 8) ? "✓ 8 lessons now exist\n" : "✗ Expected 8, found " . ($lessonsCount['cnt'] ?? 0) . "\n";

$perLesson = $database->fetchAll(
    "SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
     FROM activities a JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-02-%'
     GROUP BY l.lesson_code ORDER BY l.order_index"
);

$total = 0;
foreach ($perLesson as $pl) {
    $mark = $pl['cnt'] == 10 ? '✓' : ('✗ (has ' . $pl['cnt'] . ')');
    echo "  {$pl['lesson_code']}: {$pl['lesson_name']} — {$pl['cnt']} activities $mark\n";
    $total += (int)$pl['cnt'];
}

echo "\nTOTAL: $total / 80 activities\n";
echo ($total === 80) ? "\n✓ ALL 80 ACTIVITIES NOW EXIST\n" : "\n✗ Expected 80, got $total\n";

/* Engine check */
$engines = $database->fetchAll(
    "SELECT JSON_EXTRACT(activity_data, '$.engine') as engine, COUNT(*) as cnt
     FROM activities a JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-02-%'
     GROUP BY engine"
);
echo "\nEngine usage:\n";
foreach ($engines as $e) { echo "  {$e['engine']}: {$e['cnt']} activities\n"; }

echo "\n=== FIX COMPLETE ===\n";
echo "</pre>";
