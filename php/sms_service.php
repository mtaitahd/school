<?php
/**
 * KONA YA HISABATI - SMS Service Class
 * Webline Africa SMS API Integration
 * Production-ready SMS notification system
 */

require_once __DIR__ . '/db_connection.php';

class SmsService {
    private $apiToken;
    private $senderId;
    private $baseUrl;
    private $database;
    private $rateLimitDelay = 1000; // 1 second between requests
    private $lastRequestTime = 0;
    private $maxRetries = 3;
    private $timeout = 30;

    /**
     * Constructor - Initialize SMS service with configuration
     */
    public function __construct($apiToken = null, $senderId = null, $baseUrl = null) {
        // Load from .env file if not provided
        $envConfig = $this->loadEnvFile();
        
        // Load from environment, .env file, or parameters
        $this->apiToken = $apiToken ?? ($envConfig['SMS_API_TOKEN'] ?? '');
        $this->senderId = $senderId ?? ($envConfig['SMS_SENDER_ID'] ?? 'KONA YA HISABATI');
        $this->baseUrl = $baseUrl ?? ($envConfig['SMS_BASE_URL'] ?? 'https://sms.webline.co.tz/api/send');
        
        // Initialize database connection
        $this->database = new Database();
        
        // Validate configuration
        if (empty($this->apiToken)) {
            error_log("SMS Service: API Token not configured");
        }
    }

    /**
     * Load .env file and return configuration array
     * 
     * @return array - Configuration values
     */
    private function loadEnvFile() {
        $envFile = __DIR__ . '/../.env';
        $config = [];
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse key=value
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $config[trim($key)] = trim($value);
                }
            }
        }
        
        return $config;
    }

    /**
     * Send single SMS message
     * 
     * @param string $phone - Phone number (Tanzania format: +255...)
     * @param string $message - SMS message content
     * @param string $type - SMS type (assignment, performance, parent_link, fee_payment, general)
     * @param string $recipientType - Recipient type (parent, student, teacher)
     * @param int|null $relatedId - Related record ID
     * @return array - Response with status and data
     */
    public function sendSMS($phone, $message, $type = 'general', $recipientType = 'parent', $relatedId = null) {
        try {
            // Best-effort: keep this method safe even if DLR sync methods are not configured.

            // Validate phone number
            if (!$this->validatePhone($phone)) {
                return [
                    'success' => false,
                    'message' => 'Invalid phone number format. Use Tanzania format: +255XXXXXXXXX'
                ];
            }

            // Sanitize message
            $message = $this->sanitizeMessage($message);
            
            // Check message length (SMS limit is 160 characters for single SMS)
            if (strlen($message) > 160) {
                error_log("SMS Service: Message exceeds 160 characters. Length: " . strlen($message));
            }

            // Rate limiting
            $this->applyRateLimit();

            // Log SMS attempt
            $smsLogId = $this->logSMS($phone, $message, 'pending', $type, $recipientType, $relatedId);

            // Send SMS with retry mechanism
            $response = $this->sendWithRetry($phone, $message, $smsLogId);

            // After sending, try to sync any DLR we may already have (best-effort polling)
            // This will not replace proper scheduled DLR sync, but improves immediacy.
            // If DLR sync methods are not yet implemented/configured, this should be a no-op.
            if (method_exists($this, 'syncSingleSMSDeliveryReport')) {
                $this->syncSingleSMSDeliveryReport($smsLogId);
            }

            return $response;



        } catch (Exception $e) {
            error_log("SMS Service Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'SMS sending failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send bulk SMS to multiple recipients
     * 
     * @param array $phones - Array of phone numbers
     * @param string $message - SMS message content
     * @param string $type - SMS type
     * @param string $recipientType - Recipient type
     * @param int|null $relatedId - Related record ID
     * @return array - Response with status and results
     */
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
            
            $results[] = [
                'phone' => $phone,
                'response' => $response
            ];

            // Add delay between bulk messages to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        return [
            'success' => true,
            'message' => "Bulk SMS sent. Success: $successCount, Failed: $failureCount",
            'results' => $results,
            'summary' => [
                'total' => count($phones),
                'success' => $successCount,
                'failed' => $failureCount
            ]
        ];
    }

    /**
     * Validate phone number for Tanzania format
     * Accepts: +255XXXXXXXXX, 255XXXXXXXXX, 0XXXXXXXXX
     * 
     * @param string $phone - Phone number to validate
     * @return bool - True if valid, false otherwise
     */
    public function validatePhone($phone) {
        $raw = (string)$phone;
        $raw = trim($raw);

        // Allow input like: +2557XXXXXXXX / 2556XXXXXXXX / 0XXXXXXXXX
        // Reject spaces, +, dashes, parentheses by normalizing to digits.
        $normalized = $this->normalizePhone($raw);

        // Strict Tanzanian allowed patterns per requirement:
        // 1) 2557XXXXXXXX  ( +255 + 10 digits where first after 255 is 7 )
        // 2) 2556XXXXXXXX
        if (preg_match('/^\+255[6-7][0-9]{8}$/', $normalized)) {
            return true;
        }

        return false;
    }


    /**
     * Normalize phone number to standard format (+255XXXXXXXXX)
     * 
     * @param string $phone - Phone number to normalize
     * @return string - Normalized phone number
     */
    public function normalizePhone($phone) {
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // If starts with 0, replace with +255
        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+255' . substr($phone, 1);
        }
        
        // If starts with 255 without +, add +
        if (preg_match('/^255[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }
        
        return $phone;
    }

    /**
     * Normalize phone number for API payload (digits only, no leading +)
     * 
     * @param string $phone - Phone number to normalize
     * @return string - API-friendly phone number
     */
    private function formatPhoneForApi($phone) {
        $normalized = $this->normalizePhone($phone);
        return ltrim($normalized, '+');
    }

    /**
     * Log SMS to database
     * 
     * @param string $phone - Phone number
     * @param string $message - Message content
     * @param string $status - SMS status
     * @param string $type - SMS type
     * @param string $recipientType - Recipient type
     * @param int|null $relatedId - Related record ID
     * @return int - SMS log ID
     */
    private function logSMS($phone, $message, $status, $type, $recipientType, $relatedId) {
        $sql = "INSERT INTO sms_logs (phone, message, status, sms_type, recipient_type, related_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $params = [
            $this->normalizePhone($phone),
            $message,
            $status,
            $type,
            $recipientType,
            $relatedId
        ];
        
        return $this->database->insert($sql, $params);
    }

    /**
     * Update SMS log status
     * 
     * @param int $smsLogId - SMS log ID
     * @param string $status - New status
     * @param string|null $response - API response
     * @param string|null $errorMessage - Error message if any
     * @return bool - True if successful
     */
    private function updateSMSLog($smsLogId, $status, $response = null, $errorMessage = null) {
        $sql = "UPDATE sms_logs SET status = ?, response = ?, error_message = ?, sent_at = NOW() 
                WHERE sms_id = ?";
        
        $params = [$status, $response, $errorMessage, $smsLogId];
        
        return $this->database->execute($sql, $params);
    }

    /**
     * Send SMS with retry mechanism
     * 
     * @param string $phone - Phone number
     * @param string $message - Message content
     * @param int $smsLogId - SMS log ID
     * @return array - Response
     */
    private function sendWithRetry($phone, $message, $smsLogId) {
        $normalizedPhone = $this->normalizePhone($phone);
        $lastError = null;
        
        // NOTE: To avoid hitting provider sending limits and breaking delivery,
        // we do NOT perform retries for HTTP-level “sending limit exceeded” errors.
        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {

                $response = $this->executeApiRequest($normalizedPhone, $message);
                
                // Parse response
                $responseData = json_decode($response, true);

                // Always log raw response
                error_log("SMS Service: Full API Response: " . $response);
                error_log("SMS Service: Parsed Response Data: " . json_encode($responseData));

                // IMPORTANT:
                // Do NOT mark SMS as delivered/sent just because the API request succeeded.
                // We will treat Webline acceptance as success ONLY when we can extract a message_id.
                // If message_id is missing, keep it pending.
                $successMessage = 'SMS accepted by provider';

                $messageId = null;
                if (is_array($responseData)) {
                    if (isset($responseData['message_id'])) {
                        $messageId = $responseData['message_id'];
                    } elseif (isset($responseData['data']) && is_array($responseData['data']) && isset($responseData['data']['message_id'])) {
                        $messageId = $responseData['data']['message_id'];
                    } elseif (isset($responseData['id'])) {
                        $messageId = $responseData['id'];
                    }
                }

                $isRejected = false;
                $rejectReason = null;
                if (is_array($responseData)) {
                    $lowerKeys = array_change_key_case($responseData, CASE_LOWER);
                    $lowerMsg = '';
                    if (isset($lowerKeys['message'])) $lowerMsg = strtolower((string)$lowerKeys['message']);
                    if (isset($lowerKeys['error'])) $lowerMsg = strtolower((string)$lowerKeys['error']);
                    if (isset($lowerKeys['status'])) $lowerMsg = strtolower((string)$lowerKeys['status']);

                    if (isset($lowerKeys['status']) && strtolower((string)$lowerKeys['status']) === 'rejected') {
                        $isRejected = true;
                    }

                    if ($lowerMsg) {
                        if (strpos($lowerMsg, 'rejected') !== false || strpos($lowerMsg, 'invalid recipient') !== false || strpos($lowerMsg, 'unauthorized') !== false || strpos($lowerMsg, 'authentication') !== false || strpos($lowerMsg, 'sender') !== false) {
                            $isRejected = true;
                        }
                    }

                    if (isset($lowerKeys['message'])) $rejectReason = (string)$lowerKeys['message'];
                    if (!$rejectReason && isset($lowerKeys['error'])) $rejectReason = (string)$lowerKeys['error'];
                }

                if ($isRejected) {
                    $errorMessage = $rejectReason ?: 'SMS rejected by provider';
                    $lastError = $errorMessage;
                    error_log("SMS Service: Rejected: " . $errorMessage);
                    $this->updateSMSLog($smsLogId, 'failed', $response, $errorMessage);

                    // Don't retry on auth/sender/reject failures, but allow retry on provider throttling/limit errors
                    $lowerErr = strtolower($errorMessage);
                    $nonRetryPatterns = [
                        'authentication',
                        'unauthorized',
                        'rejected',
                        'invalid recipient',
                        'sender'
                    ];
                    foreach ($nonRetryPatterns as $p) {
                        if (strpos($lowerErr, $p) !== false) {
                            break 2;
                        }
                    }

                } else {
                    // Accept explicit success statuses if returned by provider.
                    $successStatus = false;
                    if (is_array($responseData)) {
                        $lowerKeys = array_change_key_case($responseData, CASE_LOWER);
                        if (isset($lowerKeys['success']) && ($lowerKeys['success'] === true || $lowerKeys['success'] === 'true')) {
                            $successStatus = true;
                        }
                        if (isset($lowerKeys['status']) && in_array(strtolower((string)$lowerKeys['status']), ['success', 'accepted', 'queued', 'sent'], true)) {
                            $successStatus = true;
                        }
                    }

                    if (!empty($messageId)) {
                        $this->updateSMSLog($smsLogId, 'sent', $response);

                        return [
                            'success' => true,
                            'queued' => false,
                            'message' => $successMessage,
                            'response' => $responseData,
                            'sms_log_id' => $smsLogId
                        ];
                    }

                    if ($successStatus) {
                        $pendingReason = 'Provider accepted request; awaiting delivery report';
                        if (!empty($response) && !is_array($responseData)) {
                            $pendingReason = 'Provider returned unparsed response; awaiting delivery report';
                        }
                        $this->updateSMSLog($smsLogId, 'pending', $response, $pendingReason);
                        return [
                            'success' => true,
                            'queued' => true,
                            'message' => 'SMS queued; awaiting delivery report',
                            'response' => $responseData,
                            'sms_log_id' => $smsLogId
                        ];
                    }

                    // No message_id or explicit success state => keep it pending for later DLR sync
                    $pendingReason = 'Provider accepted but no message_id returned; awaiting delivery report';
                    if (!empty($response) && !is_array($responseData)) {
                        $pendingReason = 'Provider returned unparsed response; awaiting delivery report';
                    }
                    $this->updateSMSLog($smsLogId, 'pending', $response, $pendingReason);
                    return [
                        'success' => true,
                        'queued' => true,
                        'message' => 'SMS queued; awaiting delivery report',
                        'response' => $responseData,
                        'sms_log_id' => $smsLogId
                    ];
                }

                
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                error_log("SMS Service - Attempt $attempt failed: " . $lastError);
                
                // Don't retry on 301 redirect errors
                if (strpos($lastError, '301 Redirect') !== false) {
                    error_log("SMS Service: 301 redirect error detected, not retrying");
                    break;
                }
                
                // Update retry count
                $this->database->execute(
                    "UPDATE sms_logs SET retry_count = retry_count + 1 WHERE sms_id = ?",
                    [$smsLogId]
                );
                
                // Wait before retry (exponential backoff)
                if ($attempt < $this->maxRetries) {
                    sleep($attempt * 2);
                }
            }
        }
        
        // All retries failed
        $this->updateSMSLog($smsLogId, 'failed', null, $lastError);
        
        return [
            'success' => false,
            'message' => 'SMS sending failed after ' . $this->maxRetries . ' attempts: ' . $lastError,
            'sms_log_id' => $smsLogId
        ];
    }

    /**
     * Execute actual API request using cURL
     * 
     * @param string $phone - Phone number
     * @param string $message - Message content
     * @return string - API response
     * @throws Exception - On cURL errors
     */
    private function executeApiRequest($phone, $message) {
        if (empty($this->apiToken)) {
            throw new Exception("SMS API Token not configured");
        }
        
        // Log the phone number being sent
        error_log("SMS Service: Sending to phone: " . $phone);
        
        $ch = curl_init();
        
        // Prepare POST data for Webline API endpoint
        // Webline Africa v3 API JSON format
        $postData = [
            'recipient' => $this->formatPhoneForApi($phone),
            'sender_id' => $this->senderId,
            'sender' => $this->senderId,
            'message' => $message
        ];
        
        error_log("SMS Service: POST data: " . json_encode($postData));
        error_log("SMS Service: URL: " . $this->baseUrl);
        
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiToken,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        error_log("SMS Service: Response: " . $response);
        error_log("SMS Service: HTTP Code: " . $httpCode);
        
        if ($curlError) {
            throw new Exception("cURL Error: " . $curlError);
        }
        
        // Check for 301 redirect error
        if ($httpCode === 301) {
            throw new Exception("HTTP 301 Redirect Error: Wrong URL endpoint. Please check SMS_BASE_URL configuration.");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: " . $httpCode . " - " . $response);
        }
        
        return $response;
    }

    /**
     * Apply rate limiting between requests
     */
    private function applyRateLimit() {
        $currentTime = microtime(true);
        $timeSinceLastRequest = ($currentTime - $this->lastRequestTime) * 1000; // Convert to milliseconds
        
        if ($timeSinceLastRequest < $this->rateLimitDelay) {
            $sleepTime = ($this->rateLimitDelay - $timeSinceLastRequest) / 1000; // Convert to seconds
            usleep($sleepTime * 1000000); // Convert to microseconds
        }
        
        $this->lastRequestTime = microtime(true);
    }

    /**
     * Sanitize message to prevent XSS and SQL injection
     * 
     * @param string $message - Message to sanitize
     * @return string - Sanitized message
     */
    private function sanitizeMessage($message) {
        // Remove HTML tags
        $message = strip_tags($message);
        
        // Remove special characters that might cause issues
        $message = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $message);
        
        // Trim whitespace
        $message = trim($message);
        
        return $message;
    }

    /**
     * Check SMS balance (if supported by API)
     * 
     * @return array - Balance information
     */
    public function checkBalance() {
        try {
            $ch = curl_init();
            
            $balanceUrl = str_replace('/send', '/balance', $this->baseUrl);
            
            curl_setopt($ch, CURLOPT_URL, $balanceUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $this->apiToken,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'balance' => $data
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Failed to check balance'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get SMS logs for a specific phone or type
     * 
     * @param string|null $phone - Filter by phone number
     * @param string|null $type - Filter by SMS type
     * @param int $limit - Maximum number of records
     * @return array - SMS logs
     */
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

    /**
     * Send student result notification
     * 
     * @param string $parentPhone - Parent's phone number
     * @param string $studentName - Student's name
     * @param string $result - Result description
     * @param int $studentId - Student ID
     * @return array - Response
     */
    public function sendStudentResultNotification($parentPhone, $studentName, $result, $studentId) {
        $message = "KONA YA HISABATI: Mtoto wako $studentName amepata $result kwenye mtihani.";
        return $this->sendSMS($parentPhone, $message, 'performance', 'parent', $studentId);
    }

    /**
     * Send fee payment confirmation
     * 
     * @param string $parentPhone - Parent's phone number
     * @param string $studentName - Student's name
     * @param string $amount - Payment amount
     * @param int $studentId - Student ID
     * @return array - Response
     */
    public function sendFeePaymentConfirmation($parentPhone, $studentName, $amount, $studentId) {
        $message = "KONA YA HISABATI: Malipo ya shule kwa $studentName yamepokelewa. Tsh $amount.";
        return $this->sendSMS($parentPhone, $message, 'fee_payment', 'parent', $studentId);
    }

    /**
     * Send assignment reminder
     * 
     * @param string $parentPhone - Parent's phone number
     * @param string $studentName - Student's name
     * @param string $assignmentTitle - Assignment title
     * @param string $dueDate - Due date
     * @param int $assignmentId - Assignment ID
     * @return array - Response
     */
    public function sendAssignmentReminder($parentPhone, $studentName, $assignmentTitle, $dueDate, $assignmentId) {
        $message = "KONA YA HISABATI: Mtoto wako $studentName ana kazi $assignmentTitle inayohitaji kukamilishwa kabla ya $dueDate.";
        return $this->sendSMS($parentPhone, $message, 'assignment', 'parent', $assignmentId);
    }

    /**
     * Send parent linking confirmation
     * 
     * @param string $parentPhone - Parent's phone number
     * @param string $studentName - Student's name
     * @param string $accessCode - Access code used
     * @param int $studentId - Student ID
     * @return array - Response
     */
    public function sendParentLinkingConfirmation($parentPhone, $studentName, $accessCode, $studentId) {
        $message = "KONA YA HISABATI: Umefanikiwa kuunganisha akaunti yako na mtoto wako $studentName. Unaweza kuona maendeleo yao sasa.";
        return $this->sendSMS($parentPhone, $message, 'parent_link', 'parent', $studentId);
    }
}



