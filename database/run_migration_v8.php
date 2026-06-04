<?php
/**
 * Database Migration Runner for v8 Subscription & Payment System
 * Run this file in your browser to execute the migration
 */

require_once __DIR__ . '/../php/db_connection.php';

echo "<h1>Kona Ya Hisabati - Database Migration v8</h1>";
echo "<h2>Subscription & Payment System</h2>";

try {
    // Check if subscriptions table already exists
    $check = $database->fetchOne("SHOW TABLES LIKE 'subscriptions'");
    
    if ($check) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Migration already completed!</strong> The subscriptions table already exists.";
        echo "</div>";
        echo "<p><a href='../admin/dashboard.php'>Go to Admin Dashboard</a></p>";
        exit;
    }

    // Create subscriptions table
    echo "<p>Creating subscriptions table...</p>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS `subscriptions` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `parent_id` INT NOT NULL,
            `status` ENUM('trial','active','expired','cancelled','past_due') NOT NULL DEFAULT 'trial',
            `trial_start` DATETIME DEFAULT NULL,
            `trial_end` DATETIME DEFAULT NULL,
            `current_period_start` DATETIME DEFAULT NULL,
            `current_period_end` DATETIME DEFAULT NULL,
            `payment_method` ENUM('snippe','manual','none') NOT NULL DEFAULT 'none',
            `last_payment_id` INT UNSIGNED DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            INDEX `idx_parent_id` (`parent_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ subscriptions table created</p>";

    // Create payments table
    echo "<p>Creating payments table...</p>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `parent_id` INT NOT NULL,
            `subscription_id` INT UNSIGNED DEFAULT NULL,
            `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `currency` VARCHAR(10) NOT NULL DEFAULT 'TZS',
            `method` ENUM('snippe','manual') NOT NULL,
            `payment_type` ENUM('subscription','topup') NOT NULL DEFAULT 'subscription',
            `phone` VARCHAR(20) DEFAULT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `transaction_id` VARCHAR(100) DEFAULT NULL,
            `reference` VARCHAR(100) DEFAULT NULL,
            `status` ENUM('pending','completed','failed','manual_review','refunded') NOT NULL DEFAULT 'pending',
            `admin_note` TEXT DEFAULT NULL,
            `api_response` TEXT DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions`(`id`) ON DELETE SET NULL,
            INDEX `idx_parent` (`parent_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_transaction` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ payments table created</p>";

    // Create wallet table
    echo "<p>Creating wallet table...</p>";
    $database->execute("
        CREATE TABLE IF NOT EXISTS `wallet` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `parent_id` INT NOT NULL UNIQUE,
            `balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`parent_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
            INDEX `idx_parent` (`parent_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p style='color: green;'>✓ wallet table created</p>";

    // Add trial columns to users table
    echo "<p>Adding trial columns to users table...</p>";
    try {
        $database->execute("ALTER TABLE `users` ADD COLUMN `trial_start` DATETIME DEFAULT NULL AFTER `parent_claimed`");
        echo "<p style='color: green;'>✓ trial_start column added</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>- trial_start already exists</p>";
    }
    try {
        $database->execute("ALTER TABLE `users` ADD COLUMN `trial_end` DATETIME DEFAULT NULL AFTER `trial_start`");
        echo "<p style='color: green;'>✓ trial_end column added</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>- trial_end already exists</p>";
    }
    try {
        $database->execute("ALTER TABLE `users` ADD COLUMN `subscription_status` ENUM('trial','active','expired','cancelled') NOT NULL DEFAULT 'trial' AFTER `trial_end`");
        echo "<p style='color: green;'>✓ subscription_status column added</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>- subscription_status already exists</p>";
    }

    // Update sms_logs enum to include payment types
    echo "<p>Updating sms_logs sms_type enum...</p>";
    try {
        $database->execute("ALTER TABLE `sms_logs` MODIFY COLUMN `sms_type` ENUM('assignment','performance','parent_link','fee_payment','general','payment_success','subscription_reminder') NOT NULL DEFAULT 'general'");
        echo "<p style='color: green;'>✓ sms_logs sms_type updated</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>- Could not update sms_logs enum: " . $e->getMessage() . "</p>";
    }

    // Initialize trial for existing parent users who don't have a subscription
    echo "<p>Initializing trial for existing parent accounts...</p>";
    $existingParents = $database->fetchAll("
        SELECT u.user_id FROM users u
        LEFT JOIN subscriptions s ON s.parent_id = u.user_id
        WHERE u.role = 'parent' AND s.id IS NULL
    ");
    foreach ($existingParents as $parent) {
        require_once __DIR__ . '/../php/includes/subscription.php';
        sub_init_trial((int) $parent['user_id']);
    }
    echo "<p style='color: green;'>✓ Initialized trial for " . count($existingParents) . " existing parents</p>";

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Migration v8 completed successfully!</strong>";
    echo "</div>";
    echo "<p><a href='../admin/dashboard.php'>Go to Admin Dashboard</a></p>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}
