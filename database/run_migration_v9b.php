<?php
/**
 * Part B: Activity data definitions for all 80 activities
 * Content matches the Foundation Numbers workbook exercises.
 *
 * Included by run_migration_v9_fix.php or run_migration_v9_main.php
 * Expects: $db (Database), $L (lesson_code => lesson_id map)
 */

function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
  return json_encode(array_merge([
    'engine'=>$engine,'instruction'=>$instruction,'objective'=>$objective,'content'=>$content,
    'choices'=>$choices,'answer'=>$answer,'feedback'=>$feedback,'difficulty'=>$difficulty,'estimated_time'=>$time
  ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$acts = [];

// ── L01: Numbers 1, 2, 3 ──────────────────────────────────────
$c='NUM-01-L01';
$acts[]=[$c,'intro',0,'Intro: Meet Numbers 1, 2, 3','Say hello to numbers 1, 2, and 3!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Say hello to 1, 2, 3!','Introduce numbers 1, 2, 3.','Numbers 1, 2, 3 — one star, two stars, three stars!',[],[],'Great! Let us learn 1, 2, 3!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Colour Number 1','Outline number 1 to colour!',act_json('number_identification',['min'=>1,'max'=>1,'poolSize'=>3,'difficulty'=>1],'Colour the number 1!','Recognise shape of 1.','Number 1 looks like a stick. Find and colour it!',[1,2,3],1,'Yes! Number 1 like a stick!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 1, 2, 3 Pencils','Watch me count pencils!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'pencil','mode'=>'demo','skip_finish'=>true,'difficulty'=>1],'Watch: one pencil, two pencils, three pencils!','Demonstrate counting 1-3.','Three pencils: one, two, three!',[],[],'Watch me!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 1 Ruler','Tap and count 1 ruler with me!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'ruler','mode'=>'count','difficulty'=>1],'Count 1 ruler together!','Practise counting 1.','One ruler — tap and say "one"!',[1,2,3],1,'Yes, 1 ruler!','easy',2)];
$acts[]=[$c,'you_do',4,'You Do: Count 2 Books','Count 2 books!',act_json('mango_counting',['min'=>2,'max'=>2,'object'=>'book','mode'=>'count','difficulty'=>1],'Count 2 books!','Count 2 independently.','Two books: one, two!',[1,2,3],2,'Super! 2 books!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 2','Tap number 2.',act_json('number_identification',['min'=>1,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 2!','Identify 2.','Which number is 2?',[1,2,3],2,'Yes! 2 like a swan!','easy',1)];
$acts[]=[$c,'game',6,'Game: Number Hunt 1-2-3','Find 1, 2, and 3!',act_json('number_identification',['min'=>1,'max'=>3,'mode'=>'hunt','poolSize'=>3,'difficulty'=>1],'Find 1, 2, 3!','Recognise numbers in a game.','Numbers are hiding — find 1, 2, 3!',[1,2,3],3,'All found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: Numbers 1-2-3','Show what you know!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about 1, 2, 3?','Assess numbers 1-3.','Count and choose the right number.',[1,2,3],3,'You know 1, 2, 3!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate learning.','You learned 1, 2, 3!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 4-5','Ready for 4 and 5!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Ready for 4, 5!','Preview next lesson.','Next: 4 and 5!',[],[],'Great!','easy',1)];

// ── L02: Numbers 4, 5 ──────────────────────────────────────────
$c='NUM-01-L02';
$acts[]=[$c,'intro',0,'Intro: Meet Numbers 4 and 5','Say hello to 4 and 5!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Hello 4 and 5!','Introduce numbers 4, 5.','Four stars, five stars!',[],[],'Learn 4 and 5!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Colour Number 4','Outline number 4 to colour!',act_json('number_identification',['min'=>4,'max'=>4,'poolSize'=>3,'difficulty'=>1],'Colour the number 4!','Recognise shape of 4.','4 looks like a flag. Find and colour it!',[3,4,5],4,'Yes! 4 like a flag!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 4 and 5 Desks','Watch me count desks!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'desk','mode'=>'demo','skip_finish'=>true,'difficulty'=>1],'Watch 4 and 5 desks!','Demonstrate counting 4-5.','Four desks, then five desks!',[],[],'Watch!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 3 Chairs','Count 3 chairs together!',act_json('mango_counting',['min'=>3,'max'=>3,'object'=>'chair','mode'=>'count','difficulty'=>1],'Count 3 chairs!','Practise counting 3.','Three chairs — tap and count!',[2,3,4],3,'Yes, 3 chairs!','easy',2)];
$acts[]=[$c,'you_do',4,'You Do: Count 4 Pencils','Count 4 pencils!',act_json('mango_counting',['min'=>4,'max'=>4,'object'=>'pencil','mode'=>'count','difficulty'=>1],'Count 4 pencils!','Count 4 independently.','Four pencils!',[3,4,5],4,'Brilliant!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 5','Tap number 5.',act_json('number_identification',['min'=>4,'max'=>5,'poolSize'=>3,'difficulty'=>1],'Find number 5!','Identify 5.','Which number is 5?',[4,5,6],5,'5 has a round tummy!','easy',1)];
$acts[]=[$c,'game',6,'Game: Number Hunt 4-5','Find 4 and 5!',act_json('number_identification',['min'=>4,'max'=>5,'mode'=>'hunt','poolSize'=>3,'difficulty'=>1],'Find 4, 5!','Recognise numbers in a game.','Find 4 and 5 hiding!',[4,5],5,'Both found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: Numbers 4-5','Show what you know!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about 4, 5?','Assess numbers 4-5.','Count and choose.',[4,5],5,'You know 4, 5!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate learning.','You learned 4, 5!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 6-7','Ready for 6 and 7!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for 6, 7!','Preview next lesson.','Next: 6 and 7!',[],[],'Great!','easy',1)];

// ── L03: Numbers 6, 7 ──────────────────────────────────────────
$c='NUM-01-L03';
$acts[]=[$c,'intro',0,'Intro: Meet Numbers 6 and 7','Hello 6 and 7!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Hello 6 and 7!','Introduce numbers 6, 7.','Six stars, seven stars!',[],[],'Learn 6 and 7!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count 6 Rabbits','Count 6 rabbits!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'rabbit','mode'=>'count','difficulty'=>2],'Count 6 rabbits!','Practise counting 6.','Six hopping rabbits!',[5,6,7],6,'Yes, 6 rabbits!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 6 and 7 Goats','Watch me count goats!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'goat','mode'=>'demo','skip_finish'=>true,'difficulty'=>2],'Watch 6 and 7 goats!','Demonstrate counting 6-7.','Six goats, seven goats!',[],[],'Watch!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 7 Ducks','Count 7 ducks together!',act_json('mango_counting',['min'=>7,'max'=>7,'object'=>'duck','mode'=>'count','difficulty'=>2],'Count 7 ducks!','Practise counting 7.','Seven ducks swimming!',[6,7,8],7,'Super, 7 ducks!','easy',3)];
$acts[]=[$c,'you_do',4,'You Do: Count 6 Fish','Count 6 fish!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'fish','mode'=>'count','difficulty'=>2],'Count 6 fish!','Count 6 independently.','Six fish!',[5,6,7],6,'Fantastic!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 7','Tap number 7.',act_json('number_identification',['min'=>6,'max'=>7,'poolSize'=>3,'difficulty'=>2],'Find number 7!','Identify 7.','Which number is 7?',[6,7,8],7,'7 like a boomerang!','easy',1)];
$acts[]=[$c,'game',6,'Game: Number Hunt 6-7','Find 6 and 7!',act_json('number_identification',['min'=>6,'max'=>7,'mode'=>'hunt','poolSize'=>4,'difficulty'=>2],'Find 6, 7!','Recognise numbers in a game.','Numbers hiding — find 6, 7!',[6,7],7,'Great!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: Numbers 6-7','Show what you know!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'quiz','difficulty'=>2],'What about 6, 7?','Assess numbers 6-7.','Count and choose.',[6,7],7,'You know 6, 7!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate learning.','You learned 6, 7!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 8-9','Ready for 8 and 9!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for 8, 9!','Preview next lesson.','Next: 8 and 9!',[],[],'Great!','easy',1)];

// ── L04: Numbers 8, 9 ──────────────────────────────────────────
$c='NUM-01-L04';
$acts[]=[$c,'intro',0,'Intro: Meet Numbers 8 and 9','Hello 8 and 9!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Hello 8 and 9!','Introduce numbers 8, 9.','Eight stars, nine stars!',[],[],'Learn 8 and 9!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count 8 Erasers','Count 8 erasers!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'eraser','mode'=>'count','difficulty'=>2],'Count 8 erasers!','Practise counting 8.','Eight erasers on the desk!',[7,8,9],8,'Yes, 8 erasers!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 8 and 9 Birds','Watch me count birds!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'bird','mode'=>'demo','skip_finish'=>true,'difficulty'=>2],'Watch 8 and 9 birds!','Demonstrate counting 8-9.','Eight birds, nine birds!',[],[],'Watch!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 9 Chickens','Count 9 chickens together!',act_json('mango_counting',['min'=>9,'max'=>9,'object'=>'chicken','mode'=>'count','difficulty'=>2],'Count 9 chickens!','Practise counting 9.','Nine chickens in the farm!',[8,9,10],9,'Super, 9 chickens!','easy',3)];
$acts[]=[$c,'you_do',4,'You Do: Count 8 Birds','Count 8 birds!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'bird','mode'=>'count','difficulty'=>2],'Count 8 birds!','Count 8 independently.','Eight birds!',[7,8,9],8,'Fantastic!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 9','Tap number 9.',act_json('number_identification',['min'=>8,'max'=>9,'poolSize'=>3,'difficulty'=>2],'Find number 9!','Identify 9.','Which number is 9?',[8,9,10],9,'9 like a balloon!','easy',1)];
$acts[]=[$c,'game',6,'Game: Number Hunt 8-9','Find 8 and 9!',act_json('number_identification',['min'=>8,'max'=>9,'mode'=>'hunt','poolSize'=>4,'difficulty'=>2],'Find 8, 9!','Recognise numbers in a game.','Find 8 and 9 hiding!',[8,9],9,'Both found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: Numbers 8-9','Show what you know!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>2],'What about 8, 9?','Assess numbers 8-9.','Count and choose.',[8,9],9,'You know 8, 9!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate learning.','You learned 8, 9!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Count 1-9','Ready to count all numbers!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready to count 1-9!','Preview next lesson.','Count all 1-9 next!',[],[],'Great!','easy',1)];

// ── L05: Counting 1-9 ──────────────────────────────────────────
$c='NUM-01-L05';
$acts[]=[$c,'intro',0,'Intro: Count All 1 to 9','Count from 1 to 9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Count 1 to 9!','Introduce full counting range.','1, 2, 3, 4, 5, 6, 7, 8, 9!',[],[],'Count all!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Pattern Counting','Count 1 fly, 2 butterflies, 3 birds!',act_json('mango_counting',['min'=>1,'max'=>5,'object'=>'butterfly','mode'=>'count','difficulty'=>1],'Pattern: 1, 2, 3, 4, 5!','Recognise increasing quantity.','Butterflies: one, two, three, four, five!',[1,2,3,4,5],5,'Pattern done!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count Objects 1-9','Watch me count 1 to 9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Watch counting 1 to 9!','Demonstrate full count.','Nine candies: 1, 2, 3... 9!',[],[],'Watch!','medium',3)];
$acts[]=[$c,'we_do',3,'We Do: Match Quantity 1-9','Match groups to numbers.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'apple','difficulty'=>3],'Match groups to numbers!','Guided matching quantity to number.','Match the number to the group of apples.',[1,2,3,4,5,6,7,8,9],5,'Correct match!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Count and Match','Count and match alone!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'book','difficulty'=>3],'Count and match alone!','Independent matching.','Find the group with the right number of books.',[1,2,3,4,5,6,7,8,9],7,'Wonderful!','medium',3)];
$acts[]=[$c,'check',5,'Check: Sequence 1-9','Put numbers 1-9 in order.',act_json('number_sequencing',['min'=>1,'max'=>9,'difficulty'=>3],'Order numbers 1 to 9!','Sequence numbers correctly.','Drag numbers into the right order.',[1,2,3,4,5,6,7,8,9],9,'Perfect order!','medium',3)];
$acts[]=[$c,'game',6,'Game: Dot-to-Dot 1 to 9','Connect 1 to 9!',act_json('dot_to_dot',['min'=>1,'max'=>9,'difficulty'=>3],'Connect dots 1 to 9!','Reinforce number sequence.','Connect dots from 1 to 9 — it makes a picture!',[1,2,3,4,5,6,7,8,9],9,'You made a picture!','medium',4)];
$acts[]=[$c,'assessment',7,'Quiz: Count 1-9','Show your counting skills.',act_json('mango_counting',['min'=>3,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>3],'Show what you know!','Assess counting 1-9.','Count objects and choose the right number.',[1,2,3,4,5,6,7,8,9],7,'You count 1-9!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Counting Champ!','Celebrate counting!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Counting champ!','Celebrate achievement.','You counted 1 to 9!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Compare Numbers','Learn more and less!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: compare!','Preview next lesson.','More and less coming!',[],[],'Ready!','medium',1)];

// ── L06: Comparing Numbers 1-9 ─────────────────────────────────
$c='NUM-01-L06';
$acts[]=[$c,'intro',0,'Intro: More and Less','Learn to compare numbers!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Which has more?','Introduce comparing.','MORE vs LESS!',[],[],'Compare!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count Two Groups','Count both groups.',act_json('mango_counting',['min'=>1,'max'=>5,'object'=>'apple','mode'=>'count','difficulty'=>2],'Count two groups!','Review counting before comparing.','Count Group A and Group B.',[3,5],5,'Good counting!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Watch Compare 3 vs 5','5 is MORE than 3!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Watch: 3 vs 5!','Demonstrate comparing.','5 is MORE than 3!',[],[],'Watch!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Which Group Has More?','Compare candies together.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'compare','difficulty'=>3],'Which has more candies?','Guided comparison.','Look at the groups — which has more candies?',[],[],'Bigger group wins!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Tap the Bigger Group','Find the bigger group!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'cookie','mode'=>'compare','difficulty'=>3],'Find the bigger group!','Compare independently.','Which plate has more cookies?',[],[],'You found more!','medium',3)];
$acts[]=[$c,'check',5,'Check: Find Equal Groups','Find the groups that are the same.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'apple','mode'=>'equal','difficulty'=>3],'Find equal groups!','Identify equal quantities.','Which two groups have the same number?',[],[],'Equal!','medium',3)];
$acts[]=[$c,'game',6,'Game: More or Less','Quick compare!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'compare','difficulty'=>3],'More or less? Quick!','Compare in a game.','Which has MORE? Tap quickly!',[],[],'Champion!','medium',3)];
$acts[]=[$c,'assessment',7,'Quiz: Compare Numbers','Show comparison skills.',act_json('mango_counting',['min'=>3,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>3],'Compare quiz!','Assess comparing.','Count and compare the two groups.',[3,5,7,9],9,'You compare well!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Compare Star!','Celebrate!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Compare star!','Celebrate achievement.','You learned MORE and LESS!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Missing Numbers','Find hidden numbers!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: missing numbers!','Preview next lesson.','Which number hides?',[],[],'Ready!','medium',1)];

// ── L07: Missing Numbers 1-9 ───────────────────────────────────
$c='NUM-01-L07';
$acts[]=[$c,'intro',0,'Intro: Missing Numbers','Find the hidden number!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Find the missing number!','Introduce missing numbers.','A number hid from the line!',[],[],'Detective time!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Order 1 to 9','Put numbers in order.',act_json('number_sequencing',['min'=>1,'max'=>9,'difficulty'=>3],'Order numbers 1 to 9!','Review sequence before finding missing.','Place numbers 1 through 9 in order.',[1,2,3,4,5,6,7,8,9],9,'Perfect ordering!','medium',3)];
$acts[]=[$c,'i_do',2,'I Do: Find Missing Number','1, 2, _, 4 — 3 is missing!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Watch me find the missing number!','Demonstrate finding missing.','1, 2, _, 4 — the missing number is 3!',[1,2,3,4,5,6,7,8,9],3,'3 comes after 2, before 4!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Missing Together','3, 4, _, 6 — find it together!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Find the missing number!','Guided practice.','3, 4, _, 6 — what number is missing?',[3,4,5,6],5,'Yes, 5!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Find Alone','Find the missing number!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Find the missing number alone!','Independent practice.','Which number is missing from the line?',[1,2,3,4,5,6,7,8,9],6,'Super detective!','medium',3)];
$acts[]=[$c,'check',5,'Check: Fill Multiple Gaps','Fill in: 1, _, 3, _, 5, _, 7, _, 9',act_json('missing_numbers',['min'=>1,'max'=>9,'mode'=>'multiple','difficulty'=>4],'Fill all the gaps!','Multiple missing numbers.','Find 2, 4, 6, and 8 to complete the line!',[2,4,6,8],8,'All gaps filled!','medium',3)];
$acts[]=[$c,'game',6,'Game: Dot Surprise','Connect dots 1 to 9 for a surprise!',act_json('dot_to_dot',['min'=>1,'max'=>9,'difficulty'=>3],'Connect the dots!','Reinforce sequence.','Connect dots 1 to 9 — see the surprise picture!',[1,2,3,4,5,6,7,8,9],9,'Beautiful picture!','medium',4)];
$acts[]=[$c,'assessment',7,'Quiz: Missing Numbers','Show detective skills.',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>4],'Missing number quiz!','Assess missing numbers.','Which number is missing from the line?',[1,2,3,4,5,6,7,8,9],4,'Great detective!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Number Detective!','Celebrate!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Number detective!','Celebrate achievement.','You found all missing numbers!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Revision','Review all 1-9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: revision!','Preview next lesson.','Review everything!',[],[],'Ready!','medium',1)];

// ── L08: Revision Numbers 1-9 ──────────────────────────────────
$c='NUM-01-L08';
$acts[]=[$c,'intro',0,'Intro: Review All 1 to 9','Review everything!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Review numbers 1 to 9!','Introduce revision.','All numbers: 1, 2, 3, 4, 5, 6, 7, 8, 9!',[],[],'Revision time!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Quick Count','Quick count 1 to 9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'apple','mode'=>'count','difficulty'=>3],'Quick count 1 to 9!','Quick review.','Count apples: 1, 2, 3... 9 go!',[],[],'Great!','medium',2)];
$acts[]=[$c,'i_do',2,'I Do: Review All Numbers','1-2-3-4-5-6-7-8-9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Review all numbers!','Full review.','Say them with me: 1 through 9!',[],[],'Review!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Match Numbers to Objects','Match numbers to groups.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','difficulty'=>4],'Match numbers to candy groups!','Guided matching review.','Match the number to the right group of candies.',[1,2,3,4,5,6,7,8,9],6,'Perfect matching!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Match Alone','Count and match by yourself!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'cookie','difficulty'=>4],'Match alone!','Independent matching review.','Find the group with the right number of cookies.',[1,2,3,4,5,6,7,8,9],8,'Wonderful!','medium',3)];
$acts[]=[$c,'check',5,'Check: Find Any Number 1-9','Find the number I say!',act_json('number_identification',['min'=>1,'max'=>9,'poolSize'=>6,'difficulty'=>4],'Find the number!','Identify any number 1-9.','I say a number — find it and tap it!',[1,2,3,4,5,6,7,8,9],5,'You know all numbers!','medium',3)];
$acts[]=[$c,'game',6,'Game: Number Hunt Challenge','Find all numbers 1 to 9!',act_json('number_identification',['min'=>1,'max'=>9,'mode'=>'hunt','poolSize'=>9,'difficulty'=>4],'Find all numbers!','Recognise all numbers.','All numbers are hiding — find every one!',[1,2,3,4,5,6,7,8,9],9,'Number master!','medium',4)];
$acts[]=[$c,'assessment',7,'Final Quiz: Numbers 1-9','The big quiz!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>4],'Show everything you learned!','Assess all numbers 1-9.','Count objects and show all you know!',[1,2,3,4,5,6,7,8,9],9,'MASTERED 1-9!','medium',5)];
$acts[]=[$c,'reward',8,'Reward: Congratulations!','You completed Numbers 1-9!',act_json('math_game',['difficulty'=>4,'step_type'=>'reward','skip_finish'=>true],'Congratulations!','Celebrate completion.','You are a MATHS STAR! You know 1 through 9!',[],[],'Congratulations!','medium',2)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 10-20','Ready for bigger numbers!',act_json('mango_counting',['min'=>10,'max'=>20,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>4],'What is next?','Preview next topic.','Numbers 10-20 and shapes coming!',[],[],'Amazing!','medium',1)];


// ── Upsert all activities ──────────────────────────────────────
// This block only runs when the file is executed directly, not when included.
if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === 'run_migration_v9b.php' && isset($L)) {
  echo "=== Upsert ".count($acts)." activities ===\n";
  $cnt = 0;
  foreach ($acts as $a) {
    [$code, $st, $so, $name, $desc, $djson] = $a;
    $lid = $L[$code] ?? null;
    if (!$lid) { echo "Missing lesson $code\n"; continue; }
    $data = json_decode($djson, true);
    $diff = $data['difficulty'] ?? 'easy';
    $existing = $db->fetchOne("SELECT activity_id FROM activities WHERE lesson_id=? AND step_type=? AND step_order=?", [$lid, $st, $so]);
    if ($existing) {
      $db->execute("UPDATE activities SET activity_name=?, activity_description=?, activity_data=?, audio_instruction=?, difficulty_level=? WHERE activity_id=?",
        [$name, $desc, $djson, $desc, $diff, $existing['activity_id']]);
    } else {
      $db->execute("INSERT INTO activities (module_id,lesson_id,step_type,step_order,activity_name,activity_description,activity_type,difficulty_level,activity_data,audio_instruction) VALUES (14,?,?,?,?,?,'counting',?,?,?)",
        [$lid, $st, $so, $name, $desc, $diff, $djson, $desc]);
    }
    $cnt++;
  }
  echo "Processed $cnt activities.\n";
}
