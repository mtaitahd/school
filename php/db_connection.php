<?php
/**
 * KONA YA HISABATI - Database Connection
 * Secure PDO Database Connection
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'kona_hisabati';
    private $username = 'root';
    private $password = '';
    private $charset = 'utf8mb4';
    private $pdo;

    /**
     * Get PDO instance
     */
    public function getConnection() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false
                ];
                
                $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                error_log("Database Connection Error: " . $e->getMessage());
                die("Database connection failed. Please try again later.");
            }
        }
        return $this->pdo;
    }

    /**
     * Execute a prepared statement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }

    /**
     * Insert data and return last insert ID
     */
    public function insert($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $this->getConnection()->lastInsertId() : false;
    }

    /**
     * Update or delete data
     */
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }

    /**
     * Close connection
     */
    public function close() {
        $this->pdo = null;
    }
}

// Create global database instance
$database = new Database();
