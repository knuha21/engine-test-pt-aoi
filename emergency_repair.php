<?php
// emergency_repair.php
require_once 'bootstrap.php';

if (!isAdmin()) {
    die('Hanya admin yang dapat mengakses script ini');
}

echo "<h1>Emergency Repair Kraepelin Data</h1>";
echo "<pre>";

try {
    $db = getDBConnection();
    
    // Ambil semua hasil test Kraepelin dengan results yang mungkin bermasalah
    $query = $db->prepare("SELECT id, results FROM test_results WHERE test_type = 'KRAEPELIN'");
    $query->execute();
    $tests = $query->fetchAll(PDO::FETCH_ASSOC);
    
    $repaired = 0;
    $total = count($tests);
    
    foreach ($tests as $test) {
        $needsUpdate = false;
        $results = null;
        
        // Coba decode JSON
        $results = json_decode($test['results'], true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($results)) {
            // Periksa dan perbaiki format deret
            if (isset($results['deret'])) {
                if (is_string($results['deret'])) {
                    $decodedDeret = json_decode($results['deret'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $results['deret'] = $decodedDeret;
                        $needsUpdate = true;
                    }
                }
            }
        } else {
            // Data mungkin rusak, coba buat data baru dari raw string
            error_log("Data corrupted for test ID: " . $test['id']);
            $results = [
                'raw_data' => $test['results'],
                'error' => 'Data corrupted - could not parse',
                'answers' => [],
                'total_score' => 0,
                'total_questions' => 0,
                'correct_answers' => 0,
                'accuracy' => 0,
                'deret' => [],
                'test_date' => date('Y-m-d H:i:s')
            ];
            $needsUpdate = true;
        }
        
        // Update database jika diperlukan
        if ($needsUpdate) {
            $updateQuery = $db->prepare("UPDATE test_results SET results = ? WHERE id = ?");
            $updateQuery->execute([json_encode($results), $test['id']]);
            $repaired++;
            
            echo "Repaired test ID: " . $test['id'] . "\n";
        }
    }
    
    echo "\nRepaired $repaired of $total Kraepelin tests\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>