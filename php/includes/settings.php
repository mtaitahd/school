<?php

function setting_get(string $key, string $default = ''): string {
    global $database;
    try {
        $row = $database->fetchOne(
            "SELECT setting_value FROM `settings` WHERE setting_key = ?",
            [$key]
        );
        return $row ? $row['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

function is_payment_enabled(): bool {
    return setting_get('payment_enabled', '1') === '1';
}

function is_start_learning_restricted(): bool {
    return setting_get('start_learning_restricted', '0') === '1';
}
