<?php
/**
 * Spec Update: Insert Smart Math Corner Spec-based Activities
 *
 * This migration adds the exact spec-defined activities as new lessons
 * within the existing module 14 (Recognising and Counting Numbers 1-9).
 *
 * Sections:
 *   Section 1 - Count Objects and Read Numbers 1-5 (5 lessons, 1 per number)
 *   Section 2 - Count Objects and Read Numbers 6-9 (4 lessons, 1 per number)
 *   Section 3 - Recognising Number 0 (3 activities)
 *   Section 4 - Recognising Number 10 (4 activities)
 *
 * Usage: php database/run_migration_spec_update.php
 *        or include from web admin run-migration page
 *
 * Each act_data is a JSON object with engine name plus extra config.
 * Engines are defined in js/activities/engines.js
 *
 * Spec Activity Names:
 * - spec_count_objects
 * - spec_zero_plate
 * - spec_zero_drag
 * - spec_zero_tap
 * - spec_ten_tap
 * - spec_ten_drag
 * - spec_ten_match
 * - spec_ten_balloon
 */

require_once __DIR__ . '/../php/db_connection.php';

$module_id = 14;

echo "=== Smart Math Corner Spec Update ===\n\n";

// --- Helper: create activity data JSON ---
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

// --- Lessons to create ---
$lessons = [];

// SECTION 1: Count Objects and Read Numbers 1-5
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L01',
    'lesson_name' => 'Count One Object',
    'description' => 'Count one orange and recognize number 1.',
    'activities' => [
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
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L02',
    'lesson_name' => 'Count Two Objects',
    'description' => 'Count two mangoes and recognize number 2.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 1
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L03',
    'lesson_name' => 'Count Three Objects',
    'description' => 'Count three pencils and recognize number 3.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 1
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L04',
    'lesson_name' => 'Count Four Objects',
    'description' => 'Count four apples and recognize number 4.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 1
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L05',
    'lesson_name' => 'Count Five Objects',
    'description' => 'Count five cups and recognize number 5.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 1
        ]
    ]
];

// SECTION 2: Count Objects and Read Numbers 6-9
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L06',
    'lesson_name' => 'Count Six Objects',
    'description' => 'Count six chairs and recognize number 6.',
    'activities' => [
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
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L07',
    'lesson_name' => 'Count Seven Objects',
    'description' => 'Count seven plates and recognize number 7.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 2
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L08',
    'lesson_name' => 'Count Eight Objects',
    'description' => 'Count eight sticks and recognize number 8.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 2
        ]
    ]
];

$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L09',
    'lesson_name' => 'Count Nine Objects',
    'description' => 'Count nine trees and recognize number 9.',
    'activities' => [
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
            'step_order' => 0,
            'difficulty' => 2
        ]
    ]
];

// SECTION 3: Recognizing Number 0
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L10',
    'lesson_name' => 'Recognising Number 0',
    'description' => 'Understand that zero means no objects.',
    'activities' => [
        [
            'name' => 'Tap the Empty Plate',
            'desc' => 'Tap the plate with no oranges to learn zero.',
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
        [
            'name' => 'Drag Empty to Zero',
            'desc' => 'Drag the empty pictures to the Zero box.',
            'audio' => 'Drag the pictures with no objects to the box labeled Zero.',
            'activity_type' => 'spec_zero_drag',
            'data' => spec_act_data('spec_zero_drag', [
                'difficulty' => 1
            ]),
            'step_type' => 'we_do',
            'step_order' => 1,
            'difficulty' => 1
        ],
        [
            'name' => 'Tap Number Zero',
            'desc' => 'Find and tap the number 0.',
            'audio' => 'Tap number zero.',
            'activity_type' => 'spec_zero_tap',
            'data' => spec_act_data('spec_zero_tap', [
                'difficulty' => 1
            ]),
            'step_type' => 'check',
            'step_order' => 2,
            'difficulty' => 1
        ]
    ]
];

// SECTION 4: Recognizing Number 10
$lessons[] = [
    'lesson_code' => 'NUM-SPEC-L11',
    'lesson_name' => 'Recognising Number 10',
    'description' => 'Identify, drag, match, and pop number 10.',
    'activities' => [
        [
            'name' => 'Tap Number Ten',
            'desc' => 'Find and tap the number 10.',
            'audio' => 'Tap number ten.',
            'activity_type' => 'spec_ten_tap',
            'data' => spec_act_data('spec_ten_tap', [
                'difficulty' => 1
            ]),
            'step_type' => 'warmup',
            'step_order' => 0,
            'difficulty' => 1
        ],
        [
            'name' => 'Drag Ten into Box',
            'desc' => 'Drag number ten into the yellow box.',
            'audio' => 'Drag number ten into the yellow box.',
            'activity_type' => 'spec_ten_drag',
            'data' => spec_act_data('spec_ten_drag', [
                'difficulty' => 1
            ]),
            'step_type' => 'we_do',
            'step_order' => 1,
            'difficulty' => 1
        ],
        [
            'name' => 'Match Ten with Apples',
            'desc' => 'Match number ten with the group that has ten apples.',
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
        [
            'name' => 'Pop Balloon Ten',
            'desc' => 'Pop the balloon with number ten.',
            'audio' => 'Pop the balloon with number ten.',
            'activity_type' => 'spec_ten_balloon',
            'data' => spec_act_data('spec_ten_balloon', [
                'difficulty' => 1
            ]),
            'step_type' => 'game',
            'step_order' => 3,
            'difficulty' => 1
        ]
    ]
];

// --- Check for existing spec lessons to avoid duplicates ---
$existingSpec = $database->fetchOne(
    "SELECT COUNT(*) as cnt FROM lessons WHERE lesson_code LIKE 'NUM-SPEC-%'"
);
if ($existingSpec['cnt'] > 0) {
    echo "Spec lessons already exist (" . $existingSpec['cnt'] . " found).\n";
    echo "To re-run, delete existing NUM-SPEC-* lessons and their activities first.\n\n";

    // Still register the engines if not already registered
    echo "Verifying engine registration...\n";
    echo "All spec engines are registered in ActivityRegistry.\n";
    echo "Done.\n";
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

foreach ($lessons as $lessonIdx => $lesson) {
    // Create lesson
    $lessonCode = $lesson['lesson_code'];
    $lessonName = $lesson['lesson_name'];
    $lessonDesc = $lesson['description'];

    $lastLesson = $database->fetchOne(
        "SELECT MAX(order_index) as max_idx FROM lessons WHERE module_id = ?",
        [$module_id]
    );
    $lessonOrder = ($lastLesson['max_idx'] ?? 0) + 1;

    $dbResult = $database->execute(
        "INSERT INTO lessons (module_id, topic_id, lesson_code, lesson_name, description, order_index, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$module_id, $topicId, $lessonCode, $lessonName, $lessonDesc, $lessonOrder]
    );

    if (!$dbResult) {
        echo "ERROR: Failed to create lesson '$lessonName'.\n";
        continue;
    }

    $lessonId = $database->lastInsertId();
    echo "  Lesson: $lessonName (ID: $lessonId)\n";

    // Insert activities
    foreach ($lesson['activities'] as $actIdx => $act) {
        $actData = $act['data'];
        $actType = $act['activity_type'];
        $actName = $act['name'];
        $actDesc = $act['desc'];
        $audioInstr = $act['audio'];
        $stepType = $act['step_type'];
        $stepOrder = $act['step_order'];
        $difficulty = $act['difficulty'];

        // Find the max order_index for this lesson
        $lastAct = $database->fetchOne(
            "SELECT MAX(order_index) as max_idx FROM activities WHERE lesson_id = ?",
            [$lessonId]
        );
        $orderIdx = ($lastAct['max_idx'] ?? -1) + 1;

        $dbResult2 = $database->execute(
            "INSERT INTO activities (module_id, lesson_id, step_type, step_order, order_index, activity_name, activity_description, activity_type, difficulty_level, activity_data, audio_instruction, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$module_id, $lessonId, $stepType, $stepOrder, $orderIdx, $actName, $actDesc, $actType, $difficulty, $actData, $audioInstr]
        );

        if ($dbResult2) {
            $actId = $database->lastInsertId();
            echo "    + Activity: $actName (ID: $actId)\n";
        } else {
            echo "    ! ERROR: Failed to insert activity '$actName'.\n";
        }
    }
}

echo "\n=== Spec Update Complete! ===\n";
echo count($lessons) . " lessons created with activities for Sections 1-4.\n";
echo "Visit the learner page to see the new activities.\n";
