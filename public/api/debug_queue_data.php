<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');

try {
    echo "HORA PHP: " . date('Y-m-d H:i:s') . "\n";

    echo "--- ÚLTIMAS SENHAS REGISTRADAS ---\n";
    $res = Database::fetchAll("SELECT id, codigo, numero, created_at FROM senhas ORDER BY id DESC LIMIT 20");
    print_r($res);

    $hoje = date('Y-m-d');
    echo "\nCONTANDO SENHAS DE HOJE ($hoje)...\n";
    $count = Database::fetch("SELECT COUNT(*) as total FROM senhas WHERE date(created_at) = ?", [$hoje]);
    print_r($count);

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
