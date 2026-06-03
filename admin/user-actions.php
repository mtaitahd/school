<?php
session_start();
require_once '../php/db_connection.php';
require_once '../php/includes/auth.php';

auth_require_role(['admin'], 'index');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
    exit;
}

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
        $username = trim($_POST['username'] ?? '');
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
        if ($database->fetchOne('SELECT user_id FROM users WHERE username = ?', [$username])) {
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

    case 'toggle':
        $user_id = (int) ($_POST['user_id'] ?? 0);
        if ($user_id < 1 || $user_id === $admin_id) {
            json_err('Cannot toggle this user.');
        }
        $database->execute('UPDATE users SET is_active = NOT is_active WHERE user_id = ?', [$user_id]);
        json_ok('User status updated.');

    default:
        json_err('Unknown action.');
}



