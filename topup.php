<?php
/**
 * Topup / Subscription Payment Page
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

// Redirect non-parents
if ($role !== 'parent') {
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } elseif ($role === 'teacher') {
        header('Location: teacher/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit;
}

$subStatus = sub_get_status($parentId);
$walletBalance = pay_get_wallet_balance($parentId);
$message = '';
$error = '';
$success = false;

// Handle POST payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $paymentMethod = $_POST['payment_method'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $transactionId = $_POST['transaction_id'] ?? '';

    if ($paymentMethod === 'snippe') {
        if (!preg_match('/^(0|\+?255)?[67]\d{8}$/', preg_replace('/[^0-9]/', '', $phone))) {
            $error = 'Tafadhali ingiza namba halali ya simu (Tanzania)';
        } else {
            $normalized = '+255' . substr(preg_replace('/[^0-9]/', '', $phone), -9);
            $result = pay_create_snippe_payment($parentId, $normalized);
            if ($result['success']) {
                if ($result['checkout_url']) {
                    header('Location: ' . $result['checkout_url']);
                    exit;
                }
                $message = 'Malipo yamepokelewa. Unaweza kuendelea.';
                $success = true;
                $subStatus = sub_get_status($parentId);
            } else {
                $error = $result['error'] ?? 'Hitilafu ya malipo. Tafadhali jaribu tena.';
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
$lang_page = 'topup.php';
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
        .topup-container { max-width: 720px; margin: 40px auto; padding: 0 20px; }
        .topup-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; padding: 28px 32px; }
        .card-header h1 { font-size: 24px; margin-bottom: 4px; }
        .card-header p { opacity: 0.9; font-size: 14px; }
        .card-body { padding: 28px 32px; }
        .status-bar { display: flex; gap: 20px; margin-bottom: 28px; flex-wrap: wrap; }
        .status-item { flex: 1; min-width: 140px; background: #f8fafc; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #e2e8f0; }
        .status-item .label { font-size: 12px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-item .value { font-size: 22px; font-weight: 700; color: #1e293b; margin-top: 4px; }
        .status-item .value.active { color: #16a34a; }
        .status-item .value.expired { color: #dc2626; }
        .status-item .value.trial { color: #2563eb; }
        .divider { height: 1px; background: #e2e8f0; margin: 24px 0; }
        .payment-option { border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px; cursor: pointer; transition: all 0.2s; }
        .payment-option:hover { border-color: #93c5fd; }
        .payment-option.selected { border-color: #2563eb; background: #eff6ff; }
        .payment-option .option-header { display: flex; align-items: center; gap: 12px; }
        .payment-option .option-header .icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: #fff; }
        .payment-option .option-header .icon.snippe { background: #2563eb; }
        .payment-option .option-header .icon.manual { background: #f59e0b; }
        .payment-option .option-header .info h3 { font-size: 16px; color: #1e293b; }
        .payment-option .option-header .info p { font-size: 13px; color: #64748b; }
        .payment-option .option-body { display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .payment-option.selected .option-body { display: block; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1.5px solid #d1d5db; border-radius: 10px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .btn-primary { width: 100%; padding: 14px; background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.15s, box-shadow 0.15s; }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(37,99,235,0.3); }
        .btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
        .manual-details { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
        .manual-details .number { font-size: 24px; font-weight: 700; color: #92400e; letter-spacing: 1px; }
        .manual-details .name { font-size: 14px; color: #92400e; margin-top: 4px; }
        .manual-details .amount { font-size: 18px; font-weight: 600; color: #92400e; margin-top: 8px; }
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-info { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .features { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 20px; }
        .features .item { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569; }
        .features .item i { color: #16a34a; font-size: 14px; }
        @media (max-width: 600px) {
            .topup-container { margin: 20px auto; }
            .card-header, .card-body { padding: 20px; }
            .status-bar { flex-direction: column; }
            .features { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/php/includes/header.php'; ?>

    <div class="topup-container">
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="topup-card">
            <div class="card-header">
                <h1><i class="fas fa-wallet"></i> <?= $current_lang === 'sw' ? 'Malipo na Kujiandikisha' : 'Topup & Subscription' ?></h1>
                <p><?= $current_lang === 'sw' ? 'Chagua njia ya malipo ili kuendelea kutumia huduma' : 'Choose a payment method to continue using the service' ?></p>
            </div>
            <div class="card-body">
                <!-- Status Bar -->
                <div class="status-bar">
                    <div class="status-item">
                        <div class="label"><?= $current_lang === 'sw' ? 'Hali' : 'Status' ?></div>
                        <div class="value <?= $subStatus['status'] ?>">
                            <?php if ($subStatus['status'] === 'active'): ?>
                                <i class="fas fa-check-circle"></i> <?= $current_lang === 'sw' ? 'Inatumika' : 'Active' ?>
                            <?php elseif ($subStatus['status'] === 'trial'): ?>
                                <i class="fas fa-clock"></i> <?= $current_lang === 'sw' ? 'Majaribio' : 'Trial' ?>
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> <?= $current_lang === 'sw' ? 'Imeisha' : 'Expired' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="label"><?= $current_lang === 'sw' ? 'Siku Zilizobaki' : 'Days Remaining' ?></div>
                        <div class="value"><?= $subStatus['days_remaining'] ?> <?= $current_lang === 'sw' ? 'siku' : 'days' ?></div>
                    </div>
                    <div class="status-item">
                        <div class="label"><?= $current_lang === 'sw' ? 'Salio la Wallet' : 'Wallet Balance' ?></div>
                        <div class="value"><?= number_format($walletBalance) ?> TZS</div>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="features">
                        <div class="item"><i class="fas fa-child"></i> <?= $current_lang === 'sw' ? 'Angalia maendeleo ya mtoto' : 'View child progress' ?></div>
                        <div class="item"><i class="fas fa-star"></i> <?= $current_lang === 'sw' ? 'Matokeo na nyota' : 'Results & stars' ?></div>
                        <div class="item"><i class="fas fa-book"></i> <?= $current_lang === 'sw' ? 'Kazi za nyumbani' : 'Assignments' ?></div>
                        <div class="item"><i class="fas fa-chart-line"></i> <?= $current_lang === 'sw' ? 'Ripoti za maendeleo' : 'Performance reports' ?></div>
                    </div>
                    <div style="margin-top:20px;text-align:center;">
                        <a href="parent/dashboard.php" class="btn-primary" style="display:inline-block;width:auto;padding:12px 32px;text-decoration:none;">
                            <i class="fas fa-arrow-left"></i> <?= $current_lang === 'sw' ? 'Rudi kwenye Dashibodi' : 'Back to Dashboard' ?>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Payment Methods -->
                    <form method="POST" id="paymentForm">
                        <?= csrf_field() ?>

                        <h3 style="margin-bottom:16px;font-size:18px;color:#1e293b;">
                            <?= $current_lang === 'sw' ? 'Chagua Njia ya Malipo' : 'Choose Payment Method' ?>
                        </h3>

                        <!-- Option 1: Snippe Instant Payment -->
                        <div class="payment-option" onclick="selectOption(this, 'snippe')">
                            <div class="option-header">
                                <div class="icon snippe"><i class="fas fa-bolt"></i></div>
                                <div class="info">
                                    <h3><?= $current_lang === 'sw' ? 'Malipo ya Papo kwa Papo' : 'Instant Payment' ?> <span style="font-size:11px;background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:4px;"><?= $current_lang === 'sw' ? 'Ilipendekezwa' : 'Recommended' ?></span></h3>
                                    <p><?= $current_lang === 'sw' ? 'Lipa kwa M-Pesa, Airtel, Tigo, Mixx au Kadi' : 'Pay via M-Pesa, Airtel, Tigo, Mixx or Card' ?></p>
                                </div>
                            </div>
                            <div class="option-body">
                                <div class="form-group">
                                    <label><?= $current_lang === 'sw' ? 'Namba ya Simu' : 'Phone Number' ?></label>
                                    <input type="tel" name="phone" placeholder="07XX XXX XXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                                </div>
                                <div style="font-size:13px;color:#64748b;margin-bottom:16px;">
                                    <strong><?= number_format(SUBSCRIPTION_AMOUNT) ?> TZS</strong> — <?= $current_lang === 'sw' ? 'Uanachama wa mwezi 1' : '1 month subscription' ?>
                                </div>
                                <button type="submit" class="btn-primary" onclick="document.getElementById('paymentMethod').value='snippe'">
                                    <i class="fas fa-credit-card"></i> <?= $current_lang === 'sw' ? 'Lipa Sasa' : 'Pay Now' ?> — <?= number_format(SUBSCRIPTION_AMOUNT) ?> TZS
                                </button>
                            </div>
                        </div>

                        <!-- Option 2: Manual Payment -->
                        <div class="payment-option" onclick="selectOption(this, 'manual')">
                            <div class="option-header">
                                <div class="icon manual"><i class="fas fa-hand-holding-usd"></i></div>
                                <div class="info">
                                    <h3><?= $current_lang === 'sw' ? 'Malipo ya Mkono' : 'Manual Payment' ?></h3>
                                    <p><?= $current_lang === 'sw' ? 'Lipa kwa Mix by Yas Lipa' : 'Pay via Mix by Yas Lipa' ?></p>
                                </div>
                            </div>
                            <div class="option-body">
                                <div class="manual-details">
                                    <div class="number"><i class="fas fa-phone-alt"></i> <?= MANUAL_PAYMENT_NUMBER ?></div>
                                    <div class="name"><i class="fas fa-user"></i> <?= MANUAL_PAYMENT_NAME ?></div>
                                    <div class="amount"><i class="fas fa-tag"></i> <?= number_format(SUBSCRIPTION_AMOUNT) ?> TZS</div>
                                </div>
                                <ol style="margin:12px 0 16px 20px;font-size:13px;color:#475569;line-height:1.8;">
                                    <li><?= $current_lang === 'sw' ? 'Tuma 1500 TZS kwa namba' : 'Send 1500 TZS to' ?> <strong><?= MANUAL_PAYMENT_NUMBER ?></strong> (<?= MANUAL_PAYMENT_NAME ?>)</li>
                                    <li><?= $current_lang === 'sw' ? 'Nakili Transaction ID uliyopokea' : 'Copy the Transaction ID you receive' ?></li>
                                    <li><?= $current_lang === 'sw' ? 'Ingiza Transaction ID hapa chini' : 'Enter the Transaction ID below' ?></li>
                                </ol>
                                <div class="form-group">
                                    <label><?= $current_lang === 'sw' ? 'Namba ya Simu uliyotumia' : 'Phone Number Used' ?></label>
                                    <input type="tel" name="phone_manual" id="manualPhone" placeholder="07XX XXX XXX">
                                </div>
                                <div class="form-group">
                                    <label><?= $current_lang === 'sw' ? 'Transaction ID' : 'Transaction ID' ?></label>
                                    <input type="text" name="transaction_id" id="manualTxnId" placeholder="e.g. YL123456789">
                                </div>
                                <button type="submit" class="btn-primary" style="background:linear-gradient(135deg,#f59e0b,#d97706);" onclick="document.getElementById('paymentMethod').value='manual'">
                                    <i class="fas fa-paper-plane"></i> <?= $current_lang === 'sw' ? 'Wasilisha Malipo' : 'Submit Payment' ?>
                                </button>
                                <p style="font-size:12px;color:#64748b;margin-top:10px;text-align:center;">
                                    <i class="fas fa-clock"></i> <?= $current_lang === 'sw' ? 'Malipo yatahakikiwa na msimamizi ndani ya saa 24' : 'Payments are verified within 24 hours by an admin' ?>
                                </p>
                            </div>
                        </div>

                        <input type="hidden" name="payment_method" id="paymentMethod" value="">
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Need Help -->
        <div style="text-align:center;margin-top:24px;font-size:13px;color:#64748b;">
            <?= $current_lang === 'sw' ? 'Una swali?' : 'Have a question?' ?>
            <a href="contact.php" style="color:#2563eb;text-decoration:none;font-weight:600;">
                <?= $current_lang === 'sw' ? 'Wasiliana nasi' : 'Contact us' ?>
            </a>
        </div>
    </div>

    <?php require_once __DIR__ . '/php/includes/footer.php'; ?>

    <script>
        function selectOption(el, method) {
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('paymentMethod').value = method;
        }

        // Auto-select if there's an error
        const method = '<?= htmlspecialchars($_POST['payment_method'] ?? '') ?>';
        if (method) {
            document.querySelectorAll('.payment-option').forEach(o => {
                const isSnippe = o.querySelector('.icon.snippe');
                if ((method === 'snippe' && isSnippe) || (method === 'manual' && !isSnippe)) {
                    o.classList.add('selected');
                }
            });
            document.getElementById('paymentMethod').value = method;
        }
    </script>
</body>
</html>
