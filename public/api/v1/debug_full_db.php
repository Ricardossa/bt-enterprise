<?php
require_once __DIR__ . '/../../../bootstrap.php';
use BTQueue\Core\Database;
header('Content-Type: application/json');
try {
    $config = Database::fetchAll("SELECT * FROM configuracoes");
    $servicos = Database::fetchAll("SELECT * FROM servicos");
    $senhas = Database::fetchAll("SELECT * FROM senhas ORDER BY id DESC LIMIT 5");
    echo json_encode([
        'config' => $config,
        'servicos' => $servicos,
        'senhas' => $senhas
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); }
