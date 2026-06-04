<?php

require_once __DIR__ . '/session.php';
if (session_status() === PHP_SESSION_NONE) {
    sec_session_start();
}

function sec_env(string $key, mixed $default = null): mixed {
    static $env = null;
    if ($env === null) {
        $env = [];
        $envPath = __DIR__ . '/../../.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v);
                }
            }
        }
    }
    return $env[$key] ?? $default;
}

function sec_is_production(): bool {
    return sec_env('APP_ENV', 'production') === 'production';
}

function sec_error_handler(int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    $logLine = date('Y-m-d H:i:s') . " | Severity: $severity | $message in $file:$line";
    error_log($logLine);
    if (!sec_is_production()) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:8px;margin:4px 0;border-radius:4px;'><strong>PHP Error:</strong> " . htmlspecialchars($message) . " in <code>" . htmlspecialchars($file) . ":$line</code></div>";
    }
    return true;
}

function sec_exception_handler(Throwable $e): void {
    $logLine = date('Y-m-d H:i:s') . " | Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
    error_log($logLine);
    if (sec_is_production()) {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>An unexpected error occurred. Please try again later.</p>";
    } else {
        echo "<h1>Uncaught Exception</h1>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> <code>" . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</code></p>";
    }
    exit;
}

function sec_init_error_handler(): void {
    set_error_handler('sec_error_handler');
    set_exception_handler('sec_exception_handler');
    ini_set('display_errors', sec_is_production() ? '0' : '1');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
}

function sec_send_headers(): void {
    if (headers_sent()) return;

    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

    if (sec_is_production()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    $csp = "default-src 'self'; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; "
         . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'self'; "
         . "form-action 'self'; "
         . "base-uri 'self'; "
         . "object-src 'none'";
    header('Content-Security-Policy: ' . $csp);
}

function sec_block_trace(): void {
    if (!empty($_SERVER['REQUEST_METHOD']) && in_array($_SERVER['REQUEST_METHOD'], ['TRACE', 'TRACK'], true)) {
        http_response_code(405);
        exit;
    }
}

sec_init_error_handler();

// --- Rate Limiting ---

const RATE_LIMIT_DIR = __DIR__ . '/../../logs/ratelimit/';
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_WINDOW = 900;
const GENERAL_MAX_REQUESTS = 120;
const GENERAL_WINDOW = 60;

function sec_rate_limit_check(string $key, int $maxAttempts, int $windowSeconds): bool {
    $dir = RATE_LIMIT_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $file = $dir . md5($key) . '.lock';
    $now = time();

    $data = [];
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        if ($content !== false) {
            $data = json_decode($content, true) ?? [];
        }
    }

    $data = array_filter($data, fn($t) => ($now - $t) <= $windowSeconds);
    $data = array_values($data);

    if (count($data) >= $maxAttempts) {
        return false;
    }

    $data[] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

function sec_login_rate_limit(string $username): bool {
    if (sec_admin_is_locked($username)) {
        return false;
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'login_' . md5($ip) . '_' . md5(strtolower($username));
    return sec_rate_limit_check($key, LOGIN_MAX_ATTEMPTS, LOGIN_WINDOW);
}

function sec_general_rate_limit(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return sec_rate_limit_check("general:$ip", GENERAL_MAX_REQUESTS, GENERAL_WINDOW);
}

function sec_require_rate_limit(): void {
    if (!sec_general_rate_limit()) {
        http_response_code(429);
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'message' => 'Too many requests. Please wait before trying again.']);
        } else {
            echo "<h1>429 Too Many Requests</h1><p>Please wait before making more requests.</p>";
        }
        exit;
    }
}

function sec_clear_login_rate_limit(string $username): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $file = RATE_LIMIT_DIR . 'login_' . md5($ip) . '_' . md5(strtolower($username)) . '.lock';
    if (file_exists($file)) {
        @unlink($file);
    }
}

function sec_clear_login_rate_limit_all(string $username): void {
    $dir = RATE_LIMIT_DIR;
    if (!is_dir($dir)) {
        return;
    }
    $suffix = md5(strtolower($username));
    $files = glob($dir . '*.lock');
    if ($files === false) {
        return;
    }
    foreach ($files as $file) {
        if (str_contains($file, $suffix)) {
            @unlink($file);
        }
    }
}

function sec_admin_lockout_file(string $username): string {
    $dir = RATE_LIMIT_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir . 'lockout_' . md5(strtolower($username)) . '.lock';
}

function sec_admin_is_locked(string $username): bool {
    $file = sec_admin_lockout_file($username);
    if (!file_exists($file)) {
        return false;
    }
    $expires = (int) @file_get_contents($file);
    if (time() - $expires > LOGIN_WINDOW) {
        @unlink($file);
        return false;
    }
    return true;
}

function sec_admin_lock(string $username): void {
    $file = sec_admin_lockout_file($username);
    file_put_contents($file, (string) time(), LOCK_EX);
}

function sec_admin_unlock(string $username): void {
    $file = sec_admin_lockout_file($username);
    if (file_exists($file)) {
        @unlink($file);
    }
}
