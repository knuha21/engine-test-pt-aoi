<?php
// Pastikan Database class sudah di-load
if (!class_exists('Database')) {
    require_once __DIR__ . '/Database.php';
}

class TIKITest {
    private $db;
    private $kunciJawaban = [];
    private $norma = [];
    
    public function __construct() {
        // Pastikan Database class tersedia
        if (!class_exists('Database')) {
            throw new Exception('Database class not found');
        }
        
        $database = new Database();
        $this->db = $database->getConnection();
        $this->loadKunciDanNorma();
    }
    
    private function loadKunciDanNorma() {
        try {
            // Load kunci jawaban dan norma dari database
            $query = "SELECT * FROM tiki_norms ORDER BY subtest, question_number";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->kunciJawaban[$row['subtest']][$row['question_number']] = $row['correct_answer'];
                $this->norma[$row['subtest']][$row['raw_score']] = $row['weighted_score'];
            }
        } catch (PDOException $e) {
            error_log("Error loading TIKI norms: " . $e->getMessage());
        }
    }
    
    public function prosesJawaban($jawabanPeserta) {
        $hasil = [];
        
        foreach ($jawabanPeserta as $subtest => $jawaban) {
            $rawScore = $this->hitungRawScore($subtest, $jawaban);
            $weightedScore = $this->konversiKeWeightedScore($subtest, $rawScore);
            $hasil[$subtest] = [
                'raw_score' => $rawScore,
                'weighted_score' => $weightedScore
            ];
        }
        
        // Tambahkan timestamp
        $hasil['metadata'] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_subtest' => count($jawabanPeserta)
        ];
        
        return $hasil;
    }
    
    private function hitungRawScore($subtest, $jawaban) {
        $score = 0;
        if (isset($this->kunciJawaban[$subtest])) {
            foreach ($jawaban as $noSoal => $jawab) {
                if (isset($this->kunciJawaban[$subtest][$noSoal]) && 
                    strtoupper(trim($jawab)) == strtoupper(trim($this->kunciJawaban[$subtest][$noSoal]))) {
                    $score++;
                }
            }
        }
        return $score;
    }
    
    private function konversiKeWeightedScore($subtest, $rawScore) {
        if (isset($this->norma[$subtest]) && isset($this->norma[$subtest][$rawScore])) {
            return $this->norma[$subtest][$rawScore];
        }
        return 0;
    }
    
    public function generateGrafik($hasilOlahan) {
        $dataGrafik = [];
        foreach ($hasilOlahan as $subtest => $score) {
            if (is_array($score) && isset($score['weighted_score'])) {
                $dataGrafik[] = [
                    'subtest' => "Subtest " . $subtest,
                    'weighted_score' => $score['weighted_score']
                ];
            }
        }
        return $dataGrafik;
    }
    
    public function simpanHasilTest($participant_id, $results) {
        try {
            $query = "INSERT INTO test_results SET participant_id=:participant_id, test_type='TIKI', results=:results, created_at=NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":participant_id", $participant_id);
            $stmt->bindParam(":results", json_encode($results));
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error saving TIKI test results: " . $e->getMessage());
            return false;
        }
    }
    
    // Method baru untuk mengambil hasil dari database
    public function getHasilTest($participant_id, $test_id = null) {
        try {
            if ($test_id) {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND id = :test_id AND test_type = 'TIKI'";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":participant_id", $participant_id);
                $stmt->bindParam(":test_id", $test_id);
            } else {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = 'TIKI' ORDER BY created_at DESC";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":participant_id", $participant_id);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting test results: " . $e->getMessage());
            return [];
        }
    }
}
?>