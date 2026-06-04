<?php
/**
 * Payment integration: Snippe API + Manual payments
 */

// Snippe API configuration
define('SNIPPE_API_URL', 'https://api.snippe.africa/v1/payments');
define('SNIPPE_API_KEY', sec_env('SNIPPE_API_KEY', ''));
define('SNIPPE_SECRET', sec_env('SNIPPE_SECRET', ''));

// Manual payment details
define('MANUAL_PAYMENT_NUMBER', '440783070');
define('MANUAL_PAYMENT_NAME', 'Smart Math Corner');
define('MANUAL_PAYMENT_NETWORK', 'Mix by Yas Lipa');

const SUBSCRIPTION_AMOUNT = 1500;
const SUBSCRIPTION_CURRENCY = 'TZS';

function pay_create_snippe_payment(int $parentId, string $phone, string $email = ''): array {
    global $database;

    $amount = SUBSCRIPTION_AMOUNT;
    $reference = 'SUB-' . $parentId . '-' . time();

    // Create pending payment record
    $paymentId = $database->insert(
        "INSERT INTO `payments` (parent_id, amount, currency, method, payment_type, phone, email, reference, status)
         VALUES (?, ?, ?, 'snippe', 'subscription', ?, ?, ?, 'pending')",
        [$parentId, $amount, SUBSCRIPTION_CURRENCY, $phone, $email, $reference]
    );

    // Call Snippe API
    $payload = [
        'api_key' => SNIPPE_API_KEY,
        'secret' => SNIPPE_SECRET,
        'amount' => (string) $amount,
        'currency' => SUBSCRIPTION_CURRENCY,
        'phone' => $phone,
        'email' => $email ?: null,
        'reference' => $reference,
        'callback_url' => rtrim(sec_env('APP_URL', 'https://smartmath.co.tz'), '/') . '/api/snippe-webhook.php',
        'metadata' => [
            'parent_id' => $parentId,
            'payment_id' => $paymentId,
            'type' => 'subscription',
        ],
    ];

    $ch = curl_init(SNIPPE_API_URL . '/charge');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $data = $response ? json_decode($response, true) : null;

    if ($curlError || $httpCode >= 400 || !$data) {
        $database->execute(
            "UPDATE `payments` SET status = 'failed', api_response = ? WHERE id = ?",
            [json_encode(['error' => $curlError, 'http_code' => $httpCode, 'response' => $response]), $paymentId]
        );
        return ['success' => false, 'error' => $curlError ?: 'Payment API error'];
    }

    // Update with transaction ID from provider
    $transactionId = $data['transaction_id'] ?? $data['id'] ?? null;
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
        'checkout_url' => $data['checkout_url'] ?? $data['redirect_url'] ?? null,
        'message' => $data['message'] ?? 'Payment initiated',
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

    return [
        'success' => true,
        'payment_id' => $paymentId,
        'reference' => $reference,
    ];
}

function pay_process_webhook(): void {
    global $database;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        exit('Invalid payload');
    }

    $reference = $input['reference'] ?? '';
    $transactionId = $input['transaction_id'] ?? $input['id'] ?? '';
    $status = $input['status'] ?? '';
    $metadata = $input['metadata'] ?? [];

    if (!$reference) {
        http_response_code(400);
        exit('Missing reference');
    }

    $payment = $database->fetchOne(
        "SELECT * FROM `payments` WHERE reference = ? LIMIT 1",
        [$reference]
    );

    if (!$payment) {
        http_response_code(404);
        exit('Payment not found');
    }

    $parentId = (int) $payment['parent_id'];

    // Determine new status
    $newStatus = 'pending';
    $isCompleted = false;

    if (in_array($status, ['completed', 'success', 'paid'])) {
        $newStatus = 'completed';
        $isCompleted = true;
    } elseif (in_array($status, ['failed', 'cancelled', 'expired'])) {
        $newStatus = 'failed';
    }

    // Update payment record
    $database->execute(
        "UPDATE `payments`
         SET status = ?, transaction_id = COALESCE(?, transaction_id), api_response = ?
         WHERE id = ?",
        [$newStatus, $transactionId, json_encode($input), $payment['id']]
    );

    // Activate subscription on success
    if ($isCompleted) {
        sub_activate_after_payment($parentId, (int) $payment['id'], 'snippe');

        // Send confirmation SMS
        try {
            require_once __DIR__ . '/sms_service.php';
            $sms = new SmsService();
            $user = $database->fetchOne("SELECT phone FROM `users` WHERE user_id = ?", [$parentId]);
            if ($user && $user['phone']) {
                $sms->sendSMS(
                    $user['phone'],
                    "Smart Math Corner: Malipo yako ya 1500 TZS yamethibitishwa. Akaunti yako imeanzishwa kwa siku 30. Karibu!",
                    'payment_success',
                    'parent',
                    $parentId
                );
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
        $database->execute(
            "UPDATE `payments` SET status = 'completed' WHERE id = ?",
            [$paymentId]
        );
        sub_activate_after_payment((int) $payment['parent_id'], $paymentId, 'manual');
        return true;
    }

    $database->execute(
        "UPDATE `payments` SET status = 'failed' WHERE id = ?",
        [$paymentId]
    );
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
