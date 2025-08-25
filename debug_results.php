<?php
require_once 'bootstrap.php';

// Hanya untuk debugging - jangan di production
if (!APP_DEBUG) {
    header("Location: index.php");
    exit();
}

echo "<h1>Debug Results</h1>";

try {
    $db = getDBConnection();
    
    // Tampilkan semua hasil test
    $query = "SELECT * FROM test_results ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>10 Hasil Test Terbaru:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Participant ID</th><th>Test Type</th><th>Created At</th><th>Results</th></tr>";
    
    foreach ($results as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['participant_id']}</td>";
        echo "<td>{$row['test_type']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "<td><pre>" . htmlspecialchars($row['results']) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Tampilkan data session
    echo "<h2>Session Data:</h2>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    
    // Tampilkan data POST terakhir jika ada
    echo "<h2>Last POST Data:</h2>";
    echo "<pre>" . print_r($_POST, true) . "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<br><br>
<a href="pages/kraepelin-test.php">Kembali ke Test Kraepelin</a>