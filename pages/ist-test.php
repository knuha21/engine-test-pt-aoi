<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pastikan class ISTTest ada
        if (!class_exists('ISTTest')) {
            throw new Exception('ISTTest class not found');
        }
        
        $istTest = new ISTTest();
        $jawaban = $_POST['answers'];
        $hasilOlahan = $istTest->prosesJawaban($jawaban);
        $grafik = $istTest->generateGrafikIST($hasilOlahan);
        
        // Simpan ke database
        $db = getDBConnection();
        if ($istTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Dapatkan ID test yang baru saja disimpan
            $testId = $db->lastInsertId();
            
            // Redirect ke results dengan ID
            header("Location: results.php?test=ist&id=" . $testId);
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Daftar subtest IST
$subtests = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IST Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Test IST</h1>
            <p>Alat tes untuk memetakan struktur kecerdasan (IQ) seseorang secara rinci.</p>
        </header>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <?php foreach ($subtests as $subtest): ?>
            <div class="subtest-section">
                <h2>Subtest <?php echo $subtest; ?></h2>
                
                <?php for ($i = 1; $i <= 10; $i++): ?>
                <div class="question">
                    <label>Soal <?php echo $i; ?>:</label>
                    <select name="answers[<?php echo $subtest; ?>][<?php echo $i; ?>]" required>
                        <option value="">Pilih Jawaban</option>
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
                <?php endfor; ?>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn-submit">Kirim Jawaban</button>
            <button type="button" onclick="window.history.back();" class="btn-back">Kembali</button>
        </form>
    </div>
</body>
</html>