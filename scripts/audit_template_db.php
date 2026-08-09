<?php
try {
    $dbPath = dirname(__DIR__) . '/database/banco_template.db';
    if (!file_exists($dbPath)) die("TEMPLATE NOT FOUND\n");
    $db = new PDO('sqlite:' . $dbPath);
    $stmt = $db->query("PRAGMA table_info(licencas)");
    echo "COLUMNS IN licencas (TEMPLATE):\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . "\n";
    }
} catch (Exception $e) { echo "ERROR: " . $e->getMessage(); }
?>
