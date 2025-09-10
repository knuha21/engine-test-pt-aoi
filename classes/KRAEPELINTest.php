<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class KRAEPELINTest {
    private $db;
    private $deret;

    function __construct($deret = null) {
        // Handle berbagai format deret
        if (is_string($deret)) {
            // Coba decode JSON string
            $decoded = json_decode($deret, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->deret = $decoded;
                error_log("Deret decoded from JSON string: " . count($decoded) . " rows");
            } else {
                // Fallback: try to parse as serialized array
                $unserialized = @unserialize($deret);
                if ($unserialized !== false && is_array($unserialized)) {
                    $this->deret = $unserialized;
                    error_log("Deret unserialized from string: " . count($unserialized) . " rows");
                } else {
                    throw new Exception("Deret harus berupa array atau string JSON yang valid");
                }
            }
        } elseif (is_array($deret)) {
            $this->deret = $deret;
            error_log("Deret from array: " . count($deret) . " rows");
        } else {
            throw new Exception("Deret harus berupa array, diberikan: " . gettype($deret));
        }
        
        $this->db = getDBConnection();
        
        // Validasi struktur deret
        if (empty($this->deret) || !is_array($this->deret)) {
            throw new Exception("Deret tidak boleh kosong");
        }
        
        error_log("KRAEPELINTest Constructor - Deret rows: " . count($this->deret));
    }

    /**
     * Validasi jawaban Kraepelin yang benar
     */
    private function validateKraepelinAnswer($baris, $kolom, $userAnswer) {
        // Pastikan deret ada dan baris valid
        if (!is_array($this->deret) || !isset($this->deret[$baris]) || !is_array($this->deret[$baris])) {
            return false;
        }
        
        // Pastikan kolom dan kolom+1 ada
        if (!isset($this->deret[$baris][$kolom]) || !isset($this->deret[$baris][$kolom + 1])) {
            return false;
        }
        
        $num1 = (int)$this->deret[$baris][$kolom];
        $num2 = (int)$this->deret[$baris][$kolom + 1];
        $correctSum = $num1 + $num2;
        
        // Digit terakhir yang ditulis
        $correctAnswer = $correctSum % 10;
        
        $userAnswer = trim($userAnswer);
        if ($userAnswer === '') {
            return false;
        }
        
        $userAnswerInt = (int)$userAnswer;
        
        return ($userAnswerInt === $correctAnswer);
    }

    /**
     * Memproses jawaban peserta untuk test Kraepelin
     */
    public function prosesJawaban($jawaban) {
        $hasil = [];
        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = 0;
        
        error_log("=== KRAEPELIN PROCESSING START ===");
        error_log("Deret type: " . gettype($this->deret));
        error_log("Deret structure: " . (is_array($this->deret) ? count($this->deret) . " rows" : "Not array"));
        error_log("Jawaban structure: " . (is_array($jawaban) ? count($jawaban) . " rows" : "Not array"));
        
        if (!is_array($jawaban)) {
            error_log("ERROR: Jawaban bukan array");
            return $this->createEmptyResult();
        }
        
        $jumlahBaris = count($this->deret);
        
        // Iterasi melalui setiap baris
        for ($baris = 0; $baris < $jumlahBaris; $baris++) {
            if (!isset($this->deret[$baris]) || !is_array($this->deret[$baris])) {
                error_log("WARNING: Baris $baris tidak valid dalam deret");
                continue;
            }
            
            if (!isset($jawaban[$baris]) || !is_array($jawaban[$baris])) {
                error_log("WARNING: Baris $baris tidak ada dalam jawaban");
                continue;
            }
            
            $jumlahKolom = count($this->deret[$baris]);
            
            // Iterasi melalui setiap kolom (kecuali kolom terakhir)
            for ($kolom = 0; $kolom < $jumlahKolom - 1; $kolom++) {
                $userAnswer = isset($jawaban[$baris][$kolom]) ? trim($jawaban[$baris][$kolom]) : '';
                
                // Skip jika jawaban kosong
                if ($userAnswer === '') {
                    error_log("SKIP: Baris $baris, Kolom $kolom - Jawaban kosong");
                    continue;
                }
                
                $totalQuestions++;
                
                $isCorrect = $this->validateKraepelinAnswer($baris, $kolom, $userAnswer);
                $score = $isCorrect ? 1 : 0;
                
                if ($isCorrect) {
                    $correctAnswers++;
                }
                
                // Ambil angka dari deret
                $num1 = $this->deret[$baris][$kolom];
                $num2 = $this->deret[$baris][$kolom + 1];
                $expected = ($num1 + $num2) % 10;
                
                $hasil[] = [
                    'baris' => $baris,
                    'kolom' => $kolom,
                    'jawaban' => $userAnswer,
                    'is_correct' => $isCorrect,
                    'score' => $score,
                    'num1' => $num1,
                    'num2' => $num2,
                    'expected' => $expected
                ];
                
                $totalScore += $score;
                
                error_log("PROCESSED: Baris " . ($baris+1) . ", Kolom " . ($kolom+1) . 
                         " - $num1+$num2=" . ($num1+$num2) . "→$expected, " .
                         "Jawaban: $userAnswer, Correct: " . ($isCorrect ? 'YES' : 'NO'));
            }
        }
        
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        error_log("FINAL RESULT: Total: $totalQuestions, Correct: $correctAnswers, Accuracy: " . number_format($accuracy, 1) . "%");
        error_log("=== KRAEPELIN PROCESSING END ===");
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'test_date' => date('Y-m-d H:i:s'),
            'deret' => $this->deret
        ];
    }
    
    private function createEmptyResult() {
        return [
            'answers' => [],
            'total_score' => 0,
            'total_questions' => 0,
            'correct_answers' => 0,
            'accuracy' => 0,
            'test_date' => date('Y-m-d H:i:s'),
            'deret' => $this->deret
        ];
    }
    
    /**
     * Menyimpan hasil test ke database
     */
    public function simpanHasilTest($participantId, $hasilOlahan) {
        try {
            // Pastikan deret disimpan sebagai JSON string
            if (is_array($hasilOlahan) && isset($hasilOlahan['deret'])) {
                $hasilOlahan['deret'] = json_encode($hasilOlahan['deret']);
            }
            
            $resultsJson = json_encode($hasilOlahan);
            
            $query = $this->db->prepare("
                INSERT INTO test_results (participant_id, test_type, results, created_at) 
                VALUES (?, 'KRAEPELIN', ?, NOW())
            ");
            
            $success = $query->execute([$participantId, $resultsJson]);
            
            if ($success) {
                error_log("KRAEPELIN test results saved successfully for participant: $participantId");
            } else {
                error_log("Failed to save KRAEPELIN test results for participant: $participantId");
            }
            
            return $success;
        } catch (Exception $e) {
            error_log("Error saving Kraepelin test results: " . $e->getMessage());
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
}
?>