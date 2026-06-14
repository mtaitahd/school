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

$parents = $database->fetchAll("
    SELECT u.*,
        (SELECT COUNT(*) FROM parent_student_links psl WHERE psl.parent_id = u.user_id AND psl.is_active = 1) AS linked_children,
        (SELECT COUNT(*) FROM users c WHERE c.parent_id = u.user_id AND c.role = 'learner') AS legacy_children
    FROM users u
    WHERE u.role = 'parent'
    ORDER BY u.created_at DESC
");

require_once __DIR__ . '/../php/includes/lang.php';
$base_path = '../';
$dashboard_role = 'admin';
$sidebar_active = 'parents';
$dashboard_page_title = 'Manage Parents';
$lang_page = 'parents.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - Kona Ya Hisabati</title>
    <link rel="icon" type="image/png" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <?php echo csrf_meta(); ?>
</head>
<body class="dashboard-body"><?php include '../php/includes/dashboard-start.php'; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h1 class="h3 mb-0 text-gray-800" style="font-family:'Poppins',sans-serif;font-weight:700;">Manage Parents</h1>
        </div>

        <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold" style="color:var(--primary-blue);">Parents</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Children</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($parents as $parent): ?>
                            <?php $totalChildren = (int) $parent['linked_children'] + (int) $parent['legacy_children']; ?>
                            <tr>
                                <td style="font-weight:600;"><?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($parent['username']); ?></td>
                                <td><?php echo htmlspecialchars($parent['email'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($parent['phone'] ?? '-'); ?></td>
                                <td><span class="badge bg-info"><?php echo $totalChildren; ?></span></td>
                                <td>
                                    <?php if ($parent['is_active']): ?>
                                        <span class="text-success fw-semibold">Active</span>
                                    <?php else: ?>
                                        <span class="text-danger fw-semibold">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-primary btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;" onclick="viewChildren(<?php echo (int)$parent['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($parent['first_name'] . ' ' . $parent['last_name'])); ?>')"><i class="fas fa-eye me-1"></i>View</button>
                                    <button type="button" class="btn btn-success btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="markPaid(<?php echo (int)$parent['user_id']; ?>)"><i class="fas fa-check-circle me-1"></i>Mark Paid</button>
                                    <button type="button" class="btn btn-warning btn-sm" style="border:none;border-radius:50px;padding:4px 12px;font-size:0.8rem;margin-left:4px;" onclick="toggleUser(<?php echo (int)$parent['user_id']; ?>)"><i class="fas fa-power-off"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <!-- View Children Modal -->
    <div class="modal fade" id="viewChildrenModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
                <div class="modal-header" style="border-bottom:1px solid #f0f0f0;padding:20px 24px 16px;">
                    <h5 class="modal-title" style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--navbar-dark);">Children of <span id="parentName"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:20px 24px;" id="childrenList">
                    <p class="text-muted">Loading...</p>
                </div>
                <div class="modal-footer" style="border-top:1px solid #f0f0f0;padding:16px 24px 20px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius:50px;padding:8px 20px;font-size:0.85rem;">Close</button>
                </div>
            </div>
        </div>
    </div>

<?php include '../php/includes/dashboard-end.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/main.js"></script>
    <script src="../js/dashboard.js"></script>
    <script>
        function viewChildren(parentId, parentName) {
            document.getElementById('parentName').textContent = parentName;
            document.getElementById('childrenList').innerHTML = '<p class="text-muted">Loading...</p>';
            new bootstrap.Modal('#viewChildrenModal').show();

            fetch('../api/parent-children?parent_id=' + parentId)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('childrenList').innerHTML = '<p class="text-danger">' + data.error + '</p>';
                        return;
                    }
                    if (data.length === 0) {
                        document.getElementById('childrenList').innerHTML = '<p class="text-muted">No children linked to this parent.</p>';
                        return;
                    }
                    let html = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Username</th><th>Name</th><th>Status</th></tr></thead><tbody>';
                    data.forEach(function(c) {
                        let status = c.is_active == 1 ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>';
                        html += '<tr><td>' + escapeHtml(c.username) + '</td><td>' + escapeHtml(c.first_name + ' ' + c.last_name) + '</td><td>' + status + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                    document.getElementById('childrenList').innerHTML = html;
                })
                .catch(function() {
                    document.getElementById('childrenList').innerHTML = '<p class="text-danger">Failed to load children.</p>';
                });
        }

        function markPaid(parentId) {
            if (!confirm('Activate 30-day subscription for this parent?')) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            fetch('user-actions', { method: 'POST', body: new URLSearchParams({ action: 'mark_paid', user_id: parentId, days: 30, _csrf_token: token }), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
                .then(r => r.json())
                .then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }

        function toggleUser(userId) {
            if (!confirm('Toggle parent active status?')) return;
            const fd = new FormData(); fd.append('user_id', userId);
            fetch('user-actions', { method: 'POST', body: new URLSearchParams({ action: 'toggle', user_id: userId }) })
                .then(r => r.json())
                .then(res => { if (res.ok) location.reload(); else alert(res.message); });
        }

        function escapeHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    </script>
</body>
</html>
