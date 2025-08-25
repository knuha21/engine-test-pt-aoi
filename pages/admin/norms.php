<?php
require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

$testType = isset($_GET['test']) ? strtoupper(trim($_GET['test'])) : 'TIKI';
$message = '';
$error = '';

// Handle form submission untuk update norms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = getDBConnection();
        
        $testTypePost = $_POST['test_type'];
        $action = $_POST['action'];
        
        if ($action == 'update') {
            // Update existing norm
            $query = "UPDATE {$testTypePost}_norms SET 
                     correct_answer = :correct_answer, 
                     weighted_score = :weighted_score,
                     interpretation = :interpretation
                     WHERE subtest = :subtest AND question_number = :question_number";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":correct_answer", $_POST['correct_answer']);
            $stmt->bindParam(":weighted_score", $_POST['weighted_score']);
            $stmt->bindParam(":interpretation", $_POST['interpretation']);
            $stmt->bindParam(":subtest", $_POST['subtest']);
            $stmt->bindParam(":question_number", $_POST['question_number']);
            
            if ($stmt->execute()) {
                $message = "Norma berhasil diperbarui!";
            }
            
        } elseif ($action == 'add') {
            // Add new norm
            $query = "INSERT INTO {$testTypePost}_norms 
                     (subtest, question_number, correct_answer, weighted_score, interpretation) 
                     VALUES (:subtest, :question_number, :correct_answer, :weighted_score, :interpretation)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":subtest", $_POST['subtest']);
            $stmt->bindParam(":question_number", $_POST['question_number']);
            $stmt->bindParam(":correct_answer", $_POST['correct_answer']);
            $stmt->bindParam(":weighted_score", $_POST['weighted_score']);
            $stmt->bindParam(":interpretation", $_POST['interpretation']);
            
            if ($stmt->execute()) {
                $message = "Norma berhasil ditambahkan!";
            }
        }
        
    } catch (PDOException $e) {
        $error = "Error mengelola norma: " . $e->getMessage();
    }
}

try {
    $db = getDBConnection();
    
    // Ambil data norms berdasarkan test type
    $query = "SELECT * FROM {$testType}_norms ORDER BY subtest, question_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $norms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by subtest untuk organized display
    $groupedNorms = [];
    foreach ($norms as $norm) {
        $groupedNorms[$norm['subtest']][] = $norm;
    }
    
} catch (PDOException $e) {
    $error = "Error mengambil data norma: " . $e->getMessage();
    $norms = [];
    $groupedNorms = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Norma - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Kelola Norma Test</h1>
            <p>Admin Panel - PT. Apparel One Indonesia</p>
            <div class="user-actions">
                <a href="../dashboard.php" class="btn-back">← Dashboard</a>
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
        <div class="success-message">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <!-- Test Type Selection -->
            <div class="filter-section">
                <h2>Pilih Jenis Test</h2>
                <div class="filter-buttons">
                    <a href="?test=TIKI" class="btn-filter <?php echo $testType == 'TIKI' ? 'active' : ''; ?>">TIKI</a>
                    <a href="?test=KRAEPELIN" class="btn-filter <?php echo $testType == 'KRAEPELIN' ? 'active' : ''; ?>">KRAEPELIN</a>
                    <a href="?test=PAULI" class="btn-filter <?php echo $testType == 'PAULI' ? 'active' : ''; ?>">PAULI</a>
                    <a href="?test=IST" class="btn-filter <?php echo $testType == 'IST' ? 'active' : ''; ?>">IST</a>
                </div>
            </div>
            
            <!-- Add New Norm Form -->
            <div class="results-section">
                <h2>Tambah Norma Baru</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="test_type" value="<?php echo $testType; ?>">
                    
                    <div class="form-group">
                        <label>Subtest:</label>
                        <input type="text" name="subtest" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nomor Soal:</label>
                        <input type="number" name="question_number" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jawaban Benar:</label>
                        <input type="text" name="correct_answer" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Weighted Score:</label>
                        <input type="number" name="weighted_score" step="0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Interpretasi:</label>
                        <textarea name="interpretation" rows="3"></textarea>
                    </div>
                    
                    <button type="submit" class="btn-admin">Tambah Norma</button>
                </form>
            </div>
            
            <!-- Norms Table -->
            <div class="results-section">
                <h2>Daftar Norma <?php echo $testType; ?></h2>
                
                <?php if (count($groupedNorms) > 0): ?>
                <?php foreach ($groupedNorms as $subtest => $norms): ?>
                <h3>Subtest <?php echo $subtest; ?></h3>
                <table class="results-table">
                    <tr>
                        <th>No Soal</th>
                        <th>Jawaban Benar</th>
                        <th>Weighted Score</th>
                        <th>Interpretasi</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($norms as $norm): ?>
                    <tr>
                        <td><?php echo $norm['question_number']; ?></td>
                        <td><?php echo htmlspecialchars($norm['correct_answer']); ?></td>
                        <td><?php echo $norm['weighted_score']; ?></td>
                        <td><?php echo htmlspecialchars($norm['interpretation']); ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="test_type" value="<?php echo $testType; ?>">
                                <input type="hidden" name="subtest" value="<?php echo $norm['subtest']; ?>">
                                <input type="hidden" name="question_number" value="<?php echo $norm['question_number']; ?>">
                                
                                <input type="text" name="correct_answer" value="<?php echo htmlspecialchars($norm['correct_answer']); ?>" 
                                       style="width: 50px; margin-bottom: 5px;">
                                <br>
                                <input type="number" name="weighted_score" value="<?php echo $norm['weighted_score']; ?>" 
                                       step="0.1" style="width: 70px; margin-bottom: 5px;">
                                <br>
                                <button type="submit" class="btn-admin" style="padding: 5px 10px; font-size: 12px;">Update</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endforeach; ?>
                <?php else: ?>
                <p>Tidak ada norma ditemukan untuk <?php echo $testType; ?>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>