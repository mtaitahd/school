<?php
/**
 * Part A: Migration v9 — Schema + Structure
 * Run: php database/run_migration_v9.php (includes this via require)
 */
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/db_connection.php';
$db = new Database();
echo "=== Schema v3 ===\n";
ensure_schema_v3($db);
echo "OK\n=== Seed structure ===\n";
$db->execute("INSERT IGNORE INTO domains (domain_name,domain_code,domain_icon,domain_color,description,order_index) VALUES ('Mathematics','MATH','fa-calculator','#4A90E2','Numbers, shapes, patterns, and early math skills',1)");
$m = $db->fetchOne("SELECT domain_id FROM domains WHERE domain_code='MATH'")['domain_id'];
$db->execute("INSERT IGNORE INTO strands (domain_id,strand_name,strand_code,strand_icon,description,learning_hours,order_index) VALUES ($m,'Number & Operations','NUM','fa-sort-numeric-up','Counting, number recognition, and early arithmetic',40,1)");
$db->execute("INSERT IGNORE INTO modules (module_id,module_name,module_description,module_icon,module_color,audio_prompt,order_index) VALUES (14,'Recognising and Counting Numbers 1-9','Learn to recognise, count, and write numbers from 1 to 9','fa-sort-numeric-up','#FF8C00','Touch here for Recognising and Counting Numbers!',14)");
$num = $db->fetchOne("SELECT strand_id FROM strands WHERE strand_code='NUM'")['strand_id'];
$db->execute("INSERT IGNORE INTO topics (strand_id,module_id,topic_name,topic_code,age_range,description,estimated_sessions,order_index) VALUES ($num,14,'Recognising and Counting Numbers 1-9','NUM-01','4-5','Recognise, count, trace, and compare numbers 1 through 9',8,1)");
$t = $db->fetchOne("SELECT topic_id FROM topics WHERE topic_code='NUM-01'")['topic_id'];
$L = [];
$defs = [
  ['NUM-01-L01','Numbers 1, 2, 3','...recognise count trace 1,2,3.','Identifies 1,2,3 counts to 3 traces.',20,null,1],
  ['NUM-01-L02','Numbers 4, 5','...recognise count trace 4,5.','Identifies 4,5 counts 4-5 traces.',20,'["NUM-01-L01"]',2],
  ['NUM-01-L03','Numbers 6, 7','...recognise count trace 6,7.','Identifies 6,7 counts 6-7 traces.',20,'["NUM-01-L02"]',3],
  ['NUM-01-L04','Numbers 8, 9','...recognise count trace 8,9.','Identifies 8,9 counts 8-9 traces.',20,'["NUM-01-L03"]',4],
  ['NUM-01-L05','Counting 1-9','...count 1-9 order match.','Counts 1-9 matches arranges.',20,'["NUM-01-L04"]',5],
  ['NUM-01-L06','Comparing 1-9','...compare more fewer.','Identifies more/less within 1-9.',20,'["NUM-01-L05"]',6],
  ['NUM-01-L07','Missing 1-9','...find missing in sequence.','Finds missing number 1-9.',20,'["NUM-01-L06"]',7],
  ['NUM-01-L08','Revision 1-9','...mastery of 1-9.','Scores >=80%.',25,'["NUM-01-L07"]',8],
];
foreach ($defs as $d) {
  $db->execute("INSERT IGNORE INTO lessons (topic_id,lesson_code,lesson_name,learning_objective,success_criteria,estimated_minutes,prerequisite_lesson_ids,order_index) VALUES (?,?,?,?,?,?,?,?)",array_merge([$t],$d));
  $r = $db->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code=?",[$d[0]]);
  if ($r) $L[$d[0]]=$r['lesson_id'];
}
echo "Lessons: ".count($L)."\n";
echo "Lesson IDs:\n";
foreach ($L as $k=>$v) echo "  $k => $v\n";
