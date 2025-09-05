<?php
// test_fix.php - Test langsung tanpa form
require_once 'bootstrap.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct Test Fix</h1>";

// Create simple deret
$deret = [
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 0],
    [2, 3, 4, 5, 6, 7, 8, 9, 0, 1],
    [3, 4, 5, 6, 7, 8, 9, 0, 1, 2]
];

echo "<h2>Deret yang digunakan:</h2>";
foreach ($deret as $i => $baris) {
    echo "Baris $i: " . implode(' ', $baris) . "<br>";
}

// Test manual calculation
echo "<h2>Perhitungan Manual:</h2>";
for ($i = 0; $i < 2; $i++) {
    for ($j = 0; $j < 9; $j++) {
        $num1 = $deret[$i][$j];
        $num2 = $deret[$i][$j + 1];
        $sum = $num1 + $num2;
        $expected = $sum % 10;
        echo "Baris $i, Kolom $j: $num1 + $num2 = $sum → $expected<br>";
    }
}

// Test dengan class
if (class_exists('KRAEPELINTest')) {
    echo "<h2>Testing dengan KRAEPELINTest Class:</h2>";
    
    $kraepelinTest = new KRAEPELINTest($deret);
    
    // Jawaban yang benar
    $jawaban_benar = [
        0 => [0 => '3', 1 => '5', 2 => '7', 3 => '9', 4 => '1', 5 => '3', 6 => '5', 7 => '7', 8 => '9'],
        1 => [0 => '5', 1 => '7', 2 => '9', 3 => '1', 4 => '3', 5 => '5', 6 => '7', 7 => '9', 8 => '1']
    ];
    
    $hasil = $kraepelinTest->prosesJawaban($jawaban_benar);
    
    echo "<h3>Hasil dengan jawaban benar:</h3>";
    echo "Total Questions: " . $hasil['total_questions'] . "<br>";
    echo "Correct Answers: " . $hasil['correct_answers'] . "<br>";
    echo "Accuracy: " . $hasil['accuracy'] . "%<br>";
    
    echo "<h3>Detail Jawaban:</h3>";
    foreach ($hasil['answers'] as $answer) {
        $status = $answer['is_correct'] ? '✅ BENAR' : '❌ SALAH';
        $color = $answer['is_correct'] ? 'green' : 'red';
        echo "<span style='color: $color'>";
        echo "Baris {$answer['baris']}, Kolom {$answer['kolom']}: ";
        echo "{$answer['num1']} + {$answer['num2']} = " . ($answer['num1'] + $answer['num2']);
        echo " → {$answer['expected']}, Jawaban: {$answer['jawaban']} - $status";
        echo "</span><br>";
    }
}

// Test connection to database
echo "<h2>Database Test:</h2>";
try {
    $db = getDBConnection();
    if ($db) {
        echo "✅ Database connected successfully<br>";
        
        // Test participants table
        $stmt = $db->query("SELECT COUNT(*) as count FROM participants");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Participants: $count records<br>";
        
        // Test test_results table
        $stmt = $db->query("SELECT COUNT(*) as count FROM test_results");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Test Results: $count records<br>";
        
    } else {
        echo "❌ Database connection failed<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Session Info:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
if (isset($_SESSION)) {
    echo "Session Data: <pre>" . print_r($_SESSION, true) . "</pre>";
}
?>