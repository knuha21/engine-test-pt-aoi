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
 * Membuat koneksi database menggunakan PDO
 * 
 * @return PDO|null Objek PDO atau null jika gagal
 */

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

// Membuat direktori yang diperlukan
createRequiredDirectories();

// Debug info
if (DEBUG_MODE) {
    error_log("Database configuration loaded successfully");
    error_log("BASE_PATH: " . BASE_PATH);
    error_log("BASE_URL: " . BASE_URL);
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    $class_file = CLASS_PATH . $class_name . '.class.php';
    
    if (file_exists($class_file)) {
        require_once $class_file;
        
        if (DEBUG_MODE) {
            error_log("Class loaded: " . $class_name);
        }
    } else if (DEBUG_MODE) {
        error_log("Class file not found: " . $class_file);
    }
});

?>