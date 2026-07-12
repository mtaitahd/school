<?php
/**
 * PHASE 8 — Create NUM-05: Addition
 *
 * Creates: topic, 8 lessons, 80 activities (10 per lesson)
 * Uses: drag_addition engine (primary), mango_counting, number_identification,
 *       match_quantity, math_game
 *
 * Visit: https://smartmathconner.co.tz/database/migrate_num05_addition.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== PHASE 8: Create NUM-05 — Addition ===\n\n";

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
        'visual' => ['theme' => 'addition', 'background' => 'light', 'show_progress' => true, 'large_numbers' => true, 'large_objects' => true, 'animation' => 'fade']
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ----------------------------------------------------------------
   STEP 1: Create Module + Topic
   ---------------------------------------------------------------- */
echo "--- STEP 1: Module & Topic ---\n";

/* Create module 57: Addition */
$database->execute(
    "INSERT IGNORE INTO modules (module_id, module_name, module_description, module_icon, module_color, audio_prompt, order_index)
     VALUES (57, 'Addition', 'Learn to add numbers using pictures and objects', 'fa-plus', '#27ae60', 'Touch here for Addition!', 15)"
);
echo "Module 57 (Addition) ensured.\n";

/* Get strand_id for NUM */
$strandRow = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strandRow) {
    die("ERROR: Strand 'NUM' not found. Run migrations_v9 first.\n");
}
$strand_id = (int)$strandRow['strand_id'];

/* Create topic NUM-05 */
$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
     VALUES (?, 57, 'Addition', 'NUM-05', '4-5', 'Learn to add two groups of objects and find the total using pictures', 8, 5)",
    [$strand_id]
);
echo "Topic NUM-05 (Addition) ensured.\n\n";

/* ----------------------------------------------------------------
   STEP 2: Create 8 Lessons
   ---------------------------------------------------------------- */
echo "--- STEP 2: Lessons ---\n";

$lessons = [
    ['NUM-05-L01', 'Introduction to Addition',
     'By the end of this lesson, the child understands that addition means putting two groups together to make a bigger group.',
     'Child can explain that adding means combining two groups, and can demonstrate with objects by moving them together.',
     20, null, 1],
    ['NUM-05-L02', 'Adding Two Groups',
     'By the end of this lesson, the child can count each group separately before combining them.',
     'Child can count the first group, count the second group, and then combine both groups to find the total.',
     20, '["NUM-05-L01"]', 2],
    ['NUM-05-L03', 'Finding the Total',
     'By the end of this lesson, the child can count the combined group to find the total.',
     'Child can combine two groups and count all objects together to find the correct total.',
     20, '["NUM-05-L02"]', 3],
    ['NUM-05-L04', 'Picture Addition',
     'By the end of this lesson, the child can add using pictures of familiar objects like eggplants, oranges, and cabbages.',
     'Child can use pictures to add two groups and find the total using objects from the workbook.',
     20, '["NUM-05-L03"]', 4],
    ['NUM-05-L05', 'Reading Addition Sentences',
     'By the end of this lesson, the child can read a simple addition sentence like 3 + 2 = 5 and understand what it means.',
     'Child can read the plus sign as "and" or "put together", and the equals sign as "makes" or "is".',
     20, '["NUM-05-L04"]', 5],
    ['NUM-05-L06', 'Guided Practice',
     'By the end of this lesson, the child can add two groups of different objects confidently.',
     'Child can independently count two groups, combine them, and state the total using various objects.',
     20, '["NUM-05-L05"]', 6],
    ['NUM-05-L07', 'Addition Game',
     'By the end of this lesson, the child can solve addition problems quickly through a fun game.',
     'Child can solve addition sums up to 10 with at least 80% accuracy in a game format.',
     25, '["NUM-05-L06"]', 7],
    ['NUM-05-L08', 'Addition Assessment',
     'By the end of this lesson, the child can demonstrate mastery of addition through picture-based problems and simple number sentences.',
     'Child scores at least 80% on the addition assessment covering counting groups, combining, and finding totals.',
     25, '["NUM-05-L07"]', 8],
];

foreach ($lessons as [$code, $name, $objective, $criteria, $minutes, $prereq, $order]) {
    $database->execute(
        "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, prerequisite_lesson_ids, order_index)
         VALUES ((SELECT topic_id FROM topics WHERE topic_code = 'NUM-05'), ?, ?, ?, ?, ?, ?, ?)",
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
   L01: Introduction to Addition
   Focus: "Putting groups together"
   Objects: apple, star, mango | Sums ≤ 4
   ================================================================ */
$c = 'NUM-05-L01';
$acts[] = [$c, 'intro', 0, 'Putting Groups Together', 'Addition means putting two groups together!',
    act_json('drag_addition', ['a'=>1,'b'=>1,'object'=>'apple','difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Put the apples together!', 'Understand addition concept.', 'One apple plus one apple. Put them in the basket!',
    [2], 2, 'Two apples together!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Stars', 'Count how many stars you see!',
    act_json('mango_counting', ['min'=>1,'max'=>3,'object'=>'star','difficulty'=>1,'step_type'=>'warmup'],
    'Count the stars!', 'Review counting 1 to 3.', 'Count all the stars carefully!',
    [1,2,3], 3, 'Three stars!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Add Mangoes', 'Watch as I add one mango and two mangoes!',
    act_json('drag_addition', ['a'=>1,'b'=>2,'object'=>'mango','difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me add!', 'Teacher demonstrates addition.', 'One mango plus two mangoes. Watch them come together!',
    [3], 3, 'Three mangoes!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add Apples Together', 'Let us add two apples and one apple!',
    act_json('drag_addition', ['a'=>2,'b'=>1,'object'=>'apple','difficulty'=>1,'step_type'=>'we_do'],
    'Let us add together!', 'Guided practice combining groups.', 'Two apples plus one apple. Move them to the basket!',
    [3], 3, 'Three apples!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add Stars Yourself', 'Add one star and three stars by yourself!',
    act_json('drag_addition', ['a'=>1,'b'=>3,'object'=>'star','difficulty'=>1,'step_type'=>'you_do'],
    'Now you add!', 'Independent practice.', 'One star plus three stars. Move them to the basket!',
    [4], 4, 'Four stars!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 4', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>6,'poolSize'=>3,'target_number'=>4,'difficulty'=>1,'step_type'=>'check'],
    'Find number 4!', 'Check understanding.', 'Which number is 4?',
    [3,4,5], 4, 'Yes! 4!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Addition game!', 'Game: practice adding.', 'Let us play an addition game!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Add Two and Two', 'Add two apples and two apples!',
    act_json('drag_addition', ['a'=>2,'b'=>2,'object'=>'apple','difficulty'=>1,'step_type'=>'assessment'],
    'Add 2 apples and 2 apples!', 'Assess: add two groups.', 'Two apples plus two apples. How many in total?',
    [4], 4, 'You know addition!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You learned what addition means!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You learned addition!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Adding Two Groups', 'Ready for more addition!',
    act_json('mango_counting', ['min'=>1,'max'=>4,'object'=>'apple','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: adding groups!', 'Preview.', 'Ready to add two groups?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L02: Adding Two Groups
   Focus: Count group 1, count group 2, combine
   Objects: ball, fish, candy | Sums ≤ 5
   ================================================================ */
$c = 'NUM-05-L02';
$acts[] = [$c, 'intro', 0, 'Two Groups Become One', 'Count each group, then put them together!',
    act_json('drag_addition', ['a'=>2,'b'=>1,'object'=>'ball','difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Two groups become one!', 'Understand combining groups.', 'Two balls plus one ball. Put them together!',
    [3], 3, 'Three balls!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Fish', 'Count all the fish!',
    act_json('mango_counting', ['min'=>1,'max'=>4,'object'=>'fish','difficulty'=>1,'step_type'=>'warmup'],
    'Count the fish!', 'Review counting to 4.', 'Count all the fish carefully!',
    [1,2,3,4], 4, 'Four fish!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Add Candies', 'Watch as I add two candies and two candies!',
    act_json('drag_addition', ['a'=>2,'b'=>2,'object'=>'candy','difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me add candies!', 'Teacher counts both groups.', 'Two candies plus two candies. Watch them come together!',
    [4], 4, 'Four candies!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add Balls Together', 'Let us add three balls and one ball!',
    act_json('drag_addition', ['a'=>3,'b'=>1,'object'=>'ball','difficulty'=>1,'step_type'=>'we_do'],
    'Let us count and add!', 'Guided practice counting groups.', 'Three balls plus one ball. Move them to the basket!',
    [4], 4, 'Four balls!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add Fish Yourself', 'Add two fish and three fish by yourself!',
    act_json('drag_addition', ['a'=>2,'b'=>3,'object'=>'fish','difficulty'=>1,'step_type'=>'you_do'],
    'Count both groups yourself!', 'Independent practice.', 'Two fish plus three fish. Move them to the basket!',
    [5], 5, 'Five fish!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 5', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>7,'poolSize'=>4,'target_number'=>5,'difficulty'=>1,'step_type'=>'check'],
    'Find number 5!', 'Check understanding.', 'Which number is 5?',
    [4,5,6], 5, 'Yes! 5!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Addition game!', 'Game: practice adding.', 'Let us play an addition game!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Add Four and One', 'Add four candies and one candy!',
    act_json('drag_addition', ['a'=>4,'b'=>1,'object'=>'candy','difficulty'=>1,'step_type'=>'assessment'],
    'Add 4 candies and 1 candy!', 'Assess: add two groups.', 'Four candies plus one candy. How many in total?',
    [5], 5, 'You know addition!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You can count two groups and add them!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You added two groups!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Finding the Total', 'Ready to find the total!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'ball','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: finding the total!', 'Preview.', 'Ready to find totals?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L03: Finding the Total
   Focus: Count the combined group
   Objects: duck, balloon, flower | Sums ≤ 6
   ================================================================ */
$c = 'NUM-05-L03';
$acts[] = [$c, 'intro', 0, 'Finding the Total', 'Count all the objects after combining!',
    act_json('drag_addition', ['a'=>2,'b'=>1,'object'=>'duck','difficulty'=>1,'step_type'=>'intro','skip_finish'=>true],
    'Finding the total!', 'Understand finding total.', 'Two ducks plus one duck. Count the total!',
    [3], 3, 'Three ducks!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Balloons', 'Count all the balloons!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'balloon','difficulty'=>1,'step_type'=>'warmup'],
    'Count the balloons!', 'Review counting to 5.', 'Count all the balloons!',
    [1,2,3,4,5], 5, 'Five balloons!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Find the Total', 'Watch as I add three flowers and two flowers!',
    act_json('drag_addition', ['a'=>3,'b'=>2,'object'=>'flower','difficulty'=>1,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me find the total!', 'Teacher demonstrates finding total.', 'Three flowers plus two flowers. Count them all!',
    [5], 5, 'Five flowers!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Find the Total Together', 'Let us add two ducks and three ducks!',
    act_json('drag_addition', ['a'=>2,'b'=>3,'object'=>'duck','difficulty'=>1,'step_type'=>'we_do'],
    'Let us find the total together!', 'Guided practice finding total.', 'Two ducks plus three ducks. Count the total!',
    [5], 5, 'Five ducks!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Find the Total Yourself', 'Add four balloons and one balloon!',
    act_json('drag_addition', ['a'=>4,'b'=>1,'object'=>'balloon','difficulty'=>1,'step_type'=>'you_do'],
    'Find the total yourself!', 'Independent practice.', 'Four balloons plus one balloon. Count the total!',
    [5], 5, 'Five balloons!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find the Group with 5', 'Which group has 5 flowers?',
    act_json('match_quantity', ['min'=>1,'max'=>6,'object'=>'flower','target'=>5,'difficulty'=>1,'step_type'=>'check'],
    'Find the group with 5 flowers!', 'Check: match quantity.', 'Which group has 5 flowers?',
    [4,5,6], 5, 'Yes! 5 flowers!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'game'],
    'Addition game!', 'Game: practice adding.', 'Let us play an addition game!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Add Three and Two', 'How many flowers in total?',
    act_json('drag_addition', ['a'=>3,'b'=>2,'object'=>'flower','difficulty'=>1,'step_type'=>'assessment'],
    'Add 3 flowers and 2 flowers!', 'Assess: finding total.', 'Three flowers plus two flowers. How many in total?',
    [5], 5, 'You found the total!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You can find the total!',
    act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You found the total!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Picture Addition', 'Ready for picture addition!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'flower','difficulty'=>1,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: picture addition!', 'Preview.', 'Ready for workbook pictures?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L04: Picture Addition
   Focus: Workbook objects — eggplant, orange, cabbage, apple
   Objects: orange, eggplant, cabbage, apple | Sums ≤ 7
   ================================================================ */
$c = 'NUM-05-L04';
$acts[] = [$c, 'intro', 0, 'Adding with Pictures', 'Use pictures to add!',
    act_json('drag_addition', ['a'=>1,'b'=>2,'object'=>'orange','difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Adding with pictures!', 'Understand picture addition.', 'One orange plus two oranges. Move them!',
    [3], 3, 'Three oranges!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Apples', 'Count all the apples!',
    act_json('mango_counting', ['min'=>1,'max'=>4,'object'=>'apple','difficulty'=>1,'step_type'=>'warmup'],
    'Count the apples!', 'Review counting.', 'Count all the apples!',
    [1,2,3,4], 4, 'Four apples!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Add Eggplants', 'Watch as I add two eggplants and one eggplant!',
    act_json('drag_addition', ['a'=>2,'b'=>1,'object'=>'eggplant','difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me add eggplants!', 'Teacher demonstrates with workbook objects.', 'Two eggplants plus one eggplant!',
    [3], 3, 'Three eggplants!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add Cabbages Together', 'Let us add one cabbage and two cabbages!',
    act_json('drag_addition', ['a'=>1,'b'=>2,'object'=>'cabbage','difficulty'=>2,'step_type'=>'we_do'],
    'Let us add cabbages!', 'Guided practice with workbook objects.', 'One cabbage plus two cabbages!',
    [3], 3, 'Three cabbages!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add Oranges Yourself', 'Add three oranges and one orange!',
    act_json('drag_addition', ['a'=>3,'b'=>1,'object'=>'orange','difficulty'=>2,'step_type'=>'you_do'],
    'Add the oranges yourself!', 'Independent practice with workbook objects.', 'Three oranges plus one orange!',
    [4], 4, 'Four oranges!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 4', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>7,'poolSize'=>4,'target_number'=>4,'difficulty'=>1,'step_type'=>'check'],
    'Find number 4!', 'Check understanding.', 'Which number is 4?',
    [3,4,5], 4, 'Yes! 4!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Addition game!', 'Game: add with pictures.', 'Let us play!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Add Two and Two', 'Add two apples and two apples!',
    act_json('drag_addition', ['a'=>2,'b'=>2,'object'=>'apple','difficulty'=>2,'step_type'=>'assessment'],
    'Add 2 apples and 2 apples!', 'Assess: picture addition.', 'Two apples plus two apples. How many in total?',
    [4], 4, 'You used pictures to add!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You added with pictures!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You added with pictures!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Addition Sentences', 'Ready for number sentences!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'orange','difficulty'=>2,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: addition sentences!', 'Preview.', 'Ready for 2 + 1 = 3?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L05: Reading Addition Sentences
   Focus: 3 + 2 = 5 format
   Objects: apple, mango, ball, fish, candy | Sums ≤ 7
   ================================================================ */
$c = 'NUM-05-L05';
$acts[] = [$c, 'intro', 0, 'Reading Addition Sentences', 'Learn what the plus and equals signs mean!',
    act_json('drag_addition', ['a'=>2,'b'=>1,'object'=>'apple','difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Reading addition sentences!', 'Understand + and = signs.', '2 + 1 means 2 apples and 1 apple. The answer is 3!',
    [3], 3, '2 plus 1 equals 3!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Stars', 'Count all the stars!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'star','difficulty'=>1,'step_type'=>'warmup'],
    'Count the stars!', 'Review counting to 5.', 'Count all the stars!',
    [1,2,3,4,5], 5, 'Five stars!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch: 3 + 2 = 5', 'Watch as I read 3 plus 2 equals 5!',
    act_json('drag_addition', ['a'=>3,'b'=>2,'object'=>'mango','difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me read the sentence!', 'Teacher reads addition sentence.', 'Three mangoes plus two mangoes equals five!',
    [5], 5, '3 plus 2 equals 5!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Read: 2 + 3 = 5', 'Read this sentence: 2 plus 3!',
    act_json('drag_addition', ['a'=>2,'b'=>3,'object'=>'ball','difficulty'=>2,'step_type'=>'we_do'],
    'Read the addition sentence!', 'Guided practice reading sentences.', 'Two balls plus three balls. What is the answer?',
    [5], 5, '2 plus 3 equals 5!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Read: 4 + 1 = 5', 'Read and solve: 4 plus 1!',
    act_json('drag_addition', ['a'=>4,'b'=>1,'object'=>'fish','difficulty'=>2,'step_type'=>'you_do'],
    'Read and solve!', 'Independent practice reading sentences.', 'Four fish plus one fish. What is the answer?',
    [5], 5, '4 plus 1 equals 5!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 5', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>8,'poolSize'=>4,'target_number'=>5,'difficulty'=>2,'step_type'=>'check'],
    'Find number 5!', 'Check understanding.', 'Which number is 5?',
    [4,5,6], 5, 'Yes! 5!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Addition game!', 'Game: read sentences.', 'Let us play!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'What is 3 + 3?', 'Solve: 3 plus 3!',
    act_json('drag_addition', ['a'=>3,'b'=>3,'object'=>'candy','difficulty'=>2,'step_type'=>'assessment'],
    'What is 3 plus 3?', 'Assess: reading sentences.', 'Three candies plus three candies. How many in total?',
    [6], 6, '3 plus 3 equals 6!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You can read addition sentences!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You read addition sentences!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Guided Practice', 'Ready for more practice!',
    act_json('mango_counting', ['min'=>1,'max'=>7,'object'=>'candy','difficulty'=>2,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: more practice!', 'Preview.', 'Ready for guided practice?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L06: Guided Practice
   Focus: Various workbook objects — boat, eraser, mango, chair, bird
   Objects: boat, eraser, mango, chair, bird | Sums ≤ 8
   ================================================================ */
$c = 'NUM-05-L06';
$acts[] = [$c, 'intro', 0, 'Practice Adding!', 'Let us practice adding with different objects!',
    act_json('drag_addition', ['a'=>2,'b'=>2,'object'=>'boat','difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Practice adding!', 'Practice with workbook objects.', 'Two boats plus two boats. Move them!',
    [4], 4, 'Four boats!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Count the Birds', 'Count all the birds!',
    act_json('mango_counting', ['min'=>1,'max'=>5,'object'=>'bird','difficulty'=>1,'step_type'=>'warmup'],
    'Count the birds!', 'Review counting.', 'Count all the birds!',
    [1,2,3,4,5], 5, 'Five birds!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch Me Add Erasers', 'Watch as I add three erasers and two erasers!',
    act_json('drag_addition', ['a'=>3,'b'=>2,'object'=>'eraser','difficulty'=>2,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me add erasers!', 'Teacher demonstrates.', 'Three erasers plus two erasers!',
    [5], 5, 'Five erasers!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add Chairs Together', 'Let us add four chairs and one chair!',
    act_json('drag_addition', ['a'=>4,'b'=>1,'object'=>'chair','difficulty'=>2,'step_type'=>'we_do'],
    'Let us add chairs!', 'Guided practice.', 'Four chairs plus one chair!',
    [5], 5, 'Five chairs!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add Mangoes Yourself', 'Add two mangoes and four mangoes!',
    act_json('drag_addition', ['a'=>2,'b'=>4,'object'=>'mango','difficulty'=>2,'step_type'=>'you_do'],
    'Add the mangoes yourself!', 'Independent practice.', 'Two mangoes plus four mangoes!',
    [6], 6, 'Six mangoes!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find the Group with 6', 'Which group has 6 birds?',
    act_json('match_quantity', ['min'=>1,'max'=>8,'object'=>'bird','target'=>6,'difficulty'=>2,'step_type'=>'check'],
    'Find the group with 6 birds!', 'Check: match quantity.', 'Which group has 6 birds?',
    [5,6,7], 6, 'Yes! 6 birds!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Game', 'Play a fun addition game!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'game'],
    'Addition game!', 'Game: practice adding.', 'Let us play!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Add Three and Three', 'How many boats in total?',
    act_json('drag_addition', ['a'=>3,'b'=>3,'object'=>'boat','difficulty'=>2,'step_type'=>'assessment'],
    'Add 3 boats and 3 boats!', 'Assess: guided practice.', 'Three boats plus three boats. How many in total?',
    [6], 6, 'You can add well!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You practiced adding with many objects!',
    act_json('math_game', ['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'Great practice!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Addition Game', 'Ready for the game!',
    act_json('mango_counting', ['min'=>1,'max'=>8,'object'=>'bird','difficulty'=>2,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: addition game!', 'Preview.', 'Ready for a fun game?',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L07: Addition Game
   Focus: Fun game with larger sums
   Objects: mixed | Sums ≤ 10
   ================================================================ */
$c = 'NUM-05-L07';
$acts[] = [$c, 'intro', 0, 'Addition Game Time!', 'Let us play a fun addition game!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'intro','skip_finish'=>true],
    'Addition game time!', 'Game introduction.', 'Let us play an addition game!',
    [], [], 'Let us play!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Quick Count', 'Count quickly from 1 to 6!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'star','difficulty'=>2,'step_type'=>'warmup'],
    'Quick count!', 'Fast counting warm-up.', 'Count all the stars quickly!',
    [1,2,3,4,5,6], 6, 'Six stars!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Watch: 5 + 2 = 7', 'Watch me add 5 and 2!',
    act_json('drag_addition', ['a'=>5,'b'=>2,'object'=>'apple','difficulty'=>3,'step_type'=>'i_do','skip_finish'=>true],
    'Watch me add 5 and 2!', 'Teacher adds larger numbers.', 'Five apples plus two apples!',
    [7], 7, '5 plus 2 equals 7!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add 3 + 4', 'Let us add 3 and 4!',
    act_json('drag_addition', ['a'=>3,'b'=>4,'object'=>'ball','difficulty'=>3,'step_type'=>'we_do'],
    'Let us add 3 and 4!', 'Guided practice with larger sums.', 'Three balls plus four balls!',
    [7], 7, '3 plus 4 equals 7!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add 6 + 2', 'Add 6 and 2 by yourself!',
    act_json('drag_addition', ['a'=>6,'b'=>2,'object'=>'fish','difficulty'=>3,'step_type'=>'you_do'],
    'Add 6 and 2!', 'Independent practice with larger sums.', 'Six fish plus two fish!',
    [8], 8, '6 plus 2 equals 8!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 7', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>10,'poolSize'=>5,'target_number'=>7,'difficulty'=>2,'step_type'=>'check'],
    'Find number 7!', 'Check understanding.', 'Which number is 7?',
    [6,7,8], 7, 'Yes! 7!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Addition Challenge', 'Challenge: solve as many as you can!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'game'],
    'Addition challenge!', 'Challenge game.', 'Let us see how many you can solve!',
    [], [], 'Challenge!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'What is 4 + 4?', 'Solve: 4 plus 4!',
    act_json('drag_addition', ['a'=>4,'b'=>4,'object'=>'candy','difficulty'=>3,'step_type'=>'assessment'],
    'What is 4 plus 4?', 'Assess: larger sums.', 'Four candies plus four candies. How many in total?',
    [8], 8, '4 plus 4 equals 8!', 'easy', 3)];

$acts[] = [$c, 'reward', 8, 'Great Work!', 'You played the addition game!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],
    'You earned a star!', 'Celebrate.', 'You played the game!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Next: Assessment', 'Ready for the test!',
    act_json('mango_counting', ['min'=>1,'max'=>10,'object'=>'candy','difficulty'=>3,'step_type'=>'next_steps','skip_finish'=>true],
    'Next: the test!', 'Preview.', 'Show everything you know!',
    [], [], 'Great!', 'easy', 1)];

/* ================================================================
   L08: Addition Assessment
   Focus: Comprehensive assessment
   Objects: mixed workbook objects | Sums ≤ 10
   ================================================================ */
$c = 'NUM-05-L08';
$acts[] = [$c, 'intro', 0, 'Assessment Time', 'Show what you know about addition!',
    act_json('number_identification', ['min'=>1,'max'=>10,'poolSize'=>5,'target_number'=>5,'difficulty'=>2,'step_type'=>'intro','skip_finish'=>true],
    'Assessment time!', 'Assessment introduction.', 'Show what you know!',
    [5], 5, 'Let us begin!', 'easy', 2)];

$acts[] = [$c, 'warmup', 1, 'Quick Warm-Up', 'Count quickly!',
    act_json('mango_counting', ['min'=>1,'max'=>6,'object'=>'apple','difficulty'=>2,'step_type'=>'warmup'],
    'Quick warm-up!', 'Fast warm-up.', 'Count all the apples!',
    [1,2,3,4,5,6], 6, 'Six apples!', 'easy', 2)];

$acts[] = [$c, 'i_do', 2, 'Add 4 Mangoes + 3 Mangoes', 'Add four mangoes and three mangoes!',
    act_json('drag_addition', ['a'=>4,'b'=>3,'object'=>'mango','difficulty'=>3,'step_type'=>'i_do','skip_finish'=>true],
    'Add 4 mangoes and 3 mangoes!', 'Assessment: adding groups.', 'Four mangoes plus three mangoes!',
    [7], 7, '4 plus 3 equals 7!', 'easy', 2)];

$acts[] = [$c, 'we_do', 3, 'Add 5 Oranges + 2 Oranges', 'Add five oranges and two oranges!',
    act_json('drag_addition', ['a'=>5,'b'=>2,'object'=>'orange','difficulty'=>3,'step_type'=>'we_do'],
    'Add 5 oranges and 2 oranges!', 'Assessment: picture addition.', 'Five oranges plus two oranges!',
    [7], 7, '5 plus 2 equals 7!', 'easy', 2)];

$acts[] = [$c, 'you_do', 4, 'Add 3 Birds + 4 Birds', 'Add three birds and four birds!',
    act_json('drag_addition', ['a'=>3,'b'=>4,'object'=>'bird','difficulty'=>3,'step_type'=>'you_do'],
    'Add 3 birds and 4 birds!', 'Assessment: independent.', 'Three birds plus four birds!',
    [7], 7, '3 plus 4 equals 7!', 'easy', 2)];

$acts[] = [$c, 'check', 5, 'Find Number 8', 'Tap the correct number!',
    act_json('number_identification', ['min'=>1,'max'=>10,'poolSize'=>5,'target_number'=>8,'difficulty'=>2,'step_type'=>'check'],
    'Find number 8!', 'Check understanding.', 'Which number is 8?',
    [7,8,9], 8, 'Yes! 8!', 'easy', 1)];

$acts[] = [$c, 'game', 6, 'Final Game', 'Play the final addition game!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'game'],
    'Final game!', 'Final game.', 'Let us play one more time!',
    [], [], 'Fun!', 'easy', 3)];

$acts[] = [$c, 'assessment', 7, 'Final Test: 5 + 3', 'The final test: 5 plus 3!',
    act_json('drag_addition', ['a'=>5,'b'=>3,'object'=>'apple','difficulty'=>3,'step_type'=>'assessment'],
    'Final test: 5 plus 3!', 'Final assessment.', 'Five apples plus three apples. How many in total?',
    [8], 8, '5 plus 3 equals 8!', 'easy', 5)];

$acts[] = [$c, 'reward', 8, 'Congratulations!', 'You completed Addition!',
    act_json('math_game', ['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],
    'Congratulations!', 'Celebrate completion.', 'You learned addition!',
    [], [], 'Amazing!', 'easy', 1)];

$acts[] = [$c, 'next_steps', 9, 'Ready for Subtraction!', 'You are ready for subtraction!',
    act_json('mango_counting', ['min'=>1,'max'=>10,'object'=>'star','difficulty'=>3,'step_type'=>'next_steps','skip_finish'=>true],
    'What is next?', 'Preview next topic.', 'You know addition! Ready for subtraction!',
    [], [], 'Amazing!', 'easy', 1)];

echo "Defined " . count($acts) . " activities.\n\n";

/* ----------------------------------------------------------------
   STEP 4: Insert/Update activities
   ---------------------------------------------------------------- */
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
             VALUES (?, ?, ?, ?, ?, ?, 'addition', ?, ?, ?)",
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
$topic = $database->fetchOne("SELECT * FROM topics WHERE topic_code = 'NUM-05'");
echo $topic ? "✓ Topic NUM-05 exists: {$topic['topic_name']}\n" : "✗ Topic NUM-05 NOT found\n";

/* Lessons check */
$lessonsCount = $database->fetchOne("SELECT COUNT(*) as cnt FROM lessons WHERE lesson_code LIKE 'NUM-05-%'");
echo ($lessonsCount && $lessonsCount['cnt'] === 8) ? "✓ 8 lessons created\n" : "✗ Expected 8 lessons, found " . ($lessonsCount['cnt'] ?? 0) . "\n";

/* Activities per lesson */
$perLesson = $database->fetchAll(
    "SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-05-%'
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
     WHERE l.lesson_code LIKE 'NUM-05-%'
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
     WHERE l.lesson_code LIKE 'NUM-05-%'
     AND JSON_VALID(activity_data) = 0"
);
echo ($invalid && $invalid['cnt'] == 0) ? "\n✓ All activity JSON valid\n" : "\n✗ Invalid JSON found: " . ($invalid['cnt'] ?? '?') . "\n";

echo "\n=== PHASE 8 COMPLETE ===\n";
echo "</pre>";
