<?php
/**
 * Phase 5 — Migration B: 40 Activities for NUM-02 (Recognising Number 0)
 *
 * 4 lessons × 10 activities each
 * Engines: number_identification, match_quantity, math_game
 * All workbook content for Number 0
 *
 * Included by run_migration_v10_fix.php
 * Expects: $db (Database), $L (lesson_code => lesson_id map)
 */

function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
  return json_encode(array_merge([
    'engine'=>$engine,'instruction'=>$instruction,'objective'=>$objective,'content'=>$content,
    'choices'=>$choices,'answer'=>$answer,'feedback'=>$feedback,'difficulty'=>$difficulty,'estimated_time'=>$time,
    'audio'=>['instruction'=>$instruction,'number_name'=>'zero','enabled'=>false],
    'visual'=>['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
  ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$acts = [];

/* ================================================================
   L01: Recognising Number 0
   Content: Colour 0, find 0, shape of 0, trace 0, match empty
   ================================================================ */
$c='NUM-02-L01';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 0','Outline number 0 for colouring!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>0,'difficulty'=>1],'Colour the number 0!','Recognise the shape of 0.','Number 0 is a big circle. Tap to colour it!',[0,1,2],0,'Yes! Number 0 is a circle!','easy',2)];
// 2. What is Zero?
$acts[]=[$c,'warmup',1,'What is Zero?','Zero means nothing — no objects at all!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'target_number'=>0,'difficulty'=>1],'Find number 0!','Understand zero means nothing.','Zero means no objects. Find the number 0!',[0,1,2],0,'Yes! 0 means nothing!','easy',2)];
// 3. Shape of Number 0
$acts[]=[$c,'i_do',2,'Shape of Number 0','Number 0 looks like an orange!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'shape_object'=>'orange','difficulty'=>1],'Find the object shaped like number 0!','Recognise shape of 0.','Number 0 is round like an orange. Find it!',[0,1,2],0,'0 looks like an orange!','easy',2)];
// 4. Match Empty Group
$acts[]=[$c,'we_do',3,'Match the Empty Group','Find the group with zero objects.',act_json('match_quantity',['min'=>0,'max'=>3,'object'=>'star','target'=>0,'difficulty'=>1],'Find the group with 0 stars!','Match zero to empty.','Which group has zero stars?',[],0,'Correct! That group is empty!','easy',2)];
// 5. Trace Number 0
$acts[]=[$c,'you_do',4,'Trace Number 0','Trace and write number 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>1],'Trace number 0!','Practise writing 0.','Trace the number 0 — it is one smooth circle!',[0],0,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 0','Tap number 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 0!','Identify 0.','Which number is 0?',[0,1,2,3],0,'Yes! 0!','easy',1)];
// 7. Number Game
$acts[]=[$c,'game',6,'Number Game: Find 0','Find number 0 in the game!',act_json('math_game',['difficulty'=>1,'min'=>0,'max'=>3,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 0!','Game: recognise 0.','Hop to number 0!',[],0,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Number 0','Show what you know about 0!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>4,'difficulty'=>1],'What about number 0?','Assess knowledge of 0.','Find number 0.',[0,1,2,3],0,'You know number 0!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Recognising Number 0!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You learned number 0!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Shape of 0','Ready for the shape of 0!',act_json('number_identification',['min'=>0,'max'=>2,'poolSize'=>3,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: shape of 0!','Preview.','Learn what 0 looks like!',[],[],'Great!','easy',1)];


/* ================================================================
   L02: Shape of Number 0
   Content: Round objects, orange/egg/tomato, circle the shape of 0
   ================================================================ */
$c='NUM-02-L02';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 0','Outline number 0 for colouring!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>0,'difficulty'=>1],'Colour the number 0!','Recognise shape of 0.','Number 0 is round like a ball. Tap to colour it!',[0,1,2],0,'Yes! 0 is round!','easy',2)];
// 2. Circle Objects Shaped Like 0
$acts[]=[$c,'warmup',1,'Circle Round Objects','Which objects are round like number 0?',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'shape_object'=>'orange','difficulty'=>1],'Find round objects!','Match round shapes to 0.','An orange is round like 0. Find it!',[0,1,2],0,'Orange is round like 0!','easy',2)];
// 3. Orange, Egg, Tomato
$acts[]=[$c,'i_do',2,'Orange, Egg, Tomato','These all look like number 0!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'shape_object'=>'egg','difficulty'=>1],'Find the object shaped like 0!','Match objects to 0 shape.','An egg is round like 0. Find it!',[0,1,2],0,'Egg looks like 0!','easy',2)];
// 4. Find Round Objects
$acts[]=[$c,'we_do',3,'Find Round Objects','Pick the round object that looks like 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'shape_object'=>'tomato','difficulty'=>1],'Which object is round like 0?','Identify round shapes.','A tomato is round like 0. Find it!',[0,1,2],0,'Tomato is round like 0!','easy',2)];
// 5. Trace Number 0
$acts[]=[$c,'you_do',4,'Trace Number 0','Trace and write number 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>1],'Trace number 0!','Practise writing 0.','Trace number 0 — one smooth round circle!',[0],0,'Well traced!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 0','Tap number 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 0!','Identify 0.','Which number is 0?',[0,1,2,3],0,'Yes! 0!','easy',1)];
// 7. Shape Game
$acts[]=[$c,'game',6,'Shape Game: Round Objects','Find the round objects!',act_json('math_game',['difficulty'=>1,'min'=>0,'max'=>3,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find round objects!','Game: recognise shapes.','Which object looks like 0?',[],0,'Round like 0!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Shape of 0','Show what you know about the shape of 0!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>4,'shape_object'=>'orange','difficulty'=>1],'What shape is 0?','Assess shape knowledge.','Which object looks like 0?',[0,1,2,3],0,'0 is round!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Shape of Number 0!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You know the shape of 0!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Tracing 0','Ready to trace number 0!',act_json('number_identification',['min'=>0,'max'=>2,'poolSize'=>3,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: trace 0!','Preview.','Time to write number 0!',[],[],'Great!','easy',1)];


/* ================================================================
   L03: Tracing Number 0
   Content: Trace 0, write 0, circular motion practice
   ================================================================ */
$c='NUM-02-L03';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 0','Outline number 0 for colouring!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>0,'difficulty'=>1],'Colour the number 0!','Practise 0 shape.','Number 0 is a circle. Tap to colour it!',[0,1,2],0,'Yes! Colour the circle!','easy',2)];
// 2. Trace the Circle
$acts[]=[$c,'warmup',1,'Trace the Circle','Draw a circle — that is number 0!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>1],'Trace the circle!','Practise circular motion.','Draw a circle — that is how we write 0!',[0],0,'A circle like number 0!','easy',2)];
// 3. Trace Number 0 (main)
$acts[]=[$c,'i_do',2,'Trace Number 0','Start at the top and go around!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>1],'Trace number 0!','Write 0 with correct motion.','Start at the top. Go around and back to the top!',[0],0,'Beautiful 0!','easy',2)];
// 4. Write Number 0
$acts[]=[$c,'we_do',3,'Write Number 0','Write number 0 on your own.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>2],'Write number 0!','Independent practice.','Write number 0 — one smooth circle!',[0],0,'Well written!','easy',2)];
// 5. Draw Number 0
$acts[]=[$c,'you_do',4,'Draw Number 0','Draw number 0 like a round ball.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'mode'=>'trace','target_number'=>0,'difficulty'=>2],'Draw number 0!','Creative practice.','Draw number 0 — it looks like a ball!',[0],0,'Great drawing!','easy',2)];
// 6. Check
$acts[]=[$c,'check',5,'Find Number 0','Tap number 0.',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 0!','Identify 0.','Which number is 0?',[0,1,2,3],0,'Yes! 0!','easy',1)];
// 7. Tracing Game
$acts[]=[$c,'game',6,'Tracing Game','Trace the numbers!',act_json('math_game',['difficulty'=>1,'min'=>0,'max'=>3,'game_type'=>'number_hopscotch','skip_finish'=>false],'Trace the numbers!','Game: trace and find.','Find number 0 in the game!',[],0,'Found it!','easy',3)];
// 8. Assessment
$acts[]=[$c,'assessment',7,'Quiz: Tracing 0','Show how well you trace 0!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>4,'difficulty'=>1],'Can you find 0?','Assess tracing knowledge.','Find number 0.',[0,1,2,3],0,'You can trace 0!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Great Work!','You completed Tracing Number 0!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned a star!','Celebrate.','You can trace 0!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Next: Finding 0','Ready to find number 0!',act_json('number_identification',['min'=>0,'max'=>2,'poolSize'=>3,'step_type'=>'next_steps','skip_finish'=>true,'difficulty'=>1],'Next: find 0!','Preview.','Time to find number 0 everywhere!',[],[],'Great!','easy',1)];


/* ================================================================
   L04: Finding Number 0
   Content: Find 0 among numbers, empty containers, final quiz
   ================================================================ */
$c='NUM-02-L04';
// 1. Number Coloring
$acts[]=[$c,'intro',0,'Colour Number 0','Outline number 0 for colouring!',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>3,'interaction'=>'coloring','target_number'=>0,'difficulty'=>1],'Colour the number 0!','Final colouring of 0.','Colour number 0 — the round circle!',[0,1,2],0,'Yes! Colour the circle!','easy',2)];
// 2. Circle the Empty Container
$acts[]=[$c,'warmup',1,'Find the Empty Container','Which container is empty? That is zero!',act_json('match_quantity',['min'=>0,'max'=>3,'object'=>'star','target'=>0,'difficulty'=>1],'Find the empty group!','Match zero to empty.','Which group has zero objects?',[],0,'That group is empty — zero!','easy',2)];
// 3. Find Number 0 (main)
$acts[]=[$c,'i_do',2,'Find Number 0','Look carefully — which one is 0?',act_json('number_identification',['min'=>0,'max'=>3,'poolSize'=>4,'target_number'=>0,'difficulty'=>1],'Find number 0!','Identify 0 among numbers.','Among 0, 1, 2, 3 — find 0!',[0,1,2,3],0,'Yes! That is 0!','easy',2)];
// 4. Match Zero
$acts[]=[$c,'we_do',3,'Match Zero to Empty','Match the number 0 to the empty group.',act_json('match_quantity',['min'=>0,'max'=>3,'object'=>'chair','target'=>0,'difficulty'=>1],'Find the group with 0 chairs!','Match zero to empty group.','Which group has zero chairs?',[],0,'Correct! No chairs — zero!','easy',2)];
// 5. Identify Number 0
$acts[]=[$c,'you_do',4,'Identify Number 0','Point to number 0!',act_json('number_identification',['min'=>0,'max'=>4,'poolSize'=>4,'target_number'=>0,'difficulty'=>2],'Point to number 0!','Independent identification.','Find number 0 among 0, 1, 2, 3, 4!',[0,1,2,3,4],0,'You found 0!','easy',2)];
// 6. Final Check
$acts[]=[$c,'check',5,'Final Check: Number 0','Show what you know!',act_json('number_identification',['min'=>0,'max'=>4,'poolSize'=>4,'target_number'=>0,'difficulty'=>2],'Find number 0!','Final check of knowledge.','Which number is 0?',[0,1,2,3,4],0,'Yes! 0!','easy',1)];
// 7. Finding Game
$acts[]=[$c,'game',6,'Finding Game: Find 0','Find number 0 in the game!',act_json('math_game',['difficulty'=>1,'min'=>0,'max'=>4,'game_type'=>'number_hopscotch','skip_finish'=>false],'Find 0!','Game: find 0.','Hop to number 0!',[],0,'Found it!','easy',3)];
// 8. Final Quiz
$acts[]=[$c,'assessment',7,'Final Quiz: Number 0','Show everything you know about 0!',act_json('number_identification',['min'=>0,'max'=>4,'poolSize'=>4,'difficulty'=>2],'Final quiz: find 0!','Assess all knowledge.','Find number 0 one more time!',[0,1,2,3,4],0,'You know number 0!','easy',3)];
// 9. Reward
$acts[]=[$c,'reward',8,'Congratulations!','You completed Recognising Number 0!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'Congratulations!','Celebrate.','You learned number 0! You know 0!',[],[],'Amazing!','easy',1)];
// 10. Next Steps
$acts[]=[$c,'next_steps',9,'Ready for More!','You are ready for bigger numbers!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'What is next?','Preview next topic.','You know 0! Ready for more numbers!',[],[],'Amazing!','easy',1)];
