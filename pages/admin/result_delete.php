<?php

require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

$resultId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($resultId === 0) {
    header("Location: results.php");
    exit();
}

try {
    $db = getDBConnection();
    
    // Hapus hasil test
    $query = "DELETE FROM test_results WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $resultId);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Hasil test berhasil dihapus!";
    } else {
        $_SESSION['error_message'] = "Gagal menghapus hasil test.";
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Error menghapus hasil test: " . $e->getMessage();
}

header("Location: results.php");
exit();