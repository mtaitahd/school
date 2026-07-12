<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/settings.php';

sec_require_rate_limit();
sec_send_headers();

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'learner') {
    header('Location: dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $input = strtolower(trim($_POST['username'] ?? ($_GET['username'] ?? '')));

    if (empty($input)) {
        $error = 'Please enter your username or claim code.';
    } elseif (!sec_login_rate_limit($input)) {
        $error = 'Too many login attempts. Please try again in 15 minutes.';
    } else {
        // Try claim code first (KH-XXXXXX format), then username
        if (preg_match('/^KH-[A-Z0-9]{6}$/i', $input)) {
            $learner = $database->fetchOne(
                "SELECT * FROM users WHERE UPPER(claim_code) = UPPER(?) AND role = 'learner' AND is_active = 1",
                [$input]
            );
        } else {
            $learner = $database->fetchOne(
                "SELECT * FROM users WHERE LOWER(username) = LOWER(?) AND role = 'learner' AND is_active = 1",
                [$input]
            );
        }

        if ($learner) {
            // Bypass subscription check if payment is disabled
            if (is_payment_enabled()) {
                require_once __DIR__ . '/../php/includes/SubscriptionMiddleware.php';
                $trialInfo = SubscriptionMiddleware::getLearnerTrialInfo((int) $learner['user_id']);
                if (!$trialInfo['is_active']) {
                    $error = 'Tafadhali mwambie mzazi wako alipe ada yako ili uweze kuendelea na masomo.';
                }
            }
            if (empty($error)) {
                sec_clear_login_rate_limit($input);
                sec_session_regenerate();
                $_SESSION['user_id'] = (int) $learner['user_id'];
                $_SESSION['username'] = $learner['username'];
                $_SESSION['role'] = $learner['role'];
                $_SESSION['first_name'] = $learner['first_name'];
                $_SESSION['last_name'] = $learner['last_name'];
                $_SESSION['profile_image'] = $learner['profile_image'] ?? '';
                $_SESSION['email'] = $learner['email'] ?? '';
                $_SESSION['_CREATED'] = time();

                header('Location: dashboard');
                exit;
            }
        } else {
            $error = 'Username or claim code not found. Please ask your parent or teacher for help.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page auth-split-page">
<?php
$auth_base = '../';
include '../php/includes/auth-split-start.php';
?>
            <header class="auth-form-header">
                <h1 class="auth-form-title">Login</h1>
                <p class="auth-form-subtitle">Learner account &mdash; enter your username or claim code to start learning</p>
            </header>

            <?php if ($error): ?>
                <div class="alert-child alert-child-error text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <?php echo csrf_field(); ?>
                <div class="form-group-child">
                    <label class="form-label-child" for="username">Username or Claim Code</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <input type="text"
                               class="form-control-child auth-input"
                               id="username"
                               name="username"
                               placeholder="e.g. SMART/chil/001 or KH-AB12CD"
                               required
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ($_GET['username'] ?? '')); ?>">
                    </div>
                </div>

                <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                    <span class="auth-btn-text"><i class="fas fa-play-circle me-2"></i>Start Learning</span>
                </button>
            </form>

            <footer class="auth-form-footer">
                <p style="font-size: 1rem; color: var(--text-light);">
                    <i class="fas fa-info-circle me-1"></i>
                    Enter your claim code (KH-XXXXXX) or ask your teacher for your username
                </p>
                <a href="../index" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </footer>
<?php include '../php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/auth-ui.js"></script>
</body>
</html>
