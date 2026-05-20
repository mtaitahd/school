<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/claim_code_generator.php';
require_once '../php/sms_service.php';

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
    if ($parent_phone !== '') {
        try {
            $sms = new SmsService();
            $msg = "Kona Ya Hisabati: Your child account has been created. Username: $username, Claim Code: $claim_code. Use these to link your child on your parent dashboard.";
            $sms->sendSMS($parent_phone, $msg, 'parent_link', 'parent', $student_id);
        } catch (Exception $e) {
            error_log('SMS: ' . $e->getMessage());
        }
    }
    header('Location: ' . $redirect . '?success=1&code=' . urlencode($claim_code));
} else {
    header('Location: ' . $redirect . '?error=create_failed');
}
exit;



