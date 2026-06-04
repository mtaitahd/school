<?php
/**
 * Database Migration Runner for v4 Claim Code System
 * Run this file in your browser to execute the migration
 */

require_once '__DIR__ . '/../php/db_connection.php';

echo "<h1>Kona Ya Hisabati - Database Migration v4</h1>";
echo "<h2>Parent Claim Code System</h2>";

try {
    // Check if columns already exist
    $check_columns = $database->fetchAll("SHOW COLUMNS FROM users LIKE 'claim_code'");
    
    if (!empty($check_columns)) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Migration already completed!</strong> The claim code columns already exist in the users table.";
        echo "</div>";
        echo "<p><a href='../teacher/dashboard.php'>Go to Teacher Dashboard</a></p>";
        exit;
    }
    
    // Add parent_phone column
    echo "<p>Adding parent_phone column...</p>";
    $database->execute("ALTER TABLE users ADD COLUMN parent_phone VARCHAR(20) NULL AFTER phone");
    echo "<p style='color: green;'>âœ“ parent_phone column added</p>";
    
    // Add claim_code column
    echo "<p>Adding claim_code column...</p>";
    $database->execute("ALTER TABLE users ADD COLUMN claim_code VARCHAR(20) NULL AFTER parent_phone");
    echo "<p style='color: green;'>âœ“ claim_code column added</p>";
    
    // Add parent_claimed column
    echo "<p>Adding parent_claimed column...</p>";
    $database->execute("ALTER TABLE users ADD COLUMN parent_claimed TINYINT(1) DEFAULT 0 AFTER claim_code");
    echo "<p style='color: green;'>âœ“ parent_claimed column added</p>";
    
    // Add claim_code_created_at column
    echo "<p>Adding claim_code_created_at column...</p>";
    $database->execute("ALTER TABLE users ADD COLUMN claim_code_created_at TIMESTAMP NULL AFTER parent_claimed");
    echo "<p style='color: green;'>âœ“ claim_code_created_at column added</p>";
    
    // Add indexes
    echo "<p>Adding indexes...</p>";
    $database->execute("ALTER TABLE users ADD INDEX idx_claim_code (claim_code)");
    $database->execute("ALTER TABLE users ADD INDEX idx_parent_claimed (parent_claimed)");
    echo "<p style='color: green;'>âœ“ Indexes added</p>";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Migration completed successfully!</strong>";
    echo "</div>";
    
    echo "<p>The Parent Claim Code System is now ready to use.</p>";
    echo "<p><a href='../teacher/dashboard.php'>Go to Teacher Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}
?>



