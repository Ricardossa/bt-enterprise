<?php
try {
    $db = new PDO('sqlite:Y:/bt-enterprise/database/banco.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->query('PRAGMA table_info(senhas)');
    echo "COLUMNS IN senhas:\n";
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . " (" . $row['type'] . ")\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
