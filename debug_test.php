<?php
// debug_test.php
require_once 'bootstrap.php';

echo "<h1>Debug Test Kraepelin/Pauli</h1>";

// Test sederhana
$deret_test = [
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 0],
    [2, 3, 4, 5, 6, 7, 8, 9, 0, 1]
];

echo "<h2>Deret Test:</h2>";
echo "<pre>" . print_r($deret_test, true) . "</pre>";

// Test class Kraepelin
if (class_exists('KRAEPELINTest')) {
    echo "<h2>Testing KRAEPELINTest:</h2>";
    $kraepelinTest = new KRAEPELINTest($deret_test);
    
    // Test jawaban
    $jawaban_test = [
        0 => [0 => '3', 1 => '5', 2 => '7', 3 => '9', 4 => '1', 5 => '3', 6 => '5', 7 => '7', 8 => '9'],
        1 => [0 => '5', 1 => '7', 2 => '9', 3 => '1', 4 => '3', 5 => '5', 6 => '7', 7 => '8', 8 => '1']
    ];
    
    echo "<h3>Jawaban Test:</h3>";
    echo "<pre>" . print_r($jawaban_test, true) . "</pre>";
    
    $hasil = $kraepelinTest->prosesJawaban($jawaban_test);
    
    echo "<h3>Hasil Validasi:</h3>";
    echo "<pre>" . print_r($hasil, true) . "</pre>";
    
    echo "<h3>Detail Perhitungan:</h3>";
    foreach ($hasil['answers'] as $answer) {
        $status = $answer['is_correct'] ? '✅ BENAR' : '❌ SALAH';
        echo "Baris {$answer['baris']}, Kolom {$answer['kolom']}: ";
        echo "{$answer['num1']} + {$answer['num2']} = " . ($answer['num1'] + $answer['num2']);
        echo " → {$answer['expected']}, Jawaban: {$answer['jawaban']} - {$status}<br>";
    }
}

// Test class Pauli
if (class_exists('PauliTest')) {
    echo "<h2>Testing PauliTest:</h2>";
    $pauliTest = new PauliTest($deret_test);
    
    $hasil_pauli = $pauliTest->prosesJawaban($jawaban_test);
    
    echo "<h3>Hasil Validasi Pauli:</h3>";
    echo "<pre>" . print_r($hasil_pauli, true) . "</pre>";
}

echo "<h2>PHP Info:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Session: " . (isset($_SESSION) ? 'Active' : 'Inactive') . "<br>";

// Test database connection
try {
    $db = getDBConnection();
    echo "Database: Connected<br>";
    
    // Check if tables exist
    $tables = ['participants', 'test_results', 'kraepelin_norms'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        echo "Table $table: " . ($stmt->rowCount() > 0 ? 'Exists' : 'Missing') . "<br>";
    }
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "<br>";
}
?>