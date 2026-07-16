<?php
require_once __DIR__ . '/../php/includes/session.php';
require_once __DIR__ . '/../php/includes/security.php';
require_once __DIR__ . '/../php/includes/csrf.php';
require_once __DIR__ . '/../php/db_connection.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/subscription.php';

sec_require_rate_limit();

auth_require_role(['admin'], 'index');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

csrf_require();

$action = $_POST['action'] ?? '';
$admin_id = auth_user_id();

function json_ok(string $message, array $extra = []): void {
    echo json_encode(array_merge(['ok' => true, 'message' => $message], $extra));
    exit;
}

function json_err(string $message): void {
    echo json_encode(['ok' => false, 'message' => $message]);
    exit;
}

switch ($action) {
    case 'create':
        $username = strtolower(trim($_POST['username'] ?? ''));
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'parent';
        $email = trim($_POST['email'] ?? '');

        if ($username === '' || $first_name === '' || $last_name === '' || $password === '') {
            json_err('All required fields must be filled.');
        }
        if (!in_array($role, ['admin', 'teacher', 'parent', 'learner'], true)) {
            json_err('Invalid role.');
        }
        if ($database->fetchOne('SELECT user_id FROM users WHERE LOWER(username) = LOWER(?)', [$username])) {
            json_err('Username already exists.');
        }
        $id = $database->insert(
            'INSERT INTO users (username, email, password, role, first_name, last_name) VALUES (?, ?, ?, ?, ?, ?)',
            [$username, $email ?: null, password_hash($password, PASSWORD_DEFAULT), $role, $first_name, $last_name]
        );
        json_ok('User created successfully.', ['user_id' => $id]);

    case 'update':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        if ($user_id < 1 || $first_name === '' || $last_name === '') {
            json_err('Invalid data.');
        }
        if ($user_id === $admin_id && !$is_active) {
            json_err('You cannot deactivate your own account.');
        }
        $params = [$first_name, $last_name, $email ?: null, $is_active];
        $sql = 'UPDATE users SET first_name = ?, last_name = ?, email = ?, is_active = ?';
        if ($role && in_array($role, ['admin', 'teacher', 'parent', 'learner'], true) && $user_id !== $admin_id) {
            $sql .= ', role = ?';
            $params[] = $role;
        }
        if ($password !== '') {
            $sql .= ', password = ?';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $sql .= ' WHERE user_id = ?';
        $params[] = $user_id;
        $database->execute($sql, $params);
        json_ok('User updated successfully.');

    case 'delete':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id < 1 || $user_id === $admin_id) {
            json_err('Cannot delete this user.');
        }
        $database->execute('DELETE FROM users WHERE user_id = ?', [$user_id]);
        json_ok('User deleted successfully.');

    case 'bulk_delete':
        $user_ids = $_POST['user_ids'] ?? [];
        if (!is_array($user_ids) || empty($user_ids)) {
            json_err('No users selected.');
        }
        $ids = array_map('intval', array_filter($user_ids));
        $ids = array_filter($ids, fn($id) => $id > 0 && $id !== $admin_id);
        if (empty($ids)) {
            json_err('No valid users to delete.');
        }
        $pdo = $database->getPdo();
        $deleted = 0;
        try {
            $pdo->beginTransaction();
            $chunks = array_chunk($ids, 200);
            foreach ($chunks as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id IN ($placeholders)");
                $stmt->execute($chunk);
                $deleted += $stmt->rowCount();
            }
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            json_err('Delete failed: ' . $e->getMessage());
        }
        json_ok("Deleted {$deleted} user(s).");

    case 'toggle':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id < 1 || $user_id === $admin_id) {
            json_err('Cannot toggle this user.');
        }
        $database->execute('UPDATE users SET is_active = NOT is_active WHERE user_id = ?', [$user_id]);
        json_ok('User status updated.');

    case 'locklogin':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id < 1) {
            json_err('Invalid user.');
        }
        $user = $database->fetchOne('SELECT username FROM users WHERE user_id = ?', [$user_id]);
        if (!$user) {
            json_err('User not found.');
        }
        sec_admin_lock($user['username']);
        json_ok('User login locked for 15 minutes.');

    case 'unlocklogin':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id < 1) {
            json_err('Invalid user.');
        }
        $user = $database->fetchOne('SELECT username FROM users WHERE user_id = ?', [$user_id]);
        if (!$user) {
            json_err('User not found.');
        }
        sec_admin_unlock($user['username']);
        sec_clear_login_rate_limit_all($user['username']);
        json_ok('User login unlocked.');

    case 'mark_paid':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        $days = max(1, (int) ($_POST['days'] ?? 30));
        if ($user_id < 1) {
            json_err('Invalid user.');
        }
        $user = $database->fetchOne('SELECT user_id, role FROM users WHERE user_id = ?', [$user_id]);
        if (!$user) {
            json_err('User not found.');
        }
        if ($user['role'] === 'learner') {
            $parent = $database->fetchOne(
                "SELECT COALESCE(psl.parent_id, u.parent_id) AS parent_id
                 FROM users u
                 LEFT JOIN parent_student_links psl ON u.user_id = psl.student_id AND psl.is_active = 1
                 WHERE u.user_id = ? LIMIT 1",
                [$user_id]
            );
            if ($parent && $parent['parent_id']) {
                $parentId = (int) $parent['parent_id'];
            } else {
                // Unlinked learner: activate subscription on their own user_id
                $parentId = $user_id;
            }
        } elseif ($user['role'] === 'parent') {
            $parentId = $user_id;
        } else {
            json_err('Only parents and learners can be marked as paid.');
        }
        sub_add_days($parentId, $days);
        json_ok('Subscription activated for ' . $days . ' days.');

    default:
        json_err('Unknown action.');
}



