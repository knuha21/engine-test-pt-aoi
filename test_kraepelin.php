<?php
// test_kraepelin.php
require_once 'bootstrap.php';

// Data sample deret
$deret = [
    [1, 2, 3, 4, 5, 6, 7, 8, 9, 0],
    [2, 3, 4, 5, 6, 7, 8, 9, 0, 1],
    [3, 4, 5, 6, 7, 8, 9, 0, 1, 2],
    [4, 5, 6, 7, 8, 9, 0, 1, 2, 3],
    [5, 6, 7, 8, 9, 0, 1, 2, 3, 4]
];

// Jawaban yang seharusnya benar semua
$jawabanBenar = [
    0 => [0 => '3', 1 => '5', 2 => '7', 3 => '9', 4 => '1', 5 => '3', 6 => '5', 7 => '7', 8 => '9'],
    1 => [0 => '5', 1 => '7', 2 => '9', 3 => '1', 4 => '3', 5 => '5', 6 => '7', 7 => '9', 8 => '1'],
    2 => [0 => '7', 1 => '9', 2 => '1', 3 => '3', 4 => '5', 5 => '7', 6 => '9', 7 => '1', 8 => '3'],
    3 => [0 => '9', 1 => '1', 2 => '3', 3 => '5', 4 => '7', 5 => '9', 6 => '1', 7 => '3', 8 => '5'],
    4 => [0 => '1', 1 => '3', 2 => '5', 3 => '7', 4 => '9', 5 => '1', 6 => '3', 7 => '5', 8 => '7']
];

$test = new KRAEPELINTest($deret);
$hasil = $test->prosesJawaban($jawabanBenar);

echo "Total Questions: " . $hasil['total_questions'] . "\n";
echo "Correct Answers: " . $hasil['correct_answers'] . "\n";
echo "Accuracy: " . $hasil['accuracy'] . "%\n";

// Tampilkan detail
foreach ($hasil['answers'] as $answer) {
    $status = $answer['is_correct'] ? 'BENAR' : 'SALAH';
    echo "Baris {$answer['baris']}, Kolom {$answer['kolom']}: {$answer['num1']}+{$answer['num2']}=" . 
         (($answer['num1'] + $answer['num2']) % 10) . 
         ", Jawaban: {$answer['jawaban']}, Status: $status\n";
}
?>