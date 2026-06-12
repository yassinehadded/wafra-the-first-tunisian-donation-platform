<?php
/**
 * Database Connection Class
 * Singleton pattern for database connection
 */
class Database {
    private static $instance = null;
    private $pdo = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            // Handle empty password (XAMPP default - no password)
            if (empty(DB_PASS) || DB_PASS === '') {
                $this->pdo = new PDO($dsn, DB_USER, null, $options);
            } else {
                $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    /**
     * Get database connection instance (Singleton)
     * @return PDO
     */
    public static function connect() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance->pdo;
    }
    
    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization of the instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}


