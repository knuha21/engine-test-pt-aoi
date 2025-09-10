<?php
// Simple autoloader untuk classes
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    // Coba alternative path
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    error_log("Class $className not found in autoloader");
    return false;
});

// Load configuration - hanya jika belum diload
if (!defined('DB_HOST')) {
    $configPath = __DIR__ . '/config/database.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        // Fallback configuration
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'alat_test_db');
        define('DB_USER', 'root');
        define('DB_PASS', '');
    }
}

// Set error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Debug function
function debug($data, $exit = false) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        
        if ($exit) {
            exit;
        }
    }
}

spl_autoload_register(function ($className) {
    // Support untuk berbagai format penulisan class
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    // Coba alternative naming convention
    $classFile = __DIR__ . '/classes/' . strtolower($className) . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    
    return false;
});
?>