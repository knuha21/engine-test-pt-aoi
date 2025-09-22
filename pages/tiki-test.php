<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Ambil data peserta dari database
$db = getDBConnection();
$participant_id = $_SESSION['participant_id'];
$participant_query = $db->prepare("SELECT * FROM participants WHERE id = ?");
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
        // Pastikan class TIKITest ada
        if (!class_exists('TIKITest')) {
            throw new Exception('TIKITest class not found');
        }
        
        $tikiTest = new TIKITest();
        
        // Process answers
        $jawaban = [];
        foreach ($_POST['answers'] as $subtest => $questions) {
            foreach ($questions as $questionNumber => $answer) {
                if (is_array($answer)) {
                    // Multiple answers (checkboxes)
                    $jawaban[$subtest][$questionNumber] = implode(', ', $answer);
                } else {
                    // Single answer (radio)
                    $jawaban[$subtest][$questionNumber] = $answer;
                }
            }
        }
        
        $hasilOlahan = $tikiTest->prosesJawaban($jawaban);
        
        // Simpan ke database
        if ($tikiTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Redirect ke results
            header("Location: results.php?test=tiki&id=" . $db->lastInsertId());
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Ambil data soal menggunakan method baru dari class TIKITest
$tikiTest = new TIKITest();
$soal = $tikiTest->getAllSoal();

// Group soal by subtest
$soalBySubtest = [];
foreach ($soal as $item) {
    $soalBySubtest[$item['subtest']][] = $item;
}

// Jika tidak ada soal, gunakan data default
if (empty($soal)) {
    $soal = [
        [
            'question_id' => 1,
            'subtest' => 'berhitung_angka',
            'question_number' => 1,
            'question_text' => 'Hasil dari 5 + 7 adalah?',
            'option_a' => '10',
            'option_b' => '12',
            'option_c' => '14',
            'option_d' => '16',
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
            'question_text' => 'Pilih dua bagian yang membentuk gambar utuh:',
            'option_a' => 'Bagian A',
            'option_b' => 'Bagian B',
            'option_c' => 'Bagian C',
            'option_d' => 'Bagian D',
            'option_e' => 'Bagian E',
            'option_f' => 'Bagian F',
            'correct_answer' => 'A, C',
            'raw_score' => 1,
            'weighted_score' => 1
        ]
    ];
    
    foreach ($soal as $item) {
        $soalBySubtest[$item['subtest']][] = $item;
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
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #ae69f8ff 0%, #3d82f8ff 100%);
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
            font-size: 0.9rem;
        }
        
        .instructions h3 {
            color: #6a11cb;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .progress-container {
            padding: 15px 20px;
            background-color: #f8f9fa;
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
            background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 5px;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .question-container {
            padding: 25px;
        }
        
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .question-number {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .subtest-name {
            background-color: #6a11cb;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .question-text {
            font-size: 1.1rem;
            margin-bottom: 25px;
            line-height: 1.6;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #6a11cb;
        }
        
        .options-container {
            margin-bottom: 25px;
        }
        
        .option {
            display: block;
            padding: 15px;
            margin-bottom: 12px;
            background-color: #f8f9fa;
            border: 2px solid #e2e6ea;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .option:hover {
            background-color: #e9ecef;
            border-color: #c4c9d0;
        }
        
        .option.selected {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .option input {
            margin-right: 10px;
        }
        
        /* Multiple answers style */
        .multiple-answers {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .multiple-answers label {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 2px solid #e2e6ea;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .multiple-answers label:hover {
            background-color: #f8f9fa;
        }
        
        .multiple-answers input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .answer-preview {
            margin-top: 10px;
            padding: 10px;
            background-color: #e8f4fc;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        button {
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
            background-color: #007bff;
            color: white;
        }
        
        .btn-next:hover {
            background-color: #0069d9;
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
        
        .subtest-section {
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        
        .subtest-section h2 {
            color: #6a11cb;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        @media (max-width: 768px) {
            .question-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .subtest-name {
                margin-top: 10px;
            }
            
            .navigation {
                flex-direction: column;
                gap: 10px;
            }
            
            button {
                width: 100%;
            }
            
            .multiple-answers {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">        
        <div class="test-info">
            <p><strong>Peserta:</strong> <?php echo htmlspecialchars($participant['name']); ?> | <strong>Email:</strong> <?php echo htmlspecialchars($participant['email']); ?></p>
            <?php if ($participant['birth_date']): ?>
            <p><strong>Tanggal Lahir:</strong> <?php echo date('d/m/Y', strtotime($participant['birth_date'])); ?> | 
            <strong>Usia:</strong> <?php echo floor((time() - strtotime($participant['birth_date'])) / 31556926); ?> tahun</p>
            <?php endif; ?>
        </div>
        
        <div class="instructions">
            <h3>Petunjuk Pengerjaan</h3>
            <p>Pilih jawaban yang paling benar untuk setiap soal. Untuk soal dengan multiple answers, pilih minimal 2 jawaban. Jawaban tidak dapat diubah setelah melanjutkan ke soal berikutnya.</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress" id="progress-bar" style="width: 0%;"></div>
            </div>
            <div class="progress-text">
                <span id="progress-current">Soal 0 dari <?php echo count($soal); ?></span>
                <span id="progress-percentage">0% selesai</span>
            </div>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="tiki-test-form">
            <?php 
            $questionIndex = 0;
            foreach ($soalBySubtest as $subtest => $questions): 
                $subtestName = $tikiTest->getSubtestName($subtest);
            ?>
            <div class="subtest-section">
                <h2><?php echo htmlspecialchars($subtestName); ?></h2>
                
                <?php foreach ($questions as $item): ?>
                <div class="question-container" id="question-<?php echo $questionIndex; ?>" style="<?php echo $questionIndex > 0 ? 'display: none;' : ''; ?>">
                    <div class="question-header">
                        <div class="question-number">Soal #<?php echo $item['question_number']; ?></div>
                        <div class="subtest-name"><?php echo htmlspecialchars($subtestName); ?></div>
                    </div>
                    
                    <div class="question-text">
                        <p><?php echo htmlspecialchars($item['question_text']); ?></p>
                    </div>
                    
                    <div class="options-container">
                        <?php if (in_array($subtest, ['gabungan_bagian', 'hubungan_angka', 'abstraksi_non_verbal'])): ?>
                        <!-- Multiple answers -->
                        <div class="multiple-answers">
                            <?php for ($i = 'a'; $i <= 'f'; $i++): ?>
                                <?php if (!empty($item['option_' . $i])): ?>
                                <label>
                                    <input type="checkbox" name="answers[<?php echo $subtest; ?>][<?php echo $item['question_number']; ?>][]" value="<?php echo strtoupper($i); ?>">
                                    <span><?php echo strtoupper($i) . '. ' . htmlspecialchars($item['option_' . $i]); ?></span>
                                </label>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="answer-preview" id="preview-<?php echo $subtest; ?>-<?php echo $item['question_number']; ?>">
                            Jawaban: <span class="selected-answers"></span>
                        </div>
                        <?php else: ?>
                        <!-- Single answer -->
                        <?php for ($i = 'a'; $i <= 'd'; $i++): ?>
                            <?php if (!empty($item['option_' . $i])): ?>
                            <label class="option">
                                <input type="radio" name="answers[<?php echo $subtest; ?>][<?php echo $item['question_number']; ?>]" value="<?php echo strtoupper($i); ?>" required>
                                <span><?php echo strtoupper($i) . '. ' . htmlspecialchars($item['option_' . $i]); ?></span>
                            </label>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="navigation">
                        <button type="button" class="btn-prev" onclick="showQuestion(<?php echo $questionIndex - 1; ?>)" <?php echo $questionIndex === 0 ? 'disabled' : ''; ?>>Sebelumnya</button>
                        
                        <?php if ($questionIndex < count($soal) - 1): ?>
                        <button type="button" class="btn-next" onclick="validateAndNext(<?php echo $questionIndex; ?>)">Selanjutnya</button>
                        <?php else: ?>
                        <button type="submit" class="btn-submit">Kirim Jawaban</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php $questionIndex++; endforeach; ?>
            </div>
            <?php endforeach; ?>
        </form>
    </div>

    <script>
        // Data soal dari PHP
        const questions = <?php echo json_encode($soal); ?>;
        let currentQuestionIndex = 0;
        
        // Fungsi untuk menampilkan soal tertentu
        function showQuestion(index) {
            if (index < 0 || index >= questions.length) return;
            
            // Sembunyikan semua soal
            document.querySelectorAll('.question-container').forEach(container => {
                container.style.display = 'none';
            });
            
            // Tampilkan soal yang dipilih
            document.getElementById('question-' + index).style.display = 'block';
            currentQuestionIndex = index;
            
            // Update progress bar
            updateProgressBar();
            
            // Update status tombol navigasi
            updateNavigationButtons();
        }
        
        // Fungsi untuk memvalidasi dan lanjut ke soal berikutnya
        function validateAndNext(index) {
            const currentQuestion = document.getElementById('question-' + index);
            const subtest = questions[index].subtest;
            
            if (['gabungan_bagian', 'hubungan_angka', 'abstraksi_non_verbal'].includes(subtest)) {
                // Validasi multiple answers
                const checkboxes = currentQuestion.querySelectorAll('input[type="checkbox"]');
                const checked = Array.from(checkboxes).some(cb => cb.checked);
                
                if (!checked) {
                    alert('Silakan pilih minimal satu jawaban.');
                    return;
                }
                
                // Pastikan minimal 2 jawaban untuk multiple answers
                const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
                if (checkedCount < 2) {
                    alert('Silakan pilih minimal 2 jawaban untuk soal ini.');
                    return;
                }
            } else {
                // Validasi single answer
                const radios = currentQuestion.querySelectorAll('input[type="radio"]');
                const answered = Array.from(radios).some(radio => radio.checked);
                
                if (!answered) {
                    alert('Silakan pilih jawaban sebelum melanjutkan.');
                    return;
                }
            }
            
            // Lanjut ke soal berikutnya
            showQuestion(index + 1);
        }
        
        // Fungsi untuk update progress bar
        function updateProgressBar() {
            const progress = ((currentQuestionIndex + 1) / questions.length) * 100;
            document.getElementById('progress-bar').style.width = `${progress}%`;
            document.getElementById('progress-current').textContent = `Soal ${currentQuestionIndex + 1} dari ${questions.length}`;
            document.getElementById('progress-percentage').textContent = `${Math.round(progress)}% selesai`;
        }
        
        // Fungsi untuk update status tombol navigasi
        function updateNavigationButtons() {
            const prevButtons = document.querySelectorAll('.btn-prev');
            prevButtons.forEach(btn => {
                btn.disabled = currentQuestionIndex === 0;
            });
        }
        
        // Event listener untuk opsi jawaban
        document.addEventListener('DOMContentLoaded', function() {
            // Tambahkan event listener untuk semua opsi single answer
            document.querySelectorAll('.option').forEach(option => {
                option.addEventListener('click', function() {
                    // Hapus selected dari semua opsi dalam question yang sama
                    const question = this.closest('.question-container');
                    question.querySelectorAll('.option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    
                    // Tambahkan selected ke opsi yang diklik
                    this.classList.add('selected');
                    
                    // Centang radio button
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                });
            });
            
            // Event listener untuk multiple answers
            document.querySelectorAll('.multiple-answers input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const name = this.name;
                    const matches = name.match(/\[(.*?)\]\[(\d+)\]/);
                    const subtest = matches[1];
                    const questionNumber = matches[2];
                    
                    const selected = Array.from(document.querySelectorAll(`input[name="${name}"]:checked`))
                        .map(cb => cb.value)
                        .sort()
                        .join(', ');
                    
                    document.getElementById(`preview-${subtest}-${questionNumber}`).querySelector('.selected-answers').textContent = selected;
                });
            });
            
            // Inisialisasi
            updateProgressBar();
            updateNavigationButtons();
        });
    </script>
</body>
</html>