<?php
/**
 * Payment integration: Snippe API (2026-01-25) + Manual payments
 *
 * Snippe API: https://api.snippe.sh/v1/payments
 * Auth: Bearer <API_KEY>
 * Idempotency-Key header prevents duplicate charges
 * Webhooks are the ONLY source of truth for payment success
 */

define('SNIPPE_API_BASE', 'https://api.snippe.sh/v1');
define('SNIPPE_API_KEY', sec_env('SNIPPE_API_KEY', ''));

define('MANUAL_PAYMENT_NUMBER', '440783070');
define('MANUAL_PAYMENT_NAME', 'Smart Math Corner');
define('MANUAL_PAYMENT_NETWORK', 'Mix by Yas Lipa');

const SUBSCRIPTION_AMOUNT = 1500;
const SUBSCRIPTION_CURRENCY = 'TZS';

const SNIPPE_IP_WHITELIST = [
    '34.xxx.xxx.xxx',
    '35.xxx.xxx.xxx',
];

function pay_idempotency_key(string $reference): string {
    return 'pay-' . $reference;
}

function pay_normalize_phone(string $phone): string {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) === 9) {
        return '255' . $digits;
    }
    if (strlen($digits) === 10 && $digits[0] === '0') {
        return '255' . substr($digits, 1);
    }
    if (strlen($digits) === 12 && substr($digits, 0, 3) === '255') {
        return $digits;
    }
    if (strlen($digits) === 13 && substr($digits, 0, 4) === '2550') {
        return '255' . substr($digits, 4);
    }
    return $digits;
}

function pay_create_snippe_payment(int $parentId, string $phone, string $email = '', string $type = 'subscription'): array {
    global $database;

    $amount = ($type === 'wallet_topup') ? (float) ($_POST['amount'] ?? SUBSCRIPTION_AMOUNT) : SUBSCRIPTION_AMOUNT;
    $reference = strtoupper(bin2hex(random_bytes(8)));
    $idempotencyKey = pay_idempotency_key($reference);
    $rawAppUrl = rtrim(sec_env('APP_URL', 'https://smartmathconner.co.tz'), '/');
    // Never allow localhost URLs in production API calls
    if (preg_match('/localhost/i', $rawAppUrl)) {
        $rawAppUrl = 'https://smartmathconner.co.tz';
    }
    $appUrl = preg_replace('/^http:/i', 'https:', $rawAppUrl);

    $user = $database->fetchOne("SELECT first_name, last_name, email FROM `users` WHERE user_id = ?", [$parentId]);
    $firstName = $user['first_name'] ?? 'Parent';
    $lastName = $user['last_name'] ?? 'User';
    $userEmail = trim($user['email'] ?? '');
    if ($userEmail === '') {
        $userEmail = $email !== '' ? $email : 'parent' . $parentId . '@smartmathconner.co.tz';
    }

    $phone = pay_normalize_phone($phone);

    $paymentId = $database->insert(
        "INSERT INTO `payments` (parent_id, amount, currency, method, payment_type, phone, email, reference, status)
         VALUES (?, ?, ?, 'snippe', ?, ?, ?, ?, 'pending')",
        [$parentId, $amount, SUBSCRIPTION_CURRENCY, $type, $phone, $userEmail, $reference]
    );

    $webhookUrl = $appUrl . '/webhooks/snippe';
    $webhookUrl = preg_replace('/^http:/i', 'https:', $webhookUrl);

    $metadata = [
        'parent_id' => (string) $parentId,
        'payment_id' => (string) $paymentId,
        'type' => $type,
    ];

    $payload = [
        'payment_type' => 'mobile',
        'details' => [
            'amount' => (int) $amount,
            'currency' => SUBSCRIPTION_CURRENCY,
        ],
        'phone_number' => $phone,
        'customer' => [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $userEmail,
        ],
        'webhook_url' => $webhookUrl,
        'metadata' => $metadata,
    ];

    $ch = curl_init(SNIPPE_API_BASE . '/payments');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SNIPPE_API_KEY,
            'Idempotency-Key: ' . $idempotencyKey,
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;

    if ($curlError || $httpCode >= 400 || !$data) {
        $errorMsg = $curlError;
        if ($response && !$data) {
            $data = ['raw_response' => $response];
        }
        if (!$errorMsg && $data) {
            $errorMsg = $data['message'] ?? $data['error'] ?? json_encode($data['errors'] ?? $data) ?: ('HTTP ' . $httpCode);
        }
        if (!$errorMsg) {
            $errorMsg = 'Payment API error (HTTP ' . $httpCode . ')';
        }
        $database->execute(
            "UPDATE `payments` SET status = 'failed', api_response = ? WHERE id = ?",
            [json_encode(['error' => $errorMsg, 'http_code' => $httpCode, 'response' => $response]), $paymentId]
        );
        return ['success' => false, 'error' => $errorMsg];
    }

    // Try to extract the Snippe identifiers from the response
    // - reference/UUID: used for verify (GET) and void endpoints
    // - id (pymt_xxx): used for push endpoint (POST /v1/payments/{id}/push)
    $respData = $data['data'] ?? $data;
    $transactionId = $respData['reference']
        ?? $data['transaction_id']
        ?? $respData['id']
        ?? $respData['payment_id']
        ?? $data['payment']['reference']
        ?? $data['payment']['id']
        ?? $data['id']
        ?? null;
    // Also capture the payment ID (push endpoint uses {id}, verify/void use {reference})
    $snippePaymentId = $respData['id'] !== ($transactionId ?? '')
        ? ($respData['id'] ?? null)
        : null;
    $paymentUrl = $respData['payment_url'] ?? $data['checkout_url'] ?? $data['redirect_url'] ?? null;

    // Always save api_response so we can debug identifier extraction
    $database->execute(
        "UPDATE `payments` SET api_response = ? WHERE id = ?",
        [json_encode($data), $paymentId]
    );

    if ($transactionId) {
        $database->execute(
            "UPDATE `payments` SET transaction_id = ? WHERE id = ?",
            [$transactionId, $paymentId]
        );

        // Use specific payment ID for push if available, otherwise use the reference
        $pushRef = $snippePaymentId ?: $transactionId;
        $pushResult = pay_retry_push($pushRef);
        if (!$pushResult['success']) {
            error_log('Snippe initial push failed for payment ' . $reference . ' (push_ref ' . $pushRef . ', snippe_ref ' . $transactionId . '): ' . ($pushResult['error'] ?? 'unknown'));
        }
    } else {
        error_log('Snippe create payment success but NO transaction_id extracted for local ref ' . $reference . '. API response: ' . substr(json_encode($data), 0, 1000));
    }

    return [
        'success' => true,
        'payment_id' => $paymentId,
        'reference' => $reference,
        'transaction_id' => $transactionId,
        'payment_url' => $paymentUrl,
        'message' => $respData['message'] ?? $data['message'] ?? 'Payment initiated',
    ];
}

function pay_retry_push(string $reference): array {
    $ch = curl_init(SNIPPE_API_BASE . '/payments/' . urlencode($reference) . '/push');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SNIPPE_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $responseBody = $response ? substr($response, 0, 500) : '(empty)';
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;

    if ($curlError || $httpCode >= 400) {
        $errMsg = $curlError ?: ('HTTP ' . $httpCode . ' — ' . $responseBody);
        error_log('Snippe retry-push failed for ref ' . $reference . ': ' . $errMsg);
        return ['success' => false, 'error' => $errMsg, 'http_code' => $httpCode, 'response' => $responseBody];
    }

    error_log('Snippe retry-push success for ref ' . $reference . ' (HTTP ' . $httpCode . ')');
    return ['success' => true, 'data' => $data];
}

function pay_verify_snippe_payment(string $reference): array {
    $ch = curl_init(SNIPPE_API_BASE . '/payments/' . urlencode($reference));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SNIPPE_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;

    if ($httpCode >= 400 || !$data) {
        return ['verified' => false, 'error' => 'Verification failed'];
    }

    $respData = $data['data'] ?? $data;

    return [
        'verified' => ($respData['status'] ?? '') === 'completed',
        'status' => $respData['status'] ?? 'unknown',
        'data' => $data,
    ];
}

function pay_create_manual_payment(int $parentId, string $phone, string $transactionId): array {
    global $database;
    $reference = 'MANUAL-' . $parentId . '-' . time();

    $paymentId = $database->insert(
        "INSERT INTO `payments` (parent_id, amount, currency, method, payment_type, phone, reference, transaction_id, status)
         VALUES (?, ?, ?, 'manual', 'subscription', ?, ?, ?, 'manual_review')",
        [$parentId, SUBSCRIPTION_AMOUNT, SUBSCRIPTION_CURRENCY, $phone, $reference, $transactionId]
    );

    return ['success' => true, 'payment_id' => $paymentId, 'reference' => $reference];
}

/**
 * Process incoming Snippe webhook.
 * Supports both legacy (2026-01-01) and new (2026-01-25) envelope formats.
 * Only call this from /webhooks/snippe — never trust frontend alone.
 */
function pay_process_webhook(): void {
    global $database;

    $rawBody = file_get_contents('php://input');
    $timestamp = $_SERVER['HTTP_X_WEBHOOK_TIMESTAMP'] ?? '';
    $signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? $_SERVER['HTTP_X_SNIPPE_SIGNATURE'] ?? '';
    $eventHeader = $_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? '';

    // Signature verification — new format: hex(HMAC-SHA256(key, "{timestamp}.{raw_body}"))
    $webhookSecret = sec_env('SNIPPE_WEBHOOK_SECRET', '');
    if ($webhookSecret !== '') {
        if ($timestamp !== '' && $signature !== '') {
            // New format (2026-01-25)
            $currentTime = time();
            if (abs($currentTime - (int)$timestamp) > 300) {
                error_log('Snippe webhook: timestamp too old');
                http_response_code(401);
                exit('Timestamp too old');
            }
            $message = $timestamp . '.' . $rawBody;
            $expectedSig = hash_hmac('sha256', $message, $webhookSecret);
            if (!hash_equals($expectedSig, $signature)) {
                error_log('Snippe webhook: invalid signature');
                http_response_code(401);
                exit('Invalid signature');
            }
        } else {
            // Legacy format — signature over raw body
            $expectedSig = hash_hmac('sha256', $rawBody, $webhookSecret);
            if (!hash_equals($expectedSig, $signature)) {
                error_log('Snippe webhook: invalid signature (legacy)');
                http_response_code(401);
                exit('Invalid signature');
            }
        }
    }

    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remoteIp !== '' && !in_array($remoteIp, SNIPPE_IP_WHITELIST, true)) {
        error_log('Snippe webhook: untrusted IP ' . $remoteIp);
        http_response_code(401);
        exit('Untrusted IP');
    }

    $input = json_decode($rawBody, true);
    if (!$input) {
        http_response_code(400);
        exit('Invalid payload');
    }

    // Detect format: new (2026-01-25) envelope or legacy flat
    $event = $eventHeader ?: $input['type'] ?? $input['event'] ?? '';
    $data = $input['data'] ?? $input;
    $reference = $data['reference'] ?? $input['reference'] ?? '';
    $transactionId = $data['transaction_id'] ?? $data['id'] ?? $input['transaction_id'] ?? '';
    $paymentStatus = $data['status'] ?? $input['status'] ?? '';

    // Parse amount from new format object: {"value": 50000, "currency": "TZS"}
    $amountValue = null;
    if (is_array($data['amount'] ?? null)) {
        $amountValue = $data['amount']['value'] ?? null;
    } elseif (is_array($input['amount'] ?? null)) {
        $amountValue = $input['amount']['value'] ?? null;
    }

    if (!$reference) {
        http_response_code(400);
        exit('Missing reference');
    }

    $payment = $database->fetchOne(
        "SELECT * FROM `payments` WHERE reference = ? OR transaction_id = ? LIMIT 1",
        [$reference, $reference]
    );

    if (!$payment) {
        error_log('Snippe webhook: payment not found for reference/transaction_id ' . $reference);
        http_response_code(404);
        exit('Payment not found');
    }

    $parentId = (int) $payment['parent_id'];
    $newStatus = $payment['status'];
    $isCompleted = false;
    $isVoided = false;

    // Normalise event name (strip "payment." prefix if present in type field)
    $eventName = str_replace('payment.', '', $event);
    $eventName = $eventName ?: $paymentStatus;

    if ($eventName === 'completed' || $paymentStatus === 'completed') {
        $newStatus = 'completed';
        $isCompleted = true;
    } elseif ($eventName === 'failed' || $paymentStatus === 'failed') {
        $newStatus = 'failed';
    } elseif ($eventName === 'voided') {
        $newStatus = 'failed';
        $isVoided = true;
    } elseif ($eventName === 'expired') {
        $newStatus = 'failed';
    }

    // Update amount from webhook if provided (new format sends amount as object)
    $amountUpdate = '';
    $amountParams = [];
    if ($amountValue !== null) {
        $amountUpdate = ', amount = ?';
        $amountParams = [(int) $amountValue];
    }

    $database->execute(
        "UPDATE `payments` SET status = ?, transaction_id = COALESCE(?, transaction_id), api_response = ?" . $amountUpdate . " WHERE id = ?",
        array_merge([$newStatus, $transactionId, json_encode($input)], $amountParams, [$payment['id']])
    );

    if ($isCompleted) {
        $paymentType = $payment['payment_type'];

        if ($paymentType === 'subscription') {
            sub_activate_after_payment($parentId, (int) $payment['id'], 'snippe');
        } elseif ($paymentType === 'wallet_topup') {
            pay_topup_wallet($parentId, (float) ($amountValue ?? $payment['amount']));
        }

        try {
            require_once __DIR__ . '/../sms_service.php';
            $sms = new SmsService();
            $user = $database->fetchOne("SELECT phone FROM `users` WHERE user_id = ?", [$parentId]);
            if ($user && $user['phone']) {
                $msg = ($paymentType === 'subscription')
                    ? "Smart Math Corner: Malipo yako ya " . number_format((float) ($amountValue ?? $payment['amount'])) . " TZS yamethibitishwa. Uanachama wako umeanzishwa kwa siku 30. Karibu!"
                    : "Smart Math Corner: Umefanikiwa kujaza salio la wallet yako. Kiasi: " . number_format((float) ($amountValue ?? $payment['amount'])) . " TZS.";
                $sms->sendSMS($user['phone'], $msg, 'payment_success', 'parent', $parentId);
            }
        } catch (Exception $e) {
            error_log('Payment SMS error: ' . $e->getMessage());
        }

        // Notify admins of completed payment
        pay_notify_admins('completed', $reference, (float) ($amountValue ?? $payment['amount']), 'TZS', $payment['phone']);

    } elseif ($isVoided) {
        error_log('Snippe webhook: payment ' . $reference . ' voided by user');
        // Notify admins of cancelled payment
        pay_notify_admins('cancelled', $payment['reference'] ?: $reference ?: 'unknown', (float) ($amountValue ?? $payment['amount']), 'TZS', $payment['phone']);
    }

    http_response_code(200);
    exit('OK');
}

function pay_verify_manual(int $paymentId, string $action = 'approve'): bool {
    global $database;
    $payment = $database->fetchOne("SELECT * FROM `payments` WHERE id = ?", [$paymentId]);
    if (!$payment || $payment['status'] !== 'manual_review') return false;

    if ($action === 'approve') {
        $database->execute("UPDATE `payments` SET status = 'completed' WHERE id = ?", [$paymentId]);
        sub_activate_after_payment((int) $payment['parent_id'], $paymentId, 'manual');

        try {
            require_once __DIR__ . '/../sms_service.php';
            $sms = new SmsService();
            $user = $database->fetchOne("SELECT phone FROM `users` WHERE user_id = ?", [(int) $payment['parent_id']]);
            if ($user && $user['phone']) {
                $msg = "Smart Math Corner: Malipo yako ya " . number_format((float) $payment['amount']) . " TZS yamethibitishwa. Uanachama wako umeanzishwa kwa siku 30. Karibu!";
                $sms->sendSMS($user['phone'], $msg, 'payment_success', 'parent', (int) $payment['parent_id']);
            }
        } catch (Exception $e) {
            error_log('Manual approve SMS error: ' . $e->getMessage());
        }

        // Notify admins of manual approval
        pay_notify_admins('completed', $payment['reference'], (float) $payment['amount'], 'TZS', $payment['phone']);

        return true;
    }

    $database->execute("UPDATE `payments` SET status = 'failed' WHERE id = ?", [$paymentId]);

    try {
        require_once __DIR__ . '/../sms_service.php';
        $sms = new SmsService();
        $user = $database->fetchOne("SELECT phone FROM `users` WHERE user_id = ?", [(int) $payment['parent_id']]);
        if ($user && $user['phone']) {
            $msg = "Smart Math Corner: Samahani, malipo yako ya " . number_format((float) $payment['amount']) . " TZS hayakukubaliwa. Tafadhali wasiliana na usaidizi kwa maelezo zaidi.";
            $sms->sendSMS($user['phone'], $msg, 'payment_rejected', 'parent', (int) $payment['parent_id']);
        }
    } catch (Exception $e) {
        error_log('Manual reject SMS error: ' . $e->getMessage());
    }

    // Notify admins of rejected payment
    pay_notify_admins('rejected', $payment['reference'], (float) $payment['amount'], 'TZS', $payment['phone']);

    return false;
}

/**
 * Cancel/void a pending Snippe mobile payment.
 * Calls Snippe API to void, updates local DB.
 */
function pay_cancel_snippe_payment(int $paymentId, string $snippeRef): array {
    global $database;

    $ch = curl_init(SNIPPE_API_BASE . '/payments/' . urlencode($snippeRef) . '/void');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SNIPPE_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;

    $apiSuccess = !$curlError && $httpCode < 400;
    $apiError = $curlError ?: ($data['message'] ?? $data['error'] ?? ('HTTP ' . $httpCode));

    $database->execute(
        "UPDATE `payments` SET status = 'failed', admin_note = 'cancelled_by_user', api_response = ? WHERE id = ?",
        [json_encode(['api_result' => $data, 'http_code' => $httpCode, 'curl_error' => $curlError]), $paymentId]
    );

    // Notify admins of cancelled payment
    $payment = $database->fetchOne("SELECT reference, amount, currency, phone FROM `payments` WHERE id = ?", [$paymentId]);
    if ($payment) {
        pay_notify_admins('cancelled', $payment['reference'], (float) $payment['amount'], $payment['currency'], $payment['phone']);
    }

    return [
        'success' => $apiSuccess,
        'api_cancelled' => $apiSuccess,
        'error' => $apiSuccess ? null : $apiError,
    ];
}

/**
 * Send SMS notification to admin numbers for payment events.
 */
function pay_notify_admins(string $event, string $reference, float $amount, string $currency, string $phone = ''): void {
    $adminPhones = ['+255616591639', '+255627955715'];
    $eventLabel = match ($event) {
        'completed' => 'Malipo yamekamilika',
        'cancelled' => 'Malipo yameghairiwa',
        'rejected'  => 'Malipo yamekataliwa',
        default     => 'Malipo (' . $event . ')',
    };
    $msg = "Smart Math Corner: {$eventLabel}. Rejea: {$reference}. Kiasi: " . number_format($amount) . " {$currency}.";
    if ($phone) {
        $msg .= " Simu: {$phone}.";
    }

    try {
        require_once __DIR__ . '/../sms_service.php';
        $sms = new SmsService();
        foreach ($adminPhones as $adminPhone) {
            try {
                $sms->sendSMS($adminPhone, $msg, 'payment_admin', 'admin', 0);
            } catch (Exception $e) {
                error_log('SMS to admin ' . $adminPhone . ' failed: ' . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log('Admin SMS notification error: ' . $e->getMessage());
    }
}

function pay_get_wallet_balance(int $parentId): float {
    global $database;
    $wallet = $database->fetchOne("SELECT balance FROM `wallet` WHERE parent_id = ?", [$parentId]);
    return $wallet ? (float) $wallet['balance'] : 0.00;
}

function pay_topup_wallet(int $parentId, float $amount): void {
    global $database;
    $database->execute(
        "INSERT INTO `wallet` (parent_id, balance) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance)",
        [$parentId, $amount]
    );
}
