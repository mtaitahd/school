<?php

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    private $pdo = null;

    public function __construct() {
        $envPath = __DIR__ . '/../.env';
        $env = [];
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || substr($line, 0, 1) === '#') continue;
                if (strpos($line, '=') !== false) {
                    [$k, $v] = explode('=', $line, 2);
                    $env[trim($k)] = trim($v);
                }
            }
        }

        $this->host = $env['DB_HOST'] ?? 'localhost';
        $this->db_name = $env['DB_NAME'] ?? 'smartmat_kona_hisabati';
        $this->username = $env['DB_USER'] ?? 'smartmat_kona';
        $this->password = $env['DB_PASS'] ?? 'kona2026$';
    }

    private $connected = false;

    private function getConnection(): ?PDO {
        if ($this->pdo === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];
            try {
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
                $this->connected = true;
            } catch (PDOException $e) {
                error_log('DB connect error: ' . $e->getMessage());
                $this->connected = false;
                return null;
            }
        }
        return $this->pdo;
    }

    public function isConnected(): bool {
        return $this->connected;
    }

    public function query(string $sql, array $params = []) {
        $pdo = $this->getConnection();
        if ($pdo === null) {
            throw new RuntimeException('Database connection failed.');
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public function fetchAll(string $sql, array $params = []): array {
        try {
            $stmt = $this->query($sql, $params);
            return $stmt->fetchAll();
        } catch (RuntimeException $e) {
            return [];
        }
    }

    public function execute(string $sql, array $params = []): bool {
        $pdo = $this->getConnection();
        if ($pdo === null) {
            return false;
        }
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function insert(string $sql, array $params = []) {
        $pdo = $this->getConnection();
        if ($pdo === null) {
            return 0;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int) $pdo->lastInsertId();
    }

    public function getPdo(): PDO {
        return $this->getConnection();
    }
}

$database = new Database();
