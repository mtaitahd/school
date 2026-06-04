<?php
require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/includes/validator.php';
require_once __DIR__ . '/php/db_connection.php';

sec_send_headers();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    try {
        $username = Validator::username($_POST['username'] ?? '');
        $email = Validator::email($_POST['email'] ?? '');
        $password = Validator::password($_POST['password'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = Validator::string($_POST['first_name'] ?? '', 1, 100);
        $last_name = Validator::string($_POST['last_name'] ?? '', 1, 100);
        $role = Validator::inArray($_POST['role'] ?? 'parent', ['teacher', 'parent']);
    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }

    if (empty($error)) {
        if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if username already exists
            $existing_user = $database->fetchOne(
                "SELECT user_id FROM users WHERE username = ?",
                [$username]
            );

            if ($existing_user) {
                $error = 'Username already exists. Please choose another one.';
            } else {
                // Check if email already exists (if provided)
                if (!empty($email)) {
                    $existing_email = $database->fetchOne(
                        "SELECT user_id FROM users WHERE email = ?",
                        [$email]
                    );

                    if ($existing_email) {
                        $error = 'Email already registered. Please use another email or login.';
                    }
                }

                if (empty($error)) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Insert new user
                    $user_id = $database->insert(
                        "INSERT INTO users (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)",
                        [$username, $email ?: null, $hashed_password, $role, $first_name, $last_name]
                    );

                    if ($user_id) {
                        $success = 'Registration successful! You can now login.';
                        $_POST = [];
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Kona Ya Hisabati</title>
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
                <h1 class="auth-form-title">Create Account</h1>
                <p class="auth-form-subtitle">Join Kona Ya Hisabati as a teacher or parent</p>
            </header>

            <?php if ($success): ?>
                <div class="alert-child alert-child-success text-center">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
                <div class="auth-form-footer">
                    <a href="login" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-sign-in-alt me-2"></i>Go to Login</span>
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
                    <div class="auth-form-grid">
                        <div class="form-group-child">
                            <label class="form-label-child" for="first_name">First Name</label>
                            <div class="auth-input-wrap">
                                <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                                <input type="text"
                                       class="form-control-child auth-input"
                                       id="first_name"
                                       name="first_name"
                                       placeholder="First name"
                                       required
                                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="form-group-child">
                            <label class="form-label-child" for="last_name">Last Name</label>
                            <div class="auth-input-wrap">
                                <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-user"></i></span>
                                <input type="text"
                                       class="form-control-child auth-input"
                                       id="last_name"
                                       name="last_name"
                                       placeholder="Last name"
                                       required
                                       value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="username">Username</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-at"></i></span>
                            <input type="text"
                                   class="form-control-child auth-input"
                                   id="username"
                                   name="username"
                                   placeholder="Choose a username"
                                   required
                                   autocomplete="username"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="email">Email (Optional)</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-envelope"></i></span>
                            <input type="email"
                                   class="form-control-child auth-input"
                                   id="email"
                                   name="email"
                                   placeholder="your@email.com"
                                   autocomplete="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="role">I am a:</label>
                        <select class="form-control-child" id="role" name="role" required>
                            <option value="parent" <?php echo (($_POST['role'] ?? '') === 'parent') ? 'selected' : ''; ?>>Parent</option>
                            <option value="teacher" <?php echo (($_POST['role'] ?? '') === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                        </select>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="password">Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
                            <input type="password"
                                   class="form-control-child auth-input"
                                   id="password"
                                   name="password"
                                   placeholder="Choose a password (min 6 characters)"
                                   required
                                   autocomplete="new-password">
                            <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group-child">
                        <label class="form-label-child" for="confirm_password">Confirm Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon" aria-hidden="true"><i class="fas fa-lock"></i></span>
                            <input type="password"
                                   class="form-control-child auth-input"
                                   id="confirm_password"
                                   name="confirm_password"
                                   placeholder="Confirm your password"
                                   required
                                   autocomplete="new-password">
                            <button type="button" class="auth-password-toggle" data-target="confirm_password" aria-label="Show password" aria-pressed="false">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                        <span class="auth-btn-text"><i class="fas fa-user-plus me-2"></i>Register</span>
                    </button>
                </form>

                <footer class="auth-form-footer">
                    <p>Already have an account? <a href="login">Sign in</a></p>
                    <a href="index" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Home</a>
                </footer>
            <?php endif; ?>
<?php include 'php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth-ui.js"></script>
</body>
</html>
