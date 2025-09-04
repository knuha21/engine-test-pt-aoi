<?php
class KRAEPELINTest {
    public $answers;
    public $startTime;
    public $endTime;
    private $db;
    private $deret;

    function __construct($deret = null) {
        $this->deret = $deret;
        $this->db = getDBConnection();
    }

    /**
     * Validasi jawaban Kraepelin yang benar
     */
    private function validateKraepelinAnswer($baris, $kolom, $userAnswer) {
        if (!isset($this->deret[$baris][$kolom]) || !isset($this->deret[$baris][$kolom + 1])) {
            return false;
        }
        
        $num1 = $this->deret[$baris][$kolom];
        $num2 = $this->deret[$baris][$kolom + 1];
        $correctSum = $num1 + $num2;
        
        // Untuk Kraepelin, hanya digit terakhir yang ditulis
        $correctAnswer = $correctSum % 10;
        
        return ($userAnswer == $correctAnswer);
    }

    /**
     * Memproses jawaban peserta untuk test Kraepelin
     */
    public function prosesJawaban($jawaban) {
        $hasil = [];
        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = 0;
        
        $startTime = time();
        
        foreach ($jawaban as $baris => $kolomJawaban) {
            foreach ($kolomJawaban as $kolom => $jawabanPeserta) {
                $totalQuestions++;
                
                // Validasi jawaban yang benar
                $isCorrect = $this->validateKraepelinAnswer($baris, $kolom, $jawabanPeserta);
                
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
        
        $endTime = time();
        $duration = $endTime - $startTime;
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Analisis tambahan
        $sections = [];
        $patternErrors = ['awal' => 0, 'tengah' => 0, 'akhir' => 0];
        $fatigue = false;
        
        if (count($hasil) > 0) {
            // Analisis per section
            foreach ($hasil as $i => $answer) {
                $sectionIndex = floor($i / 10);
                if (!isset($sections[$sectionIndex])) $sections[$sectionIndex] = 0;
                $sections[$sectionIndex] += $answer['is_correct'] ? 1 : 0;
            }
            
            // Analisis pola kesalahan
            $third = floor(count($hasil)/3);
            $early = array_slice($hasil, 0, $third);
            $middle = array_slice($hasil, $third, $third);
            $late = array_slice($hasil, $third*2);
            
            $countWrong = function($arr){
                return count(array_filter($arr, function($a) { 
                    return !$a['is_correct']; 
                }));
            };
            
            $patternErrors = [
                'awal' => $countWrong($early),
                'tengah' => $countWrong($middle),
                'akhir' => $countWrong($late)
            ];
            
            // Analisis kelelahan
            $earlyScore = array_sum(array_column($early, 'is_correct'));
            $lateScore = array_sum(array_column($late, 'is_correct'));
            $fatigue = $lateScore < $earlyScore * 0.8;
        }
        
        // Deteksi anomaly waktu
        $timeAnomaly = $this->detectTimeAnomaly($duration, $totalQuestions);
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => $accuracy,
            'durasi_detik' => $duration,
            'rata_waktu_per_soal' => $totalQuestions > 0 ? $duration / $totalQuestions : 0,
            'pola_kesalahan' => $patternErrors,
            'grafik_per_section' => array_values($sections),
            'indikasi_kelelahan' => $fatigue,
            'time_anomaly' => $timeAnomaly,
            'test_date' => date('Y-m-d H:i:s'),
            'deret' => $this->deret // Simpan deret untuk referensi
        ];
    }
    
    /**
     * Deteksi anomaly waktu pengerjaan
     */
    private function detectTimeAnomaly($duration, $totalQuestions) {
        $expectedTimePerQuestion = 3; // 3 detik per soal (normal)
        $expectedTime = $totalQuestions * $expectedTimePerQuestion;
        
        if ($duration < $expectedTime * 0.3) {
            return 'WARNING: Waktu pengerjaan terlalu cepat (mungkin terburu-buru)';
        }
        
        if ($duration > $expectedTime * 2) {
            return 'WARNING: Waktu pengerjaan terlalu lambat (mungkin ada gangguan)';
        }
        
        return null;
    }
    
    /**
     * Menyimpan hasil test ke database
     */
    public function simpanHasilTest($participantId, $hasilOlahan) {
        try {
            $resultsJson = json_encode($hasilOlahan);
            
            $query = $this->db->prepare("
                INSERT INTO test_results (participant_id, test_type, results, created_at) 
                VALUES (?, 'KRAEPELIN', ?, NOW())
            ");
            
            return $query->execute([$participantId, $resultsJson]);
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