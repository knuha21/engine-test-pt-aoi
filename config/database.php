<?php
/**
 * Database Configuration and Path Definitions
 * 
 * File ini berisi konfigurasi database dan definisi path untuk aplikasi
 * testing psikologi. Pastikan untuk mengubah kredensial database sesuai
 * dengan environment Anda.
 * 
 * @package Config
 * @version 1.1
 */

// Pastikan tidak ada akses langsung ke file ini
if (!defined('ROOT_ACCESS')) {
    die('Akses langsung tidak diizinkan.');
}

// Mode debug - set ke false di production
define('DEBUG_MODE', true);

// Error reporting berdasarkan mode debug
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Konstanta database - hanya definisikan jika belum ada
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'alat_test_db');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

// Konstanta path - hanya definisikan jika belum ada
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__DIR__)));
}

if (!defined('CLASS_PATH')) {
    define('CLASS_PATH', BASE_PATH . '/classes/');
}

if (!defined('PAGES_PATH')) {
    define('PAGES_PATH', BASE_PATH . '/pages/');
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', BASE_PATH . '/assets/');
}

if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', BASE_PATH . '/includes/');
}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', BASE_PATH . '/uploads/');
}

if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', BASE_PATH . '/logs/');
}

// URL base untuk links dan assets
if (!defined('BASE_URL')) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Pastikan tidak ada double slash
    $base_url = $protocol . '://' . $host . $script_path;
    $base_url = rtrim($base_url, '/') . '/';
    
    define('BASE_URL', $base_url);
}

if (!defined('ASSETS_URL')) {
    define('ASSETS_URL', BASE_URL . 'assets/');
}

/**
 * Membuat folder yang diperlukan jika belum ada
 */
function createRequiredDirectories() {
    $directories = [
        LOGS_PATH,
        UPLOADS_PATH,
        UPLOADS_PATH . 'profiles/',
        UPLOADS_PATH . 'results/'
    ];
    
    foreach ($directories as $directory) {
        if (!file_exists($directory)) {
            if (mkdir($directory, 0755, true)) {
                error_log("Directory created: " . $directory);
            } else {
                error_log("Failed to create directory: " . $directory);
            }
        }
    }
}

/**
 * Membuat tabel yang diperlukan jika belum ada
 */
function createRequiredTables() {
    try {
        // Gunakan fungsi getDBConnection() dari bootstrap.php
        if (!function_exists('getDBConnection')) {
            error_log("Cannot create tables: getDBConnection function not found");
            return false;
        }
        
        $db = getDBConnection();
        
        if (!$db) {
            error_log("Cannot create tables: Database connection failed");
            return false;
        }
        
        // Buat tabel participants jika belum ada
        $db->exec("
            CREATE TABLE IF NOT EXISTS participants (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin', 'peserta') DEFAULT 'peserta',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Buat tabel test_results jika belum ada
        $db->exec("
            CREATE TABLE IF NOT EXISTS test_results (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                participant_id INT(11) NOT NULL,
                test_type ENUM('TIKI', 'KRAEPELIN', 'PAULI', 'IST') NOT NULL,
                results TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
                INDEX idx_participant_id (participant_id),
                INDEX idx_test_type (test_type),
                INDEX idx_created_at (created_at)
            )
        ");
        
        // Buat tabel tiki_norms jika belum ada
        $db->exec("
            CREATE TABLE IF NOT EXISTS tiki_norms (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                subtest VARCHAR(10) NOT NULL,
                question_number INT(11) NOT NULL,
                correct_answer VARCHAR(1) NOT NULL,
                raw_score INT(11) NOT NULL,
                weighted_score DECIMAL(5,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_norm (subtest, question_number)
            )
        ");
        
        // Buat tabel kraepelin_norms jika belum ada
        $db->exec("
            CREATE TABLE IF NOT EXISTS kraepelin_norms (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                subtest VARCHAR(10) NOT NULL,
                question_number INT(11) NOT NULL,
                correct_answer VARCHAR(1) NOT NULL,
                weighted_score DECIMAL(5,2) NOT NULL,
                interpretation TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_norm (subtest, question_number)
            )
        ");
        
        // Buat tabel tiki_questions jika belum ada
        $db->exec("
            CREATE TABLE IF NOT EXISTS tiki_questions (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                subtest VARCHAR(10) NOT NULL,
                question_number INT(11) NOT NULL,
                question_text TEXT NOT NULL,
                option_a VARCHAR(255) NOT NULL,
                option_b VARCHAR(255) NOT NULL,
                option_c VARCHAR(255) NOT NULL,
                option_d VARCHAR(255) NOT NULL,
                option_e VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_question (subtest, question_number)
            )
        ");
        
        // Insert admin user jika belum ada
        $checkAdmin = $db->query("SELECT id FROM participants WHERE email = 'admin@test.com'");
        if ($checkAdmin->rowCount() === 0) {
            $hashedPassword = sha1('admin123');
            $db->exec("
                INSERT INTO participants (name, email, password, role) 
                VALUES ('Administrator', 'admin@test.com', '$hashedPassword', 'admin')
            ");
            error_log("Admin user created successfully");
        }
        
        error_log("Database tables checked/created successfully");
        return true;
        
    } catch (PDOException $e) {
        error_log("Error creating tables: " . $e->getMessage());
        if (DEBUG_MODE) {
            echo "Error creating tables: " . $e->getMessage();
        }
        return false;
    }
}

// Membuat direktori yang diperlukan
createRequiredDirectories();

// Membuat tabel yang diperlukan
createRequiredTables();

// Debug info
if (DEBUG_MODE) {
    error_log("Database configuration loaded successfully");
    error_log("BASE_PATH: " . BASE_PATH);
    error_log("BASE_URL: " . BASE_URL);
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    $class_file = CLASS_PATH . $class_name . '.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
        
        if (DEBUG_MODE) {
            error_log("Class loaded: " . $class_name);
        }
    } else if (DEBUG_MODE) {
        error_log("Class file not found: " . $class_file);
    }
});

// Handle session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

?>