<?php
/**
 * Spec Update: Insert Smart Math Corner Spec-based Activities
 *
 * Adds 4 lessons to module 14 (Recognising and Counting Numbers 1-9):
 *   1. Count Objects and Read Numbers 1-5 (5 activities)
 *   2. Count Objects and Read Numbers 6-9 (4 activities)
 *   3. Recognising Number 0 (3 activities)
 *   4. Recognising Number 10 (4 activities)
 *
 * Usage: php database/run_migration_spec_update.php
 *
 * Engines (defined in js/activities/engines.js):
 *   spec_count_objects, spec_zero_plate, spec_zero_drag, spec_zero_tap,
 *   spec_ten_tap, spec_ten_drag, spec_ten_match, spec_ten_balloon
 */

require_once __DIR__ . '/../php/db_connection.php';

$module_id = 14;

echo "=== Smart Math Corner Spec Update ===\n\n";

function spec_act_data($engine, $extra = []) {
    return json_encode(array_merge([
        'engine' => $engine,
        'difficulty' => 1,
        'visual' => ['theme'=>'numbers','background'=>'light','show_progress'=>true,'large_numbers'=>true,'large_objects'=>true,'animation'=>'fade']
    ], $extra), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}

// --- Ensure the module exists ---
$mod = $database->fetchOne("SELECT module_id FROM modules WHERE module_id = ?", [$module_id]);
if (!$mod) {
    echo "ERROR: Module $module_id not found. Run earlier migrations first.\n";
    exit(1);
}
echo "Using module: $module_id\n\n";

// --- Lessons exactly matching the spec structure ---
$lessons = [];

// LESSON 1: COUNT OBJECTS AND READ NUMBERS 1-5
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L01',
    'lesson_name' => 'Count Objects and Read Numbers 1-5',
    'description' => 'Count objects and identify numbers 1 to 5.',
    'activities' => [
        // Activity 1.1: Count one orange
        [
            'name' => 'Count One Orange',
            'desc' => 'Tap one orange then select number 1.',
            'audio' => 'Tap one orange.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'orange',
                'count' => 1,
                'correct_number' => 1,
                'numbers' => [1, 2, 3],
                'tap_audio' => 'Tap one orange.',
                'success_audio' => 'One orange. Number one. Well done!',
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 0,
            'difficulty' => 1
        ],
        // Activity 1.2: Count two mangoes
        [
            'name' => 'Count Two Mangoes',
            'desc' => 'Tap two mangoes then select number 2.',
            'audio' => 'Tap two mangoes.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'mango',
                'count' => 2,
                'correct_number' => 2,
                'numbers' => [1, 2, 3],
                'tap_audio' => 'Tap two mangoes.',
                'success_audio' => 'Two mangoes. Number two. Well done!',
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 1,
            'difficulty' => 1
        ],
        // Activity 1.3: Count three pencils
        [
            'name' => 'Count Three Pencils',
            'desc' => 'Tap three pencils then select number 3.',
            'audio' => 'Tap three pencils.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'pencil',
                'count' => 3,
                'correct_number' => 3,
                'numbers' => [1, 2, 3, 4],
                'tap_audio' => 'Tap three pencils.',
                'success_audio' => 'Three pencils. Number three. Well done!',
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 2,
            'difficulty' => 1
        ],
        // Activity 1.4: Count four apples
        [
            'name' => 'Count Four Apples',
            'desc' => 'Tap four apples then select number 4.',
            'audio' => 'Tap four apples.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'apple',
                'count' => 4,
                'correct_number' => 4,
                'numbers' => [2, 1, 3, 4, 5],
                'tap_audio' => 'Tap four apples.',
                'success_audio' => 'Four apples. Number four. Well done!',
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 3,
            'difficulty' => 1
        ],
        // Activity 1.5: Count five cups
        [
            'name' => 'Count Five Cups',
            'desc' => 'Tap five cups then select number 5.',
            'audio' => 'Tap five cups.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'cup',
                'count' => 5,
                'correct_number' => 5,
                'numbers' => [1, 2, 3, 4, 5],
                'tap_audio' => 'Tap five cups.',
                'success_audio' => 'Five cups. Number five. Well done!',
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 4,
            'difficulty' => 1
        ],
    ]
];

// LESSON 2: COUNT OBJECTS AND READ NUMBERS 6-9
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L02',
    'lesson_name' => 'Count Objects and Read Numbers 6-9',
    'description' => 'Count objects and identify numbers 6 to 9.',
    'activities' => [
        // Activity 2.1: Count six chairs
        [
            'name' => 'Count Six Chairs',
            'desc' => 'Tap six chairs then select number 6.',
            'audio' => 'Tap six chairs.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'chair',
                'count' => 6,
                'correct_number' => 6,
                'numbers' => [1, 2, 3, 4, 5, 6],
                'tap_audio' => 'Tap six chairs.',
                'success_audio' => 'Six chairs. Number six. Well done!',
                'difficulty' => 2
            ]),
            'step_type' => 'warmup',
            'step_order' => 0,
            'difficulty' => 2
        ],
        // Activity 2.2: Count seven plates
        [
            'name' => 'Count Seven Plates',
            'desc' => 'Tap seven plates then select number 7.',
            'audio' => 'Tap seven plates.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'plate',
                'count' => 7,
                'correct_number' => 7,
                'numbers' => [2, 4, 5, 7, 1],
                'tap_audio' => 'Tap seven plates.',
                'success_audio' => 'Seven plates. Number seven. Well done!',
                'difficulty' => 2
            ]),
            'step_type' => 'warmup',
            'step_order' => 1,
            'difficulty' => 2
        ],
        // Activity 2.3: Count eight sticks
        [
            'name' => 'Count Eight Sticks',
            'desc' => 'Tap eight sticks then select number 8.',
            'audio' => 'Tap eight sticks.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'stick',
                'count' => 8,
                'correct_number' => 8,
                'numbers' => [4, 7, 1, 3, 2, 8],
                'tap_audio' => 'Tap eight sticks.',
                'success_audio' => 'Eight sticks. Number eight. Well done!',
                'difficulty' => 2
            ]),
            'step_type' => 'warmup',
            'step_order' => 2,
            'difficulty' => 2
        ],
        // Activity 2.4: Count nine trees
        [
            'name' => 'Count Nine Trees',
            'desc' => 'Tap nine trees then select number 9.',
            'audio' => 'Tap nine trees.',
            'activity_type' => 'spec_count_objects',
            'data' => spec_act_data('spec_count_objects', [
                'object' => 'tree',
                'count' => 9,
                'correct_number' => 9,
                'numbers' => [1, 2, 3, 4, 5, 6, 8, 9],
                'tap_audio' => 'Tap nine trees.',
                'success_audio' => 'Nine trees. Number nine. Well done!',
                'difficulty' => 2
            ]),
            'step_type' => 'warmup',
            'step_order' => 3,
            'difficulty' => 2
        ],
    ]
];

// LESSON 3: RECOGNISING NUMBER 0
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L03',
    'lesson_name' => 'Recognising Number 0',
    'description' => 'Understand that zero means no objects. Tap the empty plate, drag empty pictures to Zero, and tap number 0.',
    'activities' => [
        // Activity 3.1: Tap the empty plate
        [
            'name' => 'Tap the Empty Plate',
            'desc' => 'Three plates appear: 2 oranges, 1 orange, empty. Tap the empty plate.',
            'audio' => 'Tap the plate with no oranges.',
            'activity_type' => 'spec_zero_plate',
            'data' => spec_act_data('spec_zero_plate', [
                'difficulty' => 1,
                'object' => 'orange'
            ]),
            'step_type' => 'warmup',
            'step_order' => 0,
            'difficulty' => 1
        ],
        // Activity 3.2: Drag empty pictures to Zero
        [
            'name' => 'Drag Empty to Zero',
            'desc' => 'Drag pictures with no objects into the Zero box.',
            'audio' => 'Drag the pictures with no objects to the box labeled Zero.',
            'activity_type' => 'spec_zero_drag',
            'data' => spec_act_data('spec_zero_drag', [
                'difficulty' => 1
            ]),
            'step_type' => 'we_do',
            'step_order' => 1,
            'difficulty' => 1
        ],
        // Activity 3.3: Tap number zero
        [
            'name' => 'Tap Number Zero',
            'desc' => 'Find and tap the number 0 from 0, 2, 5, 7.',
            'audio' => 'Tap number zero.',
            'activity_type' => 'spec_zero_tap',
            'data' => spec_act_data('spec_zero_tap', [
                'difficulty' => 1
            ]),
            'step_type' => 'check',
            'step_order' => 2,
            'difficulty' => 1
        ],
    ]
];

// LESSON 4: RECOGNISING NUMBER 10
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L04',
    'lesson_name' => 'Recognising Number 10',
    'description' => 'Identify, drag, match, and pop number 10.',
    'activities' => [
        // Activity 4.1: Tap number ten
        [
            'name' => 'Tap Number Ten',
            'desc' => 'Find and tap the number 10 from 7, 10, 4, 9.',
            'audio' => 'Tap number ten.',
            'activity_type' => 'spec_ten_tap',
            'data' => spec_act_data('spec_ten_tap', [
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 0,
            'difficulty' => 1
        ],
        // Activity 4.2: Drag number ten into yellow box
        [
            'name' => 'Drag Ten into Box',
            'desc' => 'A yellow box labeled 10 and numbers 6, 10, 8. Drag 10 into the yellow box.',
            'audio' => 'Drag number ten into the yellow box.',
            'activity_type' => 'spec_ten_drag',
            'data' => spec_act_data('spec_ten_drag', [
                'difficulty' => 1
            ]),
            'step_type' => 'we_do',
            'step_order' => 1,
            'difficulty' => 1
        ],
        // Activity 4.3: Match ten with group of ten apples (drag)
        [
            'name' => 'Match Ten with Apples',
            'desc' => 'Three groups: 8 apples, 10 apples, 6 apples. Drag number 10 to the group with 10 apples.',
            'audio' => 'Match number ten with the group that has ten apples.',
            'activity_type' => 'spec_ten_match',
            'data' => spec_act_data('spec_ten_match', [
                'difficulty' => 1,
                'object' => 'apple'
            ]),
            'step_type' => 'you_do',
            'step_order' => 2,
            'difficulty' => 1
        ],
        // Activity 4.4: Pop balloon with ten
        [
            'name' => 'Pop Balloon Ten',
            'desc' => 'Balloons labeled 5, 10, 7, 9. Pop the balloon with 10.',
            'audio' => 'Pop the balloon with number ten.',
            'activity_type' => 'spec_ten_balloon',
            'data' => spec_act_data('spec_ten_balloon', [
                'difficulty' => 1
            ]),
            'step_type' => 'game',
            'step_order' => 3,
            'difficulty' => 1
        ],
    ]
];

// --- Check for existing spec lessons to avoid duplicates ---
$existingSpec = $database->fetchOne(
    "SELECT COUNT(*) as cnt FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%'"
);
if ($existingSpec['cnt'] > 0) {
    echo "Spec lessons already exist (" . $existingSpec['cnt'] . " found).\n";
    echo "To re-run, delete existing NUM-SPEC-* lessons and their activities first.\n\n";
    echo "Run: DELETE FROM activities WHERE lesson_id IN (SELECT lesson_id FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%');\n";
    echo "Run: DELETE FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%';\n";
    exit(0);
}

// --- Insert lessons and activities ---
echo "Inserting " . count($lessons) . " spec lessons...\n\n";

$topicId = null;
$topicRow = $database->fetchOne(
    "SELECT topic_id FROM topics WHERE module_id = ? ORDER BY order_index DESC LIMIT 1",
    [$module_id]
);
if ($topicRow) {
    $topicId = (int)$topicRow['topic_id'];
}

foreach ($lessons as $lesson) {
    $lastLesson = $database->fetchOne(
        "SELECT MAX(order_index) as max_idx FROM lessons WHERE module_id = ?",
        [$module_id]
    );
    $lessonOrder = ($lastLesson['max_idx'] ?? 0) + 1;

    $dbResult = $database->execute(
        "INSERT INTO lessons (module_id, topic_id, lesson_code, lesson_name, description, order_index, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$module_id, $topicId, $lesson['lesson_code'], $lesson['lesson_name'], $lesson['description'], $lessonOrder]
    );

    if (!$dbResult) {
        echo "ERROR: Failed to create lesson '{$lesson['lesson_name']}'.\n";
        continue;
    }

    $lessonId = $database->lastInsertId();
    echo "  Lesson: {$lesson['lesson_name']} (ID: $lessonId)\n";

    foreach ($lesson['activities'] as $act) {
        $lastAct = $database->fetchOne(
            "SELECT MAX(order_index) as max_idx FROM activities WHERE lesson_id = ?",
            [$lessonId]
        );
        $orderIdx = ($lastAct['max_idx'] ?? -1) + 1;

        $dbResult2 = $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$module_id, $lessonId, $act['step_type'], $act['step_order'], $orderIdx, $act['name'], $act['desc'], $act['activity_type'], $act['difficulty'], $act['data'], $act['audio']]
        );

        if ($dbResult2) {
            $actId = $database->lastInsertId();
            echo "    + {$act['name']} (ID: $actId)\n";
        } else {
            echo "    ! ERROR: Failed to insert activity '{$act['name']}'.\n";
        }
    }
}

echo "\n=== Spec Update Complete! ===\n";
echo "4 lessons created:\n";
echo "  1. Count Objects and Read Numbers 1-5 (5 activities)\n";
echo "  2. Count Objects and Read Numbers 6-9 (4 activities)\n";
echo "  3. Recognising Number 0 (3 activities)\n";
echo "  4. Recognising Number 10 (4 activities)\n";
echo "Run the migration from your live server:\n";
echo "  php database/run_migration_spec_update.php\n";
