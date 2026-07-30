<?php
require_once __DIR__ . '/../../bootstrap.php';
use BTQueue\Core\Database;

header('Content-Type: text/plain');

try {
    echo "HORA PHP: " . date('Y-m-d H:i:s') . "\n";
    echo "TIMEZONE: " . date_default_timezone_get() . "\n\n";

    echo "--- ÚLTIMAS 10 SENHAS ---\n";
    $res = Database::fetchAll("SELECT id, codigo, numero, created_at, date(created_at) as data_db FROM senhas ORDER BY id DESC LIMIT 10");
    print_r($res);

    $hoje = date('Y-m-d');
    echo "\nFILTRANDO POR HOJE ($hoje)...\n";
    $ultima = Database::fetch(
        "SELECT id, numero, created_at FROM senhas
         WHERE date(created_at) = ?
         ORDER BY numero DESC LIMIT 1",
        [$hoje]
    );
    print_r($ultima);

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
