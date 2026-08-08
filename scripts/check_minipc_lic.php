<?php
try {
    $db = new PDO('sqlite:W:/BTQueue/database/banco.db');
    $lic = $db->query("SELECT * FROM licencas LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    echo "--- LICENSE DATA IN MINI PC ---\n";
    if ($lic) {
        print_r($lic);
    } else {
        echo "No license record found.\n";
    }

    $lockFile = 'W:/BTQueue/database/.installed';
    echo "\n.installed EXISTS: " . (file_exists($lockFile) ? "YES" : "NO") . "\n";

} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>
