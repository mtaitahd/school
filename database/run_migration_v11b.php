<?php
/**
 * Phase 6 — Migration B: 80 Activities for NUM-03 (Recognising Number 10)
 *
 * 8 lessons × 10 activities each
 * Engines: number_identification, match_quantity, mango_counting, math_game
 * All workbook content for Number 10
 *
 * "write" activities use mode='trace' (canvas marker) — cursor kwenye wino
 * Included by run_migration_v11_fix.php
 */

function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
  return json_encode(array_merge([
    'engine'=>$engine,'instruction'=>$instruction,'objective'=>$objective,'content'=>$content,
    'choices'=>$choices,'answer'=>$answer,'feedback'=>$feedback,'difficulty'=>$difficulty,'estimated_time'=>$time,
    'audio'=>['instruction'=>$instruction,'number_name'=>'ten','enabled'=>false],
    'visual'=>['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
  ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$acts = [];

/* ================================================================
   L01: Introducing Number 10
   Content: First sight of 10, recognise 10, 10 is one-ten
   ================================================================ */
$c='NUM-03-L01';
// 1. Colour Number 10
$acts[]=[$c,'intro',0,'Colour Number 10','Outline number 10 for colouring!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'interaction'=>'coloring','target_number'=>10,'difficulty'=>1],'Colour the number 10!','Recognise the shape of 10.','Number 10 has two digits: 1 and 0. Tap to colour it!',[7,8,9,10],10,'Yes! Number 10 is one and zero together!','easy',2)];
// 2. What is Number 10?
$acts[]=[$c,'warmup',1,'What is Number 10?','Number 10 means one-ten!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find number 10!','Understand 10 is one-ten.','Ten means one group of ten objects. Find the number 10!',[7,8,9,10],10,'Yes! 10 means one-ten!','easy',2)];
// 3. See Number 10
$acts[]=[$c,'i_do',2,'See Number 10','Look at the number 10 — a 1 and a 0!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Look at number 10!','Observe 10 closely.','Number 10 is written as 1 then 0. Find it!',[7,8,9,10],10,'10 is 1 and 0 together!','easy',2)];
// 4. Point to 10
$acts[]=[$c,'we_do',3,'Point to Number 10','Tap the number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Point to number 10!','Practise identifying 10.','Among 7, 8, 9, 10 — point to 10!',[7,8,9,10],10,'You pointed to 10!','easy',2)];
// 5. Trace Number 10
$acts[]=[$c,'you_do',4,'Trace Number 10','Trace the number 10.',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>1],'Trace number 10!','Practise writing 10.','Trace number 10 — first 1, then 0!',[10],10,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 10','Tap number 10.',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Identify 10.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',1)];
// 7. Number Game
$acts[]=[$c,'game',6,'Game: Find 10','Find number 10 in the game!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 10!','Game: recognise 10.','Hop to number 10!',[],10,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 10','Show what you know about 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'What about number 10?','Assess knowledge of 10.','Find number 10.',[7,8,9,10],10,'You know number 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Introducing Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Reading 10','Ready for reading number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: reading 10!','Preview.','Learn to read number 10!',[],[],'Great!','easy',1)];


/* ================================================================
   L02: Reading Number 10
   Content: Read 10 aloud, match spoken "ten" to symbol, audio
   ================================================================ */
$c='NUM-03-L02';
// 1. Colour Number 10
$acts[]=[$c,'intro',0,'Colour Number 10','Outline number 10 for colouring!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'interaction'=>'coloring','target_number'=>10,'difficulty'=>1],'Colour number 10!','Practise shape of 10.','Colour number 10 — the two-digit number!',[7,8,9,10],10,'Yes! Colour number 10!','easy',2)];
// 2. Say "Ten"
$acts[]=[$c,'warmup',1,'Say "Ten"!','Read the number 10 aloud!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Read number 10!','Read 10 aloud.','This number is TEN. Read it aloud!',[7,8,9,10],10,'Ten! Well read!','easy',2)];
// 3. Match Spoken "Ten" to Symbol
$acts[]=[$c,'i_do',2,'Match "Ten" to 10','The word "ten" matches the number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find the number TEN!','Match word to symbol.','When someone says "ten", they mean 10! Find it!',[7,8,9,10],10,'Yes! "Ten" is 10!','easy',2)];
// 4. Read and Find
$acts[]=[$c,'we_do',3,'Read and Find','Read the number and tap it!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Read: TEN — Find 10!','Practise reading.','Read "ten" then find the number 10!',[7,8,9,10],10,'You read and found 10!','easy',2)];
// 5. Write and Say
$acts[]=[$c,'you_do',4,'Write and Say 10','Write number 10 and say "ten"!',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>2],'Write number 10!','Write and read aloud.','Write 10 and say "ten" as you write!',[10],10,'Beautiful 10!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Read Number 10','Which one says "ten"?',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Identify 10.','Which number is TEN?',[7,8,9,10],10,'Yes! 10 is ten!','easy',1)];
// 7. Reading Game
$acts[]=[$c,'game',6,'Reading Game','Find number 10 when you hear "ten"!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find TEN!','Game: read and find.','Listen and hop to ten!',[],10,'Found ten!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Reading 10','Show you can read number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Read: find TEN!','Assess reading.','Which number is ten?',[7,8,9,10],10,'You can read 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Reading Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can read number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Writing 10','Ready for writing number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: writing 10!','Preview.','Time to write number 10 with a marker!',[],[],'Great!','easy',1)];


/* ================================================================
   L03: Writing Number 10
   Content: Write 10 with marker (canvas/trace), stroke order, practice
   All "write" activities use mode='trace' — cursor kwenye wino
   ================================================================ */
$c='NUM-03-L03';
// 1. Colour Number 10
$acts[]=[$c,'intro',0,'Colour Number 10','Outline number 10 for colouring!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'interaction'=>'coloring','target_number'=>10,'difficulty'=>1],'Colour number 10!','Practise shape before writing.','Colour number 10 — get ready to write it!',[7,8,9,10],10,'Yes! Colour 10!','easy',2)];
// 2. Trace with Marker
$acts[]=[$c,'warmup',1,'Trace with Marker','Use your finger like a marker to trace 10!',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>1],'Trace number 10 with a marker!','Practise marker stroke.','Drag your finger over the number 10 like a marker!',[10],10,'Great tracing with a marker!','easy',2)];
// 3. Write Number 10 (main)
$acts[]=[$c,'i_do',2,'Write Number 10','First write 1, then write 0 — that makes 10!',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>1],'Write number 10!','Write 10 with correct order.','Write 1 first, then 0. Together they make 10!',[10],10,'Beautiful 10!','easy',2)];
// 4. Write Again
$acts[]=[$c,'we_do',3,'Write 10 Again','Practice writing number 10.',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>2],'Write number 10 again!','Independent practice.','Write number 10 — first 1, then 0!',[10],10,'Well written!','easy',2)];
// 5. Write Freely
$acts[]=[$c,'you_do',4,'Write Number 10 Freely','Write number 10 on your own.',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>2],'Write number 10 freely!','Creative practice.','Write number 10 — 1 and 0 side by side!',[10],10,'Great writing!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 10','Tap number 10.',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Identify 10.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',1)];
// 7. Writing Game
$acts[]=[$c,'game',6,'Writing Game','Trace and write numbers!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Write numbers!','Game: write and find.','Write number 10 in the game!',[],10,'Great writer!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Writing 10','Show how well you write 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Write number 10!','Assess writing.','Can you find 10?',[7,8,9,10],10,'You can write 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Writing Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can write number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Counting 10','Ready to count ten objects!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: counting 10!','Preview.','Time to count ten objects!',[],[],'Great!','easy',1)];


/* ================================================================
   L04: Counting Groups of 10
   Content: Count ten fish, ten mangoes, ten school objects
   Engines: mango_counting, match_quantity
   ================================================================ */
$c='NUM-03-L04';
// 1. Count Ten Fish
$acts[]=[$c,'intro',0,'Count Ten Fish','Count all the fish!',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'fish','difficulty'=>1],'Count the fish!','Count exactly ten.','Count all the fish in the group!',[10],10,'You counted ten fish!','easy',2)];
// 2. Count Ten Mangoes
$acts[]=[$c,'warmup',1,'Count Ten Mangoes','Count all the mangoes!',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'mango','difficulty'=>1],'Count the mangoes!','Count to ten.','Count all the mangoes!',[10],10,'Ten mangoes!','easy',2)];
// 3. Count Ten Objects
$acts[]=[$c,'i_do',2,'Count Ten Objects','Count exactly ten objects.',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'book','difficulty'=>1],'Count ten books!','Count and stop at ten.','Count the books — stop at ten!',[10],10,'Ten books!','easy',2)];
// 4. Count and Choose
$acts[]=[$c,'we_do',3,'Count and Choose','How many? Choose the right number!',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'ruler','difficulty'=>1],'How many rulers?','Count and identify.','Count the rulers and choose the number!',[10],10,'Ten rulers!','easy',2)];
// 5. Count Ten Chairs
$acts[]=[$c,'you_do',4,'Count Ten Chairs','Count the chairs on your own.',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'chair','difficulty'=>2],'Count the chairs!','Independent counting.','Count the chairs — how many?',[10],10,'Ten chairs!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'How Many?','Count and find 10.',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'fish','target'=>10,'difficulty'=>1],'Find the group with 10 fish!','Match ten to number.','Which group has ten fish?',[10],10,'Yes! Ten fish!','easy',1)];
// 7. Counting Game
$acts[]=[$c,'game',6,'Counting Game','Count to ten in the game!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Count to ten!','Game: counting.','Count the objects and find 10!',[],10,'Ten!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Counting 10','Show you can count to ten!',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'butterfly','difficulty'=>2],'Count the butterflies!','Assess counting.','Count the butterflies!',[],10,'You counted ten!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Counting Groups of 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can count to ten!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Matching 10','Ready to match number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: matching 10!','Preview.','Match groups to number 10!',[],[],'Great!','easy',1)];


/* ================================================================
   L05: Matching Number 10
   Content: Match ten objects to number 10, circle all 10s
   Engines: match_quantity, number_identification
   ================================================================ */
$c='NUM-03-L05';
// 1. Match Ten Fish
$acts[]=[$c,'intro',0,'Match Ten Fish','Find the group with ten fish!',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'fish','target'=>10,'difficulty'=>1],'Find the group with 10 fish!','Match ten to number.','Which group has exactly ten fish?',[10],10,'Yes! That group has ten fish!','easy',2)];
// 2. Match Ten Mangoes
$acts[]=[$c,'warmup',1,'Match Ten Mangoes','Which group has ten mangoes?',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'mango','target'=>10,'difficulty'=>1],'Find the group with 10 mangoes!','Match ten mangoes.','Find the group that has exactly ten mangoes!',[10],10,'Ten mangoes!','easy',2)];
// 3. Circle Number 10
$acts[]=[$c,'i_do',2,'Circle Number 10','Find and tap all the number 10s!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find number 10!','Circle all 10s.','Look carefully — which number is 10? Tap it!',[7,8,9,10],10,'Yes! That is 10!','easy',2)];
// 4. Match Ten Books
$acts[]=[$c,'we_do',3,'Match Ten Books','Which group has ten books?',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'book','target'=>10,'difficulty'=>1],'Find 10 books!','Match ten books.','Find the group with exactly ten books!',[10],10,'Ten books!','easy',2)];
// 5. Match Ten Pencils
$acts[]=[$c,'you_do',4,'Match Ten Sticks','Find ten sticks on your own.',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'stick','target'=>10,'difficulty'=>2],'Find 10 sticks!','Independent matching.','Which group has exactly ten sticks?',[10],10,'Ten sticks!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find 10','Tap number 10.',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Identify 10.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',1)];
// 7. Matching Game
$acts[]=[$c,'game',6,'Matching Game','Match groups to numbers!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Match to 10!','Game: matching.','Find the group with ten items!',[],10,'Matched!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Matching 10','Show you can match number 10!',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'eraser','target'=>10,'difficulty'=>2],'Match to 10!','Assess matching.','Which group has ten erasers?',[10],10,'You matched 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Matching Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can match number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Finding 10','Ready to find number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: finding 10!','Preview.','Find number 10 among other numbers!',[],[],'Great!','easy',1)];


/* ================================================================
   L06: Finding Number 10
   Content: Find 10 among 7, 8, 9, 10, locate in number lines
   Engine: number_identification
   ================================================================ */
$c='NUM-03-L06';
// 1. Colour Number 10
$acts[]=[$c,'intro',0,'Colour Number 10','Outline number 10 for colouring!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'interaction'=>'coloring','target_number'=>10,'difficulty'=>1],'Colour number 10!','Practise identifying 10.','Colour number 10 — it has two digits!',[7,8,9,10],10,'Yes! Colour 10!','easy',2)];
// 2. Quick Find
$acts[]=[$c,'warmup',1,'Quick Find: 10!','Find number 10 quickly!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Quick! Find 10!','Fast identification.','Which number is 10? Tap it fast!',[7,8,9,10],10,'Fast find! 10!','easy',2)];
// 3. Find Among Distractors
$acts[]=[$c,'i_do',2,'Find 10 Among Numbers','Look carefully — 10 is hiding!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find 10 among them!','Identify 10 with distractors.','7, 8, 9, 10 — which one is 10?',[7,8,9,10],10,'Found 10!','easy',2)];
// 4. Find 10 on Line
$acts[]=[$c,'we_do',3,'Find 10 in the Line','Which number in the line is 10?',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find 10 in the line!','Locate 10.','Look at the number line — find 10!',[7,8,9,10],10,'10 is at the end!','easy',2)];
// 5. Independent Find
$acts[]=[$c,'you_do',4,'Find 10 on Your Own','Point to number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>2],'Find 10 independently!','Independent practice.','Find number 10 among 7, 8, 9, 10!',[7,8,9,10],10,'You found 10!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Final Find: 10','Tap number 10.',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Final check.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',1)];
// 7. Finding Game
$acts[]=[$c,'game',6,'Finding Game: 10','Find number 10 in the game!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 10!','Game: find 10.','Hop to number 10!',[],10,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Finding 10','Show you can find number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>2],'Find 10!','Assess finding.','Which number is 10?',[7,8,9,10],10,'You can find 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Finding Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can find number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Practice & Review','Ready to review all about 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: review 10!','Preview.','Let us practise everything about 10!',[],[],'Great!','easy',1)];


/* ================================================================
   L07: Practice and Review
   Content: Mixed review of all Number 10 skills
   Engines: all engines, mixed activities
   ================================================================ */
$c='NUM-03-L07';
// 1. Review: Find 10
$acts[]=[$c,'intro',0,'Review: Find 10','Find number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>1],'Find number 10!','Review identification.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',2)];
// 2. Review: Count Ten
$acts[]=[$c,'warmup',1,'Review: Count to Ten','Count the objects!',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'duck','difficulty'=>1],'Count the ducks!','Review counting.','Count all the ducks!',[],10,'Ten ducks!','easy',2)];
// 3. Review: Write 10
$acts[]=[$c,'i_do',2,'Review: Write 10','Write number 10 with a marker.',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>1],'Write number 10!','Review writing.','Trace number 10 — 1 then 0!',[10],10,'Well written!','easy',2)];
// 4. Review: Match 10
$acts[]=[$c,'we_do',3,'Review: Match 10','Find the group with ten objects.',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'butterfly','target'=>10,'difficulty'=>1],'Find 10 butterflies!','Review matching.','Which group has ten butterflies?',[10],10,'Ten butterflies!','easy',2)];
// 5. Review: Read 10
$acts[]=[$c,'you_do',4,'Review: Read 10','Read the number aloud!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>2],'Read: find TEN!','Review reading.','Read "ten" and find the number!',[7,8,9,10],10,'Ten!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Review Check','Show everything you know!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>1],'Find number 10!','Review assessment.','Which number is 10?',[7,8,9,10],10,'Yes! 10!','easy',1)];
// 7. Review Game
$acts[]=[$c,'game',6,'Review Game','Play and review number 10!',act_json('math_game',['difficulty'=>1,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Review 10!','Game: review all.','Hop to number 10!',[],10,'Reviewed!','easy',3)];
// 8. Mini Assessment
$acts[]=[$c,'assessment',7,'Mini Assessment: 10','Quick quiz on number 10!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>2],'Find 10!','Mini assessment.','Which number is 10?',[7,8,9,10],10,'You know 10!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Practice and Review!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You reviewed number 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Assessment','Ready for the final assessment!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: assessment!','Preview.','Show everything you know about 10!',[],[],'Great!','easy',1)];


/* ================================================================
   L08: Assessment
   Content: Comprehensive assessment of all Number 10 skills
   Engines: number_identification, match_quantity, mango_counting
   ================================================================ */
$c='NUM-03-L08';
// 1. Recognise 10
$acts[]=[$c,'intro',0,'Assessment: Recognise 10','Find number 10.',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>2],'Find number 10!','Assessment: recognise.','Which number is 10?',[7,8,9,10],10,'Correct!','easy',2)];
// 2. Read 10
$acts[]=[$c,'warmup',1,'Assessment: Read 10','Read the number and find it!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'target_number'=>10,'difficulty'=>2],'Read: TEN! Find it!','Assessment: read.','Read "ten" then find 10!',[7,8,9,10],10,'Correct!','easy',2)];
// 3. Write 10
$acts[]=[$c,'i_do',2,'Assessment: Write 10','Write number 10 with a marker.',act_json('number_identification',['min'=>10,'max'=>10,'poolSize'=>1,'mode'=>'trace','target_number'=>10,'difficulty'=>2],'Write number 10!','Assessment: write.','Write number 10 — show what you can do!',[10],10,'Well written!','easy',2)];
// 4. Count Ten
$acts[]=[$c,'we_do',3,'Assessment: Count Ten','Count the objects.',act_json('mango_counting',['min'=>10,'max'=>10,'object'=>'goat','difficulty'=>2],'Count the goats!','Assessment: count.','Count the goats — how many?',[10],10,'Ten goats!','easy',2)];
// 5. Match 10
$acts[]=[$c,'you_do',4,'Assessment: Match 10','Find the group with ten.',act_json('match_quantity',['min'=>10,'max'=>10,'object'=>'chicken','target'=>10,'difficulty'=>2],'Find 10 chickens!','Assessment: match.','Which group has ten chickens?',[10],10,'Ten chickens!','easy',2)];
// 6. Mixed Check
$acts[]=[$c,'check',5,'Assessment: Mixed','Show all your skills!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>2],'Find number 10!','Assessment: mixed.','Which number is 10?',[7,8,9,10],10,'Correct!','easy',1)];
// 7. Assessment Game
$acts[]=[$c,'game',6,'Assessment Game','Play to show what you know!',act_json('math_game',['difficulty'=>2,'min'=>7,'max'=>10,'game_type'=>'number_hopscotch','skip_finish'=>false],'Show 10!','Game: assessment.','Find number 10 in the game!',[],10,'You know 10!','easy',3)];
// 8. Final Assessment
$acts[]=[$c,'assessment',7,'Final Assessment: Number 10','The final test!',act_json('number_identification',['min'=>7,'max'=>10,'poolSize'=>4,'difficulty'=>3],'Final quiz: find 10!','Final assessment.','Find number 10 — last test!',[7,8,9,10],10,'You mastered number 10!','easy',5)];
// 9. Reward
$acts[]=[$c,'reward',8,'Congratulations!','You completed Recognising Number 10!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'Congratulations!','Celebrate.','You learned number 10! You know 10!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Ready for More!','You are ready for bigger numbers!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'What is next?','Preview next topic.','You know 10! Ready for numbers 11-20!',[],[],'Amazing!','easy',1)];
