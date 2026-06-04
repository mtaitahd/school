<?php
/**
 * Migration v8: Subscription & Payment System
 * 
 * Adds:
 * - subscriptions table (trial + recurring)
 * - payments table (Snippe + manual)
 * - wallet table (topup balance)
 * - parent_phone column index
 * - trial_start / trial_end columns on subscriptions
 */
 
$db->execute("
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

$db->execute("
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

$db->execute("
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

// Add trial_start and trial_end to users table for quick check
try {
    $db->execute("ALTER TABLE `users` ADD COLUMN `trial_start` DATETIME DEFAULT NULL AFTER `parent_claimed`");
} catch (Exception $e) {
    // Column may already exist
}
try {
    $db->execute("ALTER TABLE `users` ADD COLUMN `trial_end` DATETIME DEFAULT NULL AFTER `trial_start`");
} catch (Exception $e) {
    // Column may already exist
}
try {
    $db->execute("ALTER TABLE `users` ADD COLUMN `subscription_status` ENUM('trial','active','expired','cancelled') NOT NULL DEFAULT 'trial' AFTER `trial_end`");
} catch (Exception $e) {
    // Column may already exist
}

echo "Migration v8 completed: subscriptions, payments, wallet tables created.\n";
