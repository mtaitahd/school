<?php
/**
 * KONA YA HISABATI - Claim Code Generator
 * Generates unique, secure claim codes for parent-student linking
 */

class ClaimCodeGenerator {
    private $database;
    private $prefix = 'KH';
    private $codeLength = 6;
    
    /**
     * Constructor
     */
    public function __construct() {
        require_once __DIR__ . '/db_connection.php';
        $this->database = new Database();
    }
    
    /**
     * Generate a unique claim code
     * Format: KH-XXXXXX (e.g., KH-7F92K1)
     * 
     * @return string - Unique claim code
     */
    public function generateCode() {
        $maxAttempts = 100;
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            $code = $this->generateRandomCode();
            
            // Check if code already exists
            $existing = $this->database->fetchOne(
                "SELECT claim_code FROM users WHERE claim_code = ?",
                [$code]
            );
            
            if (!$existing) {
                return $code;
            }
            
            $attempts++;
        }
        
        throw new Exception("Failed to generate unique claim code after $maxAttempts attempts");
    }
    
    /**
     * Generate a random code string
     * Uses uppercase letters and numbers, excluding confusing characters
     * 
     * @return string - Random code
     */
    private function generateRandomCode() {
        // Characters: A-Z and 2-9 (excluding 0, 1, I, O to avoid confusion)
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        
        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $this->prefix . '-' . $code;
    }
    
    /**
     * Validate claim code format
     * 
     * @param string $code - Code to validate
     * @return bool - True if valid format
     */
    public function validateFormat($code) {
        $code = strtoupper(trim($code));
        // Format: KH-XXXXXX (6 alphanumeric characters after KH-)
        return preg_match('/^KH-[A-Z0-9]{6}$/', $code) === 1;
    }
    
    /**
     * Check if claim code exists and is available
     * 
     * @param string $code - Claim code to check
     * @return array - Student info if found, null otherwise
     */
    public function getCodeInfo($code) {
        $code = strtoupper(trim($code));
        
        return $this->database->fetchOne(
            "SELECT user_id, first_name, last_name, parent_claimed, parent_phone, claim_code_created_at 
             FROM users 
             WHERE claim_code = ? AND role = 'learner'",
            [$code]
        );
    }
    
    /**
     * Check if code has already been claimed
     * 
     * @param string $code - Claim code to check
     * @return bool - True if already claimed
     */
    public function isClaimed($code) {
        $code = strtoupper(trim($code));
        
        $student = $this->database->fetchOne(
            "SELECT parent_claimed FROM users WHERE claim_code = ?",
            [$code]
        );
        
        return $student && $student['parent_claimed'] == 1;
    }
    
    /**
     * Mark code as claimed by a parent
     * 
     * @param string $code - Claim code to mark
     * @param int $parentId - Parent user ID
     * @return bool - True if successful
     */
    public function markAsClaimed($code, $parentId) {
        $code = strtoupper(trim($code));
        
        return $this->database->execute(
            "UPDATE users SET parent_claimed = 1, parent_id = ? WHERE claim_code = ?",
            [$parentId, $code]
        );
    }
    
    /**
     * Regenerate claim code for a student
     * 
     * @param int $studentId - Student user ID
     * @return string - New claim code
     */
    public function regenerateCode($studentId) {
        $newCode = $this->generateCode();
        
        $this->database->execute(
            "UPDATE users SET claim_code = ?, parent_claimed = 0, parent_id = NULL, claim_code_created_at = NOW() 
             WHERE user_id = ?",
            [$newCode, $studentId]
        );
        
        return $newCode;
    }
    
    /**
     * Get claim code for a student
     * 
     * @param int $studentId - Student user ID
     * @return string|null - Claim code or null if not set
     */
    public function getStudentCode($studentId) {
        $student = $this->database->fetchOne(
            "SELECT claim_code FROM users WHERE user_id = ?",
            [$studentId]
        );
        
        return $student ? $student['claim_code'] : null;
    }
}



