<?php
define('ROOT_ACCESS', true);
// Gunakan require_once untuk menghindari multiple includes
require_once 'bootstrap.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
    exit();
}

// Proses pendaftaran
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (!empty($name) && !empty($email) && !empty($password)) {
        if ($password !== $confirm_password) {
            $error = "Password dan konfirmasi password tidak sama.";
        } else {
            try {
                $db = getDBConnection();
                
                // Cek apakah email sudah terdaftar
                $query = "SELECT id FROM participants WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":email", $email);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $error = "Email sudah terdaftar.";
                } else {
                    // Daftarkan peserta baru
                    $query = "INSERT INTO participants (name, email, password) VALUES (:name, :email, SHA1(:password))";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":name", $name);
                    $stmt->bindParam(":email", $email);
                    $stmt->bindParam(":password", $password);
                    
                    if ($stmt->execute()) {
                        $participant_id = $db->lastInsertId();
                        
                        // Auto login setelah registrasi
                        $_SESSION['participant_id'] = $participant_id;
                        $_SESSION['participant_name'] = $name;
                        $_SESSION['participant_email'] = $email;
                        $_SESSION['role'] = 'peserta';
                        
                        header("Location: pages/dashboard.php");
                        exit();
                    } else {
                        $error = "Terjadi kesalahan. Silakan coba lagi.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Error database: " . $e->getMessage();
            }
        }
    } else {
        $error = "Semua field harus diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Engine Alat Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h1>Registrasi Peserta</h1>
            <p>PT. Apparel One Indonesia</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="name">Nama Lengkap:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-register">Daftar</button>
        </form>
        
        <div class="registration-links">
            <p>Sudah punya akun? <a href="login.php">Login disini</a></p>
        </div>
    </div>
</body>
</html>