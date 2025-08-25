<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pastikan class TIKITest ada
        if (!class_exists('TIKITest')) {
            throw new Exception('TIKITest class not found');
        }
        
        $tikiTest = new TIKITest();
        $jawaban = $_POST['answers'];
        $hasilOlahan = $tikiTest->prosesJawaban($jawaban);
        $grafik = $tikiTest->generateGrafik($hasilOlahan);
        
        // Simpan ke database
        $db = getDBConnection();
        if ($tikiTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Dapatkan ID test yang baru saja disimpan
            $testId = $db->lastInsertId();
            
            // Redirect ke results dengan ID
            header("Location: results.php?test=tiki&id=" . $testId);
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
    <title>TIKI Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Test TIKI</h1>
            <p>Alat test kecerdasan, hasil test berupa IQ</p>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <?php for ($subtest = 1; $subtest <= 5; $subtest++): ?>
            <div class="subtest-section">
                <h2>Subtest <?php echo $subtest; ?></h2>
                
                <?php for ($i = 1; $i <= 20; $i++): ?>
                <div class="question">
                    <label>Soal <?php echo $i; ?>:</label>
                    <input type="text" name="answers[<?php echo $subtest; ?>][<?php echo $i; ?>]" 
                           placeholder="Jawaban" required>
                </div>
                <?php endfor; ?>
            </div>
            <?php endfor; ?>
            
            <button type="submit" class="btn-submit">Kirim Jawaban</button>
        </form>
    </div>
</body>
</html>