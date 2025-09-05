<?php
/**
 * Class PauliTest - Untuk menangani proses testing Pauli - DIPERBAIKI
 */
class PauliTest {
    private $db;
    private $deret;
    
    public function __construct($deret = null) {
        $this->deret = $deret;
        $this->db = getDBConnection();
    }
    
    /**
     * Validasi jawaban Pauli yang benar - DIPERBAIKI
     */
    private function validatePauliAnswer($baris, $kolom, $userAnswer) {
        if (!isset($this->deret[$baris][$kolom]) || !isset($this->deret[$baris][$kolom + 1])) {
            error_log("PAULI Error: Invalid column index - Baris: $baris, Kolom: $kolom");
            return false;
        }
        
        $num1 = (int)$this->deret[$baris][$kolom];
        $num2 = (int)$this->deret[$baris][$kolom + 1];
        $correctSum = $num1 + $num2;
        
        // Untuk Pauli, hanya digit terakhir yang ditulis
        $correctAnswer = $correctSum % 10;
        
        // Konversi userAnswer ke integer
        $userAnswer = (int)$userAnswer;
        
        // Debug info
        error_log("PAULI - Baris $baris, Kolom $kolom: $num1 + $num2 = $correctSum -> $correctAnswer, User: $userAnswer");
        
        return ($userAnswer == $correctAnswer);
    }
    
    /**
     * Memproses jawaban peserta dan menghitung skor - DIPERBAIKI
     */
    public function prosesJawaban($jawaban) {
        $hasil = [];
        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = 0;
        
        // Debug: Log deret yang digunakan
        error_log("Deret PAULI yang digunakan: " . json_encode($this->deret));
        error_log("Jawaban PAULI dari form: " . json_encode($jawaban));
        
        foreach ($jawaban as $baris => $kolomJawaban) {
            foreach ($kolomJawaban as $kolom => $jawabanPeserta) {
                // Skip jika jawaban kosong
                if (empty($jawabanPeserta) && $jawabanPeserta !== '0') {
                    error_log("PAULI Skipping empty answer - Baris: $baris, Kolom: $kolom");
                    continue;
                }
                
                $totalQuestions++;
                
                // Validasi jawaban Pauli yang benar
                $isCorrect = $this->validatePauliAnswer($baris, $kolom, $jawabanPeserta);
                
                if ($isCorrect) {
                    $correctAnswers++;
                    $score = 1;
                } else {
                    $score = 0;
                }
                
                $num1 = $this->deret[$baris][$kolom];
                $num2 = isset($this->deret[$baris][$kolom + 1]) ? $this->deret[$baris][$kolom + 1] : 'N/A';
                $expected = isset($this->deret[$baris][$kolom + 1]) ? ($num1 + $num2) % 10 : 'N/A';
                
                $hasil[] = [
                    'baris' => $baris,
                    'kolom' => $kolom,
                    'jawaban' => $jawabanPeserta,
                    'is_correct' => $isCorrect,
                    'score' => $score,
                    'num1' => $num1,
                    'num2' => $num2,
                    'expected' => $expected
                ];
                
                $totalScore += $score;
            }
        }
        
        // Hitung kecepatan dan ketelitian
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Hitung fluktuasi (konsistensi)
        $fluctuation = $this->hitungFluktuasi($hasil);
        
        // Deteksi anomaly
        $consistencyWarning = $fluctuation > 2.0 ? 'WARNING: Fluktuasi tinggi - konsistensi kurang' : null;
        
        // Log hasil processing
        error_log("PAULI Results - Total: $totalQuestions, Correct: $correctAnswers, Accuracy: " . number_format($accuracy, 1) . "%");
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'fluctuation' => $fluctuation,
            'consistency_warning' => $consistencyWarning,
            'test_date' => date('Y-m-d H:i:s'),
            'deret' => $this->deret
        ];
    }
    
    /**
     * Menghitung fluktuasi jawaban (konsistensi) - DIPERBAIKI
     */
    private function hitungFluktuasi($jawaban) {
        $correctPerRow = [];
        
        // Hitung jawaban benar per baris dengan validasi
        foreach ($jawaban as $answer) {
            $baris = $answer['baris'];
            if (!isset($correctPerRow[$baris])) {
                $correctPerRow[$baris] = 0;
            }
            if ($answer['is_correct']) {
                $correctPerRow[$baris]++;
            }
        }
        
        // Hitung fluktuasi (standar deviasi)
        if (count($correctPerRow) > 1) {
            $values = array_values($correctPerRow);
            $mean = array_sum($values) / count($values);
            $sumSquaredDiff = 0;
            foreach ($values as $value) {
                $sumSquaredDiff += pow($value - $mean, 2);
            }
            return sqrt($sumSquaredDiff / count($values));
        }
        
        return 0;
    }
    
    /**
     * Menyimpan hasil test ke database
     */
    public function simpanHasilTest($participantId, $hasilOlahan) {
        try {
            $resultsJson = json_encode($hasilOlahan);
            
            $query = $this->db->prepare("
                INSERT INTO test_results (participant_id, test_type, results, created_at) 
                VALUES (?, 'PAULI', ?, NOW())
            ");
            
            $success = $query->execute([$participantId, $resultsJson]);
            
            if ($success) {
                error_log("PAULI test results saved successfully for participant: $participantId");
            } else {
                error_log("Failed to save PAULI test results for participant: $participantId");
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Error saving Pauli test results: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengambil hasil test berdasarkan ID
     */
    public function getHasilTest($testId) {
        try {
            $query = $this->db->prepare("
                SELECT r.*, p.name as participant_name, p.email 
                FROM test_results r 
                JOIN participants p ON r.participant_id = p.id 
                WHERE r.id = ?
            ");
            
            if ($query->execute([$testId])) {
                $result = $query->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $result['results'] = json_decode($result['results'], true);
                    return $result;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching test results: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Mengambil semua hasil test Pauli
     */
    public function getAllHasilTest() {
        try {
            $query = $this->db->prepare("
                SELECT r.*, p.name as participant_name, p.email 
                FROM test_results r 
                JOIN participants p ON r.participant_id = p.id 
                WHERE r.test_type = 'PAULI'
                ORDER BY r.created_at DESC
            ");
            
            if ($query->execute()) {
                $results = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($results as &$result) {
                    $result['results'] = json_decode($result['results'], true);
                }
                return $results;
            }
        } catch (Exception $e) {
            error_log("Error fetching all test results: " . $e->getMessage());
        }
        
        return [];
    }
}
?>