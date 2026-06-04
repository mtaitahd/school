<?php

require_once __DIR__ . '/session.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

function csrf_meta(): string {
    return '<meta name="csrf-token" content="' . csrf_token() . '">';
}

function csrf_verify(?string $token = null): bool {
    if ($token === null) {
        $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function csrf_require(): void {
    if (!csrf_verify()) {
        http_response_code(419);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'CSRF token validation failed. Please refresh the page and try again.']);
        } else {
            $_SESSION['_csrf_error'] = 'Session expired. Please submit the form again.';
            $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
            header('Location: ' . $referer);
        }
        exit;
    }
}

function csrf_regenerate(): void {
    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
}
