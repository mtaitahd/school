<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();
sec_send_headers();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'parent') {
        header('Location: dashboard');
    } else {
        header('Location: ../index');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (!sec_login_rate_limit($username)) {
        $error = 'Too many login attempts. Please try again in 15 minutes.';
    } else {
        // Check database for parent user
        $user = $database->fetchOne(
            "SELECT * FROM users WHERE username = ? AND role = 'parent' AND is_active = 1",
            [$username]
        );

        if ($user && password_verify($password, $user['password'])) {
            sec_clear_login_rate_limit($username);
            auth_login($user);
            header('Location: dashboard');
            exit;
        } else {
            $error = 'Invalid username or password.';
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
                <p class="auth-form-subtitle">Parent account &mdash; enter your credentials to access your dashboard</p>
            </header>

            <?php if ($error): ?>
                <div class="alert-child alert-child-error text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <?php echo csrf_field(); ?>
                <div class="form-group-child">
                    <label class="form-label-child" for="username">Username</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                        <input type="text"
                               class="form-control-child auth-input"
                               id="username"
                               name="username"
                               placeholder="Enter your username"
                               required
                               autocomplete="username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group-child">
                    <label class="form-label-child" for="password">Password</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
                        <input type="password"
                               class="form-control-child auth-input"
                               id="password"
                               name="password"
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                    <span class="auth-btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login</span>
                </button>
            </form>

            <footer class="auth-form-footer">
                <p>Don't have an account? <a href="../register">Register here</a></p>
                <a href="../index" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </footer>
<?php include '../php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/auth-ui.js"></script>
</body>
</html>
