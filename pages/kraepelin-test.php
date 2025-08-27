<?php
// Gunakan require_once untuk menghindari multiple includes
require_once __DIR__ . '/../bootstrap.php';

// Pastikan user sudah login
requireLogin();

// Ambil data peserta dari database
$db = getDBConnection();
$participant_id = $_SESSION['participant_id'];
$participant_query = $db->prepare("SELECT name, email FROM participants WHERE id = ?");
$participant_query->execute([$participant_id]);
$participant = $participant_query->fetch(PDO::FETCH_ASSOC);

// Jika tidak ada data peserta, redirect ke login
if (!$participant) {
    header("Location: login.php");
    exit();
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Pastikan class KraepelinTest ada
        if (!class_exists('KraepelinTest')) {
            throw new Exception('KraepelinTest class not found');
        }
        
        $kraepelinTest = new KraepelinTest();
        $jawaban = $_POST['answers'];
        $hasilOlahan = $kraepelinTest->prosesJawaban($jawaban);
        
        // Simpan ke database
        if ($kraepelinTest->simpanHasilTest($_SESSION['participant_id'], $hasilOlahan)) {
            // Redirect ke results
            header("Location: results.php?test=kraepelin");
            exit();
        } else {
            $error = "Gagal menyimpan hasil test. Silakan coba lagi.";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Data untuk test Kraepelin (deret angka)
$deret = [];
$jumlahKolom = 10;
$jumlahBaris = 10;

// Generate deret angka acak untuk test Kraepelin
for ($i = 0; $i < $jumlahBaris; $i++) {
    $baris = [];
    for ($j = 0; $j < $jumlahKolom; $j++) {
        $baris[] = rand(0, 9);
    }
    $deret[] = $baris;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kraepelin Test - PT. Apparel One Indonesia</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .test-info {
            background-color: #e8f4fc;
            padding: 15px 20px;
            border-bottom: 1px solid #bee5eb;
            font-size: 0.9rem;
        }
        
        .instructions {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .instructions h3 {
            color: #4e54c8;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .instructions ul {
            padding-left: 20px;
            margin-bottom: 10px;
        }
        
        .instructions li {
            margin-bottom: 5px;
        }
        
        .timer-container {
            background-color: #4e54c8;
            color: white;
            padding: 15px 20px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .test-content {
            padding: 25px;
            overflow-x: auto;
        }
        
        .kraepelin-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        
        .kraepelin-table th {
            background-color: #4e54c8;
            color: white;
            padding: 10px;
            text-align: center;
            position: sticky;
            top: 0;
        }
        
        .kraepelin-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
            height: 60px;
            vertical-align: middle;
        }
        
        .number-cell {
            background-color: #f8f9fa;
            font-size: 1.3rem;
            width: 60px;
        }
        
        .answer-cell {
            background-color: #fff;
            padding: 0;
            position: relative;
            width: 80px;
        }
        
        .answer-input-container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .answer-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 1.3rem;
            font-weight: bold;
            border: 2px solid #ddd;
            border-radius: 6px;
            -moz-appearance: textfield;
            appearance: textfield;
        }
        
        /* Hilangkan panah atas-bawah pada input number */
        .answer-input::-webkit-outer-spin-button,
        .answer-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .answer-input:focus {
            border-color: #4e54c8;
            outline: none;
            box-shadow: 0 0 0 2px rgba(78, 84, 200, 0.2);
        }
        
        .plus-sign {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1rem;
            color: #6c757d;
            background-color: white;
            padding: 0 5px;
            z-index: 1;
            font-weight: bold;
        }
        
        .navigation {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-submit {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            font-size: 1.1rem;
        }
        
        .btn-submit:hover {
            background-color: #218838;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin: 15px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
        
        .row-number {
            background-color: #4e54c8;
            color: white;
            font-weight: bold;
            width: 50px;
        }
        
        .operation-cell {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .kraepelin-table {
                font-size: 0.9rem;
            }
            
            .kraepelin-table td {
                padding: 6px;
            }
            
            .answer-input {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }
            
            .number-cell {
                font-size: 1.1rem;
                width: 50px;
            }
            
            .answer-cell {
                width: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Kraepelin Test</h1>
            <p class="subtitle">PT. Apparel One Indonesia</p>
        </header>
        
        <div class="test-info">
            <p><strong>Peserta:</strong> <?php echo htmlspecialchars($participant['name']); ?> | <strong>Email:</strong> <?php echo htmlspecialchars($participant['email']); ?></p>
        </div>
        
        <div class="instructions">
            <h3>Petunjuk Pengerjaan</h3>
            <ul>
                <li>Test Kraepelin mengukur kecepatan, ketelitian, dan ketahanan kerja</li>
                <li>Jumlahkan dua angka yang berdekatan dan tuliskan hasilnya di antara kedua angka tersebut</li>
                <li>Kerjakan secepat dan seteliti mungkin</li>
                <li>Waktu pengerjaan: 15 menit</li>
                <li>Jika hasil penjumlahan lebih dari 9, tulis hanya angka satuannya saja (contoh: 8+7=15, tulis 5)</li>
            </ul>
        </div>
        
        <?php if (isset($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <div class="timer-container">
            <div id="timer">Waktu: 15:00</div>
        </div>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="kraepelin-test-form">
            <div class="test-content">
                <table class="kraepelin-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <?php for ($i = 1; $i <= $jumlahKolom; $i++): ?>
                            <th>Kolom <?php echo $i; ?></th>
                            <?php if ($i < $jumlahKolom): ?>
                            <th>Jawaban <?php echo $i; ?></th>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < $jumlahBaris; $i++): ?>
                        <tr>
                            <td class="row-number"><?php echo $i + 1; ?></td>
                            <?php for ($j = 0; $j < $jumlahKolom; $j++): ?>
                            <td class="number-cell">
                                <?php echo $deret[$i][$j]; ?>
                            </td>
                            <?php if ($j < $jumlahKolom - 1): ?>
                            <td class="answer-cell">
                                <div class="answer-input-container">
                                    <span class="plus-sign">+</span>
                                    <input type="number" min="0" max="9" 
                                           name="answers[<?php echo $i; ?>][<?php echo $j; ?>]" 
                                           class="answer-input" required
                                           oninput="validateInput(this)"
                                           onkeydown="handleNavigation(this, event, <?php echo $i; ?>, <?php echo $j; ?>)">
                                </div>
                            </td>
                            <?php endif; ?>
                            <?php endfor; ?>
                        </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                
                <div class="navigation">
                    <button type="submit" class="btn-submit">Kirim Jawaban</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Timer untuk test Kraepelin (15 menit)
        let timeLeft = 15 * 60; // 15 menit dalam detik
        const timerElement = document.getElementById('timer');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            timerElement.textContent = `Waktu: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                // Waktu habis, submit form secara otomatis
                alert('Waktu telah habis! Jawaban akan disubmit secara otomatis.');
                document.getElementById('kraepelin-test-form').submit();
            }
        }
        
        // Validasi input
        function validateInput(input) {
            // Pastikan hanya angka 0-9
            if (input.value < 0) {
                input.value = 0;
            } else if (input.value > 9) {
                input.value = 9;
            }
            
            // Batasi hanya 1 digit
            if (input.value.length > 1) {
                input.value = input.value.slice(0, 1);
            }
            
            // Auto pindah ke input berikutnya
            if (input.value.length === 1) {
                const nextInput = findNextInput(input);
                if (nextInput) {
                    nextInput.focus();
                }
            }
        }
        
        // Handle navigasi dengan keyboard
        function handleNavigation(input, event, row, col) {
            let nextInput = null;
            
            switch(event.key) {
                case 'ArrowRight':
                    nextInput = findNextInput(input);
                    break;
                case 'ArrowLeft':
                    nextInput = findPrevInput(input);
                    break;
                case 'ArrowDown':
                    nextInput = findInputBelow(input, row, col);
                    break;
                case 'ArrowUp':
                    nextInput = findInputAbove(input, row, col);
                    break;
                case 'Enter':
                    nextInput = findNextInput(input);
                    break;
            }
            
            if (nextInput) {
                nextInput.focus();
                event.preventDefault();
            }
        }
        
        // Fungsi bantuan untuk navigasi
        function findNextInput(input) {
            const cell = input.closest('td');
            const nextCell = cell.nextElementSibling;
            if (nextCell) {
                const inputInNextCell = nextCell.querySelector('input');
                if (inputInNextCell) {
                    return inputInNextCell;
                } else {
                    // Lewati sel angka, cari sel input berikutnya
                    return findNextInput(nextCell);
                }
            } else {
                // Pindah ke baris berikutnya
                const row = input.closest('tr');
                const nextRow = row.nextElementSibling;
                if (nextRow) {
                    return nextRow.querySelector('input');
                }
            }
            return null;
        }
        
        function findPrevInput(input) {
            const cell = input.closest('td');
            const prevCell = cell.previousElementSibling;
            if (prevCell) {
                const inputInPrevCell = prevCell.querySelector('input');
                if (inputInPrevCell) {
                    return inputInPrevCell;
                } else {
                    // Lewati sel angka, cari sel input sebelumnya
                    return findPrevInput(prevCell);
                }
            } else {
                // Pindah ke baris sebelumnya
                const row = input.closest('tr');
                const prevRow = row.previousElementSibling;
                if (prevRow) {
                    const inputs = prevRow.querySelectorAll('input');
                    return inputs[inputs.length - 1];
                }
            }
            return null;
        }
        
        function findInputBelow(input, row, col) {
            const rowElement = input.closest('tr');
            const nextRow = rowElement.nextElementSibling;
            if (nextRow) {
                // Hitung indeks input yang sesuai berdasarkan kolom
                const inputs = nextRow.querySelectorAll('input');
                if (inputs[col]) {
                    return inputs[col];
                }
            }
            return null;
        }
        
        function findInputAbove(input, row, col) {
            const rowElement = input.closest('tr');
            const prevRow = rowElement.previousElementSibling;
            if (prevRow) {
                // Hitung indeks input yang sesuai berdasarkan kolom
                const inputs = prevRow.querySelectorAll('input');
                if (inputs[col]) {
                    return inputs[col];
                }
            }
            return null;
        }
        
        // Mencegah form submit dengan menekan Enter
        document.getElementById('kraepelin-test-form').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
        
        // Mulai timer ketika halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateTimer();
            
            // Focus ke input pertama
            const firstInput = document.querySelector('.answer-input');
            if (firstInput) {
                firstInput.focus();
            }
        });
    </script>
</body>
</html>