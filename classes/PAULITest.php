<?php
// Pastikan Database class sudah di-load
if (!class_exists('Database')) {
    require_once __DIR__ . '/Database.php';
}

class PAULITest {
    private $db;
    
    public function __construct() {
        // Pastikan Database class tersedia
        if (!class_exists('Database')) {
            throw new Exception('Database class not found');
        }
        
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function prosesLembarKerja($lembarKerja) {
        $hasil = [];
        
        // Implementasi perhitungan tes Pauli
        foreach ($lembarKerja as $bagian => $data) {
            $hasil[$bagian] = $this->hitungSkorPauli($data);
        }
        
        return $hasil;
    }
    
    private function hitungSkorPauli($data) {
        // Logika khusus penilaian Pauli
        $jumlah = array_sum($data);
        $rata_rata = count($data) > 0 ? $jumlah / count($data) : 0;
        
        return [
            'jumlah' => $jumlah,
            'rata_rata' => round($rata_rata, 2),
            'fluktuasi' => $this->hitungFluktuasi($data)
        ];
    }
    
    private function hitungFluktuasi($data) {
        if (count($data) < 2) return 0;
        
        $fluktuasi = 0;
        for ($i = 1; $i < count($data); $i++) {
            $fluktuasi += abs($data[$i] - $data[$i-1]);
        }
        
        return round($fluktuasi / (count($data) - 1), 2);
    }
    
    public function getInterpretasi($totalScore, $averageScore, $fluctuation) {
        try {
            $query = "SELECT interpretation FROM pauli_norms WHERE 
                     total_score <= :total_score AND
                     average_score <= :average_score AND
                     fluctuation >= :fluctuation
                     ORDER BY total_score DESC, average_score DESC, fluctuation ASC
                     LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":total_score", $totalScore);
            $stmt->bindParam(":average_score", $averageScore);
            $stmt->bindParam(":fluctuation", $fluctuation);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row['interpretation'];
            }
            
            return "Interpretasi tidak tersedia untuk hasil ini";
        } catch (PDOException $e) {
            error_log("Error getting interpretation: " . $e->getMessage());
            return "Error dalam interpretasi hasil";
        }
    }
    
    public function simpanHasilTest($participant_id, $results) {
        try {
            $query = "INSERT INTO test_results SET participant_id=:participant_id, test_type='PAULI', results=:results, created_at=NOW()";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":participant_id", $participant_id);
            $stmt->bindParam(":results", json_encode($results));
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error saving PAULI test results: " . $e->getMessage());
            return false;
        }
    }
    
    // Method baru untuk mengambil hasil dari database
    public function getHasilTest($participant_id, $test_id = null) {
        try {
            if ($test_id) {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND id = :test_id AND test_type = 'PAULI'";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(":participant_id", $participant_id);
                $stmt->bindParam(":test_id", $test_id);
            } else {
                $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = 'PAULI' ORDER BY created_at DESC";
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