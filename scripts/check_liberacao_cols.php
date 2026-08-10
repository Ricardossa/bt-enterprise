<?php
require_once __DIR__ . '/../bootstrap.php';
use BTQueue\Core\Database;

try {
    echo "--- CHECKING agenda_regras COLUMNS ---\n";
    $cols = Database::fetchAll("PRAGMA table_info(agenda_regras)");
    $names = array_column($cols, 'name');
    print_r($names);
} catch (Exception $e) { echo "ERRO: " . $e->getMessage(); }
?>
