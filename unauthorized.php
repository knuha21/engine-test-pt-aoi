<?php
require_once 'bootstrap.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Engine Alat Test</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .unauthorized-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .unauthorized-icon {
            font-size: 64px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        
        .unauthorized-container h1 {
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .unauthorized-container p {
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .btn-back:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="unauthorized-container">
        <div class="unauthorized-icon">🚫</div>
        <h1>Akses Ditolak</h1>
        <p>Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <p>Silakan hubungi administrator jika Anda memerlukan akses.</p>
        <a href="pages/dashboard.php" class="btn-back">Kembali ke Dashboard</a>
    </div>
</body>
</html>