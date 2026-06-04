<?php

function sec_session_start(): void {
    $lifetime = 7200;

    $envPath = __DIR__ . '/../../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_starts_with($line, 'SESSION_LIFETIME=')) {
                $val = (int) substr($line, strlen('SESSION_LIFETIME='));
                if ($val > 0) $lifetime = $val;
                break;
            }
        }
    }

    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', (string) $lifetime);
        ini_set('session.cookie_lifetime', '0');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    if (!isset($_SESSION['_CREATED'])) {
        $_SESSION['_CREATED'] = time();
    } elseif (time() - $_SESSION['_CREATED'] > $lifetime) {
        session_destroy();
        session_start();
    }
}

function sec_session_regenerate(): void {
    if (session_status() === PHP_SESSION_NONE) {
        sec_session_start();
    }
    session_regenerate_id(true);
    $_SESSION['_CREATED'] = time();
}

function sec_session_destroy(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    session_destroy();
}
