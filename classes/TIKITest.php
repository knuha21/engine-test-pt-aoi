<?php
/**
 * Class TIKITest - Untuk menangani proses testing TIKI
 */
class TIKITest {
    private $db;
    private $subtests = [
        'berhitung_angka' => 'Berhitung Angka',
        'gabungan_bagian' => 'Gabungan Bagian', 
        'hubungan_angka' => 'Hubungan Angka',
        'abstraksi_non_verbal' => 'Abstraksi Non Verbal'
    ];
    
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
        $subtestScores = array_fill_keys(array_keys($this->subtests), 0);
        
        // Ambil semua norma dari database
        $norms = $this->getAllNorms();
        
        // Proses setiap jawaban
        foreach ($jawaban as $subtest => $questions) {
            if (!isset($subtestScores[$subtest])) continue;
            
            foreach ($questions as $questionNumber => $userAnswer) {
                $key = $subtest . '_' . $questionNumber;
                
                if (isset($norms[$key])) {
                    // Handle multiple answers (format: "A, C" seperti di PDF)
                    $userAnswers = array_map('trim', explode(',', $userAnswer));
                    $correctAnswers = array_map('trim', explode(',', $norms[$key]['correct_answer']));
                    
                    sort($userAnswers);
                    sort($correctAnswers);
                    
                    $isCorrect = ($userAnswers == $correctAnswers);
                    $score = $isCorrect ? $norms[$key]['weighted_score'] : 0;
                    
                    $hasil[] = [
                        'subtest' => $subtest,
                        'subtest_name' => $this->subtests[$subtest],
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
        
        // Konversi ke IQ
        $iqScore = $this->convertToIQ($totalScore);
        $iqCategory = $this->getIQCategory($iqScore);
        
        return [
            'answers' => $hasil,
            'total_score' => $totalScore,
            'iq_score' => $iqScore,
            'iq_category' => $iqCategory,
            'subtest_scores' => $subtestScores,
            'subtest_names' => $this->subtests,
            'test_date' => date('Y-m-d H:i:s'),
            'grafik' => $this->generateGrafik($totalScore, $subtestScores)
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
     * Mengkonversi skor total menjadi skor IQ berdasarkan norma TIKI
     * @param float $totalScore Skor total
     * @return float Skor IQ
     */
    private function convertToIQ($totalScore) {
        try {
            $query = $this->db->prepare("
                SELECT iq_score FROM tiki_conversion 
                WHERE total_score <= ? 
                ORDER BY total_score DESC 
                LIMIT 1
            ");
            
            if ($query->execute([$totalScore]) && $row = $query->fetch(PDO::FETCH_ASSOC)) {
                return $row['iq_score'];
            }
            
            // Fallback untuk skor di luar range
            if ($totalScore >= 96) return 145;
            if ($totalScore <= 21) return 56;
            
            return 100; // Default
        } catch (Exception $e) {
            error_log("Error converting to IQ: " . $e->getMessage());
            
            // Fallback table
            $iqConversionTable = [
                0 => 56,   5 => 61,   10 => 67,  15 => 73,  20 => 79,
                25 => 85,  30 => 91,  35 => 97,  40 => 103, 45 => 109,
                50 => 115, 55 => 120, 60 => 126, 65 => 132, 70 => 138,
                75 => 144, 80 => 150, 85 => 155, 90 => 161, 95 => 167,
                100 => 173
            ];
            
            foreach ($iqConversionTable as $score => $iq) {
                if ($totalScore <= $score) {
                    return $iq;
                }
            }
            
            return 145;
        }
    }
    
    /**
     * Mendapatkan kategori IQ
     */
    private function getIQCategory($iqScore) {
        if ($iqScore >= 130) return 'Sangat Superior';
        if ($iqScore >= 120) return 'Superior';
        if ($iqScore >= 110) return 'Di Atas Rata-rata';
        if ($iqScore >= 90) return 'Rata-rata';
        if ($iqScore >= 80) return 'Di Bawah Rata-rata';
        return 'Perlu Perhatian Khusus';
    }
    
    /**
     * Mendapatkan nama subtest
     */
    public function getSubtestName($subtestKey) {
        return $this->subtests[$subtestKey] ?? $subtestKey;
    }
    
    /**
     * Menghasilkan grafik hasil test sesuai format PDF
     * @param array $hasilOlahan Hasil olahan jawaban
     * @return string HTML/CSS untuk grafik
     */
    public function generateGrafik($totalScore, $subtestScores) {
        $maxScore = 40; // Skor maksimal berdasarkan grafik PDF
        
        $html = '<div class="grafik-container">';
        $html .= '<h3>Graphs IQ TIKI</h3>';
        $html .= '<div class="grafik-chart">';
        
        // Grid lines
        for ($i = $maxScore; $i >= 0; $i -= 4) {
            $html .= '<div class="grid-line" style="bottom: ' . ($i/$maxScore*100) . '%;">';
            $html .= '<span class="grid-label">' . $i . '</span>';
            $html .= '</div>';
        }
        
        // Bars untuk setiap subtest
        foreach ($this->subtests as $key => $name) {
            $score = $subtestScores[$key] ?? 0;
            $height = min(100, ($score / $maxScore) * 100);
            
            $html .= '<div class="chart-bar">';
            $html .= '<div class="bar" style="height: ' . $height . '%;" title="' . $name . ': ' . $score . '"></div>';
            $html .= '<span class="bar-label">' . $name . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="grafik-legend">';
        
        foreach ($this->subtests as $key => $name) {
            $html .= '<div class="legend-item">';
            $html .= '<span class="legend-label">' . $name . '</span>';
            $html .= '<span class="legend-value">: ' . ($subtestScores[$key] ?? 0) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '<div class="legend-total">';
        $html .= '<span class="legend-label">Skor IQ</span>';
        $html .= '<span class="legend-value">: ' . $this->convertToIQ($totalScore) . '</span>';
        $html .= '</div>';
        
        $html .= '</div></div>';
        
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
                SELECT r.*, p.name as participant_name, p.email, p.birth_date, p.phone, p.education, p.position, p.major 
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
                    q.option_f,
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
                'subtest' => 'berhitung_angka',
                'question_number' => 1,
                'question_text' => 'Soal berhitung angka 1',
                'option_a' => '10',
                'option_b' => '15',
                'option_c' => '20',
                'option_d' => '25',
                'option_e' => null,
                'option_f' => null,
                'correct_answer' => 'B',
                'raw_score' => 1,
                'weighted_score' => 1
            ],
            [
                'question_id' => 2,
                'subtest' => 'gabungan_bagian',
                'question_number' => 1,
                'question_text' => 'Soal gabungan bagian 1',
                'option_a' => 'Bagian A',
                'option_b' => 'Bagian B',
                'option_c' => 'Bagian C',
                'option_d' => 'Bagian D',
                'option_e' => 'Bagian E',
                'option_f' => 'Bagian F',
                'correct_answer' => 'A, C',
                'raw_score' => 1,
                'weighted_score' => 1
            ],
            [
                'question_id' => 3,
                'subtest' => 'hubungan_angka',
                'question_number' => 1,
                'question_text' => 'Soal hubungan angka 1',
                'option_a' => 'Pilihan A',
                'option_b' => 'Pilihan B',
                'option_c' => 'Pilihan C',
                'option_d' => 'Pilihan D',
                'option_e' => 'Pilihan E',
                'option_f' => 'Pilihan F',
                'correct_answer' => 'A, D',
                'raw_score' => 1,
                'weighted_score' => 1
            ],
            [
                'question_id' => 4,
                'subtest' => 'abstraksi_non_verbal',
                'question_number' => 1,
                'question_text' => 'Soal abstraksi non verbal 1',
                'option_a' => 'Gambar A',
                'option_b' => 'Gambar B',
                'option_c' => 'Gambar C',
                'option_d' => 'Gambar D',
                'option_e' => 'Gambar E',
                'option_f' => 'Gambar F',
                'correct_answer' => 'B, C',
                'raw_score' => 1,
                'weighted_score' => 1
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
                    subtest VARCHAR(20) NOT NULL,
                    question_number INT(11) NOT NULL,
                    question_text TEXT NOT NULL,
                    option_a VARCHAR(255) NOT NULL,
                    option_b VARCHAR(255) NOT NULL,
                    option_c VARCHAR(255) NOT NULL,
                    option_d VARCHAR(255) NOT NULL,
                    option_e VARCHAR(255) NULL,
                    option_f VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_question (subtest, question_number)
                )
            ");

            // Buat tabel tiki_norms jika belum ada
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS tiki_norms (
                    id INT(11) PRIMARY KEY AUTO_INCREMENT,
                    subtest VARCHAR(20) NOT NULL,
                    question_number INT(11) NOT NULL,
                    correct_answer VARCHAR(10) NOT NULL,
                    raw_score INT(11) NOT NULL,
                    weighted_score INT(11) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_norm (subtest, question_number)
                )
            ");
            
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
            // Data contoh soal Berhitung Angka
            $berhitungSoal = [
                [1, 'Hasil dari 5 + 7 adalah?', '10', '12', '14', '16', null, null, 'B'],
                [2, 'Hasil dari 15 - 8 adalah?', '5', '7', '9', '11', null, null, 'B'],
                [3, 'Hasil dari 6 × 4 adalah?', '18', '20', '24', '28', null, null, 'C'],
                [4, 'Hasil dari 36 ÷ 6 adalah?', '4', '5', '6', '7', null, null, 'C']
            ];
            
            // Data contoh soal Gabungan Bagian (multiple answers)
            $gabunganSoal = [
                [1, 'Pilih dua bagian yang membentuk gambar utuh:', 'A', 'B', 'C', 'D', 'E', 'F', 'A, C'],
                [2, 'Pilih dua bagian yang saling melengkapi:', 'A', 'B', 'C', 'D', 'E', 'F', 'A, D'],
                [3, 'Pilih dua elemen yang berhubungan:', 'A', 'B', 'C', 'D', 'E', 'F', 'D, F'],
                [4, 'Pilih dua komponen yang sesuai:', 'A', 'B', 'C', 'D', 'E', 'F', 'B, E']
            ];
            
            // Data contoh soal Hubungan Angka
            $hubunganSoal = [
                [1, 'Pilih dua angka yang berhubungan:', '5', '10', '15', '20', '25', '30', 'A, D'],
                [2, 'Pilih dua pola yang sesuai:', '2', '4', '6', '8', '10', '12', 'A, C'],
                [3, 'Pilih dua urutan yang benar:', '3', '6', '9', '12', '15', '18', 'A, C'],
                [4, 'Pilih dua hubungan yang logis:', '1', '3', '5', '7', '9', '11', 'A, B']
            ];
            
            // Data contoh soal Abstraksi Non Verbal
            $abstraksiSoal = [
                [1, 'Pilih dua bentuk yang serupa:', 'Segitiga', 'Kotak', 'Lingkaran', 'Bintang', 'Hexagon', 'Trapesium', 'B, C'],
                [2, 'Pilih dua pola yang match:', 'Pola A', 'Pola B', 'Pola C', 'Pola D', 'Pola E', 'Pola F', 'D, F'],
                [3, 'Pilih dua gambar yang berhubungan:', 'Gbr A', 'Gbr B', 'Gbr C', 'Gbr D', 'Gbr E', 'Gbr F', 'A, D'],
                [4, 'Pilih dua simbol yang sesuai:', 'Sim A', 'Sim B', 'Sim C', 'Sim D', 'Sim E', 'Sim F', 'B, E']
            ];
            
            // Masukkan data soal
            $stmtQuestion = $this->db->prepare("
                INSERT INTO tiki_questions 
                (subtest, question_number, question_text, option_a, option_b, option_c, option_d, option_e, option_f) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                question_text = VALUES(question_text),
                option_a = VALUES(option_a),
                option_b = VALUES(option_b),
                option_c = VALUES(option_c),
                option_d = VALUES(option_d),
                option_e = VALUES(option_e),
                option_f = VALUES(option_f)
            ");
            
            // Masukkan data norma
            $stmtNorm = $this->db->prepare("
                INSERT INTO tiki_norms (subtest, question_number, correct_answer, raw_score, weighted_score) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                correct_answer = VALUES(correct_answer),
                raw_score = VALUES(raw_score),
                weighted_score = VALUES(weighted_score)
            ");
            
            // Insert Berhitung Angka
            foreach ($berhitungSoal as $soal) {
                $stmtQuestion->execute(['berhitung_angka', $soal[0], $soal[1], $soal[2], $soal[3], $soal[4], $soal[5], $soal[6], $soal[7]]);
                $stmtNorm->execute(['berhitung_angka', $soal[0], $soal[8], 1, 1]);
            }
            
            // Insert Gabungan Bagian
            foreach ($gabunganSoal as $soal) {
                $stmtQuestion->execute(['gabungan_bagian', $soal[0], $soal[1], $soal[2], $soal[3], $soal[4], $soal[5], $soal[6], $soal[7]]);
                $stmtNorm->execute(['gabungan_bagian', $soal[0], $soal[8], 1, 1]);
            }
            
            // Insert Hubungan Angka
            foreach ($hubunganSoal as $soal) {
                $stmtQuestion->execute(['hubungan_angka', $soal[0], $soal[1], $soal[2], $soal[3], $soal[4], $soal[5], $soal[6], $soal[7]]);
                $stmtNorm->execute(['hubungan_angka', $soal[0], $soal[8], 1, 1]);
            }
            
            // Insert Abstraksi Non Verbal
            foreach ($abstraksiSoal as $soal) {
                $stmtQuestion->execute(['abstraksi_non_verbal', $soal[0], $soal[1], $soal[2], $soal[3], $soal[4], $soal[5], $soal[6], $soal[7]]);
                $stmtNorm->execute(['abstraksi_non_verbal', $soal[0], $soal[8], 1, 1]);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error seeding sample data: " . $e->getMessage());
            return false;
        }
    }
}
?>