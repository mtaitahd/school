<?php
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once __DIR__ . '/../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$learners = $database->fetchAll("
    SELECT u.*,
        COALESCE(psl.parent_id, u.parent_id) AS parent_user_id,
        p.first_name AS parent_first,
        p.last_name AS parent_last,
        p.username AS parent_username
    FROM users u
    LEFT JOIN parent_student_links psl ON psl.student_id = u.user_id AND psl.is_active = 1
    LEFT JOIN users p ON p.user_id = COALESCE(psl.parent_id, u.parent_id)
    WHERE u.role = 'learner'
    ORDER BY u.created_at DESC
");

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'learners';
$dashboard_page_title = 'Manage Learners';
$lang_page = 'learners.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Learners - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <?php echo csrf_meta(); ?>
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Manage Learners</h1>
            <div class="d-flex gap-2">
                <a href="export-learners?mode=all" class="btn btn-success" style="border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                    <i class="fas fa-file-excel me-1"></i> Export All
                </a>
                <a href="export-learners?mode=paid" class="btn btn-info text-white" style="border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                    <i class="fas fa-file-excel me-1"></i> Export Paid
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Learners</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Parent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($learners as $learner): ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($learner['first_name'] . ' ' . $learner['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($learner['username']); ?></td>
                                <td>
                                    <?php if ($learner['parent_user_id']): ?>
                                        <span class="text-primary"><?php echo htmlspecialchars($learner['parent_first'] . ' ' . $learner['parent_last']); ?></span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($learner['parent_username']); ?></small>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">No parent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($learner['is_active']): ?>
                                        <span class="text-success fw-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick='editUser(<?php echo json_encode($learner, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                    <button type="button" class="btn btn-success btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="markPaidLearner(<?php echo (int)$learner['user_id']; ?>)"><i class="fas fa-check-circle me-1"></i>Mark Paid</button>
                                    <button type="button" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="toggleUser(<?php echo (int)$learner['user_id']; ?>)"><i class="fas fa-power-off"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function postUser(action, data) {
            data.append('action', action);
            return fetch('user-actions', { method: 'POST', body: data }).then(r => r.json());
        }
        function editUser(user) {
            const modal = document.getElementById('editUserModal');
            if (!modal) {
                alert('Edit modal not available. Use the Manage Users page to edit users.');
                return;
            }
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            new bootstrap.Modal('#editUserModal').show();
        }
        function markPaidLearner(learnerId) {
            if (!confirm('Activate 30-day subscription for this learner\'s parent?')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch('user-actions', { method: 'POST', body: new URLSearchParams({ action: 'mark_paid', user_id: learnerId, days: 30, _csrf_token: token }), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
                .then(r => r.json())
                .then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }

        function toggleUser(userId) {
            if (!confirm('Toggle learner active status?')) return;
            const fd = new FormData(); fd.append('user_id', userId);
            postUser('toggle', fd).then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }
    </script>

    <!-- Edit User Modal (reuse from users.php functionality) -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">Edit Learner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Username</label>
                            <input type="text" id="edit_username" class="form-control" disabled style="border-radius:10px;">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">First name *</label>
                                    <input type="text" name="first_name" id="edit_first_name" class="form-control" required style="border-radius:10px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Last name *</label>
                                    <input type="text" name="last_name" id="edit_last_name" class="form-control" required style="border-radius:10px;">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Role</label>
                            <select name="role" id="edit_role" class="form-select" style="border-radius:10px;">
                                <option value="learner">Learner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">New password (leave blank to keep)</label>
                            <input type="password" name="password" class="form-control" minlength="6" style="border-radius:10px;">
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input">
                            <label class="form-check-label fw-semibold" for="edit_is_active" style="font-size:0.85rem;color:var(--text-dark);">Active</label>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            postUser('update', new FormData(this)).then(res => {
                if (res.ok) location.reload(); else alert(res.message);
            });
        });
    </script>
</body>
</html>
