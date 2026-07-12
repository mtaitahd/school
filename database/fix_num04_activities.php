<?php
/**
 * PHASE 7 FIX — Populate Activities for Existing NUM-04 Lessons
 *
 * THIS IS THE CORRECT SCRIPT.
 * It does NOT create lessons or topics.
 * It only inserts activities into existing lessons.
 *
 * Visit: https://smartmathconner.co.tz/database/fix_num04_activities.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== PHASE 7 FIX: Populate NUM-04 Activities ===\n\n";

/* ----------------------------------------------------------------
   STEP 1: Find existing lesson IDs
   ---------------------------------------------------------------- */
$lessonCodes = [
    'NUM-04-L01', 'NUM-04-L02', 'NUM-04-L03', 'NUM-04-L04',
    'NUM-04-L05', 'NUM-04-L06', 'NUM-04-L07', 'NUM-04-L08'
];

$L = [];
foreach ($lessonCodes as $code) {
    $row = $database->fetchOne("SELECT lesson_id, module_id FROM lessons WHERE lesson_code = ?", [$code]);
    if ($row) {
        $L[$code] = ['lesson_id' => (int)$row['lesson_id'], 'module_id' => (int)$row['module_id']];
        echo "FOUND: $code → lesson_id={$row['lesson_id']}, module_id={$row['module_id']}\n";
    } else {
        echo "ERROR: Lesson $code not found in database!\n";
    }
}

if (count($L) !== 8) {
    die("ERROR: Expected 8 lessons, found " . count($L) . ". Cannot continue.\n");
}

/* Get module_id (use the one from any lesson) */
$module_id = reset($L)['module_id'];
echo "\nUsing module_id = $module_id\n\n";

/* ----------------------------------------------------------------
   STEP 2: Helper function to build activity_data JSON
   ---------------------------------------------------------------- */
function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
  return json_encode(array_merge([
    'engine'=>$engine,'instruction'=>$instruction,'objective'=>$objective,'content'=>$content,
    'choices'=>$choices,'answer'=>$answer,'feedback'=>$feedback,'difficulty'=>$difficulty,'estimated_time'=>$time,
    'audio'=>['instruction'=>$instruction,'number_name'=>'','enabled'=>false],
    'visual'=>['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
  ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

/* ----------------------------------------------------------------
   STEP 3: Define all 80 activities
   ---------------------------------------------------------------- */
$acts = [];

/* ===== L01: Introducing Numbers 11–12 ===== */
$c = 'NUM-04-L01';
$acts[] = [$c, 'intro', 0, 'Colour Number 11', 'Outline number 11 for colouring!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'interaction'=>'coloring','target_number'=>11,'difficulty'=>1], 'Colour the number 11!', 'Recognise 11.', 'Number 11 has two 1s side by side. Tap to colour!', [11,12,13], 11, 'Yes! 11 is two ones!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 11 Cows', 'Count all the cows!', act_json('mango_counting', ['min'=>11,'max'=>11,'object'=>'cow','difficulty'=>1], 'Count the cows!', 'Count to 11.', 'Count all the cows — there are 11!', [11], 11, 'Eleven cows!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Find Number 11', 'Which number is 11?', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'target_number'=>11,'difficulty'=>1], 'Find number 11!', 'Identify 11.', 'Which number is 11? Tap it!', [11,12,13,14,15], 11, 'Yes! 11!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 12 Chickens', 'Count the chickens!', act_json('mango_counting', ['min'=>12,'max'=>12,'object'=>'chicken','difficulty'=>1], 'Count the chickens!', 'Count to 12.', 'Count all the chickens — there are 12!', [12], 12, 'Twelve chickens!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Match 11 and 12', 'Find the group with the right number.', act_json('match_quantity', ['min'=>11,'max'=>12,'object'=>'cow','target'=>11,'difficulty'=>1], 'Find 11 cows!', 'Match quantity to number.', 'Which group has 11 cows?', [11,12], 11, 'Correct! 11 cows!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find 11 and 12', 'Tap the correct number.', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'difficulty'=>1], 'Find number 12!', 'Identify 12.', 'Which number is 12?', [11,12,13,14,15], 12, 'Yes! 12!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Counting Game', 'Count objects in the game!', act_json('math_game', ['difficulty'=>1,'min'=>11,'max'=>12,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Count to 12!', 'Game: count objects.', 'Hop to number 12!', [], 12, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Quiz: 11 and 12', 'Show what you know!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'difficulty'=>1], 'Find 11!', 'Assess knowledge.', 'Which number is 11?', [11,12,13,14,15], 11, 'You know 11 and 12!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Numbers 11–12!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You learned 11 and 12!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: 13 and 14', 'Ready for more numbers!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: 13 and 14!', 'Preview.', 'Ready for 13 and 14?', [], [], 'Great!', 'easy', 1)];

/* ===== L02: Numbers 13–14 ===== */
$c = 'NUM-04-L02';
$acts[] = [$c, 'intro', 0, 'Colour Number 13', 'Outline number 13 for colouring!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'interaction'=>'coloring','target_number'=>13,'difficulty'=>1], 'Colour the number 13!', 'Recognise 13.', 'Number 13 is 1 and 3. Tap to colour!', [11,12,13], 13, 'Yes! 13 is one and three!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 13 Apples', 'Count all the green apples!', act_json('mango_counting', ['min'=>13,'max'=>13,'object'=>'apple','difficulty'=>1], 'Count the green apples!', 'Count to 13.', 'Count all the green apples — there are 13!', [13], 13, 'Thirteen apples!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Find Number 13', 'Which number is 13?', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'target_number'=>13,'difficulty'=>1], 'Find number 13!', 'Identify 13.', 'Which number is 13? Tap it!', [11,12,13,14,15], 13, 'Yes! 13!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 14 Pumpkins', 'Count the pumpkins!', act_json('mango_counting', ['min'=>14,'max'=>14,'object'=>'pumpkin','difficulty'=>1], 'Count the pumpkins!', 'Count to 14.', 'Count all the pumpkins — there are 14!', [14], 14, 'Fourteen pumpkins!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Match 13 and 14', 'Find the group with the right number.', act_json('match_quantity', ['min'=>13,'max'=>14,'object'=>'apple','target'=>13,'difficulty'=>1], 'Find 13 apples!', 'Match quantity to number.', 'Which group has 13 apples?', [13,14], 13, 'Correct! 13 apples!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find 13 and 14', 'Tap the correct number.', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'difficulty'=>1], 'Find number 14!', 'Identify 14.', 'Which number is 14?', [11,12,13,14,15], 14, 'Yes! 14!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Counting Game', 'Count objects in the game!', act_json('math_game', ['difficulty'=>1,'min'=>13,'max'=>14,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Count to 14!', 'Game: count objects.', 'Hop to number 14!', [], 14, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Quiz: 13 and 14', 'Show what you know!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'difficulty'=>1], 'Find 13!', 'Assess knowledge.', 'Which number is 13?', [11,12,13,14,15], 13, 'You know 13 and 14!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Numbers 13–14!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You learned 13 and 14!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: 15 and 16', 'Ready for more numbers!', act_json('number_identification', ['min'=>11,'max'=>15,'poolSize'=>5,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: 15 and 16!', 'Preview.', 'Ready for 15 and 16?', [], [], 'Great!', 'easy', 1)];

/* ===== L03: Numbers 15–16 ===== */
$c = 'NUM-04-L03';
$acts[] = [$c, 'intro', 0, 'Colour Number 15', 'Outline number 15 for colouring!', act_json('number_identification', ['min'=>13,'max'=>17,'poolSize'=>5,'interaction'=>'coloring','target_number'=>15,'difficulty'=>1], 'Colour the number 15!', 'Recognise 15.', 'Number 15 is 1 and 5. Tap to colour!', [13,14,15], 15, 'Yes! 15 is one and five!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 15 Bells', 'Count all the bells!', act_json('mango_counting', ['min'=>15,'max'=>15,'object'=>'bell','difficulty'=>1], 'Count the bells!', 'Count to 15.', 'Count all the bells — there are 15!', [15], 15, 'Fifteen bells!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Find Number 15', 'Which number is 15?', act_json('number_identification', ['min'=>13,'max'=>17,'poolSize'=>5,'target_number'=>15,'difficulty'=>1], 'Find number 15!', 'Identify 15.', 'Which number is 15? Tap it!', [13,14,15,16,17], 15, 'Yes! 15!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 16 Guitars', 'Count the guitars!', act_json('mango_counting', ['min'=>16,'max'=>16,'object'=>'guitar','difficulty'=>1], 'Count the guitars!', 'Count to 16.', 'Count all the guitars — there are 16!', [16], 16, 'Sixteen guitars!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Match 15 and 16', 'Find the group with the right number.', act_json('match_quantity', ['min'=>15,'max'=>16,'object'=>'bell','target'=>15,'difficulty'=>1], 'Find 15 bells!', 'Match quantity to number.', 'Which group has 15 bells?', [15,16], 15, 'Correct! 15 bells!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find 15 and 16', 'Tap the correct number.', act_json('number_identification', ['min'=>13,'max'=>17,'poolSize'=>5,'difficulty'=>1], 'Find number 16!', 'Identify 16.', 'Which number is 16?', [13,14,15,16,17], 16, 'Yes! 16!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Counting Game', 'Count objects in the game!', act_json('math_game', ['difficulty'=>1,'min'=>15,'max'=>16,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Count to 16!', 'Game: count objects.', 'Hop to number 16!', [], 16, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Quiz: 15 and 16', 'Show what you know!', act_json('number_identification', ['min'=>13,'max'=>17,'poolSize'=>5,'difficulty'=>1], 'Find 15!', 'Assess knowledge.', 'Which number is 15?', [13,14,15,16,17], 15, 'You know 15 and 16!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Numbers 15–16!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You learned 15 and 16!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: 17 and 18', 'Ready for more numbers!', act_json('number_identification', ['min'=>13,'max'=>17,'poolSize'=>5,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: 17 and 18!', 'Preview.', 'Ready for 17 and 18?', [], [], 'Great!', 'easy', 1)];

/* ===== L04: Numbers 17–18 ===== */
$c = 'NUM-04-L04';
$acts[] = [$c, 'intro', 0, 'Colour Number 17', 'Outline number 17 for colouring!', act_json('number_identification', ['min'=>15,'max'=>19,'poolSize'=>5,'interaction'=>'coloring','target_number'=>17,'difficulty'=>1], 'Colour the number 17!', 'Recognise 17.', 'Number 17 is 1 and 7. Tap to colour!', [15,16,17], 17, 'Yes! 17 is one and seven!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 17 Watermelons', 'Count all the watermelons!', act_json('mango_counting', ['min'=>17,'max'=>17,'object'=>'watermelon','difficulty'=>1], 'Count the watermelons!', 'Count to 17.', 'Count all the watermelons — there are 17!', [17], 17, 'Seventeen watermelons!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Find Number 17', 'Which number is 17?', act_json('number_identification', ['min'=>15,'max'=>19,'poolSize'=>5,'target_number'=>17,'difficulty'=>1], 'Find number 17!', 'Identify 17.', 'Which number is 17? Tap it!', [15,16,17,18,19], 17, 'Yes! 17!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 18 Whistles', 'Count the whistles!', act_json('mango_counting', ['min'=>18,'max'=>18,'object'=>'whistle','difficulty'=>1], 'Count the whistles!', 'Count to 18.', 'Count all the whistles — there are 18!', [18], 18, 'Eighteen whistles!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Match 17 and 18', 'Find the group with the right number.', act_json('match_quantity', ['min'=>17,'max'=>18,'object'=>'watermelon','target'=>17,'difficulty'=>1], 'Find 17 watermelons!', 'Match quantity to number.', 'Which group has 17 watermelons?', [17,18], 17, 'Correct! 17 watermelons!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find 17 and 18', 'Tap the correct number.', act_json('number_identification', ['min'=>15,'max'=>19,'poolSize'=>5,'difficulty'=>1], 'Find number 18!', 'Identify 18.', 'Which number is 18?', [15,16,17,18,19], 18, 'Yes! 18!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Counting Game', 'Count objects in the game!', act_json('math_game', ['difficulty'=>1,'min'=>17,'max'=>18,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Count to 18!', 'Game: count objects.', 'Hop to number 18!', [], 18, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Quiz: 17 and 18', 'Show what you know!', act_json('number_identification', ['min'=>15,'max'=>19,'poolSize'=>5,'difficulty'=>1], 'Find 17!', 'Assess knowledge.', 'Which number is 17?', [15,16,17,18,19], 17, 'You know 17 and 18!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Numbers 17–18!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You learned 17 and 18!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: 19 and 20', 'Ready for more numbers!', act_json('number_identification', ['min'=>15,'max'=>19,'poolSize'=>5,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: 19 and 20!', 'Preview.', 'Ready for 19 and 20?', [], [], 'Great!', 'easy', 1)];

/* ===== L05: Numbers 19–20 ===== */
$c = 'NUM-04-L05';
$acts[] = [$c, 'intro', 0, 'Colour Number 19', 'Outline number 19 for colouring!', act_json('number_identification', ['min'=>17,'max'=>20,'poolSize'=>4,'interaction'=>'coloring','target_number'=>19,'difficulty'=>1], 'Colour the number 19!', 'Recognise 19.', 'Number 19 is 1 and 9. Tap to colour!', [17,18,19], 19, 'Yes! 19 is one and nine!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 19 Papayas', 'Count all the papayas!', act_json('mango_counting', ['min'=>19,'max'=>19,'object'=>'papaya','difficulty'=>1], 'Count the papayas!', 'Count to 19.', 'Count all the papayas — there are 19!', [19], 19, 'Nineteen papayas!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Find Number 19', 'Which number is 19?', act_json('number_identification', ['min'=>17,'max'=>20,'poolSize'=>4,'target_number'=>19,'difficulty'=>1], 'Find number 19!', 'Identify 19.', 'Which number is 19? Tap it!', [17,18,19,20], 19, 'Yes! 19!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 20 Glasses', 'Count the glasses of water!', act_json('mango_counting', ['min'=>20,'max'=>20,'object'=>'glass','difficulty'=>1], 'Count the glasses!', 'Count to 20.', 'Count all the glasses of water — there are 20!', [20], 20, 'Twenty glasses!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Match 19 and 20', 'Find the group with the right number.', act_json('match_quantity', ['min'=>19,'max'=>20,'object'=>'papaya','target'=>19,'difficulty'=>1], 'Find 19 papayas!', 'Match quantity to number.', 'Which group has 19 papayas?', [19,20], 19, 'Correct! 19 papayas!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find 19 and 20', 'Tap the correct number.', act_json('number_identification', ['min'=>17,'max'=>20,'poolSize'=>4,'difficulty'=>1], 'Find number 20!', 'Identify 20.', 'Which number is 20?', [17,18,19,20], 20, 'Yes! 20!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Counting Game', 'Count objects in the game!', act_json('math_game', ['difficulty'=>1,'min'=>19,'max'=>20,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Count to 20!', 'Game: count objects.', 'Hop to number 20!', [], 20, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Quiz: 19 and 20', 'Show what you know!', act_json('number_identification', ['min'=>17,'max'=>20,'poolSize'=>4,'difficulty'=>1], 'Find 19!', 'Assess knowledge.', 'Which number is 19?', [17,18,19,20], 19, 'You know 19 and 20!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Numbers 19–20!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You learned 19 and 20!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: Read and Write 11–20', 'Ready to write numbers!', act_json('number_identification', ['min'=>17,'max'=>20,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: writing 11–20!', 'Preview.', 'Time to write all numbers 11 to 20!', [], [], 'Great!', 'easy', 1)];

/* ===== L06: Reading and Writing 11–20 ===== */
$c = 'NUM-04-L06';
$acts[] = [$c, 'intro', 0, 'Trace Number 11', 'Write number 11 with a marker.', act_json('number_identification', ['min'=>11,'max'=>11,'poolSize'=>1,'mode'=>'trace','target_number'=>11,'difficulty'=>1], 'Write number 11!', 'Practise writing 11.', 'Trace number 11 — two ones side by side!', [11], 11, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Trace Number 12', 'Write number 12 with a marker.', act_json('number_identification', ['min'=>12,'max'=>12,'poolSize'=>1,'mode'=>'trace','target_number'=>12,'difficulty'=>1], 'Write number 12!', 'Practise writing 12.', 'Trace number 12 — one and two!', [12], 12, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Trace Number 13', 'Write number 13 with a marker.', act_json('number_identification', ['min'=>13,'max'=>13,'poolSize'=>1,'mode'=>'trace','target_number'=>13,'difficulty'=>1], 'Write number 13!', 'Practise writing 13.', 'Trace number 13 — one and three!', [13], 13, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Trace Number 14', 'Write number 14 with a marker.', act_json('number_identification', ['min'=>14,'max'=>14,'poolSize'=>1,'mode'=>'trace','target_number'=>14,'difficulty'=>1], 'Write number 14!', 'Practise writing 14.', 'Trace number 14 — one and four!', [14], 14, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Trace Number 15', 'Write number 15 with a marker.', act_json('number_identification', ['min'=>15,'max'=>15,'poolSize'=>1,'mode'=>'trace','target_number'=>15,'difficulty'=>2], 'Write number 15!', 'Practise writing 15.', 'Trace number 15 — one and five!', [15], 15, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Find and Write 16', 'Find number 16, then write it.', act_json('number_identification', ['min'=>16,'max'=>16,'poolSize'=>1,'mode'=>'trace','target_number'=>16,'difficulty'=>2], 'Write number 16!', 'Practise writing 16.', 'Trace number 16 — one and six!', [16], 16, 'Well written!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Trace Numbers Game', 'Write numbers in the game!', act_json('math_game', ['difficulty'=>1,'min'=>11,'max'=>15,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Trace numbers!', 'Game: write and find.', 'Hop to the next number!', [], 15, 'Found it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Trace Numbers 17–20', 'Write numbers 17, 18, 19, 20.', act_json('number_identification', ['min'=>17,'max'=>17,'poolSize'=>1,'mode'=>'trace','target_number'=>17,'difficulty'=>2], 'Write number 17!', 'Assess writing.', 'Trace number 17 — one and seven!', [17], 17, 'Well written!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed Reading and Writing 11–20!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You can write numbers 11 to 20!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: Snake and Ladder', 'Ready for the number game!', act_json('math_game', ['difficulty'=>1,'min'=>11,'max'=>20,'step_type'=>'next_steps','skip_finish'=>true], 'Next: Snake and Ladder!', 'Preview.', 'Time to play Snake and Ladder!', [], [], 'Great!', 'easy', 1)];

/* ===== L07: Number Game (Snake and Ladder) ===== */
$c = 'NUM-04-L07';
$acts[] = [$c, 'intro', 0, 'Snake and Ladder!', 'Roll the dice and count to 20!', act_json('math_game', ['difficulty'=>1,'min'=>1,'max'=>20,'game_type'=>'snake_ladder','skip_finish'=>false], 'Snake and Ladder!', 'Game: count 1 to 20.', 'Roll the dice and climb the ladder!', [], 1, 'Let us play!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Count 1 to 10', 'Start counting from 1!', act_json('number_sequencing', ['min'=>1,'max'=>10,'difficulty'=>1], 'Put numbers 1 to 10 in order!', 'Sequence 1–10.', 'Drag the numbers into the right order!', [1,2,3,4,5,6,7,8,9,10], '1,2,3...10', 'Great sequencing!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Count 11 to 15', 'Now count from 11 to 15!', act_json('number_sequencing', ['min'=>11,'max'=>15,'difficulty'=>1], 'Put numbers 11 to 15 in order!', 'Sequence 11–15.', 'Drag the numbers into the right order!', [11,12,13,14,15], '11,12,13,14,15', 'Great sequencing!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Count 16 to 20', 'Count from 16 to 20!', act_json('number_sequencing', ['min'=>16,'max'=>20,'difficulty'=>1], 'Put numbers 16 to 20 in order!', 'Sequence 16–20.', 'Drag the numbers into the right order!', [16,17,18,19,20], '16,17,18,19,20', 'Great sequencing!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Missing Numbers', 'What number is missing?', act_json('missing_numbers', ['min'=>11,'max'=>20,'difficulty'=>1], 'Find the missing number!', 'Identify gaps.', 'Which number is missing from the line?', [11,12,13,14,15], 13, 'You found it!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Snake Ladder Round', 'Play a round of Snake and Ladder!', act_json('math_game', ['difficulty'=>1,'min'=>1,'max'=>20,'game_type'=>'snake_ladder','skip_finish'=>false], 'Play Snake and Ladder!', 'Game: count and climb.', 'Roll the dice and move!', [], 10, 'Fun!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Number Hopscotch', 'Hop through numbers 1 to 20!', act_json('math_game', ['difficulty'=>1,'min'=>1,'max'=>20,'game_type'=>'number_hopscotch','skip_finish'=>false], 'Hop to 20!', 'Game: number hopscotch.', 'Jump to each number!', [], 20, 'You made it!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Game Quiz', 'Show your counting skills!', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'difficulty'=>2], 'Find number 18!', 'Assess knowledge.', 'Which number is 18?', [16,17,18,19,20], 18, 'You know your numbers!', 'easy', 3)];
$acts[] = [$c, 'reward', 8, 'Great Work!', 'You completed the Number Game!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'You earned a star!', 'Celebrate.', 'You played the number game!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Next: Assessment', 'Ready for the final test!', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1], 'Next: assessment!', 'Preview.', 'Show everything you know about 11–20!', [], [], 'Great!', 'easy', 1)];

/* ===== L08: Assessment and Review ===== */
$c = 'NUM-04-L08';
$acts[] = [$c, 'intro', 0, 'Assessment: Find 11', 'Find number 11.', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'target_number'=>11,'difficulty'=>2], 'Find number 11!', 'Assess: recognise.', 'Which number is 11?', [11,12,13,14,15], 11, 'Correct!', 'easy', 2)];
$acts[] = [$c, 'warmup', 1, 'Assessment: Count Cows', 'How many cows?', act_json('mango_counting', ['min'=>11,'max'=>11,'object'=>'cow','difficulty'=>2], 'Count the cows!', 'Assess: count.', 'Count the cows — how many?', [11], 11, 'Eleven cows!', 'easy', 2)];
$acts[] = [$c, 'i_do', 2, 'Assessment: Find 15', 'Find number 15.', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'target_number'=>15,'difficulty'=>2], 'Find number 15!', 'Assess: recognise.', 'Which number is 15?', [13,14,15,16,17], 15, 'Correct!', 'easy', 2)];
$acts[] = [$c, 'we_do', 3, 'Assessment: Count Bells', 'How many bells?', act_json('mango_counting', ['min'=>15,'max'=>15,'object'=>'bell','difficulty'=>2], 'Count the bells!', 'Assess: count.', 'Count the bells — how many?', [15], 15, 'Fifteen bells!', 'easy', 2)];
$acts[] = [$c, 'you_do', 4, 'Assessment: Write 12', 'Write number 12.', act_json('number_identification', ['min'=>12,'max'=>12,'poolSize'=>1,'mode'=>'trace','target_number'=>12,'difficulty'=>2], 'Write number 12!', 'Assess: write.', 'Trace number 12 — one and two!', [12], 12, 'Well written!', 'easy', 2)];
$acts[] = [$c, 'check', 5, 'Assessment: Find 20', 'Find number 20.', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'target_number'=>20,'difficulty'=>2], 'Find number 20!', 'Assess: recognise.', 'Which number is 20?', [18,19,20], 20, 'Correct!', 'easy', 1)];
$acts[] = [$c, 'game', 6, 'Assessment: Sequence', 'Put numbers in order.', act_json('number_sequencing', ['min'=>11,'max'=>15,'difficulty'=>2], 'Arrange 11 to 15!', 'Assess: sequence.', 'Put numbers 11 to 15 in order!', [11,12,13,14,15], '11,12,13,14,15', 'Perfect order!', 'easy', 3)];
$acts[] = [$c, 'assessment', 7, 'Final Quiz: 11–20', 'The final test!', act_json('number_identification', ['min'=>11,'max'=>20,'poolSize'=>5,'difficulty'=>3], 'Final quiz!', 'Final assessment.', 'Find number 17.', [15,16,17,18,19], 17, 'You mastered 11–20!', 'easy', 5)];
$acts[] = [$c, 'reward', 8, 'Congratulations!', 'You completed Numbers 11–20!', act_json('math_game', ['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true], 'Congratulations!', 'Celebrate.', 'You learned numbers 11 to 20!', [], [], 'Amazing!', 'easy', 1)];
$acts[] = [$c, 'next_steps', 9, 'Ready for Addition!', 'You are ready for addition!', act_json('math_game', ['difficulty'=>1,'min'=>1,'max'=>20,'step_type'=>'next_steps','skip_finish'=>true], 'What is next?', 'Preview next topic.', 'You know 11–20! Ready for addition!', [], [], 'Amazing!', 'easy', 1)];

echo "Defined " . count($acts) . " activities.\n\n";

/* ----------------------------------------------------------------
   STEP 4: Insert activities into existing lessons
   Do NOT insert lessons or topics — only activities
   ---------------------------------------------------------------- */
$inserted = 0;
$errors = 0;
$skipped = 0;

foreach ($acts as [$lesson_code, $step_type, $step_order, $name, $desc, $json]) {
    $lid = $L[$lesson_code]['lesson_id'] ?? null;
    if (!$lid) {
        echo "ERROR: No lesson_id for $lesson_code\n";
        $errors++;
        continue;
    }

    $data = json_decode($json, true);
    if (!$data || !isset($data['engine'])) {
        echo "ERROR: Invalid JSON for $name\n";
        $errors++;
        continue;
    }

    $diff = $data['difficulty'] <= 1 ? 'easy' : ($data['difficulty'] <= 2 ? 'medium' : 'hard');
    $instruction_text = $data['instruction'] ?? '';

    /* Check if activity already exists for this lesson+step_type+step_order */
    $existing = $database->fetchOne(
        "SELECT activity_id FROM activities WHERE lesson_id = ? AND step_type = ? AND step_order = ?",
        [$lid, $step_type, $step_order]
    );

    if ($existing) {
        /* Update existing */
        $database->execute(
            "UPDATE activities SET activity_name=?, activity_description=?, difficulty_level=?, activity_data=?, audio_instruction=? WHERE activity_id=?",
            [$name, $desc, $diff, $json, $instruction_text, $existing['activity_id']]
        );
        $updated++;
        echo "  UPDATE: $lesson_code $step_type $step_order — $name\n";
    } else {
        /* Insert new */
        $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction)
             VALUES (?, ?, ?, ?, ?, ?, 'counting', ?, ?, ?)",
            [$module_id, $lid, $step_type, $step_order, $name, $desc, $diff, $json, $instruction_text]
        );
        $inserted++;
        echo "  INSERT: $lesson_code $step_type $step_order — $name\n";
    }
}

echo "\nResults: $inserted inserted, $updated (if any) updated, $errors errors\n";

/* ----------------------------------------------------------------
   STEP 5: Verification — count activities per lesson
   ---------------------------------------------------------------- */
echo "\n=== VERIFICATION ===\n";
$perLesson = $database->fetchAll(
    "SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_code LIKE 'NUM-04-%'
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
echo ($total === 80) ? "\n✓ ALL 80 ACTIVITIES POPULATED SUCCESSFULLY\n" : "\n✗ Expected 80, got $total\n";
echo "</pre>";
