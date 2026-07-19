<?php
/**
 * Migration: Split "Counting Numbers 1-9" into two sub-categories
 *
 * Current state: "Counting Numbers 1-9" module has topic NUM-C1
 *   with lessons COUNT-N1 through COUNT-N9 (one lesson per number)
 *
 * Creates:
 *   - Module "Numbers 1-5" (module_id=15) with topic NUM-C1A
 *   - Module "Numbers 6-9" (module_id=16) with topic NUM-C1B
 * Moves lessons:
 *   - COUNT-N1 through COUNT-N5 → NUM-C1A / Module 15
 *   - COUNT-N6 through COUNT-N9 → NUM-C1B / Module 16
 * Updates activities to point to new modules.
 *
 * Visit: https://smartmathconner.co.tz/database/migrate_split_count19.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../php/db_connection.php';

echo "<pre>\n";
echo "=== Split Counting 1-9 into Numbers 1-5 and Numbers 6-9 ===\n\n";

/* ----------------------------------------------------------------
   STEP 0: Find parent module and topic
   ---------------------------------------------------------------- */
echo "--- STEP 0: Current State ---\n";

$parentModule = $database->fetchOne("SELECT module_id, module_name FROM modules WHERE module_name = 'Counting Numbers 1-9'");
if (!$parentModule) {
    die("ERROR: 'Counting Numbers 1-9' module not found.\n");
}
$parentId = (int)$parentModule['module_id'];
echo "Parent module: ID=$parentId ({$parentModule['module_name']})\n";

$parentTopic = $database->fetchOne("SELECT topic_id, topic_code FROM topics WHERE module_id = ? LIMIT 1", [$parentId]);
if (!$parentTopic) {
    die("ERROR: No topic found for parent module.\n");
}
$parentTopicId = (int)$parentTopic['topic_id'];
echo "Parent topic: ID=$parentTopicId ({$parentTopic['topic_code']})\n";

/* Check existing lessons */
$existingLessons = $database->fetchAll(
    "SELECT lesson_id, lesson_code, lesson_name FROM lessons WHERE topic_id = ? AND is_active = 1 ORDER BY order_index",
    [$parentTopicId]
);
echo "Active lessons under parent: " . count($existingLessons) . "\n";
foreach ($existingLessons as $el) {
    echo "  {$el['lesson_code']}: {$el['lesson_name']}\n";
}
echo "\n";

/* ----------------------------------------------------------------
   STEP 1: Create two new modules
   ---------------------------------------------------------------- */
echo "--- STEP 1: Create Modules ---\n";

$database->execute(
    "INSERT IGNORE INTO modules (module_id, module_name, module_description, module_icon, module_color, audio_prompt, order_index, is_active)
     VALUES (15, 'Numbers 1-5', 'Learn to count, match, and play with numbers 1 to 5', 'fa-hand-paper', '#27ae60', 'Touch here for Numbers 1 to 5!', 1, 1)"
);
echo "Module 15 (Numbers 1-5) ensured.\n";

$database->execute(
    "INSERT IGNORE INTO modules (module_id, module_name, module_description, module_icon, module_color, audio_prompt, order_index, is_active)
     VALUES (16, 'Numbers 6-9', 'Learn to count, match, and play with numbers 6 to 9', 'fa-finger-blast', '#e74c3c', 'Touch here for Numbers 6 to 9!', 2, 1)"
);
echo "Module 16 (Numbers 6-9) ensured.\n\n";

/* ----------------------------------------------------------------
   STEP 2: Create two new topics
   ---------------------------------------------------------------- */
echo "--- STEP 2: Create Topics ---\n";

$strandRow = $database->fetchOne("SELECT strand_id FROM strands WHERE strand_code = 'NUM'");
if (!$strandRow) {
    $strandRow = $database->fetchOne("SELECT strand_id FROM strands LIMIT 1");
}
if (!$strandRow) {
    die("ERROR: No strand found. Run migrations_v9 first.\n");
}
$strand_id = (int)$strandRow['strand_id'];

$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index, is_active)
     VALUES (?, 15, 'Numbers 1-5', 'NUM-C1A', '3-5', 'Count, match, and play with numbers 1 to 5', 5, 1, 1)",
    [$strand_id]
);
echo "Topic NUM-C1A (Numbers 1-5) ensured.\n";

$database->execute(
    "INSERT IGNORE INTO topics (strand_id, module_id, topic_name, topic_code, age_range, description, estimated_sessions, order_index, is_active)
     VALUES (?, 16, 'Numbers 6-9', 'NUM-C1B', '3-5', 'Count, match, and play with numbers 6 to 9', 4, 2, 1)",
    [$strand_id]
);
echo "Topic NUM-C1B (Numbers 6-9) ensured.\n\n";

/* Get topic IDs */
$topicA = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-C1A'");
$topicB = $database->fetchOne("SELECT topic_id FROM topics WHERE topic_code = 'NUM-C1B'");
$topicA_id = (int)$topicA['topic_id'];
$topicB_id = (int)$topicB['topic_id'];

/* ----------------------------------------------------------------
   STEP 3: Move lessons to new topics
   ---------------------------------------------------------------- */
echo "--- STEP 3: Move Lessons ---\n";

/* Lessons for NUM-C1A (Numbers 1-5): COUNT-N1 through COUNT-N5 */
for ($n = 1; $n <= 5; $n++) {
    $code = 'COUNT-N' . $n;
    $database->execute("UPDATE lessons SET topic_id = ? WHERE lesson_code = ? AND topic_id = ?", [$topicA_id, $code, $parentTopicId]);
    echo "  Moved $code → NUM-C1A (Numbers 1-5)\n";
}

/* Lessons for NUM-C1B (Numbers 6-9): COUNT-N6 through COUNT-N9 */
for ($n = 6; $n <= 9; $n++) {
    $code = 'COUNT-N' . $n;
    $database->execute("UPDATE lessons SET topic_id = ? WHERE lesson_code = ? AND topic_id = ?", [$topicB_id, $code, $parentTopicId]);
    echo "  Moved $code → NUM-C1B (Numbers 6-9)\n";
}
echo "\n";

/* ----------------------------------------------------------------
   STEP 4: Update activities to point to new modules
   ---------------------------------------------------------------- */
echo "--- STEP 4: Update Activities ---\n";

/* Activities under COUNT-N1 through COUNT-N5 → module 15 */
for ($n = 1; $n <= 5; $n++) {
    $lesson = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", ['COUNT-N' . $n]);
    if ($lesson) {
        $database->execute("UPDATE activities SET module_id = 15 WHERE lesson_id = ?", [(int)$lesson['lesson_id']]);
    }
}
$countA = $database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = 15 AND is_active = 1");
echo "  Activities for Numbers 1-5: " . ($countA['cnt'] ?? 0) . "\n";

/* Activities under COUNT-N6 through COUNT-N9 → module 16 */
for ($n = 6; $n <= 9; $n++) {
    $lesson = $database->fetchOne("SELECT lesson_id FROM lessons WHERE lesson_code = ?", ['COUNT-N' . $n]);
    if ($lesson) {
        $database->execute("UPDATE activities SET module_id = 16 WHERE lesson_id = ?", [(int)$lesson['lesson_id']]);
    }
}
$countB = $database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = 16 AND is_active = 1");
echo "  Activities for Numbers 6-9: " . ($countB['cnt'] ?? 0) . "\n\n";

/* ----------------------------------------------------------------
   STEP 5: Update parent module to show sub-categories
   ---------------------------------------------------------------- */
echo "--- STEP 5: Mark Parent Module as Sub-Category Container ---\n";

/* Add a metadata field or use audio_prompt to mark it as a container */
$database->execute(
    "UPDATE modules SET module_description = 'Choose a number range to start learning' WHERE module_id = ?",
    [$parentId]
);
echo "  Parent module description updated.\n";

/* Set display order */
$database->execute("UPDATE modules SET order_index = 1 WHERE module_id = ?", [$parentId]);
$database->execute("UPDATE modules SET order_index = 2 WHERE module_id = 15");
$database->execute("UPDATE modules SET order_index = 3 WHERE module_id = 16");
echo "  Order: Parent=1, Numbers 1-5=2, Numbers 6-9=3\n\n";

/* ----------------------------------------------------------------
   VERIFICATION
   ---------------------------------------------------------------- */
echo "=== VERIFICATION ===\n";

$mod15 = $database->fetchOne("SELECT * FROM modules WHERE module_id = 15");
echo $mod15 ? "✓ Module 15: {$mod15['module_name']} ({$mod15['module_description']})\n" : "✗ Module 15 NOT found\n";

$mod16 = $database->fetchOne("SELECT * FROM modules WHERE module_id = 16");
echo $mod16 ? "✓ Module 16: {$mod16['module_name']} ({$mod16['module_description']})\n" : "✗ Module 16 NOT found\n";

$topicA_check = $database->fetchOne("SELECT * FROM topics WHERE topic_code = 'NUM-C1A'");
echo $topicA_check ? "✓ Topic NUM-C1A: {$topicA_check['topic_name']}\n" : "✗ Topic NUM-C1A NOT found\n";

$topicB_check = $database->fetchOne("SELECT * FROM topics WHERE topic_code = 'NUM-C1B'");
echo $topicB_check ? "✓ Topic NUM-C1B: {$topicB_check['topic_name']}\n" : "✗ Topic NUM-C1B NOT found\n";

/* Lessons per topic */
$lessonsInA = $database->fetchAll("SELECT lesson_code, lesson_name FROM lessons WHERE topic_id = ? AND is_active = 1 ORDER BY order_index", [$topicA_id]);
echo "\nLessons in Numbers 1-5 (NUM-C1A):\n";
foreach ($lessonsInA as $la) echo "  {$la['lesson_code']}: {$la['lesson_name']}\n";

$lessonsInB = $database->fetchAll("SELECT lesson_code, lesson_name FROM lessons WHERE topic_id = ? AND is_active = 1 ORDER BY order_index", [$topicB_id]);
echo "\nLessons in Numbers 6-9 (NUM-C1B):\n";
foreach ($lessonsInB as $lb) echo "  {$lb['lesson_code']}: {$lb['lesson_name']}\n";

/* Activities per lesson */
echo "\nActivities per lesson:\n";
$perLesson = $database->fetchAll(
    "SELECT l.lesson_code, l.lesson_name, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.topic_id IN (?, ?) AND a.is_active = 1
     GROUP BY l.lesson_code
     ORDER BY l.order_index",
    [$topicA_id, $topicB_id]
);
$total = 0;
foreach ($perLesson as $pl) {
    echo "  {$pl['lesson_code']}: {$pl['lesson_name']} — {$pl['cnt']} activities\n";
    $total += (int)$pl['cnt'];
}
echo "TOTAL: $total activities\n";

echo "\n=== MIGRATION COMPLETE ===\n";
echo "</pre>";
