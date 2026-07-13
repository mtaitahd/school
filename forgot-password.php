<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/includes/auth.php';
require_once __DIR__ . '/php/db_connection.php';

sec_require_rate_limit();
sec_send_headers();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'teacher') { header('Location: teacher/dashboard'); }
    elseif ($role === 'parent') { header('Location: parent/dashboard'); }
    elseif ($role === 'admin') { header('Location: admin/dashboard'); }
    else { header('Location: index'); }
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Find user by email
        $user = $database->fetchOne(
            "SELECT user_id, first_name, last_name, email FROM users WHERE LOWER(email) = LOWER(?) AND is_active = 1",
            [$email]
        );

        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Delete any existing tokens for this user
            $database->execute(
                "DELETE FROM password_resets WHERE user_id = ?",
                [$user['user_id']]
            );

            // Insert new token
            $database->execute(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                [$user['user_id'], $token, $expires]
            );

            // Build reset URL
            $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
                . '://' . $_SERVER['HTTP_HOST']
                . dirname($_SERVER['REQUEST_URI'])
                . '/reset-password.php?token=' . $token;

            // Send email
            require_once __DIR__ . '/php/email_service.php';
            $mailer = new EmailService();
            $userName = trim($user['first_name'] . ' ' . $user['last_name']);

            if ($mailer->sendPasswordReset($user['email'], $userName, $resetUrl)) {
                $success = 'A password reset link has been sent to your email address. Please check your inbox.';
            } else {
                $error = 'Failed to send email. Please try again later or contact support.';
                // Log the error but still show success to user (don't reveal if email exists)
            }
        } else {
            // Always show success even if email not found (security)
            $success = 'If an account with that email exists, a password reset link has been sent.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Kona Ya Hisabati</title>
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
                <h1 class="auth-form-title">Forgot Password?</h1>
                <p class="auth-form-subtitle">Enter your email address and we'll send you a link to reset your password.</p>
            </header>

            <?php if ($success): ?>
                <div class="alert-child alert-child-success text-center">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
                <div class="auth-form-footer">
                    <a href="login" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-sign-in-alt me-2"></i>Back to Login</span>
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
                        <label class="form-label-child" for="email">Email Address</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                            <input type="email"
                                   class="form-control-child auth-input"
                                   id="email"
                                   name="email"
                                   placeholder="Enter your registered email"
                                   required
                                   autocomplete="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-paper-plane me-2"></i>Send Reset Link</span>
                    </button>
                </form>

                <footer class="auth-form-footer">
                    <p>Remember your password? <a href="login">Sign in</a></p>
                    <a href="index" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Home</a>
                </footer>
            <?php endif; ?>
<?php include 'php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth-ui.js"></script>
</body>
</html>
