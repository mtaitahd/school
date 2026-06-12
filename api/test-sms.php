<?php
/**
 * Test SMS endpoint — accessible via browser GET.
 * Returns detailed debug info about SMS service configuration and send attempt.
 */
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();
header('Content-Type: application/json');

if (!auth_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized. Please login first.']);
    exit;
}

$phone = $_REQUEST['phone'] ?? '+255616591639';
$msg = $_REQUEST['msg'] ?? 'Smart Math Corner: SMS service test. If you receive this, SMS is working!';

$debug = [
    'phone_input' => $phone,
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_id' => auth_user_id(),
    'role' => auth_role(),
];

try {
    require_once __DIR__ . '/../php/sms_service.php';
    $sms = new SmsService();

    $phoneValid = $sms->validatePhone($phone);
    $debug['phone_valid'] = $phoneValid;
    $debug['phone_normalized'] = $sms->normalizePhone($phone);

    if (!$phoneValid) {
        echo json_encode([
            'sent' => false,
            'error' => 'Invalid phone number format. Expected +255XXXXXXXXX (Tanzania)',
            'debug' => $debug,
        ]);
        exit;
    }

    $result = $sms->sendSMS($phone, $msg, 'test', 'admin', 0);
    $debug['api_response'] = $result;

    echo json_encode([
        'sent' => $result['success'] ?? false,
        'queued' => $result['queued'] ?? null,
        'message' => $result['message'] ?? 'Unknown response',
        'phone' => $phone,
        'debug' => $debug,
    ]);
} catch (Exception $e) {
    $debug['exception'] = $e->getMessage();
    echo json_encode([
        'sent' => false,
        'error' => $e->getMessage(),
        'phone' => $phone,
        'debug' => $debug,
    ]);
}
