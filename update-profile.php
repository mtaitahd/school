<?php
session_start();
require_once 'php/db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                // Update profile image in database
                $database->query(
                    "UPDATE users SET profile_image = ? WHERE user_id = ?",
                    [$upload_path, $user_id]
                );
                $_SESSION['profile_image'] = $upload_path;
            }
        }
    }
    
    // Update other fields
    $database->query(
        "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?",
        [$first_name, $last_name, $email, $user_id]
    );
    
    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name'] = $last_name;
    $_SESSION['email'] = $email;
    
    // Redirect back to referring page
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header('Location: ' . $referer);
    exit;
}
