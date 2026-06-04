<?php
require_once '../php/includes/session.php';
require_once '../php/includes/security.php';
require_once '../php/includes/csrf.php';
require_once '../php/db_connection.php';

sec_require_rate_limit();

// Check if user is logged in and is a parent
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
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
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>



