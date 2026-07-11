<?php
/**
 * Part B: Activity data — 80 activities from the Foundation Numbers workbook.
 *
 * Content follows the curriculum spec exactly:
 *   10 categories × workbook objects, instructions, and engines.
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

/* ================================================================
   L01: Recognising Number 1
   Content: Colour number 1, count one object, trace 1, match one
   ================================================================ */
$c='NUM-01-L01';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 1','Outline number 1 for colouring!',act_json('number_identification',['min'=>1,'max'=>1,'poolSize'=>3,'interaction'=>'coloring','target_number'=>1,'difficulty'=>1],'Colour the number 1!','Recognise the shape of 1.','Number 1 looks like a stick. Tap to colour it!',[1,2,3],1,'Yes! Number 1 like a stick!','easy',2)];
// 2. Counting Objects
$acts[]=[$c,'warmup',1,'Count 1 Pencil','Count one pencil!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'pencil','mode'=>'count','difficulty'=>1],'Count the pencils!','Count one object.','One pencil — tap and say "one"!',[],1,'Yes, 1 pencil!','easy',2)];
// 3. Shapes of Numbers
$acts[]=[$c,'i_do',2,'Shape of Number 1','Number 1 looks like a stick!',act_json('number_identification',['min'=>1,'max'=>1,'poolSize'=>3,'shape_object'=>'stick','difficulty'=>1],'Find the object shaped like number 1!','Recognise shape of 1.','Number 1 looks like a stick. Find it!',[1,2,3],1,'1 like a stick!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match One Object','Match one rabbit to number 1.',act_json('match_quantity',['min'=>1,'max'=>1,'object'=>'rabbit','difficulty'=>1],'Find the group with 1 rabbit!','Match quantity to number.','One rabbit — which number matches?',[1,2,3],1,'Correct, 1 rabbit!','easy',2)];
// 5. Trace and Write Numbers
$acts[]=[$c,'you_do',4,'Trace Number 1','Trace and write number 1.',act_json('number_identification',['min'=>1,'max'=>1,'poolSize'=>3,'mode'=>'trace','target_number'=>1,'difficulty'=>1],'Trace number 1!','Practise writing 1.','Trace the number 1 with your finger.',[1],1,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 1','Tap number 1.',act_json('number_identification',['min'=>1,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 1!','Identify 1.','Which number is 1?',[1,2,3],1,'Yes! 1!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Number Game: Find 1','Find number 1 in the game!',act_json('math_game',['difficulty'=>1,'min'=>1,'max'=>1,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 1!','Game: recognise 1.','Hop to number 1!',[],1,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 1','Show what you know about 1!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about number 1?','Assess knowledge of 1.','Count the objects and choose 1.',[1],1,'You know number 1!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 1!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 1!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 2','Ready for number 2!',act_json('mango_counting',['min'=>2,'max'=>2,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Ready for number 2!','Preview.','Next: number 2!',[],[],'Great!','easy',1)];


/* ================================================================
   L02: Recognising Number 2
   Content: Colour number 2, count two objects, match quantity 2
   ================================================================ */
$c='NUM-01-L02';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 2','Outline number 2 for colouring!',act_json('number_identification',['min'=>2,'max'=>2,'poolSize'=>3,'interaction'=>'coloring','target_number'=>2,'difficulty'=>1],'Colour the number 2!','Recognise the shape of 2.','Number 2 looks like a duck. Tap to colour it!',[1,2,3],2,'Yes! 2 like a duck!','easy',2)];
// 2. Counting Objects
$acts[]=[$c,'warmup',1,'Count 2 Tables','Count two tables!',act_json('mango_counting',['min'=>2,'max'=>2,'object'=>'table','mode'=>'count','difficulty'=>1],'Count the tables!','Count two objects.','Two tables — tap and count one, two!',[],2,'Yes, 2 tables!','easy',2)];
// 3. Shapes of Numbers
$acts[]=[$c,'i_do',2,'Shape of Number 2','Number 2 looks like a duck!',act_json('number_identification',['min'=>2,'max'=>2,'poolSize'=>3,'shape_object'=>'duck','difficulty'=>1],'Find the object shaped like number 2!','Recognise shape of 2.','Number 2 looks like a duck. Find it!',[1,2,3],2,'2 like a duck!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match Two Objects','Match two tables to number 2.',act_json('match_quantity',['min'=>2,'max'=>2,'object'=>'table','difficulty'=>1],'Find the group with 2 tables!','Match quantity to number.','Two tables — which number matches?',[1,2,3],2,'Correct, 2 tables!','easy',2)];
// 5. Trace and Write Numbers
$acts[]=[$c,'you_do',4,'Trace Number 2','Trace and write number 2.',act_json('number_identification',['min'=>2,'max'=>2,'poolSize'=>3,'mode'=>'trace','target_number'=>2,'difficulty'=>1],'Trace number 2!','Practise writing 2.','Trace the number 2 with your finger.',[2],2,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 2','Tap number 2.',act_json('number_identification',['min'=>1,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 2!','Identify 2.','Which number is 2?',[1,2,3],2,'Yes! 2 like a swan!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Number Game: Find 2','Find number 2 in the game!',act_json('math_game',['difficulty'=>1,'min'=>2,'max'=>2,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 2!','Game: recognise 2.','Hop to number 2!',[],2,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 2','Show what you know about 2!',act_json('mango_counting',['min'=>2,'max'=>2,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about number 2?','Assess knowledge of 2.','Count the objects and choose 2.',[2],2,'You know number 2!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 2!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 2!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 3','Ready for number 3!',act_json('mango_counting',['min'=>3,'max'=>3,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Ready for number 3!','Preview.','Next: number 3!',[],[],'Great!','easy',1)];


/* ================================================================
   L03: Recognising Number 3
   Content: Number 3 shapes, count objects to 3
   ================================================================ */
$c='NUM-01-L03';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 3','Outline number 3 for colouring!',act_json('number_identification',['min'=>3,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>3,'difficulty'=>1],'Colour the number 3!','Recognise the shape of 3.','Number 3 looks like a butterfly. Tap to colour it!',[2,3,4],3,'Yes! 3 like a butterfly!','easy',2)];
// 2. Counting Objects
$acts[]=[$c,'warmup',1,'Count 3 Desks','Count three desks!',act_json('mango_counting',['min'=>3,'max'=>3,'object'=>'desk','mode'=>'count','difficulty'=>1],'Count the desks!','Count three objects.','Three desks — tap and count 1, 2, 3!',[],3,'Yes, 3 desks!','easy',2)];
// 3. Shapes of Numbers
$acts[]=[$c,'i_do',2,'Shape of Number 3','Number 3 looks like a butterfly!',act_json('number_identification',['min'=>3,'max'=>3,'poolSize'=>3,'shape_object'=>'butterfly','difficulty'=>1],'Find the object shaped like number 3!','Recognise shape of 3.','Number 3 looks like a butterfly. Find it!',[2,3,4],3,'3 like a butterfly!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match Three Objects','Match three chairs to number 3.',act_json('match_quantity',['min'=>3,'max'=>3,'object'=>'chair','difficulty'=>1],'Find the group with 3 chairs!','Match quantity to number.','Three chairs — which number matches?',[2,3,4],3,'Correct, 3 chairs!','easy',2)];
// 5. Trace and Write Numbers
$acts[]=[$c,'you_do',4,'Trace Number 3','Trace and write number 3.',act_json('number_identification',['min'=>3,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>3,'difficulty'=>1],'Trace number 3!','Practise writing 3.','Trace the number 3 with your finger.',[3],3,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 3','Tap number 3.',act_json('number_identification',['min'=>2,'max'=>4,'poolSize'=>3,'difficulty'=>1],'Find number 3!','Identify 3.','Which number is 3?',[2,3,4],3,'Yes! 3 like a butterfly!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Number Game: Find 3','Find number 3 in the game!',act_json('math_game',['difficulty'=>1,'min'=>3,'max'=>3,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 3!','Game: recognise 3.','Hop to number 3!',[],3,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 3','Show what you know about 3!',act_json('mango_counting',['min'=>3,'max'=>3,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about number 3?','Assess knowledge of 3.','Count the objects and choose 3.',[3],3,'You know number 3!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 3!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 3!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 4','Ready for number 4!',act_json('mango_counting',['min'=>4,'max'=>4,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Ready for number 4!','Preview.','Next: number 4!',[],[],'Great!','easy',1)];


/* ================================================================
   L04: Recognising Number 4
   Content: Counting objects to 4, matching numbers
   ================================================================ */
$c='NUM-01-L04';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 4','Outline number 4 for colouring!',act_json('number_identification',['min'=>4,'max'=>4,'poolSize'=>3,'interaction'=>'coloring','target_number'=>4,'difficulty'=>1],'Colour the number 4!','Recognise the shape of 4.','Number 4 looks like a flag. Tap to colour it!',[3,4,5],4,'Yes! 4 like a flag!','easy',2)];
// 2. Counting Objects
$acts[]=[$c,'warmup',1,'Count 4 Chairs','Count four chairs!',act_json('mango_counting',['min'=>4,'max'=>4,'object'=>'chair','mode'=>'count','difficulty'=>1],'Count the chairs!','Count four objects.','Four chairs — tap and count 1, 2, 3, 4!',[],4,'Yes, 4 chairs!','easy',2)];
// 3. Shapes of Numbers
$acts[]=[$c,'i_do',2,'Shape of Number 4','Number 4 looks like a boat!',act_json('number_identification',['min'=>4,'max'=>4,'poolSize'=>3,'shape_object'=>'boat','difficulty'=>1],'Find the object shaped like number 4!','Recognise shape of 4.','Number 4 looks like a boat. Find it!',[3,4,5],4,'4 like a boat!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match Four Objects','Match four boards to number 4.',act_json('match_quantity',['min'=>4,'max'=>4,'object'=>'board','difficulty'=>1],'Find the group with 4 boards!','Match quantity to number.','Four boards — which number matches?',[3,4,5],4,'Correct, 4 boards!','easy',2)];
// 5. Trace and Write Numbers
$acts[]=[$c,'you_do',4,'Trace Number 4','Trace and write number 4.',act_json('number_identification',['min'=>4,'max'=>4,'poolSize'=>3,'mode'=>'trace','target_number'=>4,'difficulty'=>1],'Trace number 4!','Practise writing 4.','Trace the number 4 with your finger.',[4],4,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 4','Tap number 4.',act_json('number_identification',['min'=>3,'max'=>5,'poolSize'=>3,'difficulty'=>1],'Find number 4!','Identify 4.','Which number is 4?',[3,4,5],4,'Yes! 4!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Number Game: Find 4','Find number 4 in the game!',act_json('math_game',['difficulty'=>1,'min'=>4,'max'=>4,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 4!','Game: recognise 4.','Hop to number 4!',[],4,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 4','Show what you know about 4!',act_json('mango_counting',['min'=>4,'max'=>4,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about number 4?','Assess knowledge of 4.','Count the objects and choose 4.',[4],4,'You know number 4!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 4!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 4!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 5','Ready for number 5!',act_json('mango_counting',['min'=>5,'max'=>5,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for number 5!','Preview.','Next: number 5!',[],[],'Great!','easy',1)];


/* ================================================================
   L05: Recognising Number 5
   Content: Butterfly/object counting, trace and write 5
   ================================================================ */
$c='NUM-01-L05';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 5','Outline number 5 for colouring!',act_json('number_identification',['min'=>5,'max'=>5,'poolSize'=>3,'interaction'=>'coloring','target_number'=>5,'difficulty'=>2],'Colour the number 5!','Recognise the shape of 5.','Number 5 has a round tummy. Tap to colour it!',[4,5,6],5,'Yes! 5 has a round tummy!','easy',2)];
// 2. Pattern Counting (1 fly, 2 butterflies, 3 birds, 4 mosquitoes, 5 bees)
$acts[]=[$c,'warmup',1,'Pattern: Count 1 to 5','Count the pattern: 1 fly, 2 butterflies, 3 birds, 4 mosquitoes, 5 bees!',act_json('mango_counting',['min'=>1,'max'=>5,'object'=>'butterfly','mode'=>'count','difficulty'=>2],'Count the pattern!','Recognise increasing quantity.','Pattern: 1 fly, 2 butterflies, 3 birds, 4 mosquitoes, 5 bees!',[],5,'Pattern complete!','easy',3)];
// 3. Count and Write Number
$acts[]=[$c,'i_do',2,'Count 5 Butterflies','Count five butterflies!',act_json('mango_counting',['min'=>5,'max'=>5,'object'=>'butterfly','mode'=>'count','difficulty'=>2],'Count the butterflies!','Count five objects.','Five butterflies — tap and count 1, 2, 3, 4, 5!',[],5,'Yes, 5 butterflies!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match Five Objects','Match five butterflies to number 5.',act_json('match_quantity',['min'=>5,'max'=>5,'object'=>'butterfly','difficulty'=>2],'Find the group with 5 butterflies!','Match quantity to number.','Five butterflies — which number matches?',[4,5,6],5,'Correct, 5 butterflies!','easy',2)];
// 5. Trace and Write Numbers
$acts[]=[$c,'you_do',4,'Trace Number 5','Trace and write number 5.',act_json('number_identification',['min'=>5,'max'=>5,'poolSize'=>3,'mode'=>'trace','target_number'=>5,'difficulty'=>2],'Trace number 5!','Practise writing 5.','Trace the number 5 with your finger.',[5],5,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 5','Tap number 5.',act_json('number_identification',['min'=>4,'max'=>6,'poolSize'=>3,'difficulty'=>2],'Find number 5!','Identify 5.','Which number is 5?',[4,5,6],5,'Yes! 5!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Number Game: Find 5','Find number 5 in the game!',act_json('math_game',['difficulty'=>2,'min'=>5,'max'=>5,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 5!','Game: recognise 5.','Hop to number 5!',[],5,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 5','Show what you know about 5!',act_json('mango_counting',['min'=>5,'max'=>5,'object'=>'butterfly','mode'=>'quiz','difficulty'=>2],'What about number 5?','Assess knowledge of 5.','Count the butterflies and choose 5.',[5],5,'You know number 5!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 5!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 5!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 6','Ready for number 6!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for number 6!','Preview.','Next: number 6!',[],[],'Great!','easy',1)];


/* ================================================================
   L06: Recognising Number 6
   Content: Animal counting (rabbits, goats, ducks), object matching
   ================================================================ */
$c='NUM-01-L06';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 6','Outline number 6 for colouring!',act_json('number_identification',['min'=>6,'max'=>6,'poolSize'=>3,'interaction'=>'coloring','target_number'=>6,'difficulty'=>2],'Colour the number 6!','Recognise the shape of 6.','Number 6 has a loop at the bottom. Tap to colour it!',[5,6,7],6,'Yes! 6 has a loop!','easy',2)];
// 2. Counting Animals
$acts[]=[$c,'warmup',1,'Count 6 Rabbits','Count six rabbits!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'rabbit','mode'=>'count','difficulty'=>2],'Count the rabbits!','Count six animals.','Six rabbits hopping — tap and count 1 to 6!',[],6,'Yes, 6 rabbits!','easy',2)];
// 3. Shapes of Numbers
$acts[]=[$c,'i_do',2,'Shape of Number 6','Number 6 looks like a spiral!',act_json('number_identification',['min'=>6,'max'=>6,'poolSize'=>3,'shape_object'=>'rabbit','difficulty'=>1],'Find the object shaped like number 6!','Recognise shape of 6.','Number 6 curls like a rabbit\'s tail. Find it!',[5,6,7],6,'6 curls like a spiral!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match 6 Goats','Match six goats to number 6.',act_json('match_quantity',['min'=>6,'max'=>6,'object'=>'goat','difficulty'=>2],'Find the group with 6 goats!','Match quantity to number.','Six goats — which number matches?',[5,6,7],6,'Correct, 6 goats!','easy',2)];
// 5. Counting Animals
$acts[]=[$c,'you_do',4,'Count 6 Ducks','Count six ducks!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'duck','mode'=>'count','difficulty'=>2],'Count the ducks!','Count six ducks independently.','Six ducks swimming — count them!',[],6,'Fantastic, 6 ducks!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 6','Tap number 6.',act_json('number_identification',['min'=>5,'max'=>7,'poolSize'=>3,'difficulty'=>2],'Find number 6!','Identify 6.','Which number is 6?',[5,6,7],6,'Yes! 6!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Animal Counting Game','Count the animals!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'rabbit','mode'=>'count','difficulty'=>2],'Count the rabbits!','Game: count animals.','How many rabbits? Count and choose!',[],6,'6 rabbits!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 6','Show what you know about 6!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'goat','mode'=>'quiz','difficulty'=>2],'What about number 6?','Assess knowledge of 6.','Count the goats and choose 6.',[6],6,'You know number 6!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Number 6!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 6!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Numbers 7 and 8','Ready for 7 and 8!',act_json('mango_counting',['min'=>7,'max'=>8,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for 7 and 8!','Preview.','Next: numbers 7 and 8!',[],[],'Great!','easy',1)];


/* ================================================================
   L07: Recognising Numbers 7 and 8
   Content: Books, erasers, animals for 7 and 8
   ================================================================ */
$c='NUM-01-L07';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Numbers 7 and 8','Outline numbers 7 and 8!',act_json('number_identification',['min'=>7,'max'=>8,'poolSize'=>4,'interaction'=>'coloring','target_number'=>7,'difficulty'=>2],'Colour numbers 7 and 8!','Recognise shapes of 7 and 8.','7 and 8 — tap to colour them!',[6,7,8,9],7,'Let us learn 7 and 8!','easy',2)];
// 2. Counting Objects (Books)
$acts[]=[$c,'warmup',1,'Count 7 Books','Count seven books!',act_json('mango_counting',['min'=>7,'max'=>7,'object'=>'book','mode'=>'count','difficulty'=>2],'Count the books!','Count seven objects.','Seven books on the shelf — tap and count 1 to 7!',[],7,'Yes, 7 books!','easy',2)];
// 3. Counting Objects (Erasers)
$acts[]=[$c,'i_do',2,'Count 8 Erasers','Count eight erasers!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'eraser','mode'=>'count','difficulty'=>2],'Count the erasers!','Count eight objects.','Eight erasers — tap and count 1 to 8!',[],8,'Yes, 8 erasers!','easy',2)];
// 4. Match Objects With Numbers (7 chickens)
$acts[]=[$c,'we_do',3,'Match 7 Chickens','Match seven chickens to number 7.',act_json('match_quantity',['min'=>7,'max'=>7,'object'=>'chicken','difficulty'=>2],'Find the group with 7 chickens!','Match quantity to number.','Seven chickens — which number matches?',[6,7,8],7,'Correct, 7 chickens!','easy',2)];
// 5. Count and Write Number (8 ducks)
$acts[]=[$c,'you_do',4,'Count 8 Ducks','Count eight ducks!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'duck','mode'=>'count','difficulty'=>2],'Count the ducks!','Count eight ducks independently.','Eight ducks swimming — count them!',[],8,'Fantastic, 8 ducks!','easy',2)];
// 6. Check (Find 7 or 8)
$acts[]=[$c,'check',5,'Find Number 7 or 8','Tap number 7 or 8.',act_json('number_identification',['min'=>7,'max'=>8,'poolSize'=>4,'difficulty'=>2],'Find number 7!','Identify 7 and 8.','Which number is 7?',[6,7,8,9],7,'Yes! 7!','easy',1)];
// 7. Number Games
$acts[]=[$c,'game',6,'Book and Eraser Game','Count books and erasers!',act_json('mango_counting',['min'=>7,'max'=>8,'object'=>'book','mode'=>'count','difficulty'=>2],'Count the books!','Game: count objects.','How many books? Count and choose!',[],7,'7 books!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Numbers 7 and 8','Show what you know!',act_json('mango_counting',['min'=>7,'max'=>8,'object'=>'star','mode'=>'quiz','difficulty'=>2],'What about 7 and 8?','Assess knowledge of 7 and 8.','Count the objects and choose.',[7,8],8,'You know 7 and 8!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed 7 and 8!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate.','You learned numbers 7 and 8!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Number 9','Ready for number 9!',act_json('mango_counting',['min'=>9,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for number 9!','Preview.','Next: number 9!',[],[],'Great!','easy',1)];


/* ================================================================
   L08: Recognising Number 9
   Content: Rabbits, missing numbers, number games
   ================================================================ */
$c='NUM-01-L08';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 9','Outline number 9 for colouring!',act_json('number_identification',['min'=>9,'max'=>9,'poolSize'=>3,'interaction'=>'coloring','target_number'=>9,'difficulty'=>2],'Colour the number 9!','Recognise the shape of 9.','Number 9 looks like a balloon. Tap to colour it!',[8,9,10],9,'Yes! 9 like a balloon!','easy',2)];
// 2. Counting Animals (Rabbits)
$acts[]=[$c,'warmup',1,'Count 9 Rabbits','Count nine rabbits!',act_json('mango_counting',['min'=>9,'max'=>9,'object'=>'rabbit','mode'=>'count','difficulty'=>2],'Count the rabbits!','Count nine animals.','Nine rabbits hopping — tap and count 1 to 9!',[],9,'Yes, 9 rabbits!','easy',2)];
// 3. Missing Number Exercises
$acts[]=[$c,'i_do',2,'Missing Number: 1,2,_,4,5','Find the missing number!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>2],'What number is missing?','Find missing number in sequence.','1, 2, _, 4, 5 — what is missing?',[3],3,'3 is missing!','easy',2)];
// 4. Match Objects With Numbers
$acts[]=[$c,'we_do',3,'Match 9 Objects','Match nine objects to number 9.',act_json('match_quantity',['min'=>9,'max'=>9,'object'=>'rabbit','difficulty'=>2],'Find the group with 9 rabbits!','Match quantity to number.','Nine rabbits — which number matches?',[8,9,10],9,'Correct, 9 rabbits!','easy',2)];
// 5. Number Games
$acts[]=[$c,'you_do',4,'Number Hopscotch','Play number hopscotch 1 to 9!',act_json('math_game',['difficulty'=>2,'min'=>1,'max'=>9,'game_type'=>'number_hopscotch','skip_finish'=>false],'Play hopscotch!','Number game.','Hop through numbers 1 to 9!',[],9,'You did it!','easy',3)];
// 6. Check (Find 9)
$acts[]=[$c,'check',5,'Find Number 9','Tap number 9.',act_json('number_identification',['min'=>8,'max'=>10,'poolSize'=>3,'difficulty'=>2],'Find number 9!','Identify 9.','Which number is 9?',[8,9,10],9,'Yes! 9 like a balloon!','easy',1)];
// 7. Missing Numbers Game
$acts[]=[$c,'game',6,'Missing Numbers Game','Find the missing numbers!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Which number hides?','Game: find missing.','1, 2, _, 4 — find the hidden number!',[],3,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Final Quiz: Number 9','Show what you know about 9!',act_json('mango_counting',['min'=>9,'max'=>9,'object'=>'rabbit','mode'=>'quiz','difficulty'=>2],'What about number 9?','Assess knowledge of 9.','Count the rabbits and choose 9.',[9],9,'You know number 9!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Congratulations!','You completed Number 9!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'Congratulations!','Celebrate.','You learned number 9! You know 1 to 9!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'What is Next?','Ready for more numbers!',act_json('mango_counting',['min'=>10,'max'=>20,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'What is next?','Preview next topic.','Numbers 10 to 20 coming next!',[],[],'Amazing!','easy',1)];


// ── Upsert all activities ──────────────────────────────────────
// Only runs when executed directly (not when require'd)
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
