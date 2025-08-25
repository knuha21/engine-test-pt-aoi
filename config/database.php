<?php
// Pastikan constant belum didefinisikan sebelumnya
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

// Debug info
error_log("Database configuration loaded successfully");
?>