<?php
/**
 * SnippeCardPaymentService — Dedicated handler for Snippe card payment flow
 *
 * Card Payment Flow:
 * 1. Validate all required customer fields
 * 2. POST to Snippe /v1/payments with payment_type=card
 * 3. Receive payment_url from Snippe response
 * 4. Redirect customer to Snippe secure checkout
 * 5. Snippe sends webhook (ONLY source of truth)
 * 6. Never activate subscription from redirect_url alone
 */

class SnippeCardPaymentService {
    private string $apiBase;
    private string $apiKey;
    private string $appUrl;
    private PDO $db;

    public function __construct(PDO $db) {
        $this->apiBase = 'https://api.snippe.sh/v1';
        $this->apiKey = sec_env('SNIPPE_API_KEY', '');
        $this->appUrl = rtrim(sec_env('APP_URL', 'https://smartmathconner.co.tz'), '/');
        if (preg_match('/localhost/i', $this->appUrl)) {
            $this->appUrl = 'https://smartmathconner.co.tz';
        }
        $this->appUrl = preg_replace('/^http:/i', 'https:', $this->appUrl);
        $this->db = $db;
    }

    /**
     * Create a card payment and return the payment_url for redirect.
     *
     * @param int    $parentId
     * @param string $email
     * @param string $paymentType 'subscription' | 'wallet_topup'
     * @param float  $customAmount For wallet topup
     * @return array ['success' => bool, 'payment_url' => ?string, 'reference' => ?string, 'error' => ?string]
     */
    public function createPayment(int $parentId, string $email, string $paymentType = 'subscription', float $customAmount = 0): array {
        $user = $this->fetchUser($parentId);
        if (!$user) {
            return $this->error('User not found');
        }

        $firstName = trim($user['first_name'] ?? '');
        $lastName = trim($user['last_name'] ?? '');
        $userEmail = trim($user['email'] ?? '');
        if ($userEmail === '') {
            $userEmail = $email !== '' ? $email : 'parent' . $parentId . '@smartmathconner.co.tz';
        }

        // Validate required card payment fields
        $validationErrors = [];
        if ($firstName === '') $validationErrors[] = 'customer.firstname is required';
        if ($lastName === '') $validationErrors[] = 'customer.lastname is required';
        if ($userEmail === '' || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) $validationErrors[] = 'customer.email is required';

        if (!empty($validationErrors)) {
            $errorMsg = implode('; ', $validationErrors);
            $this->log('Card payment validation failed: ' . $errorMsg, 'ERROR', ['parent_id' => $parentId]);
            return $this->error($errorMsg);
        }

        $amount = $paymentType === 'wallet_topup' ? max(500, $customAmount) : 1500;
        $reference = strtoupper(bin2hex(random_bytes(8)));
        $idempotencyKey = 'pay-' . $reference;
        $webhookUrl = $this->appUrl . '/webhooks/snippe';
        $redirectUrl = $this->appUrl . '/payment-success?ref=' . $reference;
        $cancelUrl = $this->appUrl . '/payment-failed?ref=' . $reference;

        $paymentId = $this->insertPayment($parentId, $amount, $reference, $userEmail, $paymentType);

        $payload = [
            'payment_type' => 'card',
            'details' => [
                'amount' => (int) $amount,
                'currency' => 'TZS',
                'redirect_url' => $redirectUrl,
                'cancel_url' => $cancelUrl,
            ],
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
            'metadata' => [
                'parent_id' => (string) $parentId,
                'payment_id' => (string) $paymentId,
                'type' => $paymentType,
            ],
        ];

        $this->log('Sending card payment request', 'INFO', [
            'payload' => $this->sanitizePayload($payload),
            'reference' => $reference,
        ]);

        $response = $this->callSnippeApi($payload, $idempotencyKey);

        if (!$response['success']) {
            $this->updatePaymentFailed($paymentId, $response['error'], $response['raw_response'] ?? null);
            return $this->error($response['error']);
        }

        $data = $response['data'];
        $respData = $data['data'] ?? $data;
        $transactionId = $respData['reference'] ?? $respData['transaction_id'] ?? $respData['id'] ?? null;
        $paymentUrl = $respData['payment_url'] ?? $respData['checkout_url'] ?? $respData['redirect_url'] ?? null;

        $this->updatePaymentSuccess($paymentId, $transactionId, $data);

        $this->log('Card payment created successfully', 'INFO', [
            'reference' => $reference,
            'transaction_id' => $transactionId,
            'has_payment_url' => $paymentUrl ? true : false,
        ]);

        if (!$paymentUrl) {
            return $this->error('Snippe did not return a payment URL. Response: ' . json_encode($data));
        }

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'reference' => $reference,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
        ];
    }

    private function fetchUser(int $parentId): ?array {
        $stmt = $this->db->prepare("SELECT first_name, last_name, email, parent_phone FROM users WHERE user_id = ?");
        $stmt->execute([$parentId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    private function insertPayment(int $parentId, float $amount, string $reference, string $email, string $type): int {
        $stmt = $this->db->prepare(
            "INSERT INTO payments (parent_id, amount, currency, method, payment_type, phone, email, reference, status)
             VALUES (?, ?, 'TZS', 'snippe_card', ?, '', ?, ?, 'pending')"
        );
        $stmt->execute([$parentId, $amount, $type, $email, $reference]);
        return (int) $this->db->lastInsertId();
    }

    private function updatePaymentFailed(int $paymentId, string $error, $rawResponse = null): void {
        $responseData = ['error' => $error];
        if ($rawResponse) {
            $responseData['raw_response'] = $rawResponse;
        }
        $stmt = $this->db->prepare(
            "UPDATE payments SET status = 'failed', api_response = ? WHERE id = ?"
        );
        $stmt->execute([json_encode($responseData), $paymentId]);
    }

    private function updatePaymentSuccess(int $paymentId, ?string $transactionId, array $data): void {
        $stmt = $this->db->prepare(
            "UPDATE payments SET transaction_id = COALESCE(?, transaction_id), api_response = ? WHERE id = ?"
        );
        $stmt->execute([$transactionId, json_encode($data), $paymentId]);
    }

    private function callSnippeApi(array $payload, string $idempotencyKey): array {
        $ch = curl_init($this->apiBase . '/payments');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Idempotency-Key: ' . $idempotencyKey,
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        $data = $response ? json_decode($response, true) : null;

        $this->log('Snippe API response', 'INFO', [
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response' => $data,
        ]);

        if ($curlError) {
            return ['success' => false, 'error' => 'Network error: ' . $curlError, 'raw_response' => $response];
        }

        if ($httpCode >= 400) {
            $errorMsg = $data['message'] ?? $data['error'] ?? '';
            if (empty($errorMsg) && isset($data['errors']) && is_array($data['errors'])) {
                $errorParts = [];
                foreach ($data['errors'] as $field => $msgs) {
                    if (is_array($msgs)) {
                        $errorParts[] = implode(', ', $msgs);
                    } else {
                        $errorParts[] = (string) $msgs;
                    }
                }
                $errorMsg = implode('; ', $errorParts);
            }
            if (empty($errorMsg)) {
                $errorMsg = 'Snippe API error (HTTP ' . $httpCode . ')';
            }
            $this->log('Card payment API error', 'ERROR', [
                'http_code' => $httpCode,
                'error' => $errorMsg,
                'response' => $data,
            ]);
            return ['success' => false, 'error' => $errorMsg, 'raw_response' => $response];
        }

        if (!$data) {
            return ['success' => false, 'error' => 'Invalid response from Snippe (empty or malformed)', 'raw_response' => $response];
        }

        if (isset($data['status']) && $data['status'] === 'error') {
            $errMsg = $data['message'] ?? $data['error'] ?? 'Snippe returned error status';
            return ['success' => false, 'error' => $errMsg, 'raw_response' => $response];
        }

        return ['success' => true, 'data' => $data];
    }

    private function sanitizePayload(array $payload): array {
        $safe = $payload;
        unset($safe['webhook_url']);
        return $safe;
    }

    private function log(string $message, string $level = 'INFO', array $context = []): void {
        $entry = '[' . date('Y-m-d H:i:s') . '] [' . $level . '] [SnippeCardPaymentService] ' . $message;
        if (!empty($context)) {
            $entry .= ' | ' . json_encode($context);
        }
        error_log($entry);

        // Also log to browser console via JavaScript if this is an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            // For AJAX requests, you can add custom logging header
        }
    }

    private function error(string $message): array {
        return ['success' => false, 'error' => $message];
    }
}
