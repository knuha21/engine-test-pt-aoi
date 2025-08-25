<?php
require_once 'bootstrap.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: pages/dashboard.php");
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
                
                header("Location: pages/dashboard.php");
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
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #34495e;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .btn-login:hover {
            background-color: #2980b9;
        }
        
        .login-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .login-links a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
        
        .demo-accounts {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .demo-accounts h3 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
    </style>
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