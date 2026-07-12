<?php
/**
 * Phase 7b — Create Separate Modules for Number 0, Number 10, Numbers 11–20
 *
 * Currently all NUM topics (1-9, 0, 10, 11-20) share module_id=14.
 * This migration creates separate modules so each chapter appears
 * as its own category on the home page.
 *
 * Usage: php database/run_migration_v13_modules.php
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 7b: Create Separate Number Modules ===\n\n";

/* 1. Check current state */
$current = $database->fetchAll("SELECT module_id, module_name, order_index FROM modules ORDER BY order_index");
echo "Current modules:\n";
foreach ($current as $m) {
    echo "  ID={$m['module_id']} | {$m['module_name']} | idx={$m['order_index']}\n";
}

/* 2. Get max order_index */
$maxOrder = (int)$database->fetchOne("SELECT MAX(order_index) as mx FROM modules")['mx'];
echo "\nMax order_index = $maxOrder\n";

/* 3. Create new modules */
$newModules = [
    [
        'name' => 'Number Zero',
        'desc' => 'Learn to recognise, trace, and find the number 0',
        'icon' => 'fa-circle',
        'color' => '#9B59B6',
        'audio' => 'Touch here for Number Zero!',
    ],
    [
        'name' => 'Number Ten',
        'desc' => 'Learn to recognise, read, write, and count to 10',
        'icon' => 'fa-hands-helping',
        'color' => '#E67E22',
        'audio' => 'Touch here for Number Ten!',
    ],
    [
        'name' => 'Numbers 11–20',
        'desc' => 'Count objects and learn numbers 11 to 20',
        'icon' => 'fa-sort-numeric-up-alt',
        'color' => '#1ABC9C',
        'audio' => 'Touch here for Numbers 11 to 20!',
    ],
];

$moduleMap = []; // topic_code prefix => new module_id
$idx = $maxOrder;

foreach ($newModules as $nm) {
    $idx++;
    $database->execute(
        "INSERT IGNORE INTO modules (module_name, module_description, module_icon, module_color, audio_prompt, order_index, is_active)
         VALUES (?, ?, ?, ?, ?, ?, 1)",
        [$nm['name'], $nm['desc'], $nm['icon'], $nm['color'], $nm['audio'], $idx]
    );
    $row = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = ?", [$nm['name']]);
    if ($row) {
        echo "  Created module: {$nm['name']} → module_id={$row['module_id']}\n";
    }
}

/* 4. Map topic codes to modules */
$num0 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Zero'");
$num10 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Number Ten'");
$num1120 = $database->fetchOne("SELECT module_id FROM modules WHERE module_name = 'Numbers 11–20'");

if (!$num0 || !$num10 || !$num1120) {
    echo "ERROR: Could not find new modules\n";
    exit(1);
}

$moduleMap['NUM-02'] = (int)$num0['module_id'];
$moduleMap['NUM-03'] = (int)$num10['module_id'];
$moduleMap['NUM-04'] = (int)$num1120['module_id'];

echo "\nModule map:\n";
echo "  NUM-02 (Number 0) → module_id={$moduleMap['NUM-02']}\n";
echo "  NUM-03 (Number 10) → module_id={$moduleMap['NUM-03']}\n";
echo "  NUM-04 (Numbers 11-20) → module_id={$moduleMap['NUM-04']}\n";

/* 5. Move topics to new modules */
foreach ($moduleMap as $topicCode => $newModuleId) {
    $topics = $database->fetchAll("SELECT topic_id, topic_name FROM topics WHERE topic_code = ?", [$topicCode]);
    foreach ($topics as $t) {
        $database->execute("UPDATE topics SET module_id = ? WHERE topic_id = ?", [$newModuleId, $t['topic_id']]);
        echo "  Moved topic {$t['topic_code']} ({$t['topic_name']}) → module_id=$newModuleId\n";
    }
}

/* 6. Move activities to new modules via lessons */
foreach ($moduleMap as $topicCode => $newModuleId) {
    $lessons = $database->fetchAll("SELECT lesson_id, lesson_code FROM lessons WHERE lesson_code LIKE ?", [$topicCode . '-%']);
    $lessonIds = array_column($lessons, 'lesson_id');
    if (empty($lessonIds)) continue;

    $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
    $params = array_merge([$newModuleId], $lessonIds);
    $database->execute("UPDATE activities SET module_id = ? WHERE lesson_id IN ($placeholders)", $params);

    $cnt = (int)$database->fetchOne("SELECT COUNT(*) as cnt FROM activities WHERE module_id = ? AND lesson_id IN ($placeholders)", $params)['cnt'];
    echo "  Moved $cnt activities from " . count($lessons) . " lessons → module_id=$newModuleId\n";
}

/* 7. Verify */
echo "\n=== Verification ===\n";
$verify = $database->fetchAll("
    SELECT m.module_id, m.module_name, COUNT(a.activity_id) as act_count
    FROM modules m
    LEFT JOIN activities a ON a.module_id = m.module_id
    WHERE m.module_name IN ('Recognising and Counting Numbers 1-9', 'Number Zero', 'Number Ten', 'Numbers 11–20')
    GROUP BY m.module_id
    ORDER BY m.order_index
");
foreach ($verify as $v) {
    echo "  {$v['module_name']}: {$v['act_count']} activities\n";
}

/* 8. Show all modules */
echo "\n=== All Modules (Home Page) ===\n";
$all = $database->fetchAll("
    SELECT m.module_id, m.module_name, m.module_icon, m.module_color, COUNT(a.activity_id) as act_count
    FROM modules m
    LEFT JOIN activities a ON a.module_id = m.module_id
    WHERE m.is_active = 1
    GROUP BY m.module_id
    ORDER BY m.order_index
");
foreach ($all as $a) {
    echo "  ID={$a['module_id']} | {$a['module_name']} | {$a['module_icon']} | {$a['module_color']} | {$a['act_count']} activities\n";
}

echo "\nDone.\n";
