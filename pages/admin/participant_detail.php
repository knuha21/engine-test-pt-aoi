<?php
require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

$participantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$participantEmail = isset($_GET['email']) ? trim($_GET['email']) : '';

if ($participantId === 0 && empty($participantEmail)) {
    header("Location: participants.php");
    exit();
}

try {
    $db = getDBConnection();
    
    if ($participantId === 0 && !empty($participantEmail)) {
        // Cari by email
        $query = "SELECT * FROM participants WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":email", $participantEmail);
        $stmt->execute();
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($participant) {
            $participantId = $participant['id'];
        }
    } else {
        // Ambil data participant by ID
        $query = "SELECT * FROM participants WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $participantId);
        $stmt->execute();
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
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
                    <th>Skor</th>
                    <th>Tanggal Test</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($testHistory as $test): 
                    $testData = json_decode($test['results'], true);
                ?>
                <tr>
                    <td>#<?php echo $test['id']; ?></td>
                    <td><?php echo $test['test_type']; ?></td>
                    <td>
                        <?php 
                        if ($test['test_type'] === 'KRAEPELIN' && isset($testData['total_score'])) {
                            echo $testData['total_score'] . ' poin';
                        } elseif ($test['test_type'] === 'TIKI' && isset($testData['total_score'])) {
                            echo $testData['total_score'] . ' poin';
                        } else {
                            echo 'Completed';
                        }
                        ?>
                    </td>
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
</body>
</html>