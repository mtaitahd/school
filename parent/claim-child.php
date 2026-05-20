<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/claim_code_generator.php';
require_once '../php/sms_service.php';

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../login.php');
    exit;
}

$parent_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Handle claim code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_code'])) {
    $claim_code = trim(strtoupper($_POST['claim_code']));
    
    // Initialize claim code generator
    $codeGenerator = new ClaimCodeGenerator();
    
    // Validate claim code format
    if (!$codeGenerator->validateFormat($claim_code)) {
        $error = "Invalid claim code format. Code must be in format KH-XXXXXX (e.g., KH-7F92K1).";
    } else {
        // Get code info
        $student_info = $codeGenerator->getCodeInfo($claim_code);
        
        if (!$student_info) {
            $error = "Invalid claim code. Please check and try again.";
        } else {
            // Check if code has already been claimed
            if ($codeGenerator->isClaimed($claim_code)) {
                // Check if already claimed by this parent
                if ($student_info['parent_claimed'] && $student_info['parent_id'] == $parent_id) {
                    $error = "You have already claimed this child.";
                } else {
                    $error = "This claim code has already been used by another parent.";
                }
            } else {
                // Check if parent already has this student linked via parent_student_links
                $existing_link = $database->fetchOne("
                    SELECT * FROM parent_student_links 
                    WHERE parent_id = ? AND student_id = ? AND is_active = 1
                ", [$parent_id, $student_info['user_id']]);
                
                if ($existing_link) {
                    $error = "You are already linked to this student.";
                } else {
                    // Mark code as claimed
                    $claimed = $codeGenerator->markAsClaimed($claim_code, $parent_id);
                    
                    if ($claimed) {
                        // Also create record in parent_student_links for compatibility
                        $database->insert(
                            "INSERT INTO parent_student_links (parent_id, student_id, access_code, is_active) 
                             VALUES (?, ?, ?, 1)",
                            [$parent_id, $student_info['user_id'], $claim_code]
                        );
                        
                        $success = "Successfully linked to " . htmlspecialchars($student_info['first_name'] . ' ' . $student_info['last_name']) . "!";
                        
                        // Send SMS confirmation if parent has phone number
                        $parent = $database->fetchOne("SELECT phone FROM users WHERE user_id = ?", [$parent_id]);
                        if ($parent && $parent['phone']) {
                            try {
                                $smsService = new SmsService();
                                $smsService->sendParentLinkingConfirmation(
                                    $parent['phone'],
                                    $student_info['first_name'] . ' ' . $student_info['last_name'],
                                    $claim_code,
                                    $student_info['user_id']
                                );
                            } catch (Exception $e) {
                                error_log("SMS confirmation failed: " . $e->getMessage());
                            }
                        }
                        
                        // Redirect to dashboard after successful claim
                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = "Failed to claim child. Please try again.";
                    }
                }
            }
        }
    }
}

// Redirect to dashboard if not POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
?>



