<?php
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/claim_code_generator.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    csrf_require();

    if ($_POST['action'] === 'link') {
        $learner_id = (int) ($_POST['learner_id'] ?? 0);
        $parent_id = (int) ($_POST['parent_id'] ?? 0);

        if ($learner_id <= 0 || $parent_id <= 0) {
            $message = 'Invalid learner or parent ID.';
            $message_type = 'danger';
        } else {
            $learner = $database->fetchOne("SELECT user_id, role FROM users WHERE user_id = ?", [$learner_id]);
            $parent = $database->fetchOne("SELECT user_id, role FROM users WHERE user_id = ?", [$parent_id]);

            if (!$learner || $learner['role'] !== 'learner') {
                $message = 'Learner not found.';
                $message_type = 'danger';
            } elseif (!$parent || $parent['role'] !== 'parent') {
                $message = 'Parent not found.';
                $message_type = 'danger';
            } else {
                $existing = $database->fetchOne("
                    SELECT * FROM parent_student_links
                    WHERE parent_id = ? AND student_id = ?
                ", [$parent_id, $learner_id]);

                if ($existing) {
                    if (!$existing['is_active']) {
                        $database->execute("UPDATE parent_student_links SET is_active = 1 WHERE link_id = ?", [$existing['link_id']]);
                        $database->execute("UPDATE users SET parent_claimed = 1, parent_id = ? WHERE user_id = ?", [$parent_id, $learner_id]);
                        $message = 'Learner re-linked to parent successfully.';
                        $message_type = 'success';
                    } else {
                        $message = 'This learner is already linked to this parent.';
                        $message_type = 'warning';
                    }
                } else {
                    $database->execute("
                        INSERT INTO parent_student_links (parent_id, student_id, access_code, is_active)
                        VALUES (?, ?, ?, 1)
                    ", [$parent_id, $learner_id, 'ADMIN-LINK-' . $learner_id]);
                    $database->execute("UPDATE users SET parent_claimed = 1, parent_id = ? WHERE user_id = ?", [$parent_id, $learner_id]);
                    $message = 'Learner linked to parent successfully.';
                    $message_type = 'success';
                }
            }
        }
    } elseif ($_POST['action'] === 'link_by_code') {
        $claim_code = trim(strtoupper($_POST['claim_code'] ?? ''));
        $parent_id = (int) ($_POST['parent_id'] ?? 0);

        if ($parent_id <= 0) {
            $message = 'Please select a parent.';
            $message_type = 'danger';
        } elseif (!preg_match('/^KH-[A-Z0-9]{6}$/', $claim_code)) {
            $message = 'Invalid claim code format. Use KH-XXXXXX.';
            $message_type = 'danger';
        } else {
            $codeGenerator = new ClaimCodeGenerator();
            $learner = $codeGenerator->getCodeInfo($claim_code);

            if (!$learner) {
                $message = 'No learner found with that claim code.';
                $message_type = 'danger';
            } else {
                $parent = $database->fetchOne("SELECT user_id, role FROM users WHERE user_id = ?", [$parent_id]);
                if (!$parent || $parent['role'] !== 'parent') {
                    $message = 'Selected parent not found.';
                    $message_type = 'danger';
                } else {
                    $existing = $database->fetchOne("
                        SELECT * FROM parent_student_links
                        WHERE parent_id = ? AND student_id = ?
                    ", [$parent_id, $learner['user_id']]);

                    if ($existing) {
                        if (!$existing['is_active']) {
                            $database->execute("UPDATE parent_student_links SET is_active = 1 WHERE link_id = ?", [$existing['link_id']]);
                            $database->execute("UPDATE users SET parent_claimed = 1, parent_id = ? WHERE user_id = ?", [$parent_id, $learner['user_id']]);
                            $message = 'Learner re-linked to parent successfully.';
                            $message_type = 'success';
                        } else {
                            $message = 'This learner is already linked to this parent.';
                            $message_type = 'warning';
                        }
                    } else {
                        $database->execute("
                            INSERT INTO parent_student_links (parent_id, student_id, access_code, is_active)
                            VALUES (?, ?, ?, 1)
                        ", [$parent_id, $learner['user_id'], $claim_code]);
                        $database->execute("UPDATE users SET parent_claimed = 1, parent_id = ? WHERE user_id = ?", [$parent_id, $learner['user_id']]);
                        $message = 'Learner linked to parent successfully.';
                        $message_type = 'success';
                    }
                }
            }
        }
    } elseif ($_POST['action'] === 'unlink') {
        $learner_id = (int) ($_POST['learner_id'] ?? 0);

        if ($learner_id <= 0) {
            $message = 'Invalid learner ID.';
            $message_type = 'danger';
        } else {
            $database->execute("UPDATE parent_student_links SET is_active = 0 WHERE student_id = ?", [$learner_id]);
            $database->execute("UPDATE users SET parent_claimed = 0, parent_id = NULL WHERE user_id = ?", [$learner_id]);
            $message = 'Learner unlinked from parent.';
            $message_type = 'success';
        }
    }
}

$learners = $database->fetchAll("
    SELECT u.*,
        COALESCE(psl.parent_id, u.parent_id) AS parent_user_id,
        p.first_name AS parent_first,
        p.last_name AS parent_last,
        p.username AS parent_username,
        p.phone AS parent_phone
    FROM users u
    LEFT JOIN parent_student_links psl ON psl.student_id = u.user_id AND psl.is_active = 1
    LEFT JOIN users p ON p.user_id = COALESCE(psl.parent_id, u.parent_id)
    WHERE u.role = 'learner'
    ORDER BY u.created_at DESC
");

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'link-children';
$dashboard_page_title = 'Link Children to Parents';
$lang_page = 'link-children.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Children - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <?php echo csrf_meta(); ?>
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Link Children to Parents</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show py-2 px-3 mb-4 text-center" style="border-radius:10px;font-size:0.9rem;border:none;max-width:700px;margin:0 auto;" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> me-1"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Link by Claim Code -->
        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-green);"><i class="fas fa-key me-2"></i>Quick Link by Claim Code</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="" class="row g-3 align-items-end">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="link_by_code">
                    <input type="hidden" name="parent_id" id="quickParentId" value="">

                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Claim Code</label>
                        <input type="text" class="form-control" name="claim_code" placeholder="KH-XXXXXX" maxlength="9" required
                               style="text-transform:uppercase;letter-spacing:2px;font-size:1.1rem;text-align:center;">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold" style="font-size:0.85rem;">Search Parent</label>
                        <input type="text" class="form-control" id="quickParentSearch" placeholder="Type parent name, username or phone..." autocomplete="off">
                        <div id="quickParentResults" class="list-group mt-2" style="max-height:180px;overflow-y:auto;display:none;"></div>
                        <div id="quickSelectedParent" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;" class="mt-2 p-2 rounded d-flex align-items-center">
                            <i class="fas fa-user-check text-success me-2"></i>
                            <span id="quickSelectedParentName" class="flex-grow-1 fw-semibold small"></span>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearQuickParent()" style="border-radius:50px;padding:2px 8px;font-size:0.75rem;"><i class="fas fa-times"></i></button>
                        </div>
                        <small class="text-muted">Type at least 2 characters to search</small>
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-success w-100" id="quickLinkBtn" disabled style="border:none;border-radius:50px;padding:8px 20px;font-weight:600;">
                            <i class="fas fa-link me-1"></i>Link to Parent
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">All Learners</h6>
                <span class="badge bg-primary"><?php echo count($learners); ?> total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Learner</th>
                                <th>Username</th>
                                <th>Parent</th>
                                <th>Parent Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($learners as $learner): ?>
                            <?php $hasParent = !empty($learner['parent_user_id']); ?>
                            <tr>
                                <td style="font-weight:600;text-transform:lowercase"><?php echo htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($learner['username']); ?></td>
                                <td>
                                    <?php if ($hasParent): ?>
                                        <span class="text-success fw-semibold">
                                            <i class="fas fa-user-check me-1"></i>
                                            <?php echo htmlspecialchars($learner['parent_first'] . ' ' . $learner['parent_last']); ?>
                                            <small class="text-muted">(@<?php echo htmlspecialchars($learner['parent_username']); ?>)</small>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted"><em>No parent linked</em></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($learner['parent_phone'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($learner['is_active']): ?>
                                        <span class="text-success fw-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($hasParent): ?>
                                        <button type="button" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick="unlinkLearner(<?php echo (int)$learner['user_id']; ?>)">
                                            <i class="fas fa-unlink me-1"></i>Unlink
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-primary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick="showLinkModal(<?php echo (int)$learner['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($learner['first_name'] . ' ' . $learner['last_name'])); ?>')">
                                            <i class="fas fa-link me-1"></i>Link Parent
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Link Modal -->
    <div class="modal fade" id="linkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">Link Parent to <span id="learnerName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body" style="padding:20px 24px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="link">
                        <input type="hidden" name="learner_id" id="learnerId" value="">
                        <input type="hidden" name="parent_id" id="parentId" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;">Search Parent</label>
                            <input type="text" class="form-control" id="parentSearch" placeholder="Type parent name, username or phone..." autocomplete="off">
                            <div id="parentResults" class="list-group mt-2" style="max-height:200px;overflow-y:auto;display:none;"></div>
                            <small class="text-muted">Type at least 2 characters to search</small>
                        </div>

                        <div id="selectedParent" style="display:none;" class="p-3 rounded" style="background:#f8f9fa;border:1px solid #e9ecef;">
                            <div class="d-flex align-items-center">
                                <div style="width:40px;height:40px;border-radius:50%;background:var(--primary-green);display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;flex-shrink:0;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="ms-3 flex-grow-1">
                                    <strong id="selectedParentName"></strong>
                                    <small class="d-block text-muted" id="selectedParentDetails"></small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearSelectedParent()" style="border-radius:50px;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="linkSubmitBtn" disabled style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 20px;font-size:0.85rem;font-weight:600;">
                            <i class="fas fa-link me-1"></i>Link to Parent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Unlink Form -->
    <form method="POST" id="unlinkForm" style="display:none;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="unlink">
        <input type="hidden" name="learner_id" id="unlinkLearnerId" value="">
    </form>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        let searchTimeout;

        document.getElementById('parentSearch')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const q = this.value.trim();
            if (q.length < 2) {
                document.getElementById('parentResults').style.display = 'none';
                return;
            }
            searchTimeout = setTimeout(function() {
                fetch('../api/search-parents?q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        const container = document.getElementById('parentResults');
                        container.innerHTML = '';
                        if (data.length === 0) {
                            container.innerHTML = '<div class="list-group-item text-muted">No parents found</div>';
                        } else {
                            data.forEach(function(p) {
                                const div = document.createElement('div');
                                div.className = 'list-group-item list-group-item-action';
                                div.style.cursor = 'pointer';
                                div.innerHTML = '<strong>' + escapeHtml(p.first_name + ' ' + p.last_name) + '</strong> <small class="text-muted">(@' + escapeHtml(p.username) + ')' + (p.phone ? ' - ' + escapeHtml(p.phone) : '') + '</small>';
                                div.addEventListener('click', function() {
                                    selectParent(p.user_id, p.first_name + ' ' + p.last_name, p.username, p.phone);
                                });
                                container.appendChild(div);
                            });
                        }
                        container.style.display = 'block';
                    });
            }, 300);
        });

        function selectParent(id, name, username, phone) {
            document.getElementById('parentId').value = id;
            document.getElementById('selectedParentName').textContent = name;
            document.getElementById('selectedParentDetails').textContent = '@' + username + (phone ? ' - ' + phone : '');
            document.getElementById('selectedParent').style.display = 'block';
            document.getElementById('parentResults').style.display = 'none';
            document.getElementById('parentSearch').value = name;
            document.getElementById('parentSearch').disabled = true;
            document.getElementById('linkSubmitBtn').disabled = false;
        }

        function clearSelectedParent() {
            document.getElementById('parentId').value = '';
            document.getElementById('selectedParent').style.display = 'none';
            document.getElementById('parentSearch').value = '';
            document.getElementById('parentSearch').disabled = false;
            document.getElementById('parentSearch').focus();
            document.getElementById('linkSubmitBtn').disabled = true;
        }

        function showLinkModal(learnerId, learnerName) {
            document.getElementById('learnerId').value = learnerId;
            document.getElementById('learnerName').textContent = learnerName;
            clearSelectedParent();
            new bootstrap.Modal('#linkModal').show();
        }

        function unlinkLearner(learnerId) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Unlink Learner?',
                    text: 'This will remove the parent-child relationship.',
                    confirmButtonText: 'Yes, Unlink',
                    confirmButtonColor: '#dc3545',
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    customClass: { popup: 'rounded-4', confirmButton: 'rounded-pill px-4 fw-bold', cancelButton: 'rounded-pill px-3' }
                }).then(function(r) {
                    if (r.isConfirmed) {
                        document.getElementById('unlinkLearnerId').value = learnerId;
                        document.getElementById('unlinkForm').submit();
                    }
                });
            } else {
                if (confirm('Unlink this learner from their parent?')) {
                    document.getElementById('unlinkLearnerId').value = learnerId;
                    document.getElementById('unlinkForm').submit();
                }
            }
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        // Quick link claim code parent search
        var quickSearchTimeout;
        document.getElementById('quickParentSearch')?.addEventListener('input', function() {
            clearTimeout(quickSearchTimeout);
            var q = this.value.trim();
            if (q.length < 2) {
                document.getElementById('quickParentResults').style.display = 'none';
                return;
            }
            quickSearchTimeout = setTimeout(function() {
                fetch('../api/search-parents?q=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var container = document.getElementById('quickParentResults');
                        container.innerHTML = '';
                        if (data.length === 0) {
                            container.innerHTML = '<div class="list-group-item text-muted">No parents found</div>';
                        } else {
                            data.forEach(function(p) {
                                var div = document.createElement('div');
                                div.className = 'list-group-item list-group-item-action';
                                div.style.cursor = 'pointer';
                                div.innerHTML = '<strong>' + escapeHtml(p.first_name + ' ' + p.last_name) + '</strong> <small class="text-muted">(@' + escapeHtml(p.username) + ')' + (p.phone ? ' - ' + escapeHtml(p.phone) : '') + '</small>';
                                div.addEventListener('click', function() {
                                    document.getElementById('quickParentId').value = p.user_id;
                                    document.getElementById('quickSelectedParentName').textContent = p.first_name + ' ' + p.last_name + ' (@' + p.username + ')';
                                    document.getElementById('quickSelectedParent').style.display = 'flex';
                                    document.getElementById('quickParentResults').style.display = 'none';
                                    document.getElementById('quickParentSearch').value = p.first_name + ' ' + p.last_name;
                                    document.getElementById('quickParentSearch').disabled = true;
                                    document.getElementById('quickLinkBtn').disabled = false;
                                });
                                container.appendChild(div);
                            });
                        }
                        container.style.display = 'block';
                    });
            }, 300);
        });

        function clearQuickParent() {
            document.getElementById('quickParentId').value = '';
            document.getElementById('quickSelectedParent').style.display = 'none';
            document.getElementById('quickParentSearch').value = '';
            document.getElementById('quickParentSearch').disabled = false;
            document.getElementById('quickParentSearch').focus();
            document.getElementById('quickLinkBtn').disabled = true;
        }
    </script>
</body>
</html>
