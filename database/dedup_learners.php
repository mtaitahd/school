<?php
/**
 * Deduplicate Learners
 *
 * Finds learners with the same first_name + last_name (case-insensitive)
 * and removes duplicates, keeping the oldest record (earliest created_at).
 *
 * Usage:
 *   php database/dedup_learners.php              -- dry run (shows what would be deleted)
 *   php database/dedup_learners.php --execute     -- actually deletes duplicates
 *
 * Safe: all foreign keys reference users(user_id) with ON DELETE CASCADE,
 *       so related records (parent_student_links, assignments, etc.) are cleaned up automatically.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(0);

require_once __DIR__ . '/../php/db_connection.php';

$execute = in_array('--execute', $argv);

echo "<pre>\n";
echo "=== DEDUP LEARNERS ===\n";
echo "Mode: " . ($execute ? "LIVE DELETE" : "DRY RUN (use --execute to delete)") . "\n\n";

/* ----------------------------------------------------------------
   Step 1: Find duplicate groups
   ---------------------------------------------------------------- */
$duplicates = $database->fetchAll("
    SELECT LOWER(TRIM(first_name)) AS fn, LOWER(TRIM(last_name)) AS ln,
           COUNT(*) AS cnt
    FROM users
    WHERE role = 'learner'
    GROUP BY fn, ln
    HAVING cnt > 1
    ORDER BY cnt DESC
");

$totalGroups = count($duplicates);
$totalExtra = 0;

echo "Found {$totalGroups} duplicate groups\n\n";

if ($totalGroups === 0) {
    echo "No duplicates found. Exiting.\n";
    exit(0);
}

/* ----------------------------------------------------------------
   Step 2: For each group, keep the oldest, delete the rest
   ---------------------------------------------------------------- */
$deleted = 0;
$errors = 0;

foreach ($duplicates as $dup) {
    $firstName = $dup['fn'];
    $lastName = $dup['ln'];
    $count = (int)$dup['cnt'];

    // Fetch all matching learners ordered by created_at (oldest first)
    $learners = $database->fetchAll("
        SELECT user_id, username, first_name, last_name, parent_id, created_at
        FROM users
        WHERE role = 'learner'
          AND LOWER(TRIM(first_name)) = ?
          AND LOWER(TRIM(last_name)) = ?
        ORDER BY created_at ASC, user_id ASC
    ", [$firstName, $lastName]);

    if (count($learners) < 2) continue;

    $keep = $learners[0];
    $remove = array_slice($learners, 1);
    $totalExtra += count($remove);

    echo str_repeat('-', 60) . "\n";
    echo "GROUP: {$keep['first_name']} {$keep['last_name']} ({$count} records)\n";
    echo "  KEEP:  user_id={$keep['user_id']}  username={$keep['username']}  created={$keep['created_at']}\n";
    foreach ($remove as $r) {
        echo "  DEL:   user_id={$r['user_id']}  username={$r['username']}  created={$r['created_at']}\n";
    }

    if ($execute) {
        foreach ($remove as $r) {
            try {
                $database->execute("DELETE FROM users WHERE user_id = ?", [(int)$r['user_id']]);
                $deleted++;
                echo "  -> Deleted user_id={$r['user_id']}\n";
            } catch (\Exception $e) {
                $errors++;
                echo "  -> ERROR deleting user_id={$r['user_id']}: " . $e->getMessage() . "\n";
            }
        }
    }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "SUMMARY:\n";
echo "  Duplicate groups:     {$totalGroups}\n";
echo "  Records to remove:    {$totalExtra}\n";
if ($execute) {
    echo "  Successfully deleted: {$deleted}\n";
    echo "  Errors:               {$errors}\n";
    echo "\nDone! Duplicates removed.\n";
} else {
    echo "\nDry run complete. Run with --execute to actually delete.\n";
}
echo "</pre>\n";
