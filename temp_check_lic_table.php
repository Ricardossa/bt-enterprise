<?php
try {
    $db = new PDO('sqlite:Y:/bt-enterprise/database/banco.db');
    $stmt = $db->query('PRAGMA table_info(licencas)');
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo "- " . $row['name'] . "\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
?>
