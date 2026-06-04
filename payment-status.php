<?php
/**
 * Payment Status Page
 * Shows real-time payment status after initiating a payment
 */

require_once __DIR__ . '/php/includes/session.php';
require_once __DIR__ . '/php/includes/security.php';
require_once __DIR__ . '/php/includes/csrf.php';
require_once __DIR__ . '/php/db_connection.php';
require_once __DIR__ . '/php/includes/auth.php';
require_once __DIR__ . '/php/includes/subscription.php';
require_once __DIR__ . '/php/includes/payment.php';

sec_require_rate_limit();
sec_send_headers();

$parentId = auth_user_id();
$role = auth_role();

if ($role !== 'parent') {
    if ($role === 'admin') { header('Location: admin/dashboard.php'); exit; }
    if ($role === 'teacher') { header('Location: teacher/dashboard.php'); exit; }
    header('Location: index.php'); exit;
}

$ref = $_GET['ref'] ?? '';
if (!$ref) {
    header('Location: topup.php'); exit;
}

$payment = $database->fetchOne(
    "SELECT * FROM `payments` WHERE reference = ? AND parent_id = ? LIMIT 1",
    [$ref, $parentId]
);

if (!$payment) {
    header('Location: topup.php'); exit;
}

$amount = number_format((float) $payment['amount']) . ' ' . $payment['currency'];
$isMobile = $payment['method'] === 'snippe';
$isManual = $payment['method'] === 'manual';
$initialStatus = $payment['status'];

$current_lang = $_SESSION['lang'] ?? 'en';
$dashboard_role = 'parent';
$sidebar_active = 'topup';
$dashboard_page_title = 'Payment Status';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'sw' ? 'sw' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status - Smart Math Corner</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .status-card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .status-card .card-header-custom { padding: 1.75rem 2rem; }
        .status-card .card-body { padding: 2rem; }
        .status-icon-circle { width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1rem; }
        .ref-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.75rem 1rem; font-family: monospace; font-size: 1rem; letter-spacing: 0.5px; }
        .poll-indicator { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
    </style>
</head>
<body class="dashboard-body">

<?php include 'php/includes/dashboard-start.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">

        <div class="card status-card mb-4">
            <!-- Header color changes with status -->
            <div class="card-header-custom text-white" id="statusHeader" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">
                <div class="text-center">
                    <div class="status-icon-circle bg-white bg-opacity-25 mx-auto" id="statusIconCircle">
                        <i class="fas fa-spinner fa-pulse text-white" id="statusIcon"></i>
                    </div>
                    <h3 class="fw-bold mb-1" id="statusTitle">Payment Status</h3>
                    <p class="mb-0 small" style="opacity:0.85;" id="statusSubtitle">Processing your payment...</p>
                </div>
            </div>
            <div class="card-body text-center">

                <!-- Reference -->
                <div class="mb-3">
                    <small class="text-muted text-uppercase fw-semibold">Reference</small>
                    <div class="ref-box text-center mt-1" id="paymentRef"><?= htmlspecialchars($payment['reference']) ?></div>
                </div>

                <!-- Amount -->
                <div class="mb-3">
                    <small class="text-muted text-uppercase fw-semibold">Amount</small>
                    <div class="fw-bold fs-4" id="paymentAmount"><?= $amount ?></div>
                </div>

                <!-- Payment method badge -->
                <div class="mb-4">
                    <?php if ($isMobile): ?>
                        <span class="badge bg-info rounded-pill px-3 py-2"><i class="fas fa-mobile-alt me-1"></i> Mobile Money</span>
                    <?php elseif ($payment['method'] === 'snippe_card'): ?>
                        <span class="badge bg-primary rounded-pill px-3 py-2"><i class="fas fa-credit-card me-1"></i> Card Payment</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="fas fa-hand-holding-usd me-1"></i> Manual</span>
                    <?php endif; ?>
                </div>

                <!-- Status Message Area -->
                <div id="statusMessageArea" class="mb-4">
                    <div class="alert alert-primary border-0 rounded-3" id="statusMessageAlert">
                        <div class="d-flex align-items-center gap-2 justify-content-center">
                            <span class="poll-indicator"></span>
                            <span id="statusMessageText">
                                <?php if ($initialStatus === 'completed'): ?>
                                    Malipo yamefanikiwa.
                                <?php elseif ($initialStatus === 'failed'): ?>
                                    Malipo hayajakamilika. Tafadhali jaribu tena.
                                <?php elseif ($initialStatus === 'manual_review'): ?>
                                    Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.
                                <?php else: ?>
                                    <?php if ($isMobile): ?>
                                        Ombi la malipo limetumwa kwenye simu yako. Tafadhali angalia simu yako na uingize siri yako ya Mobile Money kuthibitisha malipo.
                                    <?php else: ?>
                                        Tunasubiri uthibitisho wa malipo. Tafadhali kamilisha ombi kwenye simu yako.
                                    <?php endif; ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Action buttons (hidden initially, shown on completion/failure) -->
                <div id="actionArea" style="display:none;">
                    <hr class="my-4">
                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                        <a href="parent/dashboard.php" class="btn btn-primary rounded-pill px-4" id="dashboardBtn">
                            <i class="fas fa-tachometer-alt me-2"></i> Go to Dashboard
                        </a>
                        <a href="topup.php" class="btn btn-outline-secondary rounded-pill px-4" id="retryBtn" style="display:none;">
                            <i class="fas fa-redo me-2"></i> Try Again
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- Auto-refresh note for manual payments -->
        <?php if ($isManual): ?>
        <div class="text-center">
            <small class="text-muted">Refresh this page to check the latest status.</small>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'php/includes/dashboard-end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const ref = '<?= htmlspecialchars($payment['reference']) ?>';
const isManual = <?= $isManual ? 'true' : 'false' ?>;
const initialStatus = '<?= $initialStatus ?>';

function updateUI(data) {
    const icon = document.getElementById('statusIcon');
    const iconCircle = document.getElementById('statusIconCircle');
    const header = document.getElementById('statusHeader');
    const title = document.getElementById('statusTitle');
    const subtitle = document.getElementById('statusSubtitle');
    const alert = document.getElementById('statusMessageAlert');
    const text = document.getElementById('statusMessageText');
    const actionArea = document.getElementById('actionArea');
    const retryBtn = document.getElementById('retryBtn');

    // Update icon and colors based on status
    icon.className = 'fas text-white';
    alert.className = 'alert border-0 rounded-3';

    switch (data.status) {
        case 'completed':
            icon.className += ' fa-check-circle';
            iconCircle.style.background = 'rgba(255,255,255,0.25)';
            header.style.background = 'linear-gradient(135deg, #16a34a, #15803d)';
            alert.className += ' alert-success';
            title.textContent = 'Payment Successful';
            subtitle.textContent = 'Your subscription is now active.';
            text.innerHTML = '<i class="fas fa-check-circle me-2"></i> Malipo yamefanikiwa. Usajili wako umewezeshwa.';
            actionArea.style.display = 'block';
            retryBtn.style.display = 'none';
            stopPolling = true;
            break;

        case 'failed':
            icon.className += ' fa-times-circle';
            iconCircle.style.background = 'rgba(255,255,255,0.25)';
            header.style.background = 'linear-gradient(135deg, #dc2626, #b91c1c)';
            alert.className += ' alert-danger';
            title.textContent = 'Payment Failed';
            subtitle.textContent = 'The payment could not be completed.';
            text.innerHTML = '<i class="fas fa-times-circle me-2"></i> Malipo hayajakamilika. Tafadhali jaribu tena.';
            actionArea.style.display = 'block';
            retryBtn.style.display = 'inline-block';
            stopPolling = true;
            break;

        case 'manual_review':
            icon.className += ' fa-clock';
            iconCircle.style.background = 'rgba(255,255,255,0.25)';
            header.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
            alert.className += ' alert-warning';
            title.textContent = 'Under Review';
            subtitle.textContent = 'Your manual payment is being verified.';
            text.innerHTML = '<i class="fas fa-clock me-2"></i> Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.';
            actionArea.style.display = 'block';
            retryBtn.style.display = 'none';
            stopPolling = true;
            break;

        default: // pending
            icon.className += ' fa-spinner fa-pulse';
            iconCircle.style.background = 'rgba(255,255,255,0.25)';
            header.style.background = 'linear-gradient(135deg, #2563eb, #1d4ed8)';
            alert.className += ' alert-primary';
            title.textContent = 'Payment Pending';
            subtitle.textContent = 'Waiting for confirmation...';
            if (isManual) {
                text.innerHTML = '<i class="fas fa-clock me-2"></i> Malipo yako yamewasilishwa kwa uhakiki. Subiri uthibitisho wa admin.';
            } else if (data.method === 'mobile') {
                text.innerHTML = '<span class="poll-indicator me-2"></span> Ombi la malipo limetumwa kwenye simu yako. Tafadhali angalia simu yako na uingize siri yako ya Mobile Money kuthibitisha malipo.';
            } else {
                text.innerHTML = '<span class="poll-indicator me-2"></span> Tunasubiri uthibitisho wa malipo. Tafadhali kamilisha ombi kwenye simu yako.';
            }
            actionArea.style.display = 'none';
            break;
    }

    if (data.amount) {
        document.getElementById('paymentAmount').textContent = data.amount;
    }
}

let stopPolling = false;

function pollStatus() {
    if (stopPolling || isManual) return;

    fetch('api/payment-status-check.php?ref=' + encodeURIComponent(ref))
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error('Poll error:', data.error);
                return;
            }
            updateUI(data);
        })
        .catch(err => console.error('Poll fetch error:', err));
}

// Poll every 5 seconds
if (!isManual && initialStatus === 'pending') {
    setInterval(pollStatus, 5000);
    // Also poll immediately after a short delay
    setTimeout(pollStatus, 2000);
}

// If already completed/failed on load, show final state
if (initialStatus !== 'pending') {
    // Fetch once to show proper state
    setTimeout(pollStatus, 500);
}
</script>
</body>
</html>
