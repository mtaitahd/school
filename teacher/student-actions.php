<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/claim_code_generator.php';
require_once __DIR__ . '/../php/sms_service.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login');
    exit;
}

$teacher_id = (int) $_SESSION['user_id'];
$redirect = $_POST['redirect'] ?? 'learners';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'add_student') {
    header('Location: ' . $redirect);
    exit;
}

csrf_require();

$fullname = trim($_POST['fullname'] ?? '');
$age = isset($_POST['age']) ? (int) $_POST['age'] : 0;
$gender = trim($_POST['gender'] ?? '');
$parent_phone = trim($_POST['parent_phone'] ?? '');
$class_id = !empty($_POST['class_id']) ? (int) $_POST['class_id'] : null;

// Split fullname into first and last name
$nameParts = explode(' ', $fullname, 2);
$first_name = $nameParts[0];
$last_name = $nameParts[1] ?? '';

if ($fullname === '') {
    header('Location: ' . $redirect . '?error=missing_fields');
    exit;
}

// Check for duplicate name (same first_name + last_name already exists as learner)
$existingName = $database->fetchOne(
    "SELECT user_id, username FROM users WHERE role = 'learner' AND LOWER(TRIM(first_name)) = LOWER(?) AND LOWER(TRIM(last_name)) = LOWER(?)",
    [$first_name, $last_name]
);
if ($existingName) {
    header('Location: ' . $redirect . '?error=duplicate_name&existing=' . urlencode($existingName['username']));
    exit;
}

// Auto-generate username in SMART/chil/NNN format
$maxNum = $database->fetchOne(
    "SELECT COALESCE(MAX(CAST(SUBSTRING(username, 12) AS UNSIGNED)), 0) + 1
     FROM users WHERE role = 'learner' AND username LIKE 'SMART/chil/%'"
);
$username = 'SMART/chil/' . str_pad((string) $maxNum, 3, '0', STR_PAD_LEFT);
// Ensure uniqueness (race condition guard)
$suffix = 0;
$base = $username;
while ($database->fetchOne('SELECT user_id FROM users WHERE LOWER(username) = LOWER(?)', [$username])) {
    $suffix++;
    $username = $base . '.' . $suffix;
}

// Auto-generate password
$password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
$hashed = password_hash($password, PASSWORD_DEFAULT);

$codeGenerator = new ClaimCodeGenerator();
$claim_code = $codeGenerator->generateCode();

$student_id = $database->insert(
    "INSERT INTO users (username, password, role, first_name, last_name, age, gender, parent_phone, claim_code, claim_code_created_at)
     VALUES (?, ?, 'learner', ?, ?, ?, ?, ?, ?, NOW())",
    [$username, $hashed, $first_name, $last_name, $age, $gender, $parent_phone, $claim_code]
);

if ($student_id) {
    $access_code = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    $database->insert(
        'INSERT INTO student_access_codes (student_id, teacher_id, access_code) VALUES (?, ?, ?)',
        [$student_id, $teacher_id, $access_code]
    );
    if ($class_id) {
        $database->insert('INSERT INTO class_enrollments (class_id, student_id) VALUES (?, ?)', [$class_id, $student_id]);
    }
    $smsErrorParam = '';
    if ($parent_phone !== '') {
        try {
            $sms = new SmsService();
            $msg = "Kona Ya Hisabati: Your child account has been created. Username: $username, Claim Code: $claim_code. Use these to link your child on your parent dashboard.";
            $smsResult = $sms->sendSMS($parent_phone, $msg, 'parent_link', 'parent', $student_id);
            if (!$smsResult['success']) {
                $smsErrorParam = '&sms_error=' . urlencode('Message not sent');
            } elseif (!empty($smsResult['queued'])) {
                $smsErrorParam = '&sms_error=' . urlencode('Message sent');
            }
        } catch (Exception $e) {
            $smsErrorParam = '&sms_error=' . urlencode('Message not sent');
        }
    }
    header('Location: ' . $redirect . '?success=1&code=' . urlencode($claim_code) . $smsErrorParam);
} else {
    header('Location: ' . $redirect . '?error=create_failed');
}
exit;



