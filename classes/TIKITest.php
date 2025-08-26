<?php
/**
 * Class TIKITest - Untuk menangani proses testing TIKI
 */
class TIKITest {
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
        $subtestScores = [];
        
        // Ambil semua norma dari database
        $norms = $this->getAllNorms();
        
        // Proses setiap jawaban
        foreach ($jawaban as $subtest => $questions) {
            $subtestScores[$subtest] = 0;
            
            foreach ($questions as $questionNumber => $userAnswer) {
                $key = $subtest . '_' . $questionNumber;
                
                if (isset($norms[$key])) {
                    $isCorrect = (strtoupper($userAnswer) == strtoupper($norms[$key]['correct_answer']));
                    $score = $isCorrect ? $norms[$key]['weighted_score'] : 0;
                    
                    $hasil[] = [
                        'subtest' => $subtest,
                        'question_number' => $questionNumber,
                        'user_answer' => $userAnswer,
                        'correct_answer' => $norms[$key]['correct_answer'],
                        'is_correct' => $isCorrect,
                        'score' => $score,
                        'raw_score' => $isCorrect ? $norms[$key]['raw_score'] : 0,
                        'weighted_score' => $score
                    ];
                    
                    $totalScore += $score;
                    $subtestScores[$subtest] += $score;
                }
            }
        }
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'iq_score' => $this->convertToIQ($totalScore),
            'subtest_scores' => $subtestScores,
            'test_date' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Mengambil semua data norma dari database
     * @return array Data norma
     */
    private function getAllNorms() {
        $norms = [];
        
        try {
            $query = $this->db->query("SELECT subtest, question_number, correct_answer, raw_score, weighted_score FROM tiki_norms");
            if ($query) {
                while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                    $key = $row['subtest'] . '_' . $row['question_number'];
                    $norms[$key] = $row;
                }
            }
        } catch (Exception $e) {
            error_log("Error fetching norms: " . $e->getMessage());
        }
        
        return $norms;
    }
    
    /**
     * Mengkonversi skor total menjadi skor IQ
     * @param float $totalScore Skor total
     * @return float Skor IQ
     */
    private function convertToIQ($totalScore) {
        // Formula konversi sederhana - dapat disesuaikan dengan kebutuhan
        // Contoh: IQ = 100 + (skor total / 2)
        return 100 + ($totalScore / 2);
    }
    
    /**
     * Menghasilkan grafik hasil test
     * @param array $hasilOlahan Hasil olahan jawaban
     * @return string HTML/CSS untuk grafik
     */
    public function generateGrafik($hasilOlahan) {
        $html = '<div class="grafik-container">';
        $html .= '<h3>Hasil Tes TIKI</h3>';
        
        // Grafik skor subtest
        if (!empty($hasilOlahan['subtest_scores'])) {
            $html .= '<div class="subtest-scores">';
            $html .= '<h4>Skor per Subtest</h4>';
            
            foreach ($hasilOlahan['subtest_scores'] as $subtest => $score) {
                $percentage = min(100, ($score / 50) * 100); // Asumsi skor maksimal 50 per subtest
                $html .= '
                <div class="score-bar">
                    <div class="subtest-name">' . htmlspecialchars($subtest) . '</div>
                    <div class="bar-container">
                        <div class="bar" style="width: ' . $percentage . '%;"></div>
                    </div>
                    <div class="score-value">' . $score . '</div>
                </div>';
            }
            
            $html .= '</div>';
        }
        
        // Skor total
        $html .= '
        <div class="total-score">
            <h4>Skor Total: ' . $hasilOlahan['total_score'] . '</h4>
            <h4>Skor IQ: ' . number_format($hasilOlahan['iq_score'], 1) . '</h4>
        </div>';
        
        $html .= '</div>';
        
        return $html;
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
                VALUES (?, 'TIKI', ?, NOW())
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
     * Mengambil semua soal TIKI dari database
     * @return array Data soal
     */
    public function getAllSoal() {
        $soal = [];
        
        try {
            $query = $this->db->query("
                SELECT 
                    q.id as question_id,
                    q.subtest,
                    q.question_number,
                    q.question_text,
                    q.option_a,
                    q.option_b,
                    q.option_c,
                    q.option_d,
                    q.option_e,
                    n.correct_answer,
                    n.raw_score,
                    n.weighted_score
                FROM tiki_questions q
                LEFT JOIN tiki_norms n ON q.subtest = n.subtest AND q.question_number = n.question_number
                ORDER BY q.subtest, q.question_number
            ");
            
            if ($query) {
                $soal = $query->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error fetching questions: " . $e->getMessage());
        }
        
        // Fallback jika tidak ada soal di database
        if (empty($soal)) {
            $soal = $this->getDefaultSoal();
        }
        
        return $soal;
    }
    
    /**
     * Mengembalikan data soal default jika database kosong
     * @return array Data soal default
     */
    private function getDefaultSoal() {
        return [
            [
                'question_id' => 1,
                'subtest' => 'Verbal',
                'question_number' => 1,
                'question_text' => 'Manakah kata yang paling berbeda dari yang lain?',
                'option_a' => 'Meja',
                'option_b' => 'Kursi',
                'option_c' => 'Lemari',
                'option_d' => 'Lantai',
                'option_e' => 'Rak',
                'correct_answer' => 'D',
                'raw_score' => 1,
                'weighted_score' => 5
            ],
            [
                'question_id' => 2,
                'subtest' => 'Verbal',
                'question_number' => 2,
                'question_text' => 'Sinonim dari kata "Cerdas" adalah?',
                'option_a' => 'Bodoh',
                'option_b' => 'Pintar',
                'option_c' => 'Malas',
                'option_d' => 'Lambat',
                'option_e' => 'Ceroboh',
                'correct_answer' => 'B',
                'raw_score' => 1,
                'weighted_score' => 5
            ],
            [
                'question_id' => 3,
                'subtest' => 'Verbal',
                'question_number' => 3,
                'question_text' => 'Antonim dari kata "Kaya" adalah?',
                'option_a' => 'Miskin',
                'option_b' => 'Kaya',
                'option_c' => 'Berkecukupan',
                'option_d' => 'Sederhana',
                'option_e' => 'Boros',
                'correct_answer' => 'A',
                'raw_score' => 1,
                'weighted_score' => 5
            ],
            [
                'question_id' => 4,
                'subtest' => 'Numerik',
                'question_number' => 1,
                'question_text' => 'Lanjutan dari deret: 2, 4, 6, 8, ...',
                'option_a' => '9',
                'option_b' => '10',
                'option_c' => '11',
                'option_d' => '12',
                'option_e' => '13',
                'correct_answer' => 'B',
                'raw_score' => 1,
                'weighted_score' => 5
            ],
            [
                'question_id' => 5,
                'subtest' => 'Numerik',
                'question_number' => 2,
                'question_text' => 'Hasil dari 15 + 27 adalah?',
                'option_a' => '40',
                'option_b' => '41',
                'option_c' => '42',
                'option_d' => '43',
                'option_e' => '44',
                'correct_answer' => 'C',
                'raw_score' => 1,
                'weighted_score' => 5
            ]
        ];
    }
    
    /**
     * Memeriksa apakah tabel tiki_questions ada
     * @return bool True jika tabel ada
     */
    public function checkTablesExist() {
        try {
            $result = $this->db->query("SHOW TABLES LIKE 'tiki_questions'");
            return $result && $result->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking tables: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Membuat tabel yang diperlukan jika belum ada
     * @return bool True jika berhasil
     */
    public function createTablesIfNotExist() {
        try {
            // Buat tabel tiki_questions jika belum ada
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS tiki_questions (
                    id INT(11) PRIMARY KEY AUTO_INCREMENT,
                    subtest VARCHAR(10) NOT NULL,
                    question_number INT(11) NOT NULL,
                    question_text TEXT NOT NULL,
                    option_a VARCHAR(255) NOT NULL,
                    option_b VARCHAR(255) NOT NULL,
                    option_c VARCHAR(255) NOT NULL,
                    option_d VARCHAR(255) NOT NULL,
                    option_e VARCHAR(255) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_question (subtest, question_number)
                )
            ");
            
            // Pastikan tiki_norms memiliki kolom subtest dan question_number
            $columns = $this->db->query("SHOW COLUMNS FROM tiki_norms")->fetchAll(PDO::FETCH_COLUMN);
            
            if (!in_array('subtest', $columns)) {
                $this->db->exec("ALTER TABLE tiki_norms ADD COLUMN subtest VARCHAR(10) AFTER id");
            }
            
            if (!in_array('question_number', $columns)) {
                $this->db->exec("ALTER TABLE tiki_norms ADD COLUMN question_number INT(11) AFTER subtest");
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating tables: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mengisi data contoh ke database
     * @return bool True jika berhasil
     */
    public function seedSampleData() {
        try {
            // Data contoh soal
            $sampleQuestions = [
                ['Verbal', 1, 'Manakah kata yang paling berbeda dari yang lain?', 'Meja', 'Kursi', 'Lemari', 'Lantai', 'Rak'],
                ['Verbal', 2, 'Sinonim dari kata "Cerdas" adalah?', 'Bodoh', 'Pintar', 'Malas', 'Lambat', 'Ceroboh'],
                ['Verbal', 3, 'Antonim dari kata "Kaya" adalah?', 'Miskin', 'Kaya', 'Berkecukupan', 'Sederhana', 'Boros'],
                ['Numerik', 1, 'Lanjutan dari deret: 2, 4, 6, 8, ...', '9', '10', '11', '12', '13'],
                ['Numerik', 2, 'Hasil dari 15 + 27 adalah?', '40', '41', '42', '43', '44'],
                ['Numerik', 3, 'Berapakah 25% dari 80?', '15', '20', '25', '30', '35'],
                ['Logika', 1, 'Jika semua manusia adalah makhluk hidup, dan Budi adalah manusia, maka:', 'Budi adalah makhluk hidup', 'Budi bukan makhluk hidup', 'Budi adalah tumbuhan', 'Budi adalah hewan', 'Tidak dapat disimpulkan'],
                ['Logika', 2, 'Semua kucing menyukai ikan. Tom adalah kucing. Kesimpulan yang tepat adalah:', 'Tom menyukai ikan', 'Tom tidak menyukai ikan', 'Tom adalah ikan', 'Tom bukan kucing', 'Tidak dapat disimpulkan']
            ];
            
            // Data contoh norma
            $sampleNorms = [
                ['Verbal', 1, 'D', 1, 5],
                ['Verbal', 2, 'B', 1, 5],
                ['Verbal', 3, 'A', 1, 5],
                ['Numerik', 1, 'B', 1, 5],
                ['Numerik', 2, 'C', 1, 5],
                ['Numerik', 3, 'B', 1, 5],
                ['Logika', 1, 'A', 1, 5],
                ['Logika', 2, 'A', 1, 5]
            ];
            
            // Masukkan data soal
            $stmtQuestion = $this->db->prepare("
                INSERT INTO tiki_questions (subtest, question_number, question_text, option_a, option_b, option_c, option_d, option_e) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                question_text = VALUES(question_text),
                option_a = VALUES(option_a),
                option_b = VALUES(option_b),
                option_c = VALUES(option_c),
                option_d = VALUES(option_d),
                option_e = VALUES(option_e)
            ");
            
            foreach ($sampleQuestions as $question) {
                $stmtQuestion->execute($question);
            }
            
            // Masukkan data norma
            $stmtNorm = $this->db->prepare("
                INSERT INTO tiki_norms (subtest, question_number, correct_answer, raw_score, weighted_score) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                correct_answer = VALUES(correct_answer),
                raw_score = VALUES(raw_score),
                weighted_score = VALUES(weighted_score)
            ");
            
            foreach ($sampleNorms as $norm) {
                $stmtNorm->execute($norm);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error seeding sample data: " . $e->getMessage());
            return false;
        }
    }
}
?>