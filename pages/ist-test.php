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
            $error = "Gagal menyimpan hasil test. Silakan coka lagi.";
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
            position: relative;
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
        
        /* Floating Elements */
        .floating-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            font-size: 1.2rem;
            font-weight: bold;
            min-width: 120px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .floating-progress {
            position: fixed;
            top: 90px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            min-width: 150px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .progress-circle {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
        }
        
        .progress-circle svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .progress-circle-bg {
            fill: none;
            stroke: #e6e6e6;
            stroke-width: 3.8;
        }
        
        .progress-circle-fg {
            fill: none;
            stroke: #ff6b6b;
            stroke-width: 4;
            stroke-linecap: round;
            transition: stroke-dashoffset 0.3s ease;
        }
        
        .progress-text {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .floating-subtest-info {
            position: fixed;
            top: 190px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            min-width: 150px;
            text-align: center;
            font-size: 0.9rem;
            transition: transform 0.3s ease;
        }
        
        .test-content {
            padding: 25px;
            margin-top: 30px;
        }
        
        .subtest-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
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
            transition: all 0.3s ease;
        }
        
        .question:target {
            background-color: #fff4f4;
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
            
            .floating-timer {
                top: 10px;
                right: 10px;
                font-size: 1rem;
                padding: 10px 15px;
                min-width: 100px;
            }
            
            .floating-progress {
                top: 70px;
                right: 10px;
                padding: 10px;
                min-width: 120px;
            }
            
            .progress-circle {
                width: 50px;
                height: 50px;
            }
            
            .floating-subtest-info {
                top: 140px;
                right: 10px;
                padding: 10px;
                min-width: 120px;
            }
            
            .mobile-nav {
                display: flex;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                padding: 15px;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                z-index: 999;
                justify-content: space-between;
            }
            
            .desktop-nav {
                display: none;
            }
            
            .test-content {
                padding-bottom: 80px;
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
        
        .progress-text-inline {
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
            padding: 10px 18px;
            background-color: #e9ecef;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .subtest-nav-btn:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
        }
        
        .subtest-nav-btn.active {
            background-color: #ff6b6b;
            color: white;
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.3);
        }
        
        .mobile-progress {
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #ff6b6b;
        }
        
        /* Smooth scrolling for the whole page */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <div class="container">        
        <!-- Floating Timer -->
        <div class="floating-timer" id="floating-timer">
            <div id="timer">60:00</div>
        </div>
        
        <!-- Floating Progress -->
        <div class="floating-progress" id="floating-progress">
            <div class="progress-circle">
                <svg viewBox="0 0 36 36">
                    <circle class="progress-circle-bg" cx="18" cy="18" r="15.9"></circle>
                    <circle class="progress-circle-fg" cx="18" cy="18" r="15.9" 
                            stroke-dasharray="100, 100" id="progress-circle"></circle>
                </svg>
            </div>
            <div class="progress-text">
                <span id="progress-percentage">0%</span> selesai
            </div>
        </div>
        
        <!-- Floating Subtest Info -->
        <div class="floating-subtest-info" id="floating-subtest-info">
            <div id="current-subtest">Subtest 1/6</div>
            <div id="subtest-name">Kosa Kata</div>
        </div>
        
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
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress" id="progress-bar" style="width: 0%"></div>
            </div>
            <div class="progress-text-inline">
                <span id="progress-current">Subtest 0 dari <?php echo count($subtests); ?></span>
                <span id="progress-percentage-inline">0% selesai</span>
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
            <div class="test-content">
                <?php $subtestIndex = 0; ?>
                <?php foreach ($subtests as $code => $subtest): ?>
                <div class="subtest-section" id="subtest-<?php echo $code; ?>" style="<?php echo $subtestIndex > 0 ? 'display: none;' : ''; ?>">
                    <div class="subtest-header">
                        <h3 class="subtest-title"><?php echo $subtest['name']; ?> (<?php echo $code; ?>)</h3>
                    </div>
                    
                    <?php foreach ($subtest['questions'] as $question): ?>
                    <div class="question" id="question-<?php echo $code; ?>-<?php echo $question['question_number']; ?>">
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
                    
                    <div class="navigation desktop-nav">
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
            </div>
        </form>
    </div>

    <script>
        // Data subtest
        const subtests = <?php echo json_encode(array_keys($subtests)); ?>;
        let currentSubtestIndex = 0;
        let timeLeft = 60 * 60; // 60 menit dalam detik
        const totalTime = timeLeft;
        
        // Fungsi untuk scroll ke atas halaman dengan efek smooth
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
        
        // Timer untuk test IST
        function updateTimer() {
            const hours = Math.floor(timeLeft / 3600);
            const minutes = Math.floor((timeLeft % 3600) / 60);
            const seconds = timeLeft % 60;
            
            let timeText;
            if (hours > 0) {
                timeText = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            } else {
                timeText = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }
            
            document.getElementById('timer').textContent = timeText;
            
            // Update progress circle color based on time remaining
            const progressCircle = document.getElementById('progress-circle');
            const percentage = (timeLeft / totalTime) * 100;
            
            if (percentage < 20) {
                progressCircle.style.stroke = '#dc3545'; // Red for critical time
            } else if (percentage < 40) {
                progressCircle.style.stroke = '#ffc107'; // Yellow for warning
            } else {
                progressCircle.style.stroke = '#ff6b6b'; // Normal color
            }
            
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
            const targetSubtest = document.getElementById('subtest-' + subtestCode);
            targetSubtest.style.display = 'block';
            
            // Update tombol navigasi
            updateSubtestNavButtons(subtestCode);
            
            // Update progress
            currentSubtestIndex = subtests.indexOf(subtestCode);
            updateProgress();
            
            // Scroll ke atas halaman dengan efek smooth
            scrollToTop();
            
            // Focus ke question pertama setelah delay kecil
            setTimeout(() => {
                const firstQuestion = targetSubtest.querySelector('.question');
                if (firstQuestion) {
                    // Tambahkan ID untuk question pertama
                    firstQuestion.id = `first-question-${subtestCode}`;
                    
                    // Scroll ke question pertama
                    firstQuestion.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    // Focus ke input pertama
                    const firstInput = firstQuestion.querySelector('input[type="radio"]');
                    if (firstInput) {
                        firstInput.focus();
                    }
                }
            }, 300);
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
                
                // Scroll ke question pertama yang belum dijawab
                const questions = currentSubtest.querySelectorAll('.question');
                for (let i = 0; i < questions.length; i++) {
                    const questionInputs = questions[i].querySelectorAll('input[type="radio"]:checked');
                    if (questionInputs.length === 0) {
                        questions[i].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        break;
                    }
                }
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
        
        // Update mobile navigation buttons
        function updateMobileNavigation() {
            const mobilePrevBtn = document.getElementById('mobile-prev-btn');
            const mobileNextBtn = document.getElementById('mobile-next-btn');
            const mobileProgress = document.getElementById('mobile-progress');
            
            if (mobilePrevBtn) {
                mobilePrevBtn.disabled = currentSubtestIndex === 0;
            }
            
            if (mobileNextBtn) {
                if (currentSubtestIndex === subtests.length - 1) {
                    mobileNextBtn.style.display = 'none';
                    // Tambahkan tombol submit di mobile
                    if (!document.getElementById('mobile-submit-btn')) {
                        const submitBtn = document.createElement('button');
                        submitBtn.id = 'mobile-submit-btn';
                        submitBtn.className = 'btn btn-submit';
                        submitBtn.textContent = 'Submit';
                        submitBtn.onclick = function() { document.getElementById('ist-test-form').submit(); };
                        mobileNextBtn.parentNode.replaceChild(submitBtn, mobileNextBtn);
                    }
                } else {
                    mobileNextBtn.textContent = 'Berikutnya →';
                    mobileNextBtn.style.display = 'block';
                }
            }
            
            if (mobileProgress) {
                mobileProgress.textContent = `${currentSubtestIndex + 1}/${subtests.length}`;
            }
        }
        
        // Update progress bar
        function updateProgress() {
            const progress = ((currentSubtestIndex + 1) / subtests.length) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('progress-current').textContent = `Subtest ${currentSubtestIndex + 1} dari ${subtests.length}`;
            document.getElementById('progress-percentage-inline').textContent = `${Math.round(progress)}% selesai`;
            document.getElementById('progress-percentage').textContent = `${Math.round(progress)}%`;
            
            // Update progress circle
            const progressCircle = document.getElementById('progress-circle');
            const radius = progressCircle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            const offset = circumference - (progress / 100) * circumference;
            progressCircle.style.strokeDashoffset = offset;
            
            // Update floating subtest info
            const currentSubtestElem = document.getElementById('current-subtest');
            const subtestNameElem = document.getElementById('subtest-name');
            
            if (currentSubtestElem && subtestNameElem) {
                currentSubtestElem.textContent = `Subtest ${currentSubtestIndex + 1}/${subtests.length}`;
                
                // Dapatkan nama subtest berdasarkan kode
                const subtestNames = {
                    'SE': 'Kosa Kata',
                    'WA': 'Kemampuan Verbal', 
                    'AN': 'Kemampuan Analitis',
                    'GE': 'Kemampuan Generalisasi',
                    'RA': 'Kemampuan Aritmatika',
                    'ZR': 'Kemampuan Numerik'
                };
                
                const currentSubtestCode = subtests[currentSubtestIndex];
                subtestNameElem.textContent = subtestNames[currentSubtestCode] || currentSubtestCode;
            }
            
            // Update mobile navigation
            updateMobileNavigation();
        }
        
        // Hide/show floating elements saat scroll
        let lastScrollTop = 0;
        window.addEventListener('scroll', function() {
            const floatingTimer = document.getElementById('floating-timer');
            const floatingProgress = document.getElementById('floating-progress');
            const floatingSubtest = document.getElementById('floating-subtest-info');
            const st = window.pageYOffset || document.documentElement.scrollTop;
            
            if (st > lastScrollTop && st > 100) {
                // Scroll down - hide floating elements
                floatingTimer.style.transform = 'translateY(-10px)';
                floatingProgress.style.transform = 'translateY(-10px)';
                floatingSubtest.style.transform = 'translateY(20px)';
            } else {
                // Scroll up - show floating elements
                floatingTimer.style.transform = 'translateY(5px)';
                floatingProgress.style.transform = 'translateY(5px)';
                floatingSubtest.style.transform = 'translateY(35px)';
            }
            
            lastScrollTop = st <= 0 ? 0 : st;
        }, false);
        
        // Event listener untuk opsi jawaban
        document.addEventListener('DOMContentLoaded', function() {
            // Setup progress circle
            const progressCircle = document.getElementById('progress-circle');
            const radius = progressCircle.r.baseVal.value;
            const circumference = 2 * Math.PI * radius;
            
            progressCircle.style.strokeDasharray = `${circumference} ${circumference}`;
            progressCircle.style.strokeDashoffset = circumference;
            
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
            
            // Tambahkan event listener untuk tombol navigasi subtest
            document.querySelectorAll('.subtest-nav-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    // Scroll ke atas setelah klik tombol navigasi
                    setTimeout(scrollToTop, 100);
                });
            });
            
            // Mulai timer
            updateTimer();
            
            // Inisialisasi progress
            updateProgress();
            
            // Focus ke input pertama
            const firstInput = document.querySelector('input[type="radio"]');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>