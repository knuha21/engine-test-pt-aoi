<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Get database connection
$db = getDBConnection();

// Ambil data peserta
try {
    $query = "SELECT * FROM participants WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_SESSION['participant_id']);
    $stmt->execute();
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $dashboard_error = "Error mengambil data peserta: " . $e->getMessage();
    $participant = ['name' => 'Peserta', 'email' => 'N/A', 'role' => 'peserta'];
}

// Ambil riwayat test
try {
    $query = "SELECT * FROM test_results WHERE participant_id = :id ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_SESSION['participant_id']);
    $stmt->execute();
    $testHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $test_history_error = "Error mengambil riwayat test: " . $e->getMessage();
    $testHistory = [];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Dashboard <?php echo isAdmin() ? 'Admin' : 'Peserta'; ?></h1>
            <p>Selamat datang, <?php echo htmlspecialchars($participant['name']); ?>!</p>
            <p>Email: <?php echo htmlspecialchars($participant['email']); ?></p>
            <p>Role: <?php echo ucfirst($participant['role']); ?></p>
            <div class="user-actions">
                <a href="../logout.php" class="btn-logout">Logout</a>
                <?php if (!isAdmin()): ?>
                <a href="../profile.php" class="btn-profile">Edit Profil</a>
                <?php endif; ?>
            </div>
        </header>
        
        <?php if (isset($dashboard_error)): ?>
        <div class="error-message">
            <?php echo $dashboard_error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($test_history_error)): ?>
        <div class="error-message">
            <?php echo $test_history_error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isAdmin()): ?>
        <div class="admin-section">
            <h2>Menu Admin</h2>
            <div class="admin-cards">
                <div class="admin-card">
                    <h3>Dashboard Admin</h3>
                    <p>Panel administrasi lengkap</p>
                    <a href="admin/dashboard.php" class="btn-admin">Dashboard Admin</a>
                </div>

                <div class="admin-card">
                    <h3>Kelola Peserta</h3>
                    <p>Lihat dan kelola data peserta</p>
                    <a href="admin/participants.php" class="btn-admin">Kelola</a>
                </div>

                <div class="admin-card">
                    <h3>Kelola Hasil Test</h3>
                    <p>Lihat semua hasil test peserta</p>
                    <a href="admin/results.php" class="btn-admin">Kelola</a>
                </div>

                <div class="admin-card">
                    <h3>Kelola Norma</h3>
                    <p>Kelola data norma test</p>
                    <a href="admin/norms.php" class="btn-admin">Kelola</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!isAdmin()): ?>
        <div class="dashboard-section">
            <h2>Pilih Jenis Test</h2>
            <div class="test-cards">
                <div class="test-card">
                    <h3>TIKI Test</h3>
                    <p>Alat test kecerdasan, hasil test berupa IQ</p>
                    <a href="tiki-test.php" class="btn-test">Mulai Test</a>
                </div>
                
                <div class="test-card">
                    <h3>KRAEPELIN Test</h3>
                    <p>Alat tes untuk menguji kinerja dan kecepatan</p>
                    <a href="kraepelin-test.php" class="btn-test">Mulai Test</a>
                </div>
                
                <div class="test-card">
                    <h3>PAULI Test</h3>
                    <p>Alat tes kepribadian dan daya tahan</p>
                    <a href="pauli-test.php" class="btn-test">Mulai Test</a>
                </div>
                
                <div class="test-card">
                    <h3>IST Test</h3>
                    <p>Alat tes untuk memetakan struktur kecerdasan</p>
                    <a href="ist-test.php" class="btn-test">Mulai Test</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!isAdmin()): ?>
        <div class="history-section">
            <h2>Riwayat Test Terbaru</h2>
            <?php if (count($testHistory) > 0): ?>
            <table class="history-table">
                <tr>
                    <th>Jenis Test</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Aksi</th>
                </tr>
                <?php foreach ($testHistory as $test): ?>
                <tr>
                    <td><?php echo htmlspecialchars($test['test_type']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($test['created_at'])); ?></td>
                    <td><?php echo date('H:i', strtotime($test['created_at'])); ?></td>
                    <td>
                        <a href="results.php?test=<?php echo strtolower($test['test_type']); ?>&id=<?php echo $test['id']; ?>" class="btn-view">Lihat Hasil</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
            <p>Belum ada riwayat test.</p>
            <?php endif; ?>
            
            <?php if (count($testHistory) > 0): ?>
            <div style="margin-top: 20px; text-align: center;">
                <a href="history.php" class="btn-history">Lihat Semua Riwayat Test</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>