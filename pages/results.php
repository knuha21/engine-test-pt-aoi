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
        $testTitle = 'Kraepelin Test';
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
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
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
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .score-card {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            border-radius: 8px;
            color: white;
        }
        
        .iq-score {
            font-size: 3rem;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .interpretation {
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        
        .score-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 768px) {
            .score-details {
                grid-template-columns: 1fr;
            }
        }
        
        .score-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        
        .score-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .score-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .answers-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .answers-table th, .answers-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .answers-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .answers-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .correct-answer {
            color: #28a745;
            font-weight: bold;
        }
        
        .incorrect-answer {
            color: #dc3545;
        }
        
        .subtest-scores {
            margin-bottom: 30px;
        }
        
        .score-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .subtest-name {
            width: 120px;
            font-weight: bold;
        }
        
        .bar-container {
            flex-grow: 1;
            height: 20px;
            background-color: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            margin: 0 15px;
        }
        
        .bar {
            height: 100%;
            background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
            border-radius: 10px;
        }
        
        .score-value {
            width: 60px;
            text-align: right;
            font-weight: bold;
        }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #6a11cb;
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
            background-color: #5a0fb8;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-print {
            background-color: #17a2b8;
        }
        
        .btn-print:hover {
            background-color: #138496;
        }
        
        @media print {
            .actions {
                display: none;
            }
            
            .btn {
                display: none;
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
        
        <?php if (strtoupper($testType) === 'TIKI' && $testClass && isset($testResults['answers'])): ?>
        <div class="test-results">
            <div class="score-card">
                <h2>Skor IQ Anda</h2>
                <div class="iq-score"><?php echo isset($testResults['iq_score']) ? number_format($testResults['iq_score'], 1) : 'N/A'; ?></div>
                <div class="interpretation">
                    <?php
                    if (isset($testResults['iq_score'])) {
                        $iq = $testResults['iq_score'];
                        if ($iq >= 130) {
                            echo "Sangat Unggul";
                        } elseif ($iq >= 115) {
                            echo "Unggul";
                        } elseif ($iq >= 85) {
                            echo "Rata-rata";
                        } elseif ($iq >= 70) {
                            echo "Di Bawah Rata-rata";
                        } else {
                            echo "Sangat Di Bawah Rata-rata";
                        }
                    } else {
                        echo "Tidak Tersedia";
                    }
                    ?>
                </div>
            </div>
            
            <div class="score-details">
                <div class="score-item">
                    <div class="score-label">Skor Total</div>
                    <div class="score-value"><?php echo isset($testResults['total_score']) ? $testResults['total_score'] : 'N/A'; ?></div>
                </div>
                
                <div class="score-item">
                    <div class="score-label">Jumlah Soal</div>
                    <div class="score-value"><?php echo isset($testResults['answers']) ? count($testResults['answers']) : 'N/A'; ?></div>
                </div>
                
                <div class="score-item">
                    <div class="score-label">Jawaban Benar</div>
                    <div class="score-value">
                        <?php
                        if (isset($testResults['answers'])) {
                            $correct = 0;
                            foreach ($testResults['answers'] as $answer) {
                                if ($answer['is_correct']) $correct++;
                            }
                            echo $correct;
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="score-item">
                    <div class="score-label">Persentase Benar</div>
                    <div class="score-value">
                        <?php
                        if (isset($testResults['answers']) && count($testResults['answers']) > 0) {
                            $correct = 0;
                            foreach ($testResults['answers'] as $answer) {
                                if ($answer['is_correct']) $correct++;
                            }
                            echo round(($correct / count($testResults['answers'])) * 100) . '%';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <?php if (isset($testResults['subtest_scores'])): ?>
            <div class="subtest-scores">
                <h3>Skor per Subtest</h3>
                <?php foreach ($testResults['subtest_scores'] as $subtest => $score): 
                    $percentage = min(100, ($score / 50) * 100); // Asumsi skor maksimal 50 per subtest
                ?>
                <div class="score-bar">
                    <div class="subtest-name"><?php echo htmlspecialchars($subtest); ?></div>
                    <div class="bar-container">
                        <div class="bar" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    <div class="score-value"><?php echo $score; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <h3>Detail Jawaban</h3>
            <table class="answers-table">
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
                    <?php foreach ($testResults['answers'] as $answer): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($answer['subtest']); ?></td>
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
        
        <?php elseif (strtoupper($testType) === 'KRAEPELIN'): ?>
        <div class="test-results">
            <h2>Hasil Kraepelin Test</h2>
            <p>Hasil Kraepelin test akan ditampilkan di sini.</p>
            <!-- Tambahkan tampilan khusus untuk Kraepelin test -->
        </div>
        
        <?php elseif (strtoupper($testType) === 'PAULI'): ?>
        <div class="test-results">
            <h2>Hasil Pauli Test</h2>
            <p>Hasil Pauli test akan ditampilkan di sini.</p>
            <!-- Tambahkan tampilan khusus untuk Pauli test -->
        </div>
        
        <?php elseif (strtoupper($testType) === 'IST'): ?>
        <div class="test-results">
            <h2>Hasil IST Test</h2>
            <p>Hasil IST test akan ditampilkan di sini.</p>
            <!-- Tambahkan tampilan khusus untuk IST test -->
        </div>
        
        <?php else: ?>
        <div class="test-results">
            <h2>Hasil Test</h2>
            <p>Detail hasil test tidak tersedia atau format tidak dikenali.</p>
            <pre><?php echo htmlspecialchars(json_encode($testResults, JSON_PRETTY_PRINT)); ?></pre>
        </div>
        <?php endif; ?>
        
        <div class="actions">
            <a href="dashboard.php" class="btn">Kembali ke Dashboard</a>
            <a href="javascript:window.print()" class="btn btn-print">Cetak Hasil</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>