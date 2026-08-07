<?php
try {
    $dbPath = 'W:/BTQueue/database/banco.db';
    if (!file_exists($dbPath)) {
        die("FILE NOT FOUND: $dbPath");
    }
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query('PRAGMA table_info(senhas)');
    echo "COLUMNS IN senhas (LIVE CLIENT):\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . " (" . $row['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
