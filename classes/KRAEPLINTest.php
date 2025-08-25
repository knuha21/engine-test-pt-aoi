<?php
// Pastikan Database class sudah di-load
if (!class_exists('Database')) {
    require_once __DIR__ . '/Database.php';
}

class KRAEPLINTest {
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
        
        error_log("Processing Kraepelin worksheet: " . print_r($lembarKerja, true));
        
        // Hitung jumlah digit benar per kolom
        foreach ($lembarKerja as $kolom => $jawaban) {
            $hasil[$kolom] = $this->hitungKebenaranDigit($kolom, $jawaban);
            error_log("Kolom $kolom score: " . $hasil[$kolom]);
        }
        
        error_log("Final scores: " . print_r($hasil, true));
        return $hasil;
    }
    
    private function hitungKebenaranDigit($kolom, $jawaban) {
        $score = 0;
        
        // Ambil angka yang digunakan dari session
        if (isset($_SESSION['kraepelin_numbers'][$kolom])) {
            $numbers = $_SESSION['kraepelin_numbers'][$kolom];
            
            foreach ($jawaban as $index => $jawab) {
                if ($index >= 1 && $index <= count($numbers) - 1) {
                    $angka1 = $numbers[$index - 1];
                    $angka2 = $numbers[$index];
                    $hasilBenar = ($angka1 + $angka2) % 10; // Digit terakhir
                    
                    if (intval($jawab) === $hasilBenar) {
                        $score++;
                    }
                }
            }
        }
        
        return $score;
    }
    
    public function olahData($hasilKerja) {
        $totalBenar = array_sum($hasilKerja);
        $rataRata = count($hasilKerja) > 0 ? $totalBenar / count($hasilKerja) : 0;
        
        $hasil = [
            'total_benar' => $totalBenar,
            'rata_rata' => round($rataRata, 2),
            'konsistensi' => $this->hitungKonsistensi($hasilKerja),
            'interpretasi' => $this->getInterpretasi($totalBenar),
            'per_kolom' => $hasilKerja,
            'metadata' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_kolom' => count($hasilKerja)
            ]
        ];
        
        error_log("Processed results: " . print_r($hasil, true));
        return $hasil;
    }
    
    private function hitungKonsistensi($hasilKerja) {
        // Hitung standar deviasi untuk mengukur konsistensi
        if (count($hasilKerja) < 2) return 0;
        
        $mean = array_sum($hasilKerja) / count($hasilKerja);
        $variance = 0.0;
        
        foreach ($hasilKerja as $nilai) {
            $variance += pow($nilai - $mean, 2);
        }
        
        return round(sqrt($variance / count($hasilKerja)), 2);
    }
    
    private function getInterpretasi($totalBenar) {
        try {
            $query = "SELECT interpretation FROM kraepelin_norms WHERE 
                     :score BETWEEN SUBSTRING_INDEX(score_range, '-', 1) AND 
                     SUBSTRING_INDEX(score_range, '-', -1) LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":score", $totalBenar);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row['interpretation'];
            }
            
            return "Interpretasi tidak tersedia untuk skor ini";
        } catch (PDOException $e) {
            error_log("Error getting interpretation: " . $e->getMessage());
            return "Error dalam interpretasi hasil";
        }
    }
    
    public function simpanHasilTest($participant_id, $results) {
        try {
            $query = "INSERT INTO test_results (participant_id, test_type, results, created_at) 
                     VALUES (:participant_id, 'KRAEPLIN', :results, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":participant_id", $participant_id);
            
            $resultsJson = json_encode($results, JSON_UNESCAPED_UNICODE);
            
            // Pastikan JSON encoding berhasil
            if ($resultsJson === false) {
                error_log("JSON encoding failed: " . json_last_error_msg());
                return false;
            }
            
            $stmt->bindParam(":results", $resultsJson);
            
            $success = $stmt->execute();
            
            if ($success) {
                $lastId = $this->db->lastInsertId();
                error_log("Results saved successfully for participant: $participant_id, ID: $lastId");
                return $lastId; // Kembalikan ID, bukan boolean
            } else {
                $errorInfo = $stmt->errorInfo();
                error_log("Failed to save results for participant: $participant_id");
                error_log("SQL error: " . print_r($errorInfo, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log("Error saving KRAEPLIN test results: " . $e->getMessage());
            error_log("Error details: " . $e->getTraceAsString());
            return false;
        }
    }
}
?>