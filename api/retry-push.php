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

$payment = $database->fetchOne(
    "SELECT * FROM `payments` WHERE reference = ? AND parent_id = ? LIMIT 1",
    [$ref, $parentId]
);

if (!$payment) {
    http_response_code(404);
    echo json_encode(['error' => 'Payment not found']);
    exit;
}

if ($payment['status'] !== 'pending') {
    echo json_encode(['error' => 'Payment is not in pending status']);
    exit;
}

if ($payment['method'] !== 'snippe') {
    echo json_encode(['error' => 'Only mobile money payments can retry push']);
    exit;
}

// Determine the Snippe reference for the push API
// Push endpoint uses {id} (pymt_xxx format), not the reference UUID
$snippeRef = $payment['transaction_id'];
$apiResp = $payment['api_response'] ? json_decode($payment['api_response'], true) : null;
if ($apiResp) {
    $respData = $apiResp['data'] ?? $apiResp;
    // First try the payment ID (push endpoint uses {id} not {reference})
    $paymentIdFromApi = $respData['id'] ?? $apiResp['payment_id'] ?? $apiResp['payment']['id'] ?? null;
    if ($paymentIdFromApi) {
        $snippeRef = $paymentIdFromApi;
    } else {
        // Fallback to reference/UUID
        $snippeRef = $respData['reference']
            ?? $apiResp['transaction_id']
            ?? $snippeRef
            ?? null;
    }
}
if (!$snippeRef) {
    $snippeRef = $payment['reference'];
}

if ($result['success']) {
    echo json_encode(['ok' => true, 'message' => 'USSD push resent to your phone']);
} else {
    echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Failed to retry push']);
}
