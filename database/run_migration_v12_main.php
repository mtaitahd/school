<?php
/**
 * Phase 7 — Main: Run all NUM-04 migrations
 *
 * Usage: php database/run_migration_v12_main.php
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 7: Counting Objects and Numbers 11–20 (NUM-04) ===\n\n";

/* Run the fix/upsert which handles everything */
require __DIR__ . '/run_migration_v12_fix.php';

echo "\n=== Phase 7 Complete ===\n";
