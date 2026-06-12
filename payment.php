<?php
/**
 * Payment Page / Subscription & Topup
 * Supports: Mobile Money (M-Pesa, Airtel, Mixx, Halotel), Card, Manual (Yas Lipa)
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
    if ($role === 'admin') { header('Location: admin/dashboard'); exit; }
    if ($role === 'teacher') { header('Location: teacher/dashboard'); exit; }
    header('Location: index'); exit;
}

$subStatus = sub_get_status($parentId);
$walletBalance = pay_get_wallet_balance($parentId);
$message = '';
$error = '';
$success = false;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $paymentMethod = $_POST['payment_method'] ?? '';
    $paymentType = $_POST['payment_type'] ?? 'subscription';
    $submethod = $_POST['payment_submethod'] ?? 'mobile';
    $phone = $_POST['phone'] ?? '';
    $emailAddr = $_POST['email'] ?? '';
    $transactionId = $_POST['transaction_id'] ?? '';
    $customAmount = (float) ($_POST['amount'] ?? 0);

    if ($paymentMethod === 'snippe') {
        if ($submethod === 'card') {
            require_once __DIR__ . '/php/includes/SnippeCardPaymentService.php';
            $service = new SnippeCardPaymentService($database);
            $result = $service->createPayment($parentId, $emailAddr, $paymentType, $customAmount);
            if ($result['success'] && $result['payment_url']) {
                header('Location: ' . $result['payment_url']);
                exit;
            }
            $error = $result['error'] ?? 'Hitilafu ya malipo. Jaribu tena.';
        } else {
            $normalized = pay_normalize_phone($phone);
            if (!preg_match('/^255\d{9}$/', $normalized)) {
                $error = 'Tafadhali ingiza namba halali ya simu (Tanzania)';
                } else {
                    $result = pay_create_snippe_payment($parentId, $normalized, '', $paymentType);
                    if ($result['success']) {
                        if ($result['payment_url']) {
                            header('Location: ' . $result['payment_url']);
                            exit;
                        }
                        header('Location: payment-status?ref=' . urlencode($result['reference']));
                        exit;
                    } else {
                        $error = $result['error'] ?? 'Hitilafu ya malipo. Jaribu tena.';
                    }
            }
        }
    } elseif ($paymentMethod === 'manual') {
        $manualPhone = $_POST['phone_manual'] ?? '';
        if (empty($transactionId)) {
            $error = 'Tafadhali ingiza Transaction ID';
        } elseif (empty($manualPhone)) {
            $error = 'Tafadhali ingiza namba ya simu uliyotumia';
        } else {
            $result = pay_create_manual_payment($parentId, $manualPhone, $transactionId);
            header('Location: payment-status?ref=' . urlencode($result['reference']));
            exit;
        }
    }
}

$userEmailStored = $_SESSION['email'] ?? $database->fetchOne("SELECT email FROM users WHERE user_id = ?", [$parentId])['email'] ?? '';
$current_lang = $_SESSION['lang'] ?? 'en';
$active_nav = 'topup';
$dashboard_role = 'parent';
$sidebar_active = 'topup';
$dashboard_page_title = 'Payment';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'sw' ? 'sw' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topup - Smart Math Corner</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-card { background:#fff; border:none; border-radius:16px; box-shadow:0 2px 12px rgba(0,0,0,0.06); padding:1.25rem; text-align:center; }
        .stat-card .stat-label { font-size:0.7rem; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; }
        .stat-card .stat-value { font-size:1.3rem; font-weight:700; color:#1e293b; margin-top:0.25rem; }

        .type-card { border:2px solid #e2e8f0; border-radius:14px; padding:1.25rem; cursor:pointer; transition:all 0.2s; background:#fff; text-align:center; height:100%; }
        .type-card:hover { border-color:#93c5fd; transform:translateY(-2px); box-shadow:0 4px 16px rgba(0,0,0,0.08); }
        .type-card.active { border-color:#2563eb; background:#eff6ff; }
        .type-card .type-amt { font-size:1.25rem; font-weight:700; color:#1e293b; }
        .type-card .type-lbl { font-size:0.8rem; color:#64748b; margin-top:2px; }
        .type-card .type-badge { font-size:0.65rem; background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:4px; margin-top:0.5rem; display:inline-block; }

        .method-card { border:none; border-radius:16px; padding:1.5rem; cursor:pointer; transition:all 0.25s; background:#fff; box-shadow:0 2px 12px rgba(0,0,0,0.06); height:100%; }
        .method-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(0,0,0,0.1); }
        .method-card .mc-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:#fff; margin-bottom:0.75rem; }
        .method-card .mc-title { font-size:1rem; font-weight:700; color:#1e293b; }
        .method-card .mc-desc { font-size:0.78rem; color:#94a3b8; margin-top:0.2rem; line-height:1.4; }

        .modal-pform .modal-content { border:none; border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,0.12); }
        .modal-pform .modal-header { border-bottom:none; padding:1.5rem 1.5rem 0; }
        .modal-pform .modal-body { padding:1.25rem 1.5rem 1.5rem; }

        .phone-prefix { display:flex; align-items:center; gap:0; }
        .phone-prefix .prefix { background:#f1f5f9; border:1.5px solid #d1d5db; border-right:none; border-radius:10px 0 0 10px; padding:0.75rem 1rem; font-weight:700; font-size:1.1rem; color:#334155; }
        .phone-prefix input { border-radius:0 10px 10px 0 !important; border-left:none; }

        .manual-box { background:#fffbeb; border:1.5px solid #fde68a; border-radius:12px; padding:1.25rem; }
        .manual-box .big-number { font-size:1.5rem; font-weight:700; color:#92400e; letter-spacing:0.5px; }

        @media (max-width:576px) {
            .method-card { padding:1rem; }
            .method-card .mc-icon { width:40px; height:40px; font-size:1.1rem; }
        }
    </style>
</head>
<body class="dashboard-body">

<?php include 'php/includes/dashboard-start.php'; ?>

<!-- Page Content -->
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">

        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?> alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
                <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- ===== Status Row ===== -->
        <div class="row g-3 mb-4">
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-label">Status</div>
                    <div class="stat-value">
                        <?php if ($subStatus['status'] === 'active'): ?>
                            <span class="text-success"><i class="fas fa-check-circle"></i> Active</span>
                        <?php elseif ($subStatus['status'] === 'trial'): ?>
                            <span class="text-primary"><i class="fas fa-clock"></i> Trial</span>
                        <?php else: ?>
                            <span class="text-danger"><i class="fas fa-times-circle"></i> Expired</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-label">Days Left</div>
                    <div class="stat-value"><?= $subStatus['days_remaining'] ?>d</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-label">Wallet</div>
                    <div class="stat-value text-success"><?= number_format($walletBalance) ?> <small class="fw-normal" style="font-size:0.65rem;">TZS</small></div>
                </div>
            </div>
        </div>

        <form method="POST" id="paymentForm">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_method" id="paymentMethod" value="">
            <input type="hidden" name="payment_submethod" id="paymentSubmethod" value="mobile">
            <input type="hidden" name="payment_type" id="paymentType" value="subscription">
            <input type="hidden" name="phone" id="phoneInput">
            <input type="hidden" name="email" id="emailInput">
            <input type="hidden" name="amount" id="amountInput">

            <!-- ===== Selection Buttons ===== -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <button type="button" class="btn btn-outline-primary w-100 py-4 rounded-4 shadow-sm d-flex align-items-center justify-content-center gap-3" style="border:2px dashed #93c5fd;font-size:1.05rem;" onclick="openTypeModal()">
                        <i class="fas fa-tag fa-lg"></i>
                        <span class="fw-bold">1. Choose Payment Type</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-outline-primary w-100 py-4 rounded-4 shadow-sm d-flex align-items-center justify-content-center gap-3" style="border:2px dashed #93c5fd;font-size:1.05rem;" onclick="openMethodModal()" id="chooseMethodBtn">
                        <i class="fas fa-credit-card fa-lg"></i>
                        <span class="fw-bold">2. Choose Payment Method</span>
                    </button>
                </div>
            </div>

            <!-- ===== Selected Summary ===== -->
            <div id="selectionSummary" class="d-none">
                <div class="card border-0 shadow-sm rounded-4 bg-primary bg-opacity-10 mb-3">
                    <div class="card-body d-flex align-items-center justify-content-between py-3">
                        <div>
                            <span class="badge bg-primary rounded-pill me-2" id="summaryTypeBadge">Subscription</span>
                            <span class="badge bg-success rounded-pill" id="summaryMethodBadge">Mobile Money</span>
                            <span class="ms-2 fw-bold text-dark" id="summaryAmount">1,500 TZS</span>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-3 px-5 fw-semibold shadow-sm" id="payNowBtn" disabled>
                            <i class="fas fa-mobile-alt me-2"></i> Pay Now
                        </button>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>

<!-- ===== PAYMENT TYPE MODAL ===== -->
<div class="modal fade modal-pform" id="paymentTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-tag me-2 text-primary"></i>Payment Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose what you're paying for</p>
                <div class="d-flex flex-column gap-3">
                    <div class="type-card active d-flex flex-column align-items-center justify-content-center py-4" onclick="selectTypeAndProceed('subscription')" id="typeSubCard">
                        <div class="type-amt">1,500 TZS</div>
                        <div class="type-lbl">Monthly Subscription</div>
                        <span class="type-badge">Recommended</span>
                    </div>
                    <div class="type-card d-flex flex-column align-items-center justify-content-center py-4" onclick="selectTypeAndProceed('wallet_topup')" id="typeWalletCard">
                        <div class="type-amt">Custom</div>
                        <div class="type-lbl">Wallet Topup</div>
                        <span class="type-badge" style="background:#dcfce7;color:#15803d;">Flexible</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== PAYMENT METHOD MODAL (Mobile / Card / Manual) ===== -->
<div class="modal fade modal-pform" id="methodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose how you want to pay</p>
                <div class="d-flex flex-column gap-3">
                    <!-- Mobile Money Push -->
                    <div class="method-card border-card d-flex align-items-center gap-3 p-3 rounded-4 border" onclick="selectMethod('mobile')" style="cursor:pointer;">
                        <div class="mc-icon" style="background:linear-gradient(135deg,#059669,#10b981);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-mobile-alt" style="color:#fff;font-size:1.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Mobile Money Push</div>
                            <div class="small text-muted">Lipa kwa M-Pesa, Airtel, Tigo, Halotel</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>

                    <!-- Card Payment -->
                    <div class="method-card border-card d-flex align-items-center gap-3 p-3 rounded-4 border" onclick="selectMethod('card')" style="cursor:pointer;">
                        <div class="mc-icon" style="background:linear-gradient(135deg,#6d28d9,#8b5cf6);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-credit-card" style="color:#fff;font-size:1.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Card Payment</div>
                            <div class="small text-muted">Visa, Mastercard, Amex</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>

                    <!-- Manual (Lipa Number) -->
                    <div class="method-card border-card d-flex align-items-center gap-3 p-3 rounded-4 border" onclick="selectMethod('manual')" style="cursor:pointer;">
                        <div class="mc-icon" style="background:linear-gradient(135deg,#d97706,#f59e0b);width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-hand-holding-usd" style="color:#fff;font-size:1.2rem;"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">Manual Payment</div>
                            <div class="small text-muted">Mix by Yas Lipa (24h verification)</div>
                        </div>
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MOBILE MONEY PUSH MODAL (Phone Input) ===== -->
<div class="modal fade modal-pform" id="mobilePushModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-mobile-alt me-2 text-success"></i>Mobile Money Push</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <div style="background:linear-gradient(135deg,#059669,#10b981);width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                        <i class="fas fa-mobile-alt" style="color:#fff;font-size:1.8rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">Push Payment</h6>
                    <p class="small text-muted mb-0">We'll send a USSD push request to your phone.<br>Enter your mobile money number below.</p>
                </div>

                <!-- Supported Networks -->
                <div class="d-flex justify-content-center gap-3 mb-3">
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><i class="fas fa-check-circle text-success me-1"></i> M-Pesa</span>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><i class="fas fa-check-circle text-success me-1"></i> Airtel</span>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><i class="fas fa-check-circle text-success me-1"></i> Tigo</span>
                    <span class="badge bg-light text-dark border rounded-pill px-3 py-2"><i class="fas fa-check-circle text-success me-1"></i> Halotel</span>
                </div>

                <!-- Amount Display -->
                <div class="bg-light rounded-3 p-3 mb-3 text-center">
                    <span class="text-muted small">Amount to Pay</span>
                    <div class="fw-bold" style="font-size:1.5rem;" id="pushAmountDisplay">1,500 TZS</div>
                    <span class="text-muted small" id="pushTypeDisplay">Monthly Subscription</span>
                </div>

                <!-- Custom Amount (for wallet topup) -->
                <div id="pushCustomAmount" class="mb-3 d-none">
                    <label class="form-label fw-semibold text-muted small" for="pushCustomAmt">Enter Amount (TZS)</label>
                    <div class="input-group">
                        <span class="input-group-text fw-bold">TZS</span>
                        <input type="number" class="form-control form-control-lg" id="pushCustomAmt" min="500" step="100" placeholder="e.g. 2000">
                    </div>
                </div>

                <!-- Phone Number Input -->
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small" for="pushPhone">Your Mobile Money Number</label>
                    <div class="phone-prefix">
                        <span class="prefix">+255</span>
                        <input type="tel" class="form-control form-control-lg" id="pushPhone" placeholder="7XX XXX XXX" maxlength="9" inputmode="numeric" autocomplete="tel">
                    </div>
                    <div class="invalid-feedback" id="pushPhoneError">Tafadhali ingiza namba halali ya simu (Tanzania)</div>
                </div>

                <button type="button" class="btn btn-success w-100 btn-lg rounded-3 fw-semibold shadow-sm" onclick="submitMobilePush()" id="pushSubmitBtn">
                    <i class="fas fa-paper-plane me-2"></i> Send Push Request
                </button>
                <p class="text-muted small text-center mt-2 mb-0">
                    <i class="fas fa-shield-alt me-1 text-success"></i> Secured via Snippe API
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ===== MANUAL PAYMENT MODAL (Lipa Number) ===== -->
<div class="modal fade modal-pform" id="manualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-hand-holding-usd me-2 text-warning"></i>Manual Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="manual-box mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                            <i class="fas fa-university text-warning"></i>
                        </div>
                        <div>
                            <div class="big-number"><i class="fas fa-phone-alt me-1"></i> <?= MANUAL_PAYMENT_NUMBER ?></div>
                            <div class="text-muted small"><?= MANUAL_PAYMENT_NAME ?> (<?= MANUAL_PAYMENT_NETWORK ?>)</div>
                            <div class="fw-semibold text-warning-emphasis small mt-1">
                                <i class="fas fa-tag me-1"></i> <span id="manualAmountLabel">1,500 TZS (Subscription)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-light rounded-3 p-3 mb-3">
                    <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="fas fa-list-ol me-1"></i> Steps</h6>
                    <ol class="mb-0 ps-3 small text-muted" style="line-height:1.9;">
                        <li>Send to <strong><?= MANUAL_PAYMENT_NUMBER ?></strong> via Mix by Yas Lipa</li>
                        <li>Copy the <strong>Transaction ID</strong> you receive after payment</li>
                        <li>Enter the transaction details below to submit for verification</li>
                    </ol>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold text-muted small" for="manualPhone">Phone Number Used</label>
                    <input type="tel" class="form-control form-control-lg" name="phone_manual" id="manualPhone" placeholder="07XX XXX XXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small" for="manualTxnId">Transaction ID</label>
                    <input type="text" class="form-control form-control-lg" name="transaction_id" id="manualTxnId" placeholder="e.g. YL123456789">
                </div>
                <button type="button" class="btn btn-warning w-100 btn-lg rounded-3 text-white fw-semibold" onclick="submitManualPayment()">
                    <i class="fas fa-paper-plane me-2"></i> Submit for Verification
                </button>
                <p class="text-muted small text-center mt-2 mb-0">
                    <i class="fas fa-clock me-1"></i> Verification takes up to 24 hours.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'php/includes/dashboard-end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const isWallet = () => document.getElementById('paymentType').value === 'wallet_topup';

// ===== Payment Type Selection =====
function selectTypeAndProceed(type) {
    document.querySelectorAll('.type-card').forEach(o => o.classList.remove('active'));
    document.getElementById(type === 'subscription' ? 'typeSubCard' : 'typeWalletCard').classList.add('active');
    document.getElementById('paymentType').value = type;

    bootstrap.Modal.getInstance(document.getElementById('paymentTypeModal')).hide();
    updateSummary();
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('methodModal')).show();
    }, 300);
}

// ===== Payment Method Selection =====
function selectMethod(method) {
    bootstrap.Modal.getInstance(document.getElementById('methodModal')).hide();

    if (method === 'mobile') {
        document.getElementById('paymentMethod').value = 'snippe';
        document.getElementById('paymentSubmethod').value = 'mobile';
        // Show mobile push modal
        setTimeout(() => {
            updatePushModal();
            new bootstrap.Modal(document.getElementById('mobilePushModal')).show();
        }, 300);
    } else if (method === 'card') {
        document.getElementById('paymentMethod').value = 'snippe';
        document.getElementById('paymentSubmethod').value = 'card';
        updateSummary();
        // Submit immediately for card (redirects to Snippe checkout)
        document.getElementById('paymentForm').submit();
    } else if (method === 'manual') {
        document.getElementById('paymentMethod').value = 'manual';
        updateSummary();
        setTimeout(() => {
            new bootstrap.Modal(document.getElementById('manualModal')).show();
        }, 300);
    }
}

// ===== Update Push Modal =====
function updatePushModal() {
    const type = document.getElementById('paymentType').value;
    const isTopup = type === 'wallet_topup';
    document.getElementById('pushAmountDisplay').textContent = isTopup ? 'Custom Amount' : '1,500 TZS';
    document.getElementById('pushTypeDisplay').textContent = isTopup ? 'Wallet Topup' : 'Monthly Subscription';
    document.getElementById('pushCustomAmount').classList.toggle('d-none', !isTopup);
    document.getElementById('pushPhone').value = '';
    document.getElementById('pushPhone').classList.remove('is-invalid');
}

// ===== Submit Mobile Money Push =====
function submitMobilePush() {
    const phone = document.getElementById('pushPhone').value.trim();
    const errorEl = document.getElementById('pushPhoneError');
    const phoneInput = document.getElementById('pushPhone');

    // Validate: 9 digits (after +255)
    if (!/^\d{9}$/.test(phone)) {
        phoneInput.classList.add('is-invalid');
        errorEl.textContent = 'Tafadhali ingiza namba halali (7XX XXX XXX)';
        phoneInput.focus();
        return;
    }

    // Validate starting digit (Tanzania: 6 or 7)
    if (phone[0] !== '7' && phone[0] !== '6') {
        phoneInput.classList.add('is-invalid');
        errorEl.textContent = 'Namba ya simu ya Tanzania inaanza na 7 au 6';
        phoneInput.focus();
        return;
    }

    // Handle custom amount for wallet topup
    if (isWallet()) {
        const amt = document.getElementById('pushCustomAmt').value.trim();
        if (!amt || parseFloat(amt) < 500) {
            document.getElementById('pushCustomAmt').classList.add('is-invalid');
            document.getElementById('pushCustomAmt').focus();
            return;
        }
        document.getElementById('amountInput').value = amt;
    }

    const fullPhone = '+255' + phone;
    document.getElementById('phoneInput').value = fullPhone;

    const btn = document.getElementById('pushSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending Push...';

    updateSummary();
    document.getElementById('paymentForm').submit();
}

// ===== Update Summary Bar =====
function updateSummary() {
    const type = document.getElementById('paymentType').value;
    const method = document.getElementById('paymentMethod').value;
    const submethod = document.getElementById('paymentSubmethod').value;

    const typeLabel = type === 'wallet_topup' ? 'Wallet Topup' : 'Subscription';
    let methodLabel = '';
    if (method === 'manual') methodLabel = 'Manual (Lipa Number)';
    else if (method === 'snippe' && submethod === 'mobile') methodLabel = 'Mobile Money Push';
    else if (method === 'snippe' && submethod === 'card') methodLabel = 'Card Payment';

    const amount = type === 'subscription' ? '1,500 TZS' : (document.getElementById('amountInput').value || 'Custom');

    document.getElementById('summaryTypeBadge').textContent = typeLabel;
    document.getElementById('summaryMethodBadge').textContent = methodLabel;
    document.getElementById('summaryAmount').textContent = amount;

    const summary = document.getElementById('selectionSummary');
    if (method) {
        summary.classList.remove('d-none');
        document.getElementById('payNowBtn').disabled = false;
    }
}

// ===== Modal Openers =====
function openTypeModal() {
    new bootstrap.Modal(document.getElementById('paymentTypeModal')).show();
}

function openMethodModal() {
    const type = document.getElementById('paymentType').value;
    if (!type) {
        Swal.fire({
            icon: 'info',
            title: 'Select Payment Type First',
            text: 'Tafadhali chagua aina ya malipo kwanza (Step 1)',
            confirmButtonColor: '#2563eb'
        });
        return;
    }
    new bootstrap.Modal(document.getElementById('methodModal')).show();
}

// ===== Manual Payment (Lipa Number) =====
function submitManualPayment() {
    const phone = document.getElementById('manualPhone').value.trim();
    const txnId = document.getElementById('manualTxnId').value.trim();
    if (!txnId) {
        document.getElementById('manualTxnId').classList.add('is-invalid');
        document.getElementById('manualTxnId').focus();
        return;
    }
    if (!phone) {
        document.getElementById('manualPhone').classList.add('is-invalid');
        document.getElementById('manualPhone').focus();
        return;
    }
    const btn = document.querySelector('#manualModal .btn-warning');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';
    document.getElementById('paymentForm').submit();
}

// ===== Pay Now from Summary =====
document.getElementById('payNowBtn')?.addEventListener('click', function(e) {
    const method = document.getElementById('paymentMethod').value;
    const submethod = document.getElementById('paymentSubmethod').value;

    if (method === 'snippe' && submethod === 'mobile') {
        e.preventDefault();
        new bootstrap.Modal(document.getElementById('mobilePushModal')).show();
    }
});

// ===== Phone input formatting =====
document.getElementById('pushPhone')?.addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
    this.classList.remove('is-invalid');
});

document.getElementById('pushCustomAmt')?.addEventListener('input', function() {
    this.classList.remove('is-invalid');
});

// ===== Form validation styling =====
document.querySelectorAll('#manualModal input').forEach(i => {
    i.addEventListener('input', () => i.classList.remove('is-invalid'));
});
</script>
</body>
</html>
