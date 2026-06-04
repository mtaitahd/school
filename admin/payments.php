<?php
/**
 * Admin Payments & Subscriptions Management
 */

require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/payment.php';

sec_require_rate_limit();
sec_send_headers();
auth_require_role(['admin'], '../index.php');

$parentId = auth_user_id();

// Handle manual payment approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $paymentId = (int) ($_POST['payment_id'] ?? 0);

    if ($_POST['action'] === 'approve' && $paymentId) {
        pay_verify_manual($paymentId, 'approve');
        $_SESSION['flash_message'] = 'Payment approved and subscription activated.';
    } elseif ($_POST['action'] === 'reject' && $paymentId) {
        pay_verify_manual($paymentId, 'reject');
        $_SESSION['flash_message'] = 'Payment rejected.';
    }

    header('Location: payments.php');
    exit;
}

// Stats
$stats = [
    'active_subs' => $database->fetchOne("SELECT COUNT(*) as c FROM `subscriptions` WHERE status = 'active'")['c'] ?? 0,
    'trial_subs' => $database->fetchOne("SELECT COUNT(*) as c FROM `subscriptions` WHERE status = 'trial'")['c'] ?? 0,
    'expired_subs' => $database->fetchOne("SELECT COUNT(*) as c FROM `subscriptions` WHERE status = 'expired'")['c'] ?? 0,
    'pending_payments' => $database->fetchOne("SELECT COUNT(*) as c FROM `payments` WHERE status = 'pending'")['c'] ?? 0,
    'manual_review' => $database->fetchOne("SELECT COUNT(*) as c FROM `payments` WHERE status = 'manual_review'")['c'] ?? 0,
    'total_revenue' => $database->fetchOne("SELECT COALESCE(SUM(amount), 0) as c FROM `payments` WHERE status = 'completed'")['c'] ?? 0,
];

// Payments
$payments = $database->fetchAll("
    SELECT p.*, u.first_name, u.last_name, u.username
    FROM `payments` p
    JOIN `users` u ON p.parent_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 100
");

// Subscriptions
$subscriptions = $database->fetchAll("
    SELECT s.*, u.first_name, u.last_name, u.username
    FROM `subscriptions` s
    JOIN `users` u ON s.parent_id = u.user_id
    ORDER BY s.created_at DESC
    LIMIT 100
");

$flash = $_SESSION['flash_message'] ?? '';
unset($_SESSION['flash_message']);

$current_lang = $_SESSION['lang'] ?? 'en';
$active_nav = 'admin_payments';
$layout = 'dashboard';
$dashboard_role = 'admin';
$sidebar_active = 'payments';
$dashboard_page_title = 'Payments';
require_once __DIR__ . '/../php/includes/lang.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stat-card { border-radius: 12px; border: none; transition: transform 0.15s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        .badge-manual { background: #fef3c7; color: #92400e; }
        .badge-snippe { background: #dbeafe; color: #1e40af; }
        .table-hover tbody tr { transition: background 0.1s; }
    </style>
</head>
<body>
<?php include '../php/includes/dashboard-start.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800" style="font-weight:700;">Payments & Subscriptions</h1>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#dbeafe;"><i class="fas fa-check-circle text-primary"></i></div>
                    <div><div class="text-muted small">Active</div><div class="fw-bold fs-5"><?= $stats['active_subs'] ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fef3c7;"><i class="fas fa-clock text-warning"></i></div>
                    <div><div class="text-muted small">Trial</div><div class="fw-bold fs-5"><?= $stats['trial_subs'] ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fce4ec;"><i class="fas fa-times-circle text-danger"></i></div>
                    <div><div class="text-muted small">Expired</div><div class="fw-bold fs-5"><?= $stats['expired_subs'] ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e0f2fe;"><i class="fas fa-hourglass-half text-info"></i></div>
                    <div><div class="text-muted small">Pending</div><div class="fw-bold fs-5"><?= $stats['pending_payments'] ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fff3cd;"><i class="fas fa-search text-warning"></i></div>
                    <div><div class="text-muted small">Manual Review</div><div class="fw-bold fs-5"><?= $stats['manual_review'] ?></div></div>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <div class="card stat-card shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#d1fae5;"><i class="fas fa-money-bill-wave text-success"></i></div>
                    <div><div class="text-muted small">Revenue</div><div class="fw-bold fs-5"><?= number_format((float) $stats['total_revenue']) ?> TZS</div></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Payments Pending Approval -->
    <?php
    $manualPending = array_filter($payments, fn($p) => $p['status'] === 'manual_review');
    if (!empty($manualPending)):
    ?>
    <div class="card shadow-sm mb-4" style="border-radius:12px;border:none;">
        <div class="card-header bg-warning text-dark fw-bold py-3" style="border-radius:12px 12px 0 0;">
            <i class="fas fa-clock me-2"></i> Manual Payments Pending Approval (<?= count($manualPending) ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Parent</th>
                            <th>Amount</th>
                            <th>Phone</th>
                            <th>Transaction ID</th>
                            <th>Date</th>
                            <th style="width:180px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manualPending as $pmt): ?>
                        <tr>
                            <td>#<?= $pmt['id'] ?></td>
                            <td><strong><?= htmlspecialchars($pmt['first_name'] . ' ' . $pmt['last_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($pmt['username']) ?></small></td>
                            <td><strong><?= number_format((float) $pmt['amount']) ?></strong> TZS</td>
                            <td><?= htmlspecialchars($pmt['phone'] ?? '-') ?></td>
                            <td><code><?= htmlspecialchars($pmt['transaction_id'] ?? '-') ?></code></td>
                            <td><small><?= date('d M Y H:i', strtotime($pmt['created_at'])) ?></small></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="payment_id" value="<?= $pmt['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" onclick="return confirm('Approve this payment and activate subscription?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Reject this payment?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- All Payments -->
    <div class="card shadow-sm mb-4" style="border-radius:12px;border:none;">
        <div class="card-header bg-white fw-bold py-3" style="border-radius:12px 12px 0 0;border-bottom:2px solid #e2e8f0;">
            <i class="fas fa-credit-card me-2"></i> All Payments
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Parent</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pmt): ?>
                        <tr>
                            <td>#<?= $pmt['id'] ?></td>
                            <td><strong><?= htmlspecialchars($pmt['first_name'] . ' ' . $pmt['last_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($pmt['username']) ?></small></td>
                            <td><strong><?= number_format((float) $pmt['amount']) ?></strong> TZS</td>
                            <td>
                                <span class="badge <?= $pmt['method'] === 'snippe' ? 'badge-snippe' : 'badge-manual' ?>">
                                    <?= $pmt['method'] === 'snippe' ? 'Snippe' : 'Manual' ?>
                                </span>
                            </td>
                            <td><small><?= ucfirst($pmt['payment_type']) ?></small></td>
                            <td><code style="font-size:0.8rem;"><?= htmlspecialchars($pmt['transaction_id'] ?? '-') ?></code></td>
                            <td>
                                <?php $statusClass = match($pmt['status']) {
                                    'completed' => 'bg-success',
                                    'pending' => 'bg-warning text-dark',
                                    'failed' => 'bg-danger',
                                    'manual_review' => 'bg-info text-dark',
                                    default => 'bg-secondary'
                                }; ?>
                                <span class="badge <?= $statusClass ?>"><?= ucfirst(str_replace('_', ' ', $pmt['status'])) ?></span>
                            </td>
                            <td><small><?= date('d M Y H:i', strtotime($pmt['created_at'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No payments yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscriptions -->
    <div class="card shadow-sm" style="border-radius:12px;border:none;">
        <div class="card-header bg-white fw-bold py-3" style="border-radius:12px 12px 0 0;border-bottom:2px solid #e2e8f0;">
            <i class="fas fa-calendar-alt me-2"></i> Active Subscriptions
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Parent</th>
                            <th>Status</th>
                            <th>Period Start</th>
                            <th>Period End</th>
                            <th>Payment Method</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td>#<?= $sub['id'] ?></td>
                            <td><strong><?= htmlspecialchars($sub['first_name'] . ' ' . $sub['last_name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($sub['username']) ?></small></td>
                            <td>
                                <?php $sClass = match($sub['status']) {
                                    'active' => 'bg-success',
                                    'trial' => 'bg-info text-dark',
                                    'expired' => 'bg-danger',
                                    'cancelled' => 'bg-secondary',
                                    default => 'bg-warning text-dark'
                                }; ?>
                                <span class="badge <?= $sClass ?>"><?= ucfirst($sub['status']) ?></span>
                            </td>
                            <td><small><?= $sub['current_period_start'] ? date('d M Y', strtotime($sub['current_period_start'])) : '-' ?></small></td>
                            <td><small><?= $sub['current_period_end'] ? date('d M Y', strtotime($sub['current_period_end'])) : '-' ?></small></td>
                            <td><small><?= ucfirst($sub['payment_method'] ?? 'none') ?></small></td>
                            <td><small><?= date('d M Y', strtotime($sub['created_at'])) ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subscriptions)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No subscriptions yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
</body>
</html>
