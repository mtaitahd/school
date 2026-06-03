<?php
/**
 * Minimal Database wrapper — only implements what's needed for admin login.
 * Provides `query()` and `fetchOne()` used by admin pages.
 */
class Database {
	private $host = 'localhost';
	private $db_name = 'smartmat_kona_hisabati';
	private $username = 'smartmat_kona';
	private $password = 'kona2026$';
	private $charset = 'utf8mb4';
	private $pdo;

	private function getConnection() {
		if ($this->pdo === null) {
			try {
				$dsn = "mysql:host={$this->host};dbname={$this->db_name};charset={$this->charset}";
				$options = [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
					PDO::ATTR_EMULATE_PREPARES => false,
				];
				$this->pdo = new PDO($dsn, $this->username, $this->password, $options);
			} catch (PDOException $e) {
				error_log('DB connect error: '.$e->getMessage());
				$this->pdo = null;
			}
		}
		return $this->pdo;
	}

	public function query($sql, $params = []) {
		$pdo = $this->getConnection();
		if (!$pdo) return false;
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			return $stmt;
		} catch (PDOException $e) {
			error_log('DB query error: '.$e->getMessage());
			return false;
		}
	}

	public function fetchOne($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt ? $stmt->fetch() : null;
	}

	public function fetchAll($sql, $params = []) {
		$stmt = $this->query($sql, $params);
		return $stmt ? $stmt->fetchAll() : [];
	}

	public function execute($sql, $params = []) {
		$pdo = $this->getConnection();
		if (!$pdo) return false;
		try {
			$stmt = $pdo->prepare($sql);
			return $stmt->execute($params);
		} catch (PDOException $e) {
			error_log('DB execute error: '.$e->getMessage());
			return false;
		}
	}

	public function insert($sql, $params = []) {
		$pdo = $this->getConnection();
		if (!$pdo) return false;
		try {
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			return $pdo->lastInsertId();
		} catch (PDOException $e) {
			error_log('DB insert error: '.$e->getMessage());
			return false;
		}
	}

	public function getPdo() {
		return $this->getConnection();
	}
}

// Provide global `$database` for existing code.
$database = new Database();

