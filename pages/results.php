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
        $testResults = json_decode($testInfo['results'], true);
    }
}

// Redirect jika hasil test tidak ditemukan
if (!$testInfo || !$testResults) {
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
        $testTitle = 'Pauli Test';
        break;
    case 'IST':
        $testTitle = 'IST Test';
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
        
        /* Style khusus untuk hasil Kraepelin */
        .kraepelin-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .kraepelin-stats {
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
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4e54c8;
            margin: 10px 0;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
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
        
        <?php if (strtoupper($testType) === 'KRAEPELIN' && isset($testResults)): ?>
        <div class="test-results">
            <h2>Hasil Kraepelin Test</h2>
            
            <div class="kraepelin-stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($testResults['total_score']) ? $testResults['total_score'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Skor</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($testResults['correct_answers']) ? $testResults['correct_answers'] : 'N/A'; ?></div>
                    <div class="stat-label">Jawaban Benar</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($testResults['total_questions']) ? $testResults['total_questions'] : 'N/A'; ?></div>
                    <div class="stat-label">Total Soal</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-value"><?php echo isset($testResults['accuracy']) ? number_format($testResults['accuracy'], 1) . '%' : 'N/A'; ?></div>
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
                    <table class="answers-table">
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
        
        <?php elseif (strtoupper($testType) === 'TIKI' && $testClass && isset($testResults['answers'])): ?>
        <!-- Tampilan hasil TIKI Test -->
        <?php else: ?>
        <div class="test-results">
            <h2>Hasil Test</h2>
            <p>Detail hasil test tidak tersedia atau format tidak dikenali.</p>
            <?php if (isset($testResults)): ?>
            <pre><?php echo htmlspecialchars(json_encode($testResults, JSON_PRETTY_PRINT)); ?></pre>
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