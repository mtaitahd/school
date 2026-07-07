<?php
require_once __DIR__ . '/../php/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/settings.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$paymentEnabled = is_payment_enabled();
$startLearningRestricted = is_start_learning_restricted();

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'settings';
$dashboard_page_title = 'Settings';
$lang_page = 'settings.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Settings</h1>
        </div>

        <form id="toggleForm" method="POST" action="admin-toggle-setting">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="setting" value="payment_enabled">
        </form>

        <form id="toggleStartLearningForm" method="POST" action="admin-toggle-setting">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="setting" value="start_learning_restricted">
        </form>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Payment Configuration</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">Payment System</h5>
                        <p class="mb-0 text-muted">
                            <?php if ($paymentEnabled): ?>
                                Payment is <strong class="text-success">ON</strong> &mdash; users must have an active subscription or trial to access learning content.
                            <?php else: ?>
                                Payment is <strong class="text-danger">OFF</strong> &mdash; all users can access learning content for free.
                            <?php endif; ?>
                        </p>
                    </div>
                    <button class="btn btn-lg <?php echo $paymentEnabled ? 'btn-danger' : 'btn-success'; ?>" style="border:none;border-radius:50px;padding:10px 28px;font-family:'Poppins',sans-serif;font-weight:600;min-width:140px;" onclick="togglePayment()">
                        <i class="fas <?php echo $paymentEnabled ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-2"></i>
                        <?php echo $paymentEnabled ? 'Turn OFF' : 'Turn ON'; ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Start Learning Restriction</h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-1">Home Page Start Learning</h5>
                        <p class="mb-0 text-muted">
                            <?php if ($startLearningRestricted): ?>
                                Restricted &mdash; learners must have an active subscription and log in via the username prompt.
                            <?php else: ?>
                                Open &mdash; learners can use the Start Learning button and access content without login or subscription.
                            <?php endif; ?>
                        </p>
                    </div>
                    <button class="btn btn-lg <?php echo $startLearningRestricted ? 'btn-success' : 'btn-danger'; ?>" style="border:none;border-radius:50px;padding:10px 28px;font-family:'Poppins',sans-serif;font-weight:600;min-width:140px;" onclick="toggleStartLearning()">
                        <i class="fas <?php echo $startLearningRestricted ? 'fa-toggle-on' : 'fa-toggle-off'; ?> me-2"></i>
                        <?php echo $startLearningRestricted ? 'Turn OFF' : 'Turn ON'; ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">What this means</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <?php if ($paymentEnabled): ?>
                        <li>Users must have an <strong>active subscription</strong> or be within their <strong>trial period</strong> to access learning content.</li>
                        <li>Parents can subscribe via <strong>Mobile Money</strong>, <strong>Card</strong>, or <strong>Manual Payment</strong>.</li>
                        <li>Subscription costs <strong>1,500 TZS</strong> for 30 days.</li>
                        <li>New parents automatically get a <strong>5-day free trial</strong>.</li>
                    <?php else: ?>
                        <li>All users can access learning content <strong>completely free</strong>.</li>
                        <li>Subscription and trial checks are <strong>disabled</strong>.</li>
                        <li>Payment pages and verification are still accessible but will not block users.</li>
                        <li>Turn payment ON to start requiring subscriptions again.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        var paymentEnabled = <?php echo $paymentEnabled ? 'true' : 'false'; ?>;
        var startLearningRestricted = <?php echo $startLearningRestricted ? 'true' : 'false'; ?>;
        function togglePayment() {
            confirmAction('Toggle Payment', 'Toggle payment system ' + (paymentEnabled ? 'OFF' : 'ON') + '?', 'Toggle').then(function(c) {
                if (c) document.getElementById('toggleForm').submit();
            });
        }
        function toggleStartLearning() {
            confirmAction('Toggle Start Learning Restriction', 'Turn ' + (startLearningRestricted ? 'OFF' : 'ON') + ' the start learning restriction? When OFF, learners can learn without login.', 'Toggle').then(function(c) {
                if (c) document.getElementById('toggleStartLearningForm').submit();
            });
        }
    </script>
</body>
</html>
