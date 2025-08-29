<?php

require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $db = getDBConnection();
    
    // Build query dengan search
    $where = '';
    $params = [];
    
    if (!empty($search)) {
        $where = "WHERE name LIKE :search OR email LIKE :search";
        $params[':search'] = "%$search%";
    }
    
    // Hitung total participants
    $countQuery = "SELECT COUNT(*) as total FROM participants $where";
    $stmt = $db->prepare($countQuery);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalParticipants = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalParticipants / $limit);
    
    // Ambil data participants
    $query = "SELECT * FROM participants $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error mengambil data peserta: " . $e->getMessage();
    $participants = [];
    $totalPages = 1;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Peserta - Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Kelola Peserta</h1>
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
            <!-- Search Form -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Cari nama atau email..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-admin">Cari</button>
                        <?php if (!empty($search)): ?>
                        <a href="participants.php" class="btn-admin" style="background-color: #95a5a6;">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Participants Table -->
            <div class="results-section">
                <h2>Daftar Peserta (<?php echo $totalParticipants; ?>)</h2>
                
                <?php if (count($participants) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Tanggal Daftar</th>
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($participants as $participant): ?>
                    <tr>
                        <td><?php echo $participant['id']; ?></td>
                        <td><?php echo htmlspecialchars($participant['name']); ?></td>
                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                        <td><?php echo ucfirst($participant['role']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($participant['created_at'])); ?></td>
                        <td>
                            <a href="participant_detail.php?id=<?php echo $participant['id']; ?>" class="btn-view">Detail</a>
                            <a href="participant_tests.php?id=<?php echo $participant['id']; ?>" class="btn-admin">Test</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn-filter <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
                
                <?php else: ?>
                <p>Tidak ada peserta ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>