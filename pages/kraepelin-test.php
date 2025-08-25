<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pastikan class KRAEPLINTest ada
        if (!class_exists('KRAEPLINTest')) {
            throw new Exception('KRAEPLINTest class not found');
        }
        
        $kraepelinTest = new KRAEPLINTest();
        $lembarKerja = $_POST['worksheet'];
        
        // Debug: lihat data yang diterima
        error_log("Kraepelin data received: " . print_r($lembarKerja, true));
        
        $hasilKerja = $kraepelinTest->prosesLembarKerja($lembarKerja);
        $hasilOlahan = $kraepelinTest->olahData($hasilKerja);
        
        // Debug: lihat hasil olahan
        error_log("Kraepelin results: " . print_r($hasilOlahan, true));
        
        // Simpan ke database
        $db = getDBConnection();
        if ($kraepelinTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Dapatkan ID test yang baru saja disimpan
            $testId = $db->lastInsertId();
            
            // Debug: lihat ID test
            error_log("Test saved with ID: " . $testId);
            
            // Redirect ke results dengan ID
            header("Location: results.php?test=kraepelin&id=" . $testId);
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
            error_log("Failed to save Kraepelin test results");
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
        error_log("Kraepelin test error: " . $e->getMessage());
    }
}

// Generate numbers untuk test (acak untuk setiap load halaman)
$worksheetData = [];
for ($kolom = 1; $kolom <= 10; $kolom++) {
    $numbers = [];
    for ($i = 0; $i < 15; $i++) {
        $numbers[] = rand(0, 9);
    }
    $worksheetData[$kolom] = $numbers;
}

// Simpan di session untuk referensi penilaian
$_SESSION['kraepelin_numbers'] = $worksheetData;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRAEPLIN Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .worksheet-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .number-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .number {
            display: inline-block;
            width: 30px;
            height: 30px;
            text-align: center;
            line-height: 30px;
            background-color: #3498db;
            color: white;
            border-radius: 4px;
            font-weight: bold;
        }
        .answer-row {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-bottom: 15px;
        }
        .answer-row input {
            width: 40px;
            height: 40px;
            text-align: center;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 4px;
        }
        .answer-row input:focus {
            border-color: #3498db;
            outline: none;
        }
        .operator {
            font-weight: bold;
            margin: 0 5px;
        }
        .debug-info {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Test KRAEPLIN</h1>
            <p>Alat tes kinerja dan kecepatan untuk melihat performa kerja dalam "sprint" pendek yang berulang.</p>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <h3>Error:</h3>
            <?php echo $error; ?>
            <p>Silakan refresh halaman dan coba lagi, atau hubungi administrator.</p>
        </div>
        <?php endif; ?>
        
        <?php if (APP_DEBUG && isset($_POST['worksheet'])): ?>
        <div class="debug-info">
            <h3>Debug Info (Data yang dikirim):</h3>
            <pre><?php print_r($_POST['worksheet']); ?></pre>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="kraepelinForm">
            <div class="test-instructions">
                <h2>Instruksi Pengerjaan</h2>
                <p>Tambahkan dua angka yang berdekatan dan tulis digit terakhir dari hasil penjumlahan tersebut.</p>
                <p><strong>Contoh:</strong> 5 + 8 = 13 → tulis <strong>3</strong></p>
                <p>Waktu pengerjaan: 5 menit</p>
            </div>
            
            <?php foreach ($worksheetData as $kolom => $numbers): ?>
            <div class="worksheet-section">
                <h3>Kolom <?php echo $kolom; ?></h3>
                
                <div class="number-row">
                    <?php for ($i = 0; $i < count($numbers); $i++): ?>
                    <span class="number"><?php echo $numbers[$i]; ?></span>
                    <?php if ($i < count($numbers) - 1): ?>
                    <span class="operator">+</span>
                    <?php endif; ?>
                    <?php endfor; ?>
                </div>
                
                <div class="answer-row">
                    <?php for ($i = 0; $i < count($numbers) - 1; $i++): ?>
                    <input type="text" name="worksheet[<?php echo $kolom; ?>][<?php echo $i + 1; ?>]" 
                           maxlength="1" pattern="[0-9]" required 
                           title="Masukkan digit terakhir dari penjumlahan"
                           class="kraepelin-input"
                           data-kolom="<?php echo $kolom; ?>"
                           data-index="<?php echo $i + 1; ?>">
                    <?php endfor; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <button type="submit" class="btn-submit" id="submitBtn">Kirim Jawaban</button>
        </form>
    </div>

    <script>
        // Auto-focus dan auto-tab untuk memudahkan input
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.kraepelin-input');
            
            inputs.forEach((input, index) {
                // Auto-tab ke next input setelah mengisi
                input.addEventListener('input', function() {
                    if (this.value.length === 1 && index < inputs.length - 1) {
                        inputs[index + 1].focus();
                    }
                });
                
                // Validasi input hanya angka
                input.addEventListener('keypress', function(e) {
                    if (e.key < '0' || e.key > '9') {
                        e.preventDefault();
                    }
                });
            });
            
            // Validasi form sebelum submit
            document.getElementById('kraepelinForm').addEventListener('submit', function(e) {
                const emptyInputs = document.querySelectorAll('.kraepelin-input:invalid');
                if (emptyInputs.length > 0) {
                    e.preventDefault();
                    alert('Silakan lengkapi semua jawaban sebelum mengirim.');
                    emptyInputs[0].focus();
                }
            });
        });
    </script>
</body>
</html>