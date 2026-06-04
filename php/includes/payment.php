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

function pay_idempotency_key(int $parentId): string {
    return 'parent-' . $parentId . '-month-' . date('Ym');
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
    $idempotencyKey = pay_idempotency_key($parentId);
    $method = $_POST['payment_submethod'] ?? 'mobile';
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

    if ($method !== 'card') {
        $phone = pay_normalize_phone($phone);
    }

    $paymentId = $database->insert(
        "INSERT INTO `payments` (parent_id, amount, currency, method, payment_type, phone, email, reference, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')",
        [$parentId, $amount, SUBSCRIPTION_CURRENCY, $method === 'card' ? 'snippe_card' : 'snippe', $type, $phone, $userEmail, $reference]
    );

    $webhookUrl = $appUrl . '/webhooks/snippe';
    // Force HTTPS for webhook URL
    $webhookUrl = preg_replace('/^http:/i', 'https:', $webhookUrl);

    $metadata = [
        'parent_id' => (string) $parentId,
        'payment_id' => (string) $paymentId,
        'type' => $type,
    ];

    if ($method === 'card') {
        $payload = [
            'payment_type' => 'card',
            'details' => [
                'amount' => (int) $amount,
                'currency' => SUBSCRIPTION_CURRENCY,
                'redirect_url' => $appUrl . '/payment-status?ref=' . $reference,
                'cancel_url' => $appUrl . '/topup?cancelled=1',
            ],
            'phone_number' => $phone ?: null,
            'customer' => [
                'firstname' => $firstName,
                'lastname' => $lastName,
                'email' => $userEmail,
                'address' => 'Dar es Salaam',
                'city' => 'Dar es Salaam',
                'state' => 'DSM',
                'postcode' => '14101',
                'country' => 'TZ',
            ],
            'webhook_url' => $webhookUrl,
            'metadata' => $metadata,
        ];
    } else {
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
    }

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
        if (!$errorMsg && $data) {
            $errorMsg = $data['message'] ?? $data['error'] ?? ('HTTP ' . $httpCode);
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

    $respData = $data['data'] ?? $data;
    $transactionId = $respData['reference'] ?? $data['transaction_id'] ?? $respData['id'] ?? null;
    $paymentUrl = $respData['payment_url'] ?? $data['checkout_url'] ?? $data['redirect_url'] ?? null;

    if ($transactionId) {
        $database->execute(
            "UPDATE `payments` SET transaction_id = ?, api_response = ? WHERE id = ?",
            [$transactionId, json_encode($data), $paymentId]
        );
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
 * Only call this from /webhooks/snippe — never trust frontend alone.
 */
function pay_process_webhook(): void {
    global $database;

    $signature = $_SERVER['HTTP_X_SNIPPE_SIGNATURE'] ?? '';
    $rawBody = file_get_contents('php://input');

    $webhookSecret = sec_env('SNIPPE_WEBHOOK_SECRET', '');
    if ($webhookSecret !== '') {
        $expectedSig = hash_hmac('sha256', $rawBody, $webhookSecret);
        if (!hash_equals($expectedSig, $signature)) {
            error_log('Snippe webhook: invalid signature');
            http_response_code(401);
            exit('Invalid signature');
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

    $event = $input['event'] ?? '';
    $data = $input['data'] ?? $input;
    $reference = $data['reference'] ?? $input['reference'] ?? '';
    $transactionId = $data['transaction_id'] ?? $data['id'] ?? $input['transaction_id'] ?? '';
    $paymentStatus = $data['status'] ?? $input['status'] ?? '';
    $metadata = $data['metadata'] ?? $input['metadata'] ?? [];

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

    if ($event === 'payment.completed' || $paymentStatus === 'completed') {
        $newStatus = 'completed';
        $isCompleted = true;
    } elseif ($event === 'payment.failed' || $paymentStatus === 'failed') {
        $newStatus = 'failed';
    }

    $database->execute(
        "UPDATE `payments` SET status = ?, transaction_id = COALESCE(?, transaction_id), api_response = ? WHERE id = ?",
        [$newStatus, $transactionId, json_encode($input), $payment['id']]
    );

    if ($isCompleted) {
        $paymentType = $payment['payment_type'];

        if ($paymentType === 'subscription') {
            sub_activate_after_payment($parentId, (int) $payment['id'], 'snippe');
        } elseif ($paymentType === 'wallet_topup') {
            pay_topup_wallet($parentId, (float) $payment['amount']);
        }

        try {
            require_once __DIR__ . '/sms_service.php';
            $sms = new SmsService();
            $user = $database->fetchOne("SELECT phone FROM `users` WHERE user_id = ?", [$parentId]);
            if ($user && $user['phone']) {
                $msg = ($paymentType === 'subscription')
                    ? "Smart Math Corner: Malipo yako ya " . number_format((float) $payment['amount']) . " TZS yamethibitishwa. Uanachama wako umeanzishwa kwa siku 30. Karibu!"
                    : "Smart Math Corner: Umefanikiwa kujaza salio la wallet yako. Kiasi: " . number_format((float) $payment['amount']) . " TZS.";
                $sms->sendSMS($user['phone'], $msg, 'payment_success', 'parent', $parentId);
            }
        } catch (Exception $e) {
            error_log('Payment SMS error: ' . $e->getMessage());
        }
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
        return true;
    }

    $database->execute("UPDATE `payments` SET status = 'failed' WHERE id = ?", [$paymentId]);
    return false;
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
