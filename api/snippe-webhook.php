<?php
/**
 * Snippe Payment Webhook Handler
 * Called by Snippe API after payment completion
 */

require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/subscription.php';
require_once __DIR__ . '/../php/includes/payment.php';

pay_process_webhook();
