<?php

require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

$db = getDBConnection();
$testId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$testType = isset($_GET['test']) ? $_GET['test'] : '';

// Redirect jika tidak ada parameter yang diperlukan
if (!$testId || !$testType) {
    header("Location: dashboard.php");
    exit();
}

// Ambil informasi hasil test
$testQuery = $db->prepare("
    SELECT r.*, p.name as participant_name, p.email 
    FROM test_results r 
    JOIN participants p ON r.participant_id = p.id 
    WHERE r.id = ? AND r.test_type = ?
");
$testInfo = null;
$testResults = null;

if ($testQuery->execute([$testId, strtoupper($testType)])) {
    $testInfo = $testQuery->fetch(PDO::FETCH_ASSOC);
    if ($testInfo) {
        // Decode JSON results
        $testResults = json_decode($testInfo['results'], true);
        
        // Jika decode gagal, coba tangani sebagai string JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            $testResults = $testInfo['results']; // Simpan sebagai string untuk debugging
        }
    }
}

// Redirect jika hasil test tidak ditemukan
if (!$testInfo) {
    header("Location: dashboard.php");
    exit();
}

// Ambil class test yang sesuai
$testClass = null;
$testTitle = '';

switch (strtoupper($testType)) {
    case 'TIKI':
        if (class_exists('TIKITest')) {
            $testClass = new TIKITest();
            $testTitle = 'TIKI Test (Tes Inteligensi Kolektif Indonesia)';
        }
        break;
    case 'KRAEPELIN':
        if (class_exists('KraepelinTest')) {
            $testClass = new KraepelinTest();
            $testTitle = 'Kraepelin Test';
        }
        break;
    case 'PAULI':
        if (class_exists('PauliTest')) {
            $testClass = new PauliTest();
            $testTitle = 'Pauli Test';
        }
        break;
    case 'IST':
        if (class_exists('ISTTest')) {
            $testClass = new ISTTest();
            $testTitle = 'IST Test (Intelligenz Struktur Test)';
        }
        break;
    default:
        $testTitle = 'Hasil Test';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Test - PT. Apparel One Indonesia</title>
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
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .test-info {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .test-results {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        /* Style untuk statistik test */
        .test-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .test-stats {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #4e54c8;
        }
        
        .tiki-stat-card {
            border-left: 4px solid #6a11cb;
        }
        
        .kraepelin-stat-card {
            border-left: 4px solid #4e54c8;
        }
        
        .pauli-stat-card {
            border-left: 4px solid #28a745;
        }
        
        .ist-stat-card {
            border-left: 4px solid #ff6b6b;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4e54c8;
            margin: 10px 0;
        }
        
        .tiki-stat-value {
            color: #6a11cb;
        }
        
        .kraepelin-stat-value {
            color: #4e54c8;
        }
        
        .pauli-stat-value {
            color: #28a745;
        }
        
        .ist-stat-value {
            color: #ff6b6b;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .stat-percentile {
            font-size: 0.8rem;
            color: #28a745;
            margin-top: 5px;
            font-weight: bold;
        }
        
        .interpretation {
            background-color: #e8f4fc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
        }
        
        .interpretation h3 {
            color: #2196F3;
            margin-bottom: 10px;
        }
        
        .answers-detail {
            margin-top: 30px;
        }
        
        .answers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .answers-table th, .answers-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .answers-table th {
            background-color: #4e54c8;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        .tiki-answers-table th {
            background-color: #6a11cb;
        }
        
        .kraepelin-answers-table th {
            background-color: #4e54c8;
        }
        
        .pauli-answers-table th {
            background-color: #28a745;
        }
        
        .ist-answers-table th {
            background-color: #ff6b6b;
        }
        
        .answers-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .answers-table tr:hover {
            background-color: #f1f3f5;
        }
        
        .correct-answer {
            color: #28a745;
            font-weight: bold;
        }
        
        .incorrect-answer {
            color: #dc3545;
        }
        
        .answers-container {
            max-height: 400px;
            overflow-y: auto;
            margin-top: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #4e54c8;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
            margin: 0 10px;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #3f44a7;
            transform: translateY(-2px);
        }
        
        .btn-print {
            background-color: #17a2b8;
        }
        
        .btn-print:hover {
            background-color: #138496;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .subtest-scores {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .subtest-scores h3 {
            margin-top: 0;
            color: #ff6b6b;
        }
        
        .raw-data {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .raw-data pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .grafik-container {
            margin: 20px 0;
            padding: 15px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .grafik-container img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        
        @media print {
            .actions {
                display: none;
            }
            
            .btn {
                display: none;
            }
            
            .answers-container {
                max-height: none;
                overflow: visible;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Hasil <?php echo htmlspecialchars($testTitle); ?></h1>
            <p>PT. Apparel One Indonesia</p>
        </header>
        
        <div class="test-info">
            <h2>Informasi Peserta</h2>
            <p><strong>Nama:</strong> <?php echo htmlspecialchars($testInfo['participant_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($testInfo['email']); ?></p>
            <p><strong>Tanggal Test:</strong> <?php echo date('d F Y H:i', strtotime($testInfo['created_at'])); ?></p>
            <p><strong>Jenis Test:</strong> <?php echo htmlspecialchars($testTitle); ?></p>
        </div>
        
        <?php if (strtoupper($testType) === 'TIKI' && is_array($testResults)): ?>
        <div class="test-results">
            <h2>Hasil TIKI Test</h2>
            
            <div class="test-stats">
                <?php if (isset($testResults['iq_score'])): ?>
                <div class="stat-card tiki-stat-card">
                    <div class="stat-value tiki-stat-value"><?php echo $testResults['iq_score']; ?></div>
                    <div class="stat-label">Skor IQ</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($testResults['total_score'])): ?>
                <div class="stat-card tiki-stat-card">
                    <div class="stat-value tiki-stat-value"><?php echo $testResults['total_score']; ?></div>
                    <div class="stat-label">Total Skor</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($testResults['correct_answers'])): ?>
                <div class="stat-card tiki-stat-card">
                    <div class="stat-value tiki-stat-value"><?php echo $testResults['correct_answers']; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($testResults['total_questions'])): ?>
                <div class="stat-card tiki-stat-card">
                    <div class="stat-value tiki-stat-value"><?php echo $testResults['total_questions']; ?></div>
                    <div class="stat-label">Total Soal</div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($testResults['accuracy'])): ?>
                <div class="stat-card tiki-stat-card">
                    <div class="stat-value tiki-stat-value"><?php echo number_format($testResults['accuracy'], 1); ?>%</div>
                    <div class="stat-label">Tingkat Akurasi</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Tampilkan grafik jika ada -->
            <?php if (isset($testResults['grafik'])): ?>
            <div class="grafik-container">
                <h3>Grafik Hasil Test</h3>
                <?php echo $testResults['grafik']; ?>
            </div>
            <?php endif; ?>
            
            <div class="interpretation">
                <h3>Interpretasi Hasil TIKI Test</h3>
                <?php
                if (isset($testResults['iq_score'])) {
                    $iq = $testResults['iq_score'];
                    
                    if ($iq >= 130) {
                        echo "<p><strong>Sangah Superior:</strong> Kemampuan intelektual berada pada tingkat yang sangat tinggi.</p>";
                    } elseif ($iq >= 120) {
                        echo "<p><strong>Superior:</strong> Kemampuan intelektual di atas rata-rata.</p>";
                    } elseif ($iq >= 110) {
                        echo "<p><strong>Di Atas Rata-rata:</strong> Kemampuan intelektual sedikit di atas rata-rata.</p>";
                    } elseif ($iq >= 90) {
                        echo "<p><strong>Rata-rata:</strong> Kemampuan intelektual dalam kisaran normal.</p>";
                    } elseif ($iq >= 80) {
                        echo "<p><strong>Di Bawah Rata-rata:</strong> Kemampuan intelektual sedikit di bawah rata-rata.</p>";
                    } else {
                        echo "<p><strong>Perlu Perhatian Khusus:</strong> Kemampuan intelektual memerlukan evaluasi lebih lanjut.</p>";
                    }
                    
                    echo "<p>Skor IQ: " . number_format($iq, 1) . "</p>";
                } else {
                    echo "<p>Interpretasi hasil tidak tersedia.</p>";
                }
                ?>
            </div>
            
            <!-- Tampilkan skor per subtest jika ada -->
            <?php if (isset($testResults['subtest_scores']) && is_array($testResults['subtest_scores'])): ?>
            <div class="subtest-scores">
                <h3>Skor per Subtest</h3>
                <div class="test-stats">
                    <?php foreach ($testResults['subtest_scores'] as $subtest => $score): 
                        $subtestNames = [
                            'Verbal' => 'Kemampuan Verbal',
                            'Numerik' => 'Kemampuan Numerik',
                            'Logika' => 'Kemampuan Logika',
                            'Spasial' => 'Kemampuan Spasial'
                        ];
                        $subtestName = $subtestNames[$subtest] ?? $subtest;
                    ?>
                    <div class="stat-card tiki-stat-card">
                        <div class="stat-value tiki-stat-value"><?php echo $score; ?></div>
                        <div class="stat-label"><?php echo $subtestName; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($testResults['answers']) && is_array($testResults['answers']) && count($testResults['answers']) > 0): ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Berikut adalah detail jawaban yang telah Anda berikan:</p>
                
                <div class="answers-container">
                    <table class="answers-table tiki-answers-table">
                        <thead>
                            <tr>
                                <th>Subtest</th>
                                <th>Soal</th>
                                <th>Jawaban Anda</th>
                                <th>Jawaban Benar</th>
                                <th>Status</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtestNames = [
                                'Verbal' => 'Kemampuan Verbal',
                                'Numerik' => 'Kemampuan Numerik',
                                'Logika' => 'Kemampuan Logika',
                                'Spasial' => 'Kemampuan Spasial'
                            ];
                            
                            foreach ($testResults['answers'] as $answer): 
                                $subtestName = $subtestNames[$answer['subtest']] ?? $answer['subtest'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subtestName); ?></td>
                                <td><?php echo htmlspecialchars($answer['question_number']); ?></td>
                                <td class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                    <?php echo htmlspecialchars($answer['user_answer']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($answer['correct_answer']); ?></td>
                                <td>
                                    <?php if ($answer['is_correct']): ?>
                                    <span class="correct-answer">Benar</span>
                                    <?php else: ?>
                                    <span class="incorrect-answer">Salah</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $answer['score']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Data detail jawaban tidak tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif (strtoupper($testType) === 'KRAEPELIN' && is_array($testResults)): ?>
        <div class="test-results">
            <h2>Hasil Kraepelin Test</h2>
            
            <!-- Tambahkan debug info untuk testing -->
            <?php if (DEBUG_MODE && isset($testResults['answers'])): ?>
            <div class="test-instructions" style="background-color: #ffe6e6;">
                <h3>🔧 Debug Information (Hanya di Mode Development)</h3>
                <p><strong>Total Soal:</strong> <?php echo $testResults['total_questions']; ?></p>
                <p><strong>Jawaban Benar:</strong> <?php echo $testResults['correct_answers']; ?></p>
                <p><strong>Akurasi:</strong> <?php echo number_format($testResults['accuracy'], 1); ?>%</p>
            </div>
            <?php endif; ?>
                            
            <div class="test-stats">
                <div class="stat-card kraepelin-stat-card">
                    <div class="stat-value kraepelin-stat-value"><?php echo isset($testResults['total_score']) ? $testResults['total_score'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Skor</div>
                </div>
                            
                <div class="stat-card kraepelin-stat-card">
                    <div class="stat-value kraepelin-stat-value"><?php echo isset($testResults['correct_answers']) ? $testResults['correct_answers'] : 'N/A'; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                </div>
                            
                <div class="stat-card kraepelin-stat-card">
                    <div class="stat-value kraepelin-stat-value"><?php echo isset($testResults['total_questions']) ? $testResults['total_questions'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Soal</div>
                </div>
                            
                <div class="stat-card kraepelin-stat-card">
                    <div class="stat-value kraepelin-stat-value"><?php echo isset($testResults['accuracy']) ? number_format($testResults['accuracy'], 1) . '%' : 'N/A'; ?></div>
                    <div class="stat-label">Tingkat Akurasi</div>
                </div>
            </div>
                            
            <div class="interpretation">
                <h3>Interpretasi Hasil</h3>
                <?php
                if (isset($testResults['accuracy'])) {
                    $accuracy = $testResults['accuracy'];
                    if ($accuracy >= 90) {
                        echo "<p><strong>Sangat Baik:</strong> Tingkat akurasi Anda sangat tinggi, menunjukkan ketelitian dan kecepatan kerja yang excellent.</p>";
                    } elseif ($accuracy >= 80) {
                        echo "<p><strong>Baik:</strong> Tingkat akurasi Anda baik, menunjukkan kemampuan kerja yang konsisten dan teliti.</p>";
                    } elseif ($accuracy >= 70) {
                        echo "<p><strong>Cukup:</strong> Tingkat akurasi Anda cukup, namun masih perlu meningkatkan ketelitian.</p>";
                    } elseif ($accuracy >= 60) {
                        echo "<p><strong>Perlu Perbaikan:</strong> Tingkat akurasi Anda perlu ditingkatkan dengan lebih banyak latihan.</p>";
                    } else {
                        echo "<p><strong>Perlu Perhatian Khusus:</strong> Tingkat akurasi Anda rendah, disarankan untuk berlatih lebih intensif.</p>";
                    }
                } else {
                    echo "<p>Interpretasi hasil tidak tersedia.</p>";
                }
                ?>
            </div>
            
            <?php if (isset($testResults['answers']) && is_array($testResults['answers']) && count($testResults['answers']) > 0): ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Berikut adalah detail jawaban yang telah Anda berikan:</p>

                <div class="answers-container">
                    <table class="answers-table kraepelin-answers-table">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                <th>Kolom</th>
                                <th>Jawaban Anda</th>
                                <th>Status</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults['answers'] as $answer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($answer['baris'] + 1); ?></td>
                                <td><?php echo htmlspecialchars($answer['kolom'] + 1); ?></td>
                                <td class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                    <?php echo htmlspecialchars($answer['jawaban']); ?>
                                </td>
                                <td>
                                    <?php if ($answer['is_correct']): ?>
                                    <span class="correct-answer">Benar</span>
                                    <?php else: ?>
                                    <span class="incorrect-answer">Salah</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $answer['score']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Data detail jawaban tidak tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif (strtoupper($testType) === 'PAULI' && is_array($testResults)): ?>
        <div class="test-results">
            <h2>Hasil Pauli Test</h2>
            
            <div class="test-stats">
                <div class="stat-card pauli-stat-card">
                    <div class="stat-value pauli-stat-value"><?php echo isset($testResults['total_score']) ? $testResults['total_score'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Skor</div>
                </div>
                
                <div class="stat-card pauli-stat-card">
                    <div class="stat-value pauli-stat-value"><?php echo isset($testResults['correct_answers']) ? $testResults['correct_answers'] : 'N/A'; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                </div>
                
                <div class="stat-card pauli-stat-card">
                    <div class="stat-value pauli-stat-value"><?php echo isset($testResults['total_questions']) ? $testResults['total_questions'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Soal</div>
                </div>
                
                <div class="stat-card pauli-stat-card">
                    <div class="stat-value pauli-stat-value"><?php echo isset($testResults['accuracy']) ? number_format($testResults['accuracy'], 1) . '%' : 'N/A'; ?></div>
                    <div class="stat-label">Tingkat Akurasi</div>
                </div>
                
                <?php if (isset($testResults['fluctuation'])): ?>
                <div class="stat-card pauli-stat-card">
                    <div class="stat-value pauli-stat-value"><?php echo number_format($testResults['fluctuation'], 2); ?></div>
                    <div class="stat-label">Tingkat Fluktuasi</div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="interpretation">
                <h3>Interpretasi Hasil Pauli Test</h3>
                <?php
                if (isset($testResults['accuracy']) && isset($testResults['fluctuation'])) {
                    $accuracy = $testResults['accuracy'];
                    $fluctuation = $testResults['fluctuation'];
                    
                    if ($accuracy >= 90 && $fluctuation <= 1.0) {
                        echo "<p><strong>Sangat Baik:</strong> Konsistensi kerja excellent dengan akurasi tinggi dan fluktuasi rendah.</p>";
                    } elseif ($accuracy >= 80 && $fluctuation <= 1.5) {
                        echo "<p><strong>Baik:</strong> Kemampuan kerja yang konsisten dengan akurasi baik.</p>";
                    } elseif ($accuracy >= 70) {
                        echo "<p><strong>Cukup:</strong> Akurasi cukup tetapi perlu meningkatkan konsistensi.</p>";
                    } elseif ($accuracy >= 60) {
                        echo "<p><strong>Perlu Perbaikan:</strong> Perlu meningkatkan baik akurasi maupun konsistensi kerja.</p>";
                    } else {
                        echo "<p><strong>Perlu Perhatian Khusus:</strong> Hasil menunjukkan perlu latihan intensif untuk meningkatkan ketelitian dan konsistensi.</p>";
                    }
                    
                    echo "<p>Fluktuasi: " . number_format($fluctuation, 2) . " (semakin rendah semakin konsisten)</p>";
                } else {
                    echo "<p>Interpretasi hasil tidak tersedia.</p>";
                }
                ?>
            </div>
            
            <?php if (isset($testResults['answers']) && is_array($testResults['answers']) && count($testResults['answers']) > 0): ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Berikut adalah detail jawaban yang telah Anda berikan:</p>
                
                <div class="answers-container">
                    <table class="answers-table pauli-answers-table">
                        <thead>
                            <tr>
                                <th>Baris</th>
                                <th>Kolom</th>
                                <th>Jawaban Anda</th>
                                <th>Status</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults['answers'] as $answer): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($answer['baris'] + 1); ?></td>
                                <td><?php echo htmlspecialchars($answer['kolom'] + 1); ?></td>
                                <td class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                    <?php echo htmlspecialchars($answer['jawaban']); ?>
                                </td>
                                <td>
                                    <?php if ($answer['is_correct']): ?>
                                    <span class="correct-answer">Benar</span>
                                    <?php else: ?>
                                    <span class="incorrect-answer">Salah</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $answer['score']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Data detail jawaban tidak tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php elseif (strtoupper($testType) === 'IST' && is_array($testResults)): ?>
        <div class="test-results">
            <h2>Hasil IST Test (Intelligenz Struktur Test)</h2>
            
            <div class="test-stats">
                <div class="stat-card ist-stat-card">
                    <div class="stat-value ist-stat-value"><?php echo isset($testResults['total_score']) ? $testResults['total_score'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Skor</div>
                </div>
                
                <div class="stat-card ist-stat-card">
                    <div class="stat-value ist-stat-value"><?php echo isset($testResults['correct_answers']) ? $testResults['correct_answers'] : 'N/A'; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                </div>
                
                <div class="stat-card ist-stat-card">
                    <div class="stat-value ist-stat-value"><?php echo isset($testResults['total_questions']) ? $testResults['total_questions'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Soal</div>
                </div>
                
                <div class="stat-card ist-stat-card">
                    <div class="stat-value ist-stat-value"><?php echo isset($testResults['accuracy']) ? number_format($testResults['accuracy'], 1) . '%' : 'N/A'; ?></div>
                    <div class="stat-label">Tingkat Akurasi</div>
                </div>
            </div>
            
            <!-- Tampilkan skor per subtest jika ada -->
            <?php if (isset($testResults['subtest_scores']) && is_array($testResults['subtest_scores'])): ?>
            <div class="subtest-scores">
                <h3>Profil Kemampuan Kognitif</h3>
                <p>Berikut adalah hasil kemampuan kognitif Anda dalam berbagai bidang:</p>
                
                <div class="test-stats">
                    <?php 
                    $subtestNames = [
                        'SE' => 'Kosa Kata (Wortschatztest)',
                        'WA' => 'Kemampuan Verbal (Wortanalogien)',
                        'AN' => 'Kemampuan Analitis (Figurenauswahl)',
                        'GE' => 'Kemampuan Generalisasi (Rechenaufgaben)',
                        'RA' => 'Kemampuan Aritmatika (Rechenaufgaben)',
                        'ZR' => 'Kemampuan Numerik (Zahlenreihen)',
                        'FA' => 'Kemampuan Figural (Figurenauswahl)',
                        'WU' => 'Kemampuan Komprehensi (Würfelaufgaben)',
                        'ME' => 'Kemampuan Memori (Merkfähigkeit)'
                    ];
                    
                    foreach ($testResults['subtest_scores'] as $subtest => $score): 
                        $subtestName = $subtestNames[$subtest] ?? $subtest;
                        // Konversi skor mentah ke persentil (contoh sederhana)
                        $percentile = min(100, max(0, intval(($score / 20) * 100)));
                    ?>
                    <div class="stat-card ist-stat-card">
                        <div class="stat-value ist-stat-value"><?php echo $score; ?></div>
                        <div class="stat-label"><?php echo $subtestName; ?></div>
                        <div class="stat-percentile">Persentil: <?php echo $percentile; ?>%</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="interpretation">
                <h3>Interpretasi Hasil IST Test</h3>
                <?php
                if (isset($testResults['subtest_scores']) && is_array($testResults['subtest_scores'])) {
                    echo "<p><strong>IST (Intelligenz Struktur Test)</strong> mengukur berbagai kemampuan kognitif yang relatif independen. Hasil ini menunjukkan profil kemampuan Anda dalam bidang-bidang spesifik:</p>";
                    
                    // Analisis kekuatan dan kelemahan
                    $scores = $testResults['subtest_scores'];
                    if (count($scores) > 0) {
                        $maxScore = max($scores);
                        $minScore = min($scores);
                        $strongAreas = array_keys($scores, $maxScore);
                        $weakAreas = array_keys($scores, $minScore);
                        
                        if (count($strongAreas) > 0) {
                            echo "<p><strong>Kekuatan Terbesar:</strong> ";
                            $strongNames = [];
                            foreach ($strongAreas as $area) {
                                $strongNames[] = $subtestNames[$area] ?? $area;
                            }
                            echo implode(", ", $strongNames) . "</p>";
                        }
                        
                        if (count($weakAreas) > 0 && $minScore < $maxScore) {
                            echo "<p><strong>Area Perbaikan:</strong> ";
                            $weakNames = [];
                            foreach ($weakAreas as $area) {
                                $weakNames[] = $subtestNames[$area] ?? $area;
                            }
                            echo implode(", ", $weakNames) . "</p>";
                        }
                        
                        echo "<p>Setiap orang memiliki pola kemampuan kognitif yang unik. Hasil ini dapat membantu memahami kekuatan Anda dan area yang mungkin perlu dikembangkan lebih lanjut.</p>";
                    }
                } else {
                    echo "<p>Interpretasi hasil tidak tersedia.</p>";
                }
                ?>
            </div>
            
            <?php if (isset($testResults['answers']) && is_array($testResults['answers']) && count($testResults['answers']) > 0): ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Berikut adalah detail jawaban yang telah Anda berikan:</p>
                
                <div class="answers-container">
                    <table class="answers-table ist-answers-table">
                        <thead>
                            <tr>
                                <th>Subtest</th>
                                <th>Soal</th>
                                <th>Jawaban Anda</th>
                                <th>Jawaban Benar</th>
                                <th>Status</th>
                                <th>Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtestNames = [
                                'SE' => 'Kosa Kata',
                                'WA' => 'Kemampuan Verbal', 
                                'AN' => 'Kemampuan Analitis',
                                'GE' => 'Kemampuan Generalisasi',
                                'RA' => 'Kemampuan Aritmatika',
                                'ZR' => 'Kemampuan Numerik',
                                'FA' => 'Kemampuan Figural',
                                'WU' => 'Kemampuan Komprehensi',
                                'ME' => 'Kemampuan Memori'
                            ];
                            
                            foreach ($testResults['answers'] as $answer): 
                                $subtestName = $subtestNames[$answer['subtest']] ?? $answer['subtest'];
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($subtestName); ?></td>
                                <td><?php echo htmlspecialchars($answer['question_number']); ?></td>
                                <td class="<?php echo $answer['is_correct'] ? 'correct-answer' : 'incorrect-answer'; ?>">
                                    <?php echo htmlspecialchars($answer['user_answer']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($answer['correct_answer']); ?></td>
                                <td>
                                    <?php if ($answer['is_correct']): ?>
                                    <span class="correct-answer">Benar</span>
                                    <?php else: ?>
                                    <span class="incorrect-answer">Salah</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $answer['score']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php else: ?>
            <div class="answers-detail">
                <h3>Detail Jawaban</h3>
                <p>Data detail jawaban tidak tersedia.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php else: ?>
        <div class="test-results">
            <h2>Hasil Test</h2>
            <p>Detail hasil test tidak tersedia atau format tidak dikenali.</p>
            <?php if (isset($testResults)): ?>
            <div class="raw-data">
                <h4>Data Mentah:</h4>
                <pre><?php echo htmlspecialchars(json_encode($testResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
            <a href="javascript:window.print()" class="btn btn-print">Cetak Hasil</a>
        </div>
    </div>
</body>
</html>