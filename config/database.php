<?php
/**
 * Database Configuration
 * Supports both local development and Vercel deployment
 */
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Use environment variables first (for Vercel), fall back to hardcoded values
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'doublelist_clone';
        $this->username = getenv('DB_USER') ?: 'doublelist_clone';
        $this->password = getenv('DB_PASSWORD') ?: 'g4iY?vMI&9on5jyy';
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            echo "Connection Error: Database connection failed. Check your configuration.";
        }
        
        return $this->conn;
    }
}
?>
