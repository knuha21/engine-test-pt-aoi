<?php
require_once 'bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Ambil data user
try {
    $db = getDBConnection();
    $query = "SELECT * FROM participants WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $_SESSION['participant_id']);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error mengambil data user: " . $e->getMessage();
    $user = [];
}

// Proses update profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    try {
        // Verifikasi password saat ini jika ingin ganti password
        if (!empty($new_password)) {
            $query = "SELECT id FROM participants WHERE id = :id AND password = SHA1(:password)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":id", $_SESSION['participant_id']);
            $stmt->bindParam(":password", $current_password);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                $error = "Password saat ini salah.";
            } elseif ($new_password !== $confirm_password) {
                $error = "Password baru dan konfirmasi password tidak sama.";
            }
        }
        
        if (!isset($error)) {
            // Update data
            if (!empty($new_password)) {
                $query = "UPDATE participants SET name = :name, email = :email, password = SHA1(:new_password) WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":new_password", $new_password);
            } else {
                $query = "UPDATE participants SET name = :name, email = :email WHERE id = :id";
                $stmt = $db->prepare($query);
            }
            
            $stmt->bindParam(":name", $name);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":id", $_SESSION['participant_id']);
            
            if ($stmt->execute()) {
                $_SESSION['participant_name'] = $name;
                $_SESSION['participant_email'] = $email;
                $success = "Profil berhasil diperbarui.";
            } else {
                $error = "Gagal memperbarui profil.";
            }
        }
    } catch (PDOException $e) {
        $error = "Error database: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Engine Alat Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Edit Profil</h1>
            <p>Perbarui informasi akun Anda</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
        <div class="success-message">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Nama Lengkap:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="current_password">Password Saat Ini (jika ingin ganti password):</label>
                <input type="password" id="current_password" name="current_password">
            </div>
            
            <div class="form-group">
                <label for="new_password">Password Baru:</label>
                <input type="password" id="new_password" name="new_password">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password Baru:</label>
                <input type="password" id="confirm_password" name="confirm_password">
            </div>
            
            <button type="submit" class="btn-update">Perbarui Profil</button>
        </form>
        
        <div class="profile-links">
            <a href="pages/dashboard.php">Kembali ke Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>