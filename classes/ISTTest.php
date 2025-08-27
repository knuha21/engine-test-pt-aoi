<?php
/**
 * Class ISTTest - Untuk menangani proses testing IST (Intelligenz Struktur Test)
 */
class ISTTest {
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
        
        // Data norma IST (contoh data, bisa disesuaikan)
        $istNorms = $this->getISTNorms();
        
        // Hitung jawaban benar dan salah
        foreach ($jawaban as $subtest => $questions) {
            $subtestScore = 0;
            $subtestTotal = 0;
            
            foreach ($questions as $questionNumber => $userAnswer) {
                $totalQuestions++;
                $subtestTotal++;
                
                // Cek kunci jawaban
                $key = $subtest . '_' . $questionNumber;
                $isCorrect = false;
                $score = 0;
                
                if (isset($istNorms[$key])) {
                    $isCorrect = (strtoupper($userAnswer) == strtoupper($istNorms[$key]['correct_answer']));
                    
                    if ($isCorrect) {
                        $correctAnswers++;
                        $score = $istNorms[$key]['weighted_score'];
                        $subtestScore += $score;
                        $totalScore += $score;
                    }
                }
                
                $hasil['answers'][] = [
                    'subtest' => $subtest,
                    'question_number' => $questionNumber,
                    'user_answer' => $userAnswer,
                    'correct_answer' => $istNorms[$key]['correct_answer'] ?? 'N/A',
                    'is_correct' => $isCorrect,
                    'score' => $score,
                    'weighted_score' => $score
                ];
            }
            
            // Simpan skor per subtest
            $hasil['subtest_scores'][$subtest] = $subtestScore;
            $hasil['subtest_totals'][$subtest] = $subtestTotal;
        }
        
        // Hitung skor total dan konversi
        $hasil['total_score'] = $totalScore;
        $hasil['total_questions'] = $totalQuestions;
        $hasil['correct_answers'] = $correctAnswers;
        $hasil['accuracy'] = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Konversi skor ke IQ
        $hasil['iq_score'] = $this->convertToIQ($totalScore);
        $hasil['test_date'] = date('Y-m-d H:i:s');
        
        return $hasil;
    }
    
    /**
     * Mengambil norma IST dari database
     * @return array Data norma
     */
    private function getISTNorms() {
        $norms = [];
        
        try {
            // Coba ambil dari database
            $query = $this->db->query("SELECT subtest, question_number, correct_answer, weighted_score FROM ist_norms");
            if ($query) {
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $key = $row['subtest'] . '_' . $row['question_number'];
                    $norms[$key] = $row;
                }
                return $norms;
            }
        } catch (Exception $e) {
            error_log("Error fetching IST norms: " . $e->getMessage());
        }
        
        // Fallback ke data default jika tidak ada di database
        return $this->getDefaultISTNorms();
    }
    
    /**
     * Data norma default untuk IST
     * @return array Data norma default
     */
    private function getDefaultISTNorms() {
        // Data contoh - harus diganti dengan data IST yang sebenarnya
        $norms = [];
        
        $subtests = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR'];
        
        foreach ($subtests as $subtest) {
            for ($i = 1; $i <= 20; $i++) {
                $key = $subtest . '_' . $i;
                $norms[$key] = [
                    'correct_answer' => chr(rand(65, 69)), // A-E random
                    'weighted_score' => rand(1, 3)
                ];
            }
        }
        
        return $norms;
    }
    
    /**
     * Mengkonversi skor total menjadi skor IQ
     * @param float $totalScore Skor total
     * @return float Skor IQ
     */
    private function convertToIQ($totalScore) {
        // Formula konversi sederhana - harus disesuaikan dengan norma IST yang sebenarnya
        return 100 + ($totalScore / 2);
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
                VALUES (?, 'IST', ?, NOW())
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
     * Mengambil semua soal IST
     * @return array Data soal
     */
    public function getAllSoal() {
        $soal = [];
        
        try {
            // Coba ambil dari database
            $query = $this->db->query("
                SELECT id, subtest, question_number, question_text, option_a, option_b, option_c, option_d, option_e 
                FROM ist_questions 
                ORDER BY subtest, question_number
            ");
            
            if ($query) {
                $soal = $query->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error fetching IST questions: " . $e->getMessage());
        }
        
        // Jika tidak ada soal di database, gunakan data default
        if (empty($soal)) {
            $soal = $this->getDefaultSoal();
        }
        
        return $soal;
    }
    
    /**
     * Data soal default untuk IST
     * @return array Data soal default
     */
    private function getDefaultSoal() {
        $soal = [];
        $subtests = [
            'SE' => 'Kosa Kata',
            'WA' => 'Kemampuan Verbal',
            'AN' => 'Kemampuan Analitis',
            'GE' => 'Kemampuan Generalisasi',
            'RA' => 'Kemampuan Aritmatika',
            'ZR' => 'Kemampuan Numerik'
        ];
        
        $questionId = 1;
        foreach ($subtests as $code => $name) {
            for ($i = 1; $i <= 20; $i++) {
                $soal[] = [
                    'id' => $questionId++,
                    'subtest' => $code,
                    'subtest_name' => $name,
                    'question_number' => $i,
                    'question_text' => "Soal {$code} nomor {$i} - Pilih jawaban yang paling tepat",
                    'option_a' => 'Pilihan A',
                    'option_b' => 'Pilihan B',
                    'option_c' => 'Pilihan C',
                    'option_d' => 'Pilihan D',
                    'option_e' => 'Pilihan E'
                ];
            }
        }
        
        return $soal;
    }
}
?>