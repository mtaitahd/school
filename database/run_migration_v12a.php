<?php
/**
 * Phase 7 — Migration A: Create Topic NUM-04 + 8 Lessons
 *
 * "Counting Objects and Numbers 11–20"
 * Module 14 (same module as NUM-01, NUM-02, NUM-03)
 * Topic code: NUM-04
 * 8 lessons, 10 activities each = 80 activities
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 7 Migration A: Topic NUM-04 — Counting Objects and Numbers 11–20 ===\n\n";

/* 1. Get strand_id for NUM */
$strand = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strand) { echo "ERROR: strand NUM not found\n"; exit(1); }
$strand_id = $strand['strand_id'];
echo "strand_id = $strand_id\n";

/* 2. Get module_id for "Numbers 11–20" */
$mod = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Numbers 11–20' LIMIT 1");
$module_id = $mod ? (int)$mod['module_id'] : 14;
echo "module_id = $module_id\n";

/* 3. Create topic NUM-04 */
$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
     VALUES (?, ?, 'Counting Objects and Numbers 11–20', 'NUM-04', '4-5', 'Count objects and learn numbers 11 to 20: recognise, read, write, count, match, and play snake and ladder.', 8, 4)",
    [$strand_id, $module_id]
);
$topic = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-04'");
if (!$topic) { echo "ERROR: topic NUM-04 not created\n"; exit(1); }
$topic_id = $topic['topic_id'];
echo "topic_id = $topic_id\n\n";

/* 3. Create 8 lessons */
$lessons = [
    ['NUM-04-L01', 'Introducing Numbers 11–12', 'Recognise numbers 11 and 12, count 11 cows and 12 chickens.', 'Can identify 11 and 12 and count objects to match.', 15, null, 1],
    ['NUM-04-L02', 'Numbers 13–14', 'Recognise numbers 13 and 14, count 13 green apples and 14 pumpkins.', 'Can identify 13 and 14 and count objects to match.', 15, '["NUM-04-L01"]', 2],
    ['NUM-04-L03', 'Numbers 15–16', 'Recognise numbers 15 and 16, count 15 bells and 16 guitars.', 'Can identify 15 and 16 and count objects to match.', 15, '["NUM-04-L02"]', 3],
    ['NUM-04-L04', 'Numbers 17–18', 'Recognise numbers 17 and 18, count 17 watermelons and 18 whistles.', 'Can identify 17 and 18 and count objects to match.', 15, '["NUM-04-L03"]', 4],
    ['NUM-04-L05', 'Numbers 19–20', 'Recognise numbers 19 and 20, count 19 papayas and 20 glasses of water.', 'Can identify 19 and 20 and count objects to match.', 15, '["NUM-04-L04"]', 5],
    ['NUM-04-L06', 'Reading and Writing 11–20', 'Read and write all numbers from 11 to 20 using trace mode.', 'Can read and write numbers 11–20 independently.', 20, '["NUM-04-L05"]', 6],
    ['NUM-04-L07', 'Number Game (Snake and Ladder)', 'Play snake and ladder to reinforce counting from 1 to 20.', 'Can count forward from 1 to 20 during the game.', 15, '["NUM-04-L06"]', 7],
    ['NUM-04-L08', 'Assessment and Review', 'Demonstrate mastery of numbers 11–20 through a comprehensive quiz.', 'Can score 80% or higher on the numbers 11–20 assessment.', 20, '["NUM-04-L07"]', 8],
];

$created = 0;
foreach ($lessons as [$code, $name, $objective, $criteria, $mins, $prereq, $order]) {
    $database->execute(
        "INSERT IGNORE INTO lessons (topic_id, lesson_code, lesson_name, learning_objective, success_criteria, estimated_minutes, prerequisite_lesson_ids, order_index, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
        [$topic_id, $code, $name, $objective, $criteria, $mins, $prereq, $order]
    );
    $row = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", [$code]);
    if ($row) { $created++; echo "  $code → lesson_id={$row['lesson_id']}\n"; }
}

echo "\nCreated $created lessons\n";
echo "\nDone.\n";
