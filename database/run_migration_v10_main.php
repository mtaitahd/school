<?php
/**
 * Phase 5 — Main: Run all NUM-02 migrations
 *
 * Usage: php database/run_migration_v10_main.php
 */
require_once __DIR__ . '/../php/db_connection.php';

echo "=== Phase 5: Recognising Number 0 (NUM-02) ===\n\n";

/* Run the fix/upsert which handles everything */
require __DIR__ . '/run_migration_v10_fix.php';

echo "\n=== Phase 5 Complete ===\n";
