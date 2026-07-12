<?php
/**
 * Phase 6 — Migration A: Create Topic NUM-03 + 8 Lessons
 *
 * "Recognising Number 10"
 * Module 14 (same module as NUM-01, NUM-02)
 * Topic code: NUM-03
 * 8 lessons, 10 activities each = 80 activities
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 6 Migration A: Topic NUM-03 — Recognising Number 10 ===\n\n";

/* 1. Get strand_id for NUM */
$strand = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strand) { echo "ERROR: strand NUM not found\n"; exit(1); }
$strand_id = $strand['strand_id'];
echo "strand_id = $strand_id\n";

/* 2. Create topic NUM-03 */
$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
     VALUES (?, 14, 'Recognising Number 10', 'NUM-03', '4-5', 'Introduction to number 10: recognising, reading, writing, counting, matching, and finding the number 10.', 8, 3)",
    [$strand_id]
);
$topic = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-03'");
if (!$topic) { echo "ERROR: topic NUM-03 not created\n"; exit(1); }
$topic_id = $topic['topic_id'];
echo "topic_id = $topic_id\n\n";

/* 3. Create 8 lessons */
$lessons = [
    ['NUM-03-L01', 'Introducing Number 10', 'Recognise the number 10 and understand it is the first two-digit number.', 'Can identify 10 and explain it is one-ten.', 15, null, 1],
    ['NUM-03-L02', 'Reading Number 10', 'Read the number 10 aloud and match the spoken word to the symbol.', 'Can read "ten" and recognise the digits 1 and 0 together.', 15, '["NUM-03-L01"]', 2],
    ['NUM-03-L03', 'Writing Number 10', 'Write the number 10 by drawing 1 and 0 side by side using a marker.', 'Can write 10 with correct stroke order.', 20, '["NUM-03-L02"]', 3],
    ['NUM-03-L04', 'Counting Groups of 10', 'Count exactly ten objects from a larger group.', 'Can count to 10 and stop at ten.', 15, '["NUM-03-L03"]', 4],
    ['NUM-03-L05', 'Matching Number 10', 'Match a group of ten objects to the number 10.', 'Can identify which group has exactly ten items.', 15, '["NUM-03-L04"]', 5],
    ['NUM-03-L06', 'Finding Number 10', 'Find number 10 among other numbers (7, 8, 9, 10).', 'Can locate 10 in a set of number tiles.', 15, '["NUM-03-L05"]', 6],
    ['NUM-03-L07', 'Practice and Review', 'Review all skills: recognise, read, write, count, and match number 10.', 'Can perform all number 10 tasks independently.', 20, '["NUM-03-L06"]', 7],
    ['NUM-03-L08', 'Assessment', 'Demonstrate mastery of number 10 through a comprehensive quiz.', 'Can score 80% or higher on the number 10 assessment.', 15, '["NUM-03-L07"]', 8],
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
