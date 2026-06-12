<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();
header('Content-Type: application/json');

$adminOnly = auth_role() !== 'admin';
if ($adminOnly) {
    http_response_code(403);
    echo json_encode(['error' => 'Admins only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$phone = $_POST['phone'] ?? '';
if (!$phone) {
    $phone = '+255616591639';
}

try {
    require_once __DIR__ . '/../php/sms_service.php';
    $sms = new SmsService();
    $result = $sms->sendSMS($phone, 'Smart Math Corner: SMS service test. If you receive this, SMS is working!', 'test', 'admin', 0);
    echo json_encode([
        'sent' => $result['success'] ?? false,
        'message' => $result['message'] ?? 'Unknown response',
        'phone' => $phone,
        'response' => $result,
    ]);
} catch (Exception $e) {
    echo json_encode([
        'sent' => false,
        'error' => $e->getMessage(),
        'phone' => $phone,
    ]);
}
