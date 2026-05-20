<?php
session_start();
require_once '../php/db_connection.php';

if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'learner') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    
    if (empty($username)) {
        $error = 'Please enter your username.';
    } else {
        // Check for learner account (no password required)
        $learner = $database->fetchOne(
            "SELECT * FROM users WHERE username = ? AND role = 'learner' AND is_active = 1",
            [$username]
        );
        
        if ($learner) {
            // Set session variables
            $_SESSION['user_id'] = $learner['user_id'];
            $_SESSION['username'] = $learner['username'];
            $_SESSION['role'] = $learner['role'];
            $_SESSION['first_name'] = $learner['first_name'];
            $_SESSION['last_name'] = $learner['last_name'];
            $_SESSION['profile_image'] = $learner['profile_image'] ?? '';
            $_SESSION['email'] = $learner['email'] ?? '';
            
            // Redirect to learning activities
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username not found. Please ask your parent or teacher for help.';
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
                <p class="auth-form-subtitle">Learner account — enter your username to start learning</p>
            </header>

            <?php if ($error): ?>
                <div class="alert-child alert-child-error text-center">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
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

                <button type="submit" class="btn-child btn-child-primary auth-submit-btn">
                    <span class="auth-btn-text"><i class="fas fa-play-circle me-2"></i>Start Learning</span>
                </button>
            </form>

            <footer class="auth-form-footer">
                <p style="font-size: 1rem; color: var(--text-light);">
                    <i class="fas fa-info-circle me-1"></i>
                    Ask your parent or teacher if you don't know your username
                </p>
                <a href="../index.php" class="auth-link-muted"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </footer>
<?php include '../php/includes/auth-split-end.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/auth-ui.js"></script>
</body>
</html>
