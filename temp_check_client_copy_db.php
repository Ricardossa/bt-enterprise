<?php
try {
    $dbPath = 'Y:/bt-enterprise/database/temp_client.db';
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query('PRAGMA table_info(senhas)');
    echo "COLUMNS IN senhas (CLIENT COPY):\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . " (" . $row['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
