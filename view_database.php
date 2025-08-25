<?php
require_once 'bootstrap.php';

// Hanya untuk debugging
if (!APP_DEBUG) {
    header("Location: index.php");
    exit();
}

echo "<h1>Database Viewer</h1>";

try {
    $db = getDBConnection();
    
    // Tampilkan semua data test_results
    $query = "SELECT * FROM test_results WHERE participant_id = :participant_id ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Data Test Results untuk Participant ID: {$_SESSION['participant_id']}</h2>";
    
    if (count($results) > 0) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f2f2f2;'>
                <th>ID</th>
                <th>Test Type</th>
                <th>Created At</th>
                <th>Results (Preview)</th>
                <th>Actions</th>
              </tr>";
        
        foreach ($results as $row) {
            $resultsPreview = substr($row['results'], 0, 100) . '...';
            
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['test_type']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td><pre style='white-space: pre-wrap;'>" . htmlspecialchars($resultsPreview) . "</pre></td>";
            echo "<td>
                    <a href='pages/results.php?test=" . strtolower($row['test_type']) . "&id={$row['id']}' style='padding: 5px 10px; background: #3498db; color: white; text-decoration: none; border-radius: 3px;'>View</a>
                  </td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Tidak ada data test results.</p>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

echo "<h2>Session Data</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<br><br>";
echo "<a href='pages/kraepelin-test.php'>Test Kraepelin</a> | ";
echo "<a href='pages/dashboard.php'>Dashboard</a>";
?>