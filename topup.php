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
    $transactionId = $_POST['transaction_id'] ?? '';
    $customAmount = (float) ($_POST['amount'] ?? 0);

    if ($paymentMethod === 'snippe') {
        if (!preg_match('/^(0|\+?255)?[67]\d{8}$/', preg_replace('/[^0-9]/', '', $phone))) {
            $error = 'Tafadhali ingiza namba halali ya simu (Tanzania)';
        } else {
            $normalized = '+255' . substr(preg_replace('/[^0-9]/', '', $phone), -9);
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
    } elseif ($paymentMethod === 'manual') {
        if (empty($transactionId)) {
            $error = 'Tafadhali ingiza Transaction ID';
        } elseif (empty($phone)) {
            $error = 'Tafadhali ingiza namba ya simu uliyotumia';
        } else {
            $result = pay_create_manual_payment($parentId, $phone, $transactionId);
            $message = 'Malipo yako yamewasilishwa kwa uhakiki. Utapokea SMS uthibitisho.';
            $success = true;
        }
    }
}

$active_nav = 'topup';
$current_lang = $_SESSION['lang'] ?? 'en';
$layout = '';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang === 'sw' ? 'sw' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Topup - Smart Math Corner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', system-ui, sans-serif; }
        body { background: #f0f4f8; min-height: 100vh; }
        .container { max-width: 780px; margin: 40px auto; padding: 0 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; padding: 28px 32px; }
        .card-header h1 { font-size: 24px; }
        .card-header p { opacity: 0.85; font-size: 14px; margin-top: 4px; }
        .card-body { padding: 28px 32px; }
        .status-row { display: flex; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
        .status-box { flex: 1; min-width: 130px; background: #f8fafc; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; }
        .status-box .label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-box .value { font-size: 20px; font-weight: 700; color: #1e293b; margin-top: 4px; }
        .status-box .value.green { color: #16a34a; }
        .status-box .value.red { color: #dc2626; }
        .status-box .value.blue { color: #2563eb; }
        .method-tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .method-tab { flex: 1; min-width: 140px; padding: 14px 16px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; }
        .method-tab:hover { border-color: #93c5fd; }
        .method-tab.active { border-color: #2563eb; background: #eff6ff; }
        .method-tab .tab-icon { font-size: 24px; margin-bottom: 6px; }
        .method-tab .tab-label { font-size: 13px; font-weight: 600; color: #1e293b; }
        .method-tab .tab-desc { font-size: 11px; color: #64748b; }
        .method-content { display: none; }
        .method-content.active { display: block; }
        .submethod-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .submethod-btn { flex: 1; min-width: 100px; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 10px; text-align: center; cursor: pointer; transition: all 0.2s; background: #fff; font-size: 12px; font-weight: 600; }
        .submethod-btn:hover { border-color: #93c5fd; }
        .submethod-btn.active { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; }
        .submethod-btn img, .submethod-btn i { display: block; font-size: 20px; margin-bottom: 4px; }
        .amount-options { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .amount-opt { flex: 1; min-width: 120px; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .amount-opt:hover { border-color: #93c5fd; }
        .amount-opt.active { border-color: #2563eb; background: #eff6ff; }
        .amount-opt .amt-value { font-size: 18px; font-weight: 700; color: #1e293b; }
        .amount-opt .amt-label { font-size: 11px; color: #64748b; }
        .amount-opt .badge-rec { font-size: 10px; background: #dbeafe; color: #1d4ed8; padding: 2px 8px; border-radius: 4px; display: inline-block; margin-top: 4px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-group input, .form-group select { width: 100%; padding: 12px 16px; border: 1.5px solid #d1d5db; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-primary { width: 100%; padding: 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
        .btn-warning { width: 100%; padding: 14px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.15s; }
        .btn-warning:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245,158,11,0.3); }
        .manual-box { background: #fffbeb; border: 1.5px solid #fde68a; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .manual-box .number { font-size: 26px; font-weight: 700; color: #92400e; letter-spacing: 1px; }
        .manual-box .name { font-size: 14px; color: #92400e; margin-top: 2px; }
        .manual-box .amount { font-size: 18px; font-weight: 600; color: #92400e; margin-top: 8px; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 18px; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
        .features .item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; }
        .features .item i { color: #16a34a; }
        @media (max-width: 640px) {
            .container { margin: 20px auto; }
            .card-header, .card-body { padding: 20px; }
            .status-row { flex-direction: column; }
            .method-tabs { flex-direction: column; }
            .submethod-row { flex-direction: column; }
            .amount-options { flex-direction: column; }
            .features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/php/includes/header.php'; ?>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h1><i class="fas fa-wallet"></i> Topup & Subscription</h1>
                <p>Choose a payment method to subscribe or topup your wallet</p>
            </div>
            <div class="card-body">
                <!-- Status bar -->
                <div class="status-row">
                    <div class="status-box">
                        <div class="label">Status</div>
                        <div class="value <?= $subStatus['status'] === 'active' ? 'green' : ($subStatus['status'] === 'trial' ? 'blue' : 'red') ?>">
                            <?php if ($subStatus['status'] === 'active'): ?><i class="fas fa-check-circle"></i> Active
                            <?php elseif ($subStatus['status'] === 'trial'): ?><i class="fas fa-clock"></i> Trial
                            <?php else: ?><i class="fas fa-times-circle"></i> Expired<?php endif; ?>
                        </div>
                    </div>
                    <div class="status-box">
                        <div class="label">Days Left</div>
                        <div class="value"><?= $subStatus['days_remaining'] ?>d</div>
                    </div>
                    <div class="status-box">
                        <div class="label">Wallet</div>
                        <div class="value green"><?= number_format($walletBalance) ?> TZS</div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="features">
                        <div class="item"><i class="fas fa-child"></i> View child progress</div>
                        <div class="item"><i class="fas fa-star"></i> Results & stars</div>
                        <div class="item"><i class="fas fa-book"></i> Assignments</div>
                        <div class="item"><i class="fas fa-chart-line"></i> Performance reports</div>
                    </div>
                    <div style="margin-top:20px;text-align:center;">
                        <a href="parent/dashboard.php" class="btn-primary" style="display:inline-block;width:auto;padding:12px 32px;text-decoration:none;">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                    <?php return; endif; ?>

                <form method="POST" id="paymentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="payment_method" id="paymentMethod" value="">
                    <input type="hidden" name="payment_submethod" id="paymentSubmethod" value="mobile">
                    <input type="hidden" name="payment_type" id="paymentType" value="subscription">

                    <!-- Payment type: Subscription or Wallet Topup -->
                    <div class="amount-options" style="margin-bottom:24px;">
                        <div class="amount-opt active" onclick="selectAmountType(this, 'subscription')">
                            <div class="amt-value">1,500 TZS</div>
                            <div class="amt-label">Monthly Subscription</div>
                            <span class="badge-rec">Recommended</span>
                        </div>
                        <div class="amount-opt" onclick="selectAmountType(this, 'wallet_topup')">
                            <div class="amt-value">Custom</div>
                            <div class="amt-label">Wallet Topup</div>
                        </div>
                    </div>

                    <!-- Custom amount input (shown only for wallet topup) -->
                    <div id="customAmountBox" style="display:none;margin-bottom:20px;">
                        <div class="form-group">
                            <label>Amount (TZS)</label>
                            <input type="number" name="amount" id="customAmount" min="500" step="500" placeholder="Enter amount (min 500 TZS)">
                        </div>
                    </div>

                    <!-- Payment method tabs -->
                    <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:10px;">Payment Method</div>
                    <div class="method-tabs">
                        <div class="method-tab active" onclick="selectMethod(this, 'snippe', 'mobile')">
                            <div class="tab-icon"><i class="fas fa-mobile-alt"></i></div>
                            <div class="tab-label">Mobile Money</div>
                            <div class="tab-desc">M-Pesa, Airtel, Mixx, Halotel</div>
                        </div>
                        <div class="method-tab" onclick="selectMethod(this, 'snippe', 'card')">
                            <div class="tab-icon"><i class="fas fa-credit-card"></i></div>
                            <div class="tab-label">Card Payment</div>
                            <div class="tab-desc">Visa, Mastercard, Local debit</div>
                        </div>
                        <div class="method-tab" onclick="selectMethod(this, 'manual', '')">
                            <div class="tab-icon"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="tab-label">Manual</div>
                            <div class="tab-desc">Mix by Yas Lipa</div>
                        </div>
                    </div>

                    <!-- === Mobile Money content === -->
                    <div class="method-content active" id="content-snippe-mobile">
                        <p style="font-size:13px;color:#64748b;margin-bottom:12px;">
                            <i class="fas fa-info-circle"></i> You will receive a USSD push on your phone. Approve the payment to complete.
                        </p>
                        <div class="form-group">
                            <label>Phone Number (Tanzania)</label>
                            <input type="tel" name="phone" placeholder="07XX XXX XXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" required>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-mobile-alt"></i> Pay with Mobile Money
                        </button>
                    </div>

                    <!-- === Card Payment content === -->
                    <div class="method-content" id="content-snippe-card">
                        <p style="font-size:13px;color:#64748b;margin-bottom:12px;">
                            <i class="fas fa-info-circle"></i> You will be redirected to a secure checkout page to enter your card details.
                        </p>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? $_SESSION['email'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-credit-card"></i> Pay with Card
                        </button>
                    </div>

                    <!-- === Manual Payment content === -->
                    <div class="method-content" id="content-manual">
                        <div class="manual-box">
                            <div class="number"><i class="fas fa-phone-alt"></i> <?= MANUAL_PAYMENT_NUMBER ?></div>
                            <div class="name"><i class="fas fa-user"></i> <?= MANUAL_PAYMENT_NAME ?> (<?= MANUAL_PAYMENT_NETWORK ?>)</div>
                            <div class="amount"><i class="fas fa-tag"></i> 1,500 TZS (Monthly Subscription)</div>
                        </div>
                        <ol style="margin:12px 0 16px 18px;font-size:13px;color:#475569;line-height:1.9;">
                            <li>Send <strong>1,500 TZS</strong> to <strong><?= MANUAL_PAYMENT_NUMBER ?></strong> via Mix by Yas Lipa</li>
                            <li>Copy the <strong>Transaction ID</strong> you receive after payment</li>
                            <li>Enter the transaction details below to submit for verification</li>
                        </ol>
                        <div class="form-group">
                            <label>Phone Number Used</label>
                            <input type="tel" name="phone_manual" id="manualPhone" placeholder="07XX XXX XXX">
                        </div>
                        <div class="form-group">
                            <label>Transaction ID</label>
                            <input type="text" name="transaction_id" id="manualTxnId" placeholder="e.g. YL123456789">
                        </div>
                        <button type="submit" class="btn-warning">
                            <i class="fas fa-paper-plane"></i> Submit for Verification
                        </button>
                        <p style="font-size:12px;color:#64748b;margin-top:10px;text-align:center;">
                            <i class="fas fa-clock"></i> Verification takes up to 24 hours. You will receive an SMS confirmation.
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div style="text-align:center;margin-top:24px;font-size:13px;color:#64748b;">
            Need help? <a href="contact.php" style="color:#2563eb;font-weight:600;">Contact us</a>
        </div>
    </div>

    <?php require_once __DIR__ . '/php/includes/footer.php'; ?>

    <script>
        function selectAmountType(el, type) {
            document.querySelectorAll('.amount-opt').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('paymentType').value = type;
            document.getElementById('customAmountBox').style.display = type === 'wallet_topup' ? 'block' : 'none';
        }

        function selectMethod(el, method, submethod) {
            document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('paymentMethod').value = method;
            document.getElementById('paymentSubmethod').value = submethod;
            document.querySelectorAll('.method-content').forEach(c => c.classList.remove('active'));
            const target = document.getElementById('content-' + method + (submethod ? '-' + submethod : ''));
            if (target) target.classList.add('active');
        }

        // Restore state on error
        const savedMethod = '<?= htmlspecialchars($_POST['payment_method'] ?? '') ?>';
        const savedSub = '<?= htmlspecialchars($_POST['payment_submethod'] ?? '') ?>';
        const savedType = '<?= htmlspecialchars($_POST['payment_type'] ?? 'subscription') ?>';
        if (savedType === 'wallet_topup') {
            document.querySelectorAll('.amount-opt').forEach(o => {
                if (o.querySelector('.amt-label')?.textContent.includes('Wallet')) {
                    o.classList.add('active');
                    document.querySelector('.amount-opt:first-child').classList.remove('active');
                }
            });
            document.getElementById('paymentType').value = 'wallet_topup';
            document.getElementById('customAmountBox').style.display = 'block';
        }
        if (savedMethod) {
            document.querySelectorAll('.method-tab').forEach(t => {
                t.classList.remove('active');
                const isMobile = savedMethod === 'snippe' && savedSub === 'mobile' && t.querySelector('.tab-label')?.textContent === 'Mobile Money';
                const isCard = savedMethod === 'snippe' && savedSub === 'card' && t.querySelector('.tab-label')?.textContent === 'Card Payment';
                const isManual = savedMethod === 'manual' && t.querySelector('.tab-label')?.textContent === 'Manual';
                if (isMobile || isCard || isManual) t.classList.add('active');
            });
            document.getElementById('paymentMethod').value = savedMethod;
            document.getElementById('paymentSubmethod').value = savedSub;
            document.querySelectorAll('.method-content').forEach(c => c.classList.remove('active'));
            const target = document.getElementById('content-' + savedMethod + (savedSub ? '-' + savedSub : ''));
            if (target) target.classList.add('active');
        }
    </script>
</body>
</html>
