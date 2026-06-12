<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/payment.php';
require_once __DIR__ . '/../php/includes/url_helpers.php';

sec_require_rate_limit();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$parentId = auth_user_id();
if (!$parentId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$ref = $_POST['ref'] ?? '';
if (!$ref) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing reference']);
    exit;
}

csrf_require();

$payment = $database->fetchOne(
    "SELECT * FROM `payments` WHERE reference = ? AND parent_id = ? LIMIT 1",
    [$ref, $parentId]
);

if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found']);
    exit;
}

if (!in_array($payment['status'], ['pending', 'manual_review'])) {
    echo json_encode(['error' => 'Payment cannot be cancelled']);
    exit;
}

$cancelledByApi = false;
if ($payment['method'] === 'snippe' && $payment['transaction_id']) {
    $cancelResult = pay_cancel_snippe_payment((int) $payment['id'], $payment['transaction_id']);
    $cancelledByApi = $cancelResult['api_cancelled'];
} else {
    $database->execute(
        "UPDATE `payments` SET status = 'failed', admin_note = 'cancelled_by_user' WHERE id = ? AND parent_id = ?",
        [$payment['id'], $parentId]
    );
    // Notify admins of cancelled payment
    pay_notify_admins('cancelled', $payment['reference'], (float) $payment['amount'], $payment['currency'] ?? 'TZS', $payment['phone']);
}

// Notify the user (parent) about cancellation
if (!empty($payment['phone'])) {
    try {
        require_once __DIR__ . '/../php/sms_service.php';
        $sms = new SmsService();
        $msg = 'Smart Math Corner: Malipo yako yameghairiwa. Rejea: ' . $payment['reference'] . '. Kiasi: ' . number_format((float) $payment['amount']) . ' ' . ($payment['currency'] ?? 'TZS') . '.';
        $sms->sendSMS($payment['phone'], $msg, 'payment_cancelled', 'user', $parentId);
    } catch (Exception $e) {
        error_log('User cancel SMS notification failed: ' . $e->getMessage());
    }
}

echo json_encode([
    'ok' => true,
    'status' => 'cancelled',
    'reference' => $payment['reference'],
    'api_cancelled' => $cancelledByApi,
    'message' => 'Malipo yameghairiwa.',
    'redirect' => app_web_path('parent/dashboard.php'),
]);
