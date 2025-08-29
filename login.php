<?php
define('ROOT_ACCESS', true);
require_once 'bootstrap.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: pages/admin/dashboard.php");
    } else {
        header("Location: pages/dashboard.php");
    }
    exit();
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (!empty($email) && !empty($password)) {
        try {
            $db = getDBConnection();
            
            // Cek user di database
            $query = "SELECT * FROM participants WHERE email = :email AND password = SHA1(:password)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":password", $password);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Set session
                $_SESSION['participant_id'] = $user['id'];
                $_SESSION['participant_name'] = $user['name'];
                $_SESSION['participant_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect berdasarkan role
                if ($user['role'] === 'admin') {
                    header("Location: pages/admin/dashboard.php");
                } else {
                    header("Location: pages/dashboard.php");
                }
                exit();
            } else {
                $error = "Email atau password salah.";
            }
        } catch (PDOException $e) {
            $error = "Error database: " . $e->getMessage();
        }
    } else {
        $error = "Email dan password harus diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Engine Alat Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>ENGINE ALAT TEST</h1>
            <p>PT. Apparel One Indonesia</p>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <div class="login-links">
            <p>Belum punya akun? <a href="index.php">Daftar disini</a></p>
        </div>
        
        <div class="demo-accounts">
            <h3>Akun Demo:</h3>
            <p><strong>Admin:</strong> admin@test.com / admin123</p>
            <p><strong>Peserta:</strong> Daftar melalui halaman registrasi</p>
        </div>
    </div>
</body>
</html>