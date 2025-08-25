<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;
    
    public function __construct() {
        // Tentukan path absolut ke config file
        $configPath = __DIR__ . '/../config/database.php';
        
        if (file_exists($configPath)) {
            require_once $configPath;
        } else {
            // Fallback: define constants manually jika file tidak ditemukan
            define('DB_HOST', 'localhost');
            define('DB_NAME', 'alat_test_db');
            define('DB_USER', 'root');
            define('DB_PASS', '');
        }
        
        $this->host = DB_HOST;
        $this->db_name = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
    }
    
    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Koneksi database gagal: " . $exception->getMessage();
            // Untuk debugging, tampilkan detail koneksi
            echo "<br>Host: " . $this->host;
            echo "<br>Database: " . $this->db_name;
            echo "<br>User: " . $this->username;
        }
        return $this->conn;
    }
}
?>