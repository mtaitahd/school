<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notification_id = intval($_POST['notification_id'] ?? 0);
    $parent_id = $_SESSION['user_id'];
    
    if ($notification_id > 0) {
        $result = $database->execute(
            "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?",
            [$notification_id, $parent_id]
        );
        
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>



