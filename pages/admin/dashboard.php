<?php
require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

try {
    $db = getDBConnection();
    
    // Stats untuk dashboard
    $stats = [];
    
    // Total peserta
    $query = "SELECT COUNT(*) as total FROM participants WHERE role = 'peserta'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_participants'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total test yang telah dikerjakan
    $query = "SELECT COUNT(*) as total FROM test_results";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_tests'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Test per jenis
    $query = "SELECT test_type, COUNT(*) as count FROM test_results GROUP BY test_type";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['tests_by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Peserta terbaru
    $query = "SELECT name, email, created_at FROM participants WHERE role = 'peserta' ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['recent_participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test terbaru
    $query = "SELECT tr.test_type, tr.created_at, p.name 
              FROM test_results tr 
              JOIN participants p ON tr.participant_id = p.id 
              ORDER BY tr.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['recent_tests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error mengambil data statistik: " . $e->getMessage();
    $stats = [
        'total_participants' => 0,
        'total_tests' => 0,
        'tests_by_type' => [],
        'recent_participants' => [],
        'recent_tests' => []
    ];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <p>Panel Administrasi - PT. Apparel One Indonesia</p>
            <div class="user-actions">
                <a href="../../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="admin-section">
            <h2>Statistik Sistem</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>Total Peserta</h3>
                    <p style="font-size: 2em; font-weight: bold; color: #3498db;">
                        <?php echo $stats['total_participants']; ?>
                    </p>
                    <p>Peserta terdaftar</p>
                </div>
                
                <div class="admin-card">
                    <h3>Total Test</h3>
                    <p style="font-size: 2em; font-weight: bold; color: #27ae60;">
                        <?php echo $stats['total_tests']; ?>
                    </p>
                    <p>Test yang telah dikerjakan</p>
                </div>
                
                <div class="admin-card">
                    <h3>Test per Jenis</h3>
                    <?php foreach ($stats['tests_by_type'] as $test): ?>
                    <p><?php echo $test['test_type']; ?>: <?php echo $test['count']; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Admin Menu -->
        <div class="admin-section">
            <h2>Menu Administrasi</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>Kelola Peserta</h3>
                    <p>Lihat dan kelola data peserta</p>
                    <a href="participants.php" class="btn-admin">Kelola Peserta</a>
                </div>
                
                <div class="admin-card">
                    <h3>Kelola Hasil Test</h3>
                    <p>Lihat semua hasil test peserta</p>
                    <a href="results.php" class="btn-admin">Kelola Hasil</a>
                </div>
                
                <div class="admin-card">
                    <h3>Kelola Norma</h3>
                    <p>Kelola data norma test</p>
                    <a href="norms.php" class="btn-admin">Kelola Norma</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="admin-section">
            <div class="results-section">
                <h2>Peserta Terbaru</h2>
                <?php if (count($stats['recent_participants']) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Tanggal Daftar</th>
                    </tr>
                    <?php foreach ($stats['recent_participants'] as $participant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($participant['name']); ?></td>
                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($participant['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <p>Belum ada peserta terdaftar.</p>
                <?php endif; ?>
            </div>
            
            <div class="results-section">
                <h2>Test Terbaru</h2>
                <?php if (count($stats['recent_tests']) > 0): ?>
                <table class="results-table">
                    <tr>
                        <th>Jenis Test</th>
                        <th>Peserta</th>
                        <th>Tanggal Test</th>
                    </tr>
                    <?php foreach ($stats['recent_tests'] as $test): ?>
                    <tr>
                        <td><?php echo $test['test_type']; ?></td>
                        <td><?php echo htmlspecialchars($test['name']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($test['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php else: ?>
                <p>Belum ada test yang dikerjakan.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Admin Information -->
        <div class="test-instructions">
            <h2>Peran Administrator</h2>
            <p>Sebagai administrator, Anda bertanggung jawab untuk:</p>
            <ul>
                <li>Mengelola data peserta dan hasil test</li>
                <li>Memelihara data norma dan kunci jawaban</li>
                <li>Memantau performa sistem</li>
                <li>Memastikan kelancaran proses testing</li>
            </ul>
            <p><strong>Catatan:</strong> Peserta mengerjakan test melalui login sendiri di halaman dashboard peserta.</p>
        </div>
    </div>
</body>
</html>