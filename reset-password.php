<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/includes/auth.php';
require_once __DIR__ . '/php/db_connection.php';

sec_require_rate_limit();
sec_send_headers();

$error = '';
$success = '';
$validCode = false;
$userData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $code = strtoupper(trim($_POST['code'] ?? ''));
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Step 1: Validate code
    if (empty($code)) {
        $error = 'Please enter the reset code.';
    } elseif (empty($newPassword)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        // Look up code
        $resetRecord = $database->fetchOne(
            "SELECT pr.*, u.user_id, u.first_name, u.last_name, u.email
             FROM password_resets pr
             JOIN users u ON pr.user_id = u.user_id
             WHERE UPPER(pr.token) = ? AND pr.expires_at > NOW()",
            [$code]
        );

        if ($resetRecord) {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $database->execute(
                "UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?",
                [$hashedPassword, $resetRecord['user_id']]
            );

            // Delete the used code (and any other codes for this user)
            $database->execute(
                "DELETE FROM password_resets WHERE user_id = ?",
                [$resetRecord['user_id']]
            );

            $success = 'Your password has been reset successfully! You can now login with your new password.';
        } else {
            $error = 'Invalid or expired reset code. Please request a new one.';
            $validCode = true; // Keep the form visible
        }
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
    <style>
        .code-input {
            font-size: 1.5rem !important;
            letter-spacing: 0.5rem;
            text-align: center;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body class="auth-page auth-split-page">
<?php
$auth_base = '';
include 'php/includes/auth-split-start.php';
?>
            <header class="auth-form-header">
                <h1 class="auth-form-title">Reset Password</h1>
                <p class="auth-form-subtitle">Enter the code from your email and create a new password</p>
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
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert-child alert-child-error text-center">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <?php echo csrf_field(); ?>

                    <div class="form-group-child">
                        <label class="form-label-child" for="code">Reset Code</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-key"></i></span>
                            <input type="text"
                                   class="form-control-child auth-input code-input"
                                   id="code"
                                   name="code"
                                   placeholder="XXXX8888"
                                   required
                                   maxlength="8"
                                   autocomplete="off"
                                   value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>">
                        </div>
                    </div>

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

                <footer class="auth-form-footer">
                    <p>Didn't receive a code? <a href="forgot-password.php">Send again</a></p>
                    <a href="login" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </footer>
            <?php endif; ?>
<?php include 'php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth-ui.js"></script>
</body>
</html>
