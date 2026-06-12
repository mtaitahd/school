<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/payment.php';
require_once __DIR__ . '/../php/includes/subscription.php';
require_once __DIR__ . '/../php/includes/csrf.php';

header('Content-Type: application/json');

auth_require_role(['admin'], '../login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

csrf_require();

$updated = 0;
$reminded = 0;
$errors = 0;
$details = [];
$error_detail = '';

$paymentId = (int) ($_POST['payment_id'] ?? 0);

if ($paymentId) {
    $pendingPayments = $database->fetchAll(
        "SELECT p.*, u.first_name, u.last_name, u.phone AS parent_phone
         FROM `payments` p
         JOIN `users` u ON p.parent_id = u.user_id
         WHERE p.status = 'pending' AND p.method = 'snippe' AND p.transaction_id IS NOT NULL
         AND p.id = ?
         ORDER BY p.created_at ASC",
        [$paymentId]
    );
} else {
    $pendingPayments = $database->fetchAll(
        "SELECT p.*, u.first_name, u.last_name, u.phone AS parent_phone
         FROM `payments` p
         JOIN `users` u ON p.parent_id = u.user_id
         WHERE p.status = 'pending' AND p.method = 'snippe' AND p.transaction_id IS NOT NULL
         ORDER BY p.created_at ASC"
    );
}

foreach ($pendingPayments as $payment) {
    $transactionId = $payment['transaction_id'];
    $ref = $payment['reference'];

    try {
        // Verify with Snippe API
        $verifyResult = pay_verify_snippe_payment($transactionId);

        if ($verifyResult['verified']) {
            // Payment is completed — update DB and activate subscription
            $database->execute(
                "UPDATE `payments` SET status = 'completed', api_response = ? WHERE id = ?",
                [json_encode($verifyResult['data']), $payment['id']]
            );
            sub_activate_after_payment((int) $payment['parent_id'], (int) $payment['id'], 'snippe');
            pay_notify_admins('completed', $ref, (float) $payment['amount'], $payment['currency'] ?? 'TZS', $payment['phone']);

            // Notify parent
            if (!empty($payment['parent_phone'])) {
                try {
                    require_once __DIR__ . '/../php/sms_service.php';
                    $sms = new SmsService();
                    $msg = 'Smart Math Corner: Malipo yako yamekamilika. Sasa unaweza kuendelea na masomo. Asante!';
                    $sms->sendSMS($payment['parent_phone'], $msg, 'payment_completed', 'parent', (int) $payment['parent_id']);
                } catch (Exception $e) {
                    error_log('Verify SMS notify failed: ' . $e->getMessage());
                }
            }

            $updated++;
            $details[] = "Payment #{$payment['id']} ($ref): completed";
        } elseif ($verifyResult['status'] === 'failed') {
            // Still failed — send reminder
            $database->execute(
                "UPDATE `payments` SET api_response = ? WHERE id = ?",
                [json_encode($verifyResult['data']), $payment['id']]
            );

            if (!empty($payment['parent_phone'])) {
                try {
                    require_once __DIR__ . '/../php/sms_service.php';
                    $sms = new SmsService();
                    $failureReason = $verifyResult['failure_reason'] ? "Sababu: {$verifyResult['failure_reason']}. " : '';
                    $msg = "Smart Math Corner: Malipo yako bado hayajakamilika. {$failureReason}Tafadhali jaribu tena au wasiliana nasi.";
                    $sms->sendSMS($payment['parent_phone'], $msg, 'payment_reminder', 'parent', (int) $payment['parent_id']);
                } catch (Exception $e) {
                    error_log('Reminder SMS failed: ' . $e->getMessage());
                }
            }

            $reminded++;
            $details[] = "Payment #{$payment['id']} ($ref): still failed, reminder sent";
        } else {
            $errors++;
            $error_detail = "Unexpected status: {$verifyResult['status']}";
            $details[] = "Payment #{$payment['id']} ($ref): unexpected status ({$verifyResult['status']})";
        }
    } catch (Exception $e) {
        $errors++;
        $error_detail = $e->getMessage();
        $details[] = "Payment #{$payment['id']} ($ref): error - " . $e->getMessage();
    }
}

echo json_encode([
    'ok' => true,
    'updated' => $updated,
    'reminded' => $reminded,
    'errors' => $errors,
    'error_detail' => $error_detail,
    'total' => count($pendingPayments),
    'details' => $details,
    'message' => "Imekamilika: $updated zimesasishwa, $reminded zimetumwa ukumbusho, $errors hitilafu."
]);
