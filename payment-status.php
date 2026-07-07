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
require_once __DIR__ . '/php/includes/settings.php';

sec_require_rate_limit();
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
if (!$ref) {
    header('Location: payment'); exit;
}

$payment = $database->fetchOne(
    "SELECT * FROM `payments` WHERE reference = ? AND parent_id = ? LIMIT 1",
    [$ref, $parentId]
);

if (!$payment) {
    header('Location: payment'); exit;
}

// Handle cancel action before status variables
$cancelled = $payment['admin_note'] === 'cancelled_by_user';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_payment'])) {
    csrf_require();
    if (in_array($payment['status'], ['pending', 'manual_review'])) {
        if ($payment['method'] === 'snippe' && $payment['transaction_id']) {
            // Call Snippe API to void the payment
            $cancelResult = pay_cancel_snippe_payment((int) $payment['id'], $payment['transaction_id']);
            if (!$cancelResult['api_cancelled']) {
                error_log('Snippe void API failed for ' . $payment['reference'] . ': ' . ($cancelResult['error'] ?? 'unknown'));
            }
        } else {
            $database->execute(
                "UPDATE `payments` SET status = 'failed', admin_note = 'cancelled_by_user' WHERE id = ? AND parent_id = ?",
                [$payment['id'], $parentId]
            );
        }
        $payment['status'] = 'failed';
        $cancelled = true;
        // Notify user via SMS
        if (!empty($payment['phone'])) {
            try {
                require_once __DIR__ . '/php/sms_service.php';
                $sms = new SmsService();
                $msg = 'Smart Math Corner: Malipo yako yameghairiwa. Rejea: ' . $payment['reference'] . '. Kiasi: ' . number_format((float) $payment['amount']) . ' ' . ($payment['currency'] ?? 'TZS') . '.';
                $sms->sendSMS($payment['phone'], $msg, 'payment_cancelled', 'user', $parentId);
            } catch (Exception $e) {
                error_log('User cancel SMS notification failed: ' . $e->getMessage());
            }
        }
    }
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
        .swal-payment-detail { display:flex; align-items:center; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid #f1f5f9; }
        .swal-payment-detail:last-child { border-bottom:none; }
        .swal-payment-detail .label { font-size:0.75rem; text-transform:uppercase; letter-spacing:0.5px; color:#94a3b8; font-weight:600; }
        .swal-payment-detail .value { font-weight:700; color:#1e293b; }
        .swal-ref-box { font-family:'SF Mono',Consolas,monospace; font-size:0.9rem; color:#475569; letter-spacing:0.5px; }
        .swal-badge { font-size:0.75rem; font-weight:600; }
        .swal-badge-mobile { color:#0369a1; }
        .swal-badge-card { color:#6d28d9; }
        .swal-badge-manual { color:#b45309; }
        .swal-status-icon { font-size:3rem; margin-bottom:0.5rem; }
        .swal-status-icon.pending { color:#2563eb; animation:swal-pulse 1.5s infinite; }
        @keyframes swal-pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }
        .swal-action-btn { display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.5rem; border-radius:50px; font-weight:600; font-size:0.9rem; text-decoration:none; transition:all 0.15s; margin:0.25rem; }
        .swal-action-btn-primary { background:#2563eb; color:#fff; border:none; }
        .swal-action-btn-primary:hover { background:#1d4ed8; color:#fff; }
        .swal-action-btn-outline { background:transparent; border:1.5px solid #e2e8f0; color:#475569; }
        .swal-action-btn-outline:hover { border-color:#cbd5e1; color:#1e293b; }
        .swal-action-btn-danger { background:transparent; border:1.5px solid #fecaca; color:#dc2626; }
        .swal-action-btn-danger:hover { background:#fef2f2; }
        .swal-spinner { display:inline-block; width:8px; height:8px; border-radius:50%; background:#3b82f6; animation:swal-pulse 1.5s infinite; margin-right:0.5rem; }
    </style>
</head>
<body class="dashboard-body">

<?php
$badgeHtml = '';
$badgeClass = '';
if ($isMobile) {
    $badgeClass = 'swal-badge-mobile';
    $badgeHtml = '<i class="fas fa-mobile-alt"></i> Mobile Money';
} elseif ($payment['method'] === 'snippe_card') {
    $badgeClass = 'swal-badge-card';
    $badgeHtml = '<i class="fas fa-credit-card"></i> Card Payment';
} else {
    $badgeClass = 'swal-badge-manual';
    $badgeHtml = '<i class="fas fa-hand-holding-usd"></i> Manual';
}

// Build initial status message
if ($cancelled) {
    $statusMessage = 'Malipo yameghairiwa. Unaweza kujaribu tena wakati wowote.';
} elseif ($initialStatus === 'completed') {
    $statusMessage = 'Malipo yamefanikiwa. Usajili wako umewezeshwa.';
} elseif ($initialStatus === 'failed') {
    $failureReason = '';
    $apiResp = $payment['api_response'] ? json_decode($payment['api_response'], true) : null;
    if ($apiResp) {
        $respData = $apiResp['data'] ?? $apiResp;
        $failureReason = $respData['failure_reason'] ?? $apiResp['failure_reason'] ?? '';
    }
    $statusMessage = 'Malipo hayajakamilika. Tafadhali jaribu tena.';
    if ($failureReason) {
        $statusMessage = 'Malipo yamekataliwa: ' . htmlspecialchars($failureReason);
    }
} elseif ($initialStatus === 'manual_review') {
    $statusMessage = 'Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.';
} elseif ($isMobile) {
    $statusMessage = 'Ombi la malipo limetumwa kwenye simu yako. Tafadhali angalia simu yako na uingize siri yako ya Mobile Money kuthibitisha malipo.';
} else {
    $statusMessage = 'Tunasubiri uthibitisho wa malipo. Tafadhali kamilisha ombi kwenye simu yako.';
}

// Determine initial icon
if ($cancelled) {
    $initIcon = 'warning';
} elseif ($initialStatus === 'completed') {
    $initIcon = 'success';
} elseif ($initialStatus === 'failed') {
    $initIcon = 'error';
} elseif ($initialStatus === 'manual_review') {
    $initIcon = 'info';
} else {
    $initIcon = 'info';
}
?>

<?php include 'php/includes/dashboard-start.php'; ?>

<div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6" id="swalMount">
        <!-- SweetAlert2 renders here; invisible mount point -->
    </div>
</div>

<?php include 'php/includes/dashboard-end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const ref = '<?= htmlspecialchars($payment['reference']) ?>';
const isManual = <?= $isManual ? 'true' : 'false' ?>;
const initialStatus = '<?= $initialStatus ?>';
const csrfToken = '<?= htmlspecialchars(csrf_token()) ?>';

function buildContentHtml(data, status, isCancelled) {
    const s = status || data.status;
    const cancelled = (s === 'cancelled') || isCancelled;
    const completed = s === 'completed';
    const failed = s === 'failed' && !cancelled;
    const review = s === 'manual_review';
    const pending = !completed && !failed && !review && !cancelled;

    let messageHtml = '';
    if (cancelled) {
        messageHtml = '<div style="color:#64748b"><i class="fas fa-ban" style="color:#94a3b8;margin-right:0.5rem"></i> Malipo yameghairiwa. Unaweza kujaribu tena wakati wowote.</div>';
    } else if (completed) {
        messageHtml = '<div style="color:#16a34a"><i class="fas fa-check-circle" style="color:#16a34a;margin-right:0.5rem"></i> Malipo yamefanikiwa. Usajili wako umewezeshwa.</div>';
    } else if (failed) {
        let failMsg = 'Malipo hayajakamilika. Tafadhali jaribu tena.';
        if (data.failure_reason) {
            failMsg = 'Malipo yamekataliwa: ' + data.failure_reason;
        }
        messageHtml = '<div style="color:#dc2626"><i class="fas fa-times-circle" style="color:#dc2626;margin-right:0.5rem"></i> ' + failMsg + '</div>';
    } else if (review) {
        messageHtml = '<div style="color:#d97706"><i class="fas fa-clock" style="color:#d97706;margin-right:0.5rem"></i> Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.</div>';
    } else if (isManual) {
        messageHtml = '<div style="color:#2563eb"><i class="fas fa-clock" style="color:#2563eb;margin-right:0.5rem"></i> Malipo yako yamewasilishwa kwa uhakiki. Subiri uthibitisho wa admin.</div>';
    } else if (data.method === 'mobile') {
        messageHtml = '<div style="color:#2563eb"><span class="swal-spinner"></span> Ombi la malipo limetumwa kwenye simu yako. Tafadhali angalia simu yako na uingize siri yako ya Mobile Money kuthibitisha malipo.</div>';
    } else {
        messageHtml = '<div style="color:#2563eb"><span class="swal-spinner"></span> Tunasubiri uthibitisho wa malipo. Tafadhali kamilisha ombi kwenye simu yako.</div>';
    }

    const amount = data.amount || '<?= $amount ?>';
    const method = data.method || '<?= $payment['method'] ?>';
    const badgeClass = method === 'snippe' ? 'swal-badge-mobile' : (method === 'snippe_card' ? 'swal-badge-card' : 'swal-badge-manual');
    const badgeIcon = method === 'snippe' ? 'fa-mobile-alt' : (method === 'snippe_card' ? 'fa-credit-card' : 'fa-hand-holding-usd');
    const badgeLabel = method === 'snippe' ? 'Instant Pay (USSD)' : (method === 'snippe_card' ? 'Card Payment' : 'Manual');

    return `
        <div style="text-align:left;max-width:380px;margin:0 auto">
            <div class="swal-payment-detail">
                <span class="label">Reference</span>
                <span class="value swal-ref-box">${data.reference || ref}</span>
            </div>
            <div class="swal-payment-detail">
                <span class="label">Amount</span>
                <span class="value" style="font-size:1.25rem">${amount}</span>
            </div>
            <div class="swal-payment-detail">
                <span class="label">Method</span>
                <span class="swal-badge ${badgeClass}"><i class="fas ${badgeIcon}"></i> ${badgeLabel}</span>
            </div>
            <div style="margin-top:1.25rem;padding:0.75rem;background:#f8fafc;border-radius:12px;text-align:center;font-size:0.9rem;line-height:1.5">
                ${messageHtml}
            </div>
        </div>
    `;
}

function getIconConfig(s) {
    if (s === 'completed') return { icon: 'success', iconColor: '#16a34a' };
    if (s === 'failed' || s === 'cancelled') return { icon: 'error', iconColor: s === 'cancelled' ? '#94a3b8' : '#dc2626' };
    if (s === 'manual_review') return { icon: 'info', iconColor: '#d97706' };
    return { icon: 'info', iconColor: '#2563eb' };
}

let currentSwal = null;
let stopPolling = false;

function showSwal(data) {
    const status = data.status || initialStatus;
    const cancelled = status === 'cancelled' || data.isCancelled;
    const pending = status === 'pending';
    const review = status === 'manual_review';
    const completed = status === 'completed';
    const failed = status === 'failed' && !cancelled;
    const iconCfg = getIconConfig(status);

    const html = buildContentHtml(data, status, cancelled);

    const title = completed ? 'Payment Successful' :
                  failed ? 'Payment Failed' :
                  cancelled ? 'Payment Cancelled' :
                  review ? 'Under Review' :
                  'Payment Pending';

    Swal.fire({
        title: title,
        html: html,
        icon: iconCfg.icon,
        iconColor: iconCfg.iconColor,
        showConfirmButton: completed || failed || cancelled,
        confirmButtonText: '<i class="fas fa-tachometer-alt me-1"></i> Go to Dashboard',
        confirmButtonColor: '#2563eb',
        showDenyButton: (failed || cancelled) || (pending && !isManual),
        denyButtonText: (failed || cancelled)
            ? '<i class="fas fa-redo me-1"></i> Try Again'
            : '<i class="fas fa-sync me-1"></i> Resend Push',
        denyButtonColor: (failed || cancelled) ? '#64748b' : '#2563eb',
        showCancelButton: pending,
        cancelButtonText: '<i class="fas fa-ban me-1"></i> Cancel Payment',
        cancelButtonColor: '#dc2626',
        focusConfirm: false,
        allowOutsideClick: true,
        allowEscapeKey: true,
        didOpen: () => {
            currentSwal = Swal;
            if (pending && !isManual) startPolling();
        },
        didClose: () => {
            if (pending) {
                window.location.href = 'payment';
            }
        },
        preConfirm: () => {
            window.location.href = 'parent/dashboard';
        },
        preDeny: () => {
            if (failed || cancelled) {
                window.location.href = 'payment';
            } else if (pending && !isManual) {
                retryPush();
                return false;
            }
        }
    }).then(result => {
        if (result.dismiss === Swal.DismissReason.cancel) {
            cancelPayment();
        }
    });
}

function retryPush() {
    fetch('api/retry-push.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'ref=' + encodeURIComponent(ref) + '&_csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(r => r.json())
    .then(data => {
        if (data.ok) {
            Swal.fire({
                icon: 'success',
                title: 'USSD Push Sent',
                text: data.message || 'Angalia simu yako na uingize siri yako.',
                timer: 3000,
                showConfirmButton: true,
                confirmButtonText: 'OK'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: data.error || 'Failed to resend push. Try again later.'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Could not reach the server. Check your connection.'
        });
    });
}

function cancelPayment() {
    Swal.fire({
        title: 'Cancel Payment?',
        text: 'Una uhakika unataka kughairi malipo haya?',
        icon: 'question',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: 'Ndiyo, Ghairi',
        cancelButtonText: 'Hapana',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    }).then(result => {
        if (result.isConfirmed) {
            stopPolling = true;
            fetch('api/cancel-payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ref=' + encodeURIComponent(ref) + '&_csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(r => r.json())
            .then(data => {
                if (data.ok) {
                    window.location.href = data.redirect || 'parent/dashboard.php';
                } else {
                    Swal.fire({ icon: 'error', title: 'Failed', text: data.error || 'Cancellation failed.' });
                }
            })
            .catch(() => {
                Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server.' });
            });
        }
    });
}

function updateSwal(data) {
    const status = data.status || 'pending';
    const cancelled = status === 'cancelled';
    const pending = status === 'pending';
    const iconCfg = getIconConfig(status);

    const html = buildContentHtml(data, status, cancelled);

    const title = status === 'completed' ? 'Payment Successful' :
                  status === 'failed' ? 'Payment Failed' :
                  cancelled ? 'Payment Cancelled' :
                  status === 'manual_review' ? 'Under Review' :
                  'Payment Pending';

    Swal.update({
        title: title,
        html: html,
        icon: iconCfg.icon,
        iconColor: iconCfg.iconColor,
        showConfirmButton: (!pending && status !== 'manual_review'),
        showCancelButton: pending,
        showDenyButton: (!pending && (status === 'failed' || cancelled)) || (pending && !isManual),
        denyButtonText: (!pending && (status === 'failed' || cancelled))
            ? '<i class="fas fa-redo me-1"></i> Try Again'
            : '<i class="fas fa-sync me-1"></i> Resend Push',
        allowEscapeKey: !pending
    });

    if (pending) {
        Swal.showLoading();
    } else {
        Swal.hideLoading();
        stopPolling = true;
    }
}

function pollStatus() {
    if (stopPolling || isManual) return;

    fetch('api/payment-status-check.php?ref=' + encodeURIComponent(ref))
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                console.error('Poll error:', data.error);
                return;
            }
            if (currentSwal) {
                updateSwal(data);
            }
        })
        .catch(err => console.error('Poll fetch error:', err));
}

function startPolling() {
    setInterval(pollStatus, 5000);
    setTimeout(pollStatus, 2000);
}

// Initialise
document.addEventListener('DOMContentLoaded', function () {
    const initialData = {
        status: initialStatus,
        reference: ref,
        amount: '<?= $amount ?>',
        method: '<?= $payment['method'] ?>',
        isCancelled: <?= $cancelled ? 'true' : 'false' ?>,
        failure_reason: '<?= isset($failureReason) && $failureReason ? htmlspecialchars(addslashes($failureReason)) : '' ?>'
    };
    showSwal(initialData);
});
</script>
</body>
</html>
