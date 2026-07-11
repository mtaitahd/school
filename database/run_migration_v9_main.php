<?php
echo "=== Phase 1: Schema & Structure ===\n";
require __DIR__ . "/run_migration_v9a.php";
echo "\n=== Phase 2: Activity Data ===\n";
require __DIR__ . "/run_migration_v9_fix.php";
echo "\nMigration v9 complete.\n";
