<?php
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

$testType = isset($_GET['test']) ? strtolower($_GET['test']) : 'all';

try {
    $db = getDBConnection();
    
    if ($testType == 'all') {
        $query = "SELECT * FROM test_results WHERE participant_id = :participant_id ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
    } else {
        $query = "SELECT * FROM test_results WHERE participant_id = :participant_id AND test_type = :test_type ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":participant_id", $_SESSION['participant_id']);
        $stmt->bindValue(":test_type", strtoupper($testType));
    }
    
    $stmt->execute();
    $testHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil riwayat test: " . $e->getMessage();
    $testHistory = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Riwayat Test</h1>
            <p>Daftar semua test yang telah dikerjakan</p>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="filter-section">
            <h2>Filter Berdasarkan Jenis Test</h2>
            <div class="filter-buttons">
                <a href="history.php?test=all" class="btn-filter <?php echo $testType == 'all' ? 'active' : ''; ?>">Semua Test</a>
                <a href="history.php?test=tiki" class="btn-filter <?php echo $testType == 'tiki' ? 'active' : ''; ?>">TIKI</a>
                <a href="history.php?test=kraepelin" class="btn-filter <?php echo $testType == 'kraepelin' ? 'active' : ''; ?>">KRAEPLIN</a>
                <a href="history.php?test=pauli" class="btn-filter <?php echo $testType == 'pauli' ? 'active' : ''; ?>">PAULI</a>
                <a href="history.php?test=ist" class="btn-filter <?php echo $testType == 'ist' ? 'active' : ''; ?>">IST</a>
            </div>
        </div>
        
        <div class="history-section">
            <h2>Daftar Test</h2>
            <?php if (count($testHistory) > 0): ?>
            <table class="history-table">
                <tr>
                    <th>Jenis Test</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>ID Test</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($testHistory as $test): ?>
                <tr>
                    <td><?php echo htmlspecialchars($test['test_type']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                    <td><?php echo date('H:i', strtotime($test['created_at'])); ?></td>
                    <td>#<?php echo $test['id']; ?></td>
                    <td>
                        <a href="results.php?test=<?php echo strtolower($test['test_type']); ?>&id=<?php echo $test['id']; ?>" class="btn-view">Lihat Hasil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>Belum ada riwayat test.</p>
            <?php endif; ?>
        </div>
        
        <div class="navigation-buttons">
            <a href="dashboard.php" class="btn-back">Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>