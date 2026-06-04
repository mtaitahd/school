<?php
/**
 * Session helpers for role-based access
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';

if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}

function auth_login(array $user): void {
    sec_session_regenerate();

    $_SESSION['user_id'] = (int) $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['profile_image'] = $user['profile_image'] ?? '';
    $_SESSION['email'] = $user['email'] ?? '';
    $_SESSION['_CREATED'] = time();
}

function auth_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_role(): string {
    return $_SESSION['role'] ?? '';
}

function auth_is_logged_in(): bool {
    return auth_user_id() !== null;
}

function auth_require_role(array $roles, string $redirect = '../index.php'): void {
    if (!auth_is_logged_in() || !in_array(auth_role(), $roles, true)) {
        header('Location: ' . $redirect);
        exit;
    }
}

function auth_display_name(): string {
    return trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
}

/**
 * RBAC Permission Check
 * Check if user has specific permission for a resource
 */
function auth_can(string $permission, ?string $resourceType = null, ?int $resourceId = null): bool {
    global $database;
    
    if (!auth_is_logged_in()) {
        return false;
    }
    
    $userId = auth_user_id();
    $role = auth_role();
    
    // Admins have all permissions
    if ($role === 'admin') {
        return true;
    }
    
    // Check specific permission in database
    $sql = "SELECT can_create, can_read, can_update, can_delete 
            FROM user_permissions 
            WHERE user_id = ? AND permission_name = ?";
    $params = [$userId, $permission];
    
    if ($resourceType) {
        $sql .= " AND (resource_type IS NULL OR resource_type = ?)";
        $params[] = $resourceType;
    }
    
    if ($resourceId) {
        $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
        $params[] = $resourceId;
    }
    
    $permissionRecord = $database->fetchOne($sql, $params);
    
    if ($permissionRecord) {
        // Check if any permission is granted
        return $permissionRecord['can_create'] || $permissionRecord['can_read'] || 
               $permissionRecord['can_update'] || $permissionRecord['can_delete'];
    }
    
    // Default: teachers can read, parents can read their children's data
    if ($role === 'teacher' && $permission === 'read') {
        return true;
    }
    
    if ($role === 'parent' && $permission === 'read') {
        return true;
    }
    
    return false;
}

/**
 * RBAC Require Permission
 * Require specific permission or redirect
 */
function auth_require_permission(string $permission, ?string $resourceType = null, ?int $resourceId = null, string $redirect = '../index.php'): void {
    if (!auth_can($permission, $resourceType, $resourceId)) {
        header('Location: ' . $redirect);
        exit;
    }
}

/**
 * RBAC Check Create Permission
 */
function auth_can_create(string $resourceType, ?int $resourceId = null): bool {
    global $database;
    
    if (!auth_is_logged_in()) {
        return false;
    }
    
    $userId = auth_user_id();
    $role = auth_role();
    
    // Admins can create everything
    if ($role === 'admin') {
        return true;
    }
    
    // Teachers can create assignments, classes
    if ($role === 'teacher' && in_array($resourceType, ['assignment', 'class', 'activity'])) {
        return true;
    }
    
    // Check database permission
    $sql = "SELECT can_create FROM user_permissions 
            WHERE user_id = ? AND permission_name = 'create' 
            AND (resource_type IS NULL OR resource_type = ?)";
    $params = [$userId, $resourceType];
    
    if ($resourceId) {
        $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
        $params[] = $resourceId;
    }
    
    $permissionRecord = $database->fetchOne($sql, $params);
    
    return $permissionRecord && $permissionRecord['can_create'];
}

/**
 * RBAC Check Update Permission
 */
function auth_can_update(string $resourceType, ?int $resourceId = null): bool {
    global $database;
    
    if (!auth_is_logged_in()) {
        return false;
    }
    
    $userId = auth_user_id();
    $role = auth_role();
    
    // Admins can update everything
    if ($role === 'admin') {
        return true;
    }
    
    // Teachers can update their own assignments, classes
    if ($role === 'teacher' && in_array($resourceType, ['assignment', 'class'])) {
        return true;
    }
    
    // Check database permission
    $sql = "SELECT can_update FROM user_permissions 
            WHERE user_id = ? AND permission_name = 'update' 
            AND (resource_type IS NULL OR resource_type = ?)";
    $params = [$userId, $resourceType];
    
    if ($resourceId) {
        $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
        $params[] = $resourceId;
    }
    
    $permissionRecord = $database->fetchOne($sql, $params);
    
    return $permissionRecord && $permissionRecord['can_update'];
}

/**
 * RBAC Check Delete Permission
 */
function auth_can_delete(string $resourceType, ?int $resourceId = null): bool {
    global $database;
    
    if (!auth_is_logged_in()) {
        return false;
    }
    
    $userId = auth_user_id();
    $role = auth_role();
    
    // Admins can delete everything
    if ($role === 'admin') {
        return true;
    }
    
    // Teachers can delete their own assignments
    if ($role === 'teacher' && $resourceType === 'assignment') {
        return true;
    }
    
    // Check database permission
    $sql = "SELECT can_delete FROM user_permissions 
            WHERE user_id = ? AND permission_name = 'delete' 
            AND (resource_type IS NULL OR resource_type = ?)";
    $params = [$userId, $resourceType];
    
    if ($resourceId) {
        $sql .= " AND (resource_id IS NULL OR resource_id = ?)";
        $params[] = $resourceId;
    }
    
    $permissionRecord = $database->fetchOne($sql, $params);
    
    return $permissionRecord && $permissionRecord['can_delete'];
}

/**
 * Initialize default permissions for a user
 */
function auth_init_permissions(int $userId, string $role): void {
    global $database;
    
    $permissions = [];
    
    switch ($role) {
        case 'admin':
            $permissions = [
                ['permission_name' => 'create', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
                ['permission_name' => 'read', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
                ['permission_name' => 'update', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
                ['permission_name' => 'delete', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
            ];
            break;
            
        case 'teacher':
            $permissions = [
                ['permission_name' => 'create', 'resource_type' => 'assignment', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 1],
                ['permission_name' => 'create', 'resource_type' => 'class', 'can_create' => 1, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 0],
                ['permission_name' => 'read', 'can_create' => 0, 'can_read' => 1, 'can_update' => 0, 'can_delete' => 0],
                ['permission_name' => 'update', 'resource_type' => 'assignment', 'can_create' => 0, 'can_read' => 1, 'can_update' => 1, 'can_delete' => 0],
                ['permission_name' => 'delete', 'resource_type' => 'assignment', 'can_create' => 0, 'can_read' => 0, 'can_update' => 0, 'can_delete' => 1],
            ];
            break;
            
        case 'parent':
            $permissions = [
                ['permission_name' => 'read', 'can_create' => 0, 'can_read' => 1, 'can_update' => 0, 'can_delete' => 0],
            ];
            break;
            
        case 'learner':
            $permissions = [
                ['permission_name' => 'read', 'can_create' => 0, 'can_read' => 1, 'can_update' => 0, 'can_delete' => 0],
            ];
            break;
    }
    
    foreach ($permissions as $perm) {
        $sql = "INSERT INTO user_permissions (user_id, permission_name, resource_type, can_create, can_read, can_update, can_delete) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $database->execute($sql, [
            $userId,
            $perm['permission_name'],
            $perm['resource_type'] ?? null,
            $perm['can_create'],
            $perm['can_read'],
            $perm['can_update'],
            $perm['can_delete']
        ]);
    }
}
