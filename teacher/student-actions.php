<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/claim_code_generator.php';
require_once __DIR__ . '/../php/sms_service.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: login.php');
    exit;
}

$teacher_id = (int) $_SESSION['user_id'];
$redirect = $_POST['redirect'] ?? 'learners.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['action'] ?? '') !== 'add_student') {
    header('Location: ' . $redirect);
    exit;
}

csrf_require();

$username = trim($_POST['username'] ?? '');
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$password = trim($_POST['password'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$parent_phone = trim($_POST['parent_phone'] ?? '');
$class_id = !empty($_POST['class_id']) ? (int) $_POST['class_id'] : null;

if ($username === '' || $first_name === '' || $last_name === '' || $password === '') {
    header('Location: ' . $redirect . '?error=missing_fields');
    exit;
}

if ($database->fetchOne('SELECT user_id FROM users WHERE username = ?', [$username])) {
    header('Location: ' . $redirect . '?error=username_exists');
    exit;
}

$codeGenerator = new ClaimCodeGenerator();
$claim_code = $codeGenerator->generateCode();
$hashed = password_hash($password, PASSWORD_DEFAULT);

$student_id = $database->insert(
    "INSERT INTO users (username, password, role, first_name, last_name, phone, parent_phone, claim_code, claim_code_created_at)
     VALUES (?, ?, 'learner', ?, ?, ?, ?, ?, NOW())",
    [$username, $hashed, $first_name, $last_name, $phone, $parent_phone, $claim_code]
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
                error_log('SMS: ' . $smsResult['message']);
                $smsErrorParam = '&sms_error=' . urlencode($smsResult['message']);
            } elseif (!empty($smsResult['queued'])) {
                $smsErrorParam = '&sms_error=' . urlencode('SMS request accepted; delivery pending. Please verify the parent phone number.');
            }
        } catch (Exception $e) {
            error_log('SMS: ' . $e->getMessage());
            $smsErrorParam = '&sms_error=' . urlencode($e->getMessage());
        }
    }
    header('Location: ' . $redirect . '?success=1&code=' . urlencode($claim_code) . $smsErrorParam);
} else {
    header('Location: ' . $redirect . '?error=create_failed');
}
exit;



