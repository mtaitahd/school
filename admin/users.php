<?php
require_once __DIR__ . '/../php/includes/security.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}
require_once __DIR__ . '/../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index');
    exit;
}

$all_users = $database->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'users';
$dashboard_page_title = 'Manage Users';
$lang_page = 'users.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Manage Users</h1>
            <div class="d-flex gap-2 flex-wrap">
                <a href="export-learners?mode=all" class="btn btn-success" style="border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                    <i class="fas fa-file-excel me-1"></i> Export All Learners
                </a>
                <a href="export-learners?mode=paid" class="btn btn-info text-white" style="border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                    <i class="fas fa-file-excel me-1"></i> Export Paid Learners
                </a>
                <button type="button" class="btn btn-danger" id="bulkDeleteBtn" onclick="bulkDeleteSelected()" style="border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;display:none;">
                    <i class="fas fa-trash me-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 22px;font-family:'Poppins',sans-serif;font-weight:600;font-size:0.85rem;">
                    <i class="fas fa-user-plus me-2"></i>Add User
                </button>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">User Management</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="cursor:pointer;width:18px;height:18px;"></th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <input type="checkbox" class="user-cb" value="<?php echo (int)$user['user_id']; ?>" onchange="updateSelectedCount()" style="cursor:pointer;width:18px;height:18px;">
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:600;text-transform:lowercase"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><span class="text-primary fw-semibold"><?php echo ucfirst($user['role']); ?></span></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="text-success fw-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick='editUser(<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-danger btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="deleteUser(<?php echo (int)$user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"><i class="fas fa-trash"></i></button>
                                        <button type="button" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="toggleUser(<?php echo (int)$user['user_id']; ?>)"><i class="fas fa-power-off"></i></button>
                                        <button type="button" class="btn btn-info btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;color:#fff;" onclick="unlockUser(<?php echo (int)$user['user_id']; ?>)" title="Clear rate limit"><i class="fas fa-unlock"></i></button>
                                        <button type="button" class="btn btn-secondary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="lockUser(<?php echo (int)$user['user_id']; ?>)" title="Lock login for 15 min"><i class="fas fa-lock"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">Add User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addUserForm">
                    <div class="modal-body" style="padding:20px 24px;">
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Username *</label>
                            <input type="text" name="username" class="form-control" required style="border-radius:10px;">
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">First name *</label>
                                    <input type="text" name="first_name" class="form-control" required style="border-radius:10px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Last name *</label>
                                    <input type="text" name="last_name" class="form-control" required style="border-radius:10px;">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Email</label>
                            <input type="email" name="email" class="form-control" style="border-radius:10px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Role</label>
                            <select name="role" class="form-select" style="border-radius:10px;">
                                <option value="teacher">Teacher</option>
                                <option value="parent">Parent</option>
                                <option value="learner">Learner</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size:0.85rem;color:var(--text-dark);">Password *</label>
                            <input type="password" name="password" class="form-control" required minlength="6" style="border-radius:10px;">
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--primary-blue);border:none;border-radius:50px;padding:8px 24px;font-size:0.85rem;font-weight:600;">Create</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">Edit User</h5>
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
                                <option value="teacher">Teacher</option>
                                <option value="parent">Parent</option>
                                <option value="learner">Learner</option>
                                <option value="admin">Admin</option>
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

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function postUser(action, data) {
            data.append('action', action);
            return fetch('user-actions', { method: 'POST', body: data }).then(r => r.json());
        }
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            postUser('create', new FormData(this)).then(res => {
                if (res.ok) location.reload(); else showToast(res.message);
            });
        });
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            postUser('update', new FormData(this)).then(res => {
                if (res.ok) location.reload(); else showToast(res.message);
            });
        });
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            new bootstrap.Modal('#editUserModal').show();
        }
        function toggleUser(userId) {
            confirmAction('Confirm', 'Toggle user active status?').then(function(c) { if (!c) return;
                const fd = new FormData(); fd.append('user_id', userId);
                postUser('toggle', fd).then(res => { if (res.ok) location.reload(); else showToast(res.message); });
            });
        }
        function deleteUser(userId, name) {
            confirmAction('Delete User', 'Delete user ' + name + '? This cannot be undone.', 'Delete').then(function(c) { if (!c) return;
                const fd = new FormData(); fd.append('user_id', userId);
                postUser('delete', fd).then(res => { if (res.ok) location.reload(); else showToast(res.message); });
            });
        }
        function lockUser(userId) {
            confirmAction('Lock Login', 'Lock this user out of login for 15 minutes?', 'Lock').then(function(c) { if (!c) return;
                const fd = new FormData(); fd.append('user_id', userId);
                postUser('locklogin', fd).then(res => { if (res.ok) location.reload(); else showToast(res.message); });
            });
        }
        function unlockUser(userId) {
            confirmAction('Unlock Login', 'Clear rate limit and unlock login for this user?', 'Unlock').then(function(c) { if (!c) return;
                const fd = new FormData(); fd.append('user_id', userId);
                postUser('unlocklogin', fd).then(res => { if (res.ok) location.reload(); else showToast(res.message); });
            });
        }
        function toggleSelectAll(el) {
            document.querySelectorAll('.user-cb').forEach(function(cb) { cb.checked = el.checked; });
            updateSelectedCount();
        }
        function updateSelectedCount() {
            var cbs = document.querySelectorAll('.user-cb:checked');
            var n = cbs.length;
            document.getElementById('selectedCount').textContent = n;
            document.getElementById('bulkDeleteBtn').style.display = n > 0 ? 'inline-flex' : 'none';
            var allCbs = document.querySelectorAll('.user-cb');
            document.getElementById('selectAll').checked = allCbs.length > 0 && n === allCbs.length;
        }
        function bulkDeleteSelected() {
            var cbs = document.querySelectorAll('.user-cb:checked');
            if (cbs.length === 0) return;
            var ids = [];
            cbs.forEach(function(cb) { ids.push(cb.value); });
            confirmAction('Delete Users', 'Delete ' + ids.length + ' selected user(s)? This cannot be undone.', 'Delete').then(function(c) {
                if (!c) return;
                var fd = new FormData();
                fd.append('action', 'bulk_delete');
                ids.forEach(function(id) { fd.append('user_ids[]', id); });
                postUser('bulk_delete', fd).then(function(res) {
                    if (res.ok) location.reload(); else showToast(res.message);
                });
            });
        }
    </script>
</body>
</html>
