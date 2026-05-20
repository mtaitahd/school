<?php
session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Location: ../login.php');
    exit;
}

$parent_id = (int) $_SESSION['user_id'];
$teacher_id = isset($_POST['teacher_id']) ? intval($_POST['teacher_id']) : 0;
$child_id = isset($_POST['child_id']) ? intval($_POST['child_id']) : 0;
$activity_id = isset($_POST['activity_id']) && $_POST['activity_id'] !== '' ? intval($_POST['activity_id']) : null;
$message = trim($_POST['message'] ?? '');

if ($child_id === 0 || $teacher_id === 0 || $message === '') {
    header('Location: child-progress.php?child_id=' . $child_id . '&sent=0');
    exit;
}

// Verify parent-child link
$linked = $database->fetchOne(
    "SELECT 1 FROM parent_student_links WHERE parent_id = ? AND student_id = ? AND is_active = 1
     UNION SELECT 1 FROM users WHERE user_id = ? AND parent_id = ? LIMIT 1",
    [$parent_id, $child_id, $child_id, $parent_id]
);
if (!$linked) {
    header('Location: dashboard.php');
    exit;
}

// Verify teacher exists
$teacher = $database->fetchOne("SELECT user_id FROM users WHERE user_id = ? AND role = 'teacher'", [$teacher_id]);
if (!$teacher) {
    header('Location: child-progress.php?child_id=' . $child_id . '&sent=0');
    exit;
}

// Save note into parent_notes (table should be created by migration)
try {
    $database->execute(
        "INSERT INTO parent_notes (parent_id, teacher_id, child_id, activity_id, message) VALUES (?, ?, ?, ?, ?)",
        [$parent_id, $teacher_id, $child_id, $activity_id, $message]
    );
    $success = 1;
} catch (Exception $e) {
    $success = 0;
}

header('Location: child-progress.php?child_id=' . $child_id . '&sent=' . $success);
exit;
?>