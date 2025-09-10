<?php
// repair_kraepelin_data.php
require_once 'bootstrap.php';

if (!isAdmin()) {
    die('Hanya admin yang dapat mengakses script ini');
}

echo "<h1>Repair Kraepelin Data</h1>";
echo "<pre>";

try {
    $db = getDBConnection();
    
    // Ambil semua hasil test Kraepelin
    $query = $db->prepare("SELECT id, results FROM test_results WHERE test_type = 'KRAEPELIN'");
    $query->execute();
    $tests = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $repaired = 0;
    $total = count($tests);
    
    foreach ($tests as $test) {
        $results = json_decode($test['results'], true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($results)) {
            $needsUpdate = false;
            
            // Perbaiki format deret jika diperlukan
            if (isset($results['deret'])) {
                if (is_string($results['deret'])) {
                    $decodedDeret = json_decode($results['deret'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $results['deret'] = $decodedDeret;
                        $needsUpdate = true;
                        echo "Repaired deret format for test ID: " . $test['id'] . "\n";
                    }
                }
            }
            
            // Update database jika diperlukan
            if ($needsUpdate) {
                $updateQuery = $db->prepare("UPDATE test_results SET results = ? WHERE id = ?");
                $updateQuery->execute([json_encode($results), $test['id']]);
                $repaired++;
            }
        } else {
            echo "Cannot decode results for test ID: " . $test['id'] . "\n";
        }
    }
    
    echo "\nRepaired $repaired of $total Kraepelin tests\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>