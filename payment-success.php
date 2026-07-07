<?php
/**
 * Payment Success Page
 * Customer is redirected here by Snippe after successful card payment.
 * IMPORTANT: Never activate subscription from this page alone.
 * Only the webhook (payment.completed) can activate the subscription.
 */

require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/auth.php';
require_once __DIR__ . '/php/includes/subscription.php';
require_once __DIR__ . '/php/includes/settings.php';

sec_send_headers();

if (!is_payment_enabled()) {
    header('Location: parent/dashboard');
    exit;
}

$parentId = auth_user_id();
$role = auth_role();

if ($role !== 'parent') {
    if ($role === 'admin') { header('Location: admin/dashboard'); exit; }
    if ($role === 'teacher') { header('Location: teacher/dashboard'); exit; }
    header('Location: index'); exit;
}

$ref = $_GET['ref'] ?? '';
$payment = null;
$verified = false;

if ($ref) {
    $payment = $database->fetchOne(
        "SELECT * FROM payments WHERE reference = ? AND parent_id = ? LIMIT 1",
        [$ref, $parentId]
    );

    if ($payment && $payment['status'] === 'completed') {
        $verified = true;
    }
}

$current_lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'sw' ? 'sw' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Success - Smart Math Corner</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .result-container { max-width:500px; margin:4rem auto; text-align:center; }
        .result-icon { font-size:4rem; margin-bottom:1rem; }
        .spinner-border { width:3rem; height:3rem; }
    </style>
</head>
<body class="dashboard-body">
    <div class="container">
        <div class="result-container">
            <?php if ($verified): ?>
                <div class="result-icon" style="color:var(--primary-green);">&#10004;</div>
                <h2 class="fw-bold mb-3" style="color:var(--text-dark);">
                    <?= $current_lang === 'sw' ? 'Malipo Yamekamilishwa' : 'Payment Successful' ?>
                </h2>
                <p class="text-muted mb-4">
                    <?= $current_lang === 'sw'
                        ? 'Malipo yako yamepokelewa na kuthibitishwa. Usajili wako umewezeshwa.'
                        : 'Payment received and verified. Your subscription is now active.' ?>
                </p>
                <a href="parent/dashboard" class="btn btn-primary btn-lg px-5" style="border-radius:50px;background:var(--primary-blue);border:none;">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <?= $current_lang === 'sw' ? 'Nenda kwenye Dashibodi' : 'Go to Dashboard' ?>
                </a>
            <?php else: ?>
                <div class="spinner-border text-primary mb-4" role="status"></div>
                <h2 class="fw-bold mb-3" style="color:var(--text-dark);">
                    <?= $current_lang === 'sw' ? 'Malipo Yamepokelewa' : 'Payment Received' ?>
                </h2>
                <p class="text-muted mb-4">
                    <?= $current_lang === 'sw'
                        ? 'Malipo yako yamepokelewa. Tunathibitisha muamala... Hii inachukua muda mfupi.'
                        : 'Payment received successfully. Verifying transaction... This may take a moment.' ?>
                </p>
                <?php if ($ref): ?>
                    <a href="payment-status?ref=<?= urlencode($ref) ?>" class="btn btn-outline-primary btn-lg px-4 mb-3" style="border-radius:50px;">
                        <i class="fas fa-sync me-2"></i>
                        <?= $current_lang === 'sw' ? 'Angalia Hali ya Malipo' : 'Check Payment Status' ?>
                    </a>
                <?php endif; ?>
                <div>
                    <a href="parent/dashboard" class="btn btn-primary btn-lg px-5" style="border-radius:50px;background:var(--primary-blue);border:none;">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        <?= $current_lang === 'sw' ? 'Nenda kwenye Dashibodi' : 'Go to Dashboard' ?>
                    </a>
                </div>
                <script>
                    // Auto-poll for status update
                    setTimeout(function() {
                        if ('<?= $ref ?>') {
                            fetch('api/payment-status-check.php?ref=<?= urlencode($ref) ?>')
                                .then(r => r.json())
                                .then(data => {
                                    if (data.status === 'completed') {
                                        location.reload();
                                    }
                                })
                                .catch(() => {});
                        }
                    }, 5000);
                    setInterval(function() {
                        if ('<?= $ref ?>') {
                            fetch('api/payment-status-check.php?ref=<?= urlencode($ref) ?>')
                                .then(r => r.json())
                                .then(data => {
                                    if (data.status === 'completed') {
                                        location.reload();
                                    }
                                })
                                .catch(() => {});
                        }
                    }, 10000);
                </script>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
