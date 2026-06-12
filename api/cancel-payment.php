<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/payment.php';

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
}

echo json_encode([
    'ok' => true,
    'status' => 'cancelled',
    'reference' => $payment['reference'],
    'api_cancelled' => $cancelledByApi,
    'message' => 'Malipo yameghairiwa.',
]);
