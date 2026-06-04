<?php
/**
 * Topup / Subscription Payment Page
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
                $message = 'Malipo yamepokelewa. Subiri uthibitisho...';
                $success = true;
                $subStatus = sub_get_status($parentId);
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
                    $message = 'Malipo yamepokelewa. Subiri uthibitisho...';
                    $success = true;
                    $subStatus = sub_get_status($parentId);
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
            $message = 'Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.';
            $success = true;
        }
    }
}

$current_lang = $_SESSION['lang'] ?? 'en';
$active_nav = 'topup';
$dashboard_role = 'parent';
$sidebar_active = 'topup';
$dashboard_page_title = 'Topup';
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
        .payment-card { border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .payment-card .card-header-custom { background: linear-gradient(135deg, #1e40af, #3b82f6); padding: 1.75rem 2rem; }
        .payment-card .card-header-custom h1 { font-size: 1.5rem; }
        .pay-icon-circle { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; }
        .nav-payments .nav-link { border: 2px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.25rem; color: #475569; transition: all 0.2s; cursor: pointer; background: #fff; }
        .nav-payments .nav-link:hover { border-color: #93c5fd; background: #f8fafc; }
        .nav-payments .nav-link.active { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        .nav-payments .nav-link i { font-size: 1.5rem; display: block; margin-bottom: 4px; }
        .nav-payments .nav-link small { font-size: 0.75rem; color: #94a3b8; display: block; }
        .nav-payments .nav-link.active small { color: #60a5fa; }
        .opt-btn { border: 2px solid #e2e8f0; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; }
        .opt-btn:hover { border-color: #93c5fd; }
        .opt-btn.active { border-color: #2563eb; background: #eff6ff; }
        .opt-btn .amt { font-size: 1.125rem; font-weight: 700; color: #1e293b; }
        .opt-btn .lbl { font-size: 0.75rem; color: #64748b; }
        .opt-btn .badge-rec { font-size: 0.65rem; background: #dbeafe; color: #1d4ed8; padding: 2px 8px; border-radius: 4px; }
        .manual-rice { background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 1.25rem; }
        .manual-rice .big-number { font-size: 1.5rem; font-weight: 700; color: #92400e; letter-spacing: 0.5px; }
        .status-stat { background: #f8fafc; border-radius: 12px; padding: 1rem; text-align: center; border: 1px solid #e2e8f0; }
        .status-stat .stat-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-stat .stat-value { font-size: 1.25rem; font-weight: 700; color: #1e293b; }

        /* Network cards */
        .network-card { border: 2px solid #e2e8f0; border-radius: 14px; padding: 1.25rem 1rem; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; }
        .network-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .network-card.active { border-width: 3px; transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .network-card .net-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; font-size: 1.5rem; color: #fff; font-weight: 700; }
        .network-card .net-name { font-weight: 700; font-size: 0.95rem; color: #1e293b; }

        /* Phone prefix input group */
        .phone-prefix { display: flex; align-items: center; gap: 0; }
        .phone-prefix .prefix { background: #f1f5f9; border: 1.5px solid #d1d5db; border-right: none; border-radius: 10px 0 0 10px; padding: 0.75rem 1rem; font-weight: 700; font-size: 1.1rem; color: #334155; }
        .phone-prefix input { border-radius: 0 10px 10px 0 !important; border-left: none; }

        @media (max-width: 576px) {
            .payment-card .card-body { padding: 1.25rem !important; }
            .nav-payments .nav-link { padding: 0.75rem; }
            .nav-payments { flex-direction: column; }
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

        <!-- Main Card -->
        <div class="card payment-card mb-4">
            <div class="card-header-custom text-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="pay-icon-circle bg-white bg-opacity-25">
                        <i class="fas fa-wallet text-white"></i>
                    </div>
                    <div>
                        <h1 class="mb-0 fw-bold">Topup &amp; Subscription</h1>
                        <p class="mb-0 mt-1" style="opacity:0.85;font-size:0.875rem;">
                            Choose a payment method to subscribe or topup your wallet
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-body" style="padding:2rem;">

                <!-- Status Row -->
                <div class="row g-3 mb-4">
                    <div class="col-4">
                        <div class="status-stat">
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
                        <div class="status-stat">
                            <div class="stat-label">Days Left</div>
                            <div class="stat-value"><?= $subStatus['days_remaining'] ?>d</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="status-stat">
                            <div class="stat-label">Wallet</div>
                            <div class="stat-value text-success"><?= number_format($walletBalance) ?> <small class="fw-normal" style="font-size:0.65rem;">TZS</small></div>
                        </div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="text-center py-3">
                        <div class="mb-3">
                            <i class="fas fa-check-circle text-success" style="font-size:3rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Payment Submitted Successfully!</h5>
                        <p class="text-muted small">You now have access to all premium features.</p>
                        <div class="row g-2 mt-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-child text-success"></i> View child progress
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-star text-success"></i> Results &amp; stars
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-book text-success"></i> Assignments
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center gap-2 text-muted small">
                                    <i class="fas fa-chart-line text-success"></i> Performance reports
                                </div>
                            </div>
                        </div>
                        <a href="parent/dashboard.php" class="btn btn-primary mt-3 px-4 rounded-pill">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php include 'php/includes/dashboard-end.php'; ?>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                    </body></html>
                    <?php return; ?>
                <?php endif; ?>

                <form method="POST" id="paymentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_method" id="paymentMethod" value="snippe">
                    <input type="hidden" name="payment_submethod" id="paymentSubmethod" value="mobile">
                    <input type="hidden" name="payment_type" id="paymentType" value="subscription">
                    <input type="hidden" name="phone" id="phoneInput">

                    <!-- Payment Type Selection -->
                    <label class="fw-semibold text-muted small text-uppercase mb-2">Payment Type</label>
                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="opt-btn active h-100 d-flex flex-column align-items-center justify-content-center" onclick="selectType(this, 'subscription')">
                                <div class="amt">1,500 TZS</div>
                                <div class="lbl">Monthly Subscription</div>
                                <span class="badge-rec mt-1">Recommended</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="opt-btn h-100 d-flex flex-column align-items-center justify-content-center" onclick="selectType(this, 'wallet_topup')">
                                <div class="amt">Custom</div>
                                <div class="lbl">Wallet Topup</div>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Amount -->
                    <div id="customAmountBox" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold text-muted small">Amount (TZS)</label>
                        <input type="number" class="form-control" name="amount" id="customAmount" min="500" step="500" placeholder="Enter amount (min 500 TZS)">
                    </div>

                    <!-- Payment Method Tabs -->
                    <label class="fw-semibold text-muted small text-uppercase mb-2">Payment Method</label>
                    <div class="nav nav-payments nav-justified gap-2 mb-4" role="tablist">
                        <button class="nav-link active" id="tab-mobile" type="button" onclick="switchTab(this, 'content-mobile'); setMethod('snippe', 'mobile')">
                            <i class="fas fa-mobile-alt"></i>
                            Mobile Money
                            <small>M-Pesa, Airtel, Mixx, Halotel</small>
                        </button>
                        <button class="nav-link" id="tab-card" type="button" onclick="switchTab(this, 'content-card'); setMethod('snippe', 'card')">
                            <i class="fas fa-credit-card"></i>
                            Card Payment
                            <small>Visa, Mastercard, Local debit</small>
                        </button>
                        <button class="nav-link" id="tab-manual" type="button" onclick="switchTab(this, 'content-manual'); setMethod('manual', '')">
                            <i class="fas fa-hand-holding-usd"></i>
                            Manual
                            <small>Mix by Yas Lipa</small>
                        </button>
                    </div>

                    <div class="tab-content">

                        <!-- === Mobile Money === -->
                        <div class="tab-pane fade show active" id="content-mobile" role="tabpanel">
                            <div class="alert alert-info border-0 rounded-3 py-2 small d-flex align-items-center gap-2 mb-3">
                                <i class="fas fa-info-circle"></i>
                                Chagua mtandao wako na uweke namba ya simu. Utapokea USSD push kwenye simu yako.
                            </div>
                            <button type="button" class="btn btn-primary w-100 btn-lg rounded-3" data-bs-toggle="modal" data-bs-target="#mobileNetworkModal">
                                <i class="fas fa-mobile-alt me-2"></i> Pay with Mobile Money
                            </button>
                        </div>

                        <!-- === Card Payment === -->
                        <div class="tab-pane fade" id="content-card" role="tabpanel">
                            <div class="alert alert-info border-0 rounded-3 py-2 small d-flex align-items-center gap-2 mb-3">
                                <i class="fas fa-info-circle"></i>
                                You will be redirected to a secure checkout page to enter your card details.
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-muted small" for="emailCard">Email Address</label>
                                <input type="email" class="form-control form-control-lg" name="email" id="emailCard" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? $_SESSION['email'] ?? '') ?>">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg rounded-3">
                                <i class="fas fa-credit-card me-2"></i> Pay with Card
                            </button>
                        </div>

                        <!-- === Manual Payment === -->
                        <div class="tab-pane fade" id="content-manual" role="tabpanel">
                            <div class="manual-rice mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0;">
                                        <i class="fas fa-university text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="big-number"><i class="fas fa-phone-alt me-1"></i> <?= MANUAL_PAYMENT_NUMBER ?></div>
                                        <div class="text-muted small"><?= MANUAL_PAYMENT_NAME ?> (<?= MANUAL_PAYMENT_NETWORK ?>)</div>
                                        <div class="fw-semibold text-warning-emphasis small mt-1">
                                            <i class="fas fa-tag me-1"></i> 1,500 TZS (Monthly Subscription)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-light rounded-3 p-3 mb-3">
                                <h6 class="fw-bold small text-uppercase text-muted mb-2"><i class="fas fa-list-ol me-1"></i> Steps</h6>
                                <ol class="mb-0 ps-3 small text-muted" style="line-height:1.9;">
                                    <li>Send <strong>1,500 TZS</strong> to <strong><?= MANUAL_PAYMENT_NUMBER ?></strong> via Mix by Yas Lipa</li>
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
                            <button type="submit" class="btn btn-warning w-100 btn-lg rounded-3 text-white fw-semibold">
                                <i class="fas fa-paper-plane me-2"></i> Submit for Verification
                            </button>
                            <p class="text-muted small text-center mt-2 mb-0">
                                <i class="fas fa-clock me-1"></i> Verification takes up to 24 hours. You will receive an SMS confirmation.
                            </p>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<!-- ====== Mobile Network Selection Modal ====== -->
<div class="modal fade" id="mobileNetworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0" style="padding:1.5rem 1.5rem 0;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-mobile-alt me-2 text-primary"></i>Chagua Mtandao
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.5rem 1.5rem;">
                <p class="text-muted small mb-3">Bonyeza mtandao wako kisha ingiza namba ya simu</p>

                <!-- Network cards -->
                <div class="row g-3 mb-4" id="networkCards">
                    <div class="col-6">
                        <div class="network-card" data-network="mpesa" onclick="selectNetwork(this, 'mpesa')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#4CAF50,#2E7D32);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="net-name">M-Pesa</div>
                            <small class="text-muted">Vodacom</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="airtel" onclick="selectNetwork(this, 'airtel')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#E53935,#B71C1C);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="net-name">Airtel Money</div>
                            <small class="text-muted">Airtel</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="mixx" onclick="selectNetwork(this, 'mixx')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#FFB300,#F57F17);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="net-name">Mixx</div>
                            <small class="text-muted">Tigo / Yas</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="network-card" data-network="halotel" onclick="selectNetwork(this, 'halotel')">
                            <div class="net-icon" style="background:linear-gradient(135deg,#FF6D00,#E65100);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="net-name">Halotel</div>
                            <small class="text-muted">Halotel</small>
                        </div>
                    </div>
                </div>

                <!-- Phone input (hidden until network selected) -->
                <div id="phoneInputSection" style="display:none;">
                    <hr class="my-3">
                    <label class="form-label fw-semibold small text-muted mb-2">
                        <i class="fas fa-phone me-1"></i> Namba ya Simu
                    </label>
                    <div class="phone-prefix mb-2">
                        <span class="prefix">+255</span>
                        <input type="tel" class="form-control form-control-lg" id="modalPhone" placeholder="7XX XXX XXX" maxlength="9" autocomplete="off">
                    </div>
                    <div class="small text-muted mb-3">
                        <i class="fas fa-info-circle me-1"></i> Weka namba bila <strong>0</strong> au <strong>+255</strong>
                    </div>
                    <button type="button" class="btn btn-primary w-100 btn-lg rounded-3" id="payMobileBtn" onclick="submitMobilePayment()">
                        <i class="fas fa-check-circle me-2"></i> Pay
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'php/includes/dashboard-end.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let selectedNetwork = '';

function selectType(el, type) {
    document.querySelectorAll('.opt-btn').forEach(o => o.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('paymentType').value = type;
    document.getElementById('customAmountBox').style.display = type === 'wallet_topup' ? 'block' : 'none';
}

function setMethod(method, submethod) {
    document.getElementById('paymentMethod').value = method;
    document.getElementById('paymentSubmethod').value = submethod;
}

function switchTab(btn, contentId) {
    document.querySelectorAll('.nav-payments .nav-link').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('show', 'active'));
    btn.classList.add('active');
    document.getElementById(contentId).classList.add('show', 'active');
}

function selectNetwork(el, network) {
    document.querySelectorAll('.network-card').forEach(c => c.classList.remove('active'));
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

    const fullNumber = '+255' + raw.slice(-9);
    document.getElementById('phoneInput').value = fullNumber;
    document.getElementById('paymentForm').submit();
}

// Auto-format phone: only digits, max 9
document.getElementById('modalPhone').addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '').slice(0, 9);
    this.classList.remove('is-invalid');

    // Visual grouping: 7XX XXX XXX
    const v = this.value;
    if (v.length > 5) {
        this.value = v.slice(0, 5) + ' ' + v.slice(5);
    }
});

// Restore state on error
document.addEventListener('DOMContentLoaded', function () {
    const savedType = '<?= htmlspecialchars($_POST['payment_type'] ?? 'subscription') ?>';

    if (savedType === 'wallet_topup') {
        const opts = document.querySelectorAll('.opt-btn');
        opts.forEach(o => {
            if (o.querySelector('.lbl')?.textContent.includes('Wallet')) {
                o.classList.add('active');
                opts[0].classList.remove('active');
            }
        });
        document.getElementById('paymentType').value = 'wallet_topup';
        document.getElementById('customAmountBox').style.display = 'block';
    }

    const savedMethod = '<?= htmlspecialchars($_POST['payment_method'] ?? '') ?>';
    const savedSub = '<?= htmlspecialchars($_POST['payment_submethod'] ?? '') ?>';
    if (savedMethod) {
        setMethod(savedMethod, savedSub);
        const tabMap = {
            'snippe-mobile': 'tab-mobile',
            'snippe-card': 'tab-card',
            'manual-': 'tab-manual'
        };
        const tabId = tabMap[savedMethod + '-' + savedSub];
        if (tabId) {
            const tab = document.getElementById(tabId);
            if (tab) switchTab(tab, tab.getAttribute('onclick')?.match(/'([^']+)'/)?.[1] || 'content-' + savedMethod + (savedSub ? '-' + savedSub : ''));
        }
    }
});
</script>
</body>
</html>
