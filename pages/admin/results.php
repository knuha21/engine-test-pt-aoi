<?php
require_once __DIR__ . '/../../bootstrap.php';

requireAdmin();

$testType = isset($_GET['test']) ? strtoupper(trim($_GET['test'])) : '';
$participantId = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $db = getDBConnection();
    
    $where = [];
    $params = [];
    
    if (!empty($testType)) {
        $where[] = "tr.test_type = :test_type";
        $params[':test_type'] = $testType;
    }
    
    if ($participantId > 0) {
        $where[] = "tr.participant_id = :participant_id";
        $params[':participant_id'] = $participantId;
    }
    
    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $countQuery = "SELECT COUNT(*) as total FROM test_results tr $whereClause";
    $stmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalResults = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalResults / $limit);
    
    $query = "SELECT tr.*, p.name as participant_name, p.email 
              FROM test_results tr 
              LEFT JOIN participants p ON tr.participant_id = p.id 
              $whereClause 
              ORDER BY tr.created_at DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as &$result) {
        $resultData = json_decode($result['results'], true);
        if (is_array($resultData)) {
            $result['results_data'] = $resultData;
        }
    }
    
    $participantsQuery = "SELECT id, name, email FROM participants ORDER BY name";
    $participantsStmt = $db->prepare($participantsQuery);
    $participantsStmt->execute();
    $allParticipants = $participantsStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error mengambil data hasil test: " . $e->getMessage();
    $results = [];
    $totalPages = 1;
    $allParticipants = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Hasil Test - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Kelola Hasil Test</h1>
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
        
        <div class="admin-section">
            <div class="filter-section">
                <h2>Filter Hasil Test</h2>
                <form method="GET" action="">
                    <div class="form-group">
                        <label>Jenis Test:</label>
                        <select name="test">
                            <option value="">Semua Test</option>
                            <option value="TIKI" <?php echo $testType == 'TIKI' ? 'selected' : ''; ?>>TIKI</option>
                            <option value="KRAEPELIN" <?php echo $testType == 'KRAEPELIN' ? 'selected' : ''; ?>>KRAEPELIN</option>
                            <option value="PAULI" <?php echo $testType == 'PAULI' ? 'selected' : ''; ?>>PAULI</option>
                            <option value="IST" <?php echo $testType == 'IST' ? 'selected' : ''; ?>>IST</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Peserta:</label>
                        <select name="participant_id">
                            <option value="0">Semua Peserta</option>
                            <?php foreach ($allParticipants as $participant): ?>
                            <option value="<?php echo $participant['id']; ?>" 
                                <?php echo $participantId == $participant['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($participant['name'] . ' (' . $participant['email'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-admin">Terapkan Filter</button>
                    <a href="results.php" class="btn-admin" style="background-color: #95a5a6;">Reset</a>
                </form>
            </div>
            
            <div class="results-section">
                <h2>Daftar Hasil Test (<?php echo $totalResults; ?>)</h2>
                
                <?php if (count($results) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>ID Test</th>
                        <th>Peserta</th>
                        <th>Jenis Test</th>
                        <th>Skor</th>
                        <th>Akurasi</th>
                        <th>Jawaban Benar</th>
                        <th>Tanggal Test</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($results as $result): 
                        $testData = isset($result['results_data']) ? $result['results_data'] : [];
                    ?>
                    <tr>
                        <td>#<?php echo $result['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($result['participant_name']); ?><br>
                            <small><?php echo htmlspecialchars($result['email']); ?></small>
                        </td>
                        <td><?php echo $result['test_type']; ?></td>
                        <td>
                            <?php if ($result['test_type'] === 'KRAEPELIN' && isset($testData['total_score'])): ?>
                                <?php echo $testData['total_score']; ?> poin
                            <?php elseif ($result['test_type'] === 'TIKI' && isset($testData['total_score'])): ?>
                                <?php echo $testData['total_score']; ?> poin
                            <?php else: ?>
                                Completed
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($testData['accuracy'])): ?>
                                <?php echo number_format($testData['accuracy'], 1); ?>%
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($testData['correct_answers']) && isset($testData['total_questions'])): ?>
                                <?php echo $testData['correct_answers']; ?>/<?php echo $testData['total_questions']; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($result['created_at'])); ?></td>
                        <td>
                            <a href="../results.php?test=<?php echo strtolower($result['test_type']); ?>&id=<?php echo $result['id']; ?>" 
                               class="btn-view" target="_blank">Lihat Hasil</a>
                            <a href="result_delete.php?id=<?php echo $result['id']; ?>" 
                               class="btn-logout" 
                               onclick="return confirm('Hapus hasil test ini?')">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&test=<?php echo urlencode($testType); ?>&participant_id=<?php echo $participantId; ?>" 
                       class="btn-filter <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <p>Tidak ada hasil test ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>