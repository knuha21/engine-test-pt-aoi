<?php
class KRAEPELINTest {
    public $answers;
    public $startTime;
    public $endTime;
    private $db;

    function __construct($answers = null, $startTime = null, $endTime = null) {
        $this->answers = $answers;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->db = getDBConnection();
    }

    /**
     * Method untuk kompatibilitas dengan code yang sudah ada
     */
    function calculateResults() {
        $total = count($this->answers);
        $correct = 0;
        $wrong = 0;
        $sections = [];
        foreach ($this->answers as $i => $ans) {
            if ($ans['is_correct']) {
                $correct++;
            } else {
                $wrong++;
            }
            $sectionIndex = floor($i / 10); // tiap 10 soal = 1 section
            if (!isset($sections[$sectionIndex])) $sections[$sectionIndex] = 0;
            $sections[$sectionIndex] += $ans['is_correct'] ? 1 : 0;
        }

        $duration = $this->endTime - $this->startTime;
        $avgTime = $total > 0 ? $duration / $total : 0;

        // Analisis pola kesalahan
        $third = floor($total/3);
        $early = array_slice($this->answers, 0, $third);
        $middle = array_slice($this->answers, $third, $third);
        $late = array_slice($this->answers, $third*2);

        $countWrong = function($arr){return count(array_filter($arr, fn($a)=>!$a['is_correct']));};
        $patternErrors = [
            'awal' => $countWrong($early),
            'tengah' => $countWrong($middle),
            'akhir' => $countWrong($late)
        ];

        // Analisis kelelahan
        $earlyScore = array_sum(array_column($early,'is_correct'));
        $lateScore = array_sum(array_column($late,'is_correct'));
        $fatigue = $lateScore < $earlyScore * 0.8;

        return [
            'total_benar' => $correct,
            'total_salah' => $wrong,
            'durasi_detik' => $duration,
            'rata_waktu_per_soal' => $avgTime,
            'pola_kesalahan' => $patternErrors,
            'grafik_per_section' => array_values($sections),
            'indikasi_kelelahan' => $fatigue
        ];
    }

    /**
     * Memproses jawaban peserta untuk test Kraepelin (method baru)
     * @param array $jawaban Array jawaban dari form
     * @return array Hasil olahan jawaban
     */
    public function prosesJawaban($jawaban) {
        $hasil = [];
        $totalScore = 0;
        $correctAnswers = 0;
        $totalQuestions = 0;
        
        // Simpan waktu mulai dan selesai
        $startTime = time();
        
        foreach ($jawaban as $baris => $kolomJawaban) {
            foreach ($kolomJawaban as $kolom => $jawabanPeserta) {
                $totalQuestions++;
                
                // Validasi jawaban (0-9) - dalam Kraepelin, jawaban harus angka 0-9
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
        
        $endTime = time();
        $duration = $endTime - $startTime;
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;
        
        // Hitung analisis tambahan seperti di calculateResults
        $sections = [];
        $patternErrors = [
            'awal' => 0,
            'tengah' => 0,
            'akhir' => 0
        ];
        
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
            'test_date' => date('Y-m-d H:i:s')
        ];
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
}
?>