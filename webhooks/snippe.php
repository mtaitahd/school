<?php
/**
 * Snippe Payment Webhook Handler
 * 
 * Endpoint: https://yourdomain.com/webhooks/snippe
 * This is the ONLY trusted source of payment confirmation.
 * NEVER trust frontend payment success alone.
 */

require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/subscription.php';
require_once __DIR__ . '/../php/includes/payment.php';

pay_process_webhook();
