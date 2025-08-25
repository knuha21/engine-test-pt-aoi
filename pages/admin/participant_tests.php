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
    
    // Ambil riwayat test peserta
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
    <title>Test Peserta - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Riwayat Test - <?php echo htmlspecialchars($participant['name']); ?></h1>
            <p>Admin Panel - PT. Apparel One Indonesia</p>
            <div class="user-actions">
                <a href="participant_detail.php?id=<?php echo $participantId; ?>" class="btn-back">← Detail Peserta</a>
                <a href="participants.php" class="btn-back">Daftar Peserta</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <!-- Informasi Peserta -->
            <div class="results-section">
                <h2>Informasi Peserta</h2>
                <table class="results-table">
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
                </table>
            </div>
            
            <!-- Riwayat Test -->
            <div class="results-section">
                <h2>Riwayat Test yang Telah Dikerjakan</h2>
                
                <?php if (count($testHistory) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>ID Test</th>
                        <th>Jenis Test</th>
                        <th>Tanggal Test</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($testHistory as $test): ?>
                    <tr>
                        <td>#<?php echo $test['id']; ?></td>
                        <td><?php echo $test['test_type']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                        <td><?php echo date('H:i', strtotime($test['created_at'])); ?></td>
                        <td>
                            <a href="../results.php?test=<?php echo strtolower($test['test_type']); ?>&id=<?php echo $test['id']; ?>" 
                               class="btn-view" target="_blank">Lihat Hasil</a>
                            <a href="result_delete.php?id=<?php echo $test['id']; ?>" 
                               class="btn-logout" 
                               onclick="return confirm('Hapus hasil test ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <div class="test-info">
                    <h3>Belum Ada Test yang Dikerjakan</h3>
                    <p>Peserta ini belum mengerjakan test apapun.</p>
                    <p>Peserta dapat login dan mengerjakan test melalui halaman dashboard peserta.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informasi untuk Admin -->
            <div class="test-instructions">
                <h2>Informasi untuk Admin</h2>
                <p>Sebagai administrator, Anda dapat:</p>
                <ul>
                    <li>Melihat hasil test yang telah dikerjakan peserta</li>
                    <li>Mengelola data norma dan kunci jawaban</li>
                    <li>Menghapus hasil test jika diperlukan</li>
                    <li>Memonitor performa peserta melalui hasil test</li>
                </ul>
                <p><strong>Peserta mengerjakan test melalui login sendiri di halaman dashboard peserta.</strong></p>
            </div>
        </div>
    </div>
</body>
</html>