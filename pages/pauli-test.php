<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pastikan class PAULITest ada
        if (!class_exists('PAULITest')) {
            throw new Exception('PAULITest class not found');
        }
        
        $pauliTest = new PAULITest();
        $lembarKerja = $_POST['worksheet'];
        $hasilKerja = $pauliTest->prosesLembarKerja($lembarKerja);
        
        // Hitung total dan rata-rata
        $totalScore = 0;
        $totalAverage = 0;
        $totalFluctuation = 0;
        $count = count($hasilKerja);
        
        foreach ($hasilKerja as $bagian) {
            $totalScore += $bagian['jumlah'];
            $totalAverage += $bagian['rata_rata'];
            $totalFluctuation += $bagian['fluktuasi'];
        }
        
        $overallAverage = $count > 0 ? $totalAverage / $count : 0;
        $overallFluctuation = $count > 0 ? $totalFluctuation / $count : 0;
        
        $interpretasi = $pauliTest->getInterpretasi($totalScore, $overallAverage, $overallFluctuation);
        
        $hasilOlahan = [
            'per_bagian' => $hasilKerja,
            'total' => [
                'score' => $totalScore,
                'rata_rata' => round($overallAverage, 2),
                'fluktuasi' => round($overallFluctuation, 2),
                'interpretasi' => $interpretasi
            ],
            'metadata' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'total_bagian' => count($hasilKerja)
            ]
        ];
        
        // Simpan ke database
        $db = getDBConnection();
        if ($pauliTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Dapatkan ID test yang baru saja disimpan
            $testId = $db->lastInsertId();
            
            // Redirect ke results dengan ID
            header("Location: results.php?test=pauli&id=" . $testId);
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAULI Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Test PAULI</h1>
            <p>Alat tes kepribadian (sikap kerja) dan daya tahan untuk melihat performa kerja dalam sebuah "maraton".</p>
        </header>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="test-instructions">
                <h2>Instruksi Pengerjaan</h2>
                <p>Kerjakan penjumlahan sederhana berikut dengan cepat dan konsisten.</p>
            </div>
            
            <?php for ($bagian = 1; $bagian <= 5; $bagian++): ?>
            <div class="worksheet-section">
                <h3>Bagian <?php echo $bagian; ?></h3>
                
                <table class="pauli-table">
                    <tr>
                        <th>No</th>
                        <th>Angka 1</th>
                        <th>Angka 2</th>
                        <th>Hasil</th>
                    </tr>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                    <tr>
                        <td><?php echo $i; ?></td>
                        <td><?php echo rand(1, 9); ?></td>
                        <td><?php echo rand(1, 9); ?></td>
                        <td>
                            <input type="number" name="worksheet[<?php echo $bagian; ?>][<?php echo $i; ?>]" 
                                   min="0" max="18" required>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </div>
            <?php endfor; ?>
            
            <button type="submit" class="btn-submit">Kirim Jawaban</button>
        </form>
    </div>
</body>
</html>