<?php
require_once __DIR__ . '/../bootstrap.php';

if (!isset($_SESSION['participant_id']) || !isset($_GET['test'])) {
    header("Location: ../index.php");
    exit();
}

$testType = $_GET['test'];
$testId = isset($_GET['id']) ? intval($_GET['id']) : null;
$results = [];
$chartData = [];
$error = '';
$testDate = '';

// Function untuk generate chart data
function generateChartData($results) {
    $dataGrafik = [];
    foreach ($results as $subtest => $score) {
        // Skip metadata array
        if ($subtest === 'metadata') continue;
        
        if (is_array($score) && isset($score['weighted_score'])) {
            $dataGrafik[] = [
                'subtest' => "Subtest " . $subtest,
                'weighted_score' => $score['weighted_score']
            ];
        }
    }
    return $dataGrafik;
}

try {
    $db = getDBConnection();
    
    if ($testId && $testId > 0) {
        // Ambil hasil spesifik dari database berdasarkan ID
        $query = "SELECT * FROM test_results WHERE id = :id AND participant_id = :participant_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $testId);
        $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $testData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pastikan test type sesuai
            if (strtoupper($testData['test_type']) === strtoupper($testType)) {
                $results = json_decode($testData['results'], true);
                $testDate = $testData['created_at'];
                
                if ($results === null) {
                    $error = "Error decoding JSON results: " . json_last_error_msg();
                    error_log("JSON decode error: " . json_last_error_msg());
                } else {
                    // Generate chart data jika diperlukan
                    if ($testType == 'tiki' || $testType == 'ist') {
                        $chartData = generateChartData($results);
                    }
                }
            } else {
                $error = "Jenis test tidak sesuai dengan ID test. Expected: " . strtoupper($testType) . ", Found: " . $testData['test_type'];
                error_log("Test type mismatch: " . $error);
            }
        } else {
            $error = "Data hasil test tidak ditemukan untuk ID: " . $testId;
            error_log("Test results not found for ID: " . $testId);
        }
    } else {
        // Ambil hasil terbaru dari database
        $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = :test_type ORDER BY created_at DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
        $stmt->bindValue(":test_type", strtoupper($testType));
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $testData = $stmt->fetch(PDO::FETCH_ASSOC);
            $results = json_decode($testData['results'], true);
            $testId = $testData['id'];
            $testDate = $testData['created_at'];
            
            if ($results === null) {
                $error = "Error decoding JSON results: " . json_last_error_msg();
                error_log("JSON decode error: " . json_last_error_msg());
            } else {
                if ($testType == 'tiki' || $testType == 'ist') {
                    $chartData = generateChartData($results);
                }
            }
        } else {
            $error = "Belum ada hasil test untuk " . strtoupper($testType) . ". Silakan kerjakan test terlebih dahulu.";
            error_log("No test results found for type: " . $testType);
        }
    }
} catch (PDOException $e) {
    $error = "Error mengambil data hasil: " . $e->getMessage();
    error_log("Results page error: " . $e->getMessage());
}

// Debug: lihat data yang diambil
if (APP_DEBUG) {
    error_log("Test Type: " . $testType);
    error_log("Test ID: " . $testId);
    error_log("Results data: " . print_r($results, true));
    error_log("Error: " . $error);
}

// Konversi data chart ke format JSON untuk JavaScript
$chartDataJson = json_encode($chartData);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Test <?php echo strtoupper($testType); ?> - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Hasil Test <?php echo strtoupper($testType); ?></h1>
            <?php if ($testDate): ?>
            <p>Tanggal Test: <?php echo date('d/m/Y H:i', strtotime($testDate)); ?></p>
            <?php endif; ?>
            <?php if ($testId && $testId > 0): ?>
            <p>ID Test: <?php echo $testId; ?></p>
            <?php endif; ?>
        </header>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <h3>Error:</h3>
            <?php echo $error; ?>
            
            <?php if (APP_DEBUG): ?>
            <div style="margin-top: 15px; padding: 10px; background: #ffe6e6; border-radius: 5px;">
                <strong>Debug Info:</strong><br>
                Test Type: <?php echo $testType; ?><br>
                Test ID: <?php echo $testId ? $testId : 'Not specified'; ?><br>
                Participant ID: <?php echo $_SESSION['participant_id']; ?><br>
                Session: <?php echo session_id(); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (empty($results) && empty($error)): ?>
        <div class="error-message">
            <p>Data hasil test tidak ditemukan.</p>
            <p>Silakan kerjakan test terlebih dahulu atau hubungi administrator.</p>
        </div>
        <?php elseif (!empty($results)): ?>
        
        <div class="results-section">
            <h2>Detail Hasil Test</h2>
            
            <?php if ($testType == 'tiki'): ?>
            <table class="results-table">
                <tr>
                    <th>Subtest</th>
                    <th>Raw Score</th>
                    <th>Weighted Score</th>
                </tr>
                <?php foreach ($results as $subtest => $score): ?>
                <?php if ($subtest !== 'metadata' && is_array($score) && isset($score['raw_score'])): ?>
                <tr>
                    <td>Subtest <?php echo $subtest; ?></td>
                    <td><?php echo $score['raw_score']; ?></td>
                    <td><?php echo $score['weighted_score']; ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </table>
            
            <?php elseif ($testType == 'kraepelin'): ?>
            <div class="result-item">
                <h3>Hasil Test Kraepelin</h3>
                <p><strong>Total Benar:</strong> <?php echo $results['total_benar'] ?? 'N/A'; ?> dari 140 soal</p>
                <p><strong>Rata-rata per Kolom:</strong> <?php echo $results['rata_rata'] ?? 'N/A'; ?></p>
                <p><strong>Konsistensi (Standar Deviasi):</strong> <?php echo $results['konsistensi'] ?? 'N/A'; ?></p>
                <p><strong>Interpretasi:</strong> <?php echo $results['interpretasi'] ?? 'N/A'; ?></p>
            </div>

            <?php if (isset($results['per_kolom'])): ?>
            <div class="detailed-results">
                <h3>Detail per Kolom</h3>
                <table class="results-table">
                    <tr>
                        <th>Kolom</th>
                        <th>Jumlah Benar</th>
                        <th>Persentase</th>
                    </tr>
                    <?php foreach ($results['per_kolom'] as $kolom => $score): ?>
                    <tr>
                        <td>Kolom <?php echo $kolom; ?></td>
                        <td><?php echo $score; ?></td>
                        <td><?php echo round(($score / 14) * 100, 2); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>

            <?php elseif ($testType == 'pauli'): ?>
            <?php if (isset($results['per_bagian'])): ?>
            <h3>Per Bagian</h3>
            <table class="results-table">
                <tr>
                    <th>Bagian</th>
                    <th>Jumlah</th>
                    <th>Rata-rata</th>
                    <th>Fluktuasi</th>
                </tr>
                <?php foreach ($results['per_bagian'] as $bagian => $score): ?>
                <tr>
                    <td>Bagian <?php echo $bagian; ?></td>
                    <td><?php echo $score['jumlah']; ?></td>
                    <td><?php echo $score['rata_rata']; ?></td>
                    <td><?php echo $score['fluktuasi']; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
            
            <?php if (isset($results['total'])): ?>
            <h3>Hasil Keseluruhan</h3>
            <div class="result-item">
                <p><strong>Total Score:</strong> <?php echo $results['total']['score']; ?></p>
                <p><strong>Rata-rata:</strong> <?php echo $results['total']['rata_rata']; ?></p>
                <p><strong>Fluktuasi:</strong> <?php echo $results['total']['fluktuasi']; ?></p>
                <p><strong>Interpretasi:</strong> <?php echo $results['total']['interpretasi']; ?></p>
            </div>
            <?php endif; ?>

            <?php elseif ($testType == 'ist'): ?>
            <table class="results-table">
                <tr>
                    <th>Subtest</th>
                    <th>Raw Score</th>
                    <th>Weighted Score</th>
                    <th>Interpretasi</th>
                </tr>
                <?php foreach ($results as $subtest => $score): ?>
                <?php if ($subtest !== 'metadata' && is_array($score) && isset($score['raw_score'])): ?>
                <tr>
                    <td><?php echo $subtest; ?></td>
                    <td><?php echo $score['raw_score']; ?></td>
                    <td><?php echo $score['weighted_score']; ?></td>
                    <td><?php echo $score['interpretasi'] ?? 'N/A'; ?></td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        
        <?php if (($testType == 'tiki' || $testType == 'ist') && !empty($chartData)): ?>
        <div class="chart-section">
            <h2>Grafik Hasil Test</h2>
            <canvas id="resultChart" width="400" height="200"></canvas>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
        
        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn-back">Kembali ke Dashboard</a>
            
            <?php if ($testId && $testId > 0): ?>
            <a href="history.php?test=<?php echo $testType; ?>" class="btn-history">Lihat Riwayat Test</a>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (($testType == 'tiki' || $testType == 'ist') && !empty($chartData)): ?>
    <script>
        const chartData = <?php echo $chartDataJson; ?>;
        const ctx = document.getElementById('resultChart').getContext('2d');
        
        const labels = chartData.map(item => item.subtest);
        const data = chartData.map(item => item.weighted_score);
        
        const myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Weighted Score',
                    data: data,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Weighted Score'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Subtest'
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>