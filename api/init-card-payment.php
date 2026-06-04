<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/payment.php';
require_once __DIR__ . '/../php/includes/SnippeCardPaymentService.php';

header('Content-Type: application/json');
sec_send_headers();
sec_require_rate_limit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$parentId = auth_user_id();
if (!auth_has_role('parent')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
if (!csrf_verify($token)) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'CSRF token validation failed']);
    exit;
}

$paymentType = $_POST['payment_type'] ?? 'subscription';
$email = trim($_POST['email'] ?? '');
$customAmount = (float) ($_POST['amount'] ?? 0);

$service = new SnippeCardPaymentService($database);
$result = $service->createPayment($parentId, $email, $paymentType, $customAmount);

if ($result['success']) {
    echo json_encode([
        'ok' => true,
        'payment_url' => $result['payment_url'],
        'reference' => $result['reference'],
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => $result['error'],
    ]);
}
