<?php
/**
 * Class PauliTest - Untuk menangani proses testing Pauli
 */
class PauliTest {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    /**
     * Memproses jawaban peserta dan menghitung skor
     * @param array $jawaban Array jawaban dari peserta
     * @return array Hasil olahan jawaban
     */
    public function prosesJawaban($jawaban) {
        $hasil = [];
        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = 0;
        
        // Hitung jawaban benar dan salah
        foreach ($jawaban as $baris => $kolomJawaban) {
            foreach ($kolomJawaban as $kolom => $jawabanPeserta) {
                $totalQuestions++;
                
                // Dalam test Pauli, kita perlu menghitung jawaban yang benar
                // Untuk demo, kita asumsikan jawaban benar jika tidak kosong dan antara 0-9
                $isCorrect = (!empty($jawabanPeserta) && is_numeric($jawabanPeserta) && 
                             $jawabanPeserta >= 0 && $jawabanPeserta <= 9);
                
                if ($isCorrect) {
                    $correctAnswers++;
                    $score = 1;
                } else {
                    $score = 0;
                }
                
                $hasil[] = [
                    'baris' => $baris,
                    'kolom' => $kolom,
                    'jawaban' => $jawabanPeserta,
                    'is_correct' => $isCorrect,
                    'score' => $score
                ];
                
                $totalScore += $score;
            }
        }
        
        // Hitung kecepatan dan ketelitian
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Hitung fluktuasi (perbedaan antara jawaban benar tertinggi dan terendah per baris)
        $fluctuation = $this->hitungFluktuasi($jawaban);
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'fluctuation' => $fluctuation,
            'test_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Menghitung fluktuasi jawaban (konsistensi)
     * @param array $jawaban Jawaban peserta
     * @return float Tingkat fluktuasi
     */
    private function hitungFluktuasi($jawaban) {
        $correctPerRow = [];
        
        // Hitung jawaban benar per baris
        foreach ($jawaban as $baris => $kolomJawaban) {
            $correctCount = 0;
            foreach ($kolomJawaban as $jawabanPeserta) {
                if (!empty($jawabanPeserta) && is_numeric($jawabanPeserta) && 
                    $jawabanPeserta >= 0 && $jawabanPeserta <= 9) {
                    $correctCount++;
                }
            }
            $correctPerRow[] = $correctCount;
        }
        
        // Hitung fluktuasi (standar deviasi)
        if (count($correctPerRow) > 1) {
            $mean = array_sum($correctPerRow) / count($correctPerRow);
            $sumSquaredDiff = 0;
            foreach ($correctPerRow as $value) {
                $sumSquaredDiff += pow($value - $mean, 2);
            }
            return sqrt($sumSquaredDiff / count($correctPerRow));
        }
        
        return 0;
    }
    
    /**
     * Menyimpan hasil test ke database
     * @param int $participantId ID peserta
     * @param array $hasilOlahan Hasil olahan jawaban
     * @return bool Berhasil atau tidak
     */
    public function simpanHasilTest($participantId, $hasilOlahan) {
        try {
            // Encode hasil menjadi JSON untuk disimpan
            $resultsJson = json_encode($hasilOlahan);
            
            $query = $this->db->prepare("
                INSERT INTO test_results (participant_id, test_type, results, created_at) 
                VALUES (?, 'PAULI', ?, NOW())
            ");
            
            return $query->execute([$participantId, $resultsJson]);
        } catch (Exception $e) {
            error_log("Error saving test results: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengambil hasil test berdasarkan ID
     * @param int $testId ID test
     * @return array|null Data hasil test
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
     * @return array Data hasil test
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