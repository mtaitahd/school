<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';

sec_require_rate_limit();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$csrf_error = $_SESSION['_csrf_error'] ?? null;
unset($_SESSION['_csrf_error']);

$message = '';
$message_type = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($database->execute("DELETE FROM announcement_ticker WHERE ticker_id = ?", [$id])) {
        $message = 'Ticker message deleted successfully.';
        $message_type = 'success';
    } else {
        $message = 'Failed to delete ticker message.';
        $message_type = 'error';
    }
}

// Handle toggle active
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    $ticker = $database->fetchOne("SELECT is_active FROM announcement_ticker WHERE ticker_id = ?", [$id]);
    if ($ticker) {
        $new_status = $ticker['is_active'] ? 0 : 1;
        if ($database->execute("UPDATE announcement_ticker SET is_active = ? WHERE ticker_id = ?", [$new_status, $id])) {
            $message = $new_status ? 'Ticker message activated.' : 'Ticker message deactivated.';
            $message_type = 'success';
        } else {
            $message = 'Failed to toggle ticker message status.';
            $message_type = 'error';
        }
    }
}

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();
    $message_text = trim($_POST['message'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $sort_order = (int) ($_POST['sort_order'] ?? 0);
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');

    if ($message_text === '') {
        $message = 'Message is required.';
        $message_type = 'error';
    } elseif ($_POST['action'] === 'create') {
        if ($database->insert(
            "INSERT INTO announcement_ticker (message, url, is_active, sort_order, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)",
            [$message_text, $url ?: null, $is_active, $sort_order, $start_date ?: null, $end_date ?: null]
        )) {
            $message = 'Ticker message created successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to create ticker message.';
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        if ($database->execute(
            "UPDATE announcement_ticker SET message = ?, url = ?, is_active = ?, sort_order = ?, start_date = ?, end_date = ? WHERE ticker_id = ?",
            [$message_text, $url ?: null, $is_active, $sort_order, $start_date ?: null, $end_date ?: null, $id]
        )) {
            $message = 'Ticker message updated successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to update ticker message.';
            $message_type = 'error';
        }
    }
}

$tickers = $database->fetchAll("SELECT * FROM announcement_ticker ORDER BY sort_order ASC, created_at DESC");
$edit_ticker = null;
if (isset($_GET['edit'])) {
    $edit_ticker = $database->fetchOne("SELECT * FROM announcement_ticker WHERE ticker_id = ?", [(int) $_GET['edit']]);
}

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'announcement-ticker';
$dashboard_page_title = 'Manage Ticker';
$lang_page = 'announcement-ticker.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ticker - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

<div class="card mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Ticker Messages</h6>
        <div>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tickerModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:6px 18px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                <i class="fas fa-plus me-1"></i>New
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($csrf_error): ?>
            <div class="alert alert-danger py-2 px-3 mb-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
                <?php echo htmlspecialchars($csrf_error); ?>
            </div>
        <?php endif; ?>
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> py-2 px-3 mb-3 text-center" style="border-radius:10px;font-size:0.9rem;border:none;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Order</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tickers)): ?>
                        <tr><td colspan="6" class="text-center py-4" style="color:var(--text-light);">No ticker messages yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tickers as $t): ?>
                        <tr>
                            <td style="font-weight:600;max-width:400px;">
                                <?php echo htmlspecialchars($t['message']); ?>
                                <?php if ($t['url']): ?>
                                    <a href="<?php echo htmlspecialchars($t['url']); ?>" target="_blank" class="ms-1" style="color:var(--primary-blue);" title="<?php echo htmlspecialchars($t['url']); ?>">
                                        <i class="fas fa-external-link-alt" style="font-size:0.75rem;"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['is_active']): ?>
                                    <span class="text-success fw-semibold">Active</span>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo (int) $t['sort_order']; ?></td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo $t['start_date'] ? date('M j, Y', strtotime($t['start_date'])) : '—'; ?></td>
                            <td style="color:var(--text-light);font-size:0.9rem;"><?php echo $t['end_date'] ? date('M j, Y', strtotime($t['end_date'])) : '—'; ?></td>
                            <td style="text-align:center;">
                                <a href="?edit=<?php echo $t['ticker_id']; ?>" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?toggle=<?php echo $t['ticker_id']; ?>" class="btn btn-sm <?php echo $t['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;">
                                    <i class="fas <?php echo $t['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i> <?php echo $t['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </a>
                                <a href="?delete=<?php echo $t['ticker_id']; ?>" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 14px;font-size:0.8rem;font-weight:600;margin-left:4px;" onclick="return confirm('Delete this ticker message?');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="tickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">
                    <i class="fas fa-scroll me-2" style="color:var(--primary-blue);"></i>
                    <?php echo $edit_ticker ? 'Edit Ticker Message' : 'New Ticker Message'; ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="<?php echo $edit_ticker ? 'update' : 'create'; ?>">
                <?php if ($edit_ticker): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_ticker['ticker_id']; ?>">
                <?php endif; ?>
                <div class="modal-body" style="padding:20px 24px;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="message" style="font-size:0.85rem;color:var(--text-dark);">Message <span style="color:#e74a3b;">*</span></label>
                        <textarea id="message" name="message" class="form-control" rows="3" required maxlength="500" style="border-radius:10px;"><?php echo htmlspecialchars($edit_ticker['message'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="url" style="font-size:0.85rem;color:var(--text-dark);">URL <small style="color:var(--text-light);font-weight:400;">(optional - link users to more info)</small></label>
                        <input type="url" id="url" name="url" class="form-control" value="<?php echo htmlspecialchars($edit_ticker['url'] ?? ''); ?>" placeholder="https://" maxlength="500" style="border-radius:10px;">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold" for="sort_order" style="font-size:0.85rem;color:var(--text-dark);">Sort Order</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?php echo (int) ($edit_ticker['sort_order'] ?? 0); ?>" min="0" style="border-radius:10px;">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold" for="start_date" style="font-size:0.85rem;color:var(--text-dark);">Start Date <small style="color:var(--text-light);font-weight:400;">(optional)</small></label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $edit_ticker['start_date'] ?? ''; ?>" style="border-radius:10px;">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold" for="end_date" style="font-size:0.85rem;color:var(--text-dark);">End Date <small style="color:var(--text-light);font-weight:400;">(optional)</small></label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $edit_ticker['end_date'] ?? ''; ?>" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" style="width:20px;height:20px;cursor:pointer;" <?php echo ($edit_ticker['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label fw-semibold" for="is_active" style="font-size:0.9rem;color:var(--text-dark);cursor:pointer;">
                            Active (shows on homepage ticker)
                        </label>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">
                        <i class="fas fa-save me-2"></i><?php echo $edit_ticker ? 'Update' : 'Save'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../php/includes/dashboard-end.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/main.js"></script>
<script src="../js/dashboard.js"></script>
<?php if ($edit_ticker): ?>
<script>new bootstrap.Modal('#tickerModal').show();</script>
<?php endif; ?>
</body>
</html>
