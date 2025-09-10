<?php
// debug_post.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>DEBUG POST DATA</h1>";
    echo "<pre>";
    echo "POST Data:\n";
    print_r($_POST);
    echo "\nAnswers Structure:\n";
    if (isset($_POST['answers'])) {
        print_r($_POST['answers']);
        echo "\nCount: " . count($_POST['answers']) . " rows\n";
        foreach ($_POST['answers'] as $row => $cols) {
            echo "Row $row: " . count($cols) . " columns\n";
        }
    }
    echo "</pre>";
    exit();
}
?>