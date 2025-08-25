<?php
require_once 'bootstrap.php';

echo "<h1>Test Database Connection and Results System</h1>";

try {
    $db = getDBConnection();
    if ($db) {
        echo "✅ Database connection successful!<br>";
        
        // Test query untuk participants
        $stmt = $db->query("SELECT COUNT(*) as count FROM participants");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Participants count: " . $result['count'] . "<br>";
        
        // Test query untuk test_results
        $stmt = $db->query("SELECT COUNT(*) as count FROM test_results");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Test results count: " . $result['count'] . "<br>";
        
        // Test getTestResults function
        if (isset($_SESSION['participant_id'])) {
            $results = getTestResults('tiki');
            if ($results) {
                echo "✅ getTestResults function working!<br>";
                echo "Latest TIKI test ID: " . $results['id'] . "<br>";
            } else {
                echo "ℹ️ No TIKI test results found<br>";
            }
        }
        
    } else {
        echo "❌ Database connection failed!<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Check your database configuration in config/database.php<br>";
}

echo "<h2>Session Info:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Participant ID: " . ($_SESSION['participant_id'] ?? 'Not set') . "<br>";
echo "Participant Name: " . ($_SESSION['participant_name'] ?? 'Not set') . "<br>";

echo "<h2>Quick Links:</h2>";
echo "<a href='login.php'>Login</a> | ";
echo "<a href='index.php'>Register</a> | ";
echo "<a href='pages/dashboard.php'>Dashboard</a> | ";
echo "<a href='pages/history.php'>History</a>";
?>