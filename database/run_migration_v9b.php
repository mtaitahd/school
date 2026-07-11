<?php
/**
 * Part B: Activity data definitions for all 80 activities
 * Included by run_migration_v9.php
 * Uses $db (Database) and $L (lesson_code => lesson_id map)
 */

function act_json($engine, $extra, $instruction, $objective, $content, $choices, $answer, $feedback, $difficulty, $time) {
  return json_encode(array_merge([
    'engine'=>$engine,'instruction'=>$instruction,'objective'=>$objective,'content'=>$content,
    'choices'=>$choices,'answer'=>$answer,'feedback'=>$feedback,'difficulty'=>$difficulty,'estimated_time'=>$time
  ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

$acts = [];

// L1: Numbers 1,2,3
$c='NUM-01-L01';
$acts[]=[$c,'intro',0,'Intro: Meet Numbers 1,2,3','Say hello to numbers 1, 2, and 3!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Say hello to 1,2,3!','Introduce numbers 1,2,3.','Numbers 1,2,3 — one star, two stars, three stars!',[],[],'Great! Let us learn 1,2,3!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Colour Number 1','Tap the number 1!',act_json('number_identification',['min'=>1,'max'=>1,'poolSize'=>3,'difficulty'=>1],'Find and touch number 1!','Recognise shape of 1.','Number 1 looks like a line. Find it!',[1,2,3],1,'Yes! Number 1 like a stick!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 1-2-3','Watch me count one two three!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>1],'Watch: one two three!','Demonstrate 1-3.','Three stars: one, two, three!',[],[],'Watch me!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 1 Apple','Tap and count 1 with me!',act_json('mango_counting',['min'=>1,'max'=>1,'object'=>'apple','mode'=>'count','difficulty'=>1],'Count 1 apple together!','Practise counting 1.','One apple — tap and say "one"!',[1,2,3],1,'Yes 1 apple!','easy',2)];
$acts[]=[$c,'you_do',4,'You Do: Count 2 Fish','Count 2 fish!',act_json('mango_counting',['min'=>2,'max'=>2,'object'=>'fish','mode'=>'count','difficulty'=>1],'Count 2 fish!','Count 2 independently.','Two fish: one, two!',[1,2,3],2,'Super! 2 fish!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 2','Tap number 2.',act_json('number_identification',['min'=>1,'max'=>3,'poolSize'=>3,'difficulty'=>1],'Find number 2!','Identify 2.','Which is 2?',[1,2,3],2,'Yes! 2 like a swan!','easy',1)];
$acts[]=[$c,'game',6,'Game: Hunt 1-2-3','Find 1,2,3!',act_json('number_identification',['min'=>1,'max'=>3,'mode'=>'hunt','poolSize'=>3,'difficulty'=>1],'Find 1,2,3!','Recognise in a game.','Numbers hiding — find 1,2,3!',[1,2,3],3,'All found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: 1-2-3','Show what you know!',act_json('mango_counting',['min'=>1,'max'=>3,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about 1-3?','Assess 1-3.','Count and choose.',[1,2,3],3,'You know 1,2,3!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate.','You learned 1,2,3!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 4-5','Ready for 4 and 5!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Ready for 4,5!','Preview.','Next: 4 and 5!',[],[],'Great!','easy',1)];

// L2: Numbers 4,5
$c='NUM-01-L02';
$acts[]=[$c,'intro',0,'Intro: Meet 4 and 5','Say hello to 4 and 5!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>1],'Hello 4 and 5!','Introduce 4,5.','Four stars, five stars!',[],[],'Learn 4 and 5!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Colour 4','Tap number 4!',act_json('number_identification',['min'=>4,'max'=>4,'poolSize'=>3,'difficulty'=>1],'Find number 4!','Recognise 4.','4 like a flag!',[3,4,5],4,'Yes 4!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 4-5','Watch: four five!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>1],'Watch 4,5!','Demonstrate 4,5.','Four stars then five!',[],[],'Watch me!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 3 Balls','Count 3 balls together!',act_json('mango_counting',['min'=>3,'max'=>3,'object'=>'ball','mode'=>'count','difficulty'=>1],'Count 3 balls!','Practise 3.','Three balls!',[2,3,4],3,'Yes 3!','easy',2)];
$acts[]=[$c,'you_do',4,'You Do: Count 4 Fish','Count 4 fish!',act_json('mango_counting',['min'=>4,'max'=>4,'object'=>'fish','mode'=>'count','difficulty'=>1],'Count 4 fish!','Count 4 independently.','Four fish!',[3,4,5],4,'Brilliant!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 5','Tap number 5.',act_json('number_identification',['min'=>4,'max'=>5,'poolSize'=>3,'difficulty'=>1],'Find 5!','Identify 5.','Which is 5?',[4,5,6],5,'5 has a round tummy!','easy',1)];
$acts[]=[$c,'game',6,'Game: Hunt 4-5','Find 4 and 5!',act_json('number_identification',['min'=>4,'max'=>5,'mode'=>'hunt','poolSize'=>3,'difficulty'=>1],'Find 4,5!','Recognise in game.','Find them!',[4,5],5,'Both found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: 4-5','Show what you know!',act_json('mango_counting',['min'=>4,'max'=>5,'object'=>'star','mode'=>'quiz','difficulty'=>1],'What about 4,5?','Assess 4,5.','Count and choose.',[4,5],5,'You know 4,5!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>1,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate.','You learned 4,5!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 6-7','Ready for 6 and 7!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for 6,7!','Preview.','Next: 6 and 7!',[],[],'Great!','easy',1)];

// L3: Numbers 6,7
$c='NUM-01-L03';
$acts[]=[$c,'intro',0,'Intro: Meet 6 and 7','Hello 6 and 7!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Hello 6 and 7!','Introduce 6,7.','Six stars, seven stars!',[],[],'Learn 6 and 7!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count 6 Rabbits','Count 6 rabbits!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'bunny','mode'=>'count','difficulty'=>2],'Count 6 rabbits!','Practise 6.','Six hopping rabbits!',[5,6,7],6,'Yes 6 rabbits!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 6-7','Watch: six seven!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>2],'Watch 6,7!','Demonstrate 6,7.','Six stars, seven stars!',[],[],'Watch!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 7 Ducks','Count 7 ducks!',act_json('mango_counting',['min'=>7,'max'=>7,'object'=>'duck','mode'=>'count','difficulty'=>2],'Count 7 ducks!','Practise 7.','Seven ducks swimming!',[6,7,8],7,'Super 7 ducks!','easy',3)];
$acts[]=[$c,'you_do',4,'You Do: Count 6 Fish','Count 6 fish!',act_json('mango_counting',['min'=>6,'max'=>6,'object'=>'fish','mode'=>'count','difficulty'=>2],'Count 6 fish!','Count 6 independently.','Six fish!',[5,6,7],6,'Fantastic!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 7','Tap 7.',act_json('number_identification',['min'=>6,'max'=>7,'poolSize'=>3,'difficulty'=>2],'Find 7!','Identify 7.','Which is 7?',[6,7,8],7,'7 like a boomerang!','easy',1)];
$acts[]=[$c,'game',6,'Game: Hunt 6-7','Find 6 and 7!',act_json('number_identification',['min'=>6,'max'=>7,'mode'=>'hunt','poolSize'=>4,'difficulty'=>2],'Find 6,7!','Recognise in game.','Find them!',[6,7],7,'Great!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: 6-7','Show what you know!',act_json('mango_counting',['min'=>6,'max'=>7,'object'=>'star','mode'=>'quiz','difficulty'=>2],'What about 6,7?','Assess 6,7.','Count and choose.',[6,7],7,'You know 6,7!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate.','You learned 6,7!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Numbers 8-9','Ready for 8 and 9!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready for 8,9!','Preview.','Next: 8 and 9!',[],[],'Great!','easy',1)];

// L4: Numbers 8,9
$c='NUM-01-L04';
$acts[]=[$c,'intro',0,'Intro: Meet 8 and 9','Hello 8 and 9!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Hello 8 and 9!','Introduce 8,9.','Eight stars, nine stars!',[],[],'Learn 8 and 9!','easy',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count 8 Balls','Count 8 balls!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'ball','mode'=>'count','difficulty'=>2],'Count 8 balls!','Practise 8.','Eight balls!',[7,8,9],8,'Yes 8 balls!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 8-9','Watch: eight nine!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>2],'Watch 8,9!','Demonstrate 8,9.','Eight stars, nine stars!',[],[],'Watch!','easy',2)];
$acts[]=[$c,'we_do',3,'We Do: Count 9 Birds','Count 9 birds!',act_json('mango_counting',['min'=>9,'max'=>9,'object'=>'bird','mode'=>'count','difficulty'=>2],'Count 9 birds!','Practise 9.','Nine birds flying!',[8,9,10],9,'Super 9 birds!','easy',3)];
$acts[]=[$c,'you_do',4,'You Do: Count 8 Birds','Count 8 birds!',act_json('mango_counting',['min'=>8,'max'=>8,'object'=>'bird','mode'=>'count','difficulty'=>2],'Count 8 birds!','Count 8 independently.','Eight birds!',[7,8,9],8,'Fantastic!','easy',2)];
$acts[]=[$c,'check',5,'Check: Find Number 9','Tap 9.',act_json('number_identification',['min'=>8,'max'=>9,'poolSize'=>3,'difficulty'=>2],'Find 9!','Identify 9.','Which is 9?',[8,9,10],9,'9 like a balloon!','easy',1)];
$acts[]=[$c,'game',6,'Game: Hunt 8-9','Find 8 and 9!',act_json('number_identification',['min'=>8,'max'=>9,'mode'=>'hunt','poolSize'=>4,'difficulty'=>2],'Find 8,9!','Recognise in game.','Find them!',[8,9],9,'Both found!','easy',3)];
$acts[]=[$c,'assessment',7,'Quiz: 8-9','Show what you know!',act_json('mango_counting',['min'=>8,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>2],'What about 8,9?','Assess 8,9.','Count and choose.',[8,9],9,'You know 8,9!','easy',3)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>2,'step_type'=>'reward','skip_finish'=>true],'You earned stars!','Celebrate.','You learned 8,9!',[],[],'Amazing!','easy',1)];
$acts[]=[$c,'next_steps',9,'Next: Count 1-9','Ready to count all!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>2],'Ready count 1-9!','Preview.','Count all 1-9 next!',[],[],'Great!','easy',1)];

// L5: Counting 1-9
$c='NUM-01-L05';
$acts[]=[$c,'intro',0,'Intro: Count All 1-9','Count from 1 to 9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Count 1 to 9!','Introduce full count.','1,2,3,4,5,6,7,8,9!',[],[],'Count all!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Pattern 1-5','Count 1 to 5.',act_json('mango_counting',['min'=>1,'max'=>5,'object'=>'apple','mode'=>'count','difficulty'=>1],'Warm up 1-5!','Review 1-5.','Apples 1 to 5!',[1,2,3,4,5],5,'Warm-up done!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Count 1-9','Watch 1 to 9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Watch 1 to 9!','Demonstrate 1-9.','Nine candies 1-9!',[],[],'Watch!','medium',3)];
$acts[]=[$c,'we_do',3,'We Do: Match 1-9','Match groups to numbers.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'apple','difficulty'=>3],'Match groups!','Guided matching.','Match number to group.',[1,2,3,4,5,6,7,8,9],5,'Correct match!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Match Alone','Count and match!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','difficulty'=>3],'Match alone!','Independent matching.','Find the group.',[1,2,3,4,5,6,7,8,9],7,'Wonderful!','medium',3)];
$acts[]=[$c,'check',5,'Check: Sequence 1-9','Put 1-9 in order.',act_json('number_sequencing',['min'=>1,'max'=>9,'difficulty'=>3],'Order 1-9!','Sequence.','Drag numbers!',[1,2,3,4,5,6,7,8,9],9,'Perfect order!','medium',3)];
$acts[]=[$c,'game',6,'Game: Dot-to-Dot 1-9','Connect 1 to 9!',act_json('dot_to_dot',['min'=>1,'max'=>9,'difficulty'=>3],'Connect dots!','Reinforce sequence.','1 to 9 makes a picture!',[1,2,3,4,5,6,7,8,9],9,'You made it!','medium',4)];
$acts[]=[$c,'assessment',7,'Quiz: Count 1-9','Show counting skills.',act_json('mango_counting',['min'=>3,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>3],'Show what you know!','Assess 1-9.','Count and choose.',[1,2,3,4,5,6,7,8,9],7,'You count 1-9!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate counting!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Counting champ!','Celebrate.','Counted 1-9!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Compare','Learn more and less!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: compare!','Preview.','More and less!',[],[],'Ready!','medium',1)];

// L6: Comparing 1-9
$c='NUM-01-L06';
$acts[]=[$c,'intro',0,'Intro: More and Less','Learn to compare!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Which has more?','Introduce comparing.','MORE vs LESS!',[],[],'Compare!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Count 2 Groups','Count both groups.',act_json('mango_counting',['min'=>1,'max'=>5,'object'=>'apple','mode'=>'count','difficulty'=>2],'Count 2 groups!','Review counting.','Count A and B.',[3,5],5,'Good!','easy',2)];
$acts[]=[$c,'i_do',2,'I Do: Watch Compare','3 vs 5 — more?',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Watch compare!','Demonstrate.','5 is MORE than 3.',[],[],'Watch!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Which More?','Compare candies.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'compare','difficulty'=>3],'Which more?','Guided compare.','Which has more candies?',[],[],'Bigger wins!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Bigger Group','Tap the bigger!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'cookie','mode'=>'compare','difficulty'=>3],'Find bigger!','Compare independently.','Which has more cookies?',[],[],'You found more!','medium',3)];
$acts[]=[$c,'check',5,'Check: Equal Groups','Find same groups.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'apple','mode'=>'equal','difficulty'=>3],'Find equal!','Identify equal.','Which are the same?',[],[],'Equal!','medium',3)];
$acts[]=[$c,'game',6,'Game: More or Less','Quick compare!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','mode'=>'compare','difficulty'=>3],'More or less?','Game.','Which has MORE?',[],[],'Champion!','medium',3)];
$acts[]=[$c,'assessment',7,'Quiz: Compare','Show comparison skills.',act_json('mango_counting',['min'=>3,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>3],'Compare quiz!','Assess compare.','Count and compare.',[3,5,7,9],9,'You compare!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Compare star!','Celebrate.','You learned MORE/LESS!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Missing Numbers','Find hidden numbers!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: missing!','Preview.','Which hides?',[],[],'Ready!','medium',1)];

// L7: Missing Numbers 1-9
$c='NUM-01-L07';
$acts[]=[$c,'intro',0,'Intro: Missing Numbers','Find the hidden number!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Find missing number!','Introduce missing.','Number hid from the line!',[],[],'Detective!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Order 1-9','Put 1-9 in order.',act_json('number_sequencing',['min'=>1,'max'=>9,'difficulty'=>3],'Order 1-9!','Review sequence.','Place numbers 1-9.',[1,2,3,4,5,6,7,8,9],9,'Perfect!','medium',3)];
$acts[]=[$c,'i_do',2,'I Do: Find Missing','1,2,_,4 — 3 missing!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Watch me!','Demo missing.','1,2,_,4 — 3 is missing!',[1,2,3,4,5,6,7,8,9],3,'3 after 2 before 4!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Missing Together','3,4,_,6 — find it!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Find together!','Guided.','3,4,_,6 — what?',[3,4,5,6],5,'Yes 5!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Find Alone','Find missing!',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>3],'Find alone!','Independent.','Which is missing?',[1,2,3,4,5,6,7,8,9],6,'Super detective!','medium',3)];
$acts[]=[$c,'check',5,'Check: Fill Multiple','Fill 1,_,3,_,5,_,7,_,9',act_json('missing_numbers',['min'=>1,'max'=>9,'mode'=>'multiple','difficulty'=>4],'Fill all!','Multiple missing.','Find 2,4,6,8!',[2,4,6,8],8,'All found!','medium',3)];
$acts[]=[$c,'game',6,'Game: Dot Surprise','Connect 1-9 for surprise!',act_json('dot_to_dot',['min'=>1,'max'=>9,'difficulty'=>3],'Connect dots!','Reinforce.','1-9 = surprise!',[1,2,3,4,5,6,7,8,9],9,'Beautiful!','medium',4)];
$acts[]=[$c,'assessment',7,'Quiz: Missing','Show detective skills.',act_json('missing_numbers',['min'=>1,'max'=>9,'difficulty'=>4],'Missing quiz!','Assess.','Which missing?',[1,2,3,4,5,6,7,8,9],4,'Detective!','medium',4)];
$acts[]=[$c,'reward',8,'Reward: Great Work!','Celebrate!',act_json('math_game',['difficulty'=>3,'step_type'=>'reward','skip_finish'=>true],'Number detective!','Celebrate.','You find missing numbers!',[],[],'Amazing!','medium',1)];
$acts[]=[$c,'next_steps',9,'Next: Revision','Review all 1-9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Next: review!','Preview.','Review everything!',[],[],'Ready!','medium',1)];

// L8: Revision 1-9
$c='NUM-01-L08';
$acts[]=[$c,'intro',0,'Intro: Review 1-9','Review everything!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>3],'Review 1-9!','Introduce revision.','All numbers 1-9!',[],[],'Review time!','medium',2)];
$acts[]=[$c,'warmup',1,'Warm-Up: Quick Count','Quick count 1-9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'apple','mode'=>'count','difficulty'=>3],'Quick count!','Quick review.','Apples 1-9 go!',[1,2,3,4,5,6,7,8,9],9,'Great!','medium',2)];
$acts[]=[$c,'i_do',2,'I Do: Review All','1-2-3-4-5-6-7-8-9!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'demo','skip_finish'=>true,'difficulty'=>3],'Review all!','Review.','Say them with me!',[],[],'Review!','medium',2)];
$acts[]=[$c,'we_do',3,'We Do: Match to Objects','Match numbers to groups.',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'candy','difficulty'=>4],'Match together!','Guided.','Match number to candies.',[1,2,3,4,5,6,7,8,9],6,'Perfect!','medium',3)];
$acts[]=[$c,'you_do',4,'You Do: Match Alone','Count and match!',act_json('match_quantity',['min'=>1,'max'=>9,'object'=>'cookie','difficulty'=>4],'Match alone!','Independent.','Find the group!',[1,2,3,4,5,6,7,8,9],8,'Wonderful!','medium',3)];
$acts[]=[$c,'check',5,'Check: Find Any 1-9','Find the number I say!',act_json('number_identification',['min'=>1,'max'=>9,'poolSize'=>6,'difficulty'=>4],'Find the number!','Identify any.','I say a number — find it!',[1,2,3,4,5,6,7,8,9],5,'You know all!','medium',3)];
$acts[]=[$c,'game',6,'Game: Hunt Challenge','Find all 1-9!',act_json('number_identification',['min'=>1,'max'=>9,'mode'=>'hunt','poolSize'=>9,'difficulty'=>4],'Find all numbers!','Recognise all.','All numbers hiding!',[1,2,3,4,5,6,7,8,9],9,'Number master!','medium',4)];
$acts[]=[$c,'assessment',7,'Final Quiz: 1-9','The big quiz!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'quiz','difficulty'=>4],'Show everything!','Assess all.','Count and show all you know!',[1,2,3,4,5,6,7,8,9],9,'MASTERED 1-9!','medium',5)];
$acts[]=[$c,'reward',8,'Reward: Congratulations!','You completed 1-9!',act_json('math_game',['difficulty'=>4,'step_type'=>'reward','skip_finish'=>true],'Congratulations!','Celebrate.','You are a MATHS STAR!',[],[],'Congratulations!','medium',2)];
$acts[]=[$c,'next_steps',9,'Next: What Next?','Ready for more!',act_json('mango_counting',['min'=>1,'max'=>9,'object'=>'star','mode'=>'intro','skip_finish'=>true,'difficulty'=>4],'What is next?','Preview.','Numbers 10-20, shapes!',[],[],'Amazing!','medium',1)];

// Now upsert all
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
