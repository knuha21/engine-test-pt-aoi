<?php
/**
 * Database Configuration and Path Definitions
 * 
 * File ini berisi konfigurasi database dan definisi path untuk aplikasi
 * testing psikologi. Pastikan untuk mengubah kredensial database sesuai
 * dengan environment Anda.
 * 
 * @package Config
 * @version 1.2
 */

// Pastikan tidak ada akses langsung ke file ini
if (!defined('ROOT_ACCESS')) {
    die('Akses langsung tidak diizinkan.');
}

// Mode debug - set ke true untuk development
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
        
        // Buat tabel tiki_conversion untuk konversi skor ke IQ
        $db->exec("
            CREATE TABLE IF NOT EXISTS tiki_conversion (
                id INT(11) PRIMARY KEY AUTO_INCREMENT,
                total_score INT(11) NOT NULL,
                iq_score INT(11) NOT NULL,
                category VARCHAR(50) NULL,
                UNIQUE KEY unique_conversion (total_score)
            )
        ");
        
        // Insert data konversi skor ke IQ
        $conversionData = [
            [96, 145, 'Sangat Superior'], [95, 144, 'Sangat Superior'],
            [94, 143, 'Sangat Superior'], [93, 142, 'Sangat Superior'],
            [92, 140, 'Sangat Superior'], [91, 139, 'Sangat Superior'],
            [90, 138, 'Sangat Superior'], [89, 137, 'Sangat Superior'],
            [88, 135, 'Sangat Superior'], [87, 134, 'Sangat Superior'],
            [86, 133, 'Sangat Superior'], [85, 132, 'Sangat Superior'],
            [84, 131, 'Sangat Superior'], [83, 130, 'Sangat Superior'],
            [82, 129, 'Superior'], [81, 127, 'Superior'],
            [80, 126, 'Superior'], [79, 125, 'Superior'],
            [78, 124, 'Superior'], [77, 123, 'Superior'],
            [76, 122, 'Superior'], [75, 120, 'Superior'],
            [74, 119, 'Superior'], [73, 118, 'Superior'],
            [72, 117, 'Superior'], [71, 115, 'Superior'],
            [70, 114, 'Di Atas Rata-rata'], [69, 113, 'Di Atas Rata-rata'],
            [68, 112, 'Di Atas Rata-rata'], [67, 110, 'Di Atas Rata-rata'],
            [66, 109, 'Di Atas Rata-rata'], [65, 108, 'Di Atas Rata-rata'],
            [64, 107, 'Di Atas Rata-rata'], [63, 106, 'Di Atas Rata-rata'],
            [62, 105, 'Di Atas Rata-rata'], [61, 104, 'Di Atas Rata-rata'],
            [60, 103, 'Di Atas Rata-rata'], [59, 102, 'Di Atas Rata-rata'],
            [58, 100, 'Rata-rata'], [57, 99, 'Rata-rata'],
            [56, 98, 'Rata-rata'], [55, 97, 'Rata-rata'],
            [54, 96, 'Rata-rata'], [53, 94, 'Rata-rata'],
            [52, 93, 'Rata-rata'], [51, 92, 'Rata-rata'],
            [50, 91, 'Rata-rata'], [49, 90, 'Rata-rata'],
            [48, 89, 'Rata-rata'], [47, 87, 'Rata-rata'],
            [46, 86, 'Rata-rata'], [45, 85, 'Rata-rata'],
            [44, 84, 'Rata-rata'], [43, 83, 'Rata-rata'],
            [42, 81, 'Rata-rata'], [41, 80, 'Rata-rata'],
            [40, 79, 'Rata-rata'], [39, 78, 'Rata-rata'],
            [38, 77, 'Rata-rata'], [37, 75, 'Rata-rata'],
            [36, 74, 'Rata-rata'], [35, 73, 'Rata-rata'],
            [34, 72, 'Di Bawah Rata-rata'], [33, 71, 'Di Bawah Rata-rata'],
            [32, 70, 'Di Bawah Rata-rata'], [31, 68, 'Di Bawah Rata-rata'],
            [30, 67, 'Di Bawah Rata-rata'], [29, 66, 'Di Bawah Rata-rata'],
            [28, 65, 'Di Bawah Rata-rata'], [27, 64, 'Di Bawah Rata-rata'],
            [26, 62, 'Di Bawah Rata-rata'], [25, 61, 'Di Bawah Rata-rata'],
            [24, 60, 'Di Bawah Rata-rata'], [23, 58, 'Di Bawah Rata-rata'],
            [22, 57, 'Di Bawah Rata-rata'], [21, 56, 'Di Bawah Rata-rata']
        ];
        
        $stmt = $db->prepare("
            INSERT INTO tiki_conversion (total_score, iq_score, category) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE iq_score = VALUES(iq_score), category = VALUES(category)
        ");
        
        foreach ($conversionData as $data) {
            $stmt->execute($data);
        }
        
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