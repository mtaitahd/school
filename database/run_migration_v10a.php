<?php
/**
 * Phase 5 — Migration A: Create Topic NUM-02 + 4 Lessons
 *
 * "Recognising Number 0"
 * Module 14 (same module as NUM-01)
 * Topic code: NUM-02
 * 4 lessons, 10 activities each = 40 activities
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 5 Migration A: Topic NUM-02 — Recognising Number 0 ===\n\n";

/* 1. Get strand_id for NUM */
$strand = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strand) { echo "ERROR: strand NUM not found\n"; exit(1); }
$strand_id = $strand['strand_id'];
echo "strand_id = $strand_id\n";

/* 2. Create topic NUM-02 */
$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index)
     VALUES (?, 14, 'Recognising Number 0', 'NUM-02', '4-5', 'Introduction to zero: recognising, shaping, tracing, and finding the number 0.', 4, 2)",
    [$strand_id]
);
$topic = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-02'");
if (!$topic) { echo "ERROR: topic NUM-02 not created\n"; exit(1); }
$topic_id = $topic['topic_id'];
echo "topic_id = $topic_id\n\n";

/* 3. Create 4 lessons */
$lessons = [
    ['NUM-02-L01', 'Recognising Number 0', 'Recognise the number 0 and understand it means zero/none.', 'Can identify 0 and explain zero means nothing.', 15, null, 1],
    ['NUM-02-L02', 'Shape of Number 0', 'Recognise that 0 is round like an orange, egg, or tomato.', 'Can match round objects to the shape of 0.', 15, '["NUM-02-L01"]', 2],
    ['NUM-02-L03', 'Tracing Number 0', 'Trace and write the number 0 in a smooth circular motion.', 'Can trace 0 without lifting the finger.', 15, '["NUM-02-L02"]', 3],
    ['NUM-02-L04', 'Finding Number 0', 'Find number 0 among other numbers and identify empty containers.', 'Can find 0 in number lines and match empty groups.', 15, '["NUM-02-L03"]', 4],
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
