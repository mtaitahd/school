<?php
/**
 * Part C: Update existing lesson names to match curriculum spec.
 * The old lessons grouped numbers (L01=1,2,3) — the spec requires one-per-lesson.
 *
 * Run: php database/run_migration_v9c.php
 */
require_once __DIR__ . '/../php/includes/migrate.php';
require_once __DIR__ . '/../php/db_connection.php';
$db = new Database();

echo "=== Update Lessons to Match Spec ===\n";

$updates = [
  ['NUM-01-L01','Recognising Number 1','Colour, count, trace, and match number 1.','Identifies number 1, counts one object, traces 1, matches one object.',15,1],
  ['NUM-01-L02','Recognising Number 2','Colour, count, trace, and match number 2.','Identifies number 2, counts two objects, traces 2, matches quantity 2.',15,2],
  ['NUM-01-L03','Recognising Number 3','Shape of number 3, count objects to 3.','Identifies number 3 shape, counts three objects.',15,3],
  ['NUM-01-L04','Recognising Number 4','Counting objects to 4, matching numbers.','Counts objects to 4, matches number to quantity.',15,4],
  ['NUM-01-L05','Recognising Number 5','Butterfly counting, trace and write 5.','Counts butterflies 1-5, traces and writes number 5.',15,5],
  ['NUM-01-L06','Recognising Number 6','Animal counting, object matching for 6.','Counts rabbits, goats, ducks; matches quantity 6.',15,6],
  ['NUM-01-L07','Recognising Numbers 7 and 8','Books, erasers, animals for 7 and 8.','Counts books and erasers to 7-8, matches quantities.',20,7],
  ['NUM-01-L08','Recognising Number 9','Rabbits, missing numbers, number games.','Counts rabbits to 9, finds missing numbers, plays number games.',20,8],
];

foreach ($updates as [$code, $name, $obj, $criteria, $mins, $idx]) {
  $db->execute(
    "UPDATE lessons SET lesson_name=?, learning_objective=?, success_criteria=?, estimated_minutes=?, order_index=? WHERE lesson_code=?",
    [$name, $obj, $criteria, $mins, $idx, $code]
  );
  echo "  Updated $code => $name\n";
}

echo "\nDone. Lessons now match curriculum spec.\n";
