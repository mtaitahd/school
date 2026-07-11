<?php
echo "=== Phase 1 ===\n";
require __DIR__ . "/run_migration_v9a.php";
echo "\n=== Phase 2 ===\n";
require __DIR__ . "/run_migration_v9b.php";
echo "\nMigration v9 complete.\n";
