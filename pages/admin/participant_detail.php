<?php
require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

$participantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($participantId === 0) {
    header("Location: participants.php");
    exit();
}

try {
    $db = getDBConnection();
    
    // Ambil data participant
    $query = "SELECT * FROM participants WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $participantId);
    $stmt->execute();
    
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        header("Location: participants.php");
        exit();
    }
    
    // Ambil test history participant
    $testsQuery = "SELECT * FROM test_results WHERE participant_id = :id ORDER BY created_at DESC";
    $testsStmt = $db->prepare($testsQuery);
    $testsStmt->bindParam(":id", $participantId);
    $testsStmt->execute();
    
    $testHistory = $testsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error mengambil data peserta: " . $e->getMessage();
    $participant = [];
    $testHistory = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peserta - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Detail Peserta</h1>
            <p>Admin Panel - PT. Apparel One Indonesia</p>
            <div class="user-actions">
                <a href="participants.php" class="btn-back">← Kembali</a>
                <a href="../dashboard.php" class="btn-back">Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <!-- Participant Info -->
            <div class="results-section">
                <h2>Informasi Peserta</h2>
                <table class="results-table">
                    <tr>
                        <th>ID</th>
                        <td><?php echo $participant['id']; ?></td>
                    </tr>
                    <tr>
                        <th>Nama</th>
                        <td><?php echo htmlspecialchars($participant['name']); ?></td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                    </tr>
                    <tr>
                        <th>Role</th>
                        <td><?php echo ucfirst($participant['role']); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Daftar</th>
                        <td><?php echo date('d/m/Y H:i', strtotime($participant['created_at'])); ?></td>
                    </tr>
                </table>
            </div>
            
            <!-- Test History -->
            <div class="results-section">
                <h2>Riwayat Test</h2>
                
                <?php if (count($testHistory) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>ID Test</th>
                        <th>Jenis Test</th>
                        <th>Tanggal Test</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($testHistory as $test): ?>
                    <tr>
                        <td>#<?php echo $test['id']; ?></td>
                        <td><?php echo $test['test_type']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($test['created_at'])); ?></td>
                        <td>
                            <a href="../results.php?test=<?php echo strtolower($test['test_type']); ?>&id=<?php echo $test['id']; ?>" 
                               class="btn-view" target="_blank">Lihat Hasil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <p>Belum ada riwayat test.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>