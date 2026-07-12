<?php
/**
 * PHASE 9 — Create NUM-06: Subtraction
 *
 * Creates: topic, 8 lessons, 80 activities (10 per lesson)
 * Uses: visual_subtraction engine (primary), mango_counting, number_identification,
 *       match_quantity, math_game
 *
 * Visit: https://smartmathconner.co.tz/database/migrate_num06_subtraction.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== PHASE 9: Create NUM-06 — Subtraction ===\n\n";

/* ----------------------------------------------------------------
   STEP 0: Helper function — build activity JSON
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
        'audio' => ['instruction' => $instruction, 'number_name' => '', 'enabled' => false],
        'visual' => ['theme' => 'subtraction', 'background' => 'light', 'show_progress' => true, 'large_numbers' => true, 'large_objects' => true, 'animation' => 'fade']
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ----------------------------------------------------------------
   STEP 1: Create Module + Topic
   ---------------------------------------------------------------- */
echo "--- STEP 1: Module & Topic ---\n";

/* Create module 58: Subtraction */
$database->execute(
    "INSERT IGNORE INTO modules (module_id, module_name, module_description, module_icon, module_color, audio_prompt, order_index)
     VALUES (58, 'Subtraction', 'Learn to subtract numbers using pictures and objects', 'fa-minus', '#e74c3c', 'Touch here for Subtraction!', 16)"
);
echo "Module 58 (Subtraction) ensured.\n";

/* Get strand_id for NUM */
$strandRow = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strandRow) {
    die("ERROR: Strand 'NUM' not found. Run migrations_v9 first.\n");
}
$strand_id = (int)$strandRow['strand_id'];

/* Create topic NUM-06 */
$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
     VALUES (?, 58, 'Subtraction', 'NUM-06', '4-5', 'Learn to subtract by taking away objects and counting what remains using pictures', 8, 6)",
    [$strand_id]
);
echo "Topic NUM-06 (Subtraction) ensured.\n\n";

/* ----------------------------------------------------------------
   STEP 2: Create 8 Lessons
   ---------------------------------------------------------------- */
echo "--- STEP 2: Lessons ---\n";

$lessons = [
    ['NUM-06-L01', 'Introduction to Subtraction',
     'By the end of this lesson, the child understands that subtraction means taking away objects from a group.',
     'Child can explain that subtracting means removing objects, and can demonstrate by tapping objects to take them away.',
     20, null, 1],
    ['NUM-06-L02', 'Taking Away Objects',
     'By the end of this lesson, the child can take away objects one by one and count how many are removed.',
     'Child can tap objects to remove them, count the removed objects, and understand the concept of taking away.',
     20, '["NUM-06-L01"]', 2],
    ['NUM-06-L03', 'Counting What Remains',
     'By the end of this lesson, the child can count the remaining objects after some are taken away.',
     'Child can take away objects and then count what is left to find the correct answer.',
     20, '["NUM-06-L02"]', 3],
    ['NUM-06-L04', 'Picture Subtraction',
     'By the end of this lesson, the child can subtract using pictures of familiar objects like leaves, oranges, and chairs.',
     'Child can use pictures to subtract groups and find how many are left using objects from the workbook.',
     20, '["NUM-06-L03"]', 4],
    ['NUM-06-L05', 'Reading Simple Subtraction',
     'By the end of this lesson, the child can read a simple subtraction sentence like 5 - 2 = 3 and understand what it means.',
     'Child can read the minus sign as "take away" and the equals sign as "leaves" or "makes".',
     20, '["NUM-06-L04"]', 5],
    ['NUM-06-L06', 'Guided Practice',
     'By the end of this lesson, the child can subtract confidently with different objects and numbers.',
     'Child can independently take away objects, count what remains, and select the correct answer.',
     20, '["NUM-06-L05"]', 6],
    ['NUM-06-L07', 'Subtraction Game',
     'By the end of this lesson, the child can solve subtraction problems quickly through a fun game.',
     'Child can solve subtraction problems with at least 80% accuracy in a game format.',
     25, '["NUM-06-L06"]', 7],
    ['NUM-06-L08', 'Subtraction Assessment',
     'By the end of this lesson, the child can demonstrate mastery of subtraction through picture-based problems and simple number sentences.',
     'Child scores at least 80% on the subtraction assessment covering taking away, counting remainders, and finding answers.',
     25, '["NUM-06-L07"]', 8],
];

foreach ($lessons as [$code, $name, $objective, $criteria, $minutes, $prereq, $order]) {
    $database->execute(
        "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, prerequisite_lesson_ids, order_index)
         VALUES ((SELECT topic_id FROM topics WHERE topic_code = 'NUM-06'), ?, ?, ?, ?, ?, ?, ?)",
        [$code, $name, $objective, $criteria, $minutes, $prereq, $order]
    );
    echo "  Lesson $code: $name\n";
}
echo "\n";

/* ----------------------------------------------------------------
   STEP 3: Build all 80 activities
   ---------------------------------------------------------------- */
echo "--- STEP 3: Activities (80 total) ---\n";

$acts = [];

/* ================================================================
   L01: Introduction to Subtraction
   Focus: "Taking away" concept
   Objects: apple | Small numbers: start 3-4, remove 1
   ================================================================ */
$c = 'NUM-06-L01';
$acts[] = [$c, 'intro', 0, 'Taking Away Apples', 'Subtraction means taking objects away!',
    act_json('visual_subtraction', ['object'=>'apple','start'=>3,'remove'=>1,'difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Tap one apple to take it away!', 'Understand subtraction concept.', 'We have 3 apples. Tap 1 to take it away!',
    [2], 2, '2 apples left! Taking away means subtraction!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Apples', 'Count how many apples you see!',
    act_json('mango_counting', ['min'=>1,'max'=>3,'object'=>'apple','difficulty'=>1,'step_type'=>'warmup'],
    'Count the apples!', 'Review counting 1 to 3.', 'Count all the apples carefully!',
    [1,2,3], 3, 'Three apples!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Take Away', 'Watch as I take away 1 apple from 3!',
    act_json('visual_subtraction', ['object'=>'apple','start'=>3,'remove'=>1,'difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me tap to take away!', 'Model subtraction by taking away.', '3 apples minus 1. Watch me take one away!',
    [2], 2, '3 minus 1 equals 2!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'We Take Away Together', 'Let us take away 1 apple together!',
    act_json('visual_subtraction', ['object'=>'apple','start'=>4,'remove'=>1,'difficulty'=>1,'step_type'=>'we_do'],
    'Tap together to take away!', 'Practice taking away with guidance.', '4 apples. Tap 1 to take it away!',
    [3], 3, '4 minus 1 equals 3!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'You Take Away!', 'Now you try! Take away 1 apple.',
    act_json('visual_subtraction', ['object'=>'apple','start'=>3,'remove'=>1,'difficulty'=>1,'step_type'=>'you_do'],
    'Tap to take away!', 'Practice subtraction independently.', '3 apples. Take away 1!',
    [2], 2, '2 apples left!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Which Number Is Left?', 'Choose the number of apples left!',
    act_json('number_identification', ['min'=>1,'max'=>5,'poolSize'=>3,'target_number'=>2,'difficulty'=>1,'step_type'=>'check'],
    'Find the number of apples left!', 'Identify the remaining count.', 'How many apples are left? Find the number!',
    [1,2,3], 2, '2 apples are left!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Subtraction Game', 'Play a fun subtraction game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Play the subtraction game!', 'Reinforce subtraction through play.', 'Tap the correct answers as fast as you can!',
    [], '', 'Great job playing!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Show What You Know', 'How many are left after taking away?',
    act_json('visual_subtraction', ['object'=>'apple','start'=>4,'remove'=>2,'difficulty'=>1,'step_type'=>'assessment'],
    'Take away and count what is left!', 'Assess subtraction understanding.', '4 apples. Take away 2. How many are left?',
    [2], 2, '4 minus 2 equals 2!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Well Done!', 'You completed the lesson!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate learning.', 'You are learning to subtract!',
    [], '', 'Amazing work!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Getting Ready for More', 'Count more objects for our next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>4,'object'=>'apple','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the apples!', 'Prepare for next lesson.', 'Count all the apples to get ready!',
    [1,2,3,4], 4, 'Four apples!', 'easy', 2)];

/* ================================================================
   L02: Taking Away Objects
   Focus: Remove 1-2 objects from small groups
   Objects: chicken | Numbers: start 3-5, remove 1-2
   ================================================================ */
$c = 'NUM-06-L02';
$acts[] = [$c, 'intro', 0, 'Chickens Going Away', 'Some chickens are going back to the coop!',
    act_json('visual_subtraction', ['object'=>'chicken','start'=>4,'remove'=>1,'difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Tap chickens to send them to the coop!', 'Extend subtraction to new objects.', '4 chickens! Tap 1 to send it back!',
    [3], 3, '3 chickens left!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Chickens', 'How many chickens can you count?',
    act_json('mango_counting', ['min'=>1,'max'=>4,'object'=>'chicken','difficulty'=>1,'step_type'=>'warmup'],
    'Count the chickens!', 'Review counting to 4.', 'Count all the chickens!',
    [1,2,3,4], 4, 'Four chickens!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Remove Chickens', 'Watch as I take away 2 chickens!',
    act_json('visual_subtraction', ['object'=>'chicken','start'=>4,'remove'=>2,'difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me tap to remove!', 'Model removing 2 objects.', '4 chickens. I tap 2 to remove!',
    [2], 2, '4 minus 2 equals 2!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'We Remove Together', 'Let us take away 1 chicken together!',
    act_json('visual_subtraction', ['object'=>'chicken','start'=>5,'remove'=>1,'difficulty'=>1,'step_type'=>'we_do'],
    'Tap together to remove!', 'Guided practice removing 1.', '5 chickens. Tap 1 to remove!',
    [4], 4, '5 minus 1 equals 4!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Your Turn to Remove!', 'Take away 2 chickens!',
    act_json('visual_subtraction', ['object'=>'chicken','start'=>4,'remove'=>2,'difficulty'=>1,'step_type'=>'you_do'],
    'Tap to remove chickens!', 'Independent removal practice.', '4 chickens. Remove 2!',
    [2], 2, '2 chickens left!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'How Many Left?', 'Find the number of chickens remaining!',
    act_json('number_identification', ['min'=>1,'max'=>5,'poolSize'=>3,'target_number'=>3,'difficulty'=>1,'step_type'=>'check'],
    'Find the remaining number!', 'Identify remaining count.', 'How many chickens are left?',
    [2,3,4], 3, '3 chickens left!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Chicken Subtraction Game', 'Play a chicken subtraction game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Play the game!', 'Reinforce subtraction through play.', 'Tap the correct answers!',
    [], '', 'Great chicken counting!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Chicken Check', 'Take away chickens and count what remains!',
    act_json('visual_subtraction', ['object'=>'chicken','start'=>5,'remove'=>2,'difficulty'=>1,'step_type'=>'assessment'],
    'Remove and count!', 'Assess subtraction with chickens.', '5 chickens. Remove 2. How many left?',
    [3], 3, '5 minus 2 equals 3!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Super Star!', 'You did amazing!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate completion.', 'You subtract like a star!',
    [], '', 'Fantastic!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count More!', 'Count objects for our next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'leaf','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the leaves!', 'Prepare for next lesson.', 'Count all the leaves!',
    [1,2,3,4,5], 5, 'Five leaves!', 'easy', 2)];

/* ================================================================
   L03: Counting What Remains
   Focus: Count remaining objects after removal
   Objects: leaf | Numbers: start 4-6, remove 1-3
   ================================================================ */
$c = 'NUM-06-L03';
$acts[] = [$c, 'intro', 0, 'Leaves Falling', 'Some leaves fall from the tree!',
    act_json('visual_subtraction', ['object'=>'leaf','start'=>5,'remove'=>1,'difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Tap leaves to let them fall!', 'Focus on counting remainders.', '5 leaves on the tree. Tap 1 to let it fall!',
    [4], 4, '4 leaves still on the tree!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Leaves', 'Count all the leaves!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'leaf','difficulty'=>1,'step_type'=>'warmup'],
    'Count the leaves!', 'Review counting to 5.', 'Count every leaf carefully!',
    [1,2,3,4,5], 5, 'Five leaves!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Count Remainder', 'Watch as I remove 2 leaves and count what is left!',
    act_json('visual_subtraction', ['object'=>'leaf','start'=>5,'remove'=>2,'difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me remove and count!', 'Model counting remainders.', '5 leaves. I remove 2. Now I count the rest!',
    [3], 3, '3 leaves remaining!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'Count Together', 'Remove 2 leaves and count what remains!',
    act_json('visual_subtraction', ['object'=>'leaf','start'=>6,'remove'=>2,'difficulty'=>1,'step_type'=>'we_do'],
    'Tap to remove, then count!', 'Guided counting of remainders.', '6 leaves. Remove 2. Count what is left!',
    [4], 4, '6 minus 2 equals 4!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Count What Is Left!', 'Remove 3 leaves and count the rest!',
    act_json('visual_subtraction', ['object'=>'leaf','start'=>5,'remove'=>3,'difficulty'=>1,'step_type'=>'you_do'],
    'Remove and count!', 'Independent remainder counting.', '5 leaves. Remove 3. How many left?',
    [2], 2, '5 minus 3 equals 2!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Match the Remaining', 'Match the number of leaves remaining!',
    act_json('match_quantity', ['min'=>1,'max'=>6,'object'=>'leaf','target'=>3,'difficulty'=>1,'step_type'=>'check'],
    'Find the group with 3 leaves!', 'Match remaining quantity.', 'How many leaves are left? Find the matching group!',
    [2,3,4], 3, '3 leaves remaining!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Leaf Game', 'Play a leaf subtraction game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Play the game!', 'Reinforce through play.', 'Tap correct answers quickly!',
    [], '', 'Great leaf counting!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Leaf Check', 'Take away leaves and count the remainder!',
    act_json('visual_subtraction', ['object'=>'leaf','start'=>6,'remove'=>3,'difficulty'=>1,'step_type'=>'assessment'],
    'Remove and count what remains!', 'Assess remainder counting.', '6 leaves. Remove 3. Count what is left!',
    [3], 3, '6 minus 3 equals 3!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Well Done!', 'You completed the leaf lesson!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You are great at counting remainders!',
    [], '', 'Amazing!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count More Objects', 'Count objects for the next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'orange','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the oranges!', 'Prepare for next lesson.', 'Count all the oranges!',
    [1,2,3,4,5,6], 6, 'Six oranges!', 'easy', 2)];

/* ================================================================
   L04: Picture Subtraction
   Focus: Subtract using workbook pictures
   Objects: orange | Numbers: start 5-7, remove 2-3
   ================================================================ */
$c = 'NUM-06-L04';
$acts[] = [$c, 'intro', 0, 'Oranges on the Table', 'Some oranges are being eaten!',
    act_json('visual_subtraction', ['object'=>'orange','start'=>5,'remove'=>2,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Tap oranges that are eaten!', 'Picture subtraction with oranges.', '5 oranges on the table. Tap 2 that are eaten!',
    [3], 3, '3 oranges left on the table!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Oranges', 'Count all the oranges!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'orange','difficulty'=>1,'step_type'=>'warmup'],
    'Count the oranges!', 'Review counting to 5.', 'Count every orange!',
    [1,2,3,4,5], 5, 'Five oranges!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Subtract Oranges', 'Watch as I take away 2 oranges!',
    act_json('visual_subtraction', ['object'=>'orange','start'=>6,'remove'=>2,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me tap to subtract!', 'Model picture subtraction.', '6 oranges. I take away 2!',
    [4], 4, '6 minus 2 equals 4!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'Subtract Together', 'Take away 3 oranges together!',
    act_json('visual_subtraction', ['object'=>'orange','start'=>7,'remove'=>3,'difficulty'=>2,'step_type'=>'we_do'],
    'Tap together to subtract!', 'Guided picture subtraction.', '7 oranges. Tap 3 to take away!',
    [4], 4, '7 minus 3 equals 4!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Subtract by Yourself!', 'Take away 2 oranges!',
    act_json('visual_subtraction', ['object'=>'orange','start'=>5,'remove'=>2,'difficulty'=>2,'step_type'=>'you_do'],
    'Tap to subtract!', 'Independent picture subtraction.', '5 oranges. Take away 2!',
    [3], 3, '3 oranges left!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Which Number?', 'Find the number of oranges remaining!',
    act_json('number_identification', ['min'=>1,'max'=>7,'poolSize'=>4,'target_number'=>3,'difficulty'=>1,'step_type'=>'check'],
    'Find the remaining number!', 'Identify remainder.', 'How many oranges are left? Find the number!',
    [2,3,4,5], 3, '3 oranges left!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Orange Game', 'Play an orange subtraction game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Reinforce through play.', 'Tap the right answers!',
    [], '', 'Great orange work!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Orange Check', 'Take away oranges and find the answer!',
    act_json('visual_subtraction', ['object'=>'orange','start'=>7,'remove'=>3,'difficulty'=>2,'step_type'=>'assessment'],
    'Subtract and count!', 'Assess picture subtraction.', '7 oranges. Remove 3. How many left?',
    [4], 4, '7 minus 3 equals 4!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Awesome!', 'You did it!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You are a subtraction star!',
    [], '', 'Awesome work!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count for Next', 'Count objects for the next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'chair','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the chairs!', 'Prepare for next lesson.', 'Count all the chairs!',
    [1,2,3,4,5,6], 6, 'Six chairs!', 'easy', 2)];

/* ================================================================
   L05: Reading Simple Subtraction
   Focus: Understand subtraction sentences (5 - 2 = 3)
   Objects: chair | Numbers: start 5-7, remove 2-3
   ================================================================ */
$c = 'NUM-06-L05';
$acts[] = [$c, 'intro', 0, 'Chairs in the Room', 'Some chairs are taken away!',
    act_json('visual_subtraction', ['object'=>'chair','start'=>6,'remove'=>2,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Tap chairs to take away!', 'Read subtraction sentences.', '6 chairs. Tap 2 to take away!',
    [4], 4, '6 - 2 = 4. Four chairs left!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Chairs', 'Count all the chairs!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'chair','difficulty'=>1,'step_type'=>'warmup'],
    'Count the chairs!', 'Review counting to 5.', 'Count every chair!',
    [1,2,3,4,5], 5, 'Five chairs!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Reading Subtraction', 'Watch me read 5 - 2 = 3!',
    act_json('visual_subtraction', ['object'=>'chair','start'=>5,'remove'=>2,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch and read the sentence!', 'Model reading subtraction sentences.', '5 chairs. Take away 2. That is 5 minus 2 equals 3!',
    [3], 3, '5 - 2 = 3!', 'easy', 3)];

$acts[] = [$c, 'we_do', 3, 'Read Together', 'Let us read 6 - 3 = 3 together!',
    act_json('visual_subtraction', ['object'=>'chair','start'=>6,'remove'=>3,'difficulty'=>2,'step_type'=>'we_do'],
    'Tap and read the sentence!', 'Guided reading of subtraction.', '6 chairs. Remove 3. Read with me!',
    [3], 3, '6 - 3 = 3!', 'easy', 3)];

$acts[] = [$c, 'you_do', 4, 'Read and Solve!', 'Read 7 - 2 and find the answer!',
    act_json('visual_subtraction', ['object'=>'chair','start'=>7,'remove'=>2,'difficulty'=>2,'step_type'=>'you_do'],
    'Read the sentence and solve!', 'Independent subtraction reading.', '7 chairs. Remove 2. What is the answer?',
    [5], 5, '7 - 2 = 5!', 'easy', 3)];

$acts[] = [$c, 'check', 5, 'Find the Answer', 'Choose the answer to 6 - 3!',
    act_json('number_identification', ['min'=>1,'max'=>7,'poolSize'=>4,'target_number'=>3,'difficulty'=>2,'step_type'=>'check'],
    'Find the answer!', 'Verify subtraction knowledge.', 'What is 6 minus 3? Find the number!',
    [2,3,4,5], 3, '3 is correct!', 'easy', 2)];

$acts[] = [$c, 'game', 6, 'Chair Game', 'Play a chair subtraction game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Reinforce through play.', 'Tap the right answers!',
    [], '', 'Great subtraction!', 'easy', 5)];

$acts[] = [$c, 'assessment', 7, 'Chair Check', 'Solve subtraction with chairs!',
    act_json('visual_subtraction', ['object'=>'chair','start'=>7,'remove'=>3,'difficulty'=>2,'step_type'=>'assessment'],
    'Solve the subtraction!', 'Assess subtraction reading.', '7 chairs. Remove 3. How many left?',
    [4], 4, '7 - 3 = 4!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Well Done!', 'You completed the lesson!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You can read subtraction sentences!',
    [], '', 'Fantastic!', 'easy', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count for Next', 'Count objects for our next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>7,'object'=>'log','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the logs!', 'Prepare for next lesson.', 'Count all the logs!',
    [1,2,3,4,5,6,7], 7, 'Seven logs!', 'easy', 2)];

/* ================================================================
   L06: Guided Practice
   Focus: Subtract with various objects
   Objects: log | Numbers: start 6-8, remove 2-4
   ================================================================ */
$c = 'NUM-06-L06';
$acts[] = [$c, 'intro', 0, 'Moving Logs', 'Some logs are being moved away!',
    act_json('visual_subtraction', ['object'=>'log','start'=>7,'remove'=>2,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Tap logs to move them!', 'Practice with logs.', '7 logs. Tap 2 to move them!',
    [5], 5, '5 logs left!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Logs', 'Count all the logs!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'log','difficulty'=>1,'step_type'=>'warmup'],
    'Count the logs!', 'Review counting to 6.', 'Count every log!',
    [1,2,3,4,5,6], 6, 'Six logs!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Move Logs', 'Watch as I move 3 logs away!',
    act_json('visual_subtraction', ['object'=>'log','start'=>8,'remove'=>3,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me subtract!', 'Model subtraction with logs.', '8 logs. I move 3 away!',
    [5], 5, '8 minus 3 equals 5!', 'medium', 3)];

$acts[] = [$c, 'we_do', 3, 'Move Logs Together', 'Move 2 logs together!',
    act_json('visual_subtraction', ['object'=>'log','start'=>7,'remove'=>2,'difficulty'=>2,'step_type'=>'we_do'],
    'Tap together to move!', 'Guided subtraction with logs.', '7 logs. Move 2 away!',
    [5], 5, '7 minus 2 equals 5!', 'medium', 3)];

$acts[] = [$c, 'you_do', 4, 'Move Logs Yourself!', 'Move 4 logs away!',
    act_json('visual_subtraction', ['object'=>'log','start'=>8,'remove'=>4,'difficulty'=>2,'step_type'=>'you_do'],
    'Tap to move logs!', 'Independent subtraction with logs.', '8 logs. Move 4 away!',
    [4], 4, '8 minus 4 equals 4!', 'medium', 3)];

$acts[] = [$c, 'check', 5, 'Match the Remainder', 'Match the number of logs remaining!',
    act_json('match_quantity', ['min'=>1,'max'=>8,'object'=>'log','target'=>4,'difficulty'=>2,'step_type'=>'check'],
    'Find the group with 4 logs!', 'Match remaining quantity.', 'How many logs are left? Find the matching group!',
    [3,4,5], 4, '4 logs remaining!', 'medium', 2)];

$acts[] = [$c, 'game', 6, 'Log Game', 'Play a log subtraction game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Reinforce through play.', 'Tap the right answers!',
    [], '', 'Great log work!', 'medium', 5)];

$acts[] = [$c, 'assessment', 7, 'Log Check', 'Subtract logs and find the answer!',
    act_json('visual_subtraction', ['object'=>'log','start'=>8,'remove'=>4,'difficulty'=>2,'step_type'=>'assessment'],
    'Subtract and count!', 'Assess subtraction with logs.', '8 logs. Remove 4. How many left?',
    [4], 4, '8 minus 4 equals 4!', 'medium', 3)];

$acts[] = [$c, 'reward', 8, 'Super Work!', 'You did amazing with logs!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate.', 'You are great at subtracting logs!',
    [], '', 'Fantastic!', 'medium', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count More!', 'Count objects for the next lesson!',
    act_json('mango_counting', ['min'=>1,'max'=>7,'object'=>'eggplant','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the eggplants!', 'Prepare for next lesson.', 'Count all the eggplants!',
    [1,2,3,4,5,6,7], 7, 'Seven eggplants!', 'easy', 2)];

/* ================================================================
   L07: Subtraction Game
   Focus: Quick subtraction through game
   Objects: eggplant | Numbers: start 6-9, remove 2-5
   ================================================================ */
$c = 'NUM-06-L07';
$acts[] = [$c, 'intro', 0, 'Eggplant Challenge', 'Time for an eggplant subtraction challenge!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Get ready for the challenge!', 'Introduce game lesson.', 'Let us play a subtraction game with eggplants!',
    [], '', 'Ready to play!', 'medium', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Eggplants', 'Count all the eggplants!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'eggplant','difficulty'=>1,'step_type'=>'warmup'],
    'Count the eggplants!', 'Review counting.', 'Count every eggplant!',
    [1,2,3,4,5,6], 6, 'Six eggplants!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Play', 'Watch as I subtract eggplants!',
    act_json('visual_subtraction', ['object'=>'eggplant','start'=>8,'remove'=>3,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me subtract fast!', 'Model quick subtraction.', '8 eggplants. I take away 3 quickly!',
    [5], 5, '8 minus 3 equals 5!', 'medium', 3)];

$acts[] = [$c, 'we_do', 3, 'Quick Subtract!', 'Subtract 4 eggplants together!',
    act_json('visual_subtraction', ['object'=>'eggplant','start'=>9,'remove'=>4,'difficulty'=>2,'step_type'=>'we_do'],
    'Tap fast to subtract!', 'Guided quick subtraction.', '9 eggplants. Tap 4 to take away!',
    [5], 5, '9 minus 4 equals 5!', 'medium', 3)];

$acts[] = [$c, 'you_do', 4, 'Speed Round!', 'Take away 3 eggplants fast!',
    act_json('visual_subtraction', ['object'=>'eggplant','start'=>7,'remove'=>3,'difficulty'=>2,'step_type'=>'you_do'],
    'Tap fast!', 'Independent quick subtraction.', '7 eggplants. Remove 3 quickly!',
    [4], 4, '7 minus 3 equals 4!', 'medium', 3)];

$acts[] = [$c, 'check', 5, 'Quick Answer!', 'Find the answer quickly!',
    act_json('number_identification', ['min'=>1,'max'=>9,'poolSize'=>5,'target_number'=>5,'difficulty'=>2,'step_type'=>'check'],
    'Find the answer!', 'Quick number identification.', 'What is 9 minus 4? Find the number!',
    [3,4,5,6,7], 5, '5 is correct!', 'medium', 2)];

$acts[] = [$c, 'game', 6, 'Eggplant Subtraction Game', 'Play the eggplant subtraction game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Play the game!', 'Game-based subtraction.', 'Tap the right answers as fast as you can!',
    [], '', 'Eggcellent work!', 'medium', 5)];

$acts[] = [$c, 'assessment', 7, 'Eggplant Test', 'Show your subtraction skills!',
    act_json('visual_subtraction', ['object'=>'eggplant','start'=>9,'remove'=>5,'difficulty'=>2,'step_type'=>'assessment'],
    'Subtract and count!', 'Assess quick subtraction.', '9 eggplants. Remove 5. How many left?',
    [4], 4, '9 minus 5 equals 4!', 'medium', 3)];

$acts[] = [$c, 'reward', 8, 'Champion!', 'You are a subtraction champion!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Great work!', 'Celebrate mastery.', 'You are a subtraction champion!',
    [], '', 'Champion effort!', 'medium', 2)];

$acts[] = [$c, 'next_steps', 9, 'Count for Assessment', 'Count objects for the final assessment!',
    act_json('mango_counting', ['min'=>1,'max'=>8,'object'=>'coconut','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the coconuts!', 'Prepare for assessment.', 'Count all the coconuts!',
    [1,2,3,4,5,6,7,8], 8, 'Eight coconuts!', 'easy', 2)];

/* ================================================================
   L08: Subtraction Assessment
   Focus: Comprehensive assessment
   Objects: coconut, cabbage (mixed) | Numbers: start 5-10, remove 1-5
   ================================================================ */
$c = 'NUM-06-L08';
$acts[] = [$c, 'intro', 0, 'Assessment Time', 'Show what you know about subtraction!',
    act_json('number_identification', ['min'=>1,'max'=>10,'poolSize'=>5,'target_number'=>5,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Find the number!', 'Prepare for assessment.', 'Find the number 5 to start!',
    [3,4,5,6,7], 5, '5! Let us begin!', 'medium', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Coconuts', 'Count all the coconuts!',
    act_json('mango_counting', ['min'=>1,'max'=>8,'object'=>'coconut','difficulty'=>1,'step_type'=>'warmup'],
    'Count the coconuts!', 'Review counting.', 'Count every coconut carefully!',
    [1,2,3,4,5,6,7,8], 8, 'Eight coconuts!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Assessment Demo', 'Watch me solve a subtraction problem!',
    act_json('visual_subtraction', ['object'=>'coconut','start'=>9,'remove'=>4,'difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch the demo!', 'Model assessment problem.', '9 coconuts. I remove 4. Watch me count!',
    [5], 5, '9 minus 4 equals 5!', 'medium', 3)];

$acts[] = [$c, 'we_do', 3, 'Practice Round', 'Solve together: 10 - 5!',
    act_json('visual_subtraction', ['object'=>'coconut','start'=>10,'remove'=>5,'difficulty'=>2,'step_type'=>'we_do'],
    'Tap to subtract!', 'Guided assessment practice.', '10 coconuts. Remove 5 together!',
    [5], 5, '10 minus 5 equals 5!', 'medium', 3)];

$acts[] = [$c, 'you_do', 4, 'Your Assessment!', 'Solve: 8 - 3!',
    act_json('visual_subtraction', ['object'=>'coconut','start'=>8,'remove'=>3,'difficulty'=>2,'step_type'=>'you_do'],
    'Solve the problem!', 'Independent assessment.', '8 coconuts. Remove 3. How many left?',
    [5], 5, '8 minus 3 equals 5!', 'medium', 3)];

$acts[] = [$c, 'check', 5, 'Final Check', 'Show your subtraction skills!',
    act_json('number_identification', ['min'=>1,'max'=>10,'poolSize'=>5,'target_number'=>5,'difficulty'=>2,'step_type'=>'check'],
    'Find the answer!', 'Assessment number identification.', 'What is 10 minus 5? Find the number!',
    [3,4,5,6,7], 5, '5 is correct!', 'medium', 2)];

$acts[] = [$c, 'game', 6, 'Assessment Game', 'Play the assessment game!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'game'],
    'Play the game!', 'Game-based assessment.', 'Tap the right answers!',
    [], '', 'Great assessment!', 'medium', 5)];

$acts[] = [$c, 'assessment', 7, 'Final Assessment', 'Show your best subtraction skills!',
    act_json('visual_subtraction', ['object'=>'cabbage','start'=>10,'remove'=>5,'difficulty'=>2,'step_type'=>'assessment'],
    'Solve the final assessment!', 'Comprehensive subtraction assessment.', '10 cabbages. Remove 5. How many left?',
    [5], 5, '10 minus 5 equals 5!', 'medium', 3)];

$acts[] = [$c, 'reward', 8, 'Congratulations!', 'You completed subtraction!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'Congratulations!', 'Celebrate completion.', 'You have mastered subtraction!',
    [], '', 'You are amazing!', 'medium', 2)];

$acts[] = [$c, 'next_steps', 9, 'What Is Next?', 'Count for the next chapter!',
    act_json('mango_counting', ['min'=>1,'max'=>10,'object'=>'star','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Count the stars!', 'Prepare for next chapter.', 'Count all the stars to get ready!',
    [1,2,3,4,5,6,7,8,9,10], 10, 'Ten stars! Ready for more!', 'easy', 2)];

/* ----------------------------------------------------------------
   STEP 4: Insert activities into database
   ---------------------------------------------------------------- */
echo "--- STEP 4: Database Insert ---\n";

$inserted = 0;
$updated = 0;
$errors = 0;

foreach ($acts as [$lesson_code, $step_type, $step_order, $name, $desc, $json]) {
    /* Look up lesson_id */
    $row = $database->fetchOne(
        "SELECT l.lesson_id, l.topic_id, t.module_id FROM lessons l JOIN topics t ON l.topic_id = t.topic_id WHERE l.lesson_code = ?",
        [$lesson_code]
    );
    if (!$row) {
        echo "ERROR: Lesson $lesson_code not found!\n";
        $errors++;
        continue;
    }
    $lid = (int)$row['lesson_id'];
    $module_id = (int)$row['module_id'];

    $data = json_decode($json, true);
    if (!$data || !isset($data['engine'])) {
        echo "ERROR: Invalid JSON for $name\n";
        $errors++;
        continue;
    }

    $diff = $data['difficulty'] <= 1 ? 'easy' : ($data['difficulty'] <= 2 ? 'medium' : 'hard');
    $instruction_text = $data['instruction'] ?? '';

    /* Check if activity already exists */
    $existing = $database->fetchOne(
        "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? AND step_order = ?",
        [$lid, $step_type, $step_order]
    );

    if ($existing) {
        $database->execute(
            "UPDATE activities SET activity_name=?, activity_description=?, difficulty_level=?, activity_data=?, audio_instruction=? WHERE activity_id=?",
            [$name, $desc, $diff, $json, $instruction_text, $existing['activity_id']]
        );
        $updated++;
        echo "  UPDATE: $lesson_code $step_type $step_order — $name\n";
    } else {
        $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction)
             VALUES (?, ?, ?, ?, ?, ?, 'subtraction', ?, ?, ?)",
            [$module_id, $lid, $step_type, $step_order, $name, $desc, $diff, $json, $instruction_text]
        );
        $inserted++;
        echo "  INSERT: $lesson_code $step_type $step_order — $name\n";
    }
}

echo "\nResults: $inserted inserted, $updated updated, $errors errors\n\n";

/* ----------------------------------------------------------------
   STEP 5: Verification
   ---------------------------------------------------------------- */
echo "=== VERIFICATION ===\n";

/* Topic check */
$topic = $database->fetchOne("SELECT * FROM topics WHERE topic_code = 'NUM-06'");
echo $topic ? "✓ Topic NUM-06 exists: {$topic['topic_name']}\n" : "✗ Topic NUM-06 NOT found\n";

/* Lessons check */
$lessonsCount = $database->fetchOne("SELECT COUNT(*) as cnt FROM lessons WHERE lesson_code LIKE 'NUM-06-%'");
echo ($lessonsCount && $lessonsCount['cnt'] === 8) ? "✓ 8 lessons created\n" : "✗ Expected 8 lessons, found " . ($lessonsCount['cnt'] ?? 0) . "\n";

/* Activities per lesson */
$perLesson = $database->fetchAll(
    "SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-06-%'
     GROUP BY l.lesson_code
     ORDER BY l.order_index"
);

$total = 0;
foreach ($perLesson as $pl) {
    $mark = $pl['cnt'] == 10 ? '✓' : ('✗ (has ' . $pl['cnt'] . ')');
    echo "  {$pl['lesson_code']}: {$pl['lesson_name']} — {$pl['cnt']} activities $mark\n";
    $total += (int)$pl['cnt'];
}

echo "\nTOTAL: $total / 80 activities\n";
echo ($total === 80) ? "\n✓ ALL 80 ACTIVITIES CREATED SUCCESSFULLY\n" : "\n✗ Expected 80, got $total\n";

/* Engine check */
$engines = $database->fetchAll(
    "SELECT JSON_EXTRACT(activity_data, '$.engine') as engine, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-06-%'
     GROUP BY engine"
);
echo "\nEngine usage:\n";
foreach ($engines as $e) {
    echo "  {$e['engine']}: {$e['cnt']} activities\n";
}

/* JSON validity check */
$invalid = $database->fetchOne(
    "SELECT COUNT(*) as cnt FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-06-%'
     AND JSON_VALID(activity_data) = 0"
);
echo ($invalid && $invalid['cnt'] == 0) ? "\n✓ All activity JSON valid\n" : "\n✗ Invalid JSON found: " . ($invalid['cnt'] ?? '?') . "\n";

echo "\n=== PHASE 9 COMPLETE ===\n";
echo "</pre>";
