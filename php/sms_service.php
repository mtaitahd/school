<?php

require_once __DIR__ . '/db_connection.php';

class SmsService {
    private $apiToken;
    private $senderId;
    private $baseUrl;
    private $database;
    private $rateLimitDelay = 1000;
    private $lastRequestTime = 0;
    private $maxRetries = 3;
    private $timeout = 30;

    public function __construct($apiToken = null, $senderId = null, $baseUrl = null) {
        $envConfig = $this->loadEnvFile();

        $this->apiToken = $apiToken ?? ($envConfig['SMS_API_TOKEN'] ?? '');
        $this->senderId = $senderId ?? ($envConfig['SMS_SENDER_ID'] ?? 'SMARTMATH');
        $this->baseUrl  = $baseUrl  ?? ($envConfig['SMS_BASE_URL']  ?? 'https://meseji.co.tz/api/v1/sms/send');

        $this->database = new Database();

        if (empty($this->apiToken)) {
            error_log("SMS Service: API Token not configured");
        }
    }

    private function loadEnvFile() {
        $envFile = __DIR__ . '/../.env';
        $config = [];
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $config[trim($key)] = trim($value);
                }
            }
        }
        return $config;
    }

    public function sendSMS($phone, $message, $type = 'general', $recipientType = 'parent', $relatedId = null) {
        try {
            if (!$this->validatePhone($phone)) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format. Use Tanzania format: +255XXXXXXXXX'
                ];
            }

            $message = $this->sanitizeMessage($message);
            $this->applyRateLimit();

            $smsLogId = $this->logSMS($phone, $message, 'pending', $type, $recipientType, $relatedId);
            $response = $this->sendWithRetry($phone, $message, $smsLogId);

            return $response;
        } catch (Exception $e) {
            error_log("SMS Service Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'SMS sending failed: ' . $e->getMessage()
            ];
        }
    }

    public function sendBulkSMS($phones, $message, $type = 'general', $recipientType = 'parent', $relatedId = null) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($phones as $phone) {
            $response = $this->sendSMS($phone, $message, $type, $recipientType, $relatedId);
            if ($response['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
            $results[] = ['phone' => $phone, 'response' => $response];
            usleep(500000);
        }

        return [
            'success' => true,
            'message' => "Bulk SMS sent. Success: $successCount, Failed: $failureCount",
            'results' => $results,
            'summary' => ['total' => count($phones), 'success' => $successCount, 'failed' => $failureCount]
        ];
    }

    public function validatePhone($phone) {
        $raw = trim((string)$phone);
        $normalized = $this->normalizePhone($raw);
        if (preg_match('/^\+255[6-7][0-9]{8}$/', $normalized)) {
            return true;
        }
        return false;
    }

    public function normalizePhone($phone) {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+255' . substr($phone, 1);
        }
        if (preg_match('/^255[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }
        return $phone;
    }

    private function formatPhoneForApi($phone) {
        $normalized = $this->normalizePhone($phone);
        return ltrim($normalized, '+');
    }

    private function logSMS($phone, $message, $status, $type, $recipientType, $relatedId) {
        $sql = "INSERT INTO sms_logs (phone, message, status, sms_type, recipient_type, related_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        return $this->database->insert($sql, [
            $this->normalizePhone($phone), $message, $status, $type, $recipientType, $relatedId
        ]);
    }

    private function updateSMSLog($smsLogId, $status, $response = null, $errorMessage = null) {
        $sql = "UPDATE sms_logs SET status = ?, response = ?, error_message = ?, sent_at = NOW() WHERE sms_id = ?";
        return $this->database->execute($sql, [$status, $response, $errorMessage, $smsLogId]);
    }

    private function sendWithRetry($phone, $message, $smsLogId) {
        $normalizedPhone = $this->normalizePhone($phone);
        $lastError = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = $this->executeApiRequest($normalizedPhone, $message);
                $responseData = json_decode($response, true);

                error_log("SMS Service (Meseji): Response: " . $response);

                if (!is_array($responseData)) {
                    $this->updateSMSLog($smsLogId, 'pending', $response, 'Unparseable response');
                    return [
                        'success' => true,
                        'queued' => true,
                        'message' => 'SMS sent; awaiting delivery report',
                        'response' => $response,
                        'sms_log_id' => $smsLogId
                    ];
                }

                $status = strtolower($responseData['status'] ?? '');
                $batchId = $responseData['batch_id'] ?? '';

                $successStatuses = ['queued', 'success', 'sent', 'accepted'];

                if (in_array($status, $successStatuses) && !empty($batchId)) {
                    $this->updateSMSLog($smsLogId, 'sent', $response);
                    return [
                        'success' => true,
                        'queued' => false,
                        'message' => 'SMS accepted by Meseji',
                        'response' => $responseData,
                        'sms_log_id' => $smsLogId
                    ];
                }

                if (in_array($status, $successStatuses)) {
                    $this->updateSMSLog($smsLogId, 'pending', $response, 'No batch_id returned');
                    return [
                        'success' => true,
                        'queued' => true,
                        'message' => 'SMS queued; awaiting delivery report',
                        'response' => $responseData,
                        'sms_log_id' => $smsLogId
                    ];
                }

                $errorMsg = $responseData['error'] ?? $responseData['message'] ?? 'Unknown API error';
                $lastError = $errorMsg;
                error_log("SMS Service (Meseji): API error: " . $errorMsg);
                $this->updateSMSLog($smsLogId, 'failed', $response, $errorMsg);

                return [
                    'success' => false,
                    'message' => 'SMS rejected: ' . $errorMsg,
                    'response' => $responseData,
                    'sms_log_id' => $smsLogId
                ];

            } catch (Exception $e) {
                $lastError = $e->getMessage();
                error_log("SMS Service (Meseji) - Attempt $attempt failed: " . $lastError);

                $this->database->execute(
                    "UPDATE sms_logs SET retry_count = retry_count + 1 WHERE sms_id = ?",
                    [$smsLogId]
                );

                if ($attempt < $this->maxRetries) {
                    sleep($attempt * 2);
                }
            }
        }

        $this->updateSMSLog($smsLogId, 'failed', null, $lastError);
        return [
            'success' => false,
            'message' => 'SMS sending failed after ' . $this->maxRetries . ' attempts: ' . $lastError,
            'sms_log_id' => $smsLogId
        ];
    }

    private function executeApiRequest($phone, $message) {
        if (empty($this->apiToken)) {
            throw new Exception("SMS API Token not configured");
        }

        $apiPhone = $this->formatPhoneForApi($phone);
        error_log("SMS Service (Meseji): Sending to: " . $apiPhone);

        $postData = [
            'sender_id' => $this->senderId,
            'message'   => $message,
            'contacts'  => $apiPhone
        ];

        error_log("SMS Service (Meseji): POST data: " . json_encode($postData));
        error_log("SMS Service (Meseji): URL: " . $this->baseUrl);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiToken,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        error_log("SMS Service (Meseji): HTTP Code: " . $httpCode);
        error_log("SMS Service (Meseji): Response: " . $response);

        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }

        if ($httpCode >= 400) {
            throw new Exception("HTTP $httpCode: " . $response);
        }

        return $response;
    }

    private function applyRateLimit() {
        $currentTime = microtime(true);
        $timeSinceLastRequest = ($currentTime - $this->lastRequestTime) * 1000;
        if ($timeSinceLastRequest < $this->rateLimitDelay) {
            $sleepTime = ($this->rateLimitDelay - $timeSinceLastRequest) / 1000;
            usleep($sleepTime * 1000000);
        }
        $this->lastRequestTime = microtime(true);
    }

    private function sanitizeMessage($message) {
        $message = strip_tags($message);
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message);
        return trim($message);
    }

    public function checkBalance() {
        try {
            $url = str_replace('/sms/send', '/sms/user-stats', $this->baseUrl);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'x-api-key: ' . $this->apiToken,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return ['success' => true, 'balance' => $data['balance'] ?? $data];
            }
            return ['success' => false, 'message' => 'Failed to check balance'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getSMSLogs($phone = null, $type = null, $limit = 50) {
        $sql = "SELECT * FROM sms_logs WHERE 1=1";
        $params = [];
        if ($phone) {
            $sql .= " AND phone = ?";
            $params[] = $this->normalizePhone($phone);
        }
        if ($type) {
            $sql .= " AND sms_type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        return $this->database->fetchAll($sql, $params);
    }

    public function sendStudentResultNotification($parentPhone, $studentName, $result, $studentId) {
        $message = "KONA YA HISABATI: Mtoto wako $studentName amepata $result kwenye mtihani.";
        return $this->sendSMS($parentPhone, $message, 'performance', 'parent', $studentId);
    }

    public function sendFeePaymentConfirmation($parentPhone, $studentName, $amount, $studentId) {
        $message = "KONA YA HISABATI: Malipo ya shule kwa $studentName yamepokelewa. Tsh $amount.";
        return $this->sendSMS($parentPhone, $message, 'fee_payment', 'parent', $studentId);
    }

    public function sendAssignmentReminder($parentPhone, $studentName, $assignmentTitle, $dueDate, $assignmentId) {
        $message = "KONA YA HISABATI: Mtoto wako $studentName ana kazi $assignmentTitle inayohitaji kukamilishwa kabla ya $dueDate.";
        return $this->sendSMS($parentPhone, $message, 'assignment', 'parent', $assignmentId);
    }

    public function sendParentLinkingConfirmation($parentPhone, $studentName, $accessCode, $studentId) {
        $message = "KONA YA HISABATI: Umefanikiwa kuunganisha akaunti yako na mtoto wako $studentName. Unaweza kuona maendeleo yao sasa.";
        return $this->sendSMS($parentPhone, $message, 'parent_link', 'parent', $studentId);
    }
}
