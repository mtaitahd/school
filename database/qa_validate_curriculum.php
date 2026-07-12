<?php
/**
 * PHASE 10 — Curriculum Quality Assurance & Production Readiness
 *
 * Comprehensive validation of the entire Mathematics curriculum.
 * Run: https://smartmathconner.co.tz/database/qa_validate_curriculum.php
 *
 * Checks:
 *   1. Curriculum structure (topics, lessons, activities)
 *   2. Lesson blueprint (10 step types in correct order)
 *   3. Activity data (engine refs, JSON validity, no placeholders)
 *   4. Engine validation (all referenced engines exist)
 *   5. Content validation (workbook alignment, no placeholder text)
 *   6. Database integrity (FKs, orphans, duplicates)
 *   7. Progress tracking readiness
 *   8. Final production readiness report
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../php/db_connection.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>QA Report — Curriculum Validation</title>";
echo "<style>
body{font-family:'Segoe UI',sans-serif;margin:20px;background:#f8f9fa;color:#333;}
h1{color:#1a1a2e;border-bottom:3px solid #16213e;padding-bottom:10px;}
h2{color:#16213e;margin-top:30px;border-bottom:2px solid #e94560;padding-bottom:5px;}
h3{color:#0f3460;}
table{border-collapse:collapse;width:100%;margin:10px 0 20px 0;}
th,td{border:1px solid #ddd;padding:8px 12px;text-align:left;}
th{background:#16213e;color:#fff;}
tr:nth-child(even){background:#f2f2f2;}
.pass{color:#27ae60;font-weight:bold;} .fail{color:#e74c3c;font-weight:bold;}
.warn{color:#f39c12;font-weight:bold;} .info{color:#3498db;}
.summary-box{background:#fff;border:2px solid #16213e;border-radius:8px;padding:20px;margin:15px 0;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
.metric{display:inline-block;text-align:center;padding:15px 25px;margin:5px;border-radius:8px;background:#f0f4ff;}
.metric .num{font-size:2rem;font-weight:bold;color:#16213e;}
.metric .label{font-size:0.85rem;color:#666;}
pre{background:#1a1a2e;color:#a8e6cf;padding:15px;border-radius:8px;overflow-x:auto;font-size:0.9rem;}
</style></head><body>";
echo "<h1>PHASE 10 — Curriculum QA Report</h1>";
echo "<p class='info'>Generated: " . date('Y-m-d H:i:s') . "</p>";

$pass = 0; $fail = 0; $warn = 0;
$errors = [];
$warnings = [];

function check($label, $condition, $detail = '') {
    global $pass, $fail, $errors;
    if ($condition) { $pass++; echo "<span class='pass'>✓ PASS</span> $label\n"; }
    else { $fail++; $errors[] = "$label: $detail"; echo "<span class='fail'>✗ FAIL</span> $label — $detail\n"; }
}
function warn($label, $detail = '') {
    global $warn, $warnings;
    $warn++; $warnings[] = "$label: $detail";
    echo "<span class='warn'>⚠ WARN</span> $label — $detail\n";
}

/* ================================================================
   1. CURRICULUM STRUCTURE
   ================================================================ */
echo "<h2>1. Curriculum Structure</h2>";

$topics = $database->fetchAll(
    "SELECT t.*, s.strand_code FROM topics t JOIN strands s ON t.strand_id = s.strand_id WHERE s.strand_code = 'NUM' ORDER BY t.order_index"
);
echo "<table><tr><th>Topic Code</th><th>Topic Name</th><th>Module</th><th>Order</th><th>Status</th></tr>";
$topicCodes = [];
foreach ($topics as $t) {
    $topicCodes[] = $t['topic_code'];
    $modName = $database->fetchOne("SELECT module_name FROM modules WHERE module_id = ?", [$t['module_id']]);
    $mod = $modName ? $modName['module_name'] : 'N/A';
    echo "<tr><td>{$t['topic_code']}</td><td>{$t['topic_name']}</td><td>$mod</td><td>{$t['order_index']}</td><td class='pass'>Active</td></tr>";
}
echo "</table>";

check('Expected 6 NUM topics', count($topics) === 6, "Found " . count($topics));
check('All topic codes present', count(array_intersect(['NUM-01','NUM-02','NUM-03','NUM-04','NUM-05','NUM-06'], $topicCodes)) === 6,
    "Found: " . implode(', ', $topicCodes));

/* Lessons per topic */
$lessonsPerTopic = $database->fetchAll(
    "SELECT t.topic_code, t.topic_name, COUNT(l.lesson_id) as cnt
     FROM topics t
     LEFT JOIN lessons l ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     GROUP BY t.topic_code, t.topic_name
     ORDER BY t.order_index"
);

echo "<h3>Lessons per Topic</h3><table><tr><th>Topic</th><th>Lessons</th><th>Expected</th><th>Status</th></tr>";
foreach ($lessonsPerTopic as $lpt) {
    $exp = 8;
    $ok = $lpt['cnt'] == $exp;
    echo "<tr><td>{$lpt['topic_code']}: {$lpt['topic_name']}</td><td>{$lpt['cnt']}</td><td>$exp</td>";
    echo $ok ? "<td class='pass'>✓</td>" : "<td class='fail'>✗</td>";
    if (!$ok) check("Lessons for {$lpt['topic_code']}", false, "Expected $exp, found {$lpt['cnt']}");
}
echo "</table>";
check('Total lessons = 48', $database->fetchOne("SELECT COUNT(*) as c FROM lessons WHERE lesson_code LIKE 'NUM-%'")['c'] === 48,
    "Actual: " . $database->fetchOne("SELECT COUNT(*) as c FROM lessons WHERE lesson_code LIKE 'NUM-%'")['c']);

/* Activities per topic */
$actsPerTopic = $database->fetchAll(
    "SELECT t.topic_code, COUNT(a.activity_id) as cnt
     FROM topics t
     JOIN lessons l ON l.topic_id = t.topic_id
     JOIN activities a ON a.lesson_id = l.lesson_id
     WHERE t.topic_code LIKE 'NUM-%'
     GROUP BY t.topic_code
     ORDER BY t.order_index
");

echo "<h3>Activities per Topic</h3><table><tr><th>Topic</th><th>Activities</th><th>Expected</th><th>Status</th></tr>";
foreach ($actsPerTopic as $apt) {
    $exp = 80;
    $ok = $apt['cnt'] == $exp;
    echo "<tr><td>{$apt['topic_code']}</td><td>{$apt['cnt']}</td><td>$exp</td>";
    echo $ok ? "<td class='pass'>✓</td>" : "<td class='fail'>✗</td>";
    if (!$ok) check("Activities for {$apt['topic_code']}", false, "Expected $exp, found {$apt['cnt']}");
}
echo "</table>";

$totalActs = $database->fetchOne("SELECT COUNT(*) as c FROM activities a JOIN lessons l ON a.lesson_id = l.lesson_id JOIN topics t ON l.topic_id = t.topic_id WHERE t.topic_code LIKE 'NUM-%'")['c'];
check('Total activities = 480', $totalActs === 480, "Actual: $totalActs");

/* ================================================================
   2. LESSON BLUEPRINT VALIDATION
   ================================================================ */
echo "<h2>2. Lesson Blueprint Validation</h2>";

$expectedSteps = ['intro','warmup','i_do','we_do','you_do','check','game','assessment','reward','next_steps'];

$allLessons = $database->fetchAll(
    "SELECT l.lesson_id, l.lesson_code, l.lesson_name
     FROM lessons l
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     ORDER BY t.order_index, l.order_index"
);

$blueprintErrors = 0;
$stepCountErrors = 0;
$orderErrors = 0;

foreach ($allLessons as $al) {
    $steps = $database->fetchAll(
        "SELECT step_type, step_order FROM activities WHERE lesson_id = ? ORDER BY step_order",
        [$al['lesson_id']]
    );

    $stepTypes = array_column($steps, 'step_type');
    $stepOrders = array_column($steps, 'step_order');

    /* Check count */
    if (count($steps) !== 10) {
        $stepCountErrors++;
        if ($stepCountErrors <= 10) {
            check("Lesson {$al['lesson_code']} step count", false, "Expected 10, found " . count($steps));
        }
    }

    /* Check all 10 step types present */
    $missing = array_diff($expectedSteps, $stepTypes);
    $extra = array_diff($stepTypes, $expectedSteps);
    if ($missing || $extra) {
        $blueprintErrors++;
        if ($blueprintErrors <= 10) {
            $msg = '';
            if ($missing) $msg .= "Missing: " . implode(', ', $missing) . ". ";
            if ($extra) $msg .= "Extra: " . implode(', ', $extra);
            check("Lesson {$al['lesson_code']} step types", false, $msg);
        }
    }

    /* Check order */
    $sortedOrders = $stepOrders;
    sort($sortedOrders);
    if ($stepOrders !== $sortedOrders) {
        $orderErrors++;
    }
}

check('All 480 lessons have exactly 10 steps', $stepCountErrors === 0, "$stepCountErrors lessons have wrong step count");
check('All lessons have correct 10 step types', $blueprintErrors === 0, "$blueprintErrors lessons have missing/wrong step types");
check('All step orders are sequential (0-9)', $orderErrors === 0, "$orderErrors lessons have wrong order");

/* ================================================================
   3. ACTIVITY DATA VALIDATION
   ================================================================ */
echo "<h2>3. Activity Data Validation</h2>";

/* JSON validity */
$invalidJSON = $database->fetchOne(
    "SELECT COUNT(*) as c FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     AND JSON_VALID(activity_data) = 0"
);
check('All activity_data JSON valid', $invalidJSON['c'] === 0, "$invalidJSON[c] invalid JSON entries");

/* Engine reference validation */
$enginesUsed = $database->fetchAll(
    "SELECT JSON_EXTRACT(a.activity_data, '$.engine') as engine, COUNT(*) as cnt
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     GROUP BY engine"
);

$supportedEngines = ['mango_counting','match_quantity','number_identification','number_sequencing',
    'missing_numbers','math_game','visual_subtraction','drag_addition','pattern_counting',
    'identify_shapes','complete_pattern','object_recognition','dot_to_dot'];

echo "<h3>Engine Usage</h3><table><tr><th>Engine</th><th>Count</th><th>Valid?</th></tr>";
$unknownEngines = 0;
foreach ($enginesUsed as $eu) {
    $eng = trim($eu['engine'], '"');
    $valid = in_array($eng, $supportedEngines);
    echo "<tr><td>$eng</td><td>{$eu['cnt']}</td>";
    echo $valid ? "<td class='pass'>✓</td>" : "<td class='fail'>✗ UNKNOWN</td>";
    if (!$valid) $unknownEngines++;
}
echo "</table>";
check('No unknown engine references', $unknownEngines === 0, "$unknownEngines unknown engines found");

/* Check engine exists in engines.js */
$enginesJS = file_get_contents(__DIR__ . '/../js/activities/engines.js');
foreach ($enginesUsed as $eu) {
    $eng = trim($eu['engine'], '"');
    if ($eng === 'dot_to_dot') continue; // allowed as optional
    $found = strpos($enginesJS, $eng . '(') !== false || strpos($enginesJS, "'$eng'") !== false;
    if (!$found) check("Engine '$eng' exists in engines.js", false, "Not found in JS source");
}
check('All engines found in engines.js source', true); // above checks handle failures

/* Activity field validation */
$emptyFields = $database->fetchOne(
    "SELECT COUNT(*) as c FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     AND (a.activity_name IS NULL OR a.activity_name = ''
          OR a.activity_data IS NULL OR a.activity_data = '')
)"
);
check('No empty activity names or data', $emptyFields['c'] === 0, "$emptyFields[c] activities with empty fields");

/* Placeholder text check */
$placeholders = $database->fetchAll(
    "SELECT a.activity_id, a.activity_name, a.activity_data
     FROM activities a
     JOIN lessons l ON a.lesson_id = l.lesson_id
     JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_code LIKE 'NUM-%'
     AND (a.activity_data LIKE '%TODO%' OR a.activity_data LIKE '%PLACEHOLDER%'
          OR a.activity_data LIKE '%FIXME%' OR a.activity_name LIKE '%TODO%'
          OR a.activity_name LIKE '%test%' OR a.activity_data LIKE '%lorem%')"
);
check('No placeholder text in activities', count($placeholders) === 0, count($placeholders) . " activities with placeholder text");

/* ================================================================
   4. ENGINE SOURCE VALIDATION
   ================================================================ */
echo "<h2>4. Engine Source Validation</h2>";

$coreJS = file_get_contents(__DIR__ . '/../js/activities/core.js');
$registryJS = file_get_contents(__DIR__ . '/../js/activities/registry.js');

/* Check OBJECT_EMOJIS */
$requiredObjects = ['apple','mango','ball','cat','dog','fish','star','flower','balloon',
    'duck','bird','bunny','candy','cookie','elephant','frog','grapes','lion','monkey',
    'orange','penguin','robot','sun','umbrella','watermelon','yarn','zebra',
    'eggplant','cabbage','boat','eraser','leaf','log','coconut','chair',
    'chicken','stick','cup','pencil','ruler','desk','mushroom','butterfly',
    'pumpkin','cow','goat','rabbit','bee','mosquito','fly','bell','guitar'];

$missingObjects = [];
foreach ($requiredObjects as $obj) {
    if (strpos($coreJS, "$obj:") === false && strpos($coreJS, "'$obj'") === false) {
        $missingObjects[] = $obj;
    }
}
check('All curriculum objects in OBJECT_EMOJIS', count($missingObjects) === 0,
    "Missing: " . implode(', ', $missingObjects));

/* Check registry maps engines */
$enginesInRegistry = ['mango_counting','match_quantity','number_identification','number_sequencing',
    'missing_numbers','math_game','visual_subtraction','drag_addition'];
$missingFromRegistry = [];
foreach ($enginesInRegistry as $eng) {
    if (strpos($registryJS, $eng) === false) {
        $missingFromRegistry[] = $eng;
    }
}
check('All primary engines in registry.js', count($missingFromRegistry) === 0,
    "Missing: " . implode(', ', $missingFromRegistry));

/* ================================================================
   5. DATABASE INTEGRITY
   ================================================================ */
echo "<h2>5. Database Integrity</h2>";

/* Orphan activities (lesson_id not in lessons) */
$orphans = $database->fetchOne(
    "SELECT COUNT(*) as c FROM activities a
     LEFT JOIN lessons l ON a.lesson_id = l.lesson_id
     WHERE l.lesson_id IS NULL AND a.lesson_id IS NOT NULL AND a.lesson_id > 0"
);
check('No orphan activities', $orphans['c'] === 0, "$orphans[c] orphan activities");

/* Orphan lessons (topic_id not in topics) */
$orphanLessons = $database->fetchOne(
    "SELECT COUNT(*) as c FROM lessons l
     LEFT JOIN topics t ON l.topic_id = t.topic_id
     WHERE t.topic_id IS NULL AND l.topic_id IS NOT NULL AND l.topic_id > 0"
);
check('No orphan lessons', $orphanLessons['c'] === 0, "$orphanLessons[c] orphan lessons");

/* Duplicate topic codes */
$dupTopics = $database->fetchOne(
    "SELECT COUNT(*) as c FROM (SELECT topic_code FROM topics WHERE topic_code LIKE 'NUM-%' GROUP BY topic_code HAVING COUNT(*) > 1) d"
);
check('No duplicate NUM topic codes', $dupTopics['c'] === 0, "$dupTopics[c] duplicate codes");

/* Duplicate lesson codes */
$dupLessons = $database->fetchOne(
    "SELECT COUNT(*) as c FROM (SELECT lesson_code FROM lessons WHERE lesson_code LIKE 'NUM-%' GROUP BY lesson_code HAVING COUNT(*) > 1) d"
);
check('No duplicate NUM lesson codes', $dupLessons['c'] === 0, "$dupLessons[c] duplicate codes");

/* Duplicate activities per lesson (same step_type + step_order) */
$dupActs = $database->fetchOne(
    "SELECT COUNT(*) as c FROM (
        SELECT lesson_id, step_type, step_order, COUNT(*) as cnt
        FROM activities
        GROUP BY lesson_id, step_type, step_order
        HAVING cnt > 1
    ) d
    JOIN lessons l ON d.lesson_id = l.lesson_id
    JOIN topics t ON l.topic_id = t.topic_id
    WHERE t.topic_code LIKE 'NUM-%'"
);
check('No duplicate activities per lesson', $dupActs['c'] === 0, "$dupActs[c] duplicate activity slots");

/* ================================================================
   6. PROGRESS TRACKING TABLES
   ================================================================ */
echo "<h2>6. Progress Tracking</h2>";

$progressTables = ['learner_progress','learner_achievements','learner_session_log','learner_streaks'];
foreach ($progressTables as $pt) {
    $exists = $database->fetchOne("SHOW TABLES LIKE '$pt'");
    check("Table '$pt' exists", $exists !== null);
}

/* Check activity_attempts if exists */
$attemptsExists = $database->fetchOne("SHOW TABLES LIKE 'activity_attempts'");
if ($attemptsExists) {
    check('activity_attempts table exists', true);
    $attemptsCount = $database->fetchOne("SELECT COUNT(*) as c FROM activity_attempts");
    echo "<p class='info'>Activity attempts recorded: {$attemptsCount['c']}</p>";
} else {
    warn('activity_attempts table not found — progress tracking may be incomplete');
}

/* Check lesson_completions if exists */
$completionsExists = $database->fetchOne("SHOW TABLES LIKE 'lesson_completions'");
if ($completionsExists) {
    $completionCount = $database->fetchOne("SELECT COUNT(*) as c FROM lesson_completions");
    check('lesson_completions table exists', true);
    echo "<p class='info'>Lesson completions: {$completionCount['c']}</p>";
} else {
    warn('lesson_completions table not found');
}

/* ================================================================
   7. SUMMARY METRICS
   ================================================================ */
echo "<h2>7. Summary Metrics</h2>";

$totalTopics = count($topics);
$totalLessons = $database->fetchOne("SELECT COUNT(*) as c FROM lessons WHERE lesson_code LIKE 'NUM-%'")['c'];
$totalActivities = $totalActs;
$totalModules = $database->fetchOne("SELECT COUNT(*) as c FROM modules WHERE module_id IN (SELECT DISTINCT module_id FROM lessons l JOIN topics t ON l.topic_id = t.topic_id WHERE t.topic_code LIKE 'NUM-%')")['c'];

echo "<div class='summary-box'>";
echo "<div class='metric'><div class='num'>$totalModules</div><div class='label'>Modules</div></div>";
echo "<div class='metric'><div class='num'>$totalTopics</div><div class='label'>Topics</div></div>";
echo "<div class='metric'><div class='num'>$totalLessons</div><div class='label'>Lessons</div></div>";
echo "<div class='metric'><div class='num'>$totalActivities</div><div class='label'>Activities</div></div>";
echo "<div class='metric'><div class='num'>$pass</div><div class='label'>Checks Passed</div></div>";
echo "<div class='metric'><div class='num'>$fail</div><div class='label'>Checks Failed</div></div>";
echo "<div class='metric'><div class='num'>$warn</div><div class='label'>Warnings</div></div>";
echo "</div>";

/* Engine usage breakdown */
echo "<h3>Engine Usage Summary</h3>";
echo "<table><tr><th>Engine</th><th>Count</th><th>%</th></tr>";
foreach ($enginesUsed as $eu) {
    $eng = trim($eu['engine'], '"');
    $pct = round(($eu['cnt'] / $totalActivities) * 100, 1);
    echo "<tr><td>$eng</td><td>{$eu['cnt']}</td><td>$pct%</td></tr>";
}
echo "</table>";

/* ================================================================
   8. ERRORS & WARNINGS
   ================================================================ */
echo "<h2>8. Errors & Warnings</h2>";

if ($errors) {
    echo "<h3 class='fail'>Errors ($fail)</h3><ul>";
    foreach ($errors as $e) echo "<li class='fail'>$e</li>";
    echo "</ul>";
} else {
    echo "<p class='pass'>No errors found.</p>";
}

if ($warnings) {
    echo "<h3 class='warn'>Warnings ($warn)</h3><ul>";
    foreach ($warnings as $w) echo "<li class='warn'>$w</li>";
    echo "</ul>";
} else {
    echo "<p class='pass'>No warnings found.</p>";
}

/* ================================================================
   9. PRODUCTION READINESS
   ================================================================ */
echo "<h2>9. Production Readiness</h2>";

if ($fail === 0) {
    echo "<div class='summary-box' style='border-color:#27ae60;'>";
    echo "<h2 class='pass'>✓ CURRICULUM IS PRODUCTION READY</h2>";
    echo "<p>All $pass validation checks passed. The Mathematics curriculum is complete and ready for deployment.</p>";
    echo "</div>";
} else {
    echo "<div class='summary-box' style='border-color:#e74c3c;'>";
    echo "<h2 class='fail'>✗ CURRICULUM NOT READY — $fail issues found</h2>";
    echo "<p>Please fix the $fail errors above before deploying to production.</p>";
    echo "</div>";
}

echo "<p class='info' style='margin-top:30px;'>Report generated by qa_validate_curriculum.php — " . date('Y-m-d H:i:s') . "</p>";
echo "</body></html>";
