<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/db_connection.php';

sec_send_headers();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $user_id = (int) $_SESSION['user_id'];
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $upload_dir = 'uploads/profiles/';
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 2 * 1024 * 1024;

        if ($file['size'] > $max_size) {
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $referer . '?error=file_too_large');
            exit;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($detected_mime, $allowed_mimes, true)) {
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $referer . '?error=invalid_file_type');
            exit;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_exts, true)) {
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $referer . '?error=invalid_extension');
            exit;
        }

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = 'profile_' . $user_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $upload_path = $upload_dir . $file_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Delete old profile image if exists
            $old = $database->fetchOne("SELECT profile_image FROM users WHERE user_id = ?", [$user_id]);
            if ($old && !empty($old['profile_image']) && file_exists($old['profile_image'])) {
                @unlink($old['profile_image']);
            }

            $database->execute(
                "UPDATE users SET profile_image = ? WHERE user_id = ?",
                [$upload_path, $user_id]
            );
            $_SESSION['profile_image'] = $upload_path;
        }
    }

    // Update other fields
    $database->execute(
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
