<?php
try {
    $dbPath = 'Y:/bt-enterprise/database/banco.db';
    $db = new PDO('sqlite:' . $dbPath);
    echo "--- AUDITORIA BANCO VM (Y:) ---\n";
    $ops = $db->query("SELECT nome FROM operadores")->fetchAll(PDO::FETCH_ASSOC);
    echo "Operadores na VM:\n";
    print_r($ops);
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
