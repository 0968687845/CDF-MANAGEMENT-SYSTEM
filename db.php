<?php
// Load config if not already loaded
if (!defined('DB_HOST')) {
    require_once 'config.php';
}

// Define fallback constants if they're still not defined
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'cdf_database');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Check if class already exists before declaring it
if (!class_exists('Database')) {
    class Database {
        private $host;
        private $db_name;
        private $username;
        private $password;
        public $conn;

        public function __construct() {
            $this->host = DB_HOST;
            $this->db_name = DB_NAME;
            $this->username = DB_USER;
            $this->password = DB_PASS;
        }

        public function getConnection() {
            $this->conn = null;

            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                    $this->username, 
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $exception) {
                error_log("Connection error: " . $exception->getMessage());
                // Don't die here, let the application handle it gracefully
                return null;
            }

            return $this->conn;
        }
        
        // Helper function to execute prepared statements
        public function executeQuery($sql, $params = []) {
            if (!$this->conn) {
                error_log("Database connection is not established");
                return false;
            }
            
            try {
                $stmt = $this->conn->prepare($sql);
                $stmt->execute($params);
                return $stmt;
            } catch(PDOException $e) {
                error_log("Query error: " . $e->getMessage() . " SQL: " . $sql);
                return false;
            }
        }
    }
}

// Create database instance if it doesn't exist
if (!isset($database)) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Set global database connection for backward compatibility
    if (!$db) {
        // Handle connection error gracefully
        error_log("Failed to establish database connection");
    }
}
?>