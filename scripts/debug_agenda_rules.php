<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "--- DEBUG AGENDA RULES ---\n";
    $rules = Database::fetchAll("SELECT * FROM agenda_regras");
    print_r($rules);

    echo "\nDay of week today: " . date('w') . " (" . date('l') . ")\n";
    echo "Current time: " . date('H:i') . "\n";
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
