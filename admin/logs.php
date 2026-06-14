<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

sec_require_rate_limit();
auth_require_role(['admin'], 'index');

$error_log_path = __DIR__ . '/../logs/php_errors.log';
$rate_limit_dir = __DIR__ . '/../logs/ratelimit/';

$clear_msg = '';
$clear_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();
    $action = $_POST['action'] ?? '';

    if ($action === 'clear_error_log') {
        if (file_exists($error_log_path)) {
            if (@file_put_contents($error_log_path, '') !== false) {
                $clear_msg = 'Error log cleared successfully.';
            } else {
                $clear_err = 'Failed to clear error log. Check file permissions.';
            }
        } else {
            $clear_msg = 'Error log is already empty.';
        }
    }

    if ($action === 'clear_rate_limits') {
        $count = 0;
        if (is_dir($rate_limit_dir)) {
            $files = glob($rate_limit_dir . '*.lock');
            if ($files) {
                foreach ($files as $f) {
                    if (@unlink($f)) $count++;
                }
            }
        }
        $clear_msg = "Cleared $count rate limit lock file(s).";
    }

    if ($action === 'delete_log') {
        $file = $_POST['file'] ?? '';
        $safe = basename($file);
        $path = __DIR__ . '/../logs/' . $safe;
        if ($safe && file_exists($path) && is_file($path) && strpos(realpath($path), realpath(__DIR__ . '/../logs/')) === 0) {
            if (@unlink($path)) {
                $clear_msg = "Deleted $safe.";
            } else {
                $clear_err = "Failed to delete $safe.";
            }
        }
    }
}

$log_lines = [];
$log_size = 0;
if (file_exists($error_log_path)) {
    $log_size = filesize($error_log_path);
    $content = @file_get_contents($error_log_path);
    if ($content !== false && $content !== '') {
        $lines = explode("\n", trim($content));
        $lines = array_reverse($lines);
        $max_lines = 200;
        $log_lines = array_slice($lines, 0, $max_lines);
    }
}

$rate_limit_files = [];
if (is_dir($rate_limit_dir)) {
    $files = glob($rate_limit_dir . '*.lock');
    if ($files) {
        foreach ($files as $f) {
            $name = basename($f);
            $mtime = filemtime($f);
            $rate_limit_files[] = [
                'name' => $name,
                'size' => filesize($f),
                'mtime' => $mtime,
                'age' => time() - $mtime,
            ];
        }
        usort($rate_limit_files, function($a, $b) { return $a['mtime'] - $b['mtime']; });
    }
}

$other_logs = [];
$log_dir = __DIR__ . '/../logs/';
if (is_dir($log_dir)) {
    $files = scandir($log_dir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || $f === 'ratelimit') continue;
        $path = $log_dir . $f;
        if (is_file($path) && pathinfo($f, PATHINFO_EXTENSION) === 'log') {
            $other_logs[] = [
                'name' => $f,
                'size' => filesize($path),
                'mtime' => filemtime($path),
            ];
        }
    }
}

$log_dir_path = __DIR__ . '/../logs';
$log_dir_writable = is_writable($log_dir_path);
$error_log_writable = !file_exists($error_log_path) || is_writable($error_log_path);

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'logs';
$dashboard_page_title = 'Error Logs';
$lang_page = 'logs.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Logs - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .log-line { font-family: 'Courier New', monospace; font-size: 0.78rem; line-height: 1.5; white-space: pre-wrap; word-break: break-all; padding: 2px 8px; border-bottom: 1px solid #f0f0f0; }
        .log-line:nth-child(even) { background: #fafafa; }
        .log-line:hover { background: #f0f7ff; }
        .log-error { color: #dc3545; }
        .log-warning { color: #856404; }
        .log-stack { color: #6c757d; font-style: italic; padding-left: 20px; }
        .log-time { color: #6c757d; }
        .stat-card { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
    </style>
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">
            <i class="fas fa-clipboard-list me-2" style="color:var(--primary-blue);"></i>Error Logs
        </h1>
        <div class="d-flex gap-2">
            <form method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_rate_limits">
                <button type="submit" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:6px 18px;font-size:0.85rem;font-weight:600;" data-confirm="Clear all rate limits? Users will be able to login immediately." data-confirm-title="Clear Rate Limits" data-confirm-ok="Clear" data-confirm-action="submit">
                    <i class="fas fa-trash-alt me-1"></i>Clear Rate Limits
                </button>
            </form>
            <form method="POST" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="clear_error_log">
                <button type="submit" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:6px 18px;font-size:0.85rem;font-weight:600;" data-confirm="Delete the entire error log file?" data-confirm-title="Delete Error Log" data-confirm-ok="Delete" data-confirm-action="submit">
                    <i class="fas fa-eraser me-1"></i>Clear Error Log
                </button>
            </form>
        </div>
    </div>

    <?php if ($clear_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:12px;border:none;">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($clear_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($clear_err): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:12px;border:none;">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($clear_err) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#e8f4fd;color:#007bff;"><i class="fas fa-file-alt"></i></div>
                    <div>
                        <div class="text-muted small">Error Log Size</div>
                        <div class="fw-bold fs-5"><?= $log_size > 0 ? number_format($log_size / 1024, 1) . ' KB' : 'Empty' ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#fff3cd;color:#856404;"><i class="fas fa-list"></i></div>
                    <div>
                        <div class="text-muted small">Error Entries</div>
                        <div class="fw-bold fs-5"><?= count($log_lines) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#f8d7da;color:#dc3545;"><i class="fas fa-lock"></i></div>
                    <div>
                        <div class="text-muted small">Rate Limit Files</div>
                        <div class="fw-bold fs-5"><?= count($rate_limit_files) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background:#d4edda;color:#28a745;"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="text-muted small">Log Dir Writable</div>
                        <div class="fw-bold fs-5"><?= $log_dir_writable ? 'Yes' : 'No' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Log Viewer -->
    <div class="card mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="m-0 fw-bold" style="color:var(--primary-blue);">
                <i class="fas fa-bug me-2"></i>PHP Error Log
                <span class="text-muted fw-normal" style="font-size:0.8rem;">(most recent <?= count($log_lines) ?> of <?= $log_size > 0 ? '~' . number_format($log_size / 80) . ' lines' : '0' ?>)</span>
            </h6>
            <span class="text-muted small">
                <i class="fas fa-folder me-1"></i>logs/php_errors.log
                <?php if (!$error_log_writable): ?>
                    <span class="text-danger ms-2"><i class="fas fa-exclamation-triangle"></i> Not writable</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="card-body p-0" style="max-height:600px;overflow-y:auto;">
            <?php if (empty($log_lines)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-3x mb-3" style="color:#28a745;"></i>
                    <p class="fw-semibold">No errors logged. Everything looks clean!</p>
                </div>
            <?php else: ?>
                <div style="font-family:'Courier New',monospace;font-size:0.78rem;">
                    <?php foreach ($log_lines as $line): ?>
                        <?php
                        $cls = 'log-line';
                        if (preg_match('/PHP Fatal|PHP Parse|PHP Catchable/', $line)) $cls .= ' log-error';
                        elseif (preg_match('/PHP Warning|PHP Notice/', $line)) $cls .= ' log-warning';
                        elseif (preg_match('/^#\d/', $line)) $cls .= ' log-stack';
                        ?>
                        <div class="<?= $cls ?>"><?= htmlspecialchars($line) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer py-2 text-end text-muted small">
            <i class="fas fa-info-circle me-1"></i>
            Red = Fatal errors, Yellow = Warnings/Notices, Grey italic = Stack traces
        </div>
    </div>

    <!-- Other Log Files -->
    <?php if (!empty($other_logs)): ?>
    <div class="card mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold" style="color:var(--primary-blue);"><i class="fas fa-files me-2"></i>Other Log Files</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Last Modified</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($other_logs as $lf): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($lf['name']) ?></code></td>
                            <td><?= number_format($lf['size'] / 1024, 1) ?> KB</td>
                            <td><?= date('Y-m-d H:i:s', $lf['mtime']) ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete_log">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($lf['name']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:2px 10px;font-size:0.75rem;" data-confirm="Delete <?= htmlspecialchars($lf['name']) ?>?" data-confirm-title="Delete Log" data-confirm-ok="Delete" data-confirm-action="submit">
                                        <i class="fas fa-trash"></i> Delete
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

    <!-- Rate Limit Files -->
    <div class="card mb-4">
        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h6 class="m-0 fw-bold" style="color:var(--primary-blue);">
                <i class="fas fa-lock me-2"></i>Active Rate Limits
                <span class="text-muted fw-normal" style="font-size:0.8rem;">(<?= count($rate_limit_files) ?> locked)</span>
            </h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($rate_limit_files)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-check-circle fa-2x mb-2" style="color:#28a745;"></i>
                    <p>No active rate limits.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Type</th>
                                <th>Age</th>
                                <th>Expires In</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rate_limit_files as $rf): ?>
                            <tr>
                                <td><code style="font-size:0.7rem;"><?= htmlspecialchars($rf['name']) ?></code></td>
                                <td>
                                    <?php if (strpos($rf['name'], 'lockout_') === 0): ?>
                                        <span class="badge bg-danger">Admin Lock</span>
                                    <?php elseif (strpos($rf['name'], 'login_') === 0): ?>
                                        <span class="badge bg-warning text-dark">Login Limit</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">General</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $rf['age'] > 60 ? round($rf['age'] / 60) . ' min' : $rf['age'] . ' sec' ?></td>
                                <td>
                                    <?php $remaining = 900 - $rf['age']; ?>
                                    <?php if ($remaining > 0): ?>
                                        <?= $remaining > 60 ? round($remaining / 60) . ' min' : $remaining . ' sec' ?>
                                    <?php else: ?>
                                        <span class="text-muted">Expired</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        document.querySelectorAll('.alert .btn-close').forEach(function(btn) {
            btn.addEventListener('click', function() { this.closest('.alert').remove(); });
        });
    </script>
</body>
</html>
