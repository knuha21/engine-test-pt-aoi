<?php
require_once 'bootstrap.php';

echo "<h1>Database Check</h1>";

try {
    $db = getDBConnection();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'test_results'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Table test_results exists<br>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE test_results");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Table Structure:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "❌ Table test_results does not exist<br>";
        echo "Please run the SQL script to create the table:<br>";
        echo "<pre>CREATE TABLE test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    test_type ENUM('TIKI', 'KRAEPLIN', 'PAULI', 'IST') NOT NULL,
    results TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id)
);</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>

<br><br>
<a href="pages/kraepelin-test.php">Test Kraepelin</a> | 
<a href="debug_results.php">Debug Results</a>