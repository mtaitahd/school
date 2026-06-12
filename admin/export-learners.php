<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';

auth_require_role(['admin'], 'index');

$mode = $_GET['mode'] ?? 'all';
if (!in_array($mode, ['all', 'paid'], true)) {
    $mode = 'all';
}

// Build query
$columns = "u.user_id, u.first_name, u.last_name, u.username, u.email, u.is_active, u.created_at,
            COALESCE(p.first_name, lp.first_name) AS parent_first,
            COALESCE(p.last_name, lp.last_name) AS parent_last,
            COALESCE(p.phone, lp.phone) AS parent_phone";

$joins  = "FROM users u
           LEFT JOIN parent_student_links psl ON u.user_id = psl.student_id AND psl.is_active = 1
           LEFT JOIN users p ON psl.parent_id = p.user_id
           LEFT JOIN users lp ON u.parent_id = lp.user_id";

$where  = "WHERE u.role = 'learner'";

if ($mode === 'paid') {
    $joins .= " LEFT JOIN subscriptions s ON s.parent_id = COALESCE(psl.parent_id, u.parent_id) AND s.status = 'active'";
    $where .= " AND s.id IS NOT NULL";
}

$order = "ORDER BY u.last_name, u.first_name";

$learners = $database->fetchAll("SELECT $columns $joins $where $order");

// Build filename
$filename = $mode === 'all' ? 'wanafunzi_wote' : 'wanafunzi_waliolipa';
$filename .= '_' . date('Y-m-d') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Active</th>
                <th>Parent First Name</th>
                <th>Parent Last Name</th>
                <th>Parent Phone</th>
                <th>Registered Date</th>
            </tr>
        </thead>
        <tbody>
<?php foreach ($learners as $i => $l): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($l['first_name']) ?></td>
                <td><?= htmlspecialchars($l['last_name']) ?></td>
                <td><?= htmlspecialchars($l['username']) ?></td>
                <td><?= htmlspecialchars($l['email'] ?? '-') ?></td>
                <td><?= $l['is_active'] ? 'Active' : 'Inactive' ?></td>
                <td><?= htmlspecialchars($l['parent_first'] ?? '-') ?></td>
                <td><?= htmlspecialchars($l['parent_last'] ?? '-') ?></td>
                <td><?= htmlspecialchars($l['parent_phone'] ?? '-') ?></td>
                <td><?= date('d/m/Y', strtotime($l['created_at'])) ?></td>
            </tr>
<?php endforeach; ?>
<?php if (empty($learners)): ?>
            <tr><td colspan="10" align="center">No learners found</td></tr>
<?php endif; ?>
        </tbody>
    </table>
</body>
</html>
