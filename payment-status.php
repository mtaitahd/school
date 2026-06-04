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
    header('Location: payment.php'); exit;
}

$payment = $database->fetchOne(
    "SELECT * FROM `payments` WHERE reference = ? AND parent_id = ? LIMIT 1",
    [$ref, $parentId]
);

if (!$payment) {
    header('Location: payment.php'); exit;
}

// Handle cancel action before status variables
$cancelled = $payment['admin_note'] === 'cancelled_by_user';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_payment'])) {
    csrf_require();
    if (in_array($payment['status'], ['pending', 'manual_review'])) {
        $database->execute(
            "UPDATE `payments` SET status = 'failed', admin_note = 'cancelled_by_user' WHERE id = ? AND parent_id = ?",
            [$payment['id'], $parentId]
        );
        $payment['status'] = 'failed';
        $cancelled = true;
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
        .swal-badge { display:inline-flex; align-items:center; gap:0.375rem; padding:0.3rem 0.75rem; border-radius:50px; font-size:0.75rem; font-weight:600; }
        .swal-badge-mobile { background:#e0f2fe; color:#0369a1; }
        .swal-badge-card { background:#ede9fe; color:#6d28d9; }
        .swal-badge-manual { background:#fef3c7; color:#b45309; }
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
    $statusMessage = 'Malipo hayajakamilika. Tafadhali jaribu tena.';
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
        messageHtml = '<div style="color:#dc2626"><i class="fas fa-times-circle" style="color:#dc2626;margin-right:0.5rem"></i> Malipo hayajakamilika. Tafadhali jaribu tena.</div>';
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
    const badgeLabel = method === 'snippe' ? 'Mobile Money' : (method === 'snippe_card' ? 'Card Payment' : 'Manual');

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
        showDenyButton: failed || cancelled,
        denyButtonText: '<i class="fas fa-redo me-1"></i> Try Again',
        denyButtonColor: '#64748b',
        showCancelButton: pending,
        cancelButtonText: '<i class="fas fa-ban me-1"></i> Cancel Payment',
        cancelButtonColor: '#dc2626',
        focusConfirm: false,
        allowOutsideClick: false,
        allowEscapeKey: !pending,
        didOpen: () => {
            currentSwal = Swal;
            if (pending && !isManual) startPolling();
        },
        preConfirm: () => {
            window.location.href = 'parent/dashboard.php';
        },
        preDeny: () => {
            window.location.href = 'payment.php';
        }
    }).then(result => {
        if (result.dismiss === Swal.DismissReason.cancel) {
            cancelPayment();
        }
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
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="csrf_token" value="' + csrfToken + '"><input type="hidden" name="cancel_payment" value="1">';
            document.body.appendChild(form);
            form.submit();
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
        showConfirmButton: !pending,
        showCancelButton: pending,
        showDenyButton: !pending && (status === 'failed' || cancelled),
        denyButtonText: (status === 'failed' || cancelled) ? '<i class="fas fa-redo me-1"></i> Try Again' : undefined,
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
        isCancelled: <?= $cancelled ? 'true' : 'false' ?>
    };
    showSwal(initialData);
});
</script>
</body>
</html>
