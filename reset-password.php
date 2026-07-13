<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/includes/auth.php';
require_once __DIR__ . '/php/db_connection.php';

sec_require_rate_limit();
sec_send_headers();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$validToken = false;
$userData = null;

if (empty($token)) {
    $error = 'Invalid or missing reset token.';
} else {
    // Look up token
    $resetRecord = $database->fetchOne(
        "SELECT pr.*, u.user_id, u.first_name, u.last_name, u.email
         FROM password_resets pr
         JOIN users u ON pr.user_id = u.user_id
         WHERE pr.token = ? AND pr.expires_at > NOW()",
        [$token]
    );

    if ($resetRecord) {
        $validToken = true;
        $userData = $resetRecord;
    } else {
        $error = 'This password reset link is invalid or has expired. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    csrf_require();

    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $database->execute(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?",
            [$hashedPassword, $userData['user_id']]
        );

        // Delete the used token (and any other tokens for this user)
        $database->execute(
            "DELETE FROM password_resets WHERE user_id = ?",
            [$userData['user_id']]
        );

        $success = 'Your password has been reset successfully! You can now login with your new password.';
        $validToken = false; // Prevent form from showing again
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page auth-split-page">
<?php
$auth_base = '';
include 'php/includes/auth-split-start.php';
?>
            <header class="auth-form-header">
                <h1 class="auth-form-title">Reset Password</h1>
                <p class="auth-form-subtitle">Create a new password for your account</p>
            </header>

            <?php if ($success): ?>
                <div class="alert-child alert-child-success text-center">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
                <div class="auth-form-footer">
                    <a href="login" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login Now</span>
                    </a>
                </div>
            <?php elseif ($validToken): ?>
                <?php if ($error): ?>
                    <div class="alert-child alert-child-error text-center">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="form-group-child">
                        <label class="form-label-child" for="password">New Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
                            <input type="password"
                                   class="form-control-child auth-input"
                                   id="password"
                                   name="password"
                                   placeholder="Enter new password (min 6 characters)"
                                   required
                                   minlength="6"
                                   autocomplete="new-password">
                            <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="confirm_password">Confirm New Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
                            <input type="password"
                                   class="form-control-child auth-input"
                                   id="confirm_password"
                                   name="confirm_password"
                                   placeholder="Confirm your new password"
                                   required
                                   minlength="6"
                                   autocomplete="new-password">
                            <button type="button" class="auth-password-toggle" data-target="confirm_password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-key me-2"></i>Reset Password</span>
                    </button>
                </form>
            <?php else: ?>
                <div class="alert-child alert-child-error text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <footer class="auth-form-footer">
                    <a href="forgot-password.php" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-redo me-2"></i>Request New Link</span>
                    </a>
                    <br><br>
                    <a href="login" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </footer>
            <?php endif; ?>
<?php include 'php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth-ui.js"></script>
</body>
</html>
