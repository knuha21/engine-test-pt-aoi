<?php
// debug_session.php
session_start();
echo "<h1>DEBUG SESSION DATA</h1>";
echo "<pre>";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['kraepelin_deret'])) {
    echo "<h2>Kraepelin Deret</h2>";
    echo "<table border='1'>";
    foreach ($_SESSION['kraepelin_deret'] as $row => $numbers) {
        echo "<tr>";
        echo "<td><strong>Row $row:</strong></td>";
        foreach ($numbers as $number) {
            echo "<td>$number</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>