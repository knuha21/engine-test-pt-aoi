<?php
// bootstrap.php - File untuk memastikan semua dependency terload dengan benar
// Define root access untuk file yang include bootstrap
if (!defined('ROOT_ACCESS')) {
    define('ROOT_ACCESS', true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/autoload.php';

// Function untuk memuat class dengan aman
function loadClass($className) {
    $classFile = __DIR__ . '/classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
        return true;
    }
    return false;
}

// Function untuk memastikan database connection
function getDBConnection() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}

// Function untuk check login
function isLoggedIn() {
    return isset($_SESSION['participant_id']);
}

// Function untuk check role admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function untuk redirect jika belum login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Function untuk redirect jika bukan admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../unauthorized.php");
        exit();
    }
}

// Function untuk redirect berdasarkan role
function redirectByRole() {
    if (isAdmin()) {
        header("Location: pages/admin/dashboard.php");
    } else {
        header("Location: pages/dashboard.php");
    }
    exit();
}

// Function untuk mendapatkan hasil test
function getTestResults($testType, $testId = null) {
    try {
        $db = getDBConnection();
        
        if ($testId) {
            $query = "SELECT * FROM test_results WHERE id = :id AND participant_id = :participant_id AND test_type = :test_type";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $testId);
            $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
            $stmt->bindValue(":test_type", strtoupper($testType));
        } else {
            $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = :test_type ORDER BY created_at DESC LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
            $stmt->bindValue(":test_type", strtoupper($testType));
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $testData = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'data' => json_decode($testData['results'], true),
                'id' => $testData['id'],
                'created_at' => $testData['created_at']
            ];
        }
    } catch (PDOException $e) {
        error_log("Error getting test results: " . $e->getMessage());
    }
    
    return null;
}

// Function untuk mendapatkan semua hasil test
function getAllTestResults($participantId = null, $testType = null) {
    try {
        $db = getDBConnection();
        
        if ($participantId) {
            if ($testType) {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = :test_type ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":participant_id", $participantId);
                $stmt->bindValue(":test_type", strtoupper($testType));
            } else {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":participant_id", $participantId);
            }
        } else {
            if ($testType) {
                $query = "SELECT * FROM test_results WHERE test_type = :test_type ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindValue(":test_type", strtoupper($testType));
            } else {
                $query = "SELECT * FROM test_results ORDER BY created_at DESC";
                $stmt = $db->prepare($query);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting all test results: " . $e->getMessage());
        return [];
    }
}

// Register autoload untuk classes yang mungkin terlewat
spl_autoload_register('loadClass');

// Error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    if (ini_get('display_errors')) {
        echo "<div class='error-message'>Error: $errstr</div>";
    }
});

set_exception_handler(function($exception) {
    error_log("Exception: " . $exception->getMessage());
    if (ini_get('display_errors')) {
        echo "<div class='error-message'>Exception: " . $exception->getMessage() . "</div>";
    }
});

// Check if constants are already defined before defining them
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}


// Set session timeout untuk deret (10 menit)
if (isset($_SESSION['kraepelin_generated'])) {
    $timeout = 10 * 60; // 10 menit
    if (time() - $_SESSION['kraepelin_generated'] > $timeout) {
        unset($_SESSION['kraepelin_deret']);
        unset($_SESSION['kraepelin_generated']);
        error_log("Kraepelin deret expired after timeout");
    }
}
?>