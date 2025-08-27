<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Ambil data peserta dari database
$db = getDBConnection();
$participant_id = $_SESSION['participant_id'];
$participant_query = $db->prepare("SELECT name, email FROM participants WHERE id = ?");
$participant_query->execute([$participant_id]);
$participant = $participant_query->fetch(PDO::FETCH_ASSOC);

// Jika tidak ada data peserta, redirect ke login
if (!$participant) {
    header("Location: login.php");
    exit();
}

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
        
        // Simpan ke database
        if ($istTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Redirect ke results
            header("Location: results.php?test=ist");
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil data soal IST
$istTest = new ISTTest();
$soal = $istTest->getAllSoal();

// Kelompokkan soal berdasarkan subtest
$subtests = [];
foreach ($soal as $item) {
    $subtestCode = $item['subtest'];
    if (!isset($subtests[$subtestCode])) {
        $subtests[$subtestCode] = [
            'name' => $item['subtest_name'] ?? $subtestCode,
            'questions' => []
        ];
    }
    $subtests[$subtestCode]['questions'][] = $item;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IST Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .test-info {
            background-color: #e8f4fc;
            padding: 15px 20px;
            border-bottom: 1px solid #bee5eb;
            font-size: 0.9rem;
        }
        
        .instructions {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .instructions h3 {
            color: #ff6b6b;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .instructions ul {
            padding-left: 20px;
            margin-bottom: 10px;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
        
        .timer-container {
            background-color: #ff6b6b;
            color: white;
            padding: 15px 20px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .test-content {
            padding: 25px;
        }
        
        .subtest-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
        }
        
        .subtest-header {
            background-color: #ff6b6b;
            color: white;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .subtest-title {
            font-size: 1.3rem;
            margin: 0;
        }
        
        .question {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 6px;
            border-left: 4px solid #ff6b6b;
        }
        
        .question-text {
            font-size: 1.1rem;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .options-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .options-container {
                grid-template-columns: 1fr;
            }
        }
        
        .option {
            display: flex;
            align-items: center;
            padding: 12px;
            background-color: white;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .option:hover {
            background-color: #f0f0f0;
            border-color: #ccc;
        }
        
        .option.selected {
            background-color: #ffecec;
            border-color: #ff6b6b;
        }
        
        .option input {
            margin-right: 10px;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .btn-prev {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-prev:hover:not(:disabled) {
            background-color: #5a6268;
        }
        
        .btn-prev:disabled {
            background-color: #ced4da;
            cursor: not-allowed;
        }
        
        .btn-next {
            background-color: #ff6b6b;
            color: white;
        }
        
        .btn-next:hover {
            background-color: #ff5252;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        
        .progress-container {
            background-color: white;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .progress-bar {
            height: 10px;
            background-color: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #ff6b6b 0%, #ff8e8e 100%);
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .subtest-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .subtest-nav-btn {
            padding: 8px 15px;
            background-color: #e9ecef;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .subtest-nav-btn:hover {
            background-color: #dee2e6;
        }
        
        .subtest-nav-btn.active {
            background-color: #ff6b6b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>IST Test (Intelligenz Struktur Test)</h1>
            <p class="subtitle">PT. Apparel One Indonesia</p>
        </header>
        
        <div class="test-info">
            <p><strong>Peserta:</strong> <?php echo htmlspecialchars($participant['name']); ?> | <strong>Email:</strong> <?php echo htmlspecialchars($participant['email']); ?></p>
        </div>
        
        <div class="instructions">
            <h3>Petunjuk Pengerjaan</h3>
            <ul>
                <li>IST Test mengukur berbagai kemampuan kognitif dan inteligensi</li>
                <li>Test terdiri dari beberapa subtest dengan jenis soal yang berbeda</li>
                <li>Pilih satu jawaban yang paling tepat untuk setiap soal</li>
                <li>Waktu pengerjaan: 60 menit untuk seluruh test</li>
                <li>Kerjakan dengan jujur sesuai kemampuan Anda sendiri</li>
                <li>Jawaban tidak dapat diubah setelah beralih ke subtest berikutnya</li>
            </ul>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="timer-container">
            <div id="timer">Waktu: 60:00</div>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress" id="progress-bar" style="width: 0%"></div>
            </div>
            <div class="progress-text">
                <span id="progress-current">Subtest 0 dari <?php echo count($subtests); ?></span>
                <span id="progress-percentage">0% selesai</span>
            </div>
        </div>
        
        <div class="subtest-nav">
            <?php $subtestIndex = 0; ?>
            <?php foreach ($subtests as $code => $subtest): ?>
            <button type="button" class="subtest-nav-btn <?php echo $subtestIndex === 0 ? 'active' : ''; ?>" 
                    data-subtest="<?php echo $code; ?>" onclick="showSubtest('<?php echo $code; ?>')">
                <?php echo $subtest['name']; ?>
            </button>
            <?php $subtestIndex++; ?>
            <?php endforeach; ?>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="ist-test-form">
            <?php $subtestIndex = 0; ?>
            <?php foreach ($subtests as $code => $subtest): ?>
            <div class="subtest-section" id="subtest-<?php echo $code; ?>" style="<?php echo $subtestIndex > 0 ? 'display: none;' : ''; ?>">
                <div class="subtest-header">
                    <h3 class="subtest-title"><?php echo $subtest['name']; ?> (<?php echo $code; ?>)</h3>
                </div>
                
                <?php foreach ($subtest['questions'] as $question): ?>
                <div class="question">
                    <div class="question-text">
                        <strong>Soal <?php echo $question['question_number']; ?>:</strong> 
                        <?php echo htmlspecialchars($question['question_text']); ?>
                    </div>
                    
                    <div class="options-container">
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $code; ?>][<?php echo $question['question_number']; ?>]" value="A" required>
                            <span>A. <?php echo htmlspecialchars($question['option_a']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $code; ?>][<?php echo $question['question_number']; ?>]" value="B">
                            <span>B. <?php echo htmlspecialchars($question['option_b']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $code; ?>][<?php echo $question['question_number']; ?>]" value="C">
                            <span>C. <?php echo htmlspecialchars($question['option_c']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $code; ?>][<?php echo $question['question_number']; ?>]" value="D">
                            <span>D. <?php echo htmlspecialchars($question['option_d']); ?></span>
                        </label>
                        
                        <label class="option">
                            <input type="radio" name="answers[<?php echo $code; ?>][<?php echo $question['question_number']; ?>]" value="E">
                            <span>E. <?php echo htmlspecialchars($question['option_e']); ?></span>
                        </label>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <div class="navigation">
                    <button type="button" class="btn btn-prev" onclick="showPrevSubtest()" <?php echo $subtestIndex === 0 ? 'disabled' : ''; ?>>Subtest Sebelumnya</button>
                    
                    <?php if ($subtestIndex < count($subtests) - 1): ?>
                    <button type="button" class="btn btn-next" onclick="validateAndNext('<?php echo $code; ?>')">Subtest Berikutnya</button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-submit">Kirim Jawaban</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php $subtestIndex++; ?>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        // Data subtest
        const subtests = <?php echo json_encode(array_keys($subtests)); ?>;
        let currentSubtestIndex = 0;
        let timeLeft = 60 * 60; // 60 menit dalam detik
        
        // Timer untuk test IST
        const timerElement = document.getElementById('timer');
        
        function updateTimer() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `Waktu: ${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                // Waktu habis, submit form secara otomatis
                alert('Waktu telah habis! Jawaban akan disubmit secara otomatis.');
                document.getElementById('ist-test-form').submit();
            }
        }
        
        // Tampilkan subtest tertentu
        function showSubtest(subtestCode) {
            // Sembunyikan semua subtest
            document.querySelectorAll('.subtest-section').forEach(section => {
                section.style.display = 'none';
            });
            
            // Tampilkan subtest yang dipilih
            document.getElementById('subtest-' + subtestCode).style.display = 'block';
            
            // Update tombol navigasi
            updateSubtestNavButtons(subtestCode);
            
            // Update progress
            currentSubtestIndex = subtests.indexOf(subtestCode);
            updateProgress();
        }
        
        // Tampilkan subtest berikutnya
        function showNextSubtest() {
            if (currentSubtestIndex < subtests.length - 1) {
                showSubtest(subtests[currentSubtestIndex + 1]);
            }
        }
        
        // Tampilkan subtest sebelumnya
        function showPrevSubtest() {
            if (currentSubtestIndex > 0) {
                showSubtest(subtests[currentSubtestIndex - 1]);
            }
        }
        
        // Validasi dan lanjut ke subtest berikutnya
        function validateAndNext(subtestCode) {
            const currentSubtest = document.getElementById('subtest-' + subtestCode);
            const inputs = currentSubtest.querySelectorAll('input[type="radio"]:checked');
            
            if (inputs.length < currentSubtest.querySelectorAll('.question').length) {
                alert('Silakan jawab semua soal sebelum melanjutkan ke subtest berikutnya.');
                return;
            }
            
            showNextSubtest();
        }
        
        // Update tombol navigasi subtest
        function updateSubtestNavButtons(activeSubtest) {
            document.querySelectorAll('.subtest-nav-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.subtest === activeSubtest);
            });
        }
        
        // Update progress bar
        function updateProgress() {
            const progress = ((currentSubtestIndex + 1) / subtests.length) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('progress-current').textContent = `Subtest ${currentSubtestIndex + 1} dari ${subtests.length}`;
            document.getElementById('progress-percentage').textContent = `${Math.round(progress)}% selesai`;
        }
        
        // Event listener untuk opsi jawaban
        document.addEventListener('DOMContentLoaded', function() {
            // Tambahkan event listener untuk semua opsi
            document.querySelectorAll('.option').forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    
                    // Hapus selected dari semua opsi dalam question yang sama
                    const question = this.closest('.question');
                    question.querySelectorAll('.option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Tambahkan selected ke opsi yang diklik
                    this.classList.add('selected');
                });
            });
            
            // Mulai timer
            updateTimer();
            
            // Inisialisasi progress
            updateProgress();
        });
    </script>
</body>
</html>