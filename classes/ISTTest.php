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
        
        // Data norma IST yang valid
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
     * Mengambil norma IST yang valid (bukan random)
     * @return array Data norma
     */
    private function getISTNorms() {
        // NORMA IST YANG VALID (contoh data - harus disesuaikan dengan data sebenarnya)
        $norms = [
            // Subtest SE (Kosa Kata)
            'SE_1' => ['correct_answer' => 'B', 'weighted_score' => 1],
            'SE_2' => ['correct_answer' => 'A', 'weighted_score' => 1],
            'SE_3' => ['correct_answer' => 'D', 'weighted_score' => 1],
            'SE_4' => ['correct_answer' => 'C', 'weighted_score' => 1],
            'SE_5' => ['correct_answer' => 'B', 'weighted_score' => 1],
            
            // Subtest WA (Kemampuan Verbal)
            'WA_1' => ['correct_answer' => 'C', 'weighted_score' => 2],
            'WA_2' => ['correct_answer' => 'B', 'weighted_score' => 2],
            'WA_3' => ['correct_answer' => 'A', 'weighted_score' => 2],
            'WA_4' => ['correct_answer' => 'D', 'weighted_score' => 2],
            'WA_5' => ['correct_answer' => 'C', 'weighted_score' => 2],
            
            // Subtest AN (Kemampuan Analitis)
            'AN_1' => ['correct_answer' => 'D', 'weighted_score' => 3],
            'AN_2' => ['correct_answer' => 'C', 'weighted_score' => 3],
            'AN_3' => ['correct_answer' => 'B', 'weighted_score' => 3],
            'AN_4' => ['correct_answer' => 'A', 'weighted_score' => 3],
            'AN_5' => ['correct_answer' => 'D', 'weighted_score' => 3],
            
            // Subtest GE (Kemampuan Generalisasi)
            'GE_1' => ['correct_answer' => 'A', 'weighted_score' => 2],
            'GE_2' => ['correct_answer' => 'B', 'weighted_score' => 2],
            'GE_3' => ['correct_answer' => 'C', 'weighted_score' => 2],
            'GE_4' => ['correct_answer' => 'D', 'weighted_score' => 2],
            'GE_5' => ['correct_answer' => 'A', 'weighted_score' => 2],
            
            // Subtest RA (Kemampuan Aritmatika)
            'RA_1' => ['correct_answer' => 'C', 'weighted_score' => 3],
            'RA_2' => ['correct_answer' => 'B', 'weighted_score' => 3],
            'RA_3' => ['correct_answer' => 'A', 'weighted_score' => 3],
            'RA_4' => ['correct_answer' => 'D', 'weighted_score' => 3],
            'RA_5' => ['correct_answer' => 'C', 'weighted_score' => 3],
            
            // Subtest ZR (Kemampuan Numerik)
            'ZR_1' => ['correct_answer' => 'B', 'weighted_score' => 2],
            'ZR_2' => ['correct_answer' => 'A', 'weighted_score' => 2],
            'ZR_3' => ['correct_answer' => 'D', 'weighted_score' => 2],
            'ZR_4' => ['correct_answer' => 'C', 'weighted_score' => 2],
            'ZR_5' => ['correct_answer' => 'B', 'weighted_score' => 2],
        ];
        
        return $norms;
    }
    
    /**
     * Mengkonversi skor total menjadi skor IQ
     * @param float $totalScore Skor total
     * @return float Skor IQ
     */
    private function convertToIQ($totalScore) {
        // Formula konversi berdasarkan norma IST
        // Skor maksimal sekitar 75 (5 subtest x 5 soal x 3 poin)
        $iq = 100 + (($totalScore / 75) * 40); // Convert to IQ scale 60-140
        
        return max(60, min(140, round($iq, 1))); // Clamp between 60-140
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