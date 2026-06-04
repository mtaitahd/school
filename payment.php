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
    if ($role === 'admin') { header('Location: admin/dashboard.php'); exit; }
    if ($role === 'teacher') { header('Location: teacher/dashboard.php'); exit; }
    header('Location: index.php'); exit;
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
            $result = pay_create_snippe_payment($parentId, '', $emailAddr, $paymentType);
            if ($result['success']) {
                if ($result['payment_url']) {
                    header('Location: ' . $result['payment_url']);
                    exit;
                }
                header('Location: payment-status.php?ref=' . urlencode($result['reference']));
                exit;
            } else {
                $error = $result['error'] ?? 'Hitilafu ya malipo. Jaribu tena.';
            }
        } else {
            $normalized = pay_normalize_phone($phone);
            if (!preg_match('/^255[67]\d{8}$/', $normalized)) {
                $error = 'Tafadhali ingiza namba halali ya simu (Tanzania)';
            } else {
                $result = pay_create_snippe_payment($parentId, $normalized, '', $paymentType);
                if ($result['success']) {
                    if ($result['payment_url']) {
                        header('Location: ' . $result['payment_url']);
                        exit;
                    }
                    header('Location: payment-status.php?ref=' . urlencode($result['reference']));
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
            header('Location: payment-status.php?ref=' . urlencode($result['reference']));
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

        .network-card { border:2px solid #e2e8f0; border-radius:14px; padding:1.25rem 1rem; text-align:center; cursor:pointer; transition:all 0.2s; background:#fff; }
        .network-card:hover { transform:translateY(-3px); box-shadow:0 8px 20px rgba(0,0,0,0.1); }
        .network-card.active { border-width:3px; transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.12); }
        .network-card .net-icon { width:56px; height:56px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; font-size:1.5rem; color:#fff; font-weight:700; }
        .network-card .net-name { font-weight:700; font-size:0.95rem; color:#1e293b; }

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
            <input type="hidden" name="payment_method" id="paymentMethod" value="snippe">
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
                        <span class="fw-bold">Choose Payment Type</span>
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-outline-primary w-100 py-4 rounded-4 shadow-sm d-flex align-items-center justify-content-center gap-3" style="border:2px dashed #93c5fd;font-size:1.05rem;" onclick="openMethodModal()">
                        <i class="fas fa-credit-card fa-lg"></i>
                        <span class="fw-bold">Choose Payment Method</span>
                    </button>
                </div>
            </div>

            <!-- Selected type/method summary -->
            <div class="bg-light rounded-3 p-3 mb-3 d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-semibold" style="font-size:0.65rem;">Selected</small>
                    <div class="fw-bold" id="selectionSummary" style="color:#1e293b;">
                        <span id="selectedTypeDisplay">Subscription (1,500 TZS)</span>
                        <span class="text-muted mx-2">·</span>
                        <span id="selectedMethodDisplay">Mobile Money</span>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="fas fa-arrow-right me-1"></i> Proceed
                    </button>
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
                    <div class="type-card active d-flex flex-column align-items-center justify-content-center py-4" onclick="selectType(this, 'subscription')" id="typeSubCard">
                        <div class="type-amt">1,500 TZS</div>
                        <div class="type-lbl">Monthly Subscription</div>
                        <span class="type-badge">Recommended</span>
                    </div>
                    <div class="type-card d-flex flex-column align-items-center justify-content-center py-4" onclick="selectType(this, 'wallet_topup')" id="typeWalletCard">
                        <div class="type-amt">Custom</div>
                        <div class="type-lbl">Wallet Topup</div>
                        <span class="type-badge" style="background:#dcfce7;color:#15803d;">Flexible</span>
                    </div>
                </div>
                <button type="button" class="btn btn-primary w-100 mt-3 rounded-3 py-2 fw-bold" onclick="proceedToMethod()">
                    <i class="fas fa-arrow-right me-2"></i> Continue
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== PAYMENT METHOD MODAL ===== -->
<div class="modal fade modal-pform" id="paymentMethodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Choose how you want to pay</p>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="method-card d-flex flex-column align-items-start" onclick="selectMethod('snippe', 'mobile'); bootstrap.Modal.getInstance(document.getElementById('paymentMethodModal')).hide(); openMobileModal()">
                            <div class="mc-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="mc-title">Mobile Money</div>
                            <div class="mc-desc">M-Pesa, Airtel, Mixx, Halotel<br>Pay with USSD push</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="method-card d-flex flex-column align-items-start" onclick="submitCardDirect()">
                            <div class="mc-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="mc-title">Card Payment</div>
                            <div class="mc-desc">Visa, Mastercard, Local debit<br>Redirect to secure checkout</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="method-card d-flex flex-column align-items-start" onclick="selectMethod('manual', ''); bootstrap.Modal.getInstance(document.getElementById('paymentMethodModal')).hide(); openManualModal()">
                            <div class="mc-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                            <div class="mc-title">Manual Payment</div>
                            <div class="mc-desc">Mix by Yas Lipa<br>Send to <?= MANUAL_PAYMENT_NUMBER ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== MOBILE MONEY MODAL ===== -->
<div class="modal fade modal-pform" id="mobileMoneyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-mobile-alt me-2 text-primary"></i>Mobile Money</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info border-0 rounded-3 py-2 small d-flex align-items-center gap-2 mb-3">
                    <i class="fas fa-info-circle"></i>
                    Chagua mtandao wako na uweke namba ya simu. Utapokea USSD push kwenye simu yako.
                </div>

                <div class="row g-3 mb-3" id="networkCards">
                    <div class="col-6">
                        <div class="network-card" data-network="mpesa" onclick="selectNetwork(this, 'mpesa')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#4CAF50,#2E7D32);"><i class="fas fa-mobile-alt"></i></div>
                            <div class="net-name">M-Pesa</div>
                            <small class="text-muted">Vodacom</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="airtel" onclick="selectNetwork(this, 'airtel')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#E53935,#B71C1C);"><i class="fas fa-mobile-alt"></i></div>
                            <div class="net-name">Airtel Money</div>
                            <small class="text-muted">Airtel</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="mixx" onclick="selectNetwork(this, 'mixx')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#FFB300,#F57F17);"><i class="fas fa-mobile-alt"></i></div>
                            <div class="net-name">Mixx</div>
                            <small class="text-muted">Tigo / Yas</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="halotel" onclick="selectNetwork(this, 'halotel')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#FF6D00,#E65100);"><i class="fas fa-mobile-alt"></i></div>
                            <div class="net-name">Halotel</div>
                            <small class="text-muted">Halotel</small>
                        </div>
                    </div>
                </div>

                <div id="phoneInputSection" style="display:none;">
                    <hr class="my-3">
                    <label class="form-label fw-semibold small text-muted mb-2"><i class="fas fa-phone me-1"></i> Namba ya Simu</label>
                    <div class="phone-prefix mb-2">
                        <span class="prefix">+255</span>
                        <input type="tel" class="form-control form-control-lg" id="modalPhone" placeholder="XX XXX XXXX" maxlength="10" autocomplete="off">
                    </div>
                    <div class="small text-muted mb-3">
                        <i class="fas fa-info-circle me-1"></i> Weka namba bila <strong>0</strong> au <strong>+255</strong>
                    </div>
                    <div id="mobileAmountRow" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold small text-muted">Amount (TZS)</label>
                        <input type="number" class="form-control form-control-lg" id="mobileAmount" min="500" step="500" placeholder="Enter amount">
                    </div>
                    <button type="button" class="btn btn-primary w-100 btn-lg rounded-3 mb-2" onclick="submitMobilePayment()">
                        <i class="fas fa-check-circle me-2"></i> Pay
                    </button>
                    <button type="button" class="btn btn-outline-secondary w-100 rounded-3" onclick="cancelMobilePayment()">
                        <i class="fas fa-times me-2"></i> Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===== CARD EMAIL MODAL (fallback if no stored email) ===== -->
<div class="modal fade modal-pform" id="cardEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fas fa-credit-card me-2 text-primary"></i>Card Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Enter your email to proceed to secure checkout</p>
                <div class="mb-3">
                    <input type="email" class="form-control form-control-lg" id="cardEmailInput" placeholder="your@email.com">
                </div>
                <div id="cardAmountRow" class="mb-3" style="display:none;">
                    <label class="form-label fw-semibold text-muted small">Amount (TZS)</label>
                    <input type="number" class="form-control form-control-lg" id="cardAmtInput" min="500" step="500" placeholder="Enter amount">
                </div>
                <button type="button" class="btn btn-primary w-100 btn-lg rounded-3" onclick="doCardSubmit()">
                    <i class="fas fa-arrow-right me-2"></i> Continue to Checkout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== MANUAL PAYMENT MODAL ===== -->
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
let selectedNetwork = '';
const isWallet = () => document.getElementById('paymentType').value === 'wallet_topup';
const userEmail = '<?= htmlspecialchars(addslashes($userEmailStored)) ?>';

function selectType(el, type) {
    document.querySelectorAll('.type-card').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('paymentType').value = type;
    const showAmt = type === 'wallet_topup';
    document.getElementById('mobileAmountRow').style.display = showAmt ? 'block' : 'none';
    document.getElementById('cardAmountRow').style.display = showAmt ? 'block' : 'none';
    const lbl = showAmt ? 'Custom Amount (Wallet Topup)' : '1,500 TZS (Subscription)';
    document.getElementById('manualAmountLabel').textContent = lbl;
    updateSelectionSummary();
}

function setMethod(method, submethod) {
    document.getElementById('paymentMethod').value = method;
    document.getElementById('paymentSubmethod').value = submethod;
    updateSelectionSummary();
}

function updateSelectionSummary() {
    const type = document.getElementById('paymentType').value;
    const method = document.getElementById('paymentMethod').value;
    const sub = document.getElementById('paymentSubmethod').value;
    const typeLabel = type === 'wallet_topup' ? 'Wallet Topup' : 'Subscription (1,500 TZS)';
    let methodLabel = 'Mobile Money';
    if (method === 'manual') methodLabel = 'Manual';
    else if (sub === 'card') methodLabel = 'Card Payment';
    document.getElementById('selectedTypeDisplay').textContent = typeLabel;
    document.getElementById('selectedMethodDisplay').textContent = methodLabel;
}

// ===== Modal Openers =====
function openTypeModal() {
    new bootstrap.Modal(document.getElementById('paymentTypeModal')).show();
}

function openMethodModal() {
    new bootstrap.Modal(document.getElementById('paymentMethodModal')).show();
}

function proceedToMethod() {
    bootstrap.Modal.getInstance(document.getElementById('paymentTypeModal')).hide();
    setTimeout(() => openMethodModal(), 300);
}

function openMobileModal() {
    setMethod('snippe', 'mobile');
    new bootstrap.Modal(document.getElementById('mobileMoneyModal')).show();
}

function openManualModal() {
    setMethod('manual', '');
    new bootstrap.Modal(document.getElementById('manualModal')).show();
}

function submitCardDirect() {
    setMethod('snippe', 'card');
    bootstrap.Modal.getInstance(document.getElementById('paymentMethodModal')).hide();

    if (userEmail && userEmail.includes('@')) {
        document.getElementById('emailInput').value = userEmail;
        document.getElementById('paymentForm').submit();
    } else {
        setTimeout(() => {
            const modal = new bootstrap.Modal(document.getElementById('cardEmailModal'));
            modal.show();
        }, 300);
    }
}

function doCardSubmit() {
    const email = document.getElementById('cardEmailInput').value.trim();
    if (!email || !email.includes('@')) {
        document.getElementById('cardEmailInput').classList.add('is-invalid');
        document.getElementById('cardEmailInput').focus();
        return;
    }
    document.getElementById('emailInput').value = email;
    if (isWallet()) {
        const amt = document.getElementById('cardAmtInput').value;
        if (!amt || amt < 500) {
            document.getElementById('cardAmtInput').classList.add('is-invalid');
            document.getElementById('cardAmtInput').focus();
            return;
        }
        document.getElementById('amountInput').value = amt;
    }
    bootstrap.Modal.getInstance(document.getElementById('cardEmailModal')).hide();
    document.getElementById('paymentForm').submit();
}

// ===== Mobile Money =====
function selectNetwork(el, network) {
    document.querySelectorAll('#mobileMoneyModal .network-card').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    selectedNetwork = network;
    document.getElementById('phoneInputSection').style.display = 'block';
    document.getElementById('modalPhone').focus();
}

function submitMobilePayment() {
    const phoneInput = document.getElementById('modalPhone');
    const raw = phoneInput.value.replace(/\D/g, '');

    if (raw.length < 9) {
        phoneInput.classList.add('is-invalid');
        phoneInput.focus();
        return;
    }

    if (isWallet()) {
        const amt = document.getElementById('mobileAmount').value;
        if (!amt || amt < 500) {
            document.getElementById('mobileAmount').classList.add('is-invalid');
            document.getElementById('mobileAmount').focus();
            return;
        }
        document.getElementById('amountInput').value = amt;
    }

    const fullNumber = '+255' + raw.slice(-9);
    document.getElementById('phoneInput').value = fullNumber;
    document.getElementById('paymentForm').submit();
}

function cancelMobilePayment() {
    selectedNetwork = '';
    document.querySelectorAll('#mobileMoneyModal .network-card').forEach(c => c.classList.remove('active'));
    document.getElementById('phoneInputSection').style.display = 'none';
    document.getElementById('modalPhone').value = '';
    document.getElementById('modalPhone').classList.remove('is-invalid');
    bootstrap.Modal.getInstance(document.getElementById('mobileMoneyModal')).hide();
}

// ===== Card Payment =====
// Handled by submitCardDirect() + doCardSubmit() above

// ===== Manual Payment =====
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
    document.getElementById('paymentForm').submit();
}

// ===== Phone Formatting =====
document.getElementById('modalPhone').addEventListener('input', function () {
    const digits = this.value.replace(/\D/g, '').slice(0, 9);
    this.classList.remove('is-invalid');
    this.value = digits.length > 5 ? digits.slice(0, 5) + ' ' + digits.slice(5) : digits;
});

// ===== Form validation styling =====
document.querySelectorAll('#cardEmailModal input').forEach(i => {
    i.addEventListener('input', () => i.classList.remove('is-invalid'));
});
document.querySelectorAll('#mobileMoneyModal input').forEach(i => {
    i.addEventListener('input', () => i.classList.remove('is-invalid'));
});
document.querySelectorAll('#manualModal input').forEach(i => {
    i.addEventListener('input', () => i.classList.remove('is-invalid'));
});
</script>
</body>
</html>
