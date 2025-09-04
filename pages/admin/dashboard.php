<?php
require_once __DIR__ . '/../../bootstrap.php';

// Pastikan hanya admin yang bisa akses
requireAdmin();

// Jika bukan admin, redirect ke dashboard peserta
if (!isAdmin()) {
    header("Location: ../dashboard.php");
    exit();
}

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
    
    // Peserta terbaru (5 terbaru)
    $query = "SELECT name, email, created_at FROM participants WHERE role = 'peserta' ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['recent_participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Test terbaru (5 terbaru)
    $query = "SELECT tr.test_type, tr.created_at, p.name 
              FROM test_results tr 
              JOIN participants p ON tr.participant_id = p.id 
              ORDER BY tr.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['recent_tests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hitung test hari ini
    $query = "SELECT COUNT(*) as today_tests FROM test_results WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_tests'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_tests'];
    
    // Hitung peserta baru hari ini
    $query = "SELECT COUNT(*) as today_participants FROM participants WHERE DATE(created_at) = CURDATE() AND role = 'peserta'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['today_participants'] = $stmt->fetch(PDO::FETCH_ASSOC)['today_participants'];
    
} catch (PDOException $e) {
    $error = "Error mengambil data statistik: " . $e->getMessage();
    $stats = [
        'total_participants' => 0,
        'total_tests' => 0,
        'tests_by_type' => [],
        'recent_participants' => [],
        'recent_tests' => [],
        'today_tests' => 0,
        'today_participants' => 0
    ];
}

// Ambil data admin
try {
    $query = "SELECT * FROM participants WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_SESSION['participant_id']);
    $stmt->execute();
    $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $adminData = ['name' => 'Administrator', 'email' => 'N/A'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>Admin Dashboard</h1>
            <p>Panel Admin - PT. Apparel One Indonesia</p>
            <div class="user-info">
                <p>Selamat datang, <strong><?php echo htmlspecialchars($adminData['name']); ?></strong></p>
                <p>Email: <?php echo htmlspecialchars($adminData['email']); ?></p>
            </div>
            <div class="user-actions">
                <a href="../../logout.php" class="btn-logout">Logout</a>
                <a href="../profile.php" class="btn-profile">Edit Profil</a>
            </div>
        </header>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Overview -->
        <div class="admin-section">
            <h2>Overview Sistem</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>Total Peserta</h3>
                    <p style="font-size: 2.5em; font-weight: bold; color: #3498db; margin: 10px 0;">
                        <?php echo $stats['total_participants']; ?>
                    </p>
                    <p>+<?php echo $stats['today_participants']; ?> hari ini</p>
                </div>
                
                <div class="admin-card">
                    <h3>Total Test</h3>
                    <p style="font-size: 2.5em; font-weight: bold; color: #27ae60; margin: 10px 0;">
                        <?php echo $stats['total_tests']; ?>
                    </p>
                    <p>+<?php echo $stats['today_tests']; ?> hari ini</p>
                </div>
                
                <div class="admin-card">
                    <h3>Aktivitas Hari Ini</h3>
                    <p style="font-size: 1.2em; margin: 5px 0;">
                        🎯 Test: <?php echo $stats['today_tests']; ?>
                    </p>
                    <p style="font-size: 1.2em; margin: 5px 0;">
                        👥 Peserta Baru: <?php echo $stats['today_participants']; ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Test Statistics -->
        <div class="admin-section">
            <h2>Distribusi Jenis Test</h2>
            <div class="admin-cards">
                <?php foreach ($stats['tests_by_type'] as $test): ?>
                <div class="admin-card">
                    <h3><?php echo $test['test_type']; ?></h3>
                    <p style="font-size: 1.8em; font-weight: bold; color: #e67e22;">
                        <?php echo $test['count']; ?>
                    </p>
                    <p>Test Dikerjakan</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="admin-section">
            <h2>Menu Cepat</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>📊 Kelola Peserta</h3>
                    <p>Lihat dan kelola data peserta</p>
                    <a href="participants.php" class="btn-admin">Kelola Peserta</a>
                </div>
                
                <div class="admin-card">
                    <h3>📈 Kelola Hasil Test</h3>
                    <p>Lihat semua hasil test peserta</p>
                    <a href="results.php" class="btn-admin">Kelola Hasil</a>
                </div>
                
                <div class="admin-card">
                    <h3>⚙️ Kelola Norma</h3>
                    <p>Kelola data norma test</p>
                    <a href="norms.php" class="btn-admin">Kelola Norma</a>
                </div>

                <div class="admin-card">
                    <h3>👥 Daftar Peserta</h3>
                    <p>Lihat daftar peserta terbaru</p>
                    <a href="participants.php?sort=recent" class="btn-admin">Lihat Peserta</a>
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
                        <th>Aksi</th>
                    </tr>
                    <?php foreach ($stats['recent_participants'] as $participant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($participant['name']); ?></td>
                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($participant['created_at'])); ?></td>
                        <td>
                            <a href="participant_detail.php?email=<?php echo urlencode($participant['email']); ?>" 
                               class="btn-view">Detail</a>
                        </td>
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
                    <th>Skor</th>
                    <th>Tanggal Test</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($stats['recent_tests'] as $test): 
                    $testData = json_decode($test['results'], true);
                ?>
                <tr>
                    <td><?php echo $test['test_type']; ?></td>
                    <td><?php echo htmlspecialchars($test['name']); ?></td>
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
            <p>Belum ada test yang dikerjakan.</p>
            <?php endif; ?>
        </div>
        
        <!-- Admin Information -->
        <div class="test-instructions">
            <h2>📋 Panduan Administrator</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <h3>✅ Tugas Administrator</h3>
                    <ul>
                        <li>Mengelola data peserta dan hasil test</li>
                        <li>Memelihara data norma dan kunci jawaban</li>
                        <li>Memantau performa sistem testing</li>
                        <li>Memastikan kelancaran proses testing</li>
                    </ul>
                </div>
                <div>
                    <h3>ℹ️ Informasi Penting</h3>
                    <ul>
                        <li>Peserta mengerjakan test melalui login sendiri</li>
                        <li>Hasil test otomatis tersimpan di database</li>
                        <li>Data norma menentukan scoring system</li>
                        <li>Backup database secara berkala</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Simple real-time clock untuk dashboard
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                clockElement.innerHTML = `${dateString}<br>${timeString}`;
            }
        }
        
        // Update clock setiap detik
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
    </script>
</body>
</html>