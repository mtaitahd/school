<?php
/**
 * Payment Failed / Cancelled Page
 * Customer is redirected here by Snippe after cancelled or failed card payment.
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
$current_lang = $_SESSION['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'sw' ? 'sw' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Smart Math Corner</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .result-container { max-width:500px; margin:4rem auto; text-align:center; }
        .result-icon { font-size:4rem; margin-bottom:1rem; }
    </style>
</head>
<body class="dashboard-body">
    <div class="container">
        <div class="result-container">
            <div class="result-icon" style="color:var(--primary-red);">&#10008;</div>
            <h2 class="fw-bold mb-3" style="color:var(--text-dark);">
                <?= $current_lang === 'sw' ? 'Malipo Yameghairiwa au Hayakufanikiwa' : 'Payment Cancelled or Failed' ?>
            </h2>
            <p class="text-muted mb-4">
                <?= $current_lang === 'sw'
                    ? 'Malipo yako yameghairiwa au hayakufanikiwa. Tafadhali jaribu tena.'
                    : 'Your payment was cancelled or failed. Please try again.' ?>
            </p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="payment" class="btn btn-primary btn-lg px-5" style="border-radius:50px;background:var(--primary-blue);border:none;">
                    <i class="fas fa-redo me-2"></i>
                    <?= $current_lang === 'sw' ? 'Jaribu Tena' : 'Try Again' ?>
                </a>
                <a href="parent/dashboard" class="btn btn-outline-secondary btn-lg px-5" style="border-radius:50px;">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    <?= $current_lang === 'sw' ? 'Nenda kwenye Dashibodi' : 'Go to Dashboard' ?>
                </a>
            </div>
            <?php if ($ref): ?>
                <p class="mt-4 text-muted small">
                    <?= $current_lang === 'sw' ? 'Rejea' : 'Reference' ?>: <strong><?= htmlspecialchars($ref) ?></strong>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
