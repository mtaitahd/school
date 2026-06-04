<?php
/**
 * AJAX Payment Status Check Endpoint
 * Returns JSON with current payment status
 */

require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/payment.php';

sec_require_rate_limit();

header('Content-Type: application/json');

$ref = $_GET['ref'] ?? '';
if (!$ref) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing reference']);
    exit;
}

$parentId = auth_user_id();
if (!$parentId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
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

$status = $payment['status'];

// If pending and older than 10 seconds, verify with Snippe API
if ($status === 'pending' && $payment['method'] !== 'manual') {
    $created = strtotime($payment['created_at']);
    $elapsed = time() - $created;
    if ($elapsed > 10) {
        $verifyResult = pay_verify_snippe_payment($payment['reference']);
        if ($verifyResult['verified']) {
            $database->execute(
                "UPDATE `payments` SET status = 'completed', api_response = ? WHERE id = ?",
                [json_encode($verifyResult['data']), $payment['id']]
            );
            $status = 'completed';
        } elseif ($verifyResult['status'] === 'failed') {
            $database->execute(
                "UPDATE `payments` SET status = 'failed', api_response = ? WHERE id = ?",
                [json_encode($verifyResult['data']), $payment['id']]
            );
            $status = 'failed';
        }
    }
}

// Build response
$response = [
    'status' => $status,
    'reference' => $payment['reference'],
    'transaction_id' => $payment['transaction_id'],
    'amount' => number_format((float) $payment['amount']) . ' ' . $payment['currency'],
    'method' => $payment['method'],
    'payment_type' => $payment['payment_type'],
    'created_at' => $payment['created_at'],
];

// Determine message based on status
if ($status === 'completed') {
    $response['message'] = 'Malipo yamefanikiwa.';
    $response['icon'] = 'check-circle';
    $response['color'] = 'success';
} elseif ($status === 'failed') {
    $response['message'] = 'Malipo hayajakamilika. Tafadhali jaribu tena.';
    $response['icon'] = 'times-circle';
    $response['color'] = 'danger';
} elseif ($status === 'manual_review') {
    $response['message'] = 'Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.';
    $response['icon'] = 'clock';
    $response['color'] = 'warning';
} else {
    $response['message'] = 'Tunasubiri uthibitisho wa malipo...';
    $response['icon'] = 'spinner fa-pulse';
    $response['color'] = 'primary';
}

echo json_encode($response);
