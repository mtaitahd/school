<?php
/**
 * Phase 6 — Main: Run all NUM-03 migrations
 *
 * Usage: php database/run_migration_v11_main.php
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 6: Recognising Number 10 (NUM-03) ===\n\n";

/* Run the fix/upsert which handles everything */
require __DIR__ . '/run_migration_v11_fix.php';

echo "\n=== Phase 6 Complete ===\n";
