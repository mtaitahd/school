<?php
session_start();
require_once '../php/db_connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$all_users = $database->fetchAll("SELECT * FROM users ORDER BY created_at DESC");

require_once '../php/includes/lang.php';
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
<body class="dashboard-body">
<?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-30 gap-3">
            <h1 class="activity-title mb-0">Manage Users</h1>
            <button type="button" class="btn-child btn-child-primary" onclick="openModal('addUserModal')">
                <i class="fas fa-user-plus me-2"></i>Add User
            </button>
        </div>
        
        <div class="dashboard-card mb-30">
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon" style="background: var(--primary-blue);"><i class="fas fa-users-cog"></i></div>
                <h3 class="dashboard-card-title">User Management</h3>
            </div>
            <div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--background-light);">
                            <th style="padding: 15px; text-align: left;">Name</th>
                            <th style="padding: 15px; text-align: left;">Username</th>
                            <th style="padding: 15px; text-align: left;">Role</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_users as $user): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 15px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td style="padding: 15px;"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td style="padding: 15px;">
                                <span style="background: var(--primary-blue); color: white; padding: 5px 15px; border-radius: 15px; font-size: 0.9rem;">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td style="padding: 15px;">
                                <?php if ($user['is_active']): ?>
                                    <span style="color: var(--primary-green);"><i class="fas fa-check-circle"></i> Active</span>
                                <?php else: ?>
                                    <span style="color: var(--primary-red);"><i class="fas fa-times-circle"></i> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px;">
                                <button type="button" class="btn-child btn-child-primary" style="min-height:35px;padding:0 10px;font-size:0.8rem;" onclick='editUser(<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'><i class="fas fa-edit"></i></button>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <button type="button" class="btn-child btn-child-red" style="min-height:35px;padding:0 10px;font-size:0.8rem;margin-left:4px;" onclick="deleteUser(<?php echo (int)$user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')"><i class="fas fa-trash"></i></button>
                                    <button type="button" class="btn-child btn-child-yellow" style="min-height:35px;padding:0 10px;font-size:0.8rem;margin-left:4px;" onclick="toggleUser(<?php echo (int)$user['user_id']; ?>)"><i class="fas fa-power-off"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <div id="addUserModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog">
            <div class="kona-modal-header"><h3>Add User</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
            <form id="addUserForm">
                <div class="kona-modal-body">
                    <div class="form-group-child"><label class="form-label-child">Username *</label><input type="text" name="username" class="form-control-child" required></div>
                    <div class="row-child">
                        <div class="col-child-2"><div class="form-group-child"><label class="form-label-child">First name *</label><input type="text" name="first_name" class="form-control-child" required></div></div>
                        <div class="col-child-2"><div class="form-group-child"><label class="form-label-child">Last name *</label><input type="text" name="last_name" class="form-control-child" required></div></div>
                    </div>
                    <div class="form-group-child"><label class="form-label-child">Email</label><input type="email" name="email" class="form-control-child"></div>
                    <div class="form-group-child"><label class="form-label-child">Role</label>
                        <select name="role" class="form-control-child"><option value="teacher">Teacher</option><option value="parent">Parent</option><option value="learner">Learner</option><option value="admin">Admin</option></select>
                    </div>
                    <div class="form-group-child"><label class="form-label-child">Password *</label><input type="password" name="password" class="form-control-child" required minlength="6"></div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editUserModal" class="kona-modal-overlay" aria-hidden="true">
        <div class="kona-modal" role="dialog">
            <div class="kona-modal-header"><h3>Edit User</h3><button type="button" class="kona-modal-close" data-modal-close>&times;</button></div>
            <form id="editUserForm">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="kona-modal-body">
                    <div class="form-group-child"><label class="form-label-child">Username</label><input type="text" id="edit_username" class="form-control-child" disabled></div>
                    <div class="row-child">
                        <div class="col-child-2"><div class="form-group-child"><label class="form-label-child">First name *</label><input type="text" name="first_name" id="edit_first_name" class="form-control-child" required></div></div>
                        <div class="col-child-2"><div class="form-group-child"><label class="form-label-child">Last name *</label><input type="text" name="last_name" id="edit_last_name" class="form-control-child" required></div></div>
                    </div>
                    <div class="form-group-child"><label class="form-label-child">Email</label><input type="email" name="email" id="edit_email" class="form-control-child"></div>
                    <div class="form-group-child"><label class="form-label-child">Role</label><select name="role" id="edit_role" class="form-control-child"><option value="teacher">Teacher</option><option value="parent">Parent</option><option value="learner">Learner</option><option value="admin">Admin</option></select></div>
                    <div class="form-group-child"><label class="form-label-child">New password (leave blank to keep)</label><input type="password" name="password" class="form-control-child" minlength="6"></div>
                    <div class="form-group-child"><label><input type="checkbox" name="is_active" id="edit_is_active"> Active</label></div>
                </div>
                <div class="kona-modal-footer">
                    <button type="button" class="btn-child btn-child-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn-child btn-child-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/modals.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function postUser(action, data) {
            data.append('action', action);
            return fetch('user-actions.php', { method: 'POST', body: data }).then(r => r.json());
        }
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            postUser('create', new FormData(this)).then(res => {
                if (res.ok) location.reload(); else alert(res.message);
            });
        });
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            postUser('update', new FormData(this)).then(res => {
                if (res.ok) location.reload(); else alert(res.message);
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
            openModal('editUserModal');
        }
        function toggleUser(userId) {
            if (!confirm('Toggle user active status?')) return;
            const fd = new FormData(); fd.append('user_id', userId);
            postUser('toggle', fd).then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }
        function deleteUser(userId, name) {
            if (!confirm('Delete user ' + name + '? This cannot be undone.')) return;
            const fd = new FormData(); fd.append('user_id', userId);
            postUser('delete', fd).then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }
    </script>
</body>
</html>
